<?php
/**
 * Админка: открываются ли ключевые экраны и нет ли на них ошибок.
 *
 * Каждый экран рендерится отдельным процессом (tools/qa/render-admin.php) от
 * имени администратора, в транзакции с откатом. Проверяется:
 *  - экран открылся, а не отдал «Ошибка» или «Недостаточно прав»;
 *  - нет фатальных ошибок и замечаний PHP;
 *  - на экране есть то, ради чего он нужен: поля наших настроек, список
 *    товаров, карточка заказа, паттерны.
 *
 * Это проверка серверной части админки. Работу редактора блоков в браузере
 * (перетаскивание, превью) она не заменяет — валидность самих блоков
 * проверяет tools/qa/js/blocks.test.js.
 *
 * Запуск:
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/admin.php
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/admin.php --keep   (разметка в tools/qa/out/)
 *
 * Код возврата 1 — есть замечания.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$root = dirname( __DIR__, 2 );
$out  = __DIR__ . '/out';
$keep = in_array( '--keep', $argv, true );

require __DIR__ . '/lib.php';
require $root . '/wp-load.php';

/**
 * Экраны админки: название => [адрес, строка, которая должна быть на экране].
 *
 * Идентификаторы берём из базы, а не списком: товары и заказы меняются.
 */
function whoopstore_qa_admin_screens() {
	$screens = [
		'Консоль'              => [ '/wp-admin/index.php', 'Консоль' ],
		'Товары'               => [ '/wp-admin/edit.php?post_type=product', 'wp-list-table' ],
		'Страницы'             => [ '/wp-admin/edit.php?post_type=page', 'Политика конфиденциальности' ],
		'Паттерны'             => [ '/wp-admin/edit.php?post_type=wp_block', 'wp-list-table' ],
		'Медиафайлы'           => [ '/wp-admin/upload.php', 'Медиафайлы' ],
		'Плагины'              => [ '/wp-admin/plugins.php', 'WooCommerce' ],
		'Внешний вид'          => [ '/wp-admin/themes.php', 'whoopstore' ],
		'Настройки → Общие'    => [ '/wp-admin/options-general.php', 'whoopstore_yandex_metrika_id' ],
		'Настройки → Чтение'   => [ '/wp-admin/options-reading.php', 'blog_public' ],
		'Конфиденциальность'   => [ '/wp-admin/options-privacy.php', 'Политика конфиденциальности' ],
		'Заказы'               => [ '/wp-admin/admin.php?page=wc-orders', 'wp-list-table' ],
		// С WooCommerce 11 экран платежей рисует JS, названий способов в
		// разметке нет — проверяем, что открылась именно его вкладка.
		'WooCommerce: платежи' => [ '/wp-admin/admin.php?page=wc-settings&tab=checkout', 'experimental_wc_settings_payments_main' ],
		'WooCommerce: доставка' => [ '/wp-admin/admin.php?page=wc-settings&tab=shipping', 'Зоны доставки' ],
		'WooCommerce: товары'  => [ '/wp-admin/admin.php?page=wc-settings&tab=products', 'whoopstore_default_delivery' ],
		'Наш раздел Telegram'  => [ '/wp-admin/admin.php?page=wc-settings&tab=advanced&section=whoopstore_telegram', 'whoopstore_telegram_bot_token' ],
		'Настройки ЮKassa'     => [ '/wp-admin/admin.php?page=yoomoney_api_menu', 'Настройки модуля ЮKassa' ],
		'Доставка СДЭК'        => [ '/wp-admin/admin.php?page=wc-settings&tab=shipping&section=official_cdek', 'cdek' ],
		'Статус WooCommerce'   => [ '/wp-admin/admin.php?page=wc-status', 'WooCommerce' ],
	];

	$product = current( wc_get_products( [ 'limit' => 1, 'status' => 'publish', 'orderby' => 'ID' ] ) );

	if ( $product ) {
		$screens['Карточка товара'] = [
			'/wp-admin/post.php?post=' . $product->get_id() . '&action=edit',
			'_whoopstore_size_guide',
		];
	}

	$order = current( wc_get_orders( [ 'limit' => 1, 'orderby' => 'id', 'order' => 'DESC', 'status' => array_keys( wc_get_order_statuses() ) ] ) );

	if ( $order ) {
		$screens['Карточка заказа'] = [
			'/wp-admin/admin.php?page=wc-orders&action=edit&id=' . $order->get_id(),
			'#' . $order->get_order_number(),
		];
	}

	$privacy = (int) get_option( 'wp_page_for_privacy_policy' );

	if ( $privacy ) {
		$screens['Редактор страницы'] = [ '/wp-admin/post.php?post=' . $privacy . '&action=edit', 'block-editor' ];
	}

	return $screens;
}

