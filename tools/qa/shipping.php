<?php
/**
 * Способы доставки и ПВЗ через Store API — без браузера.
 *
 * Те же маршруты, что вызывает форма «Ваш заказ» (cart/add-item,
 * cart/update-customer, cart/select-shipping-rate, checkout), только внутри
 * процесса PHP: rest_do_request() вместо HTTP. Тарифы СДЭК считает настоящий
 * плагин CDEKDelivery в его тестовом режиме (учебный аккаунт api.edu.cdek.ru),
 * поэтому нужен доступ к интернету; ответ СДЭК занимает около секунды.
 *
 * Ничего не остаётся в базе: весь прогон идёт в транзакции и откатывается в
 * конце (таблицы WooCommerce — InnoDB), включая пробные заказы. Номера
 * заказов при этом всё равно расходуются — автоинкремент откат не
 * возвращает. Письма на staging глушит Staging Guard, уведомление в Telegram
 * на время прогона отключено. Ключ Яндекс.Карт, без которого ПВЗ не
 * предлагается (mu-plugins/whoopstore-commerce/cdek.php), подставляется
 * фильтром только в памяти.
 *
 * Что проверяется:
 *  - выбранный способ доставки не подменяется другим, когда пропадает
 *    (город неизвестен СДЭК, ошибка API), а оформление без способа
 *    отклоняется (аудит 2026-09-18, I3);
 *  - «Экспресс» предлагается только в Москве (решение заказчика 2026-09-25),
 *    «Самовывоз» сохраняется при смене города, новая корзина получает способ
 *    по умолчанию;
 *  - код ПВЗ сверяется со списком пунктов города (I4).
 *
 * Запуск:
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/shipping.php
 *
 * Код возврата 1 — есть непройденные проверки.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$_SERVER['HTTP_HOST']      = getenv( 'WHOOPSTORE_QA_HOST' ) ?: 'whoop.1c2.ru';
$_SERVER['HTTPS']          = 'on';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/wp-json/wc/store/v1/cart';
$_SERVER['SCRIPT_NAME']    = '/index.php';

require dirname( __DIR__, 2 ) . '/wp-load.php';

global $wpdb;

$wpdb->query( 'START TRANSACTION' );
register_shutdown_function( function () use ( $wpdb ) {
	$wpdb->query( 'ROLLBACK' );
} );

add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
remove_action( 'woocommerce_store_api_checkout_order_processed', 'whoopstore_telegram_notify_order' );
add_filter( 'option_woocommerce_official_cdek_settings', function ( $settings ) {
	return array_merge( (array) $settings, [ 'yandex_map_api_key' => 'whoopstore-qa' ] );
} );

// Недоступность API СДЭК: true — любой запрос к СДЭК получает ошибку сети,
// 'deliverypoints' — только запрос списка пунктов выдачи.
$whoopstore_qa_cdek_down = false;
add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$whoopstore_qa_cdek_down ) {
	$url = (string) $url;

	if ( ( true === $whoopstore_qa_cdek_down && false !== strpos( $url, 'cdek' ) )
		|| ( 'deliverypoints' === $whoopstore_qa_cdek_down && false !== strpos( $url, 'deliverypoints' ) ) ) {
		return new WP_Error( 'http_request_failed', 'cURL error 28: timeout (tools/qa/shipping.php)' );
	}

	return $pre;
}, 10, 3 );

$whoopstore_qa_pass = 0;
$whoopstore_qa_fail = 0;

function whoopstore_qa_check( $title, $ok, $detail = '' ) {
	global $whoopstore_qa_pass, $whoopstore_qa_fail;

	$ok ? $whoopstore_qa_pass++ : $whoopstore_qa_fail++;
	echo ( $ok ? '  ok   ' : '  FAIL ' ) . $title . ( '' !== (string) $detail ? "  [$detail]" : '' ) . "\n";
}

/**
 * Запрос к Store API. Ответ приводится к массивам (итоги Store API отдаёт
 * объектами).
 */
function whoopstore_qa_api( $method, $path, $body = null ) {
	$request = new WP_REST_Request( $method, '/wc/store/v1/' . $path );

	if ( null !== $body ) {
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
	}

	$response = rest_do_request( $request );

	return [ $response->get_status(), json_decode( wp_json_encode( $response->get_data() ), true ) ];
}

