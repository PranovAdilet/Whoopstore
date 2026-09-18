<?php
/**
 * Данные для jsdom-тестов (tools/qa/js/run.sh), только чтение.
 *
 * В каталог из первого аргумента пишет:
 *  - env            — переменные для run.sh: адрес страницы простого товара и
 *                     существующий заказ (id, ключ, номер) для проверки
 *                     возврата из ЮKassa;
 *  - script-order.txt — хендлы скриптов редактора в порядке зависимостей
 *                     (до wp-block-library) из манифеста самого WordPress;
 *  - blocks/*.html  — разметка, которую заказчик правит в редакторе:
 *                     синхронные паттерны, опубликованные страницы, шаблоны и
 *                     части темы.
 *
 * Запуск — из run.sh; вручную:
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/js/prepare.php /tmp/каталог
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$work = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : '';

if ( '' === $work || ! is_dir( $work ) ) {
	fwrite( STDERR, "Укажите существующий каталог для данных.\n" );
	exit( 1 );
}

require dirname( __DIR__, 3 ) . '/wp-load.php';

// Простой физический товар, который можно купить: форма «В корзину» на его
// странице — то, что проверяет cart-product.test.js.
$product = current( array_filter(
	wc_get_products( [ 'status' => 'publish', 'type' => 'simple', 'virtual' => false, 'limit' => 20 ] ),
	function ( $item ) {
		return '' !== (string) $item->get_price() && $item->is_purchasable() && $item->is_in_stock();
	}
) );

// Любой существующий заказ: модалка после оплаты показывает номер, только
// если ключ в адресе совпал с ключом заказа (inc/woocommerce/cart-ui.php).
$order = current( wc_get_orders( [ 'limit' => 1, 'orderby' => 'id', 'order' => 'DESC', 'status' => array_keys( wc_get_order_statuses() ) ] ) );

if ( ! $product || ! $order ) {
	fwrite( STDERR, "Нет товара или заказа для проверки.\n" );
	exit( 1 );
}

file_put_contents( $work . '/env', implode( "\n", [
	'PRODUCT_PATH=' . escapeshellarg( (string) wp_parse_url( $product->get_permalink(), PHP_URL_PATH ) ),
	'ORDER_ID=' . (int) $order->get_id(),
	'ORDER_KEY=' . escapeshellarg( $order->get_order_key() ),
	'ORDER_NUMBER=' . escapeshellarg( (string) $order->get_order_number() ),
] ) . "\n" );

// Порядок скриптов редактора: обход зависимостей wp-block-library.
$packages = include ABSPATH . WPINC . '/assets/script-loader-packages.php';
$ordered  = [];
$visit    = function ( $handle ) use ( &$visit, &$ordered, $packages ) {
	if ( isset( $ordered[ $handle ] ) ) {
		return;
	}

	$ordered[ $handle ] = false;
	$key                = 0 === strpos( $handle, 'wp-' ) ? substr( $handle, 3 ) . '.js' : '';

	foreach ( $packages[ $key ]['dependencies'] ?? [] as $dependency ) {
		$visit( $dependency );
	}

	unset( $ordered[ $handle ] );
	$ordered[ $handle ] = true;
};
$visit( 'wp-block-library' );
file_put_contents( $work . '/script-order.txt', implode( "\n", array_keys( $ordered ) ) . "\n" );

// Разметка блоков.
$blocks = $work . '/blocks';
wp_mkdir_p( $blocks );

foreach ( get_posts( [ 'post_type' => [ 'wp_block', 'page' ], 'post_status' => 'publish', 'numberposts' => -1 ] ) as $post ) {
	if ( has_blocks( $post->post_content ) ) {
		file_put_contents( sprintf( '%s/%s-%d.html', $blocks, $post->post_type, $post->ID ), $post->post_content );
	}
}

foreach ( [ 'templates', 'parts' ] as $dir ) {
	foreach ( glob( get_theme_file_path( $dir ) . '/*.html' ) as $file ) {
		copy( $file, sprintf( '%s/theme-%s-%s', $blocks, $dir, basename( $file ) ) );
	}
}
