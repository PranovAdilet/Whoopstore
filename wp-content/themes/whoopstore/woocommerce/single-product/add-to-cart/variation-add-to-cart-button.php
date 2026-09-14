<?php
/**
 * Кнопка добавления вариации в корзину.
 *
 * Переопределение woocommerce/templates/single-product/add-to-cart/
 * variation-add-to-cart-button.php. Единственное отличие от оригинала —
 * вместо видимого поля количества (woocommerce_quantity_input) выводится
 * скрытое поле со значением 1.
 *
 * Почему в разметке, а не CSS: в макете (Figma 30:143/30:283) поля
 * количества нет — после добавления кнопка заменяется степпером
 * "В корзине: − 1 +", а до этого выбирать количество негде. Прятать поле
 * стилями пришлось бы правилом, которое перебивает
 * `.woocommerce div.product form.cart div.quantity{display:inline-block}`
 * из woocommerce-blocktheme.css — это держится на специфичности и ломается
 * от любой правки их CSS; плюс скрытый стилями <input type="number">
 * остаётся доступен с клавиатуры. Значение 1 в форме сохраняется, то есть
 * поведение то же, что у товара "продаётся поштучно" в WooCommerce, и
 * коммерческая логика не меняется.
 *
 * Оба хука количества (before/after_add_to_cart_quantity) сохранены —
 * на них могут висеть интеграции.
 *
 * @package Whoopstore
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php do_action( 'woocommerce_before_add_to_cart_quantity' ); ?>

	<input type="hidden" name="quantity" value="<?php echo esc_attr( $product->get_min_purchase_quantity() ); ?>" />

	<?php do_action( 'woocommerce_after_add_to_cart_quantity' ); ?>

	<button type="submit" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
