<?php
/**
 * Plugin Name: Whoopstore Commerce
 * Description: Коммерческая логика магазина, не зависящая от темы: поля оформления заказа, подписи способов доставки, уведомления о заказах в Telegram.
 * Version: 1.0.0
 *
 * Почему mu-plugin, а не тема: всё здесь принадлежит заказам и настройкам
 * магазина. Если бы это жило в теме, при её смене у заказов пропали бы поля
 * «Telegram» и согласие, у способов доставки — подписи, а уведомления
 * перестали бы уходить. Тема отвечает только за вывод формы.
 *
 * mu-plugins подключаются только файлами из корня каталога, поэтому здесь
 * загрузчик, а сами модули лежат в whoopstore-commerce/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/whoopstore-commerce/checkout.php';
require_once __DIR__ . '/whoopstore-commerce/shipping.php';
require_once __DIR__ . '/whoopstore-commerce/cdek.php';
require_once __DIR__ . '/whoopstore-commerce/telegram.php';

// Настройка Telegram в админке (WooCommerce → Настройки → Дополнительно → Telegram).
if ( is_admin() ) {
	require_once __DIR__ . '/whoopstore-commerce/telegram-admin.php';
}
