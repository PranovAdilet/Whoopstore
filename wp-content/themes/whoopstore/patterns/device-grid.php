<?php
/**
 * Title: Device grid
 * Slug: whoopstore/device-grid
 *
 * Сетка из 6 карточек трекеров с фильтром-пилюлями сверху (Figma, Главная):
 * первый ряд — трекеры с подпиской на 1 год (со скидкой и бейджем
 * «Распродажа»), второй — с подпиской на 1 месяц.
 *
 * С 2026-09-15 карточки — настоящие товары WooCommerce из категории
 * «Трекеры» (подкатегории «…на 1 год» и «…на 1 месяц»), в порядке «Порядок
 * в меню». Цена, старая цена и бейдж «Распродажа» — из самого товара
 * (цена распродажи), ссылка «Купить» — на страницу товара. Карточку рисует
 * общая whoopstore_device_card(), данные — whoopstore_category_cards()
 * (inc/woocommerce/product-card.php).
 *
 * Фото: предметных фото на каждую модель нет — у товаров стоят два общих фото
 * браслета из макета (XxHSGND0LxCtAUYxCMxR-1/2), чередуются, как было в
 * вёрстке. Заменить — в самом товаре.
 *
 * Фильтр-пилюли — по-прежнему статичные: поведение фильтра в макете не
 * описано (см. docs/navigation-map.md).
 */

// Модель (5.0 / MG) берётся из названия товара и уходит в data-model карточки —
// по ней фильтр-пилюли прячут лишние карточки (assets/js/device-filter.js).
$whoopstore_devices = array_map( function ( $card ) {
	$model = whoopstore_device_model( $card['name'] );

	return '' === $model ? $card : array_merge( $card, [ 'model' => $model ] );
}, whoopstore_category_cards( 'trekery' ) );
?>
<!-- wp:group {"anchor":"devices","className":"device-grid","layout":{"type":"constrained"}} -->
<div class="wp-block-group device-grid" id="devices">

	<!-- wp:html -->
	<div class="device-grid__filters" role="group" aria-label="Фильтр по устройствам">
		<button type="button" class="device-grid__filter is-active" data-model="" aria-pressed="true">Все устройства</button>
		<button type="button" class="device-grid__filter" data-model="5.0" aria-pressed="false">Whoop 5.0</button>
		<button type="button" class="device-grid__filter" data-model="MG" aria-pressed="false">Whoop MG</button>
	</div>

	<div class="device-grid__list">
		<?php foreach ( $whoopstore_devices as $device ) : whoopstore_device_card( $device ); endforeach; ?>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
