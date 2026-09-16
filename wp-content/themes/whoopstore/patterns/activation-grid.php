<?php
/**
 * Title: Activation & renewal grid
 * Slug: whoopstore/activation-grid
 *
 * Секция «Активация и продление устройства» на Главной: подзаголовок + 4
 * карточки — подписки ONE / PEAK / LIFE (подключение/продление) и активация
 * (привязка иностранной карты). Узлы Figma: homepage-desktop 1:531 ONE /
 * 1:546 PEAK / 1:516 LIFE / 1:142 активация; homepage-mobile 1:3967 / 1:3981 /
 * 1:3995 / 1:3603.
 *
 * С 2026-09-15 карточки — настоящие товары WooCommerce из категории
 * «Активация и продление» (подписки лежат в её подкатегории «Подписки
 * Whoop»), в порядке «Порядок в меню». Название, цена и ссылка — из товара;
 * товары виртуальные, без доставки.
 *
 * Медиа-зона — это оформление секции, а не фото товара: та же карточка
 * тарифа, что в «Выберите подписку» на странице «Подписки» (модификатор
 * `plan`: мокап телефона, фото браслета, слово-подложка ONE/PEAK/LIFE), плюс
 * уточнения геометрии Главной (см. .device-card--plan-compact / --plan-solo
 * в theme.css):
 *  - `plan-compact` — карточка Главной 360×464, а не 497; на mobile сжатия нет;
 *  - `plan-solo` — карточка без тарифа в названии (активация): только
 *    телефон, шире и по центру, без подложки и без фото браслета.
 *
 * Ассеты — мокап телефона subscriptions-plan-phone и фото браслета
 * XxHSGND0LxCtAUYxCMxR-2 (узел Figma отдаёт битым, взят тот же реальный ассет,
 * что у карточек тарифов).
 */

$whoopstore_activation_phone = '/wp-content/uploads/2026/09/subscriptions-plan-phone.webp';
$whoopstore_activation_band  = '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp';

$whoopstore_activation = array_map( function ( $card ) use ( $whoopstore_activation_phone, $whoopstore_activation_band ) {
	$card['phone'] = $whoopstore_activation_phone;
	$plan          = whoopstore_plan_watermark( $card['name'] );

	if ( '' === $plan ) {
		$card['modifier'] = [ 'plan', 'plan-compact', 'plan-solo' ];
		return $card;
	}

	return array_merge( $card, [
		'watermark' => $plan,
		'modifier'  => [ 'plan', 'plan-compact' ],
		'image'     => $whoopstore_activation_band,
	] );
}, whoopstore_category_cards( 'aktivaciya-i-prodlenie' ) );
?>
<!-- wp:group {"className":"activation-grid","layout":{"type":"constrained"}} -->
<div class="wp-block-group activation-grid">

	<!-- wp:heading {"level":2,"className":"activation-grid__title card-heading"} -->
	<h2 class="wp-block-heading activation-grid__title card-heading">Активация и продление устройства</h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="activation-grid__list">
		<?php foreach ( $whoopstore_activation as $item ) : whoopstore_device_card( $item ); endforeach; ?>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
