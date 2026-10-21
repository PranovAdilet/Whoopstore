<?php
/**
 * Title: FAQ accordion
 * Slug: whoopstore/faq-accordion
 *
 * Первый проход по всей Главной (2026-09-07), источник — Figma MCP get_metadata
 * (node 1:3464, "Мобилка") — см. accessories-grid.php.
 *
 * "Частые вопросы" — 10 пунктов, все заголовки вопросов подтверждены
 * текстовыми слоями Figma. Текст ответов в свёрнутом аккордеоне не
 * читается ни через get_metadata, ни через get_design_context (Figma не
 * отдаёт содержимое раскрытого состояния без отдельного узла на него —
 * см. docs/figma-design/README.md, "не удалось подтвердить"), поэтому тела
 * ответов оставлены нейтральной заглушкой "Информация уточняется" — не
 * выдумываю реальные ответы. Реализовано нативными <details>/<summary> —
 * доступный аккордеон без JS.
 *
 * Аудит 2026-09-07 (Critical): карточка + иконка — подтверждённый реальный
 * ассет "Group 311" (docs/figma-design/raw/homepage-desktop.supplementary-
 * sections.code.tsx.txt, блок 3) — это "+" (два перекрестных штриха), не
 * шеврон, как было в первом проходе по догадке. Инлайню SVG напрямую (не
 * <img>), чтобы поворотом на 45° при открытии превращать "+" в "×" —
 * тот же файл лежит в assets/icons/faq-chevron.svg для справки.
 *
 * QA раунд 3 (2026-09-07): раунд 2 не заметил отдельный слой на всю
 * секцию — атмосферная фоновая фотография (node 1:316, get_design_context,
 * homepage-desktop.partial-1) 1200×1200, opacity 0.8, с фейдом в фон
 * страницы сверху/снизу (та же идея, что у balance-banner) — растворяется
 * позади карточек FAQ, не мешая их читаемости (карточки сами по себе
 * непрозрачные). "align":"full" — фото шире обычного 1120px-контента, но
 * сам список вопросов остаётся в обычной читаемой ширине (вложенный
 * .faq__content).
 *
 * Правка 2026-09-09 (по фидбеку пользователя, сравнение со скрином Figma):
 * 1) Пропущен пункт "Можно вернуть товар или оплату за заказ" — по raw-
 *    метаданным (docs/figma-design/raw/homepage-desktop-metadata.xml,
 *    node 1:370, y=11565) он стоит между "Как купить Whoop 5.0 оптом на
 *    юр.лицо" (y=11470) и "Сотрудничество со спортивными командами"
 *    (y=11660) — было 9 пунктов вместо 10, добавлен в этом месте списка.
 * 2) Фон (см. заметку QA раунда 3 выше) не тянулся на весь экран на
 *    стейдже — сама секция .faq была ограничена родителем .front-page
 *    (max-width:1200px), фото Figma (1200×1200) ровно с холст, поэтому по
 *    одним координатам это неоднозначно (для сравнения — у .home-hero-wrap
 *    то же самое "ровно с холст" осознанно НЕ растянуто на 100vw). Так как
 *    пользователь, глядя на реальный макет, подтвердил, что фон должен
 *    доходить до края экрана — сделано full-bleed тем же приёмом, что и
 *    .balance-banner (left:50%/-50vw/100vw, см. theme.css), содержимое
 *    (.faq__content) по-прежнему по центру шириной 1120px.
 *
 * Правка 2026-09-09 (round 2, баг "весь контент ушёл вправо" после правки
 * выше): та же причина, что уже чинили у .balance-banner (см. её
 * комментарий в balance-banner.php) — `.front-page` (родитель) несёт
 * has-global-padding, а у ядра WP есть правило `.has-global-padding >
 * .alignfull{margin-left/right: calc(root-padding * -1)}` специфичностью
 * (0,2,0), выше одиночного класса `.faq` (0,1,0) — оно обнуляло
 * margin-left:-50vw, но НЕ трогало left:50%, поэтому секция съезжала на
 * половину экрана вправо, а не просто переставала быть full-bleed. Фикс —
 * тот же: обёртка `.faq-wrap` БЕЗ "layout":{"type":"constrained"} — такая
 * группа не получает has-global-padding, `.faq` внутри неё больше не
 * прямой потомок has-global-padding контейнера.
 *
 * Правка 2026-09-09 (round 3, по фидбеку пользователя):
 * 1) Фоновая фотография заменена с плейсхолдера faq-bg.png на реальный
 *    ассет Rectangle-100265.png (загружен в медиатеку, 1200×1200 — точно
 *    совпадает с размером ноды 1:316 в Figma).
 * 2) Список вопросов растягивался почти на всю ширину `.faq__content`
 *    (~1040px) — по raw-метаданным (homepage-desktop-metadata.xml, узлы
 *    "Group 1000010628" и соседние) каждая карточка вопроса шириной
 *    550px, все x=40 (одна колонка слева, а не на всю ширину) — справа от
 *    списка остаётся открытый участок с фоновым фото, как на макете.
 *    Ограничение ширины — в .faq__list (theme.css), на mobile снимается
 *    (список = 100% ширины, там колонка одна и без свободного места).
 *
 * Правка 2026-09-09 (round 5, "список слева, а не по центру" — round 4 не
 * помог): проверено фактическим рендером блока (`wp eval`), не на глаз —
 * "layout":{"type":"constrained"} у самой .faq не давало WP-классы
 * is-layout-constrained/has-global-padding ей самой, они уезжали на
 * .faq__content (обычный div, не отдельный блок) — подробности в
 * комментарии у .faq__content > .faq__list в theme.css. Убрано отсюда —
 * ".faq" ниже теперь без "layout", вся раскладка и так на ручных CSS.
 *
 * Правка 2026-09-10 (mobile, по фидбеку пользователя со скрином Figma):
 * текст вопроса обёрнут в `.faq__question-text` — на mobile ему нужен
 * собственный max-width (перенос ровно в 2 строки, как в макете), на
 * desktop ничего не меняется: раньше это был анонимный flex-элемент
 * summary, теперь именованный с теми же авто-размерами. Все mobile-
 * отличия секции — в media query theme.css, desktop не тронут.
 *
 * Правка 2026-09-10 (round 2, фон на mobile): у mobile-макета СВОЙ кадр
 * фотографии, а не тот же квадрат 1200×1200, что на desktop — пользователь
 * дал отдельный ассет Rectangle-100265-1.png (360×988 — ровно размеры
 * mobile-ноды 1:3777, рука в кадре от левого края до правого). Первый
 * подход (растянуть desktop-квадрат на 164vw и прижать влево) кадр макета
 * не воспроизводил — заменён на <picture> с <source media="(max-width:
 * 781px)">: браузер сам берёт нужный файл, оба остаются одним <img> и
 * одним набором CSS-правил, никакого дублирования разметки.
 */

