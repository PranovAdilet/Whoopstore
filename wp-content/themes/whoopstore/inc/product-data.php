<?php
/**
 * Общие данные о товарах, которые встречаются больше чем на одной странице.
 *
 * Пока это ровно один набор — три трекера с годовой подпиской. Они есть и в
 * первом ряду сетки на Главной (patterns/device-grid.php), и в секции
 * "Купить Whoop с подпиской" на странице "Подписки"
 * (patterns/subscriptions-buy.php), причём в Figma это буквально одни и те же
 * карточки: совпадают названия, обе цены и бейдж "Распродажа" (сверено по
 * raw-дампам обеих страниц). Держать два списка с одинаковыми ценами —
 * прямой путь к тому, что при следующей правке цен они разъедутся, поэтому
 * источник один.
 *
 * Это ВРЕМЕННОЕ решение уровня вёрстки: когда появятся реальные товары
 * WooCommerce, обе секции заменяются на woocommerce/product-collection, а
 * этот файл удаляется — цены будут жить в товарах, а не в теме.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Трекеры с подпиской на 1 год (One / Life / Peak), в порядке из макета.
 *
 * @return array Массив в формате whoopstore_device_card().
 */
function whoopstore_annual_plan_devices() {
	return [
		[ 'name' => 'Новый трекер Whoop One с подпиской на 1 год (Whoop 5.0)', 'price' => '31 990,00 ₽', 'old_price' => '33 990,00 ₽', 'badge' => 'Распродажа', 'image' => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-1.webp' ],
		[ 'name' => 'Новый трекер Whoop Life с подпиской на 1 год (Whoop MG)', 'price' => '49 990,00 ₽', 'old_price' => '54 990,00 ₽', 'badge' => 'Распродажа', 'image' => '/wp-content/uploads/2026/09/device-whoop-life-annual.webp' ],
		[ 'name' => 'Новый трекер Whoop Peak с подпиской на 1 год (Whoop 5.0)', 'price' => '34 990,00 ₽', 'old_price' => '41 990,00 ₽', 'badge' => 'Распродажа', 'image' => '/wp-content/uploads/2026/09/device-whoop-peak-annual.webp' ],
	];
}
