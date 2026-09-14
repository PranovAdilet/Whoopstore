<?php
/**
 * Переиспользуемые карточки-компоненты темы:
 *   whoopstore_device_card() — карточка товара/услуги (.device-card);
 *   whoopstore_tile_card()   — плитка-категория с фото (.tile-card);
 *   whoopstore_catalog_section() — заголовок раздела + сетка .device-card.
 * Последние две добавлены 2026-09-10 вместе со страницами
 * "Ремешки для WHOOP 5.0" и "Ремешки для WHOOP MG".
 *
 * Аудит 2026-09-08 (по просьбе пользователя): раньше разметка карточки
 * (div.device-card.card + внутренняя структура) была скопирована в foreach
 * трёх pattern-файлов по отдельности (device-grid.php, accessories-grid.php,
 * activation-grid.php) — CSS-класс был общим, а вот генерация HTML — нет,
 * из-за чего 3 копии рисковали незаметно разойтись (в одной есть проверка
 * скидочного бейджа, в другой — нет, и т.п.).
 *
 * В WordPress (в отличие от React) нет компонентов с пропсами в блочном
 * редакторе для файловых patterns — Synced Patterns/Reusable Blocks
 * хранятся в БД (wp_block CPT) и редактируются через UI, что не подходит
 * для контента, который живёт как PHP-массивы в файлах темы; свой блок
 * (register_block_type) был бы избыточен — эти карточки не редактируются
 * в блочном редакторе, только в коде (см. правило 15 проекта — не вводить
 * новую сущность без необходимости). Прямой WP-эквивалент "компонента с
 * пропсами" в этом случае — обычная PHP-функция, принимающая данные и
 * печатающая разметку: один источник HTML для всех секций, которые
 * используют такую карточку (сейчас 3, в будущем — другие).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Печатает одну карточку .device-card.
 *
 * @param array $item {
 *     @type string      $name        Название (обязательно).
 *     @type string|null $price       Цена, напр. "8 990,00 ₽". Если пусто —
 *                                     блок цены не выводится (см. промо-карточку
 *                                     ремешков без цены в accessories-grid).
 *     @type string|null $price_html  Готовая разметка цены от WooCommerce
 *                                     ($product->get_price_html()) — для карточек
 *                                     реальных товаров, где цена может быть
 *                                     диапазоном или парой "старая/новая" и её
 *                                     формирует сам WooCommerce. Имеет приоритет
 *                                     над price/old_price; проходит через
 *                                     wp_kses_post.
 *     @type string|null $old_price   Зачёркнутая старая цена (опционально).
 *     @type string|null $badge       Текст бейджа поверх фото ("Распродажа"),
 *                                     опционально — см. .device-card__badge
 *                                     в theme.css.
 *     @type string      $image       Путь к фото устройства (опционально —
 *                                     без него остаётся декоративный паттерн-
 *                                     заглушка, см. .device-card__media в
 *                                     theme.css; с ним заглушка убирается —
 *                                     .device-card__media--has-photo).
 *                                     Прозрачный PNG-вырез, заполняется
 *                                     через object-fit:contain.
 *     @type int|null    $image_id    ID вложения медиатеки — для карточек
 *                                     реальных товаров (см.
 *                                     whoopstore_product_card_data() в
 *                                     inc/woocommerce/product-card.php). Имеет
 *                                     приоритет над image: тег печатает
 *                                     wp_get_attachment_image() — со srcset,
 *                                     sizes, width/height и alt из медиатеки.
 *     @type string|null $image_size  Размер вложения для image_id, по
 *                                     умолчанию 'full'.
 *     @type string      $button_text Текст кнопки, по умолчанию "Купить".
 *     @type string      $href        Ссылка кнопки, по умолчанию "#".
 *     @type string|array|null $modifier BEM-модификатор карточки (или массив
 *                                     модификаторов), напр. 'plan'
 *                                     (карточка тарифа на странице "Подписки":
 *                                     та же карточка, но медиа-зона другая —
 *                                     мокап телефона, фото браслета и
 *                                     полупрозрачное слово-подложка) или
 *                                     [ 'plan', 'plan-compact' ] — та же
 *                                     карточка тарифа со сжатой по высоте
 *                                     медиа-сценой (секция "Активация и
 *                                     продление устройства" на Главной).
 *                                     См. .device-card--plan* в theme.css.
 *     @type string|null $phone       Путь к мокапу телефона (только для
 *                                     modifier='plan').
 *     @type string|null $watermark   Слово-подложка поверх медиа-зоны
 *                                     ("ONE"/"PEAK"/"LIFE"), только для
 *                                     modifier='plan'.
 * }
 */
