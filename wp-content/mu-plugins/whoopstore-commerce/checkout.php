<?php
/**
 * Оформление заказа под форму «Ваш заказ» из Figma.
 *
 * Форма отправляется в WooCommerce Store API (POST /wc/store/v1/checkout),
 * заказ создаёт штатный WooCommerce. Здесь только то, чего в штатных полях
 * нет или что в макете устроено иначе:
 *
 *  - ФИО — одно поле, оно уходит в billing first_name, поэтому фамилия не
 *    обязательна;
 *  - адрес (город, индекс, улица и дом, квартира) нужен только для доставки
 *    по адресу. У самовывоза и у заказа из одних виртуальных товаров адреса
 *    нет, поэтому на уровне страны поля адреса не обязательны, а
 *    обязательность проверяется ниже — по выбранному способу доставки;
 *  - Telegram и согласие с политикой — дополнительные поля заказа (штатный
 *    API дополнительных полей WooCommerce); комментарий — штатная заметка
 *    покупателя (customer_note), своего поля для него не нужно.
 */

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WHOOPSTORE_FIELD_TELEGRAM = 'whoopstore/telegram';
const WHOOPSTORE_FIELD_CONSENT  = 'whoopstore/privacy-consent';

add_action( 'woocommerce_init', function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field( [
		'id'       => WHOOPSTORE_FIELD_TELEGRAM,
		'label'    => 'Telegram',
		'location' => 'order',
		'type'     => 'text',
		'required' => false,
	] );

	// Обязательным чекбокс штатный API сделать не даёт ("Registering checkbox
	// fields as required is not supported"), поэтому согласие проверяется
	// отдельно — см. whoopstore_validate_checkout_request().
	woocommerce_register_additional_checkout_field( [
		'id'       => WHOOPSTORE_FIELD_CONSENT,
		'label'    => 'Я согласен(-а) с политикой конфиденциальности',
		'location' => 'order',
		'type'     => 'checkbox',
	] );
} );

/**
 * Обязательность полей адреса для России.
 *
 * У России в WooCommerce нет списка регионов, а в форме из макета нет ни
 * региона, ни фамилии. Город, индекс и улица обязательны только для доставки
 * по адресу — это проверяет whoopstore_validate_checkout_request().
 */
add_filter( 'woocommerce_get_country_locale', function ( $locale ) {
	$locale['RU'] = array_merge(
		isset( $locale['RU'] ) ? (array) $locale['RU'] : [],
		[
			'last_name' => [ 'required' => false ],
			'address_1' => [ 'required' => false ],
			'city'      => [ 'required' => false ],
			'postcode'  => [ 'required' => false ],
			'state'     => [ 'required' => false, 'hidden' => true ],
		]
	);

	return $locale;
} );

/**
 * Серверные проверки заказа, которых нет в штатной схеме Store API.
 *
 * Хук срабатывает после того, как адреса и дополнительные поля из запроса
 * записаны в заказ, но до оплаты. Исключение RouteException Store API
 * возвращает как ответ 400 с текстом ошибки — заказ не оформляется.
 *
 * @param WC_Order        $order   Заказ-черновик.
 * @param WP_REST_Request $request Запрос оформления.
 * @throws RouteException Если нет согласия или адреса для доставки по адресу.
 */
