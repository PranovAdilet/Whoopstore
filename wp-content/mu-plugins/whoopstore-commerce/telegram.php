<?php
/**
 * Уведомление о новом заказе в Telegram.
 *
 * По ТЗ в сообщении: имя, телефон, состав корзины, адрес, сумма. Сюда же —
 * e-mail, Telegram покупателя, способ доставки и оплаты, комментарий.
 *
 * Когда уходит: при онлайн-оплате (ЮKassa) — только после подтверждённой
 * оплаты, иначе менеджер получал бы и заказы, брошенные на странице оплаты,
 * не отличая их от оплаченных. При остальных способах — в момент оформления.
 *
 * Токен бота и ID чата заказчик вписывает сам: WooCommerce → Настройки →
 * Дополнительно → Telegram (telegram-admin.php, там же проверка отправки и
 * поиск чата). Доступа к файлам сайта у заказчика нет, поэтому значения
 * хранятся в опциях — как и ключи ЮKassa. В git их нет. Константы в
 * wp-config.php, если заданы, важнее настроек:
 *   define( 'WHOOPSTORE_TELEGRAM_BOT_TOKEN', '...' );
 *   define( 'WHOOPSTORE_TELEGRAM_CHAT_ID', '...' );
 * Необязательно — если api.telegram.org недоступен с хостинга:
 *   define( 'WHOOPSTORE_TELEGRAM_API_BASE', 'https://...' );
 *
 * Сообщения не с боевого окружения помечаются «[staging]», чтобы тестовый
 * заказ в чате менеджеров не приняли за настоящий.
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
 * Задача повторной отправки и счётчик попыток в мета заказа
 * (whoopstore_telegram_schedule_retry()).
 */
const WHOOPSTORE_TELEGRAM_RETRY_HOOK   = 'whoopstore_telegram_retry';
const WHOOPSTORE_TELEGRAM_ATTEMPTS_META = '_whoopstore_telegram_attempts';

/**
 * Таймауты запроса к Bot API, секунды.
 *
 * Первая отправка идёт внутри чужого запроса — оформления заказа или
 * уведомления ЮKassa, — поэтому ждать там долго нельзя. Повтор идёт в
 * планировщике, его никто не ждёт, и ему достаётся запас: маршрут до
 * api.telegram.org с российского хостинга временами теряет пакеты, и
 * TCP успевает переспросить. Заказ №450 (2026-10-23) обе попытки упёрся
 * в cURL 28 ровно на пятой секунде, хотя API в это же время отвечал за
 * 0,2 с из консоли.
 */
const WHOOPSTORE_TELEGRAM_TIMEOUT       = 5;
const WHOOPSTORE_TELEGRAM_RETRY_TIMEOUT = 20;

/**
 * Токен бота и ID чата: константа из wp-config.php, иначе — настройка.
 *
 * @return array{token: string, chat_id: string}
 */
function whoopstore_telegram_credentials() {
	$token = defined( 'WHOOPSTORE_TELEGRAM_BOT_TOKEN' ) ? WHOOPSTORE_TELEGRAM_BOT_TOKEN : get_option( 'whoopstore_telegram_bot_token', '' );
	$chat  = defined( 'WHOOPSTORE_TELEGRAM_CHAT_ID' ) ? WHOOPSTORE_TELEGRAM_CHAT_ID : get_option( 'whoopstore_telegram_chat_id', '' );

	return [
		'token'   => trim( (string) $token ),
		'chat_id' => trim( (string) $chat ),
	];
}

/**
 * Вызов метода Bot API.
 *
 * Токен в тексте ошибки заменяется на «***»: ошибка уходит в журнал и в
 * админку.
 *
 * @param string $method Метод Bot API, напр. sendMessage.
 * @param array  $params Параметры.
 * @return mixed|WP_Error Поле result ответа или ошибка (в data — HTTP-статус).
 */
