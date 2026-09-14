<?php
/**
 * Title: Personalize straps
 * Slug: whoopstore/straps-personalize
 *
 * Первый проход по всей Главной (2026-09-07), источник — Figma MCP get_metadata
 * (node 1:3464, "Мобилка") — см. accessories-grid.php.
 *
 * Правка 2026-09-09 (визуальная сверка со скриншотом пользователя, Figma vs
 * stage): секция была простым вертикальным стеком "фото сверху → текст снизу"
 * внутри серой карточки — в Figma (raw-дамп homepage-desktop.partial-2.code.tsx.txt,
 * node 1:448/1:449/1:432/1:452/1:441, + homepage-mobile.code.tsx.txt узлы
 * 1:3648-1:3675) это оверлей без фона карточки:
 *  - заголовок + описание — слева сверху, обычным потоком;
 *  - лента из 7 фото — на всю ширину карточки с уходом за края (bleed) и
 *    fade-затуханием по краям вместо видимого скроллбара;
 *  - бейдж "CoreKnit Color: Royal Blue" (1:449/1:451) — плавающая тёмная
 *    карточка 151×70 (144×68 на mobile), лежит НАД лентой фото, а не текстом
 *    под описанием;
 *  - переключатель Whoop 5.0/MG (1:452) — НАД нижним краем ленты, по центру;
 *  - стрелки-навигация (1:441) — сверху справа на desktop, сверху слева на
 *    mobile (подтверждено обоими дампами, не опечатка) — чисто декоративные,
 *    как и переключатель (нет реальных данных под MG на этом этапе, см.
 *    комментарий у .toggle в theme.css).
 * Порядок кнопок переключателя тоже был перепутан: подтверждённый порядок на
 * desktop — "Whoop MG" (неактивная, слева) → "Whoop 5.0" (активная, справа) —
 * тот же порядок, что уже был верно сделан в соседней компактной секции
 * (straps-promo.php). Новая разметка — .straps-carousel--personalize в
 * theme.css.
 */

$whoopstore_strap_photos = [
	'/wp-content/uploads/2026/09/strap-1.webp',
	'/wp-content/uploads/2026/09/strap-2.webp',
	'/wp-content/uploads/2026/09/strap-3.webp',
	'/wp-content/uploads/2026/09/strap-4.webp',
	'/wp-content/uploads/2026/09/strap-5.webp',
	'/wp-content/uploads/2026/09/strap-6.webp',
	'/wp-content/uploads/2026/09/strap-7.webp',
];
?>
<!-- wp:group {"className":"section-canvas","layout":{"type":"constrained"}} -->
<div class="wp-block-group section-canvas">

	<!-- wp:group {"className":"straps-carousel straps-carousel--personalize"} -->
	<div class="wp-block-group straps-carousel straps-carousel--personalize">

		<div class="straps-carousel__header">
			<!-- wp:heading {"level":2,"className":"straps-carousel__title section-heading"} -->
			<h2 class="wp-block-heading straps-carousel__title section-heading">Персонализируйте свой WHOOP</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"straps-carousel__text section-subtext"} -->
			<p class="straps-carousel__text section-subtext">Подберите ремешок под тренировку, работу, путешествие или повседневную жизнь. Более 50 цветов, материалов и дизайнов для WHOOP 5.0 и WHOOP MG</p>
			<!-- /wp:paragraph -->
		</div>

		<!-- wp:html -->
		<div class="straps-carousel__stage">

			<div class="straps-carousel__nav" aria-hidden="true">
				<span class="straps-carousel__arrow straps-carousel__arrow--prev"></span>
				<span class="straps-carousel__arrow straps-carousel__arrow--next"></span>
			</div>

			<div class="straps-carousel__media">
				<?php foreach ( $whoopstore_strap_photos as $photo ) : ?>
					<img class="straps-carousel__photo" src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy" />
				<?php endforeach; ?>
			</div>

			<p class="straps-carousel__badge">
				<span class="straps-carousel__badge-line straps-carousel__badge-line--strong">CoreKnit</span>
				<span class="straps-carousel__badge-line">Color: <strong>Royal Blue</strong></span>
			</p>

			<nav class="toggle straps-carousel__filters" aria-label="Каталоги ремешков">
				<?php /* Правка 2026-09-11 (наладка переходов): как и в секции
				        "Ремешки для WHOOP 5.0 / MG" выше, вкладки стали ссылками
				        на свёрстанные каталоги. По Figma это фильтр ленты фото,
				        но фильтрации на этом этапе нет (нет наборов данных под
				        модель), и оставлять на Главной второй внешне такой же
				        переключатель, который ни на что не реагирует, — хуже,
				        чем увести в каталог нужной модели. */ ?>
				<a class="toggle__option" href="/remeshki-whoop-mg/">Whoop MG</a>
				<a class="toggle__option is-active" href="/remeshki-whoop-5-0/">Whoop 5.0</a>
			</nav>

		</div>
		<!-- /wp:html -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
