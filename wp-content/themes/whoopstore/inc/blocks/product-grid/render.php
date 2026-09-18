<?php
/**
 * Серверный рендер блока whoopstore/product-grid.
 *
 * Секция с карточками товаров одной категории. В атрибутах живут только slug
 * категории и решения по оформлению — ни названий, ни цен, ни ID товаров.
 * Разметку печатают те же функции, что и раньше у паттернов
 * (whoopstore_category_section / whoopstore_catalog_section), поэтому вёрстка
 * карточки остаётся ровно одна на весь сайт.
 *
 * Два оформления:
 *
 *  - `grid` — обычный раздел каталога: заголовок и сетка карточек. Это то же
 *    самое, что делал паттерн subscriptions-buy;
 *  - `plan` — карточка тарифа из макета «Подписок»: водяной знак ONE/PEAK/LIFE,
 *    мокап телефона и общее фото браслета. У товаров-подписок собственных фото
 *    нет, так что это не подмена, а заглушка по макету.
 *
 * Порядок карточек — «Порядок в меню» товара, как во всех остальных секциях
 * сайта. Единственное исключение — оформление `plan`, и вот почему.
 *
 * Те же три товара-подписки стоят в ДВУХ секциях, и Figma задаёт им разный
 * порядок:
 *   - Главная, «Активация и продление»: ONE → PEAK → LIFE (узлы 1:531 / 1:546 /
 *     1:516), плюс карточка активации;
 *   - «Подписки», «Выберите подписку»: LIFE → ONE → PEAK (узлы 1:3019 / 1:3034 /
 *     1:3049).
 *
 * `menu_order` — одно значение на товар, выразить им оба порядка физически
 * нельзя: 2026-09-18 это проверено на живой странице — приведение menu_order к
 * порядку «Подписок» сразу переставило карточки на Главной. Поэтому порядок
 * тарифов остаётся решением секции и живёт здесь, а menu_order держит порядок
 * Главной. Если у товара нет узнаваемого тарифа в названии, он встаёт в конец,
 * не ломая порядок остальных.
 *
 * Обёртки блок не добавляет: <section class="catalog-section"> должны
 * оставаться прямыми потомками холста страницы, иначе перестанут работать
 * отступы (.catalog-page > .catalog-section и аналоги в theme.css).
 *
 * @var array $attributes Атрибуты блока.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$whoopstore_grid_category = isset( $attributes['category'] ) ? trim( (string) $attributes['category'] ) : '';

if ( '' === $whoopstore_grid_category ) {
	return;
}

$whoopstore_grid_title  = isset( $attributes['title'] ) ? trim( (string) $attributes['title'] ) : '';
$whoopstore_grid_anchor = isset( $attributes['anchor'] ) ? trim( (string) $attributes['anchor'] ) : '';
$whoopstore_grid_layout = isset( $attributes['layout'] ) ? (string) $attributes['layout'] : 'grid';

if ( 'plan' !== $whoopstore_grid_layout ) {
	// Обычный раздел каталога: вся логика уже есть в общей функции, включая
	// пропуск пустой категории и заголовок по умолчанию из названия термина.
	whoopstore_category_section(
		$whoopstore_grid_category,
		[
			'title'  => $whoopstore_grid_title,
			'anchor' => $whoopstore_grid_anchor,
		]
	);

	return;
}

// Оформление тарифов: к данным товара добавляются решения секции по макету.
$whoopstore_grid_cards = whoopstore_category_cards(
	$whoopstore_grid_category,
	[
		'modifier' => 'plan',
		'phone'    => '/wp-content/uploads/2026/09/subscriptions-plan-phone.webp',
		'image'    => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp',
	]
);

if ( ! $whoopstore_grid_cards ) {
	return;
}

// Слово-подложка берётся из названия товара: отдельного поля под тариф у
// товара нет, см. whoopstore_plan_watermark().
$whoopstore_grid_cards = array_map(
	function ( $card ) {
		return array_merge( $card, [ 'watermark' => whoopstore_plan_watermark( $card['name'] ) ] );
	},
	$whoopstore_grid_cards
);

// Порядок тарифов из макета «Подписок» — см. объяснение в шапке файла.
$whoopstore_plan_order = [ 'LIFE', 'ONE', 'PEAK' ];

usort(
	$whoopstore_grid_cards,
	function ( $a, $b ) use ( $whoopstore_plan_order ) {
		$position_a = array_search( $a['watermark'], $whoopstore_plan_order, true );
		$position_b = array_search( $b['watermark'], $whoopstore_plan_order, true );

		return ( false === $position_a ? PHP_INT_MAX : $position_a ) <=> ( false === $position_b ? PHP_INT_MAX : $position_b );
	}
);

if ( '' === $whoopstore_grid_title ) {
	$whoopstore_grid_term   = get_term_by( 'slug', $whoopstore_grid_category, 'product_cat' );
	$whoopstore_grid_title  = $whoopstore_grid_term ? $whoopstore_grid_term->name : '';
}

whoopstore_catalog_section( $whoopstore_grid_title, $whoopstore_grid_cards, '', $whoopstore_grid_anchor );
