<?php
/**
 * Title: Personalize straps
 * Slug: whoopstore/straps-personalize
 *
 * Секция «Персонализируйте свой WHOOP» (Figma: raw-дамп
 * homepage-desktop.partial-2.code.tsx.txt, node 1:448/1:449/1:432/1:452/1:441 +
 * homepage-mobile.code.tsx.txt, 1:3648-1:3675) — оверлей без фона карточки:
 *  - заголовок и описание слева сверху, обычным потоком;
 *  - лента из 7 фото на всю ширину с уходом за края и fade по краям;
 *  - плашка с названием ремешка (1:449/1:451) — над лентой;
 *  - переключатель Whoop 5.0/MG (1:452) — над нижним краем ленты;
 *  - стрелки (1:441) — сверху справа на desktop, сверху слева на mobile.
 *
 * Правка 2026-09-16 (ТЗ: «ремешки листаются свайпом и кнопками; название
 * выбранного ремешка — по центру»): лента стала интерактивной.
 *  - Фото остаются кадрами из Figma (портретная фактура 873×1308 под слот
 *    246×369). Фото товаров — квадратные предметные кадры на прозрачном фоне,
 *    в этих слотах они обрезались бы и меняли вид секции; решение заказчика
 *    2026-09-16 — кадры макета оставить, данные брать из товаров.
 *  - Каждый кадр — ссылка на свой товар; соответствие «кадр → товар» идёт по
 *    порядку категории (CoreKnit 5.0, затем ремешки на бицепс — ровно 7
 *    товаров на 7 кадров). Это соответствие условное: кадры макета
 *    обезличены, конкретный цвет на фото с товаром не сверялся.
 *  - Плашка показывает семейство и цвет выбранного ремешка
 *    (whoopstore_strap_badge_parts()), выбранным считается ближайший к центру
 *    ленты — см. assets/js/straps-carousel.js.
 * Свайп и прокрутка колесом остаются нативными (overflow-x у ленты).
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

// Товары ленты: влагостойкие CoreKnit 5.0, затем ремешки на бицепс — то же
// семейство CoreKnit, что и в плашке макета.
$whoopstore_strap_products = array_merge(
	whoopstore_category_products( 'coreknit-5-0' ),
	whoopstore_category_products( 'coreknit-bicep' )
);

// Плашка при загрузке — первый ремешок ленты; дальше её ведёт скрипт.
list( $whoopstore_badge_family, $whoopstore_badge_color ) = $whoopstore_strap_products
	? whoopstore_strap_badge_parts( $whoopstore_strap_products[0]->get_name() )
	: [ '', '' ];
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

			<div class="straps-carousel__nav">
				<?php /* Класс .carousel-arrow — общий стиль стрелки (он же у ленты
				        «Вам может понравиться»), включая приглушение на краю ленты. */ ?>
				<button type="button" class="carousel-arrow carousel-arrow--prev straps-carousel__arrow straps-carousel__arrow--prev" aria-label="Предыдущий ремешок"></button>
				<button type="button" class="carousel-arrow carousel-arrow--next straps-carousel__arrow straps-carousel__arrow--next" aria-label="Следующий ремешок"></button>
			</div>

			<div class="straps-carousel__media">
				<?php foreach ( $whoopstore_strap_photos as $whoopstore_index => $whoopstore_photo ) : ?>
					<?php
					$whoopstore_strap = isset( $whoopstore_strap_products[ $whoopstore_index ] ) ? $whoopstore_strap_products[ $whoopstore_index ] : null;
					list( $whoopstore_family, $whoopstore_color ) = $whoopstore_strap
						? whoopstore_strap_badge_parts( $whoopstore_strap->get_name() )
						: [ '', '' ];
					?>
					<?php if ( $whoopstore_strap ) : ?>
						<a class="straps-carousel__photo" href="<?php echo esc_url( $whoopstore_strap->get_permalink() ); ?>" aria-label="<?php echo esc_attr( $whoopstore_strap->get_name() ); ?>" data-family="<?php echo esc_attr( $whoopstore_family ); ?>" data-color="<?php echo esc_attr( $whoopstore_color ); ?>">
							<img src="<?php echo esc_url( $whoopstore_photo ); ?>" alt="" loading="lazy" />
						</a>
					<?php else : ?>
						<?php /* Кадров больше, чем товаров в категории — лишние остаются
						        просто фоном, без ссылки и без данных для плашки. */ ?>
						<img class="straps-carousel__photo" src="<?php echo esc_url( $whoopstore_photo ); ?>" alt="" loading="lazy" />
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<?php if ( '' !== $whoopstore_badge_family ) : ?>
				<p class="straps-carousel__badge">
					<span class="straps-carousel__badge-line straps-carousel__badge-line--strong"><?php echo esc_html( $whoopstore_badge_family ); ?></span>
					<span class="straps-carousel__badge-line">Color: <strong><?php echo esc_html( $whoopstore_badge_color ); ?></strong></span>
				</p>
			<?php endif; ?>

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
