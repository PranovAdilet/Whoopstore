<?php
/**
 * СДЭК в форме «Ваш заказ»: плитки «Доставка курьером» и «ПВЗ CDEK»
 * (Figma 30:619/30:1759).
 *
 * Доставку считает официальный плагин CDEKDelivery: тарифы — способ доставки
 * official_cdek в зоне WooCommerce, список ПВЗ — его данные в ответе корзины
 * Store API (extensions.official_cdek.points), код ПВЗ при оформлении — его
 * поле extensions.official_cdek.office_code. Здесь только то, чего форме не
 * хватает у плагина:
 *
 *  - тариф приходит с названием «ПВЗ CDEK, (2-4 дн.)»; в макете срок — отдельная
 *    строка «Срок доставки», поэтому он переносится в delivery_time тарифа, а
 *    подпись плитки — в description (как у «Экспресса» и «Самовывоза»);
 *  - WooCommerce при смене набора тарифов (появились тарифы СДЭК для города)
 *    сбрасывает выбор на первый способ — выбор покупателя сохраняется;
 *  - форме нужны названия и подписи плиток до того, как введён город, и
 *    соответствие «тариф → курьер / ПВЗ» — отдельные данные корзины Store API;
 *  - при оформлении через Store API плагин не проверяет, выбран ли ПВЗ, —
 *    проверка в whoopstore_validate_checkout_request() (checkout.php);
 *  - адрес выбранного ПВЗ сохраняется в заказе для уведомления в Telegram;
 *  - без ключа Яндекс.Карт доставка до ПВЗ не предлагается: карта без ключа
 *    пустая, а без пункта заказ не оформить.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WHOOPSTORE_CDEK_METHOD      = 'official_cdek';
const WHOOPSTORE_CDEK_OFFICE_META = '_whoopstore_cdek_office';

/**
 * Плагин CDEKDelivery активен и его классы доступны.
 *
 * @return bool
 */
function whoopstore_cdek_available() {
	return class_exists( '\Cdek\Model\Tariff' ) && class_exists( '\Cdek\MetaKeys' );
}

/**
 * Карта пунктов выдачи может работать: в настройках СДЭК есть ключ
 * Яндекс.Карт. Ключ «по умолчанию» в виджете СДЭК — не ключ Яндекса, без
 * своего ключа карта открывается пустой (проверено в браузере 2026-09-18).
 *
 * @return bool
 */
function whoopstore_cdek_map_ready() {
	$settings = (array) get_option( 'woocommerce_official_cdek_settings', [] );

	return '' !== trim( (string) ( $settings['yandex_map_api_key'] ?? '' ) );
}

// Настройки СДЭК изменились (например, вписан ключ Яндекс.Карт) — сбрасываем
// кэш тарифов в сессиях покупателей, как WooCommerce при правке доставки.
add_action( 'update_option_woocommerce_official_cdek_settings', function () {
	WC_Cache_Helper::get_transient_version( 'shipping', true );
} );

/**
 * Включённый способ «СДЭК» в зоне доставки (первый найденный).
 *
 * @return WC_Shipping_Method|null
 */
function whoopstore_cdek_zone_method() {
	static $method = false;

	if ( false !== $method ) {
		return $method;
	}

	$method = null;

	if ( ! whoopstore_cdek_available() ) {
		return $method;
	}

	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		foreach ( $zone['shipping_methods'] as $candidate ) {
			if ( WHOOPSTORE_CDEK_METHOD === $candidate->id && 'yes' === $candidate->enabled ) {
				$method = $candidate;
				return $method;
			}
		}
	}

	return $method;
}

/**
 * Вид тарифа СДЭК: door — до двери, office — до ПВЗ, postamat — до постамата.
 *
 * @param int $code Код тарифа.
 * @return string
 */
function whoopstore_cdek_tariff_kind( $code ) {
	try {
		if ( ! \Cdek\Model\Tariff::isToOffice( (int) $code ) ) {
			return 'door';
		}

		return \Cdek\Model\Tariff::isToPickup( (int) $code ) ? 'postamat' : 'office';
	} catch ( Throwable $e ) {
		return '';
	}
}

