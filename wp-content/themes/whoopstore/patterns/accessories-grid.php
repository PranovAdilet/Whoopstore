<?php
/**
 * Title: Accessories grid
 * Slug: whoopstore/accessories-grid
 *
 * Первый проход по всей Главной (2026-09-07). Источник — Figma MCP,
 * fileKey MAGGfC2v8q7afPFdxBZWpK, node 1:3464 ("Мобилка"), get_metadata
 * (лимит MCP исчерпался сразу после этого вызова, get_design_context по
 * этой секции не делался — только текстовые слои и геометрия).
 *
 * По макету сразу под основной сеткой устройств (device-grid) идёт
 * подзаголовок "Аксессуары" и 2 карточки: зарядный блок (реальная цена)
 * и промо-карточка ремешков (без цены, только CTA). Переиспользую те же
 * .device-card/.card, что и device-grid.php — так и card, и грид не
 * дублируются ради одной секции с другим контентом.
 *
 * Аудит 2026-09-08 (по просьбе пользователя): карточка рендерится общей
 * функцией whoopstore_device_card() (см. inc/card-component.php) — тот же
 * источник HTML, что у device-grid.php/activation-grid.php. Отдельно
 * исправлена сетка (.accessories-grid__list в theme.css): раньше
 * `grid-template-columns: repeat(2, 1fr)` растягивал 2 карточки на всю
 * ширину секции — по сырым Figma-метаданным (raw/homepage-desktop-metadata.xml,
 * Rectangle 100242) карточка везде фиксированные 360×464, включая эту
 * секцию, а не "на сколько есть товаров, на столько и растянуть".
 *
 * Аудит 2026-09-08 (round 2, по просьбе пользователя): фото — только у
 * промо-карточки ремешков (там оно и по смыслу подходит — это фото
 * ремешка). У зарядного блока фото НЕ добавлено намеренно: единственные
 * доступные сейчас 2 фото — общие фото браслета (см. device-grid.php),
 * показывать браслет на карточке зарядки было бы неверно по сути товара
 * (другая категория аксессуара), а не просто "оживить" картинку.
 */

$whoopstore_accessories = [
	[ 'name' => 'Проводной зарядный блок Basic Charger для Whoop 5.0 / MG', 'price' => '8 990,00 ₽' ],
	[ 'name' => 'Ремешки для WHOOP 5.0 / MG (более 50 цветов)', 'image' => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp' ],
];
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
