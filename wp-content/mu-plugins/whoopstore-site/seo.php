<?php
/**
 * Метаданные страницы: описание, Open Graph / Twitter, canonical.
 *
 * По ТЗ у страниц должны быть SEO-метаданные. Данных production нет, поэтому
 * готовых description и og:image взять неоткуда — они собираются из того
 * контента, который на сайте уже есть (краткое описание товара, описание
 * категории, текст страницы, изображение товара). **Ничего не выдумывается:**
 * если собрать осмысленное описание не из чего, тег просто не выводится, а
 * заполнение остаётся задачей заказчика (краткие описания товаров, описания
 * категорий).
 *
 * Чего здесь сознательно нет:
 *   • schema.org товара и хлебных крошек — их уже отдаёт WooCommerce
 *     (WC_Structured_Data, Product + BreadcrumbList в <head> карточки товара);
 *   • robots — им заведуют Staging Guard (весь стенд noindex) и WooCommerce
 *     (корзина, оформление, кабинет);
 *   • canonical на одиночных записях — его ставит ядро (rel_canonical).
 *   • sitemap — штатный /wp-sitemap.xml ядра, на стенде закрыт blog_public=0.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Максимальная длина описания. 160 символов — размер, который поисковые
 * системы показывают в сниппете; более длинное всё равно обрежется.
 */
const WHOOPSTORE_SEO_DESCRIPTION_MAX = 160;

/**
 * Ниже этой длины описание считается бессмысленным и не выводится.
 *
 * Контент страниц каталогов — это заголовок плюс динамические блоки; после
 * очистки от блоков от него остаётся один H1. Такой «description» в выдаче
 * дублирует title и мешает, поэтому лучше не выводить ничего.
 */
const WHOOPSTORE_SEO_DESCRIPTION_MIN = 50;

/**
 * Приводит произвольный текст к одной строке нужной длины.
 *
 * Обрезка идёт по границе слова, многоточие добавляется только если текст
 * действительно обрезан.
 *
 * @param string $text Исходный текст (может содержать HTML, блоки, шорткоды).
 * @return string
 */
function whoopstore_seo_normalize_text( $text ) {
	$text = (string) $text;

	if ( '' === trim( $text ) ) {
		return '';
	}

	// excerpt_remove_blocks() оставляет только блоки, разрешённые ядром для
	// выдержки (абзацы, заголовки, списки), и выбрасывает остальные — в том
	// числе наши динамические блоки каталога и хлебных крошек.
	if ( function_exists( 'excerpt_remove_blocks' ) && has_blocks( $text ) ) {
		$text = excerpt_remove_blocks( $text );
	}

	$text = strip_shortcodes( $text );
	$text = wp_strip_all_tags( $text, true );
	$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
	$text = preg_replace( '/\s+/u', ' ', $text );
	$text = trim( (string) $text );

	if ( '' === $text ) {
		return '';
	}

	if ( mb_strlen( $text ) <= WHOOPSTORE_SEO_DESCRIPTION_MAX ) {
		return $text;
	}

	$cut   = mb_substr( $text, 0, WHOOPSTORE_SEO_DESCRIPTION_MAX );
	$space = mb_strrpos( $cut, ' ' );

	if ( false !== $space && $space > WHOOPSTORE_SEO_DESCRIPTION_MIN ) {
		$cut = mb_substr( $cut, 0, $space );
	}

	return rtrim( $cut, " \t\n\r\0\x0B.,;:—-" ) . '…';
}

/**
 * Описание текущей страницы или пустая строка, если брать его неоткуда.
 *
 * @return string
 */
