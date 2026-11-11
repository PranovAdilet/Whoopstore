<?php
/**
 * Серверный рендер блока whoopstore/post-grid — список статей блога.
 *
 * Блог вернули по просьбе заказчика 2026-11-11 (статьи перенесены с прежнего
 * сайта по тем же адресам). В макете Figma блога нет, поэтому список собран
 * из уже существующего компонента: плитка .tile-card (фото, затемнение,
 * подпись) и сетка .tile-grid — те же, что у разделов каталога ремешков.
 *
 * На странице «Блог» (templates/page-blog.html) блок сам запрашивает записи;
 * в архиве рубрики (templates/archive.html) берёт основной запрос WordPress.
 * Пагинация штатная (paginate_links), по 12 статей на странице.
 *
 * @var array $attributes Атрибуты блока.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

if ( is_archive() ) {
	$whoopstore_posts_query = $wp_query;
} else {
	$whoopstore_posts_query = new WP_Query( [
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 12,
		'paged'               => max( 1, (int) get_query_var( 'paged' ) ),
		'ignore_sticky_posts' => true,
	] );
}

if ( ! $whoopstore_posts_query->have_posts() ) {
	echo '<p class="post-grid__empty section-subtext">Статей пока нет.</p>';
	return;
}
?>
<div class="tile-grid post-grid">
	<?php
	foreach ( $whoopstore_posts_query->posts as $whoopstore_post ) {
		$whoopstore_cover = get_the_post_thumbnail_url( $whoopstore_post, 'large' );

		whoopstore_tile_card( [
			'title' => get_the_title( $whoopstore_post ),
			'image' => $whoopstore_cover ? $whoopstore_cover : get_theme_file_uri( 'assets/icons/product-placeholder.svg' ),
			'href'  => get_permalink( $whoopstore_post ),
		] );
	}
	?>
</div>
<?php
$whoopstore_pagination = paginate_links( [
	'total'     => (int) $whoopstore_posts_query->max_num_pages,
	'current'   => max( 1, (int) get_query_var( 'paged' ) ),
	'prev_text' => '←',
	'next_text' => '→',
] );

if ( $whoopstore_pagination ) {
	echo '<nav class="post-grid__pagination" aria-label="Страницы блога">' . $whoopstore_pagination . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка paginate_links().
}