function whoopstore_qa_cart() {
	return whoopstore_qa_api( 'GET', 'cart' )[1];
}

function whoopstore_qa_rates( $cart ) {
	return $cart['shipping_rates'][0]['shipping_rates'] ?? [];
}

function whoopstore_qa_selected( $cart ) {
	foreach ( whoopstore_qa_rates( $cart ) as $rate ) {
		if ( $rate['selected'] ) {
			return $rate['rate_id'];
		}
	}

	return '';
}

function whoopstore_qa_rate( $cart, $prefix ) {
	foreach ( whoopstore_qa_rates( $cart ) as $rate ) {
		if ( 0 === strpos( $rate['rate_id'], $prefix ) ) {
			return $rate['rate_id'];
		}
	}

	return '';
}

function whoopstore_qa_line( $cart ) {
	$rates = array_map( function ( $rate ) {
		return ( $rate['selected'] ? '*' : ' ' ) . $rate['rate_id'];
	}, whoopstore_qa_rates( $cart ) );

	return '   ' . implode( ' | ', $rates ) . '  → выбран: ' . ( whoopstore_qa_selected( $cart ) ?: '(нет)' ) . "\n";
}

function whoopstore_qa_address( $city, $postcode ) {
	return whoopstore_qa_api( 'POST', 'cart/update-customer', [
		'shipping_address' => [ 'city' => $city, 'postcode' => $postcode, 'country' => 'RU' ],
	] )[1];
}

function whoopstore_qa_select( $rate_id ) {
	return whoopstore_qa_api( 'POST', 'cart/select-shipping-rate', [ 'package_id' => 0, 'rate_id' => $rate_id ] )[1];
}

/**
 * Пустая сессия покупателя с одним физическим товаром в корзине.
 */
function whoopstore_qa_fresh_cart( $product_id ) {
	whoopstore_qa_api( 'DELETE', 'cart/items' );
	WC()->session->set( 'chosen_shipping_methods', null );
	WC()->session->set( 'shipping_method_counts', null );
	WC()->customer->set_shipping_city( '' );
	WC()->customer->set_shipping_postcode( '' );
	WC()->customer->save();
	whoopstore_qa_api( 'POST', 'cart/add-item', [ 'id' => $product_id, 'quantity' => 1 ] );
}

/**
 * Оформление, как его отправляет форма «Ваш заказ».
 */
function whoopstore_qa_checkout( $city, $postcode, $street, $office_code = null ) {
	$address = [
		'first_name' => 'QA',
		'last_name'  => '',
		'address_1'  => $street,
		'city'       => $city,
		'postcode'   => $postcode,
		'country'    => 'RU',
		'phone'      => '+79990000000',
	];
	$body    = [
		'billing_address'   => array_merge( $address, [ 'email' => 'qa@example.com' ] ),
		'shipping_address'  => $address,
		'payment_method'    => 'cheque',
		'additional_fields' => [ 'whoopstore/privacy-consent' => true ],
	];

	if ( null !== $office_code ) {
		$body['extensions'] = [ 'official_cdek' => [ 'office_code' => $office_code ] ];
	}

	list( $status, $data ) = whoopstore_qa_api( 'POST', 'checkout', $body );

	return [ $status, $data['message'] ?? '', (int) ( $data['order_id'] ?? 0 ) ];
}

/**
 * Пункты выдачи города из ответа корзины (данные плагина СДЭК).
 */
function whoopstore_qa_points( $cart, $type ) {
	$points = json_decode( (string) ( $cart['extensions']['official_cdek']['points'] ?? '[]' ), true );

	return array_values( array_filter( is_array( $points ) ? $points : [], function ( $point ) use ( $type ) {
		return ( $point['type'] ?? '' ) === $type;
	} ) );
}

$product = wc_get_products( [ 'status' => 'publish', 'type' => 'simple', 'virtual' => false, 'limit' => 20 ] );
$product = current( array_filter( $product, function ( $item ) {
	return '' !== (string) $item->get_price() && $item->is_purchasable() && $item->is_in_stock();
} ) );

