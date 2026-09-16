<?php
/**
 * Уведомление о новом заказе в Telegram.
 *
 * По ТЗ в сообщении: имя, телефон, состав корзины, адрес, сумма. Сюда же —
 * e-mail, Telegram покупателя, способ доставки и оплаты, комментарий.
 *
 * Секреты — только в wp-config.php, не в БД и не в git:
 *   define( 'WHOOPSTORE_TELEGRAM_BOT_TOKEN', '...' );
 *   define( 'WHOOPSTORE_TELEGRAM_CHAT_ID', '...' );
 * Необязательно — если api.telegram.org недоступен с хостинга:
 *   define( 'WHOOPSTORE_TELEGRAM_API_BASE', 'https://...' );
 *
 * Пока токена или чата нет, сообщение не отправляется, а его текст пишется в
 * журнал WooCommerce (WooCommerce → Статус → Журналы, источник
 * whoopstore-telegram) — так содержимое проверяется без настоящего бота.
 *
 * Отправка синхронная, с коротким таймаутом: фоновые задачи (Action
 * Scheduler) на staging не выполнятся — петлевой запрос WordPress к себе
 * упирается в Basic Auth.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Значение дополнительного поля заказа (штатный API WooCommerce).
 *
 * @param WC_Order $order Заказ.
 * @param string   $key   id поля, напр. whoopstore/telegram.
 * @return string
 */
function whoopstore_order_additional_field( $order, $key ) {
	$class = 'Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields';

	if ( class_exists( 'Automattic\WooCommerce\Blocks\Package' ) && class_exists( $class ) ) {
		$fields = Automattic\WooCommerce\Blocks\Package::container()->get( $class );

		return (string) $fields->get_field_from_object( $key, $order, 'other' );
	}

	return (string) $order->get_meta( '_wc_other/' . $key );
}

/**
 * Сумма без HTML — для текстового сообщения.
 *
 * @param float    $amount Сумма.
 * @param WC_Order $order  Заказ (валюта).
 * @return string
 */
function whoopstore_telegram_price( $amount, $order ) {
	$text = wp_strip_all_tags( wc_price( $amount, [ 'currency' => $order->get_currency() ] ) );

	return str_replace( "\xc2\xa0", ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );
}

/**
 * Текст сообщения о заказе.
 *
 * @param WC_Order $order Заказ.
 * @return string
 */
function whoopstore_telegram_message( $order ) {
	$lines   = [];
	$lines[] = 'Новый заказ №' . $order->get_order_number();
	// Статус не пишем: уведомление уходит в момент оформления, до оплаты, и
	// статус в этот момент — ещё черновик. Способ оплаты — в конце сообщения.
	$lines[] = '';
	$lines[] = 'Имя: ' . trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	$lines[] = 'Телефон: ' . $order->get_billing_phone();
	$lines[] = 'E-mail: ' . $order->get_billing_email();

	$telegram = whoopstore_order_additional_field( $order, 'whoopstore/telegram' );

	if ( '' !== $telegram ) {
		$lines[] = 'Telegram: ' . $telegram;
	}

	$lines[] = '';
	$lines[] = 'Состав:';

	foreach ( $order->get_items() as $item ) {
		$meta = [];

		foreach ( $item->get_formatted_meta_data() as $entry ) {
			$meta[] = wp_strip_all_tags( $entry->display_key ) . ': ' . wp_strip_all_tags( $entry->display_value );
		}

		$lines[] = '• ' . $item->get_name() . ( $meta ? ' (' . implode( ', ', $meta ) . ')' : '' )
			. ' × ' . $item->get_quantity() . ' — ' . whoopstore_telegram_price( $item->get_total(), $order );
	}

	$lines[] = '';

	foreach ( $order->get_shipping_methods() as $shipping ) {
		$lines[] = 'Доставка: ' . $shipping->get_name() . ' — ' . whoopstore_telegram_price( $shipping->get_total(), $order );
	}

	$address = array_filter( [
		$order->get_shipping_postcode(),
		$order->get_shipping_city(),
		$order->get_shipping_address_1(),
		$order->get_shipping_address_2(),
	] );

	if ( $address ) {
		$lines[] = 'Адрес: ' . implode( ', ', $address );
	}

	if ( '' !== $order->get_customer_note() ) {
		$lines[] = 'Комментарий: ' . $order->get_customer_note();
	}

	$lines[] = 'Оплата: ' . $order->get_payment_method_title();
	$lines[] = 'Итого: ' . whoopstore_telegram_price( $order->get_total(), $order );

	return implode( "\n", $lines );
}

/**
 * Отправляет (или, без настроек, журналирует) уведомление о заказе.
 *
 * Повторно по тому же заказу не срабатывает — отметка в мета заказа.
 *
 * @param WC_Order|int $order Заказ или его id.
 */
function whoopstore_telegram_notify_order( $order ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );

	if ( ! $order || $order->get_meta( '_whoopstore_telegram_notified' ) ) {
		return;
	}

	$logger  = wc_get_logger();
	$context = [ 'source' => 'whoopstore-telegram' ];
	$text    = whoopstore_telegram_message( $order );
	$token   = defined( 'WHOOPSTORE_TELEGRAM_BOT_TOKEN' ) ? (string) WHOOPSTORE_TELEGRAM_BOT_TOKEN : '';
	$chat_id = defined( 'WHOOPSTORE_TELEGRAM_CHAT_ID' ) ? (string) WHOOPSTORE_TELEGRAM_CHAT_ID : '';

	if ( '' === $token || '' === $chat_id ) {
		$logger->info( "Telegram не настроен, сообщение не отправлено:\n" . $text, $context );
		$order->add_order_note( 'Уведомление в Telegram не отправлено: бот не настроен (текст — в журнале whoopstore-telegram).' );
		$order->update_meta_data( '_whoopstore_telegram_notified', 'skipped' );
		$order->save();
		return;
	}

	$base     = defined( 'WHOOPSTORE_TELEGRAM_API_BASE' ) ? rtrim( (string) WHOOPSTORE_TELEGRAM_API_BASE, '/' ) : 'https://api.telegram.org';
	$response = wp_remote_post( $base . '/bot' . $token . '/sendMessage', [
		'timeout' => 5,
		'body'    => [
			'chat_id'                  => $chat_id,
			'text'                     => $text,
			'disable_web_page_preview' => 'true',
		],
	] );

	$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $code ) {
		// Токен не должен попасть в журнал вместе с текстом ошибки.
		$error = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . $code . ' ' . wp_remote_retrieve_body( $response );
		$logger->error( 'Не удалось отправить уведомление о заказе №' . $order->get_order_number() . ': ' . str_replace( $token, '***', $error ), $context );
		$order->add_order_note( 'Уведомление в Telegram не отправлено: ошибка отправки (подробности — в журнале whoopstore-telegram).' );
		$order->update_meta_data( '_whoopstore_telegram_notified', 'failed' );
		$order->save();
		return;
	}

	$order->add_order_note( 'Уведомление о заказе отправлено в Telegram.' );
	$order->update_meta_data( '_whoopstore_telegram_notified', 'sent' );
	$order->save();
}

// Заказ из формы «Ваш заказ» (Store API) и, на всякий случай, со штатной
// страницы /checkout/.
add_action( 'woocommerce_store_api_checkout_order_processed', 'whoopstore_telegram_notify_order' );
add_action( 'woocommerce_checkout_order_processed', function ( $order_id ) {
	whoopstore_telegram_notify_order( $order_id );
} );