function whoopstore_seo_description() {
	$raw = '';

	if ( is_singular() ) {
		$post = get_queried_object();

		if ( $post instanceof WP_Post ) {
			// У товара post_excerpt — это «краткое описание» из карточки.
			$raw = '' !== trim( (string) $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$raw = term_description( $term );
		}
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$shop = get_post( wc_get_page_id( 'shop' ) );

		if ( $shop instanceof WP_Post ) {
			$raw = '' !== trim( (string) $shop->post_excerpt ) ? $shop->post_excerpt : $shop->post_content;
		}
	} elseif ( is_front_page() || is_home() ) {
		$raw = get_bloginfo( 'description' );
	}

	/**
	 * Позволяет задать описание страницы, которое не выводится из контента.
	 *
	 * @param string $raw Исходный текст описания.
	 */
	$raw  = apply_filters( 'whoopstore_seo_description_source', $raw );
	$text = whoopstore_seo_normalize_text( $raw );

	return mb_strlen( $text ) >= WHOOPSTORE_SEO_DESCRIPTION_MIN ? $text : '';
}

/**
 * Изображение для карточки ссылки в соцсетях и мессенджерах.
 *
 * @return string URL или пустая строка.
 */
function whoopstore_seo_image() {
	if ( is_singular() ) {
		$id = get_queried_object_id();

		if ( function_exists( 'is_product' ) && is_product() ) {
			$product = wc_get_product( $id );

			if ( $product && $product->get_image_id() ) {
				return (string) wp_get_attachment_image_url( $product->get_image_id(), 'large' );
			}
		}

		$thumbnail = get_the_post_thumbnail_url( $id, 'large' );

		if ( $thumbnail ) {
			return (string) $thumbnail;
		}
	}

	// Первый экран Главной и «Подписок» — фон в CSS, а не <img>; тему об этом
	// спрашиваем только если она на месте (mu-plugin грузится раньше темы).
	if ( function_exists( 'whoopstore_hero_background_url' ) ) {
		$hero = whoopstore_hero_background_url();

		if ( '' !== $hero ) {
			return home_url( $hero );
		}
	}

	return '';
}

/**
 * Canonical там, где его не ставит ядро.
 *
 * rel_canonical() ядра работает только на одиночных записях. Для Главной и
 * архива магазина адрес считаем сами, для остальных типов запросов (поиск,
 * 404, страницы результатов) canonical не нужен.
 *
 * @return string
 */
function whoopstore_seo_canonical() {
	if ( is_singular() ) {
		return ''; // уже вывело ядро
	}

	$url = '';

	if ( is_front_page() || is_home() ) {
		$url = home_url( '/' );
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$url = get_permalink( wc_get_page_id( 'shop' ) );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );
			$url  = is_wp_error( $link ) ? '' : $link;
		}
	}

	if ( '' === $url ) {
		return '';
	}

	$page = (int) get_query_var( 'paged' );

	return $page > 1 ? trailingslashit( $url ) . 'page/' . $page . '/' : $url;
}

/**
 * Адрес текущей страницы для og:url.
 *
 * @return string
 */
function whoopstore_seo_current_url() {
	$canonical = whoopstore_seo_canonical();

	if ( '' !== $canonical ) {
		return $canonical;
	}

	return is_singular() ? (string) get_permalink( get_queried_object_id() ) : home_url( '/' );
}

/**
 * Выводит метатеги в <head>.
 *
 * Приоритет 3 — после robots ядра и preload первого экрана (приоритет 2), но
 * заметно раньше стилей и скриптов.
 */
add_action( 'wp_head', function () {
	if ( is_404() || is_search() ) {
		return; // такие страницы в выдаче не нужны, метатеги им не нужны тоже
	}

	// wp_get_document_title() возвращает разделитель сущностью (&#8212;) — в
	// <title> это нормально, а в значении og:title соцсети покажут его как есть.
	$title       = html_entity_decode( wp_get_document_title(), ENT_QUOTES, get_bloginfo( 'charset' ) );
	$description = whoopstore_seo_description();
	$image       = whoopstore_seo_image();
	$canonical   = whoopstore_seo_canonical();
	$url         = whoopstore_seo_current_url();

	if ( '' !== $canonical ) {
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
	}

	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
	}

	$type = 'website';

	if ( function_exists( 'is_product' ) && is_product() ) {
		$type = 'product';
	} elseif ( is_singular() ) {
		$type = 'article';
	}

	$tags = [
		'og:type'       => $type,
		'og:site_name'  => get_bloginfo( 'name' ),
		'og:locale'     => get_locale(),
		'og:title'      => $title,
		'og:url'        => $url,
		'og:description' => $description,
		'og:image'      => $image,
	];

	foreach ( $tags as $property => $content ) {
		if ( '' === (string) $content ) {
			continue;
		}

		printf(
			'<meta property="%s" content="%s" />' . "\n",
			esc_attr( $property ),
			'og:url' === $property || 'og:image' === $property ? esc_url( $content ) : esc_attr( $content )
		);
	}

	// Цена в карточке ссылки — та же, что видит покупатель на странице.
	if ( 'product' === $type ) {
		$product = wc_get_product( get_queried_object_id() );

		if ( $product && '' !== (string) $product->get_price() ) {
			printf( '<meta property="product:price:amount" content="%s" />' . "\n", esc_attr( wc_format_decimal( $product->get_price(), wc_get_price_decimals() ) ) );
			printf( '<meta property="product:price:currency" content="%s" />' . "\n", esc_attr( get_woocommerce_currency() ) );
		}
	}

	printf(
		'<meta name="twitter:card" content="%s" />' . "\n",
		'' !== $image ? 'summary_large_image' : 'summary'
	);
}, 3 );
