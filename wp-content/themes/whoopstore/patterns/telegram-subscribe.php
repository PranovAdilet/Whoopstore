<?php
/**
 * Title: Telegram subscribe
 * Slug: whoopstore/telegram-subscribe
 *
 * Первый проход по всей Главной (2026-09-07), источник — Figma MCP get_metadata
 * (node 1:3464, "Мобилка") — см. accessories-grid.php. Последняя секция
 * перед Footer: заголовок + текст + CTA "Подписаться" (все подтверждены).
 *
 * QA раунд 2 (2026-09-07): мокап телефона был пустой заглушкой — найден и
 * скачан реальный ассет (get_design_context, node 1:3855, homepage-mobile
 * raw-дамп) — тот же составной Silver+Screen приём, что и в no-display.php
 * (Silver — рамка iPhone 14 Pro, размер файла совпал байт-в-байт с
 * no-display-silver — это один и тот же переиспользуемый ассет в Figma,
 * а не совпадение). Screen — собственный скриншот именно для этой секции.
 *
 * Аудит 2026-09-07 (round 5, по просьбе пользователя): обёрнуто в
 * .section-canvas (1200px, свой фон/паддинг не несёт — резерв под
 * будущий фон на всю секцию) — сама карточка .telegram-subscribe (1120px,
 * центрирована, см. theme.css) внутри него, её ширина/фон не изменились.
 *
 * Аудит 2026-09-09 (визуальная сверка со скриншотом пользователя): в Figma
 * (raw-дамп homepage-desktop-metadata.xml, узлы 1:396-1:408) у секции нет
 * карточки-фона — background/border-radius/padding убраны из
 * .telegram-subscribe в theme.css (сам .php не менялся, разметка уже была
 * "просто div" без своих стилей). Визуальный порядок телефон/текст на
 * desktop (телефон слева, а не справа) сделан через flex order на
 * .telegram-subscribe__media, а не перестановкой этих двух div — DOM-порядок
 * (текст первым) специально оставлен как есть, т.к. на mobile Figma-порядок
 * обратный (текст сверху, телефон снизу, см. комментарий в theme.css).
 *
 * Аудит 2026-09-09 (round 2, второй скриншот пользователя — "затемнение
 * снизу картинки"): заголовок обёрнут в внутренний <span> — та же причина
 * и тот же приём, что у .advantages__title-text (нельзя вешать max-width
 * на сам <h2>, ядро WP центрирует потомка через margin:auto !important) —
 * ширина 323px взята из raw-дампа Figma (node 1:403), даёт тот же перенос
 * "Подпишитесь на нас" / "в Телеграм!", что на скрине дизайна. Затемнение
 * низа фото телефона — .telegram-subscribe__media::after в theme.css, тот
 * же градиент-приём, что у .advantages__phone::after.
 *
 * Аудит 2026-09-09 (round 4, пользователь дал прямую ссылку на реальный
 * ассет): составной мокап Silver+Screen (два <img>, разные исходники,
 * несовпадающая пропорция — см. историю выше) заменён на один готовый
 * PNG с уже вклеенным скриншотом — Frame-6631.png, 243×406, тот же файл
 * (по имени и байт-размеру), что подтверждён в raw-дампе Figma как узел
 * "Frame 6631" (1:396, x=283 y=12591 width=243 height=406) — то есть это
 * не приближение, а прямой экспорт нужного узла. Уже был в Медиатеке
 * (wp-content/uploads/2026/09/), просто раньше не использовался этой
 * секцией. Классы __media-silver/__media-screen (инсеты под составной
 * мокап) удалены из theme.css — больше не нужны с одним слоем.
 *
 * Аудит 2026-09-10 (round 14, скрины пользователя Figma-мобилка vs стейдж):
 * правки ТОЛЬКО в mobile-ветке theme.css (@media max-width:781px), desktop
 * не тронут — пользователь подтвердил, что на широких экранах секция верная.
 * Разметка (.php) не менялась: mobile-раскладка из Figma отличается от
 * desktop только геометрией/выравниванием, а DOM-порядок (текст → телефон)
 * уже совпадает с mobile-макетом. Разбор узлов и причина главного дефекта
 * (flex-basis 420px, ставший на mobile ВЫСОТОЙ текстовой колонки и давший
 * огромный пустой разрыв до телефона) — в комментарии к правилам в
 * theme.css, "Round 14".
 */
?>
<!-- wp:group {"className":"section-canvas","layout":{"type":"constrained"}} -->
<div class="wp-block-group section-canvas">

	<!-- wp:group {"className":"telegram-subscribe","layout":{"type":"constrained"}} -->
	<div class="wp-block-group telegram-subscribe">

		<div class="telegram-subscribe__content">
			<!-- Аудит 2026-09-07 (единая типографика): было has-xl-font-size (35px)
			— по raw-дампу Figma (node 1:2556/1:3850) это section-heading
			(32px), слева. -->
			<!-- wp:heading {"level":2,"className":"telegram-subscribe__title section-heading"} -->
			<h2 class="wp-block-heading telegram-subscribe__title section-heading"><span class="telegram-subscribe__title-text">Подпишитесь на нас в Телеграм!</span></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"telegram-subscribe__text section-subtext"} -->
			<p class="telegram-subscribe__text section-subtext">Крупнейшее сообщество пользователей Whoop в России! В канале мы рассказываем о здоровье, анализируем показатели Whoop и делимся новостями</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Подписаться</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>

		<div class="telegram-subscribe__media" aria-hidden="true">
			<img class="telegram-subscribe__media-img" src="/wp-content/uploads/2026/09/Frame-6631.webp" width="243" height="406" alt="" loading="lazy" />
		</div>

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
