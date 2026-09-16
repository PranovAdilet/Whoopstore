<?php
/**
 * Прогон ключевых страниц стенда без браузера.
 *
 * Закрывает ту часть QA из ТЗ, которая не требует глаз и браузера: страница
 * открывается, не отдаёт 404 и лишний редирект, PHP не пишет замечаний, все
 * локальные картинки/стили/скрипты, на которые ссылается разметка, лежат на
 * диске (пункт ТЗ «нет broken assets»).
 *
 * Чего он НЕ заменяет: ошибок консоли браузера, вида страницы, поведения
 * скриптов и проверки 320–1440 px — это только в браузере.
 *
 * Запуск:
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/smoke.php
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/smoke.php --keep   (не удалять HTML)
 *
 * Код возврата 1 — есть замечания.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$root = dirname( __DIR__, 2 );
$out  = __DIR__ . '/out';
$keep = in_array( '--keep', $argv, true );

require $root . '/wp-load.php';

/**
 * Список проверяемых адресов.
 *
 * Товары берём из базы, а не списком: их слаги меняются вместе с каталогом.
 * Нужны оба типа — простой и вариативный: у них разные шаблоны формы заказа.
 */
function whoopstore_qa_urls() {
	$urls = [
		'Главная'            => home_url( '/' ),
		'Ремешки 5.0'        => get_permalink( get_page_by_path( 'remeshki-whoop-5-0' ) ),
		'Ремешки MG'         => get_permalink( get_page_by_path( 'remeshki-whoop-mg' ) ),
		'Подписки'           => get_permalink( get_page_by_path( 'podpiski' ) ),
		'Магазин'            => get_permalink( wc_get_page_id( 'shop' ) ),
		'Корзина'            => get_permalink( wc_get_page_id( 'cart' ) ),
		'Оформление заказа'  => get_permalink( wc_get_page_id( 'checkout' ) ),
		'Личный кабинет'     => get_permalink( wc_get_page_id( 'myaccount' ) ),
	];

	foreach ( [ 'simple' => 'Товар (простой)', 'variable' => 'Товар (вариативный)' ] as $type => $label ) {
		$products = wc_get_products( [ 'type' => $type, 'limit' => 1, 'status' => 'publish', 'orderby' => 'ID' ] );

		if ( $products ) {
			$urls[ $label ] = get_permalink( $products[0]->get_id() );
		}
	}

	$urls['Поиск']  = home_url( '/?s=' . rawurlencode( 'ремешок' ) );
	$urls['404']    = home_url( '/whoopstore-qa-404/' );

	return $urls;
}

/**
 * Ожидаемое поведение адреса: редирект и 404 — не всегда ошибка.
 *
 * Архивы категорий товаров намеренно уводят 301-м на страницы каталога по
 * макету (inc/woocommerce/redirects.php), «Оформление заказа» с пустой
 * корзиной уводит на корзину, а адрес 404 на то и адрес 404.
 */
function whoopstore_qa_expected( $label ) {
	return [
		'404'               => [ '404' => true ],
		'Оформление заказа' => [ 'redirect' => true ],
	][ $label ] ?? [];
}

// ---------------------------------------------------------------------------
// 1. Рендер
// ---------------------------------------------------------------------------

if ( ! is_dir( $out ) && ! mkdir( $out, 0755, true ) ) {
	fwrite( STDERR, "Не удалось создать каталог {$out}\n" );
	exit( 1 );
}

array_map( 'unlink', glob( $out . '/*.html' ) );

$php      = PHP_BINARY;
$problems = [];
$files    = [];
$rows     = [];

foreach ( whoopstore_qa_urls() as $label => $url ) {
	if ( ! $url ) {
		$problems[] = "{$label}: адрес не найден в базе";
		continue;
	}

	$uri  = wp_make_link_relative( $url );
	$file = $out . '/' . sanitize_title( $label ) . '.html';

	$descriptors = [ 1 => [ 'file', $file, 'w' ], 2 => [ 'pipe', 'w' ] ];
	$process     = proc_open(
		sprintf( '%s %s %s', escapeshellarg( $php ), escapeshellarg( __DIR__ . '/render.php' ), escapeshellarg( $uri ) ),
		$descriptors,
		$pipes,
		$root
	);

	if ( ! is_resource( $process ) ) {
		$problems[] = "{$label}: не удалось запустить рендер";
		continue;
	}

	$stderr = stream_get_contents( $pipes[2] );
	fclose( $pipes[2] );
	proc_close( $process );

	preg_match( '/404=(\w+)/', $stderr, $m404 );
	preg_match( '/redirect=(\S+)/', $stderr, $mredirect );
	preg_match( '/notices=(\d+)/', $stderr, $mnotices );

	$is404    = ( $m404[1] ?? 'no' ) === 'yes';
	$redirect = ( $mredirect[1] ?? '-' ) !== '-' ? $mredirect[1] : '';
	$notices  = (int) ( $mnotices[1] ?? 0 );
	$bytes    = file_exists( $file ) ? filesize( $file ) : 0;
	$expected = whoopstore_qa_expected( $label );

	if ( $is404 && empty( $expected['404'] ) ) {
		$problems[] = "{$label}: страница отдала 404 ({$uri})";
	}

	if ( '' !== $redirect && empty( $expected['redirect'] ) ) {
		$problems[] = "{$label}: неожиданный редирект на {$redirect}";
	}

	if ( $notices > 0 ) {
		$problems[] = "{$label}: замечания PHP ({$notices}) — " . trim( preg_replace( '/^WHOOPSTORE-QA.*\n/', '', $stderr ) );
	}

	if ( 0 === $bytes && empty( $expected['redirect'] ) ) {
		$problems[] = "{$label}: пустой ответ";
	}

	if ( $bytes > 0 ) {
		$files[] = $file;
	}

	$rows[] = [ $label, $uri, $bytes, $is404 ? '404' : ( '' !== $redirect ? '→ ' . $redirect : 'ok' ), $notices ];
}

