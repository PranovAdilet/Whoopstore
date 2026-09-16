<?php
/**
 * Title: Accessories grid
 * Slug: whoopstore/accessories-grid
 *
 * Секция «Аксессуары» на Главной (Figma, сразу под сеткой трекеров): карточки
 * тем же компонентом .device-card, что и device-grid.php.
 *
 * С 2026-09-15 товарные карточки — настоящие товары WooCommerce из категории
 * «Аксессуары» (сейчас одна — зарядный блок Basic Charger). У зарядного блока
 * фото нет намеренно: доступные фото — это фото браслета, на карточке зарядки
 * они были бы неверны по сути товара.
 *
 * Последняя карточка — промо «Ремешки для WHOOP 5.0 / MG (более 50 цветов)»
 * без цены. Это не товар, а вход в каталог ремешков, поэтому она остаётся
 * статичной и идёт после товаров, как в макете. Цели ссылки в макете нет —
 * ссылка не выдумана (см. docs/navigation-map.md).
 *
 * Сетка — .accessories-grid__list в theme.css: карточка фиксированной ширины
 * 360×464, как в макете, а не растянутая на всю секцию.
 */

$whoopstore_accessories = array_merge(
	whoopstore_category_cards( 'aksessuary' ),
	[
		[ 'name' => 'Ремешки для WHOOP 5.0 / MG (более 50 цветов)', 'image' => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp' ],
	]
);
?>
<!-- wp:group {"anchor":"accessories","className":"accessories-grid","layout":{"type":"constrained"}} -->
<div class="wp-block-group accessories-grid" id="accessories">

	<!-- wp:heading {"level":2,"className":"accessories-grid__title card-heading"} -->
	<h2 class="wp-block-heading accessories-grid__title card-heading">Аксессуары</h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="accessories-grid__list">
		<?php foreach ( $whoopstore_accessories as $item ) : whoopstore_device_card( $item ); endforeach; ?>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
