<?php
/**
 * Title: Ремешки 5.0 — каталог по разделам
 * Slug: whoopstore/straps-50-catalog
 *
 * Каталог страницы "Ремешки для WHOOP 5.0": 7 разделов, 27 SKU.
 * Источник — Figma, desktop node `1:2120` (сырой дамп
 * docs/figma-design/raw/page-50-desktop.code.tsx.txt). Названия, цены,
 * группировка по разделам и порядок карточек внутри раздела восстановлены
 * по координатам узлов (заголовок раздела — `Onest:Medium 20px`, карточки
 * идут сеткой 3 в ряд: x = 40/420/800, ряды с шагом 484px), а не по
 * прозаическому пересказу в docs/figma-design/page-50-desktop.md — в нём
 * часть названий не сопоставлена с ценами (там же это и оговорено).
 *
 * Раздел целиком рисует общая функция whoopstore_catalog_section()
 * (inc/card-component.php) — она же используется страницей MG; карточка —
 * общий whoopstore_device_card(), тот же, что у трёх секций Главной; сетка —
 * .catalog-section__list, подключённый к тому же CSS-правилу, что
 * .device-grid__list (см. theme.css). Ничего специфичного для этой страницы
 * в карточке нет.
 *
 * Данные — статические PHP-массивы, а не WooCommerce-товары: реальных
 * WC-товаров под эти SKU на стейдже нет (кроме одного пилотного), а
 * создавать 27 товаров с придуманными ID/slug/атрибутами нельзя — production
 * является источником истины для товаров (CLAUDE.md, п. 2–4). Тот же
 * "Вариант Б", что уже принят для карточек Главной (см. device-grid.php).
 * Когда появятся реальные товары, разделы заменяются на нативный
 * woocommerce/product-collection без переверстки карточки.
 *
 * Фото — реальные ассеты из Figma (узлы "75477106 N" и "Subtract"),
 * скачаны напрямую по ссылкам из raw-дампа, уменьшены до 560×560 PNG
 * с прозрачностью (оригиналы 1080×1080).
 *
 * ⚠️ У 4 SKU фото нет: Figma отдаёт по их узлам пустой прозрачный PNG
 * (1024×1024, 4220 байт — та же поломанная заливка, что уже встречалась
 * на Главной, см. straps-promo.php). Это LeatherLuxe Tapered Camel with
 * Gold, SportFlex Rush, SportFlex Drift и SuperKnit Obsidian with Titanium.
 * У них остаётся штатная заглушка .device-card__media — нужен экспорт
 * этих 4 фото из самого Figma-файла.
 *
 * Ссылки кнопок "Купить" — "#" (значение по умолчанию компонента): целевых
 * URL нет, пока нет товаров. Так же, как на Главной.
 */

