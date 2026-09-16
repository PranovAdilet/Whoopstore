<?php
/**
 * Описание товара и два аккордеона под ним — "Таблица размеров" и
 * "Доставка" (Figma 30:259…30:269 desktop, 30:1233…30:1242 mobile).
 *
 * Почему поля товара, а не текст в теме: содержимое обоих аккордеонов —
 * это контент магазина (габариты ремешка, сроки доставки), он меняется
 * без разработчика и отличается у разных товаров. Хардкод в PHP-шаблоне
 * сделал бы его неправимым из админки, а вкладки WooCommerce
 * ("Описание"/"Характеристики") в макете заменены как раз на эти
 * аккордеоны. Поэтому — два обычных мета-поля товара с полями ввода в
 * штатной панели "Данные товара", без плагинов (правило 15 CLAUDE.md).
 * Пустое поле = аккордеон не выводится.
 *
 * Правка 2026-09-16: условия доставки в макете общие для всех товаров, и
 * держать их в каждом товаре бессмысленно — 60 карточек остались бы без
 * аккордеона, пока кто-то не заполнит поле руками. Поэтому у "Доставки"
 * появился общий текст в настройках магазина, а поле товара стало
 * переопределением. "Таблица размеров" общего текста не получает: габариты
 * у каждой модели свои.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Аккордеоны страницы товара: ключ мета-поля => заголовок из макета.
 *
 * @return array
 */
function whoopstore_product_accordion_fields() {
	return [
		'_whoopstore_size_guide' => 'Таблица размеров',
		'_whoopstore_delivery'   => 'Доставка',
	];
}

/* ---------- Общий текст "Доставки" ---------- */

/**
 * Условия доставки из макета (Figma 30:269) — значение по умолчанию.
 *
 * @return string
 */
function whoopstore_delivery_text_fallback() {
	return "<strong>Срок доставки в Россию:</strong> 14-21 дней\n<strong>Доставка по России:</strong> Курьерская доставка по Москве в день заказа, доставка по России — за 2–4 дня через службы СДЭК и 5Post.";
}

/**
 * Текст "Доставки", общий для всех товаров (настройка магазина).
 *
 * @return string
 */
function whoopstore_default_delivery_text() {
	return (string) get_option( 'whoopstore_default_delivery', whoopstore_delivery_text_fallback() );
}

/**
 * Поле общего текста — в штатные настройки WooCommerce → Товары, чтобы его
 * правил менеджер, а не разработчик. WooCommerce сам сохраняет значение в
 * опцию с тем же id.
 */
add_filter( 'woocommerce_get_settings_products', function ( $settings, $section ) {
	if ( '' !== $section ) {
		return $settings;
	}

	$settings[] = [
		'type'  => 'title',
		'title' => 'Страница товара',
		'id'    => 'whoopstore_product_page_options',
	];

	$settings[] = [
		'type'    => 'textarea',
		'id'      => 'whoopstore_default_delivery',
		'title'   => 'Аккордеон «Доставка»',
		'desc'    => 'Общий для всех товаров текст. Если у товара это поле заполнено в «Данных товара», показывается его собственный текст. У виртуальных товаров (подписки, активация) аккордеон не выводится.',
		'default' => whoopstore_delivery_text_fallback(),
		'css'     => 'min-height:120px; width:100%;',
	];

	$settings[] = [
		'type' => 'sectionend',
		'id'   => 'whoopstore_product_page_options',
	];

	return $settings;
}, 10, 2 );

/* ---------- Админка: поля в панели "Данные товара" ---------- */

