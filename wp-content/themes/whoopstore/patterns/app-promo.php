<?php
/**
 * Title: App promo
 * Slug: whoopstore/app-promo
 *
 * Первый проход по всей Главной (2026-09-07), источник — Figma MCP get_metadata
 * (node 1:3464, "Мобилка") — см. accessories-grid.php.
 *
 * "Следите за здоровьем в приложении" — 3 карточки (AppStore/Google Play/
 * приложение на русском), у каждой подтверждённый заголовок из Figma и
 * своя кнопка ("Скачать" у первых двух, "Подключить" у третьей — тексты
 * кнопок различаются в самой Figma, не унификация с нашей стороны).
 *
 * Правка 2026-09-09 (визуальный аудит по скринам Figma vs stage,
 * пользователь явно выбрал вариант "стандартные брендовые SVG"):
 * - заголовок — принудительный перенос на 2 строки, подтверждён `<br aria-hidden />`
 *   в raw-дампе (node 1:234, homepage-desktop.partial-1.code.tsx.txt);
 * - кнопки были на стиле Dark (полупрозрачно-серые) — по Figma это White
 *   (белая заливка, тёмный текст, node 1:236/1:239/1:242), см. правку в theme.css;
 * - паддинг/gap карточки и gap списка были 24/16/16 — по геометрии Figma
 *   (1:228-1:244) это 30/30/20;
 * - иконки были 40×40 пустым плейсхолдером — заменены на 30×30.
 *   app-store.svg — реальный ассет, прислан пользователем прямо из Figma
 *   (node 1:244, чистый вектор) и сохранён без изменений.
 *   google-play.png — реальный ассет, получен напрямую через
 *   mcp__figma__download_assets (node 1:251, export.png) — байты пришли
 *   с сервера Figma по прямой ссылке (curl), не ручным переносом текста;
 *   сверен по md5 после сохранения.
 *   whoop-app-ru.png — для node 1:253 (frame_4962.png) первые 2 попытки
 *   (Figma MCP — упёрся в лимит Starter-плана, 20 вызовов/МЕСЯЦ, не в день;
 *   ручной перенос вставленного текстом base64 — дважды повреждён при
 *   декодировании) не удались. Решилось так: пользователь загрузил файл в
 *   Медиатеку WP (wp-content/uploads/2026/09/frame_4962.png-1.png) и прислал
 *   прямую ссылку — файл уже лежал на этом же сервере, скопирован напрямую
 *   (cp), сверен по md5 байт-в-байт. 30×30 PNG, RGBA с прозрачностью.
 */

/*
 * url пустой — адреса ещё нет (заказчик 2026-09-28: своего приложения у него
 * нет, куда ведёт «Подключить» — не решено). Тогда кнопка печатается как
 * <span>: выглядит как в макете, но ссылкой не притворяется — см.
 * .btn--inactive в theme.css. Появится адрес — достаточно вписать его сюда.
 */
$whoopstore_apps = [
	[
		'name' => 'Приложение Whoop в AppStore',
		'cta'  => 'Скачать',
		'icon' => 'app-store.svg',
		'url'  => '',
	],
	[
		'name' => 'Приложение Whoop в Play Market',
		'cta'  => 'Скачать',
		'icon' => 'google-play.png',
		'url'  => '',
	],
	[
		'name' => 'Приложение для Whoop на русском',
		'cta'  => 'Подключить',
		'icon' => 'whoop-app-ru.png',
		'url'  => '',
	],
];
?>
<!-- wp:group {"className":"app-promo","layout":{"type":"constrained"}} -->
<div class="wp-block-group app-promo">

	<!-- Аудит 2026-09-07 (единая типографика): было has-xl-font-size (35px)
	— по raw-дампу Figma (node 1:234) это section-heading (32px), слева. -->
	<!-- wp:heading {"level":2,"className":"app-promo__title section-heading"} -->
	<h2 class="wp-block-heading app-promo__title section-heading">Следите за здоровьем<br />в приложении</h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="app-promo__list">
		<?php foreach ( $whoopstore_apps as $app ) : ?>
			<div class="app-promo__card card">
				<div class="app-promo__icon" aria-hidden="true">
					<img src="<?php echo esc_url( get_theme_file_uri( 'assets/icons/' . $app['icon'] ) ); ?>" alt="" width="30" height="30" loading="lazy" />
				</div>
				<p class="app-promo__card-title text-label"><?php echo esc_html( $app['name'] ); ?></p>
				<?php if ( '' !== $app['url'] ) : ?>
					<a class="wp-block-button__link wp-element-button app-promo__button" href="<?php echo esc_url( $app['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $app['cta'] ); ?></a>
				<?php else : ?>
					<span class="wp-block-button__link wp-element-button app-promo__button btn--inactive"><?php echo esc_html( $app['cta'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