function whoopstore_telegram_api( $method, $params = [] ) {
	$token = whoopstore_telegram_credentials()['token'];

	if ( '' === $token ) {
		return new WP_Error( 'whoopstore_telegram_no_token', 'Не задан токен бота.' );
	}

	$base     = defined( 'WHOOPSTORE_TELEGRAM_API_BASE' ) ? rtrim( (string) WHOOPSTORE_TELEGRAM_API_BASE, '/' ) : 'https://api.telegram.org';
	$response = wp_remote_post( $base . '/bot' . $token . '/' . $method, [
		'timeout' => (int) apply_filters( 'whoopstore_telegram_timeout', WHOOPSTORE_TELEGRAM_TIMEOUT, $method ),
		'body'    => $params,
	] );

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'whoopstore_telegram_http', str_replace( $token, '***', $response->get_error_message() ), [ 'status' => 0 ] );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code || ! is_array( $body ) || empty( $body['ok'] ) ) {
		$description = is_array( $body ) && isset( $body['description'] ) ? (string) $body['description'] : wp_remote_retrieve_body( $response );

		return new WP_Error( 'whoopstore_telegram_api', str_replace( $token, '***', 'HTTP ' . $code . ' ' . $description ), [ 'status' => $code ] );
	}

	return $body['result'];
}

/**
 * Отправляет текст в чат уведомлений.
 *
 * @param string $text Текст сообщения.
 * @return true|WP_Error
 */
function whoopstore_telegram_send( $text ) {
	if ( 'production' !== wp_get_environment_type() ) {
		$text = "[staging]\n" . $text;
	}

	$result = whoopstore_telegram_api( 'sendMessage', [
		'chat_id'                  => whoopstore_telegram_credentials()['chat_id'],
		'text'                     => $text,
		'disable_web_page_preview' => 'true',
	] );

	return is_wp_error( $result ) ? $result : true;
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
	// Статус не пишем: при оплате без перехода уведомление уходит в момент
	// оформления, и статус тогда — ещё черновик. Способ оплаты — в конце
	// сообщения, с отметкой, если заказ уже оплачен.
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

	// СДЭК до пункта выдачи: код и адрес ПВЗ (сохраняются при оформлении, cdek.php).
	$office = function_exists( 'whoopstore_cdek_order_office' ) ? whoopstore_cdek_order_office( $order ) : '';

	if ( '' !== $office ) {
		$lines[] = 'ПВЗ СДЭК: ' . $office;
	}

	if ( '' !== $order->get_customer_note() ) {
		$lines[] = 'Комментарий: ' . $order->get_customer_note();
	}

	$lines[] = 'Оплата: ' . $order->get_payment_method_title() . ( $order->is_paid() ? ' — оплачено' : '' );
	$lines[] = 'Итого: ' . whoopstore_telegram_price( $order->get_total(), $order );

	return implode( "\n", $lines );
}

/**
 * Оплачивается ли заказ онлайн — через ЮKassa (id всех её способов оплаты
 * начинаются с «yookassa»).
 *
 * @param WC_Order $order Заказ.
 * @return bool
 */
function whoopstore_is_online_payment( $order ) {
	return 0 === strpos( (string) $order->get_payment_method(), 'yookassa' );
}

/**
 * Отправляет (или, без настроек, журналирует) уведомление о заказе.
 *
 * Повторно по тому же заказу не срабатывает — отметка в мета заказа.
 * Неоплаченный заказ с онлайн-оплатой пропускается без отметки: уведомление
 * уйдёт, когда оплата подтвердится (woocommerce_payment_complete ниже).
 * Повторные попытки оплаты того же заказа его не дублируют.
 *
 * @param WC_Order|int $order Заказ или его id.
 */
function whoopstore_telegram_notify_order( $order ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );

	if ( ! $order || $order->get_meta( '_whoopstore_telegram_notified' ) ) {
		return;
	}

	if ( whoopstore_is_online_payment( $order ) && ! $order->is_paid() ) {
		return;
	}

	$logger  = wc_get_logger();
	$context = [ 'source' => 'whoopstore-telegram' ];
	$text    = whoopstore_telegram_message( $order );
	$config  = whoopstore_telegram_credentials();

	if ( '' === $config['token'] || '' === $config['chat_id'] ) {
		$logger->info( "Telegram не настроен, сообщение не отправлено:\n" . $text, $context );
		$order->add_order_note( 'Уведомление в Telegram не отправлено: бот не настроен (текст — в журнале whoopstore-telegram).' );
		$order->update_meta_data( '_whoopstore_telegram_notified', 'skipped' );
		$order->save();
		return;
	}

	$sent = whoopstore_telegram_send( $text );

	if ( is_wp_error( $sent ) ) {
		// Токен из текста ошибки уже убран (whoopstore_telegram_api).
		$logger->error( 'Не удалось отправить уведомление о заказе №' . $order->get_order_number() . ': ' . $sent->get_error_message(), $context );
		whoopstore_telegram_schedule_retry( $order, $logger, $context );
		return;
	}

	$order->add_order_note( 'Уведомление о заказе отправлено в Telegram.' );
	$order->update_meta_data( '_whoopstore_telegram_notified', 'sent' );
	$order->delete_meta_data( WHOOPSTORE_TELEGRAM_ATTEMPTS_META );
	$order->save();
}

