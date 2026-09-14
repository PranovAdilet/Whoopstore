<?php
/**
 * Title: Analytics banner
 * Slug: whoopstore/analytics-banner
 *
 * Первый проход по всей Главной (2026-09-07), источник — Figma MCP get_metadata
 * (node 1:3464, "Мобилка") — см. accessories-grid.php.
 *
 * Аудит 2026-09-07 (docs/homepage-audit.md, Critical): подтверждено
 * get_design_context (supplementary блок 1, node 1:502) — это СВЕТЛАЯ
 * карточка (bg-white), редкий случай на тёмной странице, а не обычная
 * .card. Использует .card--light. Заголовок/текст — тёмные. Кнопка —
 * Primary (bg-accent), не outline (outline — паттерн только для тёмного
 * фона, на белом читался бы плохо). Фото устройства — реальный ассет,
 * скачан и загружен в Медиатеку, пока ссылка Figma не истекла (~7 дней).
 *
 * Аудит 2026-09-07 (round 5, по просьбе пользователя): обёрнуто в
 * .section-canvas (1200px, свой фон/паддинг не несёт — резерв под
 * будущий фон на всю секцию) — сама карточка .analytics-banner
 * (1120×442, см. theme.css) внутри него, её ширина/фон не изменились.
 *
 * Визуальный аудит 2026-09-09 (сверка со скриншотом пользователя + сырым
 * кодом Figma, node 1:502 / mobile 1:3941-3944): кнопка "Подробнее о
 * WHOOP" в Figma НЕ часть светлой карточки — это отдельный элемент под
 * карточкой, на тёмном фоне страницы (desktop: карточка кончается на
 * y=6605, кнопка начинается на y=6635 — зазор 30px; mobile: карточка до
 * y=9538, кнопка с y=9570 — зазор 32px), центрированный по всей ширине
 * секции, а не по колонке с текстом. Вынесено из .analytics-banner__content
 * в отдельный блок-сосед карточки (тот же приём, что уже физически было
 * в mobile-геометрии, просто разметка этого не отражала).
 *
 * Правка 2026-09-10 (mobile-раунд «по секциям», сверка стейджа с
 * Figma-мобилкой): разметка НЕ менялась — все расхождения оказались
 * чисто раскладочными и закрыты в mobile-медиазапросе theme.css
 * (см. комментарий у .analytics-banner в разделе Responsive):
 * порядок «заголовок → подзаголовок → фото → абзац» (на desktop фото
 * слева, отдельной колонкой), выравнивание по левому краю, отсутствие
 * серой плашки под нижним абзацем, padding 30px и радиус 10px.
 * Порядок реализован через display:contents на
 * .analytics-banner__content + order — именно чтобы не заводить вторую
 * (мобильную) версию разметки и не трогать desktop.
 */
?>
<!-- wp:group {"className":"section-canvas","layout":{"type":"constrained"}} -->
<div class="wp-block-group section-canvas">

	<!-- wp:group {"className":"analytics-banner card--light","layout":{"type":"constrained"}} -->
	<div class="wp-block-group analytics-banner card--light">

		<div class="analytics-banner__media" style="background-image:url('/wp-content/uploads/2026/09/analytics-banner-device.webp')" aria-hidden="true"></div>

		<div class="analytics-banner__content">
			<!-- Заголовок — card-heading (20px Medium), а не отдельное
			точечное правило: тот же стиль, что "Аксессуары"/"Активация...". -->
			<!-- wp:heading {"level":2,"className":"analytics-banner__title card-heading card-heading--light"} -->
			<h2 class="wp-block-heading analytics-banner__title card-heading card-heading--light">WHOOP непрерывно анализирует</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"analytics-banner__text section-subtext section-subtext--light"} -->
			<p class="analytics-banner__text section-subtext section-subtext--light">состояние организма 24 часа в сутки и помогает принимать решения на основе объективных данных</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"analytics-banner__note section-subtext section-subtext--light"} -->
			<p class="analytics-banner__note section-subtext section-subtext--light">Все показатели автоматически объединяются в персональные рекомендации по сну, восстановлению, тренировкам и образу жизни</p>
			<!-- /wp:paragraph -->
		</div>

	</div>
	<!-- /wp:group -->

	<!-- wp:buttons {"className":"analytics-banner__cta","layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons analytics-banner__cta">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#advantages">Подробнее о WHOOP</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
