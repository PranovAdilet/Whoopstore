<?php
/**
 * Страница товара: раскладка хуков WooCommerce под макет Figma
 * (docs/figma-design/states-and-components.md, nodes 30:143 / 30:1180).
 *
 * Почему классические шаблоны, а не WC-блоки (решение согласовано 2026-09-11):
 * в макете у галереи есть кнопка-лупа поверх фото и точки-пилюли под фото —
 * это ровно то, что даёт штатная классическая галерея WooCommerce
 * (photoswipe + flexslider), то есть макет с неё и рисовался. Блочная
 * woocommerce/product-gallery ни лупы, ни точек не отдаёт, а её разметку
 * (Interactivity API) пришлось бы переопределять CSS поверх чужого DOM,
 * который меняется от версии к версии. Classic Template block
 * (woocommerce/legacy-template в templates/single-product.html) —
 * штатный и документированный механизм WooCommerce, а переопределения
 * шаблонов лежат в themes/whoopstore/woocommerce/ — тоже штатный путь
 * (см. правило 7 CLAUDE.md: предпочитать стандартные механизмы).
 *
 * Здесь — только перестановка хуков. Сама разметка — в templates-
 * переопределениях, стили — в assets/css/woocommerce.css.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp', function () {
	if ( ! is_product() ) {
		return;
	}

	// <main> уже даёт блочный шаблон (templates/single-product.html),
	// второй враппер #primary/.site-main от WooCommerce не нужен.
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

	// Крошки в макете стоят внутри контентной сетки страницы, а не над ней,
	// поэтому выводим их из content-single-product.php, а не из
	// woocommerce_before_main_content.
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

	// Колонка справа по Figma: H1 → цена → форма (размер + кнопка) →
	// описание → аккордеоны. Рейтинга, краткого описания-excerpt, мета
	// (SKU/категории) и кнопок "поделиться" в макете нет.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

	// Бейдж "Распродажа" поверх фото на странице товара в макете отсутствует.
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );

	// Вкладки "Описание / Отзывы" и апселлы — в макете их нет: вместо вкладок
	// два аккордеона внутри правой колонки (см. summary ниже).
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
} );

// Цена в колонке данных по Figma (node 30:173/30:1221) — 20px Medium
// desktop / 18px mobile с трекингом −1%, то есть ровно общий стиль
// .card-heading темы (см. раздел "Typography" в theme.css). Штатный фильтр
// класса даёт переиспользовать его без копии тех же деклараций в CSS.
add_filter( 'woocommerce_product_price_class', function ( $class ) {
	return is_product() ? $class . ' card-heading' : $class;
} );

// Текст кнопки из макета (node 30:275/30:1247). Штатная строка WooCommerce
// осталась бы английской: сайт в локали ru_RU, но .mo-перевод WooCommerce на
// сервере не установлен (та же причина, что у переводов в template-hooks.php).
add_filter( 'woocommerce_product_single_add_to_cart_text', function () {
	return 'Добавить в корзину';
} );