/**
 * Подписи плиток: по одной на вид доставки, правятся в настройках способа
 * «СДЭК» в зоне доставки (как «Подпись в оформлении заказа» у других
 * способов, shipping.php). По умолчанию — тексты макета.
 *
 * @return array<string, array{field: string, default: string}>
 */
function whoopstore_cdek_caption_fields() {
	return [
		'door'   => [
			'field'   => 'whoopstore_description_door',
			'title'   => 'Подпись плитки «до двери»',
			'default' => 'СДЭК доставит заказ по адресу',
		],
		'office' => [
			'field'   => 'whoopstore_description_office',
			'title'   => 'Подпись плитки «до ПВЗ»',
			'default' => 'Выберите пункт выдачи на карте',
		],
	];
}

add_filter( 'woocommerce_shipping_instance_form_fields_' . WHOOPSTORE_CDEK_METHOD, function ( $fields ) {
	foreach ( whoopstore_cdek_caption_fields() as $caption ) {
		$fields[ $caption['field'] ] = [
			'title'       => $caption['title'],
			'type'        => 'text',
			'default'     => $caption['default'],
			'desc_tip'    => true,
			'description' => 'Вторая строка плитки в форме «Ваш заказ», после цены.',
		];
	}

	return $fields;
} );

/**
 * Подпись плитки для вида доставки.
 *
 * @param string                  $kind   door | office | postamat.
 * @param WC_Shipping_Method|null $method Способ «СДЭК».
 * @return string
 */
function whoopstore_cdek_caption( $kind, $method ) {
	$fields = whoopstore_cdek_caption_fields();
	$key    = 'door' === $kind ? 'door' : 'office';

	if ( ! $method ) {
		return $fields[ $key ]['default'];
	}

	return trim( (string) $method->get_instance_option( $fields[ $key ]['field'], $fields[ $key ]['default'] ) );
}

/**
 * Название тарифа, как его показывает плагин: своё из настройки «Изменить
 * название тарифа» («137-Доставка курьером;136-ПВЗ CDEK»), иначе — штатное.
 *
 * @param int $code Код тарифа.
 * @return string
 */
function whoopstore_cdek_tariff_title( $code ) {
	$names    = [];
	$settings = (array) get_option( 'woocommerce_official_cdek_settings', [] );

	foreach ( explode( ';', (string) ( $settings['tariff_name'] ?? '' ) ) as $pair ) {
		$parts = array_map( 'trim', explode( '-', $pair, 2 ) );

		if ( 2 === count( $parts ) && '' !== $parts[1] ) {
			$names[ (int) $parts[0] ] = $parts[1];
		}
	}

	if ( isset( $names[ (int) $code ] ) ) {
		return $names[ (int) $code ];
	}

	$list = \Cdek\Model\Tariff::list();

	return isset( $list[ $code ] ) ? preg_replace( '/\s*\(\d+\)$/', '', $list[ $code ] ) : (string) $code;
}

/**
 * Тарифы СДЭК: срок — в delivery_time, подпись плитки — в description.
 *
 * Плагин кладёт срок в название: «ПВЗ CDEK, (2-4 дн.)». Приоритет 20 — после
 * плагина, который добавляет тарифы при расчёте.
 */
add_filter( 'woocommerce_package_rates', function ( $rates ) {
	if ( ! whoopstore_cdek_available() ) {
		return $rates;
	}

	$map_ready = whoopstore_cdek_map_ready();

	foreach ( $rates as $rate_id => $rate ) {
		if ( WHOOPSTORE_CDEK_METHOD !== $rate->get_method_id() ) {
			continue;
		}

		$meta = $rate->get_meta_data();
		$kind = whoopstore_cdek_tariff_kind( $meta[ \Cdek\MetaKeys::TARIFF_CODE ] ?? 0 );

		// Без ключа Яндекс.Карт пункт выдачи не выбрать — такой заказ не
		// оформить, поэтому доставку до ПВЗ и постамата не предлагаем.
		if ( ! $map_ready && in_array( $kind, [ 'office', 'postamat' ], true ) ) {
			unset( $rates[ $rate_id ] );
			continue;
		}

		if ( preg_match( '/^(.*),\s*\(([^()]+)\)$/u', $rate->get_label(), $match ) ) {
			$rate->set_label( trim( $match[1] ) );
			$rate->set_delivery_time( trim( $match[2] ) );
		}

		$rate->set_description( whoopstore_cdek_caption( $kind, WC_Shipping_Zones::get_shipping_method( $rate->get_instance_id() ) ?: null ) );
	}

	return $rates;
}, 20 );

