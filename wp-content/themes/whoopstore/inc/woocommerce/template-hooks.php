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

// Заглушка вместо фото товара (правка 2026-09-18, QA карточки товара).
// Стандартная картинка WooCommerce — белый квадрат с серым значком — на
// тёмном сайте выглядела чужеродно. Своя — прозрачный квадрат с приглушённым
// знаком WHOOP, тот же файл стоит фоном у пустого слота карточки в каталогах.
// Фильтр штатный: его же используют страница товара и корзина.
add_filter( 'woocommerce_placeholder_img_src', function () {
	return get_theme_file_uri( 'assets/icons/product-placeholder.svg' );
} );

// Подпись заглушки для скринридеров: в русском пакете WooCommerce опечатка —
// «Ожидается изображения товара». Исправлено только согласование, смысл тот же.
add_filter( 'gettext', function ( $translated, $text, $domain ) {
	if ( 'woocommerce' === $domain && 'Awaiting product image' === $text ) {
		return 'Ожидается изображение товара';
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

// Уведомление "«Товар» has been added to your cart." остаётся английским даже с
// установленным русским пакетом WooCommerce — этой строки в нём нет (проверено
// 2026-09-16 на ru_RU). Видно её в запасном пути без JS: форма товара уходит
// обычным POST, и WooCommerce показывает это уведомление. Строка множественная
// (_n), поэтому фильтр ngettext, а не gettext.
add_filter( 'ngettext', function ( $translated, $single, $plural, $number, $domain ) {
	if ( 'woocommerce' !== $domain || '%s has been added to your cart.' !== $single ) {
		return $translated;
	}

	return 1 === (int) $number ? '%s добавлен в корзину.' : '%s добавлены в корзину.';
}, 10, 5 );
