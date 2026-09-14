<?php
/**
 * Точечные хуки WooCommerce. Каждый — под конкретную, подтверждённую QA-находку,
 * ничего "на будущее" (см. sanity-check pilot-архитектуры).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// P1-1 (часть) / раздел 5 QA: "Choose an option" в выпадающем списке атрибута —
// сайт работает в локали ru_RU, но .mo-перевод WooCommerce на сервере не установлен,
// поэтому нативная строка остаётся на английском. Переопределяем именно этот текст
// официальным русским переводом WooCommerce, не трогая остальную локализацию сайта.
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', function ( $args ) {
	$args['show_option_none'] = __( 'Выберите вариант', 'woocommerce' );
	return $args;
} );

// Полировка pilot-блоков (Figma-скрины Главной): бейдж товара по Figma —
// "Распродажа", а не английское "Sale!"/"Sale" — та же причина, что и выше
// (отсутствует .mo-перевод WooCommerce). Строка WooCommerce Blocks (карточки
// в product-collection, наш случай) — 'Sale' без "!" (см.
// src/Blocks/BlockTypes/ProductImage.php); классический шаблон использует
// 'Sale!'. Переопределяем обе, не устанавливая языковой пакет целиком.
add_filter( 'gettext', function ( $translated, $text, $domain ) {
	if ( 'woocommerce' === $domain && in_array( $text, [ 'Sale!', 'Sale' ], true ) ) {
		return 'Распродажа';
	}
	return $translated;
}, 10, 3 );

// Скрин staging (карточка "Ремешки"): кнопка variable-товара в сетке —
// "Select options" на английском, та же причина (нет .mo-перевода).
add_filter( 'gettext', function ( $translated, $text, $domain ) {
	if ( 'woocommerce' === $domain && 'Select options' === $text ) {
		return 'Выбрать опции';
	}
	return $translated;
}, 10, 3 );
