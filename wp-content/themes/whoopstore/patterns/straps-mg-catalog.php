<?php
/**
 * Title: Ремешки MG — каталог по разделам
 * Slug: whoopstore/straps-mg-catalog
 *
 * Каталог страницы "Ремешки для WHOOP MG": 6 разделов, 26 SKU.
 * Источник — Figma, desktop node `1:2584` (сырой дамп
 * docs/figma-design/raw/page-mg-desktop.code.tsx.txt). Названия, цены,
 * группировка и порядок восстановлены по координатам узлов (заголовки
 * разделов `Onest:Medium 20px` на y=309/1409/2025/3125/3741/5325, карточки
 * сеткой 3 в ряд: x = 40/420/800, ряды с шагом 484px).
 *
 * Отличия от страницы 5.0 (проверено по дампам и скриншотам макета):
 *  - НЕТ сетки плиток-категорий — каталог идёт сразу под H1 (60px);
 *  - 6 разделов вместо 7 — раздела "Влагостойкие ремешки CoreKnit Band"
 *    у MG в макете нет;
 *  - кожаные ремешки дороже (24 990 против 19 990) и их 6, а не 4;
 *  - в SportFlex появились SKU "CR7 Home" (24 990) и "Flurry" (22 990),
 *    в плетёных — "Fresh"/"Echo" (16 990);
 *  - раздел "Ремешки на бицепс CoreKnit Bicep Band" — те же 3 товара, что и
 *    на 5.0 (в названии самого товара так и написано "для Whoop 5.0 / MG").
 *
 * Разметку раздела печатает общая функция whoopstore_catalog_section()
 * (inc/card-component.php) — та же, что на странице 5.0; карточка — общий
 * whoopstore_device_card(). Ничего специфичного для MG в вёрстке нет.
 *
 * Данные — статические PHP-массивы, а не WooCommerce-товары, по той же
 * причине, что и на 5.0 (см. straps-50-catalog.php): production — источник
 * истины для товаров, придумывать ID/slug нельзя.
 *
 * Фото — реальные ассеты из Figma (узлы "75477106 N" и
 * "MG SportFlex Band CR7 Home"), скачаны напрямую по ссылкам из raw-дампа,
 * уменьшены до 560×560 PNG с прозрачностью. Внимание: одноимённые
 * константы `img7547710NN` в дампах 5.0 и MG указывают на РАЗНЫЕ файлы —
 * фото брались строго из MG-дампа. Пустых ассетов, в отличие от 5.0,
 * почти нет: единственный пустой (`img754771068` у SuperKnit Midnight with
 * Titanium) перекрыт вложенным рабочим слоем `img754771069`.
 *
 * ⚠️ У двух SKU в макете НЕТ узла с ценой: "SportFlex Band Rush" и
 * "SportFlex Band Gravity" (node 1:2675 и 1:2795 — там только название,
 * фото и кнопка). У всех соседей по разделу цена есть, так что это, скорее
 * всего, недосмотр макета. Цена НЕ додумана: карточка выводится без блока
 * цены (компонент это поддерживает штатно). Нужно подтверждение реальной
 * цены от продуктовой команды.
 *
 * ⚠️ "SportFlex Band CR7 Home" — необычное название (похоже на спортивную
 * коллаборацию), сверить с продуктовой командой; в макете оно именно такое.
 *
 * ⚠️ В макете в слоте 2-й карточки SportFlex лежат ДВА наложенных узла:
 * актуальный "…WHOOP MG SportFlex Band Drift" (1:2838, с фото) и старый
 * "…WHOOP 5.0 SportFlex Band Rush" (1:2787, без фото, текст не обновлён
 * под MG). Взят актуальный — он же виден на скриншоте макета.
 *
 * Ссылки кнопок "Купить" — "#": целевых URL нет, пока нет товаров.
 */

