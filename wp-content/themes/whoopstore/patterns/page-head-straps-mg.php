<?php
/**
 * Title: Page head — Ремешки для WHOOP MG
 * Slug: whoopstore/page-head-straps-mg
 *
 * Шапка контента страницы "Ремешки для WHOOP MG". Геометрия узла в Figma
 * (desktop node `1:2584`: крошка y=143, H1 y=179 32px; mobile node `1:4635`:
 * крошка y=99, H1 y=143 26px) — **идентична** странице 5.0 до пикселя,
 * отличаются только два текста, поэтому разметку печатает та же общая
 * функция whoopstore_page_head() (inc/page-head-component.php), что и
 * patterns/page-head-straps-50.php.
 *
 * Крошка — "Whoop MG" (node 1:2634 в raw/page-mg-desktop.code.tsx.txt).
 */
?>
<!-- wp:html -->
<?php
whoopstore_page_head( [
	'crumb'  => 'Whoop MG',
	'title'  => 'Ремешки для WHOOP MG',
	'accent' => '(более 25 цветов)',
] );
?>
<!-- /wp:html -->
