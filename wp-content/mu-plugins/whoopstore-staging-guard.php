<?php
/**
 * Plugin Name: Whoopstore Staging Guard
 * Description: Prevents indexing and accidental outbound mail on the isolated staging site.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wp_robots', static function (array $robots): array {
    $robots['noindex'] = true;
    $robots['nofollow'] = true;
    $robots['noarchive'] = true;
    return $robots;
}, 999);

add_action('send_headers', static function (): void {
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
});

add_filter('pre_wp_mail', static function ($return) {
    if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'staging') {
        return false;
    }
    return $return;
}, 999);

add_filter('automatic_updater_disabled', '__return_true');
