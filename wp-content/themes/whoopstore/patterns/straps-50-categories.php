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
 * ⚠️ Открытый вопрос (НЕ реализовано намеренно): в макете плитки читаются
 * как навигация по разделам каталога, но целей ссылок Figma не даёт, а
 * однозначно сопоставить их с 7 разделами каталога ниже нельзя (6 плиток
 * против 7 разделов, 4-я плитка дублирует 1-ю по названию, раздела
 * CloudKnit среди плиток нет). Поэтому плитки пока не кликабельны — тот же
 * подход, что уже принят для фильтра устройств на Главной
 * (.device-grid__filter, см. theme.css). Нужны подтверждённые цели ссылок.
 */

$whoopstore_straps_50_categories = [
	[ 'title' => 'Кожаные люксовые ремешки LeatherLuxe Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-leatherluxe.jpg' ],
	[ 'title' => 'Спортивные ремешки SportFlex Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-sportflex.jpg' ],
	[ 'title' => 'Плетеные ремешки SuperKnit Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-superknit.jpg' ],
	[ 'title' => 'Кожаные люксовые ремешки', 'image' => '/wp-content/uploads/2026/09/straps-50-category-leatherluxe-2.jpg' ],
	[ 'title' => 'Влагостойкие ремешки CoreKnit Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-coreknit.jpg' ],
	[ 'title' => 'Ремешки на бицепс CoreKnit Bicep Band', 'image' => '/wp-content/uploads/2026/09/straps-50-category-bicep.jpg' ],
];
?>
<!-- wp:html -->
<div class="tile-grid">
	<?php foreach ( $whoopstore_straps_50_categories as $whoopstore_tile ) : whoopstore_tile_card( $whoopstore_tile ); endforeach; ?>
</div>
<!-- /wp:html -->
