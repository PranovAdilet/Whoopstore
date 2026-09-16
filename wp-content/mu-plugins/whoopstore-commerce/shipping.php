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
