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

/* ---------- Админка: поля в панели "Данные товара" ---------- */

add_action( 'woocommerce_product_options_general_product_data', function () {
	echo '<div class="options_group">';

	foreach ( whoopstore_product_accordion_fields() as $key => $label ) {
		woocommerce_wp_textarea_input( [
			'id'          => $key,
			'label'       => $label,
			'description' => 'Содержимое одноимённого аккордеона на странице товара. Пусто — аккордеон не выводится.',
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
		$value = $product->get_meta( $key );

		if ( '' !== trim( (string) $value ) ) {
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