/**
 * Запускает tools/qa/render-admin.php и разбирает его строку состояния.
 *
 * @param string $uri  Адрес экрана.
 * @param string $file Куда сохранить разметку.
 * @return array{redirect:string,notices:int,messages:string[]}|null
 */
function whoopstore_qa_admin_render( $uri, $file ) {
	$command = sprintf(
		'%s %s %s',
		escapeshellarg( PHP_BINARY ),
		escapeshellarg( __DIR__ . '/render-admin.php' ),
		escapeshellarg( $uri )
	);

	$process = proc_open( $command, [ 1 => [ 'file', $file, 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, dirname( __DIR__, 2 ) );

	if ( ! is_resource( $process ) ) {
		return null;
	}

	$stderr = stream_get_contents( $pipes[2] );
	fclose( $pipes[2] );
	proc_close( $process );

	preg_match( '/redirect=(\S+)/', $stderr, $redirect );
	preg_match( '/notices=(\d+)/', $stderr, $notices );

	$messages = array_values( array_filter( array_map( 'trim', explode( "\n", $stderr ) ), function ( $line ) {
		return '' !== $line && 0 !== strpos( $line, 'WHOOPSTORE-QA-ADMIN' );
	} ) );

	return [
		'redirect' => '-' !== ( $redirect[1] ?? '-' ) ? $redirect[1] : '',
		'notices'  => (int) ( $notices[1] ?? 0 ),
		'messages' => $messages,
	];
}

if ( ! is_dir( $out ) && ! mkdir( $out, 0755, true ) ) {
	fwrite( STDERR, "Не удалось создать каталог {$out}\n" );
	exit( 1 );
}

$problems = [];
$files    = [];

printf( "%s %s %s %s\n", whoopstore_qa_pad( 'Экран', 22 ), whoopstore_qa_pad( 'Байт', 9 ), whoopstore_qa_pad( 'Ответ', 34 ), 'Замечаний' );

foreach ( whoopstore_qa_admin_screens() as $label => $screen ) {
	list( $uri, $marker ) = $screen;

	$file  = $out . '/admin-' . sanitize_title( $label ) . '.html';
	$state = whoopstore_qa_admin_render( $uri, $file );

	if ( null === $state ) {
		$problems[] = $label . ': не удалось запустить рендер';
		continue;
	}

	$html   = (string) file_get_contents( $file );
	$size   = strlen( $html );
	$status = 'ok';

	if ( '' !== $state['redirect'] ) {
		$status     = '→ ' . $state['redirect'];
		$problems[] = $label . ': неожиданный редирект на ' . $state['redirect'];
	} elseif ( preg_match( '#<title>[^<]*(Ошибка|Error)#u', $html ) ) {
		// Страница wp_die(): «Недостаточно прав», «Неверные данные» и подобное.
		preg_match( '#<p>(.*?)</p>#s', $html, $message );
		$status     = 'ошибка';
		$problems[] = $label . ': экран ответил ошибкой — ' . trim( wp_strip_all_tags( $message[1] ?? '' ) );
	} elseif ( 0 === $size ) {
		$status     = 'пусто';
		$problems[] = $label . ': пустой ответ';
	} elseif ( false === strpos( $html, $marker ) ) {
		$status     = 'нет «' . $marker . '»';
		$problems[] = $label . ': на экране нет «' . $marker . '»';
	}

	printf(
		"%s %s %s %d\n",
		whoopstore_qa_pad( $label, 22 ),
		whoopstore_qa_pad( number_format( $size, 0, ',', ' ' ), 9 ),
		whoopstore_qa_pad( $status, 34 ),
		$state['notices']
	);

	foreach ( $state['messages'] as $message ) {
		$problems[] = $label . ': ' . $message;
	}

	$files[] = $file;
}

if ( ! $keep ) {
	array_map( 'unlink', $files );
	@rmdir( $out );
}

if ( $problems ) {
	echo "\nЗамечания:\n";

	foreach ( $problems as $problem ) {
		echo '  • ' . $problem . "\n";
	}

	exit( 1 );
}

echo "\nЗамечаний нет.\n";
