<?php
/**
 * Форма добавления простого товара в корзину.
 *
 * Переопределение woocommerce/templates/single-product/add-to-cart/simple.php.
 * Единственное отличие от оригинала — то же, что у
 * variation-add-to-cart-button.php: вместо видимого поля количества
 * (woocommerce_quantity_input) выводится скрытое поле с минимальным
 * количеством. В макете (Figma 30:143/30:283) поля количества нет — после
 * добавления кнопка сменяется степпером "В корзине: − 1 +"
 * (assets/js/cart.js). Почему шаблоном, а не CSS, — см. шапку
 * variation-add-to-cart-button.php.
 *
 * Всё остальное сохранено как в WooCommerce: проверки is_purchasable и
 * наличия, вывод остатка, все хуки формы и количества, фильтр минимального
 * количества и кнопка button[name=add-to-cart] — по ней форма работает
 * обычным POST без JS, и её же cart.js читает как id товара.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package Whoopstore
 * @version 10.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<?php do_action( 'woocommerce_before_add_to_cart_quantity' ); ?>

		<input type="hidden" name="quantity" value="<?php echo esc_attr( apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ) ); ?>" />

		<?php do_action( 'woocommerce_after_add_to_cart_quantity' ); ?>

		<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>
