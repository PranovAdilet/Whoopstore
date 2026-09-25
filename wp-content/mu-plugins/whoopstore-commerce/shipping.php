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

/**
 * Способы доставки, которым можно ограничить город.
 *
 * Самовывоза в списке нет намеренно: туда покупатель приезжает сам, и город
 * доставки к нему отношения не имеет.
 *
 * @return string[]
 */
function whoopstore_shipping_methods_with_city_limit() {
	return [ 'flat_rate', 'free_shipping' ];
}

foreach ( whoopstore_shipping_methods_with_city_limit() as $whoopstore_method_id ) {
	add_filter( "woocommerce_shipping_instance_form_fields_{$whoopstore_method_id}", function ( $fields ) {
		$fields['whoopstore_moscow_only'] = [
			'title'       => 'Только Москва',
			'type'        => 'checkbox',
			'label'       => 'Показывать способ, только когда доставка в Москву',
			'default'     => 'no',
			'description' => 'Пока город не указан, способ показывается всем: скрывать его до заполнения адреса значило бы прятать доставку в пустой корзине.',
		];

		return $fields;
	} );
}

/**
 * Город доставки — Москва?
 *
 * Сравнение по названию города, а не по индексу: индекс у Москвы занимает
 * несколько несмежных диапазонов (101000–135999, Зеленоград, Новая Москва
 * вперемешку с областными), и ошибиться там проще, чем в названии. Название
 * приводится к общему виду: регистр, «ё», приставки «г.»/«город», лишние
 * запятые и пробелы.
 *
 * Пустой город — не повод скрывать способ: адрес ещё не заполнен.
 *
 * @param array $destination Адрес доставки из пакета WooCommerce.
 * @return bool
 */
function whoopstore_destination_is_moscow( $destination ) {
	$city = isset( $destination['city'] ) ? (string) $destination['city'] : '';
	$city = mb_strtolower( trim( $city ), 'UTF-8' );

	if ( '' === $city ) {
		return true;
	}

	$city = str_replace( 'ё', 'е', $city );
	$city = preg_replace( '/(^|\s)(г|гор|город)\.?(\s|$)/u', ' ', $city );
	$city = trim( preg_replace( '/[\s,\.]+/u', ' ', $city ) );

	return in_array( $city, [ 'москва', 'moscow' ], true );
}

/**
 * Способ с ограничением по городу не предлагается за пределами Москвы.
 *
 * Решение заказчика 2026-09-25: «Экспресс доставка» — день в день по Москве,
 * в других городах её быть не должно. Сделано настройкой способа, а не
 * проверкой его id в коде: способов доставки может стать больше, а правило
 * должно оставаться редактируемым из админки.
 *
 * Зоной доставки это не решается: зоны WooCommerce различают страны, регионы
 * и индексы, а у России списка регионов в WooCommerce нет — остались бы
 * только индексы (см. выше, почему они ненадёжны).
 */
add_filter( 'woocommerce_package_rates', function ( $rates, $package ) {
	$destination_is_moscow = whoopstore_destination_is_moscow( isset( $package['destination'] ) ? (array) $package['destination'] : [] );

	if ( $destination_is_moscow ) {
		return $rates;
	}

	foreach ( $rates as $key => $rate ) {
		if ( ! in_array( $rate->get_method_id(), whoopstore_shipping_methods_with_city_limit(), true ) ) {
			continue;
		}

		$method = WC_Shipping_Zones::get_shipping_method( $rate->get_instance_id() );

		if ( $method && 'yes' === $method->get_option( 'whoopstore_moscow_only' ) ) {
			unset( $rates[ $key ] );
		}
	}

	return $rates;
}, 10, 2 );

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
