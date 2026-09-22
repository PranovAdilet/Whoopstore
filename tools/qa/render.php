<?php
/**
 * Рендер страницы стенда без браузера.
 *
 * Стенд закрыт HTTP Basic Auth на уровне Caddy, учётных данных у разработчика
 * нет, поэтому страницы поднимаются прямо в PHP: скрипт подставляет окружение
 * запроса и подключает index.php WordPress.
 *
 * Запуск (runtime сайта — PHP 8.2, системный php в консоли другой версии):
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/render.php /remeshki-whoop-mg/ > page.html
 *
 * В stderr уходит строка состояния: 404, редирект и число замечаний PHP.
 *
 * Флаг --probe останавливает работу сразу после разбора запроса, до вывода
 * шаблона: нужно только узнать, существует ли адрес. Так проверка внутренних
 * ссылок (tools/qa/smoke.php --links) не рендерит десятки страниц целиком.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$root = dirname( __DIR__, 2 );

$whoopstore_qa_uri   = isset( $argv[1] ) ? $argv[1] : '/';
$whoopstore_qa_probe = in_array( '--probe', $argv, true );

// В CLI суперглобального $_GET нет, а WordPress берёт параметры запроса
// именно из него — без этого адрес вида /?s=... отрендерился бы как Главная.
$whoopstore_qa_query = (string) parse_url( $whoopstore_qa_uri, PHP_URL_QUERY );
parse_str( $whoopstore_qa_query, $_GET );
$_REQUEST = $_GET;

$_SERVER['HTTP_HOST']       = getenv( 'WHOOPSTORE_QA_HOST' ) ?: 'whoop.1c2.ru';
$_SERVER['SERVER_NAME']     = $_SERVER['HTTP_HOST'];
$_SERVER['REQUEST_URI']     = $whoopstore_qa_uri;
$_SERVER['QUERY_STRING']    = $whoopstore_qa_query;
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['HTTPS']           = 'on';
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';

$whoopstore_qa = [
	'uri'      => $whoopstore_qa_uri,
	'is_404'   => false,
	'redirect' => '',
	'notices'  => [],
];

// Замечания PHP собираем сами: на стенде WP_DEBUG выключен, и без этого
// deprecated/notice просто не видно.
set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$whoopstore_qa ) {
	$whoopstore_qa['notices'][] = sprintf( '[%d] %s в %s:%d', $errno, $errstr, $errfile, $errline );

	return false; // штатная обработка тоже отрабатывает
} );

register_shutdown_function( function () use ( &$whoopstore_qa ) {
	$status = [
		'uri=' . $whoopstore_qa['uri'],
		'404=' . ( $whoopstore_qa['is_404'] ? 'yes' : 'no' ),
		'redirect=' . ( '' !== $whoopstore_qa['redirect'] ? $whoopstore_qa['redirect'] : '-' ),
		'notices=' . count( $whoopstore_qa['notices'] ),
	];

	fwrite( STDERR, 'WHOOPSTORE-QA ' . implode( ' ', $status ) . "\n" );

	foreach ( array_unique( $whoopstore_qa['notices'] ) as $notice ) {
		fwrite( STDERR, '  ' . $notice . "\n" );
	}
} );

chdir( $root );

// Как index.php: без этого template-loader.php не выводит шаблон.
define( 'WP_USE_THEMES', true );

// wp-load.php поднимает WordPress, но не выполняет запрос — это даёт нам
// возможность навесить наблюдатели до wp() ниже.
require $root . '/wp-load.php';

add_action( 'template_redirect', function () use ( &$whoopstore_qa, $whoopstore_qa_probe ) {
	$whoopstore_qa['is_404'] = is_404();

	if ( $whoopstore_qa_probe ) {
		exit( 0 ); // строку состояния допишет register_shutdown_function
	}
}, 1 );

// Редирект перехватываем до отправки заголовков: иначе страница просто
// оборвётся и мы не узнаем, куда она вела.
add_filter( 'wp_redirect', function ( $location ) use ( &$whoopstore_qa ) {
	$whoopstore_qa['redirect'] = (string) $location;

	return false; // ничего не отправляем, дальше сработает exit в wp_redirect()
}, 999 );

require $root . '/wp-blog-header.php';
