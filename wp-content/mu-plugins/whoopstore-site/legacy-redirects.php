<?php
/**
 * Адреса прежнего сайта whoopstore.ru → 301 на новый адрес.
 *
 * Новый сайт встаёт на домен, где несколько лет работал прежний магазин.
 * Его страницы есть в поиске, в закладках и во внешних ссылках, а адреса у
 * сайтов разные: у товаров прежнего сайта слаги латиницей (плагин cyr2lat),
 * у нового — кириллицей, часть товаров и весь блог не переносились
 * (решение 2026-09-18, launch-checklist.md, раздел 0). Без перенаправления
 * все эти адреса отдавали бы 404.
 *
 * Таблица собрана 2026-10-28 из опубликованных записей обоих сайтов: товары
 * сопоставлены по названию, для товаров, которых на новом сайте нет, —
 * ближайший раздел каталога. Прежний сайт целиком доступен в архиве на
 * old.whoopstore.ru, если таблицу понадобится уточнить.
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

		// Тот же товар на новом сайте (сопоставлен по названию).
		'/product/aktivacziya-whoop-privyazka-inostrannoj-karty/' => '/product/активация-whoop-привязка-иностранной-кар/',
		'/product/fitnes-treker-whoop-4-0-s-podpiskoj-peak-na-12-mesyaczev-kopirovat-kopirovat-kopirovat/' => '/product/новый-трекер-whoop-one-с-подпиской-на-1-год-whoop-5-0/',
		'/product/fitnes-treker-whoop-5-0-life-s-podpiskoj-na-1-mesyacz/' => '/product/фитнес-трекер-whoop-mg-life-с-подпиской-на-1-месяц/',
		'/product/fitnes-treker-whoop-5-0-one-s-podpiskoj-na-1-mesyacz/' => '/product/фитнес-трекер-whoop-5-0-one-с-подпиской-на-1-месяц/',
		'/product/fitnes-treker-whoop-5-0-peak-s-podpiskoj-na-1-mesyacz/' => '/product/фитнес-трекер-whoop-5-0-peak-с-подпиской-на-1-месяц/',
		'/product/kashemirovyj-remeshok-dlya-whoop-5-0-cloudknit-band-fawn/' => '/product/кашемировый-ремешок-для-whoop-5-0-cloudknit-band-fawn/',
		'/product/kashemirovyj-remeshok-dlya-whoop-5-0-cloudknit-band-storm/' => '/product/кашемировый-ремешок-для-whoop-5-0-cloudknit-band-storm/',
		'/product/kashemirovyj-remeshok-dlya-whoop-mg-cloudknit-band-fawn/' => '/product/кашемировый-ремешок-для-whoop-mg-cloudknit-band-fawn/',
		'/product/kashemirovyj-remeshok-dlya-whoop-mg-cloudknit-band-storm/' => '/product/кашемировый-ремешок-для-whoop-mg-cloudknit-band-storm/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-5-0-leatherluxe-straight-band-1/' => '/product/кожаный-люксовый-ремешок-для-whoop-5-0-leatherluxe-straight-band-chestnut-c/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-5-0-leatherluxe-straight-band-2/' => '/product/кожаный-люксовый-ремешок-для-whoop-5-0-leatherluxe-tapered-band-camel-with/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-5-0-leatherluxe-straight-band-black-cream-with-titanium/' => '/product/кожаный-люксовый-ремешок-для-whoop-5-0-leatherluxe-straight-band-black-crea/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-5-0-leatherluxe-tapered-band-3/' => '/product/кожаный-люксовый-ремешок-для-whoop-5-0-leatherluxe-tapered-band-black-with/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-mg-leatherluxe-straight-band-black-cream-with-black/' => '/product/кожаный-люксовый-ремешок-для-whoop-mg-leatherluxe-straight-band-black-cre-2/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-mg-leatherluxe-straight-band-black-cream-with-titanium/' => '/product/кожаный-люксовый-ремешок-для-whoop-mg-leatherluxe-straight-band-black-cream/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-mg-leatherluxe-straight-band-black-with-gold/' => '/product/кожаный-люксовый-ремешок-для-whoop-mg-leatherluxe-straight-band-black-wit-2/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-mg-leatherluxe-straight-band-black-with-titanium/' => '/product/кожаный-люксовый-ремешок-для-whoop-mg-leatherluxe-straight-band-black-with/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-mg-leatherluxe-straight-band-camel-with-gold/' => '/product/кожаный-люксовый-ремешок-для-whoop-mg-leatherluxe-straight-band-camel-with/',
		'/product/kozhanyj-lyuksovyj-remeshok-dlya-whoop-mg-leatherluxe-straight-band-chestnut-cream-with-titanium/' => '/product/кожаный-люксовый-ремешок-для-whoop-mg-leatherluxe-straight-band-chestnut-cr/',
		'/product/novyj-treker-whoop-life-s-podpiskoj-na-1-god-whoop-mg/' => '/product/новый-трекер-whoop-life-с-подпиской-на-1-год-whoop-mg/',
		'/product/novyj-treker-whoop-peak-s-podpiskoj-na-1-god-whoop-5-0/' => '/product/новый-трекер-whoop-peak-с-подпиской-на-1-год-whoop-5-0/',
		'/product/pletenyj-lyuksovyj-remeshok-dlya-whoop-5-0-superknit-band-dune-with-gold/' => '/product/плетеный-люксовый-ремешок-для-whoop-5-0-superknit-band-dune-with-gold/',
		'/product/pletenyj-lyuksovyj-remeshok-dlya-whoop-5-0-superknit-band-midnight-with-titanium/' => '/product/плетеный-люксовый-ремешок-для-whoop-5-0-superknit-band-midnight-with-t/',
		'/product/pletenyj-lyuksovyj-remeshok-dlya-whoop-5-0-superknit-band-obsidian-with-gold/' => '/product/плетеный-люксовый-ремешок-для-whoop-5-0-superknit-band-obsidian-with-g/',
		'/product/pletenyj-lyuksovyj-remeshok-dlya-whoop-5-0-superknit-band-obsidian-with-titanium/' => '/product/плетеный-люксовый-ремешок-для-whoop-5-0-superknit-band-obsidian-with-t/',
		'/product/pletenyj-lyuksovyj-remeshok-dlya-whoop-mg-superknit-band-dune-with-gold/' => '/product/плетеный-люксовый-ремешок-для-whoop-mg-superknit-band-dune-with-gold/',
		'/product/pletenyj-lyuksovyj-remeshok-dlya-whoop-mg-superknit-band-midnight-with-titanium/' => '/product/плетеный-люксовый-ремешок-для-whoop-mg-superknit-band-midnight-with-ti/',
		'/product/pletenyj-lyuksovyj-remeshok-dlya-whoop-mg-superknit-band-obsidian-with-gold/' => '/product/плетеный-люксовый-ремешок-для-whoop-mg-superknit-band-obsidian-with-go/',
		'/product/pletenyj-remeshok-dlya-whoop-5-0-superknit-band-dune/' => '/product/плетеный-ремешок-для-whoop-5-0-superknit-band-dune/',
		'/product/pletenyj-remeshok-dlya-whoop-5-0-superknit-band-glacier/' => '/product/плетеный-ремешок-для-whoop-5-0-superknit-band-glacier/',
		'/product/pletenyj-remeshok-dlya-whoop-5-0-superknit-band-midnight-2/' => '/product/плетеный-ремешок-для-whoop-5-0-superknit-band-midnight/',
		'/product/pletenyj-remeshok-dlya-whoop-5-0-superknit-band-midnight/' => '/product/плетеный-ремешок-для-whoop-5-0-superknit-band-graphite/',
		'/product/pletenyj-remeshok-dlya-whoop-5-0-superknit-band-obsidian/' => '/product/плетеный-ремешок-для-whoop-5-0-superknit-band-forest/',
		'/product/pletenyj-remeshok-dlya-whoop-5-0-superknit-band-rhodonite/' => '/product/плетеный-ремешок-для-whoop-5-0-superknit-band-rhodonite/',
		'/product/pletenyj-remeshok-dlya-whoop-mg-superknit-band-dune/' => '/product/плетеный-ремешок-для-whoop-mg-superknit-band-dune/',
		'/product/pletenyj-remeshok-dlya-whoop-mg-superknit-band-echo/' => '/product/плетеный-ремешок-для-whoop-mg-superknit-band-echo/',
		'/product/pletenyj-remeshok-dlya-whoop-mg-superknit-band-fresh/' => '/product/плетеный-ремешок-для-whoop-mg-superknit-band-fresh/',
		'/product/pletenyj-remeshok-dlya-whoop-mg-superknit-band-glacier/' => '/product/плетеный-ремешок-для-whoop-mg-superknit-band-glacier/',
		'/product/pletenyj-remeshok-dlya-whoop-mg-superknit-band-graphite/' => '/product/плетеный-ремешок-для-whoop-mg-superknit-band-graphite/',
		'/product/pletenyj-remeshok-dlya-whoop-mg-superknit-band-midnight/' => '/product/плетеный-ремешок-для-whoop-mg-superknit-band-midnight/',
		'/product/pletenyj-remeshok-dlya-whoop-mg-superknit-band-obsidian/' => '/product/плетеный-ремешок-для-whoop-mg-superknit-band-obsidian/',
		'/product/podpiska-whoop-life-podklyuchenie-prodlenie/' => '/product/подписка-whoop-life-подключение-продление/',
		'/product/podpiska-whoop-one-podklyuchenie-prodlenie/' => '/product/подписка-whoop-one-подключение-продление/',
		'/product/podpiska-whoop-peak-podklyuchenie-prodlenie/' => '/product/подписка-whoop-peak-подключение-продление/',
		'/product/provodnoj-zaryadnyj-blok-basic-charger-dlya-whoop-5-0-mg/' => '/product/проводной-зарядный-блок-basic-charger-для-whoop-5-0-mg/',
		'/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-ash-grey-2/' => '/product/ремешок-на-бицепс-для-whoop-5-0-mg-coreknit-bicep-band-ash-grey/',
		'/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-ash-grey/' => '/product/ремешок-на-бицепс-для-whoop-5-0-mg-coreknit-bicep-band-ash-grey/',
		'/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-jet-black-2/' => '/product/ремешок-на-бицепс-для-whoop-5-0-mg-coreknit-bicep-band-jet-black/',
		'/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-jet-black/' => '/product/ремешок-на-бицепс-для-whoop-5-0-mg-coreknit-bicep-band-jet-black/',
		'/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-royal-blue-2/' => '/product/ремешок-на-бицепс-для-whoop-5-0-mg-coreknit-bicep-band-royal-blue/',
		'/product/remeshok-na-biczeps-dlya-whoop-5-0-mg-coreknit-bicep-band-royal-blue/' => '/product/ремешок-на-бицепс-для-whoop-5-0-mg-coreknit-bicep-band-royal-blue/',
		'/product/sportivnyj-remeshok-dlya-whoop-5-0-sportflex-band-bounce/' => '/product/спортивный-ремешок-для-whoop-5-0-sportflex-band-bounce/',
		'/product/sportivnyj-remeshok-dlya-whoop-5-0-sportflex-band-drift/' => '/product/спортивный-ремешок-для-whoop-5-0-sportflex-band-drift/',
		'/product/sportivnyj-remeshok-dlya-whoop-5-0-sportflex-band-gravity/' => '/product/спортивный-ремешок-для-whoop-5-0-sportflex-band-gravity/',
		'/product/sportivnyj-remeshok-dlya-whoop-5-0-sportflex-band-rush/' => '/product/спортивный-ремешок-для-whoop-5-0-sportflex-band-rush/',
		'/product/sportivnyj-remeshok-dlya-whoop-mg-sportflex-band-cr7-home/' => '/product/спортивный-ремешок-для-whoop-mg-sportflex-band-cr7-home/',
		'/product/sportivnyj-remeshok-dlya-whoop-mg-sportflex-band-drift/' => '/product/спортивный-ремешок-для-whoop-mg-sportflex-band-drift/',
		'/product/sportivnyj-remeshok-dlya-whoop-mg-sportflex-band-flurry/' => '/product/спортивный-ремешок-для-whoop-mg-sportflex-band-flurry/',
		'/product/sportivnyj-remeshok-dlya-whoop-mg-sportflex-band-gravity/' => '/product/спортивный-ремешок-для-whoop-mg-sportflex-band-gravity/',
		'/product/sportivnyj-remeshok-dlya-whoop-mg-sportflex-band-rush/' => '/product/спортивный-ремешок-для-whoop-mg-sportflex-band-rush/',
		'/product/vlagostojkij-remeshok-dlya-whoop-5-0-coreknit-band-ash-grey/' => '/product/влагостойкий-ремешок-для-whoop-5-0-coreknit-band-ash-grey/',
		'/product/vlagostojkij-remeshok-dlya-whoop-5-0-coreknit-band-evergreen/' => '/product/влагостойкий-ремешок-для-whoop-5-0-coreknit-band-evergreen/',
		'/product/vlagostojkij-remeshok-dlya-whoop-5-0-coreknit-band-jet-black/' => '/product/влагостойкий-ремешок-для-whoop-5-0-coreknit-band-jet-black/',
		'/product/vlagostojkij-remeshok-dlya-whoop-5-0-coreknit-band-royal-blue/' => '/product/влагостойкий-ремешок-для-whoop-5-0-coreknit-band-royal-blue/',

		// Товара на новом сайте нет — ближайший раздел каталога.
		'/product/besprovodnoj-zaryadnyj-blok-wireless-powerpack-dlya-whoop-5-0-mg/' => '/product-category/aksessuary/',
		'/product/kashemirovyj-remeshok-dlya-whoop-4-0-knit-wrist-bands-haven/' => '/product-category/remeshki/',
		'/product/kashemirovyj-remeshok-dlya-whoop-4-0-knit-wrist-bands-shadow/' => '/product-category/remeshki/',
		'/product/nydroknit-vand-storm-gray/' => '/product-category/remeshki/',
		'/product/nydroknit-viser-vand-deer-end/' => '/product-category/remeshki/',
		'/product/nydroknit-viser-vand-night-swim-s-m-l-hl/' => '/product-category/remeshki/',
		'/product/nydroknit-viser-vand-storm-gray/' => '/product-category/remeshki/',
		'/product/pletyonyj-remeshok-dlya-whoop-4-0-superknit-luxe-band-luxe/' => '/product-category/remeshki/',
		'/product/pletyonyj-remeshok-dlya-whoop-4-0-superknit-luxe-band-midnight-platinum/' => '/product-category/remeshki/',
		'/product/prodlenie-podpiski-na-12-ili-24-mesyacza-dlya-whoop-4-0-peak/' => '/product-category/aktivaciya-i-prodlenie/',
		'/product/sportivnyj-remeshok-dlya-whoop-4-0-sportflex-surge-2/' => '/product-category/remeshki/',
		'/product/sportivnyj-remeshok-dlya-whoop-4-0-sportflex-surge/' => '/product-category/remeshki/',
		'/product/sportivnyj-remeshok-dlya-whoop-mg-sportflex-band-fire/' => '/product-category/remeshki/sportflex-mg/',
		'/product/umnyj-treker-metabolizma-po-dyhaniyu-lumen-metabolism-tracker/' => '/product-category/trekery/',
		'/product/viser-vand-surerknit-onyh-s-m-l-hl/' => '/product-category/remeshki/',

		// Страницы и разделы каталога с новым адресом.
		'/aksessuary/' => '/product-category/aksessuary/',
		'/product-category/misc/' => '/shop/',
		'/product-category/whoop/' => '/shop/',
		'/product-tag/silikon/' => '/product-category/remeshki/',
		'/remeshki-whop-mg/' => '/remeshki-whoop-mg/',
		'/remeshki/' => '/remeshki-whoop-5-0/',
		'/test-subscriptions/' => '/podpiski/',

		// Страницы без аналога на новом сайте.
		'/copmany/' => '/',
		'/for-business/' => '/',
		'/remeshki-whop-4-0/' => '/',
		'/whoop4/' => '/',

		// Блог: на новом сайте его нет.
		'/247-2/' => '/',
		'/blog/' => '/',
		'/category/akczii/' => '/',
		'/category/blog/' => '/',
		'/category/novosti/' => '/',
		'/category/uncategorized/' => '/',
		'/ot-universitetskogo-proekta-do-milliardnoj-kompanii/' => '/',
		'/pochemu-whoop-nazyvayut-gadzhetom-milliarderov-kotorye-snyali-svoi-rolex/' => '/',
		'/primenenie-za-predelami-sporta-pochemu-whoop-5-0-davno-uzhe-ne-fitnes-treker/' => '/',
		'/whoop-4-0-i-son-rasskazyvaem-vse-chto-nuzhno-znat-ob-etom-pokazatele/' => '/',
		'/whoop-5-0-pochemu-etot-treker-ne-pro-10-tysyach-shagov-a-pro-budushhee/' => '/',

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