function whoopstore_device_card( array $item ) {
	$href        = isset( $item['href'] ) ? $item['href'] : '#';
	$button_text = isset( $item['button_text'] ) ? $item['button_text'] : 'Купить';
	// Модификаторов может быть несколько (напр. plan + plan-compact + plan-solo
	// у карточек секции "Активация и продление устройства"), поэтому строка и
	// массив обрабатываются одинаково — каждый элемент получает префикс
	// device-card--.
	$modifiers   = ! empty( $item['modifier'] ) ? (array) $item['modifier'] : [];
	$modifier    = '';
	foreach ( $modifiers as $modifier_name ) {
		$modifier .= ' device-card--' . $modifier_name;
	}

	// Фото — либо вложение медиатеки (карточки реальных товаров: WordPress сам
	// выводит srcset/sizes, width/height и alt из медиатеки), либо готовый путь
	// (статичные карточки паттернов). Класс и loading у тега одни и те же.
	// Вложение, которого нет, даёт пустую строку — тогда остаётся заглушка.
	$photo = '';
	if ( ! empty( $item['image_id'] ) ) {
		$photo = wp_get_attachment_image(
			$item['image_id'],
			! empty( $item['image_size'] ) ? $item['image_size'] : 'full',
			false,
			[
				'class'   => 'device-card__photo',
				'loading' => 'lazy',
			]
		);
	} elseif ( ! empty( $item['image'] ) ) {
		$photo = '<img class="device-card__photo" src="' . esc_url( $item['image'] ) . '" alt="" loading="lazy" />';
	}
	?>
	<div class="device-card card<?php echo esc_attr( $modifier ); ?>">
		<div class="device-card__media<?php echo '' !== $photo ? ' device-card__media--has-photo' : ''; ?>" aria-hidden="true">
			<?php if ( ! empty( $item['watermark'] ) ) : ?>
				<span class="device-card__watermark"><?php echo esc_html( $item['watermark'] ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $item['phone'] ) ) : ?>
				<span class="device-card__phone"><img src="<?php echo esc_url( $item['phone'] ); ?>" alt="" width="157" height="321" loading="lazy" /></span>
			<?php endif; ?>
			<?php if ( '' !== $photo ) : ?>
				<?php
				// "\n" — PHP съедает перевод строки сразу после закрывающего тега,
				// а статичные карточки должны остаться в прежней разметке.
				echo $photo . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- собрано через esc_url() или wp_get_attachment_image().
				?>
			<?php endif; ?>
			<?php if ( ! empty( $item['phone'] ) ) : ?>
				<span class="device-card__fade"></span>
			<?php endif; ?>
			<?php if ( ! empty( $item['badge'] ) ) : ?>
				<span class="device-card__badge"><?php echo esc_html( $item['badge'] ); ?></span>
			<?php endif; ?>
		</div>
		<p class="device-card__title"><?php echo esc_html( $item['name'] ); ?></p>
		<?php if ( ! empty( $item['price_html'] ) ) : ?>
			<?php /* bdi добавлен к разрешённым тегам: WooCommerce оборачивает в
			        него сумму (изоляция направления текста), а в стандартном
			        списке wp_kses_post его нет — без этого тег молча вырезался
			        бы из штатной разметки цены. */ ?>
			<p class="device-card__price"><?php echo wp_kses( $item['price_html'], array_merge( wp_kses_allowed_html( 'post' ), [ 'bdi' => [ 'class' => true, 'dir' => true ] ] ) ); ?></p>
		<?php elseif ( ! empty( $item['price'] ) ) : ?>
			<p class="device-card__price">
				<?php if ( ! empty( $item['old_price'] ) ) : ?>
					<del><?php echo esc_html( $item['old_price'] ); ?></del>
				<?php endif; ?>
				<ins><?php echo esc_html( $item['price'] ); ?></ins>
			</p>
		<?php endif; ?>
		<a class="wp-block-button__link wp-element-button device-card__button" href="<?php echo esc_url( $href ); ?>"><?php echo esc_html( $button_text ); ?></a>
	</div>
	<?php
}

