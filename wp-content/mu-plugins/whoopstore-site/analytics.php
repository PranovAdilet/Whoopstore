<?php
/**
 * Счётчики аналитики: Яндекс.Метрика и Google Tag Manager.
 *
 * ТЗ требует сохранить Метрику/GTM и рекламные события. Номеров счётчиков
 * заказчик пока не дал, поэтому здесь только точка подключения: пока поля
 * пустые, на страницах не появляется ничего. Когда номер появится, его
 * вписывают в «Настройки → Общие» — код менять не нужно.
 *
 * **Счётчики не работают нигде, кроме production.** Стенд закрыт Basic Auth и
 * noindex; слать оттуда визиты в реальный счётчик нельзя — испортится
 * статистика заказчика. Проверка идёт по WP_ENVIRONMENT_TYPE (на стенде —
 * staging). Переопределяется фильтром whoopstore_analytics_enabled.
 *
 * Чего здесь сознательно нет: отправки товарных («рекламных») событий —
 * просмотр товара, добавление в корзину, покупка. Их состав и названия целей
 * зависят от того, как счётчик настроен у заказчика; придумывать свою схему
 * событий нельзя — придут данные, которые не сойдутся с текущей статистикой.
 * Нужен список целей и вариант e-commerce (Метрика или dataLayer GTM).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Номер счётчика Яндекс.Метрики (только цифры) или пустая строка.
 *
 * @return string
 */
function whoopstore_metrika_id() {
	return preg_replace( '/\D/', '', (string) get_option( 'whoopstore_yandex_metrika_id', '' ) );
}

/**
 * Идентификатор контейнера GTM вида GTM-XXXXXX или пустая строка.
 *
 * @return string
 */
function whoopstore_gtm_id() {
	$id = strtoupper( trim( (string) get_option( 'whoopstore_gtm_id', '' ) ) );

	return preg_match( '/^GTM-[A-Z0-9]+$/', $id ) ? $id : '';
}

/**
 * Можно ли выводить счётчики на этой странице.
 *
 * @return bool
 */
function whoopstore_analytics_enabled() {
	$enabled = 'production' === wp_get_environment_type() && ! is_admin();

	/**
	 * Разрешает или запрещает вывод счётчиков.
	 *
	 * @param bool $enabled Значение по умолчанию.
	 */
	return (bool) apply_filters( 'whoopstore_analytics_enabled', $enabled );
}

/**
 * Счётчик DMP Programmatic.ru.
 *
 * Код вендора вставлен как есть, без обёртки в wp_enqueue_script: у него свой
 * комментарий-маркер и свой формат тега, а подменять чужой сниппет своей
 * сборкой — лишний риск на ровном месте.
 *
 * В отличие от Метрики и GTM, этот счётчик **не ограничен production** — так
 * попросил заказчик, чтобы его было видно на стенде. Значит, на стенде он
 * тоже отрабатывает и шлёт запросы в Programmatic.ru; если это нежелательно,
 * достаточно обернуть вывод в whoopstore_analytics_enabled(), как у соседей.
 */
add_action( 'wp_head', function () {
	?>
<!-- [Programmatic.ru](https://programmatic.ru/) dmp counter -->
<script async type='text/javascript' src='https://dmp.programmatic.ru/assets'></script>
<!-- [Programmatic.ru](https://programmatic.ru/) dmp counter -->
	<?php
}, 1 );

// Метрика и head-часть GTM. Приоритет 1 — счётчик должен успеть засечь визит
// даже если посетитель уйдёт со страницы сразу.
add_action( 'wp_head', function () {
	if ( ! whoopstore_analytics_enabled() ) {
		return;
	}

	$gtm = whoopstore_gtm_id();

	if ( '' !== $gtm ) {
		?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo esc_js( $gtm ); ?>');</script>
<!-- End Google Tag Manager -->
		<?php
	}

	$metrika = whoopstore_metrika_id();

	if ( '' !== $metrika ) {
		?>
<!-- Yandex.Metrika counter -->
<script>(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<e.length;j++){if(e[j].src===r){return}}k=t.createElement("script"),a=t.getElementsByTagName("script")[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document.scripts,document,"https://mc.yandex.ru/metrika/tag.js","ym");ym(<?php echo (int) $metrika; ?>,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true});</script>
<noscript><div><img src="https://mc.yandex.ru/watch/<?php echo (int) $metrika; ?>" style="position:absolute;left:-9999px" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
		<?php
	}
}, 1 );

// Запасной кадр GTM для браузеров без JS — по требованию Google идёт сразу
// после открывающего <body>. В блочной теме этот хук вызывает само ядро
// (wp-includes/template-canvas.php).
add_action( 'wp_body_open', function () {
	if ( ! whoopstore_analytics_enabled() ) {
		return;
	}

	$gtm = whoopstore_gtm_id();

	if ( '' === $gtm ) {
		return;
	}
	?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
	<?php
} );

/**
 * Поля номеров счётчиков в «Настройки → Общие».
 *
 * Отдельной страницы настроек не заводим: это две строки, которые вписывают
 * один раз при запуске.
 */
add_action( 'admin_init', function () {
	$fields = [
		'whoopstore_yandex_metrika_id' => [
			'label'       => 'Яндекс.Метрика',
			'placeholder' => 'например, 12345678',
			'description' => 'Номер счётчика. Пусто — счётчик не подключается.',
		],
		'whoopstore_gtm_id'            => [
			'label'       => 'Google Tag Manager',
			'placeholder' => 'например, GTM-ABC1234',
			'description' => 'Идентификатор контейнера. Пусто — контейнер не подключается.',
		],
	];

	foreach ( $fields as $option => $field ) {
		register_setting( 'general', $option, [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
			'show_in_rest'      => false,
		] );

		add_settings_field(
			$option,
			$field['label'],
			function () use ( $option, $field ) {
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="%3$s" /><p class="description">%4$s</p>',
					esc_attr( $option ),
					esc_attr( (string) get_option( $option, '' ) ),
					esc_attr( $field['placeholder'] ),
					esc_html( $field['description'] )
				);
			},
			'general',
			'default',
			[ 'label_for' => $option ]
		);
	}
} );
