<?php
/**
 * Title: Device grid
 * Slug: whoopstore/device-grid
 *
 * По Figma здесь сетка из 6 карточек устройств (Whoop One/Life/Peak,
 * подписка на год со скидкой + подписка на 1 месяц) с фильтром-пилюлями
 * сверху. Реальных WooCommerce-товаров под них нет и создавать не будем
 * (см. обсуждение в чате — "Вариант Б": статичная вёрстка, не WC-сущности).
 * Названия и цены — реальный контент из присланных Figma-скринов, НЕ
 * выдуманы. Фильтр-пилюли — статичные, без реальной фильтрации (фильтровать
 * нечего). Когда появятся реальные товары — эта секция заменяется на нативный
 * woocommerce/product-collection (как уже сделано для одного тестового
 * товара на этой же странице), без переверстки карточек — тот же вид класса
 * device-card можно будет применить к реальным WC-карточкам через CSS.
 *
 * Аудит 2026-09-08 (по просьбе пользователя): сама карточка теперь рендерится
 * общей функцией whoopstore_device_card() (см. inc/card-component.php) — тот
 * же источник HTML, что у accessories-grid.php и activation-grid.php,
 * вместо трёх скопированных foreach с одинаковой разметкой.
 *
 * Аудит 2026-09-08 (round 2, по просьбе пользователя): добавлены реальные
 * фото вместо декоративной заглушки. Life/Peak (годовые) — подтверждённые
 * прямые Figma-ассеты (node imgXxHsgnd0LxCtAuYxCMxR2/R3 в raw-дампе,
 * скачаны напрямую по ссылке, без MCP — та же ссылка на One (R1) и на все
 * 3 карточки "на 1 месяц" на момент проверки уже "протухла" — Figma вместо
 * файла отдаёт пустой PNG). Для этих 4 карточек пользователь вручную
 * перезалил 2 актуальных фото с тем же названием слоя из Figma
 * (XxHSGND0LxCtAUYxCMxR-1/2.png) — предметных фото на каждую модель нет,
 * поэтому чередуются между 4 карточками, как и попросил пользователь. */

// Первый ряд (годовые подписки) — общий с секцией "Купить Whoop с подпиской"
// на странице "Подписки": в Figma это те же карточки с теми же ценами, поэтому
// список один, см. inc/product-data.php.
$whoopstore_devices = array_merge( whoopstore_annual_plan_devices(), [
	[ 'name' => 'Фитнес-трекер Whoop 5.0 One с подпиской на 1 месяц', 'price' => '16 990,00 ₽', 'image' => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp' ],
	[ 'name' => 'Фитнес-трекер Whoop MG Life с подпиской на 1 месяц', 'price' => '27 990,00 ₽', 'image' => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-1.webp' ],
	[ 'name' => 'Фитнес-трекер Whoop 5.0 Peak с подпиской на 1 месяц', 'price' => '19 990,00 ₽', 'image' => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp' ],
] );
?>
<!-- wp:group {"anchor":"devices","className":"device-grid","layout":{"type":"constrained"}} -->
<div class="wp-block-group device-grid" id="devices">

	<!-- wp:html -->
	<div class="device-grid__filters" role="group" aria-label="Фильтр по устройствам">
		<button type="button" class="device-grid__filter is-active">Все устройства</button>
		<button type="button" class="device-grid__filter">Whoop 5.0</button>
		<button type="button" class="device-grid__filter">Whoop MG</button>
	</div>

	<div class="device-grid__list">
		<?php foreach ( $whoopstore_devices as $device ) : whoopstore_device_card( $device ); endforeach; ?>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
