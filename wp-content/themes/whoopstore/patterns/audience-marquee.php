<?php
/**
 * Title: Audience marquee
 * Slug: whoopstore/audience-marquee
 *
 * Первый проход по всей Главной (2026-09-07), источник — Figma MCP get_metadata
 * (node 1:3464, "Мобилка") — см. accessories-grid.php.
 *
 * "Кому подойдет фитнес-браслет WHOOP?" — список подтверждённых текстовых
 * тегов. В Figma-метаданных этот ряд буквально продублирован двумя
 * group-фреймами почти одинаковой ширины со сдвигом по X — это стандартный
 * паттерн бесконечной scroll-ленты (marquee), а не случайное повторение
 * контента. Реализовано CSS-анимацией с задублированным списком тегов
 * (обычный приём для бесшовной прокрутки).
 *
 * Аудит 2026-09-07 (High): подтверждено get_design_context — на Desktop
 * список из 8 фраз (добавляются "Любителям фитнеса" и "Всем, кто хочет
 * лучше понимать состояние своего организма" сверх 6 общих с mobile),
 * на Mobile — те же 6, что были в первом проходе (сокращение подтверждено
 * как намеренное, не ошибка выгрузки). Держим все 8 в разметке на обоих
 * брейкпоинтах и скрываем 2 лишних на mobile через CSS
 * (.audience__tag--desktop-only), а не дублируем весь список кода под
 * каждый брейкпоинт отдельно.
 *
 * Визуальный аудит 2026-09-09 (сверка со скринами Figma/стейдж от пользователя,
 * перепроверено в raw-дампе): предыдущий проход принял два group-фрейма за
 * анимационные "фазы" одной ленты и слил их в один ряд — на деле оба
 * брейкпоинта (desktop: `1:257…1:268` @y=8172 и `1:271…1:280` @y=8228;
 * mobile: `Group 1000010782` @y=11432.998 и `Group 1000010783` @y=11478.998)
 * показывают ДВА физически разных, вертикально смещённых ряда с одинаковым
 * набором тегов — это и есть "две наплывающие друг на друга строки" с
 * присланного скрина стейджа. Теперь рендерим два независимых
 * `.audience__track` (одна и та же функция-рендерер ряда, без copy-paste
 * разметки), второй ряд смещён по фазе анимации (`animation-delay`), а не
 * зеркальным направлением — направление скролла второго ряда в Figma не
 * подтверждено (статичный макет), это самый безопасный вариант.
 *
 * Аудит 2026-09-09 (round 2, баг "весь контент ушёл вправо" после попытки
 * full-bleed): `.audience` — is-layout-constrained (Gutenberg), а у ядра WP
 * есть правило `.is-layout-constrained > :where(...):not(.alignfull){
 * max-width:1120px; margin-left/right:auto !important}` для ПРЯМЫХ детей
 * без класса alignfull. `.audience__marquee` был прямым ребёнком .audience
 * и не имел alignfull — WP насильно (через !important) обнулял мой
 * margin-left:-50vw и обрезал width:100vw до 1120px, а мой же left:50%
 * поверх этого продолжал сдвигать бокс вправо — отсюда обрезанный наполовину
 * контент. Тот же приём, что уже спас .straps-carousel__stage/__nav в
 * straps-personalize.php: full-bleed элемент вложен НЕ прямым ребёнком
 * is-layout-constrained контейнера, а на уровень глубже — добавлена
 * нейтральная обёртка `.audience__marquee-wrap` (сама попадает под правило
 * WP и получает max-width:1120px/auto-margины, но это ей и не мешает — ей
 * не нужен full-bleed, только .audience__marquee внутри неё).
 */

/**
 * Рендерит один ряд бегущей строки — список тегов, задублированный x2 для
 * бесшовной прокрутки. Замыкание, а не top-level function — чтобы не
 * рисковать "Cannot redeclare" при повторном include файла паттерна (см.
 * кэш-нюанс паттернов в docs/figma-homepage-notes.md); остальные паттерны
 * темы тоже не объявляют глобальных функций.
 */
$whoopstore_render_audience_track = function ( array $tags, string $extra_class = '' ) {
	?>
	<div class="audience__track<?php echo $extra_class ? ' ' . esc_attr( $extra_class ) : ''; ?>">
		<?php for ( $i = 0; $i < 2; $i++ ) : ?>
			<?php foreach ( $tags as $tag ) : ?>
				<span class="audience__tag<?php echo ! empty( $tag['desktop_only'] ) ? ' audience__tag--desktop-only' : ''; ?>"><?php echo esc_html( $tag['text'] ); ?></span>
			<?php endforeach; ?>
		<?php endfor; ?>
	</div>
	<?php
};

$whoopstore_audience = [
	[ 'text' => 'Бегунам' ],
	[ 'text' => 'Велосипедистам' ],
	[ 'text' => 'Пловцам' ],
	[ 'text' => 'Людям, которые хотят улучшить сон' ],
	[ 'text' => 'Руководителям и предпринимателям' ],
	[ 'text' => 'Врачам и специалистам, работающим посменно' ],
	[ 'text' => 'Любителям фитнеса', 'desktop_only' => true ],
	[ 'text' => 'Всем, кто хочет лучше понимать состояние своего организма', 'desktop_only' => true ],
];
?>
<!-- wp:group {"className":"audience","layout":{"type":"constrained"}} -->
<div class="wp-block-group audience">

	<!-- Аудит 2026-09-07 (единая типографика): было has-xl-font-size (35px)
	слева — по raw-дампу Figma (node 1:254) это section-heading (32px) по
	центру; подзаголовок ниже (node 1:255) — тоже по центру. -->
	<!-- wp:heading {"level":2,"className":"audience__title section-heading section-heading--center"} -->
	<h2 class="wp-block-heading audience__title section-heading section-heading--center">Кому подойдет фитнес-браслет WHOOP?</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"audience__subtitle section-subtext"} -->
	<p class="audience__subtitle section-subtext">Фитнес-трекер подойдет:</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<div class="audience__marquee-wrap">
		<div class="audience__marquee" aria-hidden="false">
			<?php $whoopstore_render_audience_track( $whoopstore_audience ); ?>
			<?php $whoopstore_render_audience_track( $whoopstore_audience, 'audience__track--row2' ); ?>
		</div>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