add_action( 'woocommerce_product_options_general_product_data', function () {
	$descriptions = [
		'_whoopstore_size_guide' => 'Содержимое аккордеона «Таблица размеров» на странице товара. Пусто — аккордеон не выводится.',
		'_whoopstore_delivery'   => 'Условия доставки именно этого товара. Пусто — берётся общий текст из WooCommerce → Настройки → Товары.',
	];

	echo '<div class="options_group">';

	foreach ( whoopstore_product_accordion_fields() as $key => $label ) {
		woocommerce_wp_textarea_input( [
			'id'          => $key,
			'label'       => $label,
			'description' => isset( $descriptions[ $key ] ) ? $descriptions[ $key ] : '',
			'desc_tip'    => true,
			'rows'        => 4,
		] );
	}

	echo '</div>';
} );

add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	foreach ( array_keys( whoopstore_product_accordion_fields() ) as $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- нонс проверяет сам WooCommerce до этого хука.
		$value = isset( $_POST[ $key ] ) ? wp_kses_post( wp_unslash( $_POST[ $key ] ) ) : '';
		$product->update_meta_data( $key, $value );
	}
} );

/* ---------- Фронт: описание + аккордеоны в колонке данных ---------- */

/**
 * Полное описание товара (post_content) в колонке данных.
 *
 * В стандартной раскладке WooCommerce тут стоит краткое описание
 * (woocommerce_template_single_excerpt), а полное уезжает во вкладку
 * "Описание" под галереей. В макете вкладок нет, а под кнопкой стоит
 * именно полный текст с двумя полужирными зачинами — поэтому вместо
 * excerpt выводим содержимое товара тем же штатным фильтром, что и
 * вкладка WooCommerce (the_content).
 */
function whoopstore_single_description() {
	if ( ! get_the_content() ) {
		return;
	}

	echo '<div class="product-page__description">';
	the_content();
	echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'whoopstore_single_description', 40 );

/**
 * Аккордеоны "Таблица размеров" / "Доставка".
 *
 * Нативные <details>/<summary> — доступный аккордеон без JS, тот же приём,
 * что уже используется в "Частых вопросах" на Главной
 * (patterns/faq-accordion.php). Общий CSS-компонент с ним не заводим:
 * по макету это разные по оформлению блоки (там — карточка 80px на фоне
 * .surface с текстом 20px, здесь — строка 40px между линиями-разделителями
 * с текстом 13px), а правило 14 CLAUDE.md прямо запрещает объединять
 * похожие, но разные по дизайну элементы.
 */
function whoopstore_single_accordions() {
	global $product;

	$items = [];

	foreach ( whoopstore_product_accordion_fields() as $key => $label ) {
		$value = (string) $product->get_meta( $key );

		// Доставка одинакова для всех товаров, которые действительно везут:
		// пустое поле заменяется общим текстом из настроек. Виртуальные
		// товары (подписки, активация) не доставляют — у них аккордеона нет.
		if ( '' === trim( $value ) && '_whoopstore_delivery' === $key && ! $product->is_virtual() ) {
			$value = whoopstore_default_delivery_text();
		}

		if ( '' !== trim( $value ) ) {
			$items[ $label ] = $value;
		}
	}

	if ( ! $items ) {
		return;
	}
	?>
	<div class="product-accordion">
		<?php foreach ( $items as $label => $value ) : ?>
			<details class="product-accordion__item">
				<summary class="product-accordion__head">
					<span class="product-accordion__title"><?php echo esc_html( $label ); ?></span>
					<?php /* "+" из двух штрихов; в открытом состоянии вертикальный
					        прячется и остаётся "−" (Figma 30:265…30:268). */ ?>
					<svg class="product-accordion__icon" width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true">
						<line class="product-accordion__icon-h" x1="0" y1="7.5" x2="15" y2="7.5" stroke="currentColor" stroke-width="1" />
						<line class="product-accordion__icon-v" x1="7.5" y1="0" x2="7.5" y2="15" stroke="currentColor" stroke-width="1" />
					</svg>
				</summary>
				<div class="product-accordion__body"><?php echo wp_kses_post( wpautop( $value ) ); ?></div>
			</details>
		<?php endforeach; ?>
	</div>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'whoopstore_single_accordions', 50 );
