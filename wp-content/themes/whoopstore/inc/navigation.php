<?php
/**
 * Подсветка текущего раздела в меню шапки и подвала.
 *
 * Раньше класс `is-active` был вписан в разметку у пункта «Whoop 5.0»
 * (parts/header.html, parts/footer.html) — в Figma он зелёный на всех
 * четырёх макетах, и это считалось постоянным акцентом. На живом сайте это
 * читается как «вы здесь»: покупатель переходит в «Подписки», а зелёным
 * горит «Whoop 5.0» (замечание пользователя 2026-09-22). Решение
 * пользователя: подсвечивать текущий раздел.
 *
 * Поэтому класс убран из разметки, а добавляется он здесь — при выводе, по
 * адресу страницы. Разметку меню заказчик правит в редакторе, и подсветка
 * переживает правку: она привязана к ссылке пункта, а не к его месту в
 * списке. Пункты-якоря («Аксессуары» → /#accessories) и пункты без ссылки
 * («Блог», «Whoop 4.0») не подсвечиваются никогда.
 *
 * Где какой пункт активен:
 *   Главная                       → «Whoop 5.0»
 *   «Подписки»                    → «Подписки»
 *   «Ремешки 5.0» и «Ремешки MG»  → «Ремешки» (пункт один на оба каталога)
 *   страница товара, архив категории → раздел, которому принадлежит товар
 *                                   (та же карта, что у редиректов архивов)
 *   корзина, оформление, кабинет, поиск, 404 → ничего
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Путь пункта меню в сравнимом виде: без домена, без якоря, со слешем на
 * конце. Внешние ссылки и пункты без пути дают пустую строку.
 *
 * @param string $href Адрес из разметки пункта.
 * @return string
 */
function whoopstore_nav_path( $href ) {
	$href = (string) $href;

	if ( '' === $href || false !== strpos( $href, '#' ) ) {
		return '';
	}

	$host = wp_parse_url( $href, PHP_URL_HOST );

	if ( $host && $host !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		return '';
	}

	$path = (string) wp_parse_url( $href, PHP_URL_PATH );

	return '' === $path ? '' : trailingslashit( $path );
}

/**
 * Страница каталога, которой принадлежит категория товаров.
 *
 * Карта та же, по которой архивы категорий отправляются на свёрстанные
 * страницы (inc/woocommerce/redirects.php): второй такой карты заводить
 * нельзя, иначе они разойдутся. Пути с якорем («/#devices») — это разделы
 * Главной, поэтому у них активен её пункт.
 *
 * @param string $slug Slug категории товаров.
 * @return string Путь или пустая строка.
 */
function whoopstore_nav_category_path( $slug ) {
	$map = whoopstore_category_redirects();

	if ( empty( $map[ $slug ] ) ) {
		return '';
	}

	$path = (string) strtok( $map[ $slug ], '#' );

	return '' === $path ? '' : trailingslashit( $path );
}

/**
 * Путь раздела, в котором сейчас находится посетитель, или пустая строка,
 * если подсвечивать нечего.
 *
 * @return string
 */
function whoopstore_current_nav_path() {
	$path = '';

	if ( is_front_page() ) {
		$path = '/';
	} elseif ( function_exists( 'is_product' ) && is_product_category() ) {
		$term = get_queried_object();
		$path = $term instanceof WP_Term ? whoopstore_nav_category_path( $term->slug ) : '';
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		foreach ( wp_get_post_terms( get_queried_object_id(), 'product_cat', [ 'fields' => 'slugs' ] ) as $slug ) {
			$path = whoopstore_nav_category_path( $slug );

			if ( '' !== $path ) {
				break;
			}
		}
	} elseif ( is_page() ) {
		$path = whoopstore_nav_path( (string) get_permalink() );
	}

	// Пункт «Ремешки» один на оба каталога и ведёт на 5.0 — и со страницы
	// каталога MG, и с товара из категории MG активен именно он.
	return '/remeshki-whoop-mg/' === $path ? '/remeshki-whoop-5-0/' : $path;
}

/**
 * Добавляет `is-active` и aria-current пункту меню, ведущему на текущий
 * раздел.
 *
 * @param string $html  Разметка блока.
 * @param array  $block Разобранный блок.
 * @return string
 */
function whoopstore_mark_current_nav_item( $html, $block ) {
	if ( 'core/paragraph' !== ( $block['blockName'] ?? '' ) ) {
		return $html;
	}

	$class = (string) ( $block['attrs']['className'] ?? '' );

	if ( false === strpos( $class, 'site-header__nav-item' ) && false === strpos( $class, 'site-footer__nav-item' ) ) {
		return $html;
	}

	$current = whoopstore_current_nav_path();

	if ( '' === $current || ! preg_match( '#<a\s[^>]*href="([^"]*)"#', $html, $link ) ) {
		return $html;
	}

	if ( whoopstore_nav_path( html_entity_decode( $link[1] ) ) !== $current ) {
		return $html;
	}

	$html = preg_replace( '#(<p\b[^>]*class=")#', '${1}is-active ', $html, 1 );

	return preg_replace( '#<a\s#', '<a aria-current="page" ', $html, 1 );
}
add_filter( 'render_block', 'whoopstore_mark_current_nav_item', 10, 2 );
