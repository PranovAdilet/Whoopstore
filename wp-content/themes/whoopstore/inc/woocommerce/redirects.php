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
 *
 * 2026-09-15: с настоящими товарами у каждого раздела каталога своя категория
 * (на неё ведёт промежуточная крошка страницы товара), поэтому тот же принцип
 * распространён на все категории: раздел ремешков — на страницу своего
 * каталога, трекеры и аксессуары — на секции Главной, подписки и активация —
 * на страницу «Подписки».
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Категория товаров => путь свёрстанной страницы (или секции) по макету.
 *
 * @return array slug категории => путь.
 */
function whoopstore_category_redirects() {
	$catalog_50 = '/remeshki-whoop-5-0/';
	$catalog_mg = '/remeshki-whoop-mg/';

	return apply_filters( 'whoopstore_category_redirects', [
		'remeshki'               => $catalog_50,
		'leatherluxe-5-0'        => $catalog_50,
		'cloudknit-5-0'          => $catalog_50,
		'sportflex-5-0'          => $catalog_50,
		'superknit-luxe-5-0'     => $catalog_50,
		'superknit-5-0'          => $catalog_50,
		'coreknit-5-0'           => $catalog_50,
		// Бицепс — общий раздел обоих каталогов; первым в меню идёт 5.0.
		'coreknit-bicep'         => $catalog_50,
		'leatherluxe-mg'         => $catalog_mg,
		'cloudknit-mg'           => $catalog_mg,
		'sportflex-mg'           => $catalog_mg,
		'superknit-luxe-mg'      => $catalog_mg,
		'superknit-mg'           => $catalog_mg,
		'trekery'                => '/#devices',
		'trekery-1-god'          => '/#devices',
		'trekery-1-mesyac'       => '/#devices',
		'aksessuary'             => '/#accessories',
		'aktivaciya-i-prodlenie' => '/podpiski/',
		'podpiski-whoop'         => '/podpiski/',
		// Страница Whoop 4.0 (2026-11-11): ремешки и продление подписки для 4.0.
		'whoop-4-0'              => '/whoop4/',
		'remeshki-whoop-4-0'     => '/whoop4/',
		'prodlenie-whoop-4-0'    => '/whoop4/',
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

/**
 * Крошки страницы товара ведут сразу на свёрстанную страницу раздела.
 *
 * WooCommerce ставит в промежуточные крошки адреса архивов категорий, а те по
 * карте выше уходят 301-м на страницы каталога. Для покупателя разницы нет,
 * но поисковый аудит считает каждую такую ссылку лишним редиректом (правка
 * 2026-11-11). Карта та же — второй не заводим.
 *
 * @param array $crumbs Крошки WooCommerce: [ [ название, адрес ], … ].
 * @return array
 */
add_filter( 'woocommerce_get_breadcrumb', function ( $crumbs ) {
	$targets = [];

	foreach ( whoopstore_category_redirects() as $slug => $path ) {
		$link = get_term_link( $slug, 'product_cat' );

		if ( ! is_wp_error( $link ) ) {
			$targets[ untrailingslashit( $link ) ] = home_url( $path );
		}
	}

	foreach ( $crumbs as $i => $crumb ) {
		$url = isset( $crumb[1] ) ? untrailingslashit( (string) $crumb[1] ) : '';

		if ( isset( $targets[ $url ] ) ) {
			$crumbs[ $i ][1] = $targets[ $url ];
		}
	}

	return $crumbs;
} );

/**
 * Служебные страницы WooCommerce «Корзина» и «Оформление заказа» — на Главную
 * с открытой корзиной.
 *
 * В макете этих страниц нет: покупатель оформляет заказ в выезжающей корзине.
 * Удалить их нельзя — от страницы оформления WooCommerce строит служебные
 * адреса «Оплатить заказ» (туда возвращает ЮKassa при неудачной оплате) и
 * «Заказ получен». Поэтому перенаправляется только сама страница, без
 * служебных окончаний и без параметров в адресе: с параметрами приходят
 * штатные сценарии WooCommerce (оплата заказа по ссылке, добавление товара
 * по ссылке `?add-to-cart=`).
 *
 * Корзину на Главной открывает assets/js/cart-drawer.js по `?cart=open`.
 */
add_action( 'template_redirect', function () {
	if ( ! function_exists( 'is_cart' ) || is_admin() ) {
		return;
	}

	if ( ! is_cart() && ! is_checkout() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- только факт наличия параметров.
	if ( is_wc_endpoint_url() || ! empty( $_GET ) ) {
		return;
	}

	wp_safe_redirect( home_url( '/?cart=open' ), 302 );
	exit;
}, 5 );
