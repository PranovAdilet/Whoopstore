<?php
/**
 * Данные карточки .device-card из товара WooCommerce.
 *
 * Сама карточка (whoopstore_device_card(), inc/card-component.php) о
 * WooCommerce не знает — она печатает массив. Здесь единственное место, где
 * этот массив собирается из объекта товара, чтобы каждая секция с реальными
 * товарами (сейчас "Вам может понравиться", дальше — каталоги) не повторяла
 * один и тот же набор вызовов и не расходилась в деталях (где-то забыли
 * бейдж, где-то взяли другой размер фото).
 *
 * Только штатное API товара, ничего не подставляется: нет фото — карточка
 * оставляет свою заглушку; нет цены — блока цены нет; нет скидки — нет
 * бейджа. Текст кнопки и модификаторы оформления сюда не входят — это
 * решение секции по макету, а не данные товара.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Массив для whoopstore_device_card() по товару WooCommerce.
 *
 * @param WC_Product $product    Товар.
 * @param string     $image_size Размер вложения для src; srcset/sizes
 *                               WordPress добавит сам. По умолчанию
 *                               woocommerce_single (600px), а не
 *                               woocommerce_thumbnail (300px): медиа-зона
 *                               карточки — 320px, на retina 300px заметно
 *                               мылит. Если у фото такого размера нет
 *                               (оригинал мельче), WordPress отдаст оригинал.
 * @return array
 */
function whoopstore_product_card_data( WC_Product $product, $image_size = 'woocommerce_single' ) {
	$data = [
		'name'       => $product->get_name(),
		'price_html' => $product->get_price_html(),
		'href'       => $product->get_permalink(),
		'image_id'   => $product->get_image_id(),
		'image_size' => $image_size,
	];

	// Бейдж — только по реальному состоянию товара: цену распродажи и сроки
	// её действия проверяет сам WooCommerce. Текст — из макета, тот же, что у
	// статичных карточек (inc/product-data.php).
	if ( $product->is_on_sale() ) {
		$data['badge'] = 'Распродажа';
	}

	return $data;
}