$whoopstore_straps_50_catalog = [
	[
		'title' => 'Кожаные люксовые ремешки 5.0 LeatherLuxe Band',
		'items' => [
			[ 'name' => 'Кожаный люксовый ремешок для Whoop 5.0 LeatherLuxe Straight Band Black/Cream with Titanium', 'price' => '19 990,00 ₽', 'image' => 'leatherluxe-straight-black-cream-titanium' ],
			[ 'name' => 'Кожаный люксовый ремешок для Whoop 5.0 LeatherLuxe Straight Band Chestnut/Cream with Titanium', 'price' => '19 990,00 ₽', 'image' => 'leatherluxe-straight-chestnut-cream-titanium' ],
			[ 'name' => 'Кожаный люксовый ремешок для Whoop 5.0 LeatherLuxe Tapered Band Camel with Gold', 'price' => '19 990,00 ₽' ],
			[ 'name' => 'Кожаный люксовый ремешок для Whoop 5.0 LeatherLuxe Tapered Band Black with Titanium', 'price' => '19 990,00 ₽', 'image' => 'leatherluxe-tapered-black-titanium' ],
		],
	],
	[
		'title' => 'Кашемировые ремешки 5.0 CloudKnit Band',
		'items' => [
			[ 'name' => 'КАШЕМИРОВЫЙ РЕМЕШОК ДЛЯ WHOOP 5.0 CloudKnit Band Storm', 'price' => '19 990,00 ₽', 'image' => 'cloudknit-storm' ],
			[ 'name' => 'КАШЕМИРОВЫЙ РЕМЕШОК ДЛЯ WHOOP 5.0 CloudKnit Band Fawn', 'price' => '19 990,00 ₽', 'image' => 'cloudknit-fawn' ],
		],
	],
	[
		'title' => 'Спортивные ремешки 5.0 SportFlex Band',
		'items' => [
			[ 'name' => 'СПОРТИВНЫЙ РЕМЕШОК ДЛЯ WHOOP 5.0 SportFlex Band Bounce', 'price' => '19 990,00 ₽', 'image' => 'sportflex-bounce' ],
			[ 'name' => 'СПОРТИВНЫЙ РЕМЕШОК ДЛЯ WHOOP 5.0 SportFlex Band Rush', 'price' => '19 990,00 ₽' ],
			[ 'name' => 'СПОРТИВНЫЙ РЕМЕШОК ДЛЯ WHOOP 5.0 SportFlex Band Drift', 'price' => '19 990,00 ₽' ],
			[ 'name' => 'СПОРТИВНЫЙ РЕМЕШОК ДЛЯ WHOOP 5.0 SportFlex Band Gravity', 'price' => '19 990,00 ₽', 'image' => 'sportflex-gravity' ],
		],
	],
	[
		'title' => 'Плетеные люксовые ремешки 5.0 SuperKnit Band',
		'items' => [
			[ 'name' => 'Плетеный люксовый РЕМЕШОК для WHOOP 5.0 SuperKnit Band Midnight with Titanium', 'price' => '19 990,00 ₽', 'image' => 'superknit-luxe-midnight-titanium' ],
			[ 'name' => 'Плетеный люксовый РЕМЕШОК для WHOOP 5.0 SuperKnit Band Dune with Gold', 'price' => '19 990,00 ₽', 'image' => 'superknit-luxe-dune-gold' ],
			[ 'name' => 'Плетеный люксовый РЕМЕШОК для WHOOP 5.0 SuperKnit Band Obsidian with Titanium', 'price' => '19 990,00 ₽' ],
			[ 'name' => 'Плетеный люксовый РЕМЕШОК для WHOOP 5.0 SuperKnit Band Obsidian with Gold', 'price' => '19 990,00 ₽', 'image' => 'superknit-luxe-obsidian-gold' ],
		],
	],
	[
		'title' => 'Плетеные ремешки 5.0 SuperKnit Band',
		'items' => [
			[ 'name' => 'Плетеный РЕМЕШОК для WHOOP 5.0 SuperKnit Band Graphite', 'price' => '15 490,00 ₽', 'image' => 'superknit-graphite' ],
			[ 'name' => 'Плетеный РЕМЕШОК для WHOOP 5.0 SuperKnit Band Forest', 'price' => '15 490,00 ₽', 'image' => 'superknit-forest' ],
			[ 'name' => 'Плетеный РЕМЕШОК для WHOOP 5.0 SuperKnit Band Rhodonite', 'price' => '15 490,00 ₽', 'image' => 'superknit-rhodonite' ],
			[ 'name' => 'Плетеный РЕМЕШОК для WHOOP 5.0 SuperKnit Band Glacier', 'price' => '15 490,00 ₽', 'image' => 'superknit-glacier' ],
			[ 'name' => 'Плетеный РЕМЕШОК для WHOOP 5.0 SuperKnit Band Dune', 'price' => '15 490,00 ₽', 'image' => 'superknit-dune' ],
			[ 'name' => 'Плетеный РЕМЕШОК для WHOOP 5.0 SuperKnit Band Midnight', 'price' => '15 490,00 ₽', 'image' => 'superknit-midnight' ],
		],
	],
	[
		'title' => 'Влагостойкие ремешки 5.0 CoreKnit Band',
		'items' => [
			[ 'name' => 'Влагостойкий ремешок для Whoop 5.0 CoreKnit Band Evergreen', 'price' => '13 790,00 ₽', 'image' => 'coreknit-evergreen' ],
			[ 'name' => 'Влагостойкий ремешок для Whoop 5.0 CoreKnit Band Jet Black', 'price' => '13 790,00 ₽', 'image' => 'coreknit-jet-black' ],
			[ 'name' => 'Влагостойкий ремешок для Whoop 5.0 CoreKnit Band Ash Grey', 'price' => '13 790,00 ₽', 'image' => 'coreknit-ash-grey' ],
			[ 'name' => 'Влагостойкий ремешок для Whoop 5.0 CoreKnit Band Royal Blue', 'price' => '13 790,00 ₽', 'image' => 'coreknit-royal-blue' ],
		],
	],
	[
		'title' => 'Ремешки на бицепс CoreKnit Bicep Band',
		'items' => [
			[ 'name' => 'Ремешок на бицепс для Whoop 5.0 / MG CoreKnit Bicep Band Royal Blue', 'price' => '14 490,00 ₽', 'image' => 'bicep-royal-blue' ],
			[ 'name' => 'Ремешок на бицепс для Whoop 5.0 / MG CoreKnit Bicep Band Ash Grey', 'price' => '14 490,00 ₽', 'image' => 'bicep-ash-grey' ],
			[ 'name' => 'Ремешок на бицепс для Whoop 5.0 / MG CoreKnit Bicep Band Jet Black', 'price' => '14 490,00 ₽', 'image' => 'bicep-jet-black' ],
		],
	],
];
?>
<!-- wp:html -->
<?php
foreach ( $whoopstore_straps_50_catalog as $whoopstore_section ) {
	whoopstore_catalog_section(
		$whoopstore_section['title'],
		$whoopstore_section['items'],
		'/wp-content/uploads/2026/09/straps-50-'
	);
}
?>
<!-- /wp:html -->
