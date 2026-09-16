<?php
/**
 * Title: Подписки — выберите подписку
 * Slug: whoopstore/subscriptions-plans
 *
 * Секция «Выберите подписку»: заголовок + 3 карточки тарифов. Источник —
 * Figma, desktop node `1:3018` (сырой дамп
 * docs/figma-design/raw/page-subscriptions-desktop.code.tsx.txt, узлы
 * 1:3019 LIFE / 1:3034 ONE / 1:3049 PEAK).
 *
 * С 2026-09-15 карточки — настоящие товары WooCommerce из категории
 * «Подписки Whoop» (виртуальные, без доставки): название, цена и ссылка — из
 * товара. Это те же товары, что в секции «Активация и продление» на Главной,
 * но в макете здесь другой порядок — LIFE, ONE, PEAK, — поэтому порядок задан
 * в паттерне, а не «Порядком в меню».
 *
 * Разметку секции печатает общая whoopstore_catalog_section(), карточку —
 * whoopstore_device_card() с модификатором `plan` (.device-card--plan в
 * theme.css). Медиа-зона — оформление секции, а не фото товара:
 *  - мокап телефона — два слоя Figma (node 1:3024 "Screen" + 1:3025 "Silver")
 *    одним файлом subscriptions-plan-phone;
 *  - фото браслета — XxHSGND0LxCtAUYxCMxR-2 (экспорт узла из Figma битый, взят
 *    одноимённый реальный ассет, пропорции совпадают);
 *  - слово-подложка — тариф из названия товара (whoopstore_plan_watermark()).
 */

// Порядок карточек из макета.
$whoopstore_plan_order = [ 'LIFE', 'ONE', 'PEAK' ];

$whoopstore_subscription_plans = array_map( function ( $card ) {
	return array_merge( $card, [
		'watermark' => whoopstore_plan_watermark( $card['name'] ),
		'modifier'  => 'plan',
		'phone'     => '/wp-content/uploads/2026/09/subscriptions-plan-phone.webp',
		'image'     => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp',
	] );
}, whoopstore_category_cards( 'podpiski-whoop' ) );

// Тариф, которого нет в макете, встаёт в конец, не ломая порядок остальных.
usort( $whoopstore_subscription_plans, function ( $a, $b ) use ( $whoopstore_plan_order ) {
	$position_a = array_search( $a['watermark'], $whoopstore_plan_order, true );
	$position_b = array_search( $b['watermark'], $whoopstore_plan_order, true );

	return ( false === $position_a ? PHP_INT_MAX : $position_a ) <=> ( false === $position_b ? PHP_INT_MAX : $position_b );
} );
?>
<!-- wp:html -->
<?php
if ( $whoopstore_subscription_plans ) {
	whoopstore_catalog_section( 'Выберите подписку', $whoopstore_subscription_plans );
}
?>
<!-- /wp:html -->
