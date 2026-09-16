<?php
/**
 * Title: Ремешки MG — каталог по разделам
 * Slug: whoopstore/straps-mg-catalog
 *
 * Каталог страницы "Ремешки для WHOOP MG": 6 разделов из макета (Figma,
 * desktop node `1:2584`, сырой дамп docs/figma-design/raw/page-mg-desktop.code.tsx.txt).
 * Устроен так же, как каталог 5.0 (см. straps-50-catalog.php): раздел —
 * категория товаров WooCommerce, карточки — её товары.
 *
 * Отличия от страницы 5.0 (по макету):
 *  - нет сетки плиток-категорий — каталог идёт сразу под H1;
 *  - нет раздела «Влагостойкие ремешки CoreKnit Band»;
 *  - «Ремешки на бицепс» — те же 3 товара, что и на 5.0.
 *
 * ⚠️ У двух товаров в макете НЕТ цены: «SportFlex Band Rush» и «SportFlex Band
 * Gravity» (node 1:2675 и 1:2795). Цена не додумана: товары заведены без цены,
 * карточка выводится без блока цены, купить их нельзя, пока цену не укажут в
 * товаре.
 *
 * ⚠️ «SportFlex Band CR7 Home» — необычное название (похоже на коллаборацию),
 * в макете оно именно такое; сверить с продуктовой командой.
 */

// Разделы в порядке из макета — slug категорий товаров.
$whoopstore_straps_mg_sections = [
	'leatherluxe-mg',
	'cloudknit-mg',
	'sportflex-mg',
	'superknit-luxe-mg',
	'superknit-mg',
	'coreknit-bicep',
];
?>
<!-- wp:html -->
<?php
foreach ( $whoopstore_straps_mg_sections as $whoopstore_section ) {
	whoopstore_category_section( $whoopstore_section );
}
?>
<!-- /wp:html -->