$whoopstore_straps_mg_catalog = [
	[
		'title' => 'Кожаные люксовые ремешки MG LeatherLuxe Band',
		'items' => [
			[ 'name' => 'Кожаный люксовый ремешок для Whoop MG LeatherLuxe Straight Band Chestnut/Cream with Titanium', 'price' => '24 990,00 ₽', 'image' => 'leatherluxe-straight-chestnut-cream-titanium' ],
			[ 'name' => 'Кожаный люксовый ремешок для Whoop MG LeatherLuxe Straight Band Black/Cream with Titanium', 'price' => '24 990,00 ₽', 'image' => 'leatherluxe-straight-black-cream-titanium' ],
			[ 'name' => 'Кожаный люксовый ремешок для Whoop MG LeatherLuxe Straight Band Camel with Gold', 'price' => '24 990,00 ₽', 'image' => 'leatherluxe-straight-camel-gold' ],
			[ 'name' => 'Кожаный люксовый ремешок для Whoop MG LeatherLuxe Straight Band Black with Titanium', 'price' => '24 990,00 ₽', 'image' => 'leatherluxe-straight-black-titanium' ],
			[ 'name' => 'Кожаный люксовый ремешок для Whoop MG LeatherLuxe Straight Band Black/Cream with Black', 'price' => '24 990,00 ₽', 'image' => 'leatherluxe-straight-black-cream-black' ],
			[ 'name' => 'Кожаный люксовый ремешок для Whoop MG LeatherLuxe Straight Band Black with Gold', 'price' => '24 990,00 ₽', 'image' => 'leatherluxe-straight-black-gold' ],
		],
	],
	[
		'title' => 'Кашемировые ремешки MG CloudKnit Band',
		'items' => [
			[ 'name' => 'КАШЕМИРОВЫЙ РЕМЕШОК ДЛЯ WHOOP MG CloudKnit Band Storm', 'price' => '19 990,00 ₽', 'image' => 'cloudknit-storm' ],
			[ 'name' => 'КАШЕМИРОВЫЙ РЕМЕШОК ДЛЯ WHOOP MG CloudKnit Band Fawn', 'price' => '19 990,00 ₽', 'image' => 'cloudknit-fawn' ],
		],
	],
	[
		'title' => 'Спортивные ремешки MG SportFlex Band',
		'items' => [
			// Цены нет в макете — см. предупреждение в шапке файла.
			[ 'name' => 'СПОРТИВНЫЙ РЕМЕШОК ДЛЯ WHOOP MG SportFlex Band Rush', 'image' => 'sportflex-rush' ],
			[ 'name' => 'СПОРТИВНЫЙ РЕМЕШОК ДЛЯ WHOOP MG SportFlex Band Drift', 'price' => '19 990,00 ₽', 'image' => 'sportflex-drift' ],
			// Цены нет в макете — см. предупреждение в шапке файла.
			[ 'name' => 'СПОРТИВНЫЙ РЕМЕШОК ДЛЯ WHOOP MG SportFlex Band Gravity', 'image' => 'sportflex-gravity' ],
			[ 'name' => 'СПОРТИВНЫЙ РЕМЕШОК ДЛЯ WHOOP MG SportFlex Band CR7 Home', 'price' => '24 990,00 ₽', 'image' => 'sportflex-cr7-home' ],
			[ 'name' => 'СПОРТИВНЫЙ РЕМЕШОК ДЛЯ WHOOP MG SportFlex Band Flurry', 'price' => '22 990,00 ₽', 'image' => 'sportflex-flurry' ],
		],
	],
	[
		'title' => 'Плетеные люксовые ремешки MG SuperKnit Band',
		'items' => [
			[ 'name' => 'Плетеный люксовый РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Midnight with Titanium', 'price' => '19 990,00 ₽', 'image' => 'superknit-luxe-midnight-titanium' ],
			[ 'name' => 'Плетеный люксовый РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Dune with Gold', 'price' => '19 990,00 ₽', 'image' => 'superknit-luxe-dune-gold' ],
			[ 'name' => 'Плетеный люксовый РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Obsidian with Gold', 'price' => '19 990,00 ₽', 'image' => 'superknit-luxe-obsidian-gold' ],
		],
	],
	[
		'title' => 'Плетеные ремешки MG SuperKnit Band',
		'items' => [
			[ 'name' => 'Плетеный РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Midnight', 'price' => '15 490,00 ₽', 'image' => 'superknit-midnight' ],
			[ 'name' => 'Плетеный РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Graphite', 'price' => '15 490,00 ₽', 'image' => 'superknit-graphite' ],
			[ 'name' => 'Плетеный РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Obsidian', 'price' => '15 490,00 ₽', 'image' => 'superknit-obsidian' ],
			[ 'name' => 'Плетеный РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Glacier', 'price' => '15 490,00 ₽', 'image' => 'superknit-glacier' ],
			[ 'name' => 'Плетеный РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Dune', 'price' => '15 490,00 ₽', 'image' => 'superknit-dune' ],
			[ 'name' => 'Плетеный РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Fresh', 'price' => '16 990,00 ₽', 'image' => 'superknit-fresh' ],
			[ 'name' => 'Плетеный РЕМЕШОК ДЛЯ WHOOP MG SuperKnit Band Echo', 'price' => '16 990,00 ₽', 'image' => 'superknit-echo' ],
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
foreach ( $whoopstore_straps_mg_catalog as $whoopstore_section ) {
	whoopstore_catalog_section(
		$whoopstore_section['title'],
		$whoopstore_section['items'],
		'/wp-content/uploads/2026/09/straps-mg-'
	);
}
?>
<!-- /wp:html -->