if ( ! $product ) {
	echo "Нет физического товара с ценой для проверки.\n";
	exit( 1 );
}

echo 'Товар: #' . $product->get_id() . ' ' . $product->get_name() . "\n";

/**
 * Отвечает ли СДЭК. Проверяем его собственным вызовом: у учебного аккаунта
 * эндпоинт location/cities возвращал пустой список на любой запрос
 * (2026-09-22, воспроизводится и обычным curl, мимо WordPress). Без кода
 * города плагин не считает тарифы и не отдаёт пункты выдачи, поэтому разделы
 * СДЭК тогда проверять нечем: это сбой внешнего сервиса, а не наша ошибка.
 */
function whoopstore_qa_cdek_ready() {
	try {
		return null !== ( new \Cdek\CdekApi() )->cityCodeGet( 'Санкт-Петербург', '190000' );
	} catch ( Throwable $e ) {
		return false;
	}
}

$cdek_ready = whoopstore_qa_cdek_ready();

if ( ! $cdek_ready ) {
	echo "\nВНИМАНИЕ: СДЭК не отвечает кодами городов — разделы СДЭК и ПВЗ пропущены.\n";
	echo "Проверить их можно, когда СДЭК ответит или когда появятся ключи заказчика.\n";
}

echo "\n== Новая корзина: способ по умолчанию\n";
whoopstore_qa_fresh_cart( $product->get_id() );
$cart = whoopstore_qa_cart();
echo whoopstore_qa_line( $cart );
whoopstore_qa_check( 'выбран первый способ по порядку («Экспресс»)', 0 === strpos( whoopstore_qa_selected( $cart ), 'flat_rate' ), whoopstore_qa_selected( $cart ) );

if ( $cdek_ready ) {
	echo "\n== I3: выбран курьер СДЭК, город неизвестен СДЭК\n";
	$cart    = whoopstore_qa_address( 'Москва', '101000' );
	$courier = whoopstore_qa_rate( $cart, 'official_cdek' );
	whoopstore_qa_check( 'для Москвы есть тариф СДЭК', '' !== $courier, $courier );
	$cart = whoopstore_qa_select( $courier );
	whoopstore_qa_check( 'СДЭК выбран', whoopstore_qa_selected( $cart ) === $courier );
	$cart = whoopstore_qa_address( 'Абвгдейкаград', '999999' );
	echo whoopstore_qa_line( $cart );
	whoopstore_qa_check( 'тарифа СДЭК больше нет', '' === whoopstore_qa_rate( $cart, 'official_cdek' ) );
	whoopstore_qa_check( 'вместо него не выбран другой способ', '' === whoopstore_qa_selected( $cart ), whoopstore_qa_selected( $cart ) );
	whoopstore_qa_check( 'и при повторном чтении корзины тоже', '' === whoopstore_qa_selected( whoopstore_qa_cart() ) );
	list( $status, $message ) = whoopstore_qa_checkout( 'Абвгдейкаград', '999999', 'ул. Тестовая, 1' );
	whoopstore_qa_check( 'оформление без способа доставки отклонено', 400 === $status, $status . ' ' . $message );
	$cart = whoopstore_qa_address( 'Москва', '101000' );
	whoopstore_qa_check( 'город вернули — вернулся прежний выбор', whoopstore_qa_selected( $cart ) === $courier, whoopstore_qa_selected( $cart ) );

	echo "\n== I3: выбран курьер СДЭК, API СДЭК не отвечает\n";
	whoopstore_qa_fresh_cart( $product->get_id() );
	$cart    = whoopstore_qa_address( 'Санкт-Петербург', '190000' );
	$courier = whoopstore_qa_rate( $cart, 'official_cdek:137' ) ?: whoopstore_qa_rate( $cart, 'official_cdek' );
	whoopstore_qa_select( $courier );
	$whoopstore_qa_cdek_down = true;
	$cart                    = whoopstore_qa_address( 'Санкт-Петербург', '190001' );
	$whoopstore_qa_cdek_down = false;
	echo whoopstore_qa_line( $cart );
	whoopstore_qa_check( 'выбор не подменён', '' === whoopstore_qa_selected( $cart ), whoopstore_qa_selected( $cart ) );
	$cart = whoopstore_qa_select( whoopstore_qa_rate( $cart, 'local_pickup' ) );
	whoopstore_qa_check( 'покупатель сам выбрал «Самовывоз» — принято', 0 === strpos( whoopstore_qa_selected( $cart ), 'local_pickup' ), whoopstore_qa_selected( $cart ) );
}

