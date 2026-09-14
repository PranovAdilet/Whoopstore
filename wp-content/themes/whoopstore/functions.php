<?php
/**
 * Whoopstore theme bootstrap.
 *
 * Держим этот файл пустым от логики — только подключение inc/.
 * Каждый файл в inc/ создаётся только под конкретную, подтверждённую задачу
 * (см. архитектурный sanity-check pilot-этапа).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'inc/setup.php' );
require_once get_theme_file_path( 'inc/card-component.php' );
require_once get_theme_file_path( 'inc/page-head-component.php' );
require_once get_theme_file_path( 'inc/product-data.php' );
require_once get_theme_file_path( 'inc/woocommerce/template-hooks.php' );
require_once get_theme_file_path( 'inc/woocommerce/single-product.php' );
require_once get_theme_file_path( 'inc/woocommerce/product-accordions.php' );
require_once get_theme_file_path( 'inc/woocommerce/cart-ui.php' );
require_once get_theme_file_path( 'inc/woocommerce/redirects.php' );
