<?php
/**
 * Старые адреса страниц → 301 на новый адрес.
 *
 * WordPress запоминает прежний слаг страницы в мета-поле `_wp_old_slug`, но
 * сам по нему НЕ перенаправляет: штатная wp_old_slug_redirect() (ядро,
 * wp-includes/query.php) выходит на иерархических типах записей, а страница —
 * как раз такой тип. Мета-поле пишется, а старый URL отдаёт 404.
 *
 * Случай не гипотетический: 2026-09-25 страница политики конфиденциальности
 * получила человекочитаемый слаг (politika-konfidenczialnosti), и прежний
 * /privacy-policy/ — адрес, который уже стоял в подвале и в согласии на
 * обработку данных при оформлении заказа, — начал отдавать 404.
 *
 * Правило общее, не про одну страницу: любая опубликованная страница, у
 * которой в `_wp_old_slug` записан запрошенный слаг, отдаёт 301 на свой
 * текущий адрес. Ничего не настраивается вручную — метаполе ведёт сам
 * WordPress при каждой смене слага.
 *
 * Границы намеренные:
 *   • только 404 — существующие адреса не трогаем;
 *   • только страницы — у записей и товаров работает ядро;
 *   • если старый слаг нашёлся у двух страниц, не перенаправляем никуда:
 *     угадывать, какая из них имелась в виду, нельзя.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}

	/*
	 * Откуда берётся слаг. Когда страница не нашлась, ядро обнуляет pagename и
	 * оставляет запрошенный путь в name (WP_Query::parse_query) — именно этот
	 * случай нам и нужен. pagename проверяем первым на случай 404 по другой
	 * причине (например, страница снята с публикации).
	 */
	$requested = get_query_var( 'pagename' );

	if ( ! is_string( $requested ) || '' === $requested ) {
		$requested = get_query_var( 'name' );
	}

	if ( ! is_string( $requested ) || '' === $requested ) {
		return;
	}

	// У вложенной страницы здесь весь путь; менялся последний сегмент.
	$slug = basename( untrailingslashit( $requested ) );

	if ( '' === $slug ) {
		return;
	}

	$found = get_posts( array(
		'post_type'              => 'page',
		'post_status'            => 'publish',
		'meta_key'               => '_wp_old_slug',
		'meta_value'             => $slug,
		'posts_per_page'         => 2,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	) );

	if ( 1 !== count( $found ) ) {
		return;
	}

	$link = get_permalink( $found[0] );

	if ( ! $link || untrailingslashit( $link ) === untrailingslashit( home_url( add_query_arg( array() ) ) ) ) {
		return;
	}

	wp_safe_redirect( $link, 301, 'Whoopstore old page slug' );
	exit;
} );
