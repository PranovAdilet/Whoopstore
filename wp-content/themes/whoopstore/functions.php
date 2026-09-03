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
