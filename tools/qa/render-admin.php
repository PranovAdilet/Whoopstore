<?php
/**
 * Рендер одного экрана админки без браузера.
 *
 * Тот же приём, что у tools/qa/render.php для витрины: скрипт подставляет
 * окружение запроса и подключает файл админки. Отличие одно — админка
 * требует входа, поэтому процесс выдаёт себе куку администратора: создаёт
 * сессионный токен, собирает из него штатную куку авторизации и кладёт в
 * $_COOKIE. Наружу она не уходит и живёт только внутри процесса.
 *
 * Всё выполняется в транзакции с откатом: экраны админки по дороге пишут
 * transient'ы, заметки WooCommerce и сам сессионный токен — после прогона
 * в базе не остаётся ничего.
 *
 * Запуск (обычно через tools/qa/admin.php):
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/render-admin.php "/wp-admin/edit.php?post_type=product" > screen.html
 *
 * В stderr — строка состояния: редирект, число замечаний PHP и сами
 * замечания.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$root = dirname( __DIR__, 2 );
$uri  = isset( $argv[1] ) ? $argv[1] : '/wp-admin/';
$path = (string) parse_url( $uri, PHP_URL_PATH );
$file = $root . '/' . ltrim( $path, '/' );

if ( 0 !== strpos( realpath( dirname( $file ) ) ?: '', $root . '/wp-admin' ) || ! is_file( $file ) ) {
	fwrite( STDERR, "WHOOPSTORE-QA-ADMIN uri={$uri} error=нет такого экрана\n" );
	exit( 1 );
}

$query = (string) parse_url( $uri, PHP_URL_QUERY );
parse_str( $query, $_GET );
$_REQUEST = $_GET;

$_SERVER['HTTP_HOST']       = getenv( 'WHOOPSTORE_QA_HOST' ) ?: 'whoop.1c2.ru';
$_SERVER['SERVER_NAME']     = $_SERVER['HTTP_HOST'];
$_SERVER['REQUEST_URI']     = $uri;
$_SERVER['QUERY_STRING']    = $query;
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['HTTPS']           = 'on';
$_SERVER['SCRIPT_NAME']     = $path;
$_SERVER['SCRIPT_FILENAME'] = $file;
$_SERVER['PHP_SELF']        = $path;

$whoopstore_qa = [
	'uri'      => $uri,
	'redirect' => '',
	'notices'  => [],
];

// Замечания PHP собираем сами: на стенде WP_DEBUG выключен.
set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$whoopstore_qa ) {
	$whoopstore_qa['notices'][] = sprintf( '[%d] %s в %s:%d', $errno, $errstr, str_replace( dirname( __DIR__, 2 ) . '/', '', $errfile ), $errline );

	return false;
} );

register_shutdown_function( function () use ( &$whoopstore_qa ) {
	$fatal = error_get_last();

	if ( $fatal && in_array( $fatal['type'], [ E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR ], true ) ) {
		$whoopstore_qa['notices'][] = sprintf( '[fatal] %s в %s:%d', $fatal['message'], $fatal['file'], $fatal['line'] );
	}

	fwrite( STDERR, sprintf(
		"WHOOPSTORE-QA-ADMIN uri=%s redirect=%s notices=%d\n",
		$whoopstore_qa['uri'],
		'' !== $whoopstore_qa['redirect'] ? $whoopstore_qa['redirect'] : '-',
		count( $whoopstore_qa['notices'] )
	) );

	foreach ( array_unique( $whoopstore_qa['notices'] ) as $notice ) {
		fwrite( STDERR, '  ' . $notice . "\n" );
	}
} );

chdir( $root );

// Как wp-admin/admin.php: константа объявляется до загрузки WordPress, иначе
// плагины не подключат свою админскую часть (у WooCommerce без неё экраны
// заказов падают на собственных функциях).
define( 'WP_ADMIN', true );

require $root . '/wp-load.php';

global $wpdb;

$wpdb->query( 'START TRANSACTION' );
register_shutdown_function( function () use ( $wpdb ) {
	$wpdb->query( 'ROLLBACK' );
} );

$whoopstore_qa_admin = current( get_users( [ 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ] ) );

if ( ! $whoopstore_qa_admin ) {
	fwrite( STDERR, "  нет пользователя с ролью администратора\n" );
	exit( 1 );
}

$whoopstore_qa_expires = time() + HOUR_IN_SECONDS;
$whoopstore_qa_token   = WP_Session_Tokens::get_instance( $whoopstore_qa_admin->ID )->create( $whoopstore_qa_expires );

$_COOKIE[ is_ssl() ? SECURE_AUTH_COOKIE : AUTH_COOKIE ] = wp_generate_auth_cookie( $whoopstore_qa_admin->ID, $whoopstore_qa_expires, is_ssl() ? 'secure_auth' : 'auth', $whoopstore_qa_token );
$_COOKIE[ LOGGED_IN_COOKIE ]                            = wp_generate_auth_cookie( $whoopstore_qa_admin->ID, $whoopstore_qa_expires, 'logged_in', $whoopstore_qa_token );

// Текущего пользователя WordPress определил при загрузке, когда куки ещё не
// было, поэтому назначаем его явно: куку читает auth_redirect(), а права
// проверяются по текущему пользователю.
wp_set_current_user( $whoopstore_qa_admin->ID );

// Редирект перехватываем: иначе страница просто оборвётся и мы не узнаем,
// куда она вела.
add_filter( 'wp_redirect', function ( $location ) use ( &$whoopstore_qa ) {
	$whoopstore_qa['redirect'] = (string) $location;

	return false; // дальше сработает exit в wp_redirect()
}, 999 );

require $file;
