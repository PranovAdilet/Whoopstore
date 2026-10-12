<?php
/**
 * Типографика текстов: неразрывные пробелы.
 *
 * Просьба заказчика (2026-10-12): «расставьте типограф по текстам, чтобы не
 * было висячих предлогов и переносов, чтобы текст был компактнее».
 *
 * Почему не руками в разметке: текстов на сайте несколько сотен и живут они
 * в разных местах — шаблоны темы, синхронные паттерны, содержимое страниц,
 * названия и описания товаров. Расставленные руками пробелы пропали бы при
 * первой же правке текста в редакторе. Здесь обработка идёт в момент вывода:
 * в базе текст остаётся обычным, и всё, что заказчик напишет завтра,
 * типографируется само.
 *
 * Что делает:
 *  - короткие слова (предлоги, союзы, частицы до трёх букв) приклеивает к
 *    следующему слову — именно они «висят» в конце строки;
 *  - тире приклеивает к предыдущему слову (правило русской типографики:
 *    тире не начинает строку);
 *  - числа приклеивает к следующему за ними слову или знаку — «990 ₽»,
 *    «5.0 One», «2 года» не разрываются.
 *
 * Чего не делает намеренно: не меняет кавычки и дефисы (этим занимается
 * штатный wptexturize ядра) и не расставляет мягкие переносы — просьба была
 * обратная, переносов быть не должно.
 *
 * Где не работает: в админке и в запросах редактора. Иначе неразрывные
 * пробелы попали бы в сохраняемый текст и расползлись по базе.
 *
 * @package Whoopstore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Неразрывный пробел. Именно символ, а не сущность `&nbsp;`: сущность в
 * атрибуте или в уже экранированном тексте выглядела бы как набор символов.
 */
const WHOOPSTORE_NBSP = "\u{00A0}";

/**
 * Короткие слова, которые нельзя оставлять в конце строки.
 *
 * Список закрытый, а не «любое слово до трёх букв»: так под правило не
 * попадают, например, «год» или «шаг», которые висеть в конце строки вполне
 * могут.
 *
 * @return string Часть регулярного выражения.
 */
function whoopstore_typography_short_words() {
	static $pattern = null;

	if ( null === $pattern ) {
		$words = [
			'а', 'без', 'бы', 'в', 'во', 'да', 'для', 'до', 'же', 'за', 'и',
			'из', 'или', 'к', 'ко', 'на', 'над', 'не', 'ни', 'но', 'о', 'об',
			'от', 'по', 'под', 'при', 'про', 'с', 'со', 'то', 'у', 'уже',
			'что', 'эти', 'это', 'я',
		];

		$pattern = implode( '|', $words );
	}

	return $pattern;
}

/**
 * Расставляет неразрывные пробелы в куске обычного текста (без тегов).
 *
 * @param string $text Текст.
 * @return string
 */
function whoopstore_typography_text( $text ) {
	if ( '' === trim( $text ) ) {
		return $text;
	}

	$nbsp  = WHOOPSTORE_NBSP;
	$short = whoopstore_typography_short_words();

	// Короткое слово + пробел → приклеиваем к следующему слову. Слово берём
	// с границей слева, иначе «на» поймается внутри «она».
	$text = preg_replace(
		'~(?<![\p{L}\p{N}-])(' . $short . ')\x20+(?=[\p{L}\p{N}«(])~iu',
		'$1' . $nbsp,
		$text
	);

	// Тире остаётся с предыдущим словом: «гарантия — быстрая».
	$text = preg_replace( '~\x20+(?=[—–]\x20)~u', $nbsp, $text );

	// Число и то, что за ним: «990 ₽», «5.0 One», «12 месяцев».
	$text = preg_replace( '~(\d)\x20+(?=[\p{L}₽%])~u', '$1' . $nbsp, $text );

	return $text;
}

/**
 * Обрабатывает готовую разметку, не трогая теги.
 *
 * Разметка режется по тегам, и правила применяются только к кускам между
 * ними — поэтому ни атрибуты, ни адреса ссылок не затрагиваются. Содержимое
 * script/style/textarea/pre/code пропускается целиком: там пробел значащий.
 *
 * @param string $html Разметка.
 * @return string
 */
function whoopstore_typography_html( $html ) {
	if ( '' === $html || false === strpbrk( $html, ' ' ) ) {
		return $html;
	}

	$parts  = preg_split( '~(<[^>]++>)~u', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
	$skip   = 0;
	$result = '';

	foreach ( $parts as $part ) {
		if ( '<' === $part[0] ) {
			if ( preg_match( '~^<\s*(script|style|textarea|pre|code)\b~i', $part ) ) {
				++$skip;
			} elseif ( preg_match( '~^<\s*/\s*(script|style|textarea|pre|code)\s*>~i', $part ) && $skip > 0 ) {
				--$skip;
			}

			$result .= $part;
			continue;
		}

		$result .= $skip > 0 ? $part : whoopstore_typography_text( $part );
	}

	return $result;
}

/**
 * Работает ли типографика на этом запросе.
 *
 * @return bool
 */
function whoopstore_typography_enabled() {
	// Выключатель для проверок: tools/qa/typography.php рендерит одну и ту же
	// страницу с типографикой и без неё и сверяет, что разметка совпала.
	if ( 'off' === getenv( 'WHOOPSTORE_TYPOGRAPHY' ) ) {
		return false;
	}

	if ( is_admin() || wp_is_json_request() ) {
		return false;
	}

	return ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
}

add_filter( 'render_block', function ( $content ) {
	return whoopstore_typography_enabled() ? whoopstore_typography_html( $content ) : $content;
}, 20 );

foreach ( [ 'the_content', 'the_excerpt', 'woocommerce_short_description' ] as $whoopstore_typography_filter ) {
	add_filter( $whoopstore_typography_filter, function ( $content ) {
		return whoopstore_typography_enabled() ? whoopstore_typography_html( $content ) : $content;
	}, 20 );
}

add_filter( 'the_title', function ( $title ) {
	return whoopstore_typography_enabled() ? whoopstore_typography_text( $title ) : $title;
}, 20 );
