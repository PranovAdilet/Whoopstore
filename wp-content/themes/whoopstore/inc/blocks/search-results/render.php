<?php
/**
 * Серверный рендер блока whoopstore/search-results.
 *
 * Страница результатов поиска в Figma не нарисована, поэтому собрана из того,
 * что в проекте уже есть: раздел каталога (whoopstore_catalog_section) и
 * карточка товара (.device-card) — те же, что на каталогах и Главной. Своей
 * вёрстки и своих текстов блок не заводит: заголовок и сообщение «ничего не
 * найдено» — формулировки из русского языкового пакета WooCommerce.
 *
 * Ищем только по товарам: поиск в магазине — это поиск товара, а страниц
 * контента на сайте четыре и попадают они в выдачу поисковых систем, а не
 * во внутренний поиск. Штатный WP_Query главного запроса не используем —
 * он смешал бы товары со страницами, и сетка карточек развалилась бы.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$whoopstore_search_query = trim( get_search_query() );

if ( '' === $whoopstore_search_query || ! function_exists( 'wc_get_products' ) ) {
	return;
}

$whoopstore_search_products = wc_get_products( [
	'status'     => 'publish',
	'visibility' => 'catalog',
	's'          => $whoopstore_search_query,
	'limit'      => -1,
	'orderby'    => 'relevance',
] );

// «Результат поиска: «…»» и «Товаров, соответствующих вашему запросу, не
// обнаружено.» — строки WooCommerce; кавычки в них заданы сущностями,
// поэтому декодируем до экранирования, иначе на странице будет «&ldquo;».
$whoopstore_search_title = sprintf(
	html_entity_decode( __( 'Search results: &ldquo;%s&rdquo;', 'woocommerce' ), ENT_QUOTES, 'UTF-8' ),
	$whoopstore_search_query
);

if ( $whoopstore_search_products ) {
	whoopstore_catalog_section(
		$whoopstore_search_title,
		array_map( 'whoopstore_product_card_data', $whoopstore_search_products )
	);

	return;
}

// Пустая выдача — тот же раздел каталога, но вместо сетки одна строка.
// Разметка повторяет whoopstore_catalog_section() только заголовком: сетки
// карточек здесь нет, и звать функцию с пустым списком нечестно — она
// напечатала бы пустой .catalog-section__list.
?>
<section class="catalog-section">
	<h2 class="catalog-section__title card-heading"><?php echo esc_html( $whoopstore_search_title ); ?></h2>
	<p><?php echo esc_html( __( 'No products were found matching your selection.', 'woocommerce' ) ); ?></p>
</section>
<?php
