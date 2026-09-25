<?php
/**
 * Plugin Name: Whoopstore Site
 * Description: Общесайтовые вещи, не относящиеся ни к оформлению заказа, ни к вёрстке: SEO-метаданные страниц и подключение счётчиков аналитики.
 * Version: 1.0.0
 *
 * Почему mu-plugin, а не тема: метаданные страницы и счётчики принадлежат
 * сайту, а не оформлению. При смене или доработке темы они пропадать не должны.
 *
 * Почему не SEO-плагин: по ТЗ новые плагины ставятся только по согласованию, а
 * задача здесь узкая — отдать description/Open Graph/canonical из уже
 * существующего контента. Yoast/Rank Math принесли бы админ-интерфейс,
 * sitemap, редиректы и собственную схему schema.org, которая конфликтует с той,
 * что WooCommerce уже отдаёт на страницах товаров. Если заказчику понадобится
 * ручное редактирование метатегов по каждой странице — это отдельное решение,
 * и тогда SEO-плагин обоснован.
 *
 * mu-plugins подключаются только файлами из корня каталога, поэтому здесь
 * загрузчик, а сами модули лежат в whoopstore-site/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/whoopstore-site/seo.php';
require_once __DIR__ . '/whoopstore-site/analytics.php';
require_once __DIR__ . '/whoopstore-site/content.php';
require_once __DIR__ . '/whoopstore-site/redirects.php';
