<?php
/**
 * Title: Help block
 * Slug: whoopstore/help-block
 *
 * По Figma заголовок/CTA лежат на фоне страницы, а 3 пункта — отдельные
 * приподнятые карточки в ряд (не общий блок с колонками внутри одной
 * подложки, как было раньше). У каждого пункта есть иконка над текстом,
 * положенная прямо на фон карточки — по raw-коду Figma
 * (docs/figma-design/raw/homepage-desktop.partial-1.code.tsx.txt:514-519)
 * под иконками НЕТ отдельного фона/бейджа, это просто img 30×30 (иконки 1-2)
 * / ~18×24 (иконка 3, без бейджа изначально).
 *
 * Аудит 2026-09-07 (docs/homepage-audit.md, High): иконки 2 и 3 были
 * плейсхолдером — реальные ассеты найдены в docs/figma-design/raw
 * (get_design_context, node 1:3624 "Strap_Vibration" / 1:3628 "Group
 * 1000010493"), скачаны, пока ссылки Figma не истекли (~7 дней), и
 * добавлены: иконка 2 — в Медиатеку (PNG), иконка 3 — SVG-загрузка в
 * Медиатеку заблокирована WP по умолчанию для этого типа файлов, поэтому
 * она лежит в теме (assets/icons/help-renew.svg).
 *
 * Правка 2026-09-08 (сверка со скриншотом стейджа vs Figma): иконка 1
 * ("Поможем с переходом на WHOOP 5.0") ошибочно использовала
 * assets/icons/transition.svg — тот же вектор ("Group 1000010493"), что и
 * иконка 3, просто отмасштабированный (22×30 вместо 17.6×24) — визуально
 * иконки 1 и 3 были одинаковыми, хотя в Figma это разные узлы. Настоящая
 * иконка 1 — узел "Wireless_Battery_Pack__1_ 1" (старый fileKey
 * MAGGfC2v8q7afPFdxBZWpK, node 1:3335), скачана через
 * mcp__figma__download_assets и загружена в Медиатеку как help-icon-1.png.
 * Файл assets/icons/transition.svg удалён как неверный/неиспользуемый.
 * Заодно убран самопальный CSS-бейдж (.help-block__icon 40×40 с фоном и
 * рамкой) — в исходном коде под иконками нет отдельного div-фона вообще,
 * бейдж был добавлен без опоры на Figma; и добавлен max-width на заголовок
 * секции (node 1:184, w="514px"), чтобы он переносился на 2 строки, как в
 * макете, а не растягивался в одну строку на всю ширину ряда.
 *
 * Правка 2026-09-08 (2) — по прямому запросу пользователя перенос строк
 * зафиксирован жёстко через <br>, а не только через max-width/ширину
 * колонки (которая при других брейкпоинтах/правках CSS может не совпасть
 * и перенос "уедет"):
 *   - заголовок — как в самом raw-коде Figma (node 1:184: `<br
 *     aria-hidden />и продление подписки`), у него и так был жёсткий
 *     перенос, а не просто перенос по ширине;
 *   - 3 текста карточек — в Figma это перенос по ширине (w="222px", без
 *     <br>), но пользователь явно попросил всегда переносить в этих же
 *     местах, поэтому здесь это осознанное отступление от буквального
 *     raw-кода в пользу явного пользовательского требования.
 */
?>
<!-- wp:group {"className":"help-block","layout":{"type":"constrained"}} -->
<div class="wp-block-group help-block">

	<!-- wp:group {"className":"help-block__head","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group help-block__head">
		<!-- Аудит 2026-09-07 (единая типографика): было has-lg-font-size (22px)
		— по raw-дампу Figma (node 1:184) это section-heading (32px), с
		"Whoop 5.0" полужирным внутри обычной строки. -->
		<!-- wp:heading {"className":"section-heading help-block__title"} -->
		<h2 class="wp-block-heading section-heading help-block__title">Подключение <strong>Whoop 5.0</strong><br />и продление подписки</h2>
		<!-- /wp:heading -->

		<!-- Аудит 2026-09-07 (единая система кнопок): раньше здесь ошибочно был
		is-style-outline ("Написать нам" визуально похоже на hero) — сверка с
		docs/figma-design/raw/homepage-desktop.partial-1.code.tsx.txt:509-511
		(bg-[#02f1a0]) подтвердила, что эта кнопка Primary, как и "Купить" —
		убран лишний класс. -->
		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#contacts">Написать нам</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"className":"help-block__columns"} -->
	<div class="wp-block-columns help-block__columns">
		<!-- wp:column -->
		<div class="wp-block-column help-block__item card">
			<!-- wp:html --><div class="help-block__icon"><img src="/wp-content/uploads/2026/09/help-icon-1.webp" alt="" width="30" height="30" loading="lazy" /></div><!-- /wp:html -->
			<!-- wp:paragraph {"className":"help-block__item-text text-label"} -->
			<p class="help-block__item-text text-label">Поможем с переходом<br />на WHOOP 5.0</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column help-block__item card">
			<!-- wp:html --><div class="help-block__icon"><img src="/wp-content/uploads/2026/09/help-icon-2.webp" alt="" width="30" height="30" loading="lazy" /></div><!-- /wp:html -->
			<!-- wp:paragraph {"className":"help-block__item-text text-label"} -->
			<p class="help-block__item-text text-label">Подключим и<br />настроим WHOOP 5.0</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column help-block__item card">
			<!-- wp:html --><div class="help-block__icon"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/icons/help-renew.svg' ) ); ?>" alt="" width="18" height="24" loading="lazy" /></div><!-- /wp:html -->
			<!-- wp:paragraph {"className":"help-block__item-text text-label"} -->
			<p class="help-block__item-text text-label">Продлим подписку<br />WHOOP 5.0</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