/**
 * Выбор способа доставки не сбрасывается, когда меняется набор тарифов.
 *
 * WooCommerce при смене набора (у города появились или пропали тарифы СДЭК)
 * выбирает первый способ по порядку, оставляя прежний только для самовывоза.
 * Тарифы СДЭК зависят от адреса, поэтому без этого выбор «Экспресса» молча
 * менялся бы на курьера СДЭК, стоит покупателю ввести город, — в том числе в
 * момент оформления. Прежний выбор остаётся, если он по-прежнему доступен.
 *
 * @param string $default       Способ по умолчанию.
 * @param array  $rates         Доступные тарифы.
 * @param string $chosen_method Прежний выбор.
 * @return string
 */
add_filter( 'woocommerce_shipping_chosen_method', function ( $default, $rates, $chosen_method ) {
	return ( $chosen_method && isset( $rates[ $chosen_method ] ) ) ? $chosen_method : $default;
}, 10, 3 );

/**
 * Данные для формы в ответе корзины Store API (extensions.whoopstore.cdek).
 *
 * tiles  — плитки в порядке макета, пока тарифов ещё нет (не введён город);
 * rates  — вид доставки и тип пунктов для каждого тарифа СДЭК корзины;
 * widget — скрипт виджета ПВЗ плагина и его ключ Яндекс.Карт.
 */
add_action( 'woocommerce_blocks_loaded', function () {
	if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
		return;
	}

	woocommerce_store_api_register_endpoint_data( [
		'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
		'namespace'       => 'whoopstore',
		'schema_type'     => ARRAY_A,
		'schema_callback' => function () {
			return [
				'cdek' => [
					'description' => 'Плитки СДЭК формы «Ваш заказ»',
					'type'        => [ 'object', 'null' ],
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
			];
		},
		'data_callback'   => 'whoopstore_cdek_cart_data',
	] );
} );

/**
 * @return array{cdek: array|null}
 */
