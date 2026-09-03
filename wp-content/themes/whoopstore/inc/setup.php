<?php
/**
 * Theme setup: theme supports + asset enqueue.
 * Pilot-этап: только то, что реально используется Header/Footer/Главной/страницей товара.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
} );

add_action( 'wp_enqueue_scripts', function () {
	$version = wp_get_theme()->get( 'Version' );

	// Общесайтовые стили (header/footer/hero/advantages/help-block/кнопки).
	wp_enqueue_style( 'whoopstore-theme', get_theme_file_uri( 'assets/css/theme.css' ), [], $version );

	// Точечные WC-переопределения сверх theme.json (галерея, аккордеон, мини-корзина).
	wp_enqueue_style( 'whoopstore-woocommerce', get_theme_file_uri( 'assets/css/woocommerce.css' ), [ 'whoopstore-theme' ], $version );

	// Бургер-меню — нужен на каждой странице (Header есть везде).
	wp_enqueue_script( 'whoopstore-header-nav', get_theme_file_uri( 'assets/js/header-nav.js' ), [], $version, true );

	// Sticky-scroll блока "Преимущества" — только на Главной, единственном подтверждённом месте.
	if ( is_front_page() ) {
		wp_enqueue_script( 'whoopstore-advantages', get_theme_file_uri( 'assets/js/advantages-sticky-scroll.js' ), [], $version, true );
	}

	// Тост "Товар добавлен в корзину" — реагирует на нативное событие WooCommerce Blocks
	// (wc-blocks_added_to_cart), подключаем только там, где есть форма добавления в корзину.
	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_enqueue_script( 'whoopstore-cart-toast', get_theme_file_uri( 'assets/js/cart-toast.js' ), [], $version, true );
	}
} );

// NB: текст бейджа "Распродажа" (вместо стандартного WC "Sale!") сознательно НЕ переопределён —
// у pilot-товара нет sale_price, проверить хук не на чем. Добавить фильтром
// woocommerce_sale_flash, когда появится первый товар со скидкой.
