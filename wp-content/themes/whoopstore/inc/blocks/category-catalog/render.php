<?php
/**
 * Серверный рендер блока whoopstore/category-catalog.
 *
 * Раздел каталога = категория товаров WooCommerce. Разметку печатает
 * whoopstore_category_section() (inc/woocommerce/product-card.php) — та же
 * функция, что и у паттернов каталога, которые ещё не мигрировали. Ни цен,
 * ни названий, ни фотографий у блока в атрибутах нет и быть не может: он
 * хранит только slug'и, всё остальное берётся из товаров на каждом запросе.
 * Поэтому сохранение страницы в редакторе не может «заморозить» каталог.
 *
 * Пустой раздел (категории нет или в ней нет товаров) не выводится —
 * это поведение самой whoopstore_category_section(), здесь не дублируется.
 *
 * Обёртки блок не добавляет: <section class="catalog-section"> должны
 * остаться прямыми потомками холста страницы, иначе перестанут работать
 * отступы (.catalog-page > .catalog-section в theme.css).
 *
 * @var array $attributes Атрибуты блока.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$whoopstore_catalog_categories = isset( $attributes['categories'] ) && is_array( $attributes['categories'] )
	? $attributes['categories']
	: [];

foreach ( $whoopstore_catalog_categories as $whoopstore_catalog_slug ) {
	if ( ! is_string( $whoopstore_catalog_slug ) || '' === $whoopstore_catalog_slug ) {
		continue;
	}

	whoopstore_category_section( $whoopstore_catalog_slug );
}
