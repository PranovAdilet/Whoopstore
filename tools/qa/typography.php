<?php
/**
 * Типографика: проверка, что неразрывные пробелы расставляются и что при
 * этом не меняется разметка.
 *
 * Главная проверка — инвариант: типографика ТОЛЬКО заменяет обычный пробел
 * на неразрывный и больше ничего не делает. Поэтому страница рендерится
 * дважды — с типографикой и без неё (переменная окружения
 * WHOOPSTORE_TYPOGRAPHY=off), и после замены неразрывных пробелов обратно на
 * обычные обе версии обязаны совпасть байт в байт. Если фильтр хоть где-то
 * съест тег, порвёт атрибут или тронет скрипт — страницы разойдутся.
 *
 * Запуск:
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/typography.php
 *
 * Код возврата 1 — есть непройденные проверки.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

require dirname( __DIR__, 2 ) . '/wp-load.php';

$whoopstore_qa_pass = 0;
$whoopstore_qa_fail = 0;

function whoopstore_qa_check( $title, $ok, $detail = '' ) {
	global $whoopstore_qa_pass, $whoopstore_qa_fail;

	if ( $ok ) {
		++$whoopstore_qa_pass;
		printf( "  ok   %s%s\n", $title, '' !== $detail ? '  [' . $detail . ']' : '' );

		return;
	}

	++$whoopstore_qa_fail;
	printf( "  FAIL %s%s\n", $title, '' !== $detail ? '  [' . $detail . ']' : '' );
}

$nbsp = WHOOPSTORE_NBSP;

echo "== Правила на коротких примерах\n";

$cases = [
	'предлог не висит в конце строки'      => [ 'помощь с подключением', 'помощь с' . $nbsp . 'подключением' ],
	'тире остаётся с предыдущим словом'    => [ 'гарантия — быстрая доставка', 'гарантия' . $nbsp . '— быстрая доставка' ],
	'число не отрывается от того, что за ним' => [ 'Купить за 990 ₽', 'Купить за' . $nbsp . '990' . $nbsp . '₽' ],
	'внутри слова ничего не склеивается'   => [ 'она и она', 'она и' . $nbsp . 'она' ],
	'атрибуты тега не трогаются'           => [ '<a href="/a?x=1&y=2" title="в корзину">в корзину</a>', '<a href="/a?x=1&y=2" title="в корзину">в' . $nbsp . 'корзину</a>' ],
	'содержимое script не трогается'       => [ '<script>var a = "и это";</script>', '<script>var a = "и это";</script>' ],
];

foreach ( $cases as $title => $case ) {
	list( $input, $expected ) = $case;
	$actual = whoopstore_typography_html( $input );
	whoopstore_qa_check( $title, $actual === $expected, str_replace( $nbsp, '·', $actual ) );
}

echo "\n== Страницы целиком: разметка не изменилась, пробелы расставлены\n";

$pages = [
	'Главная'            => '/',
	'Ремешки 5.0'        => '/remeshki-whoop-5-0/',
	'Подписки'           => '/podpiski/',
	'Политика'           => '/politika-konfidenczialnosti/',
];

$php    = PHP_BINARY;
$render = escapeshellarg( dirname( __FILE__ ) . '/render.php' );

foreach ( $pages as $title => $uri ) {
	$with = shell_exec( sprintf( '%s %s %s 2>/dev/null', escapeshellarg( $php ), $render, escapeshellarg( $uri ) ) );
	$без  = shell_exec( sprintf( 'WHOOPSTORE_TYPOGRAPHY=off %s %s %s 2>/dev/null', escapeshellarg( $php ), $render, escapeshellarg( $uri ) ) );

	$added = substr_count( (string) $with, $nbsp ) - substr_count( (string) $без, $nbsp );
	$same  = str_replace( $nbsp, ' ', (string) $with ) === str_replace( $nbsp, ' ', (string) $без );

	whoopstore_qa_check(
		$title . ' — разметка та же',
		$same && '' !== trim( (string) $with ),
		strlen( (string) $with ) . ' байт'
	);
	whoopstore_qa_check( $title . ' — пробелы расставлены', $added > 0, 'добавлено ' . $added );
}

printf( "\nИтого: %d ok, %d FAIL\n", $whoopstore_qa_pass, $whoopstore_qa_fail );
exit( $whoopstore_qa_fail ? 1 : 0 );
