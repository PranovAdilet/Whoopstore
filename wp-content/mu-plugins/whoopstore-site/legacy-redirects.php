<?php
/**
 * Адреса прежнего сайта whoopstore.ru → 301 на новый адрес.
 *
 * Новый сайт встаёт на домен, где несколько лет работал прежний магазин.
 * Его страницы есть в поиске, в закладках и во внешних ссылках. Часть
 * товаров и страниц прежнего сайта на новом сайте не повторяются (решение
 * 2026-09-18, launch-checklist.md, раздел 0). Без перенаправления все эти
 * адреса отдавали бы 404. Блог 2026-11-11 перенесён целиком по прежним
 * адресам, в таблице от него остались только пустые рубрики.
 *
 * Таблица собрана 2026-10-28 из опубликованных записей обоих сайтов; для
 * товаров, которых на новом сайте нет, — ближайший раздел каталога.
 * 2026-11-03 совпавшим товарам вернули прежние латинские адреса (слаги
 * прежнего сайта, плагин cyr2lat), поэтому в таблице их больше нет. Временные
 * кириллические адреса этих товаров ведут на латинские штатно: WordPress
 * хранит прежний слаг (_wp_old_slug). Цели в таблице — конечные адреса, без
 * цепочки редиректов. Прежний сайт целиком — в папке
 * whoopstore.ru.prev-2026-10-29 на хостинге, если таблицу понадобится уточнить.
 *
 * Границы намеренные:
 *   • только 404 — существующие адреса нового сайта не перехватываются, даже
 *     если совпадут со строкой таблицы;
 *   • сравнение по пути без query string и без учёта регистра, завершающий
 *     слэш необязателен.
 *
 * /politika-konfidenczialnosti/ и /publichnaya-oferta/ на новом сайте живут
 * по тем же адресам, поэтому в таблице только их кривые варианты.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Старый путь → новый путь.
 *
 * @return array<string, string>
 */
function whoopstore_legacy_redirects() {
	return array(

		// Дубли товаров прежнего сайта (адрес с -2) — тот же товар. Остальные
		// совпавшие товары с 2026-11-03 открываются по прежним адресам сами.
		'/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-ash-grey-2/' => '/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-ash-grey/',
		'/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-jet-black-2/' => '/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-jet-black/',
		'/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-royal-blue-2/' => '/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-royal-blue/',

		// Товара на новом сайте нет — ближайший раздел каталога.
		'/product/besprovodnoj-zaryadnyj-blok-wireless-powerpack-dlya-whoop-5-0-mg/' => '/#accessories',
		'/product/nydroknit-vand-storm-gray/' => '/remeshki-whoop-5-0/',
		'/product/nydroknit-viser-vand-deer-end/' => '/remeshki-whoop-5-0/',
		'/product/nydroknit-viser-vand-night-swim-s-m-l-hl/' => '/remeshki-whoop-5-0/',
		'/product/nydroknit-viser-vand-storm-gray/' => '/remeshki-whoop-5-0/',
		'/product/sportivnyj-remeshok-dlya-whoop-mg-sportflex-band-fire/' => '/remeshki-whoop-mg/',
		'/product/umnyj-treker-metabolizma-po-dyhaniyu-lumen-metabolism-tracker/' => '/#devices',
		'/product/viser-vand-surerknit-onyh-s-m-l-hl/' => '/remeshki-whoop-5-0/',

		// Страницы и разделы каталога с новым адресом.
		'/aksessuary/' => '/#accessories',
		'/product-category/misc/' => '/shop/',
		'/product-category/whoop/' => '/shop/',
		'/product-tag/silikon/' => '/remeshki-whoop-5-0/',
		'/new-home/' => '/',
		'/remeshki-whop-mg/' => '/remeshki-whoop-mg/',
		'/remeshki/' => '/remeshki-whoop-5-0/',
		'/test-subscriptions/' => '/podpiski/',

		// Страницы без аналога на новом сайте.
		'/copmany/' => '/',
		'/for-business/' => '/',
		'/remeshki-whop-4-0/' => '/whoop4/',

		// Пустые рубрики прежнего блога. Сам блог, его статьи и рубрика
		// «Блог» с 2026-11-11 есть на новом сайте по прежним адресам.
		'/category/akczii/' => '/blog/',
		'/category/novosti/' => '/blog/',
		'/category/uncategorized/' => '/blog/',
		'/author/admin/' => '/blog/',

		// Пустой черновик-дубль страницы «Политика использования файлов
		// cookie»; сама страница перенесена по адресу /cookie-policy/.
		'/cookie-policy2/' => '/cookie-policy/',

		// Перенесено из .htaccess старого сайта.
		'/product-category/politika-konfidenczialnosti/' => '/politika-konfidenczialnosti/',
		'/product-category/whoop/page/politika-konfidenczialnosti/' => '/politika-konfidenczialnosti/',
		'/product-tag/politika-konfidenczialnosti/' => '/politika-konfidenczialnosti/',
		'/product/politika-konfidenczialnosti/' => '/politika-konfidenczialnosti/',
		'/product-category/publichnaya-oferta/' => '/publichnaya-oferta/',
		'/product-category/whoop/page/publichnaya-oferta/' => '/publichnaya-oferta/',
		'/product-tag/publichnaya-oferta/' => '/publichnaya-oferta/',
		'/product/publichnaya-oferta/' => '/publichnaya-oferta/',
	);
}

// Приоритет 9 — раньше redirect_canonical (10): на 404 ядро угадывает адрес по
// похожему слагу и могло бы увести не туда, куда указывает таблица.
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}

	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );

	if ( ! is_string( $path ) || '' === $path ) {
		return;
	}

	$path = trailingslashit( mb_strtolower( rawurldecode( $path ) ) );
	$map  = whoopstore_legacy_redirects();

	if ( ! isset( $map[ $path ] ) ) {
		return;
	}

	wp_safe_redirect( home_url( $map[ $path ] ), 301, 'Whoopstore legacy URL' );
	exit;
}, 9 );
