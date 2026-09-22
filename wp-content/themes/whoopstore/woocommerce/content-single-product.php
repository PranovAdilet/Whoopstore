<?php
/**
 * Страница товара — раскладка по Figma (nodes 30:143 desktop / 30:1180 mobile).
 *
 * Переопределение woocommerce/templates/content-single-product.php.
 * Отличия от оригинала — только раскладка: добавлены крошки внутри контентной
 * сетки и обёртка .product-page__layout (галерея слева + колонка данных
 * справа). Все хуки WooCommerce сохранены в исходном порядке, чтобы
 * плагины/интеграции продолжали работать.
 *
 * @see inc/woocommerce/single-product.php — какие стандартные хуки сняты и почему.
 * @package Whoopstore
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}

woocommerce_breadcrumb();
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'product-page__product', $product ); ?>>

	<div class="product-page__layout">

		<?php
		/**
		 * Hook: woocommerce_before_single_product_summary.
		 *
		 * @hooked woocommerce_show_product_images - 20
		 */
		do_action( 'woocommerce_before_single_product_summary' );
		?>

		<div class="summary entry-summary product-page__summary">
			<?php
			/**
			 * Hook: woocommerce_single_product_summary.
			 *
			 * @hooked woocommerce_template_single_title - 5
			 * @hooked woocommerce_template_single_price - 10
			 * @hooked woocommerce_template_single_add_to_cart - 30
			 * @hooked whoopstore_single_description - 40
			 * @hooked whoopstore_single_accordions - 50
			 * @hooked WC_Structured_Data::generate_product_data() - 60
			 */
			do_action( 'woocommerce_single_product_summary' );
			?>
		</div>

	</div>

	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 *
	 * @hooked woocommerce_output_related_products - 20
	 */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
