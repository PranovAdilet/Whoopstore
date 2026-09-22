<?php
/**
 * Подпись под названием способа доставки в форме «Ваш заказ».
 *
 * В макете у плиток доставки есть вторая строка: «1 500 ₽ · день в день по
 * Москве», «Бесплатно: Москва, ул. Олеко Дундича, 3». Цену форма берёт из
 * тарифа, а текстовая часть — это условия конкретного способа, их должен
 * менять менеджер, а не разработчик.
 *
 * У штатных «Единая ставка» и «Самовывоз» поля описания нет, но с WooCommerce
 * 9.2 у тарифа есть свойство description, и Store API его отдаёт. Поэтому:
 * поле «Подпись в оформлении заказа» добавляется в настройки способа (в зоне
 * доставки), а его значение переносится в тариф.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Способы доставки, у которых есть поле подписи.
 *
 * @return string[]
 */
function whoopstore_shipping_methods_with_description() {
	return [ 'flat_rate', 'free_shipping', 'local_pickup' ];
}

foreach ( whoopstore_shipping_methods_with_description() as $whoopstore_method_id ) {
	add_filter( "woocommerce_shipping_instance_form_fields_{$whoopstore_method_id}", function ( $fields ) {
		$fields['whoopstore_description'] = [
			'title'       => 'Подпись в оформлении заказа',
			'type'        => 'text',
			'default'     => '',
			'desc_tip'    => true,
			'description' => 'Короткий текст под названием способа в форме «Ваш заказ»: условия доставки или адрес самовывоза.',
		];

		return $fields;
	} );
}

add_filter( 'woocommerce_package_rates', function ( $rates ) {
	foreach ( $rates as $rate ) {
		if ( ! in_array( $rate->get_method_id(), whoopstore_shipping_methods_with_description(), true ) ) {
			continue;
		}

		$method = WC_Shipping_Zones::get_shipping_method( $rate->get_instance_id() );

		if ( ! $method ) {
			continue;
		}

		$description = trim( (string) $method->get_option( 'whoopstore_description' ) );

		if ( '' !== $description ) {
			$rate->set_description( $description );
		}
	}

	return $rates;
} );

/**
 * Где забрать заказ — в письме покупателю и магазину.
 *
 * WooCommerce печатает в письме адрес доставки, но у самовывоза и у доставки
 * в пункт выдачи адреса доставки нет: покупатель не видит, куда ехать.
 * Адрес самовывоза берётся из подписи способа доставки (настройка в зоне),
 * пункт выдачи СДЭК — из заказа (cdek.php). Ничего не выдумывается: если
 * адрес не заполнен, строки нет.
 *
 * @param WC_Order $order         Заказ.
 * @param bool     $sent_to_admin Письмо магазину.
 * @param bool     $plain_text    Текстовое письмо.
 */
add_action( 'woocommerce_email_order_meta', function ( $order, $sent_to_admin, $plain_text ) {
	$lines = [];

	foreach ( $order->get_shipping_methods() as $item ) {
		if ( 0 !== strpos( (string) $item->get_method_id(), 'local_pickup' ) ) {
			continue;
		}

		$method  = WC_Shipping_Zones::get_shipping_method( $item->get_instance_id() );
		$address = $method ? trim( (string) $method->get_option( 'whoopstore_description' ) ) : '';

		if ( '' !== $address ) {
			$lines[] = 'Самовывоз: ' . $address;
		}
	}

	$office = function_exists( 'whoopstore_cdek_order_office' ) ? whoopstore_cdek_order_office( $order ) : '';

	if ( '' !== $office ) {
		$lines[] = 'Пункт выдачи СДЭК: ' . $office;
	}

	if ( ! $lines ) {
		return;
	}

	if ( $plain_text ) {
		echo "\n" . esc_html( implode( "\n", $lines ) ) . "\n";
		return;
	}

	echo '<p style="margin:0 0 16px;">' . implode( '<br>', array_map( 'esc_html', $lines ) ) . '</p>';
}, 10, 3 );