$whoopstore_faq = [
	'Гарантия на Whoop 5.0 (One, Peak, Life)',
	'Как оплатить подписку Whoop 5.0',
	'Как продлить подписку Whoop 5.0',
	'Как настроить Whoop 5.0',
	'Где купить Whoop 5.0 (One, Peak, Life)',
	'Доставка Whoop 5.0 по Москве и России',
	'Адрес нашего пункта самовывоза',
	'Как купить Whoop 5.0 оптом на юр.лицо',
	'Можно вернуть товар или оплату за заказ',
	'Сотрудничество со спортивными командами',
];
?>
<!-- wp:group {"className":"faq-wrap"} -->
<div class="wp-block-group faq-wrap">

	<!-- wp:group {"className":"faq","align":"full"} -->
	<div class="wp-block-group alignfull faq">

		<div class="faq__media" aria-hidden="true">
			<picture class="faq__media-picture">
				<source media="(max-width: 781px)" srcset="/wp-content/uploads/2026/09/Rectangle-100265-1.webp" width="360" height="988" />
				<img class="faq__media-photo" src="/wp-content/uploads/2026/09/Rectangle-100265-2x.webp" alt="" width="1200" height="1200" loading="lazy" />
			</picture>
			<div class="faq__media-scrim"></div>
		</div>

		<div class="faq__content">
			<!-- Аудит 2026-09-07 (единая типографика): было has-xl-font-size (35px)
			— по raw-дампу Figma (page-subscriptions-desktop, node 1:3345) это
			section-heading (32px), слева. -->
			<!-- wp:heading {"level":2,"className":"faq__title section-heading"} -->
			<h2 class="wp-block-heading faq__title section-heading">Частые вопросы</h2>
			<!-- /wp:heading -->

			<!-- wp:html -->
			<div class="faq__list">
				<?php foreach ( $whoopstore_faq as $question ) : ?>
					<details class="faq__item">
						<summary class="faq__question">
							<span class="faq__question-text"><?php echo esc_html( $question ); ?></span>
							<svg class="faq__chevron" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><line x1="0" y1="8" x2="16" y2="8" stroke="currentColor" stroke-width="0.76"/><line x1="8" y1="0" x2="8" y2="16" stroke="currentColor" stroke-width="0.76"/></svg>
						</summary>
						<p class="faq__answer section-subtext">Информация уточняется.</p>
					</details>
				<?php endforeach; ?>
			</div>
			<!-- /wp:html -->
		</div>

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
