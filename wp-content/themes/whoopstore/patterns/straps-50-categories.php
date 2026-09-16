<?php
/**
 * Title: Ремешки 5.0 — плитки категорий
 * Slug: whoopstore/straps-50-categories
 *
 * Сетка из 6 фото-плиток под H1 страницы "Ремешки для WHOOP 5.0".
 * Источник — Figma: desktop node `1:2448`…`1:2465` (3 колонки × 2 ряда,
 * плитка 360×300, gap 20px по обеим осям — считано по координатам
 * x = 40/420/800 и y = 279/599), mobile node `1:4623`…`1:4634`
 * (2 колонки × 3 ряда, плитка 155×180, gap 10px). Сырые дампы:
 * docs/figma-design/raw/page-50-desktop.code.tsx.txt и
 * raw/page-50-mobile.code.tsx.txt.
 *
 * Порядок плиток на desktop (по рядам) и на mobile (тоже по рядам, при
 * 2 колонках) — одна и та же последовательность, проверено по координатам,
 * поэтому один общий список без раскладочных перестановок.
 *
 * Подписи — дословно из Figma. Обрати внимание: 4-я плитка называется
 * "Кожаные люксовые ремешки" (без суффикса Band) и дублирует по смыслу
 * 1-ю — так в макете на обоих брейкпоинтах, это не опечатка вёрстки;
 * отдельной плитки под раздел каталога "Кашемировые ремешки 5.0 CloudKnit
 * Band" в макете нет.
 *
 * Фото — реальные ассеты из Figma (imgRectangle100242…100247), скачаны
 * напрямую по ссылкам из raw-дампа и уменьшены до ≤900px/JPEG q85
 * (оригиналы 800…2600px весили 0.4–7 МБ на плитку 360×300 — избыточно).
 *
 * Цели плиток (решение заказчика 2026-09-16): плитка ведёт к своему разделу
 * каталога на этой же странице — якорь совпадает со slug категории товаров,
 * его печатает whoopstore_category_section() (см. straps-50-catalog.php).
 * Перезагрузки нет, работает и без JS. Ссылаться на архив категории нельзя:
 * он 301-м возвращается на эту же страницу (inc/woocommerce/redirects.php).
 *
 * Две нестыковки самого макета и что с ними решено:
 *  - 4-я плитка подписана «Кожаные люксовые ремешки», как и 1-я. Ведёт она на
 *    «Плетёные люксовые SuperKnit» — единственный оставшийся люксовый раздел;
 *    подпись оставлена дословно из макета, не переписана.
 *  - Раздел «Кашемировые CloudKnit» плитки в макете не имеет — плитку не
 *    придумывали, раздел доступен прокруткой каталога.
 */

$whoopstore_straps_50_categories = [
	[ 'title' => 'Кожаные люксовые ремешки LeatherLuxe Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-leatherluxe.jpg', 'section' => 'leatherluxe-5-0' ],
	[ 'title' => 'Спортивные ремешки SportFlex Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-sportflex.jpg', 'section' => 'sportflex-5-0' ],
	[ 'title' => 'Плетеные ремешки SuperKnit Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-superknit.jpg', 'section' => 'superknit-5-0' ],
	[ 'title' => 'Кожаные люксовые ремешки', 'image' => '/wp-content/uploads/2026/09/straps-50-category-leatherluxe-2.jpg', 'section' => 'superknit-luxe-5-0' ],
	[ 'title' => 'Влагостойкие ремешки CoreKnit Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-coreknit.jpg', 'section' => 'coreknit-5-0' ],
	[ 'title' => 'Ремешки на бицепс CoreKnit Bicep Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-bicep.jpg', 'section' => 'coreknit-bicep' ],
];
?>
<!-- wp:html -->
<div class="tile-grid">
	<?php foreach ( $whoopstore_straps_50_categories as $whoopstore_tile ) : ?>
		<?php whoopstore_tile_card( array_merge( $whoopstore_tile, [ 'href' => '#' . $whoopstore_tile['section'] ] ) ); ?>
	<?php endforeach; ?>
</div>
<!-- /wp:html -->