echo "\n== Выбранный способ исчез из набора (без участия СДЭК)\n";

// Та же защита, что и для тарифов СДЭК (I3), но воспроизведённая без
// внешнего сервиса: способ убирается из набора фильтром. Так проверка
// работает, даже когда СДЭК недоступен.
whoopstore_qa_fresh_cart( $product->get_id() );
$express = whoopstore_qa_rate( whoopstore_qa_cart(), 'flat_rate' );
whoopstore_qa_select( $express );
$whoopstore_qa_hide = function ( $rates ) use ( $express ) {
	unset( $rates[ $express ] );

	return $rates;
};

add_filter( 'woocommerce_package_rates', $whoopstore_qa_hide, 100 );
WC_Cache_Helper::get_transient_version( 'shipping', true );
$cart = whoopstore_qa_cart();
echo whoopstore_qa_line( $cart );
whoopstore_qa_check( 'способ пропал — другой не подставлен', '' === whoopstore_qa_selected( $cart ), whoopstore_qa_selected( $cart ) );
list( $status, $message ) = whoopstore_qa_checkout( 'Москва', '101000', 'ул. Тестовая, 1' );
whoopstore_qa_check( 'оформление отклонено', 400 === $status && false !== mb_stripos( $message, 'способ доставки' ), $status . ' ' . $message );

remove_filter( 'woocommerce_package_rates', $whoopstore_qa_hide, 100 );
WC_Cache_Helper::get_transient_version( 'shipping', true );
$cart = whoopstore_qa_cart();
whoopstore_qa_check( 'способ вернулся — вернулся и выбор', whoopstore_qa_selected( $cart ) === $express, whoopstore_qa_selected( $cart ) );

echo "\n== «Экспресс» — только Москва (решение заказчика 2026-09-25)\n";

// Способ ограничен настройкой whoopstore_moscow_only у способа доставки
// (mu-plugins/whoopstore-commerce/shipping.php). Город сверяется по названию,
// поэтому проверяются и написания, которые набирает живой покупатель.
whoopstore_qa_fresh_cart( $product->get_id() );
$cart = whoopstore_qa_cart();
whoopstore_qa_check( 'адрес не заполнен — «Экспресс» предлагается', '' !== whoopstore_qa_rate( $cart, 'flat_rate' ), whoopstore_qa_line( $cart ) );

foreach ( [ 'Москва', 'г. Москва', 'МОСКВА' ] as $whoopstore_qa_moscow ) {
	$cart = whoopstore_qa_address( $whoopstore_qa_moscow, '101000' );
	whoopstore_qa_check( '«' . $whoopstore_qa_moscow . '» — «Экспресс» есть', '' !== whoopstore_qa_rate( $cart, 'flat_rate' ), whoopstore_qa_line( $cart ) );
}

foreach ( [ 'Санкт-Петербург' => '190000', 'Новосибирск' => '630000' ] as $whoopstore_qa_city => $whoopstore_qa_zip ) {
	$cart = whoopstore_qa_address( $whoopstore_qa_city, $whoopstore_qa_zip );
	whoopstore_qa_check( $whoopstore_qa_city . ' — «Экспресса» нет', '' === whoopstore_qa_rate( $cart, 'flat_rate' ), whoopstore_qa_line( $cart ) );
}

// Уехал из Москвы с уже выбранным «Экспрессом»: способ пропадает, но другой
// молча не подставляется — та же защита, что у тарифов СДЭК (I3).
whoopstore_qa_fresh_cart( $product->get_id() );
$cart = whoopstore_qa_address( 'Москва', '101000' );
whoopstore_qa_select( whoopstore_qa_rate( $cart, 'flat_rate' ) );
$cart = whoopstore_qa_address( 'Новосибирск', '630000' );
whoopstore_qa_check( 'сменил город — выбор «Экспресса» не подменён', '' === whoopstore_qa_selected( $cart ), whoopstore_qa_selected( $cart ) );
$cart = whoopstore_qa_address( 'Москва', '101000' );
whoopstore_qa_check( 'вернулся в Москву — вернулся и выбор', 0 === strpos( whoopstore_qa_selected( $cart ), 'flat_rate' ), whoopstore_qa_selected( $cart ) );