function whoopstore_cdek_cart_data() {
	$method = whoopstore_cdek_zone_method();

	if ( ! $method ) {
		return [ 'cdek' => null ];
	}

	$settings = (array) get_option( 'woocommerce_official_cdek_settings', [] );
	$tiles    = [];

	foreach ( (array) ( $settings['tariff_list'] ?? [] ) as $code ) {
		$kind = whoopstore_cdek_tariff_kind( $code );
		$slot = 'door' === $kind ? 'door' : 'office';

		if ( '' === $kind || isset( $tiles[ $slot ] ) || ( 'office' === $slot && ! whoopstore_cdek_map_ready() ) ) {
			continue;
		}

		$tiles[ $slot ] = [
			'kind'        => $slot,
			'title'       => whoopstore_cdek_tariff_title( (int) $code ),
			'description' => whoopstore_cdek_caption( $kind, $method ),
		];
	}

	$rates = [];
	$pack  = WC()->shipping() ? WC()->shipping()->get_packages() : [];

	foreach ( $pack as $package ) {
		foreach ( (array) ( $package['rates'] ?? [] ) as $rate_id => $rate ) {
			if ( WHOOPSTORE_CDEK_METHOD !== $rate->get_method_id() ) {
				continue;
			}

			$kind              = whoopstore_cdek_tariff_kind( $rate->get_meta_data()[ \Cdek\MetaKeys::TARIFF_CODE ] ?? 0 );
			$rates[ $rate_id ] = [
				'kind'      => 'door' === $kind ? 'door' : 'office',
				'pointType' => 'office' === $kind ? 'PVZ' : ( 'postamat' === $kind ? 'POSTAMAT' : null ),
			];
		}
	}

	$order = [ 'door', 'office' ];
	$city  = WC()->customer ? trim( WC()->customer->get_shipping_city() ) : '';

	// Город введён, а тарифов СДЭК нет — СДЭК его не нашёл. Текст — из
	// языкового пакета плагина.
	$unavailable = '' !== $city && ! $rates
		? trim( sprintf( __( 'Failed to determine locality in %1$s %2$s', 'cdekdelivery' ), $city, WC()->customer->get_shipping_postcode() ) )
		: '';

	return [
		'cdek' => [
			'tiles'  => array_values( array_filter( array_map( function ( $slot ) use ( $tiles ) {
				return $tiles[ $slot ] ?? null;
			}, $order ) ) ),
			'rates'  => (object) $rates,
			'widget' => [
				'script' => plugins_url( 'build/cdek-widget.umd.js', WP_PLUGIN_DIR . '/cdekdelivery/cdek.php' ),
				'apiKey' => (string) ( $settings['yandex_map_api_key'] ?? '' ),
				'lang'   => 'rus',
			],
			// Строку плагина переводит его языковой пакет; эта в пакете не
			// переведена, поэтому — дословно по-русски.
			'noPoints' => 'В этом направлении нет пунктов выдачи СДЭК — выберите другой способ доставки.',
			'unavailable' => $unavailable,
		],
	];
}

/**
 * Выбранный ПВЗ: код из запроса оформления и адрес из списка пунктов плагина
 * (тот же список, что форма показывала на карте, — берётся из кэша плагина).
 *
 * @param WP_REST_Request $request Запрос оформления.
 * @return array{code: string, address: string}|null
 */
function whoopstore_cdek_requested_office( $request ) {
	$code = $request['extensions']['official_cdek']['office_code'] ?? '';
	$code = is_string( $code ) ? sanitize_text_field( $code ) : '';

	if ( '' === $code ) {
		return null;
	}

	$address = '';
	$city    = WC()->customer ? WC()->customer->get_shipping_city() : '';

	if ( '' !== $city && class_exists( '\Cdek\Helpers\OfficeListCache' ) ) {
		$cached = \Cdek\Helpers\OfficeListCache::get( $city, WC()->customer->get_shipping_postcode() );
		$points = json_decode( (string) ( $cached['points'] ?? '[]' ), true );

		foreach ( is_array( $points ) ? $points : [] as $point ) {
			if ( isset( $point['code'] ) && $point['code'] === $code ) {
				$address = (string) ( $point['location']['address_full'] ?? $point['location']['address'] ?? '' );
				break;
			}
		}
	}

	return [
		'code'    => $code,
		'address' => $address,
	];
}

/**
 * Выбранный тариф СДЭК в сессии: вид доставки или '' (не СДЭК).
 *
 * @return string door | office | postamat | ''.
 */
function whoopstore_cdek_chosen_kind() {
	if ( ! whoopstore_cdek_available() || ! WC()->session || ! WC()->shipping() ) {
		return '';
	}

	$chosen = (array) WC()->session->get( 'chosen_shipping_methods', [] );

	foreach ( WC()->shipping()->get_packages() as $key => $package ) {
		$rate_id = $chosen[ $key ] ?? '';
		$rate    = $package['rates'][ $rate_id ] ?? null;

		if ( $rate && WHOOPSTORE_CDEK_METHOD === $rate->get_method_id() ) {
			return whoopstore_cdek_tariff_kind( $rate->get_meta_data()[ \Cdek\MetaKeys::TARIFF_CODE ] ?? 0 );
		}
	}

	return '';
}

/**
 * Адрес ПВЗ заказа для уведомления: «код — адрес».
 *
 * @param WC_Order $order Заказ.
 * @return string
 */
function whoopstore_cdek_order_office( $order ) {
	return (string) $order->get_meta( WHOOPSTORE_CDEK_OFFICE_META );
}
