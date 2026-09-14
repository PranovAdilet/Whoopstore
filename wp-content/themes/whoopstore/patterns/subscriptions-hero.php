<?php
/**
 * Title: Подписки — hero
 * Slug: whoopstore/subscriptions-hero
 *
 * Hero страницы "Подписки": фото на всю ширину холста, поверх него слева —
 * тёмная карточка с крошкой, H1, подзаголовком и двумя кнопками.
 * Источник — Figma, desktop node `1:3018` (сырой дамп
 * docs/figma-design/raw/page-subscriptions-desktop.code.tsx.txt),
 * mobile node `1:3995`.
 *
 * Подтверждённая геометрия desktop:
 *   - фото (node 1:3064) 1200×665 от y=0, т.е. проходит и за шапкой сайта —
 *     ровно тот же приём, что на Главной (см. .home-hero-wrap);
 *   - вертикальные градиенты-затемнения: 146px сверху (1:3066) и 246px
 *     снизу (1:3065). Горизонтального затемнения слева, в отличие от
 *     Главной, здесь НЕТ — текст лежит на своей непрозрачной карточке;
 *   - карточка (1:3067) 560×517 @ x=40, y=80, rounded-20, фон `#0f1217`
 *     (1:3068) + мягкий зелёный блик — размытый эллипс `#02F1A0` 70%
 *     (1:3069). Это тот же самый узел, что и на Главной, поэтому карточка
 *     переиспользует .hero-panel__content-left без изменений;
 *   - внутри карточки: крошка y=40, H1 y=85 (32px, акцент "для себя" —
 *     SemiBold в той же строке), подзаголовок y=170 (14px, ширина 390),
 *     кнопки y=357 ("Подключить" — Primary, "Написать нам" — Dark).
 *
 * Что переиспользовано без изменений: обёртка .home-hero-wrap (фото за
 * шапкой), .hero-panel / .hero-panel__content / .hero-panel__content-left /
 * .hero-panel__text / .hero-panel__actions, кнопки, и шапка контента
 * (крошки + H1) — общая функция whoopstore_page_head(), та же, что на
 * страницах ремешков.
 *
 * Отличия от Главной вынесены в модификаторы, а не в копии правил:
 *   .home-hero-wrap--subscriptions — своя фотография и другой скрим;
 *   .hero-panel--subscriptions     — нет фото устройства справа, карточка
 *                                    чуть выше (517 против 490);
 *   .page-head--in-hero            — у шапки контента нет собственных
 *                                    страничных отступов, она лежит внутри
 *                                    карточки.
 *
 * NB: класс обёртки называется .home-hero-wrap, хотя используется уже не
 * только на Главной — переименование затронуло бы ~30 мест в theme.css и
 * шаблон Главной без визуальной пользы, поэтому оставлено как есть
 * осознанно; читать как "hero-обёртка", а не "только для главной".
 */
?>
<!-- wp:html -->
<div class="home-hero-wrap__media" aria-hidden="true"></div>
<div class="home-hero-wrap__scrim" aria-hidden="true"></div>
<!-- /wp:html -->

<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:html -->
<div class="hero-panel hero-panel--subscriptions">
	<div class="hero-panel__content">
		<div class="hero-panel__content-left">
			<div>
				<?php
				whoopstore_page_head(
					[
						'crumb'  => 'Подписки Whoop',
						'title'  => 'Подписки WHOOP: выберите оптимальный план',
						'accent' => 'для себя',
						'inline' => true,
						'class'  => 'page-head--in-hero',
					]
				);
				?>
				<p class="hero-panel__text section-subtext">Здесь вы найдете все, что нужно знать об планах подписки, их отличиях, цена в рублях, пробном периоде и часто задаваемых вопросах</p>
			</div>

			<div class="wp-block-buttons hero-panel__actions">
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#buy">Подключить</a></div>
				<div class="wp-block-button is-style-dark"><a class="wp-block-button__link wp-element-button" href="#contacts">Написать нам</a></div>
			</div>
		</div>
	</div>
</div>
<!-- /wp:html -->