/**
 * Дополняет строку пробелами по числу символов, а не байтов (printf считает
 * байты, и колонки с кириллицей разъезжаются).
 *
 * @param string $text  Текст.
 * @param int    $width Ширина колонки.
 * @return string
 */
function whoopstore_qa_pad( $text, $width ) {
	$text = mb_strimwidth( $text, 0, $width, '…' );

	return $text . str_repeat( ' ', max( 0, $width - mb_strlen( $text ) ) );
}

echo whoopstore_qa_pad( 'Страница', 20 ) . ' ' . whoopstore_qa_pad( 'Адрес', 44 ) . ' ' . whoopstore_qa_pad( 'Байт', 9 ) . ' ' . whoopstore_qa_pad( 'Ответ', 40 ) . " Замечаний\n";

foreach ( $rows as $row ) {
	echo whoopstore_qa_pad( $row[0], 20 ) . ' '
		. whoopstore_qa_pad( urldecode( $row[1] ), 44 ) . ' '
		. whoopstore_qa_pad( number_format( $row[2], 0, '', ' ' ), 9 ) . ' '
		. whoopstore_qa_pad( $row[3], 40 ) . ' '
		. $row[4] . "\n";
}

// ---------------------------------------------------------------------------
// 2. Битые ассеты
// ---------------------------------------------------------------------------

/**
 * Ссылки на локальные статические файлы в разметке страницы.
 *
 * @param string $html Разметка.
 * @return string[] Пути от корня сайта.
 */
function whoopstore_qa_asset_links( $html ) {
	$found = [];

	$patterns = [
		'/\ssrc=["\']([^"\']+)["\']/i',
		'/<(?:link|a)[^>]*\shref=["\']([^"\']+)["\']/i',
		'/\sposter=["\']([^"\']+)["\']/i',
		'/url\(\s*["\']?([^)"\']+)["\']?\s*\)/i',
	];

	foreach ( $patterns as $pattern ) {
		if ( preg_match_all( $pattern, $html, $matches ) ) {
			$found = array_merge( $found, $matches[1] );
		}
	}

	// srcset — несколько адресов через запятую.
	if ( preg_match_all( '/\ssrcset=["\']([^"\']+)["\']/i', $html, $matches ) ) {
		foreach ( $matches[1] as $srcset ) {
			foreach ( explode( ',', $srcset ) as $candidate ) {
				$found[] = trim( explode( ' ', trim( $candidate ) )[0] );
			}
		}
	}

	return array_unique( $found );
}

$home    = home_url( '/' );
$origin  = untrailingslashit( $home );
$missing = [];
$checked = 0;

foreach ( $files as $file ) {
	$html = (string) file_get_contents( $file );
	$page = basename( $file, '.html' );

	foreach ( whoopstore_qa_asset_links( $html ) as $link ) {
		$link = trim( $link );

		if ( '' === $link || str_starts_with( $link, 'data:' ) || str_starts_with( $link, '#' ) ) {
			continue;
		}

		if ( str_starts_with( $link, 'http' ) || str_starts_with( $link, '//' ) ) {
			if ( ! str_starts_with( $link, $origin ) ) {
				continue; // чужой домен — не наша ответственность
			}

			$link = substr( $link, strlen( $origin ) );
		}

		if ( ! str_starts_with( $link, '/' ) ) {
			continue;
		}

		$path = rawurldecode( strtok( strtok( $link, '?' ), '#' ) );

		// Только статика: остальное — маршруты WordPress.
		if ( ! preg_match( '/\.(css|js|png|jpe?g|webp|avif|gif|svg|ico|woff2?|ttf|eot|mp4|webm)$/i', $path ) ) {
			continue;
		}

		++$checked;

		if ( ! file_exists( $root . $path ) ) {
			$missing[ $path ][ $page ] = true;
		}
	}
}

printf( "\nСсылок на локальную статику проверено: %d\n", $checked );

if ( $missing ) {
	foreach ( $missing as $path => $pages ) {
		$problems[] = 'нет файла ' . $path . ' (страницы: ' . implode( ', ', array_keys( $pages ) ) . ')';
	}
} else {
	echo "Битых ассетов нет.\n";
}

if ( ! $keep ) {
	array_map( 'unlink', glob( $out . '/*.html' ) );
	@rmdir( $out );
}

// ---------------------------------------------------------------------------
// 3. Итог
// ---------------------------------------------------------------------------

if ( $problems ) {
	echo "\nЗамечания:\n";

	foreach ( $problems as $problem ) {
		echo '  • ' . $problem . "\n";
	}

	exit( 1 );
}

echo "\nЗамечаний нет.\n";