echo "\n== «Самовывоз» при смене города\n";
whoopstore_qa_fresh_cart( $product->get_id() );
whoopstore_qa_select( whoopstore_qa_rate( whoopstore_qa_cart(), 'local_pickup' ) );
$cart = whoopstore_qa_address( 'Москва', '101000' );
whoopstore_qa_check( '«Самовывоз» сохранён', 0 === strpos( whoopstore_qa_selected( $cart ), 'local_pickup' ), whoopstore_qa_selected( $cart ) );

echo "\n== Пропавший выбор не переходит в новую корзину\n";
// Выбирается «Экспресс», а НЕ курьер СДЭК: в Москве курьер стоит первым в
// наборе, то есть он же и способ по умолчанию — на нём проверка «вернулся
// запомненный или подставился обычный по умолчанию» ничего бы не различила.
whoopstore_qa_fresh_cart( $product->get_id() );
$cart = whoopstore_qa_address( 'Москва', '101000' );
$whoopstore_qa_express = whoopstore_qa_rate( $cart, 'flat_rate' );
whoopstore_qa_select( $whoopstore_qa_express );

// Город без тарифов СДЭК и без «Экспресса»: выбранный способ пропадает и
// запоминается в сессии.
$cart = whoopstore_qa_address( 'Абвгдейкаград', '999999' );
whoopstore_qa_check( 'остался только самовывоз — он не выбирается сам', '' === whoopstore_qa_selected( $cart ), whoopstore_qa_line( $cart ) );

whoopstore_qa_api( 'DELETE', 'cart/items' );
whoopstore_qa_api( 'POST', 'cart/add-item', [ 'id' => $product->get_id(), 'quantity' => 1 ] );
$cart = whoopstore_qa_address( 'Москва', '101000' );
whoopstore_qa_check(
	'новая корзина — способ по умолчанию, а не запомненный «Экспресс»',
	0 === strpos( whoopstore_qa_selected( $cart ), 'official_cdek' ),
	whoopstore_qa_selected( $cart ) . ' (пропадал ' . $whoopstore_qa_express . ')'
);

