<?php
/**
 * Title: Hero panel
 * Slug: whoopstore/hero-panel
 *
 * По Figma — полноэкранное фото с тёмным градиентом продолжается ЗА header
 * (шапка и hero — одна общая фотозона, header просто наложен сверху, без
 * своего фона). Оба фото загружены пользователем в Медиатеку (staging
 * uploads), путь — root-relative. Структура content-left/content-right —
 * правки пользователя поверх исходной (зелёная карточка текста + фото
 * устройства раздельно).
 *
 * Аудит 2026-09-07 (Low): атрибуты width/height у фото устройства были
 * 92×102 (вдвое меньше, чем реальный размер файла и фактический CSS-размер
 * 184×205) — проверено напрямую (JPEG SOF-маркер), файл НЕ retina-ассет,
 * а ровно 184×205, атрибуты были просто устаревшими/неверными. Исправлено
 * на реальный размер файла.
 *
 * Аудит 2026-09-07 (round 2, по просьбе пользователя): фон/скругление/
 * alignfull-обёртка переехали в общий wrapper .home-hero-wrap
 * (templates/front-page.html), который включает и header, и этот паттерн —
 * т.к. по Figma фото — это фон ОБЩЕЙ зоны шапка+hero, а не только hero.
 * Раньше фон рисовался внутри .hero-panel, а header выводился из потока
 * через position:absolute, чтобы "наложиться" на него — пользователь
 * справедливо отклонил абсолютное позиционирование header (нестандартно
 * для WP, хрупко). Теперь header в обычном потоке, сам паттерн — просто
 * текстовый контент без своего фона/alignfull/border-radius.
 */
?>
<!-- wp:group {"className":"hero-panel","layout":{"type":"constrained"}} -->
<div class="wp-block-group hero-panel">

	<!-- wp:group {"className":"hero-panel__content","layout":{"type":"flow"}} -->
	<div class="wp-block-group hero-panel__content">

		<!-- wp:heading {"level":1,"fontSize":"hero","className":"hero-panel__title"} -->
		<div class="hero-panel__content-left">
			<div>
				<h1 class="wp-block-heading hero-panel__title has-hero-font-size">Купить фитнес-трекер <strong>Whoop</strong> в России</h1>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"hero-panel__text section-subtext"} -->
				<p class="hero-panel__text section-subtext">Фитнес трекеры WHOOP 5.0, ONE, PEAK и LIFE — быстрая доставка, гарантия, помощь с подключением и оплатой</p>
			<!-- /wp:paragraph -->

			</div>
			<!-- wp:buttons {"className":"hero-panel__actions"} -->
			<div class="wp-block-buttons hero-panel__actions">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/#devices">Купить</a></div>
				<!-- /wp:button -->

				<!-- Аудит 2026-09-07 (единая система кнопок, docs/figma-design/raw/
				homepage-desktop.partial-1.code.tsx.txt:124): именно здесь "Написать
				нам" подтверждена как тёмно-серая (bg rgba(255,255,255,0.1)) — is-style-dark,
				не is-style-outline (тот теперь настоящий прозрачный+обводка). -->
				<!-- wp:button {"className":"is-style-dark"} -->
				<div class="wp-block-button is-style-dark"><a class="wp-block-button__link wp-element-button" href="#contacts">Написать нам</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>

		<div class="hero-panel__content-right" aria-hidden="true">
			<!-- Без loading="lazy": картинка находится в первом экране, и откладывать
			     её незачем — lazy здесь только добавляет задержку, потому что браузер
			     доходит до неё уже после первичной раскладки. Правило простое:
			     lazy нужен всему, что НИЖЕ первого экрана, и вреден тому, что в нём. -->
			<img src="/wp-content/uploads/2026/09/Group-1000010441.webp" alt="" width="184" height="205" />
		</div>
	</div>

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
