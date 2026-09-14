<?php
/**
 * Title: Balance banner
 * Slug: whoopstore/balance-banner
 *
 * Первый проход по всей Главной (2026-09-07), источник — Figma MCP get_metadata
 * (node 1:3464, "Мобилка") — см. accessories-grid.php.
 *
 * Небольшой блок-цитата между Audience и "Почему у WHOOP нет экрана":
 * "WHOOP помогает находить баланс между Восстановлением = Нагрузкой =
 * Качеством жизни" (3 подтверждённых текстовых слоя, разделены текстовыми
 * узлами "=" в самой Figma) + CTA "Подобрать модель".
 *
 * QA раунд 3 (2026-09-07): раунд 2 принял декоративный слой рядом за
 * маленький кружок-плейсхолдер — на деле (get_design_context, node 1:212)
 * это ПОЛНОРАЗМЕРНАЯ фоновая фотография 1621×692, ШИРЕ канвы 1200px
 * (full-bleed), с фейдом в фон страницы сверху/снизу — тот же приём, что у
 * hero (см. "align":"full" там).
 *
 * Аудит 2026-09-09 (round 5, реальный десктопный ассет): пользователь
 * прислал правильное фото (Rectangle-100247.png — то же название, что и у
 * узла 1:212 в Figma) — 1200×692, landscape, высота ТОЧНО совпадает с
 * подтверждённой высотой узла. Не 1621px (полный full-bleed кроп), но уже
 * нормальная ориентация вместо случайно использованного портретного
 * mobile-кропа (balance-banner-bg-mobile.jpg, 1696×2528 — закрыт как
 * причина сильной обрезки, см. history theme.css у __media-photo). Заменил
 * src на новый файл, .balance-banner__media-photo вернул на object-fit:
 * cover (см. её комментарий в theme.css насчёт object-position).
 *
 * Аудит 2026-09-09 (баг "фон-секция не full-bleed" после попытки выхода на
 * 100vw): `.front-page` (родитель) — has-global-padding (от
 * layout:{"type":"constrained"} в front-page.html), а у ядра WP есть
 * правило `.has-global-padding > .alignfull{margin-left/right: calc(root-
 * padding * -1)}` — специфичность (0,2,0) выше, чем у одиночного класса
 * `.balance-banner` (0,1,0), поэтому оно перебивало мой margin-left:-50vw
 * ДАЖЕ БЕЗ !important — просто за счёт специфичности. Тот же класс проблем,
 * что уже чинили у audience-marquee (см. её комментарий), только другое
 * WP-правило-триггер (там — is-layout-constrained/margin:auto!important,
 * здесь — has-global-padding/margin:0 без !important, но с более высокой
 * специфичностью). Фикс — тот же: обёртка `.balance-banner-wrap` БЕЗ
 * "layout":{"type":"constrained"} в атрибутах — такая группа не получает
 * has-global-padding (сверено на структуре .straps-carousel--compact/
 * --personalize, у которых group без layout-атрибута тоже не получает этот
 * класс), поэтому `.balance-banner` внутри неё больше не прямой потомок
 * has-global-padding контейнера.
 */
?>
<!-- wp:group {"className":"balance-banner-wrap"} -->
<div class="wp-block-group balance-banner-wrap">

	<!-- wp:group {"className":"balance-banner","align":"full","layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignfull balance-banner">

		<div class="balance-banner__media" aria-hidden="true">
			<img class="balance-banner__media-photo" src="/wp-content/uploads/2026/09/Rectangle-100247.webp" alt="" loading="lazy" />
			<div class="balance-banner__media-scrim"></div>
		</div>

		<div class="balance-banner__content">
			<!-- wp:heading {"level":2,"className":"balance-banner__title section-heading section-heading--center"} -->
			<h2 class="wp-block-heading balance-banner__title section-heading section-heading--center">WHOOP помогает <br/> находить баланс между</h2>
			<!-- /wp:heading -->

			<!-- wp:html -->
			<p class="balance-banner__equation">
				<span>Восстановлением</span>
				<span class="balance-banner__eq" aria-hidden="true">=</span>
				<span>Нагрузкой</span>
				<span class="balance-banner__eq" aria-hidden="true">=</span>
				<span>Качеством жизни</span>
			</p>
			<!-- /wp:html -->

			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/#devices">Подобрать модель</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
