<?php
/**
 * Серверный рендер блока whoopstore/breadcrumbs.
 *
 * Разметку печатает whoopstore_breadcrumbs() (inc/page-head-component.php) —
 * та же функция, что и у whoopstore_page_head() на страницах, которые ещё не
 * мигрировали. Своей копии крошек у блока нет намеренно: иначе два источника
 * одной разметки разошлись бы при первой же правке.
 *
 * Обёртки блок не добавляет — вывод начинается сразу с <nav class="page-head__crumbs">,
 * потому что вёрстка рассчитывает на прямое родство внутри .page-head.
 *
 * @var array $attributes Атрибуты блока.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$whoopstore_crumb_label = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';

// Пустое поле — берём заголовок страницы: так крошка не исчезает, если
// заказчик очистил поле, и по умолчанию показывает осмысленный текст.
if ( '' === $whoopstore_crumb_label ) {
	$whoopstore_crumb_label = get_the_title();
}

whoopstore_breadcrumbs( $whoopstore_crumb_label );
