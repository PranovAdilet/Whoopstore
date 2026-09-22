<?php
/**
 * ЮKassa без ключей и без браузера: то, что в проекте написано вокруг плагина.
 *
 *  - staging-защита (mu-plugins/whoopstore-commerce/checkout.php): на staging
 *    способы ЮKassa предлагаются покупателю только с тестовым ключом test_,
 *    «Тестовая оплата» — только на staging;
 *  - возврат после оплаты (themes/whoopstore/inc/woocommerce/cart-ui.php):
 *    страница успеха ЮKassa подменяется Главной с модалкой, только если ключ
 *    заказа в запросе возврата совпал; номер для модалки отдаётся тоже только
 *    по совпавшему ключу.
 *
 * Ничего не пишет в базу: ключ ЮKassa и включённость способа подменяются
 * фильтрами опций только в этом процессе. Настоящий платёж, callback и
 * возврат со статусом из ЮKassa проверяются только с тестовыми ключами
 * заказчика (docs/launch-checklist.md, раздел 6).
 *
 * Запуск:
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/yookassa.php
 *
 * Код возврата 1 — есть непройденные проверки.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

require dirname( __DIR__, 2 ) . '/wp-load.php';

$whoopstore_qa_pass = 0;
$whoopstore_qa_fail = 0;

function whoopstore_qa_check( $title, $ok, $detail = '' ) {
	global $whoopstore_qa_pass, $whoopstore_qa_fail;

	$ok ? $whoopstore_qa_pass++ : $whoopstore_qa_fail++;
	echo ( $ok ? '  ok   ' : '  FAIL ' ) . $title . ( '' !== (string) $detail ? "  [$detail]" : '' ) . "\n";
}

if ( ! class_exists( 'YooKassaGatewayEPL' ) ) {
	echo "Плагин ЮKassa не активен.\n";
	exit( 1 );
}

// Сеть наружу закрыта: проверка не должна ходить в ЮKassa.
add_filter( 'pre_http_request', function () {
	return new WP_Error( 'whoopstore_qa_blocked', 'outbound HTTP blocked in tools/qa/yookassa.php' );
} );

/**
 * Способы оплаты, доступные покупателю, при заданном ключе ЮKassa и
 * включённом «Умном платеже».
 */
function whoopstore_qa_gateways( $password, $epl_enabled ) {
	$key = function () use ( $password ) {
		return $password;
	};
	$epl = function ( $settings ) use ( $epl_enabled ) {
		return array_merge( (array) $settings, [ 'enabled' => $epl_enabled ? 'yes' : 'no' ] );
	};

	add_filter( 'pre_option_yookassa_shop_password', $key );
	add_filter( 'option_woocommerce_yookassa_epl_settings', $epl );

	$gateways = WC()->payment_gateways();
	$gateways->init();
	$ids = array_keys( $gateways->get_available_payment_gateways() );

	remove_filter( 'pre_option_yookassa_shop_password', $key );
	remove_filter( 'option_woocommerce_yookassa_epl_settings', $epl );

	return $ids;
}

echo 'Окружение: ' . wp_get_environment_type() . "\n";

if ( 'staging' === wp_get_environment_type() ) {
	echo "\n== Staging: ЮKassa только с тестовым ключом\n";
	$ids = whoopstore_qa_gateways( '', true );
	whoopstore_qa_check( 'способ включён, ключа нет — ЮKassa скрыта', ! preg_grep( '/^yookassa/', $ids ) && in_array( 'cheque', $ids, true ), implode( ', ', $ids ) );
	$ids = whoopstore_qa_gateways( 'live_whoopstoreqa', true );
	whoopstore_qa_check( 'боевой ключ live_ — ЮKassa скрыта', ! preg_grep( '/^yookassa/', $ids ), implode( ', ', $ids ) );
	$ids = whoopstore_qa_gateways( 'test_whoopstoreqa', true );
	whoopstore_qa_check( 'тестовый ключ test_ — ЮKassa предлагается', in_array( 'yookassa_epl', $ids, true ), implode( ', ', $ids ) );
	$ids = whoopstore_qa_gateways( 'test_whoopstoreqa', false );
	whoopstore_qa_check( 'способ выключен — ЮKassa не предлагается и с тестовым ключом', ! preg_grep( '/^yookassa/', $ids ), implode( ', ', $ids ) );
	whoopstore_qa_check( '«Тестовая оплата» на staging доступна', in_array( 'cheque', $ids, true ) );
} else {
	echo "\n== Не staging: «Тестовой оплаты» нет\n";
	whoopstore_qa_check( '«Тестовая оплата» скрыта', ! in_array( 'cheque', whoopstore_qa_gateways( '', false ), true ) );
}

echo "\n== Возврат после оплаты: страница успеха и модалка\n";
$order = current( wc_get_orders( [ 'limit' => 1, 'orderby' => 'id', 'order' => 'DESC', 'status' => array_keys( wc_get_order_statuses() ) ] ) );

if ( ! $order ) {
	echo "Нет заказа для проверки.\n";
	exit( 1 );
}

$gateway = new YooKassaGatewayEPL();
$url     = new ReflectionMethod( $gateway, 'get_success_fail_url' );
$url->setAccessible( true );
$success = function () use ( $url, $gateway, $order ) {
	return (string) $url->invoke( $gateway, 'yookassa_success', $order );
};

set_query_var( 'yookassa-order-id', '' );
whoopstore_qa_check( 'без запроса возврата — штатная «Заказ получен»', false !== strpos( $success(), 'order-received' ), wp_parse_url( $success(), PHP_URL_PATH ) );
set_query_var( 'yookassa-order-id', 'wc_order_wrong' );
whoopstore_qa_check( 'возврат с чужим ключом — штатная «Заказ получен»', false !== strpos( $success(), 'order-received' ) );
set_query_var( 'yookassa-order-id', $order->get_order_key() );
$home = $success();
whoopstore_qa_check( 'возврат со своим ключом — Главная с модалкой', 0 === strpos( $home, home_url( '/' ) ) && false !== strpos( $home, 'order-placed=' . $order->get_id() ) && false === strpos( $home, 'order-received' ) );
whoopstore_qa_check( 'страница отказа — оплата того же заказа', false !== strpos( (string) $url->invoke( $gateway, 'yookassa_fail', $order ), 'order-pay' ) );
set_query_var( 'yookassa-order-id', '' );

$_GET = [ 'order-placed' => (string) $order->get_id(), 'key' => $order->get_order_key() ];
whoopstore_qa_check( 'номер для модалки — по совпавшему ключу', whoopstore_returned_order_number() === (string) $order->get_order_number() );
$_GET['key'] = 'wc_order_wrong';
whoopstore_qa_check( 'чужой ключ — номера нет', '' === whoopstore_returned_order_number() );
$_GET = [];

echo "\nИтого: $whoopstore_qa_pass ok, $whoopstore_qa_fail FAIL\n";
exit( $whoopstore_qa_fail ? 1 : 0 );
