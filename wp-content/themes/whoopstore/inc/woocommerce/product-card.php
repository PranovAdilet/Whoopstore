<?php
/**
 * Данные карточки .device-card из товара WooCommerce.
 *
 * Сама карточка (whoopstore_device_card(), inc/card-component.php) о
 * WooCommerce не знает — она печатает массив. Здесь единственное место, где
 * этот массив собирается из объекта товара, чтобы каждая секция с реальными
 * товарами (сейчас "Вам может понравиться", дальше — каталоги) не повторяла
 * один и тот же набор вызовов и не расходилась в деталях (где-то забыли
 * бейдж, где-то взяли другой размер фото).
 *
 * Только штатное API товара, ничего не подставляется: нет фото — карточка
 * оставляет свою заглушку; нет цены — блока цены нет; нет скидки — нет
 * бейджа. Текст кнопки и модификаторы оформления сюда не входят — это
 * решение секции по макету, а не данные товара.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Массив для whoopstore_device_card() по товару WooCommerce.
 *
 * @param WC_Product $product    Товар.
 * @param string     $image_size Размер вложения для src; srcset/sizes
 *                               WordPress добавит сам. По умолчанию
 *                               woocommerce_single (600px), а не
 *                               woocommerce_thumbnail (300px): медиа-зона
 *                               карточки — 320px, на retina 300px заметно
 *                               мылит. Если у фото такого размера нет
 *                               (оригинал мельче), WordPress отдаст оригинал.
 * @return array
 */
function whoopstore_product_card_data( WC_Product $product, $image_size = 'woocommerce_single' ) {
	$data = [
		'name'       => $product->get_name(),
		'price_html' => $product->get_price_html(),
		'href'       => $product->get_permalink(),
		'image_id'   => $product->get_image_id(),
		'image_size' => $image_size,
	];

	// Бейдж — только по реальному состоянию товара: цену распродажи и сроки
	// её действия проверяет сам WooCommerce. Текст — из макета (бейдж
	// карточек трекеров с подпиской на 1 год).
	if ( $product->is_on_sale() ) {
		$data['badge'] = 'Распродажа';
	}

	return $data;
}

/**
 * Тариф Whoop из названия товара — для слова-подложки карточки тарифа
 * (ONE / PEAK / LIFE, .device-card__watermark в theme.css).
 *
 * Подложка — оформление карточки из макета, а не свойство товара, поэтому
 * отдельного поля в товаре под неё нет: слово берётся из названия
 * («Подписка Whoop One …»). Нет тарифа в названии — нет подложки.
 *
 * @param string $name Название товара.
 * @return string ONE, PEAK, LIFE или пустая строка.
 */
function whoopstore_plan_watermark( $name ) {
	return preg_match( '/\bWhoop\s+(One|Peak|Life)\b/iu', (string) $name, $match ) ? mb_strtoupper( $match[1] ) : '';
}

/**
 * Модель трекера из названия товара — для фильтра «Все устройства / Whoop 5.0 /
 * Whoop MG» на Главной.
 *
 * Отдельного поля модели у товара нет, а в названиях трекеров она всегда
 * указана («…(Whoop MG)», «Фитнес-трекер Whoop 5.0 One…»). Используется только
 * для трекеров: в названиях ремешков часто стоит «для Whoop 5.0 / MG», то есть
 * подходят обе модели.
 *
 * @param string $name Название товара.
 * @return string MG, 5.0 или пустая строка.
 */
function whoopstore_device_model( $name ) {
	if ( preg_match( '/\bMG\b/u', (string) $name ) ) {
		return 'MG';
	}

	return preg_match( '/\b5\.0\b/u', (string) $name ) ? '5.0' : '';
}

/**
 * Семейство и цвет ремешка из названия товара — для плашки над лентой
 * «Персонализируйте свой WHOOP» (в макете две строки: «CoreKnit» и
 * «Color: Royal Blue», node 1:449/1:451).
 *
 * Отдельного поля цвета у товара нет, поэтому строки берутся из названия:
 * слова латиницей непосредственно перед словом «Band» — семейство
 * («CoreKnit», «CoreKnit Bicep»), всё после «Band» — цвет («Royal Blue»).
 * Модель («Whoop 5.0», «MG») в семейство не попадает. Название в другом
 * формате даёт две пустые строки — плашка тогда не выводится вовсе.
 *
 * @param string $name Название товара.
 * @return array [ семейство, цвет ]; обе строки пустые, если формат не распознан.
 */
function whoopstore_strap_badge_parts( $name ) {
	$parts = preg_split( '/\bBand\b/u', (string) $name, 2 );

	if ( ! isset( $parts[1] ) || '' === trim( $parts[1] ) ) {
		return [ '', '' ];
	}

	$family = [];

	foreach ( array_reverse( preg_split( '/\s+/u', trim( $parts[0] ) ) ) as $word ) {
		if ( ! preg_match( '/^[A-Za-z][A-Za-z0-9]*$/', $word ) || in_array( $word, [ 'Whoop', 'MG' ], true ) ) {
			break;
		}

		array_unshift( $family, $word );
	}

	return $family ? [ implode( ' ', $family ), trim( $parts[1] ) ] : [ '', '' ];
}

/**
 * Опубликованные товары категории в порядке из макета.
 *
 * Порядок — штатное поле товара «Порядок в меню» (menu_order), так менеджер
 * меняет порядок карточек в админке, без разработчика. Подкатегории
 * включаются (штатное поведение запроса по категории): «Трекеры» на Главной
 * — это и годовые, и месячные. Скрытые из каталога товары не выводятся.
 *
 * @param string $slug Slug категории товаров.
 * @return WC_Product[]
 */
function whoopstore_category_products( $slug ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return [];
	}

	return wc_get_products( [
		'status'     => 'publish',
		'visibility' => 'catalog',
		'category'   => [ $slug ],
		'limit'      => -1,
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
	] );
}

/**
 * Карточки .device-card для товаров категории.
 *
 * @param string $slug  Slug категории товаров.
 * @param array  $extra Решения секции, общие для всех карточек (напр.
 *                      button_text, modifier).
 * @return array Массивы в формате whoopstore_device_card().
 */
function whoopstore_category_cards( $slug, array $extra = [] ) {
	return array_map( function ( $product ) use ( $extra ) {
		return array_merge( whoopstore_product_card_data( $product ), $extra );
	}, whoopstore_category_products( $slug ) );
}

/**
 * Раздел каталога из категории: заголовок + сетка карточек её товаров.
 *
 * Разметку печатает та же whoopstore_catalog_section(), что и раньше; здесь
 * только данные. Пустой раздел (категории нет или в ней нет товаров) не
 * выводится вовсе — заголовок без карточек выглядел бы как ошибка вёрстки.
 *
 * @param string $slug Slug категории товаров.
 * @param array  $args {
 *     @type string $title  Заголовок раздела, если в макете он отличается от
 *                          названия категории. По умолчанию — название категории.
 *     @type string $anchor id раздела для ссылок внутри страницы.
 * }
 */
function whoopstore_category_section( $slug, array $args = [] ) {
	$term  = get_term_by( 'slug', $slug, 'product_cat' );
	$cards = $term ? whoopstore_category_cards( $slug ) : [];

	if ( ! $cards ) {
		return;
	}

	whoopstore_catalog_section(
		! empty( $args['title'] ) ? $args['title'] : $term->name,
		$cards,
		'',
		! empty( $args['anchor'] ) ? $args['anchor'] : ''
	);
}
