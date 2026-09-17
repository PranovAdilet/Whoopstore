<?php
/**
 * Theme setup: theme supports + asset enqueue.
 * Pilot-этап: только то, что реально используется Header/Footer/Главной/страницей товара.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	// gallery_thumbnail_image_width: миниатюры галереи товара в макете — 120×120
	// (Figma 30:144/30:247/30:248), стандартные WooCommerce — 100×100.
	// После изменения нужен `wp media regenerate` для уже загруженных фото,
	// иначе WordPress отдаст под этот размер оригинал.
	add_theme_support( 'woocommerce', [
		'gallery_thumbnail_image_width' => 120,
	] );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );

	// Галерея товара (см. inc/woocommerce/single-product.php — почему
	// классические шаблоны). Три штатных theme support WooCommerce:
	//   slider   — flexslider: свайп на mobile + синхронизация миниатюр
	//              с большим фото (в макете миниатюры слева переключают фото);
	//   lightbox — photoswipe: полноэкранный просмотр по клику на лупу;
	//   zoom     — WooCommerce создаёт саму кнопку-лупу
	//              (.woocommerce-product-gallery__trigger) только при
	//              включённом zoom, поэтому без него элемента из макета
	//              (квадрат 30×30 поверх фото) просто не существует.
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-zoom' );

	// Стили редактора. Без них Gutenberg рисует блоки голым HTML: превью
	// каталога — вертикальный список неоформленных карточек, по которому
	// невозможно принимать вёрстку. WordPress подмешивает эти правила в холст
	// редактора, префиксуя селекторы своей обёрткой, — на фронт они не влияют.
	//
	// Подключаем только theme.css: карточки (.device-card), сетки разделов
	// (.catalog-section__list), шапка страницы и типографика живут там.
	// woocommerce.css в холст не идёт намеренно — из блоков, которые заказчик
	// редактирует, его правила не задействует ни одно (единственное упоминание
	// карточки там — .related-carousel__track > .device-card, это лента
	// «Вам может понравиться» на странице товара), а лишние 52 КБ инлайна в
	// админке не бесплатны, особенно на узком канале.
	add_theme_support( 'editor-styles' );
	//
	// editor.css идёт вторым и содержит только отступы между секциями каталога:
	// на фронте их задаёт предок .catalog-page, которого в холсте редактора нет
	// (подробности — в шапке самого файла). На фронт этот файл не подключается.
	add_editor_style( [ 'assets/css/theme.css', 'assets/css/editor.css' ] );
} );

// Единая система кнопок (см. assets/css/theme.css, раздел "Buttons"): 4
// переиспользуемых стиля пилюли, подтверждённых в самом коде Figma
// (docs/figma-design/raw/*.tsx.txt) — Primary (стиль по умолчанию core/button,
// наследует theme.json), встроенный Outline ядра WP (переопределён под
// настоящий вид — прозрачный + белая обводка), и 2 дополнительных стиля темы:
// Dark (тёмно-серая, полупрозрачная заливка) и White (белая заливка). Так же
// они переиспользуются вне блочного редактора — переключатель Whoop 5.0/MG,
// фильтр устройств, свотчи вариаций (см. .btn--dark/.btn--white/.btn--outline
// в CSS) — вместо того, чтобы каждый компонент заново задавал свои цвета.
add_action( 'init', function () {
	register_block_style( 'core/button', [
		'name'  => 'dark',
		'label' => __( 'Тёмно-серая', 'whoopstore' ),
	] );

	register_block_style( 'core/button', [
		'name'  => 'white',
		'label' => __( 'Белая', 'whoopstore' ),
	] );
} );

// QA стейджа, раунд 2 (2026-09-07): версия ассетов была статичной
// (номер темы 0.1.0-pilot), а сами файлы отдаются с Cache-Control
// max-age=604800 — после любого деплоя браузер, уже открывавший сайт,
// мог неделю показывать старый CSS/JS без жёсткого обновления. filemtime()
// конкретного файла меняет ?ver= автоматически при каждом изменении файла,
// без ручного бампа номера версии темы.
function whoopstore_asset_version( $relative_path ) {
	$path = get_theme_file_path( $relative_path );
	return file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' );
}

// Подменяет расширение картинки из uploads на .webp, если такой файл реально
// лежит на диске; иначе возвращает путь как есть.
//
// Нужна там, где путь собирается из частей и его нельзя проверить глазами:
// каталог ремешков строит его как "префикс + слаг товара + расширение"
// (см. whoopstore_catalog_section). Если для какого-то товара .webp не
// сгенерирован, карточка покажет оригинал, а не битую картинку.
//
// Там, где путь к файлу записан в разметке целиком, подстановка не нужна —
// такие ссылки переведены на .webp напрямую.
function whoopstore_webp_src( $src ) {
	$webp = preg_replace( '/\.(png|jpe?g)$/i', '.webp', $src );

	if ( null === $webp || $webp === $src ) {
		return $src;
	}

	return file_exists( ABSPATH . ltrim( $webp, '/' ) ) ? $webp : $src;
}

add_action( 'wp_enqueue_scripts', function () {
	// Общесайтовые стили (header/footer/hero/advantages/help-block/кнопки).
	wp_enqueue_style( 'whoopstore-theme', get_theme_file_uri( 'assets/css/theme.css' ), [], whoopstore_asset_version( 'assets/css/theme.css' ) );

	// Точечные WC-переопределения сверх theme.json (галерея, аккордеон, мини-корзина).
	wp_enqueue_style( 'whoopstore-woocommerce', get_theme_file_uri( 'assets/css/woocommerce.css' ), [ 'whoopstore-theme' ], whoopstore_asset_version( 'assets/css/woocommerce.css' ) );

	// Бургер-меню — нужен на каждой странице (Header есть везде).
	wp_enqueue_script( 'whoopstore-header-nav', get_theme_file_uri( 'assets/js/header-nav.js' ), [], whoopstore_asset_version( 'assets/js/header-nav.js' ), true );

	// Точки-пагинация мобильного слайдера преимуществ (секция "Отсутствие дисплея…").
	// Паттерн whoopstore/no-display пока используется только на Главной — подключаем
	// точечно, а не глобально; сам свайп работает на CSS scroll-snap и без скрипта.
	if ( is_front_page() ) {
		wp_enqueue_script( 'whoopstore-benefits-slider', get_theme_file_uri( 'assets/js/benefits-slider.js' ), [], whoopstore_asset_version( 'assets/js/benefits-slider.js' ), true );

		// Pinned-скролл секции "Преимущества": секция залипает на весь экран,
		// заголовок исчезает, дальнейший скролл листает слайды. Сам пин
		// включает только скрипт (см. .is-pinned) — без него секция остаётся
		// обычным вертикальным списком слайдов, поэтому точечное подключение
		// на Главной ничего не ломает на остальных страницах.
		wp_enqueue_script( 'whoopstore-advantages-sticky-scroll', get_theme_file_uri( 'assets/js/advantages-sticky-scroll.js' ), [], whoopstore_asset_version( 'assets/js/advantages-sticky-scroll.js' ), true );

		// Кнопка "Посмотреть ещё" в секции показателей (раскрывает третий
		// ряд карточек и подсекцию "Дополнительно в WHOOP MG" — состояние
		// "Раскрытый" из Figma). Тот же паттерн подключения: секция пока
		// только на Главной.
		wp_enqueue_script( 'whoopstore-advantages-more', get_theme_file_uri( 'assets/js/advantages-more.js' ), [], whoopstore_asset_version( 'assets/js/advantages-more.js' ), true );

		// Лента "Персонализируйте свой WHOOP": шаг стрелками и плашка с
		// названием выбранного ремешка. Свайп работает и без скрипта —
		// прокрутка у ленты нативная.
		wp_enqueue_script( 'whoopstore-straps-carousel', get_theme_file_uri( 'assets/js/straps-carousel.js' ), [], whoopstore_asset_version( 'assets/js/straps-carousel.js' ), true );

		// Фильтр-пилюли в сетке трекеров.
		wp_enqueue_script( 'whoopstore-device-filter', get_theme_file_uri( 'assets/js/device-filter.js' ), [], whoopstore_asset_version( 'assets/js/device-filter.js' ), true );
	}

	// Скрипты страницы товара. Тост "Товар добавлен в Корзину", степпер и
	// кнопка-корзина в шапке переехали в общий модуль корзины
	// (assets/js/cart.js, подключается в inc/woocommerce/cart-ui.php) —
	// он нужен на всех страницах, а не только на карточке товара.
	if ( function_exists( 'is_product' ) && is_product() ) {
		// P1-1: pill-переключатель атрибута вариации поверх нативного <select>.
		// Зависит от wc-add-to-cart-variation, чтобы штатный обработчик 'change'
		// уже был навешан к моменту первого программного выбора варианта.
		wp_enqueue_script( 'whoopstore-variation-swatches', get_theme_file_uri( 'assets/js/variation-swatches.js' ), [ 'wc-add-to-cart-variation' ], whoopstore_asset_version( 'assets/js/variation-swatches.js' ), true );

		// Точки-пагинация галереи (в макете есть и на desktop, и на mobile —
		// вторая навигация по слайдам рядом с миниатюрами, см. сам файл).
		wp_enqueue_script( 'whoopstore-product-gallery', get_theme_file_uri( 'assets/js/product-gallery.js' ), [ 'wc-single-product' ], whoopstore_asset_version( 'assets/js/product-gallery.js' ), true );

		// Стрелки ленты "Вам может понравиться" (сам свайп — на CSS scroll-snap).
		wp_enqueue_script( 'whoopstore-related-carousel', get_theme_file_uri( 'assets/js/related-carousel.js' ), [], whoopstore_asset_version( 'assets/js/related-carousel.js' ), true );
	}
} );

/**
 * Скрипты WooCommerce на страницах, где нет ни одной формы добавления в корзину.
 *
 * На Главной, каталогах ремешков и «Подписках» карточки — обычные ссылки на
 * товар, форм добавления нет вовсе (проверено рендером). WooCommerce при этом
 * ставит туда jQuery, jQuery Migrate, blockUI, add-to-cart, js-cookie и свой
 * frontend-скрипт: он обслуживает поля чекаута, личный кабинет и показ пароля.
 * Наша корзина работает на Store API и от jQuery не зависит, фрагменты корзины
 * WooCommerce не используются, зависимостей от этих хендлов на таких страницах
 * нет (wp_scripts->registered проверен).
 *
 * Сбор источника заказа (sourcebuster-js + wc-order-attribution) НЕ трогаем: он
 * должен работать на всех страницах, включая точку входа, иначе в заказе
 * потеряется источник перехода. От снимаемых хендлов он не зависит.
 *
 * Это обычная оптимизация (минус 6 запросов со страницы), а не лечение 504 на
 * стенде — те приходят от прокси перед сайтом, см. docs/commerce-notes.md.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}

	// Любая страница WooCommerce (магазин, товар, категория), корзина, оформление
	// заказа и кабинет получают их в полном объёме.
	if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
		return;
	}

	foreach ( [ 'woocommerce', 'wc-add-to-cart', 'jquery-blockui', 'js-cookie' ] as $handle ) {
		wp_dequeue_script( $handle );
	}
}, 99 );

// Фотография первого экрана задана в CSS (background: url(...) на
// .home-hero-wrap__media), а не тегом <img>. Такие картинки браузер находит
// только после загрузки и разбора theme.css, то есть заметно позже разметки —
// а это ровно LCP-элемент страницы, и задержка видна пользователю.
// Preload в <head> даёт браузеру ссылку до того, как он дойдёт до CSS.
//
// Карта "контекст -> файл" держит оба hero в одном месте: Главная и Подписки
// используют общую обёртку .home-hero-wrap и отличаются только фотографией
// (см. .home-hero-wrap--subscriptions в theme.css).
function whoopstore_hero_background_url() {
	if ( is_front_page() ) {
		return '/wp-content/uploads/2026/09/Rectangle-100233.webp';
	}

	if ( is_page_template( 'page-subscriptions' ) ) {
		return '/wp-content/uploads/2026/09/subscriptions-hero.jpg';
	}

	return '';
}

// Приоритет 2 — до вывода стилей, иначе preload теряет смысл.
add_action( 'wp_head', function () {
	$whoopstore_hero_background = whoopstore_hero_background_url();

	if ( '' === $whoopstore_hero_background ) {
		return;
	}

	printf(
		'<link rel="preload" as="image" href="%s" fetchpriority="high" />' . "\n",
		esc_url( $whoopstore_hero_background )
	);
}, 2 );

// NB: текст бейджа "Распродажа" (вместо стандартного WC "Sale!") сознательно НЕ переопределён —
// у pilot-товара нет sale_price, проверить хук не на чем. Добавить фильтром
// woocommerce_sale_flash, когда появится первый товар со скидкой.