/**
 * Через сколько повторять отправку после сбоя: 5 минут, 15 минут, час.
 * Дальше — сдаёмся и оставляем заметку в заказе.
 *
 * @return int[]
 */
function whoopstore_telegram_retry_delays() {
	return [ 5 * MINUTE_IN_SECONDS, 15 * MINUTE_IN_SECONDS, HOUR_IN_SECONDS ];
}

/**
 * Планирует повтор отправки после сбоя связи.
 *
 * Сбой сети или временная ошибка Telegram не должны терять заказ: менеджер
 * иначе просто не узнает о нём (аудит 2026-09-18, I5). Повтор ставится в
 * штатный планировщик WooCommerce (Action Scheduler) — **на боевом сайте ему
 * нужен рабочий cron**, см. docs/launch-checklist.md, раздел 3. Пока повтор
 * запланирован, отметка об отправке не ставится, иначе повтор сам себя
 * пропустит.
 *
 * @param WC_Order  $order   Заказ.
 * @param WC_Logger $logger  Журнал.
 * @param array     $context Источник журнала.
 */
function whoopstore_telegram_schedule_retry( $order, $logger, $context ) {
	$delays  = whoopstore_telegram_retry_delays();
	$attempt = (int) $order->get_meta( WHOOPSTORE_TELEGRAM_ATTEMPTS_META ) + 1;
	$delay   = $delays[ $attempt - 1 ] ?? null;

	$order->update_meta_data( WHOOPSTORE_TELEGRAM_ATTEMPTS_META, $attempt );

	if ( null === $delay || ! function_exists( 'as_schedule_single_action' ) ) {
		$order->add_order_note( 'Уведомление в Telegram не отправлено: ошибка отправки (подробности — в журнале whoopstore-telegram).' );
		$order->update_meta_data( '_whoopstore_telegram_notified', 'failed' );
		$order->save();
		$logger->error( 'Повторы исчерпаны, уведомление о заказе №' . $order->get_order_number() . ' не доставлено.', $context );

		return;
	}

	$order->add_order_note( sprintf( 'Уведомление в Telegram не отправлено, попытка %d. Повтор через %s.', $attempt, human_time_diff( time(), time() + $delay ) ) );
	$order->save();

	as_schedule_single_action( time() + $delay, WHOOPSTORE_TELEGRAM_RETRY_HOOK, [ 'order_id' => $order->get_id() ], 'whoopstore' );
}

/**
 * Повтор отправки: тот же код, что и при оформлении, — отметки об отправке на
 * заказе ещё нет, поэтому уведомление уйдёт заново. Отличие одно: запрос ждёт
 * дольше (WHOOPSTORE_TELEGRAM_RETRY_TIMEOUT).
 *
 * @param int|WC_Order $order Заказ или его ID.
 */
function whoopstore_telegram_notify_order_retry( $order ) {
	$timeout = static function () {
		return WHOOPSTORE_TELEGRAM_RETRY_TIMEOUT;
	};

	add_filter( 'whoopstore_telegram_timeout', $timeout );

	try {
		whoopstore_telegram_notify_order( $order );
	} finally {
		remove_filter( 'whoopstore_telegram_timeout', $timeout );
	}
}
add_action( WHOOPSTORE_TELEGRAM_RETRY_HOOK, 'whoopstore_telegram_notify_order_retry' );

// Заказ из формы «Ваш заказ» (Store API) и, на всякий случай, со штатной
// страницы /checkout/.
add_action( 'woocommerce_store_api_checkout_order_processed', 'whoopstore_telegram_notify_order' );
add_action( 'woocommerce_checkout_order_processed', function ( $order_id ) {
	whoopstore_telegram_notify_order( $order_id );
} );

// Подтверждённая оплата: ЮKassa вызывает WC_Order::payment_complete() по
// уведомлению о платеже, после этого заказ уже в статусе «Обработка».
add_action( 'woocommerce_payment_complete', function ( $order_id ) {
	whoopstore_telegram_notify_order( $order_id );
} );
