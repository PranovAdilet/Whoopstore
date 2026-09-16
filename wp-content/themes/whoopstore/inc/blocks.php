<?php
/**
 * Динамические блоки темы.
 *
 * Блок здесь заводится только там, где разметку нельзя хранить в контенте
 * страницы: она собирается из данных WooCommerce или из ассетов темы. Всё
 * остальное (заголовки, тексты, картинки) — обычные блоки ядра в
 * post_content, ради этого миграция и делается.
 *
 * Принцип, общий для всех блоков файла: **блок не выводит собственную
 * обёртку**. Вёрстка темы завязана на прямое родство селекторов
 * (`.catalog-page > .catalog-section`, `.page-head > .page-head__title`),
 * поэтому лишний <div> вокруг вывода сломал бы отступы. Отсюда
 * `get_block_wrapper_attributes()` не используется, а в block.json выключены
 * className/customClassName/anchor — иначе редактор предложил бы настройки,
 * которым негде примениться.
 *
 * Скрипты редактора регистрируются здесь вручную, а не через
 * `"editorScript": "file:./editor.js"`: у варианта с file: зависимости
 * берутся из соседнего *.asset.php, который генерирует сборка. Сборки в
 * проекте нет и заводить её ради двух блоков незачем (весь JS темы — чистый
 * ES5 без пайплайна), поэтому зависимости перечислены явно.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Зависимости скриптов редактора блоков.
 *
 * wp-server-side-render нужен обоим: превью в редакторе рисует сервер той же
 * функцией, что и фронт, иначе редактор показывал бы приблизительную копию
 * вёрстки, которая со временем разойдётся с настоящей.
 *
 * @return string[]
 */
function whoopstore_block_editor_deps() {
	return [
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-i18n',
		'wp-server-side-render',
	];
}

/**
 * Список категорий товаров для выпадающего списка в редакторе.
 *
 * Отдаём в скрипт инлайном, а не запросом к REST: выбор категорий нужен
 * только в редакторе, а лишняя зависимость от прав доступа к маршруту
 * product_cat здесь ничего не даёт.
 *
 * @return array<int, array{slug: string, name: string, count: int}>
 */
function whoopstore_product_category_options() {
	$terms = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'name',
	] );

	if ( is_wp_error( $terms ) ) {
		return [];
	}

	return array_map( function ( $term ) {
		return [
			'slug'  => $term->slug,
			'name'  => $term->name,
			'count' => (int) $term->count,
		];
	}, $terms );
}

add_action( 'init', function () {
	$blocks = [ 'breadcrumbs', 'category-catalog', 'search-results' ];

	foreach ( $blocks as $block ) {
		$handle   = 'whoopstore-block-' . $block;
		$relative = 'inc/blocks/' . $block . '/editor.js';

		wp_register_script(
			$handle,
			get_theme_file_uri( $relative ),
			whoopstore_block_editor_deps(),
			whoopstore_asset_version( $relative ),
			true
		);

		register_block_type( get_theme_file_path( 'inc/blocks/' . $block ) );
	}

	// Только в админке: на фронте список категорий никому не нужен, а
	// get_terms() — лишний запрос на каждой странице сайта.
	if ( is_admin() ) {
		wp_add_inline_script(
			'whoopstore-block-category-catalog',
			'window.whoopstoreProductCategories = ' . wp_json_encode( whoopstore_product_category_options() ) . ';',
			'before'
		);
	}
} );
