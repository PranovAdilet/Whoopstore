<?php
/**
 * Title: Ремешки 5.0 — каталог по разделам
 * Slug: whoopstore/straps-50-catalog
 *
 * Каталог страницы "Ремешки для WHOOP 5.0": 7 разделов из макета (Figma,
 * desktop node `1:2120`, сырой дамп docs/figma-design/raw/page-50-desktop.code.tsx.txt).
 *
 * С 2026-09-15 карточки — настоящие товары WooCommerce. Раздел каталога — это
 * категория товаров (дочерняя к «Ремешкам»): заголовок раздела — название
 * категории, порядок карточек — «Порядок в меню» у товара, цена, фото и
 * ссылка «Купить» — из самого товара. Товары заведены по данным макета
 * (названия, цены, фото, группировка). В паттерне остался только порядок
 * разделов на странице — это решение макета, а не данные товара.
 *
 * Раздел рисует whoopstore_category_section() (inc/woocommerce/product-card.php)
 * через общие whoopstore_catalog_section() и whoopstore_device_card() — та же
 * карточка, что на странице MG и на Главной.
 *
 * ⚠️ У 4 товаров нет фото — Figma отдаёт по их узлам пустой PNG: LeatherLuxe
 * Tapered Band Camel with Gold, SportFlex Band Rush, SportFlex Band Drift,
 * SuperKnit Band Obsidian with Titanium. У карточки остаётся штатная
 * заглушка; фото добавляется в самом товаре.
 */

// Разделы в порядке из макета — slug категорий товаров. «Ремешки на бицепс»
// — общая категория с каталогом MG: это одни и те же товары «для Whoop 5.0 / MG».
$whoopstore_straps_50_sections = [
	'leatherluxe-5-0',
	'cloudknit-5-0',
	'sportflex-5-0',
	'superknit-luxe-5-0',
	'superknit-5-0',
	'coreknit-5-0',
	'coreknit-bicep',
];
?>
<!-- wp:html -->
<?php
foreach ( $whoopstore_straps_50_sections as $whoopstore_section ) {
	// id раздела = slug категории: на него ведут плитки-категории над каталогом
	// (см. straps-50-categories.php).
	whoopstore_category_section( $whoopstore_section, [ 'anchor' => $whoopstore_section ] );
}
?>
<!-- /wp:html -->