function whoopstore_validate_checkout_request( $order, $request ) {
	$fields = (array) $request['additional_fields'];

	if ( empty( $fields[ WHOOPSTORE_FIELD_CONSENT ] ) ) {
		throw new RouteException( 'whoopstore_privacy_consent_required', 'Подтвердите согласие с политикой конфиденциальности.', 400 );
	}

	// Настройку «Телефон: обязательно» Store API не применяет (проверено на
	// staging 2026-09-15: заказ без телефона оформлялся), а без телефона
	// менеджер не свяжется с покупателем. Магазин продаёт только по России, в
	// форме префикс +7 зашит, поэтому номер — это 11 цифр: одно «+7» из
	// пустого поля проверку не проходит.
	if ( strlen( preg_replace( '/\D/', '', (string) $order->get_billing_phone() ) ) < 11 ) {
		throw new RouteException( 'whoopstore_phone_required', 'Укажите телефон полностью.', 400 );
	}

	if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return;
	}

	$chosen    = (array) WC()->session->get( 'chosen_shipping_methods', [] );
	$is_pickup = (bool) array_filter( $chosen, function ( $rate_id ) {
		return 0 === strpos( (string) $rate_id, 'local_pickup' );
	} );

	if ( $is_pickup ) {
		return;
	}

	$required = [
		'city'      => 'город',
		'postcode'  => 'индекс',
		'address_1' => 'улицу и дом',
	];

	foreach ( $required as $key => $label ) {
		$getter = 'get_shipping_' . $key;

		if ( '' === trim( (string) $order->$getter() ) ) {
			throw new RouteException( 'whoopstore_shipping_address_required', sprintf( 'Укажите %s для доставки.', $label ), 400 );
		}
	}
}
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'whoopstore_validate_checkout_request', 10, 2 );

/**
 * Тестовый способ оплаты — только на staging.
 *
 * На staging включён штатный «Чек» под названием «Тестовая оплата (staging)»:
 * он создаёт заказ без списания денег и нужен для проверки оформления, пока
 * нет ключей ЮKassa. Если эта база окажется на боевом окружении, способ не
 * должен появиться у покупателей — отсюда проверка WP_ENVIRONMENT_TYPE.
 */
add_filter( 'woocommerce_available_payment_gateways', function ( $gateways ) {
	if ( 'staging' !== wp_get_environment_type() ) {
		unset( $gateways['cheque'] );
	}

	return $gateways;
} );

/**
 * ЮKassa на staging — только с тестовым ключом.
 *
 * Правило проекта: на staging никаких боевых ключей и реальных списаний.
 * Тестовый секретный ключ ЮKassa начинается с «test_», боевой — с «live_».
 * С любым другим ключом (или без него — например, при подключении через
 * OAuth, где тест от боя здесь не отличить) способы оплаты ЮKassa на staging
 * покупателю не предлагаются, и форма оформления берёт «Тестовую оплату».
 * На боевом окружении фильтр ничего не делает.
 */
add_filter( 'woocommerce_available_payment_gateways', function ( $gateways ) {
	if ( 'staging' !== wp_get_environment_type()
		|| 0 === strpos( (string) get_option( 'yookassa_shop_password' ), 'test_' ) ) {
		return $gateways;
	}

	foreach ( array_keys( $gateways ) as $id ) {
		if ( 0 === strpos( (string) $id, 'yookassa' ) ) {
			unset( $gateways[ $id ] );
		}
	}

	return $gateways;
} );

/**
 * Выключенный «Плати частями» ЮKassa не грузит свой виджет.
 *
 * ЮKassa 2.17.1 (YooKassaSberBnpl::showInfo() и соседние методы) подключает
 * скрипт виджета с yookassa.ru и свои стили на странице товара ещё до
 * проверки, включён ли способ. Способ выключен — снимаем их из очереди, пока
 * они не выведены: в блочной теме страница собирается до wp_head, в
 * классической вывод может быть и позже — отсюда два хука. Стиль общий с
 * блоком рассрочки, поэтому он снимается, только если выключено и то и другое.
 */
function whoopstore_dequeue_disabled_yookassa_widgets() {
	if ( '1' === get_option( 'yookassa_sber_bnpl_enabled' ) ) {
		return;
	}

	wp_dequeue_script( 'yookassa-sber-bnpl-widget' );

	if ( '1' !== get_option( 'yookassa_epl_installments' ) && '1' !== get_option( 'yookassa_add_installments_block' ) ) {
		wp_dequeue_style( 'yookassa' );
	}
}
add_action( 'wp_head', 'whoopstore_dequeue_disabled_yookassa_widgets', 0 );
add_action( 'wp_footer', 'whoopstore_dequeue_disabled_yookassa_widgets', 0 );
