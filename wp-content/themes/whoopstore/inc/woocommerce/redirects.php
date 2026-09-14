<?php
/**
 * Перенаправление штатных архивов WooCommerce на свёрстанные по макету страницы.
 *
 * В Figma архивов категорий товаров нет вовсе — каталог ремешков нарисован
 * двумя отдельными страницами ("Ремешки для WHOOP 5.0" и "Ремешки для
 * WHOOP MG"), а /product-category/<slug>/ создаёт сам WooCommerce.
 * Решение заказчика 2026-09-11: не отключать архив (на него ведёт крошка
 * "Ремешки" на странице товара, и на нём завязана внутренняя логика
 * WooCommerce), а отправлять посетителя 301-м на соответствующую
 * свёрстанную страницу. URL остаётся рабочим, ничего не ломается.
 *
 * Карта соответствий — одна константа ниже: добавить новую категорию =
 * дописать строку, без правки логики.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Категория товаров => путь свёрстанной страницы каталога.
 *
 * @return array slug категории => путь.
 */
function whoopstore_category_redirects() {
	return apply_filters( 'whoopstore_category_redirects', [
		'remeshki' => '/remeshki-whoop-5-0/',
	] );
}

add_action( 'template_redirect', function () {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return;
	}

	$term = get_queried_object();

	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$map = whoopstore_category_redirects();

	if ( empty( $map[ $term->slug ] ) ) {
		return;
	}

	wp_safe_redirect( home_url( $map[ $term->slug ] ), 301 );
	exit;
} );