if ( $cdek_ready ) {
	echo "\n== I4: код ПВЗ при оформлении\n";

	/**
	 * Новая корзина, город, выбранный тариф до ПВЗ — и оформление с кодом пункта.
	 * Каждая попытка — с чистой корзины: принятый заказ её очищает.
	 */
	function whoopstore_qa_pvz_attempt( $product_id, $office_code, $drop_cache = false, $cdek_down = false ) {
		global $whoopstore_qa_cdek_down;

		whoopstore_qa_fresh_cart( $product_id );
		$cart = whoopstore_qa_address( 'Санкт-Петербург', '190000' );
		$rate = '';

		foreach ( whoopstore_qa_rates( $cart ) as $item ) {
			if ( 'office' === ( $cart['extensions']['whoopstore']['cdek']['rates'][ $item['rate_id'] ]['kind'] ?? '' ) ) {
				$rate = $item['rate_id'];
			}
		}

		if ( '' === $rate ) {
			return [ 0, 'нет тарифа до ПВЗ', 0 ];
		}

		whoopstore_qa_select( $rate );

		if ( $drop_cache ) {
			WC()->session->set( 'official_cdek_office_list_cache', null );
		}

		$whoopstore_qa_cdek_down = $cdek_down;
		$result                  = whoopstore_qa_checkout( 'Санкт-Петербург', '190000', '', $office_code );
		$whoopstore_qa_cdek_down = false;

		return $result;
	}

	whoopstore_qa_fresh_cart( $product->get_id() );
	$cart      = whoopstore_qa_address( 'Санкт-Петербург', '190000' );
	$pvz       = whoopstore_qa_points( $cart, 'PVZ' );
	$postamats = whoopstore_qa_points( $cart, 'POSTAMAT' );
	$moscow    = whoopstore_qa_points( whoopstore_qa_address( 'Москва', '101000' ), 'PVZ' );
	whoopstore_qa_check( 'в корзине есть список ПВЗ города', count( $pvz ) > 0 && count( $moscow ) > 0, 'СПб: ' . count( $pvz ) . ' ПВЗ, ' . count( $postamats ) . ' постаматов; Москва: ' . count( $moscow ) . ' ПВЗ' );

	list( $status, $message ) = whoopstore_qa_pvz_attempt( $product->get_id(), null );
	whoopstore_qa_check( 'без кода ПВЗ — отказ', 400 === $status, $status . ' ' . $message );

	list( $status, $message ) = whoopstore_qa_pvz_attempt( $product->get_id(), 'ZZZ999' );
	whoopstore_qa_check( 'несуществующий код ПВЗ — отказ', 400 === $status, $status . ' ' . $message );

	if ( $postamats ) {
		list( $status, $message ) = whoopstore_qa_pvz_attempt( $product->get_id(), $postamats[0]['code'] );
		whoopstore_qa_check( 'постамат при тарифе до ПВЗ — отказ', 400 === $status, $status . ' ' . $message );
	}

	list( $status, $message ) = whoopstore_qa_pvz_attempt( $product->get_id(), $moscow[0]['code'] ?? 'MSK' );
	whoopstore_qa_check( 'ПВЗ другого города — отказ', 400 === $status, $status . ' ' . $message );

	list( $status, $message, $order_id ) = whoopstore_qa_pvz_attempt( $product->get_id(), $pvz[0]['code'] );
	$saved = $order_id ? (string) wc_get_order( $order_id )->get_meta( WHOOPSTORE_CDEK_OFFICE_META ) : '';
	whoopstore_qa_check( 'настоящий ПВЗ города — заказ принят', 200 === $status && $order_id > 0, $status . ' ' . $message );
	whoopstore_qa_check( 'в заказе код и адрес ПВЗ', false !== strpos( $saved, $pvz[0]['code'] . ' — ' ), $saved );

	// Кэш списка пунктов у плагина живёт 5 минут, а покупатель мог заполнять
	// форму дольше. Проверка должна работать и без кэша.
	list( $status, $message, $order_id ) = whoopstore_qa_pvz_attempt( $product->get_id(), $pvz[0]['code'], true );
	whoopstore_qa_check( 'кэш плагина истёк — ПВЗ всё равно проверен, заказ принят', 200 === $status && $order_id > 0, $status . ' ' . $message );
	list( $status, $message ) = whoopstore_qa_pvz_attempt( $product->get_id(), 'ZZZ999', true );
	whoopstore_qa_check( 'кэш плагина истёк — несуществующий код всё равно отклонён', 400 === $status, $status . ' ' . $message );

	// Список пунктов СДЭК не отдал (сбой API), а тариф уже посчитан: сверить не
	// с чем. Заказ не теряем — код принимается без сверки, как до проверки
	// (ограничение описано в cdek.php и checkout.php).
	list( $status, $message, $order_id ) = whoopstore_qa_pvz_attempt( $product->get_id(), $pvz[0]['code'], true, 'deliverypoints' );
	$saved = $order_id ? (string) wc_get_order( $order_id )->get_meta( WHOOPSTORE_CDEK_OFFICE_META ) : '';
	whoopstore_qa_check( 'список пунктов недоступен — заказ принят с кодом ПВЗ без адреса', 200 === $status && $saved === $pvz[0]['code'], trim( $status . ' ' . $message . ' ' . $saved ) );

	// СДЭК не отвечает вовсе: пропадает сам тариф до ПВЗ. Оформление отклоняется
	// с просьбой выбрать способ доставки (I3), а не с чужой ошибкой адреса.
	list( $status, $message ) = whoopstore_qa_pvz_attempt( $product->get_id(), $pvz[0]['code'], true, true );
	whoopstore_qa_check( 'СДЭК не отвечает — отказ «выберите способ доставки»', 400 === $status && false !== mb_stripos( $message, 'способ доставки' ), $status . ' ' . $message );
}

echo "\nИтого: $whoopstore_qa_pass ok, $whoopstore_qa_fail FAIL\n";
exit( $whoopstore_qa_fail ? 1 : 0 );