/**
 * Печатает одну плитку-категорию .tile-card (фото + затемнение снизу вверх +
 * подпись поверх фото).
 *
 * Отдельный компонент, а не модификатор .device-card: у них нет общей части
 * (у .device-card — фон-карточка, отдельная медиа-зона, цена и кнопка; у
 * плитки — только фото на всю площадь, градиент и текст поверх), объединять
 * их было бы искусственно (см. правило 14 CLAUDE.md — "если элементы похожи,
 * но отличаются по назначению, не объединять").
 *
 * Подтверждено в Figma (страница "Ремешки для WHOOP 5.0", desktop node
 * 1:2448…1:2465, mobile node 1:4623…1:4634): плитка 360×300 rounded-20 на
 * desktop и 155×180 rounded-10 на mobile, градиент
 * `linear-gradient(to top, rgba(15,18,23,0.8), rgba(15,18,23,0) 70%)`,
 * подпись 20px/13px Onest Regular белым. Тот же паттерн плитки встречается
 * и на странице "Ремешки для WHOOP MG" — поэтому компонент общий, а не
 * инлайн-разметка в одном паттерне.
 *
 * @param array $item {
 *     @type string $title Подпись плитки (обязательно).
 *     @type string $image Путь к фото (обязательно — плитка без фото
 *                          визуально не существует, в отличие от
 *                          .device-card с её паттерн-заглушкой).
 * }
 */
function whoopstore_tile_card( array $item ) {
	?>
	<div class="tile-card">
		<img class="tile-card__photo" src="<?php echo esc_url( $item['image'] ); ?>" alt="" loading="lazy" />
		<span class="tile-card__scrim" aria-hidden="true"></span>
		<span class="tile-card__title"><?php echo esc_html( $item['title'] ); ?></span>
	</div>
	<?php
}

/**
 * Печатает один раздел каталога: заголовок + сетка карточек .device-card.
 *
 * Один и тот же блок повторяется 7 раз на странице "Ремешки для WHOOP 5.0"
 * и 6 раз на "Ремешки для WHOOP MG" — общая функция вместо копии замыкания
 * в каждом паттерне (правило 14 CLAUDE.md). Сетка — класс
 * .catalog-section__list, подключённый к тому же CSS-правилу, что и сетки
 * секций Главной (см. .device-grid__list в theme.css).
 *
 * @param string $title      Заголовок раздела (Onest Medium 20px по Figma —
 *                            общий класс .card-heading).
 * @param array  $items      Массив карточек в формате whoopstore_device_card().
 *                            Значение 'image' трактуется как имя файла без
 *                            префикса и расширения, если задан $image_base.
 * @param string $image_base Префикс пути к фото, напр.
 *                            '/wp-content/uploads/2026/09/straps-mg-'.
 *                            К нему добавляется 'image' и расширение (.webp,
 *                            если он сгенерирован, иначе .png). Пусто —
 *                            'image' используется как есть (полный путь).
 * @param string $anchor     id секции для внутристраничных ссылок (напр.
 *                            'buy' — кнопка "Подключить" в hero "Подписок"
 *                            ведёт на #buy). Пусто — id не выводится.
 */
function whoopstore_catalog_section( $title, array $items, $image_base = '', $anchor = '' ) {
	?>
	<section class="catalog-section"<?php echo '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?>>
		<h2 class="catalog-section__title card-heading"><?php echo esc_html( $title ); ?></h2>
		<div class="catalog-section__list">
			<?php
			foreach ( $items as $item ) {
				if ( '' !== $image_base && ! empty( $item['image'] ) ) {
					// Исходники каталога лежат в uploads как .png; отдаём .webp,
					// когда он сгенерирован (см. whoopstore_webp_src).
					$item['image'] = whoopstore_webp_src( $image_base . $item['image'] . '.png' );
				}
				whoopstore_device_card( $item );
			}
			?>
		</div>
	</section>
	<?php
}
