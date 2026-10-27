<?php
/**
 * Уведомления о заказах в Telegram — без настоящего бота.
 *
 * Проверяет mu-plugins/whoopstore-commerce/telegram.php и telegram-admin.php:
 *  - когда уходит сообщение: «Тестовая оплата» — при оформлении, ЮKassa —
 *    только после подтверждённой оплаты, одно на заказ, без дублей при
 *    повторных попытках; брошенный заказ в чат не попадает;
 *  - текст: пометка [staging] не на боевом, «оплачено» у ЮKassa, ПВЗ СДЭК;
 *  - сбой сети: отметка в заказе, токен в журнал не попадает;
 *  - настройки в админке: сохранение полей, поиск и выбор чата, тестовое
 *    сообщение, разбор ошибок, права;
 *  - константы wp-config.php важнее настроек.
 *
 * Telegram подменён на уровне HTTP (pre_http_request), любые другие исходящие
 * запросы закрыты. Всё идёт в транзакции и откатывается: пробные заказы и
 * опции не остаются (номера заказов расходуются — автоинкремент откат не
 * возвращает).
 *
 * Запуск:
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/telegram.php
 *
 * Код возврата 1 — есть непройденные проверки.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

require dirname( __DIR__, 2 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once WP_CONTENT_DIR . '/mu-plugins/whoopstore-commerce/telegram-admin.php';

global $wpdb;

$wpdb->query( 'START TRANSACTION' );
register_shutdown_function( function () use ( $wpdb ) {
	$wpdb->query( 'ROLLBACK' );
	wp_cache_flush();
} );

$whoopstore_qa_pass = 0;
$whoopstore_qa_fail = 0;

function whoopstore_qa_check( $title, $ok, $detail = '' ) {
	global $whoopstore_qa_pass, $whoopstore_qa_fail;

	$ok ? $whoopstore_qa_pass++ : $whoopstore_qa_fail++;
	echo ( $ok ? '  ok   ' : '  FAIL ' ) . $title . ( '' !== (string) $detail ? "  [$detail]" : '' ) . "\n";
}

// Поддельный Telegram. Токен GOOD — рабочий бот, чат -100777 существует.
$whoopstore_qa_sent     = [];
$whoopstore_qa_calls    = [];
$whoopstore_qa_offline  = false;
$whoopstore_qa_timeouts = [];

add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$whoopstore_qa_sent, &$whoopstore_qa_calls, &$whoopstore_qa_offline, &$whoopstore_qa_timeouts ) {
	if ( ! preg_match( '#api\.telegram\.org/bot([^/]+)/(\w+)#', (string) $url, $match ) ) {
		return new WP_Error( 'whoopstore_qa_blocked', 'outbound HTTP blocked in tools/qa/telegram.php' );
	}

	list( , $token, $method ) = $match;
	$whoopstore_qa_calls[]    = [ $method, $args['body'] ];
	$whoopstore_qa_timeouts[] = (int) $args['timeout'];

	if ( $whoopstore_qa_offline ) {
		return new WP_Error( 'http_request_failed', 'cURL error 28: timeout for ' . $url );
	}

	$reply = function ( $code, $body ) {
		return [ 'headers' => [], 'body' => wp_json_encode( $body ), 'response' => [ 'code' => $code, 'message' => '' ], 'cookies' => [] ];
	};

	if ( 'GOOD' !== $token ) {
		return $reply( 401, [ 'ok' => false, 'error_code' => 401, 'description' => 'Unauthorized' ] );
	}

	if ( 'getUpdates' === $method ) {
		return $reply( 200, [ 'ok' => true, 'result' => [
			[ 'update_id' => 1, 'message' => [ 'chat' => [ 'id' => 5551, 'type' => 'private', 'first_name' => 'Анна', 'last_name' => 'Менеджер' ] ] ],
			[ 'update_id' => 2, 'my_chat_member' => [ 'chat' => [ 'id' => -100777, 'type' => 'supergroup', 'title' => 'Заказы Whoopstore' ] ] ],
			[ 'update_id' => 3, 'message' => [ 'chat' => [ 'id' => -100777, 'type' => 'supergroup', 'title' => 'Заказы Whoopstore' ] ] ],
		] ] );
	}

	if ( 'sendMessage' === $method ) {
		if ( '-100777' !== (string) $args['body']['chat_id'] ) {
			return $reply( 400, [ 'ok' => false, 'description' => 'Bad Request: chat not found' ] );
		}

		$whoopstore_qa_sent[] = $args['body']['text'];

		return $reply( 200, [ 'ok' => true, 'result' => [ 'message_id' => 1 ] ] );
	}

	return $reply( 404, [ 'ok' => false, 'description' => 'Not Found' ] );
}, 10, 3 );

$whoopstore_qa_logged = [];
add_filter( 'woocommerce_logger_log_message', function ( $message ) use ( &$whoopstore_qa_logged ) {
	$whoopstore_qa_logged[] = $message;

	return $message;
} );

$whoopstore_qa_product = current( array_filter(
	wc_get_products( [ 'status' => 'publish', 'type' => 'simple', 'limit' => 20 ] ),
	function ( $item ) {
		return '' !== (string) $item->get_price();
	}
) );

/**
 * Заказ, как после оформления формой: pending, выбранный способ оплаты.
 */
function whoopstore_qa_order( $gateway_id ) {
	global $whoopstore_qa_product;

	$gateways = WC()->payment_gateways()->payment_gateways();
	$order    = wc_create_order();
	$order->add_product( $whoopstore_qa_product, 1 );
	$order->set_billing_first_name( 'QA Telegram' );
	$order->set_billing_phone( '+79001234567' );
	$order->set_billing_email( 'qa@example.com' );
	$order->set_payment_method( $gateways[ $gateway_id ] );
	$order->calculate_totals();
	$order->set_status( 'pending' );
	$order->save();

	return $order;
}

/**
 * Действие проверки в админке (admin-post.php) — результат и адрес возврата.
 */
function whoopstore_qa_tool( $tool, $extra = [] ) {
	$_GET     = array_merge( [ 'action' => 'whoopstore_telegram_tool', 'tool' => $tool ], $extra );
	$_REQUEST = $_GET + [ '_wpnonce' => wp_create_nonce( 'whoopstore_telegram_tool' ) ];
	$stop     = function ( $location ) {
		throw new Exception( $location );
	};

	add_filter( 'wp_redirect', $stop );

	try {
		do_action( 'admin_post_whoopstore_telegram_tool' );
		$location = '(нет редиректа)';
	} catch ( Exception $e ) {
		$location = $e->getMessage();
	}

	remove_filter( 'wp_redirect', $stop );
	$result = get_transient( 'whoopstore_telegram_tool_' . get_current_user_id() );
	delete_transient( 'whoopstore_telegram_tool_' . get_current_user_id() );

	return [ $location, is_array( $result ) ? $result : [ 'type' => '', 'text' => '' ] ];
}

if ( ! $whoopstore_qa_product ) {
	echo "Нет товара с ценой для проверки.\n";
	exit( 1 );
}

$whoopstore_qa_admin = current( get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] ) );
wp_set_current_user( (int) $whoopstore_qa_admin );

echo "== Настройки в админке (WooCommerce → Настройки → Дополнительно → Telegram)\n";
$advanced = null;

foreach ( WC_Admin_Settings::get_settings_pages() as $page ) {
	if ( 'advanced' === $page->get_id() ) {
		$advanced = $page;
	}
}

WC_Admin_Settings::save_fields( $advanced->get_settings_for_section( 'whoopstore_telegram' ), [ 'whoopstore_telegram_bot_token' => '  BAD  ', 'whoopstore_telegram_chat_id' => '' ] );
whoopstore_qa_check( 'поля сохраняются штатной формой, пробелы вокруг токена убраны', 'BAD' === whoopstore_telegram_credentials()['token'] );
list( $location, $result ) = whoopstore_qa_tool( 'chats' );
whoopstore_qa_check( 'неверный токен — понятная ошибка и возврат в раздел', 'error' === $result['type'] && false !== strpos( $result['text'], 'не принял токен' ) && false !== strpos( $location, 'section=whoopstore_telegram' ), $result['text'] );
update_option( 'whoopstore_telegram_bot_token', 'GOOD' );
list( , $result ) = whoopstore_qa_tool( 'test' );
whoopstore_qa_check( 'тест без выбранного чата — просьба выбрать чат', 'error' === $result['type'] && false !== strpos( $result['text'], 'выберите чат' ), $result['text'] );
list( , $result ) = whoopstore_qa_tool( 'chats' );
whoopstore_qa_check( 'поиск чатов — два чата без повторов', 2 === count( $result['chats'] ?? [] ), $result['text'] );
list( , $result ) = whoopstore_qa_tool( 'use', [ 'chat' => '-100777' ] );
whoopstore_qa_check( 'выбор чата', '-100777' === get_option( 'whoopstore_telegram_chat_id' ), $result['text'] );
list( , $result ) = whoopstore_qa_tool( 'use', [ 'chat' => '-100777abc' ] );
whoopstore_qa_check( 'мусорный ID чата отклонён', '-100777' === get_option( 'whoopstore_telegram_chat_id' ) && 'error' === $result['type'], $result['text'] );
$whoopstore_qa_calls = [];
list( , $result )    = whoopstore_qa_tool( 'test' );
$text                = (string) ( $whoopstore_qa_calls[0][1]['text'] ?? '' );
whoopstore_qa_check( 'тестовое сообщение отправлено', 'success' === $result['type'], $result['text'] );
whoopstore_qa_check( 'не на боевом текст начинается с [staging]', 'production' === wp_get_environment_type() || 0 === strpos( $text, "[staging]\n" ), strtok( $text, "\n" ) );
update_option( 'whoopstore_telegram_chat_id', '-1' );
list( , $result ) = whoopstore_qa_tool( 'test' );
whoopstore_qa_check( 'несуществующий чат — понятная ошибка', 'error' === $result['type'] && false !== strpos( $result['text'], 'Чат не найден' ), $result['text'] );
update_option( 'whoopstore_telegram_chat_id', '-100777' );

wp_set_current_user( 0 );
$_REQUEST['_wpnonce'] = 'x';
$guest_stopped        = false;
add_filter( 'wp_die_handler', function () {
	return function ( $message ) {
		throw new Exception( is_string( $message ) ? $message : 'wp_die' );
	};
} );

try {
	do_action( 'admin_post_whoopstore_telegram_tool' );
} catch ( Exception $e ) {
	$guest_stopped = true;
}

whoopstore_qa_check( 'гость остановлен', $guest_stopped );
wp_set_current_user( (int) $whoopstore_qa_admin );

echo "\n== Когда уходит уведомление\n";
$whoopstore_qa_sent = [];
$cheque             = whoopstore_qa_order( 'cheque' );
do_action( 'woocommerce_store_api_checkout_order_processed', $cheque );
whoopstore_qa_check( '«Тестовая оплата» — сразу при оформлении', 1 === count( $whoopstore_qa_sent ) );
whoopstore_qa_check( 'отметка sent в заказе', 'sent' === wc_get_order( $cheque->get_id() )->get_meta( '_whoopstore_telegram_notified' ) );
whoopstore_qa_check( 'строка оплаты без «оплачено»', (bool) preg_match( '/^Оплата: [^\n]*$/mu', $whoopstore_qa_sent[0] ?? '', $line ) && false === strpos( $line[0], 'оплачено' ), $line[0] ?? '' );
do_action( 'woocommerce_store_api_checkout_order_processed', wc_get_order( $cheque->get_id() ) );
whoopstore_qa_check( 'повторное оформление — без дубля', 1 === count( $whoopstore_qa_sent ) );

$whoopstore_qa_sent = [];
$online             = whoopstore_qa_order( 'yookassa_epl' );
do_action( 'woocommerce_store_api_checkout_order_processed', $online );
do_action( 'woocommerce_store_api_checkout_order_processed', wc_get_order( $online->get_id() ) );
whoopstore_qa_check( 'ЮKassa: оформление и повторная попытка оплаты — без уведомления', 0 === count( $whoopstore_qa_sent ) && '' === wc_get_order( $online->get_id() )->get_meta( '_whoopstore_telegram_notified' ) );
wc_get_order( $online->get_id() )->payment_complete( 'whoopstore-qa-payment' );
whoopstore_qa_check( 'ЮKassa: после подтверждённой оплаты — одно, с «оплачено»', 1 === count( $whoopstore_qa_sent ) && false !== strpos( $whoopstore_qa_sent[0], 'Оплата: Онлайн-оплата — оплачено' ), wc_get_order( $online->get_id() )->get_status() );
do_action( 'woocommerce_payment_complete', $online->get_id() );
do_action( 'woocommerce_store_api_checkout_order_processed', wc_get_order( $online->get_id() ) );
whoopstore_qa_check( 'ЮKassa: повторное уведомление об оплате — без дубля', 1 === count( $whoopstore_qa_sent ) );

$whoopstore_qa_sent = [];
$abandoned          = whoopstore_qa_order( 'yookassa_epl' );
do_action( 'woocommerce_store_api_checkout_order_processed', $abandoned );
wc_get_order( $abandoned->get_id() )->update_status( 'cancelled' );
whoopstore_qa_check( 'ЮKassa: брошенный и отменённый заказ в чат не попал', 0 === count( $whoopstore_qa_sent ) );

echo "\n== Текст сообщения\n";
$whoopstore_qa_sent = [];
$pickup             = whoopstore_qa_order( 'cheque' );
$pickup->update_meta_data( WHOOPSTORE_CDEK_OFFICE_META, 'SPB55 — Санкт-Петербург, пр-т 2-й Муринский, 49' );
$pickup->save();
do_action( 'woocommerce_store_api_checkout_order_processed', $pickup );
$text = $whoopstore_qa_sent[0] ?? '';
whoopstore_qa_check( 'номер, имя, телефон, состав и сумма', false !== strpos( $text, 'Новый заказ №' . $pickup->get_order_number() ) && false !== strpos( $text, 'Имя: QA Telegram' ) && false !== strpos( $text, 'Телефон: +79001234567' ) && false !== strpos( $text, $whoopstore_qa_product->get_name() ) && false !== strpos( $text, 'Итого:' ) );
whoopstore_qa_check( 'ПВЗ СДЭК в сообщении', false !== strpos( $text, 'ПВЗ СДЭК: SPB55 — ' ) );

echo "\n== Сбой сети и журнал\n";
$whoopstore_qa_offline = true;
$whoopstore_qa_logged  = [];
$offline               = whoopstore_qa_order( 'cheque' );
do_action( 'woocommerce_store_api_checkout_order_processed', $offline );
$whoopstore_qa_offline = false;
$log                   = implode( "\n", $whoopstore_qa_logged );
whoopstore_qa_check( 'отметки об отправке нет — запланирован повтор', '' === wc_get_order( $offline->get_id() )->get_meta( '_whoopstore_telegram_notified' ) );
whoopstore_qa_check( 'в журнале токен скрыт', '' !== $log && false === strpos( $log, 'GOOD' ) && false !== strpos( $log, '***' ) );

echo "\n== Повтор после сбоя связи\n";
$whoopstore_qa_offline = true;
$retry                 = whoopstore_qa_order( 'cheque' );
do_action( 'woocommerce_store_api_checkout_order_processed', $retry );
$order_id = $retry->get_id();
whoopstore_qa_check( 'отметки об отправке нет — ждём повтора', '' === wc_get_order( $order_id )->get_meta( '_whoopstore_telegram_notified' ) );
whoopstore_qa_check( 'попытка записана', 1 === (int) wc_get_order( $order_id )->get_meta( WHOOPSTORE_TELEGRAM_ATTEMPTS_META ) );
whoopstore_qa_check( 'повтор запланирован', (bool) as_next_scheduled_action( WHOOPSTORE_TELEGRAM_RETRY_HOOK, [ 'order_id' => $order_id ], 'whoopstore' ) );

whoopstore_qa_check( 'первая отправка ждёт недолго — она внутри чужого запроса', [ WHOOPSTORE_TELEGRAM_TIMEOUT ] === array_slice( $whoopstore_qa_timeouts, -1 ), implode( ',', $whoopstore_qa_timeouts ) );

$whoopstore_qa_sent     = [];
$whoopstore_qa_timeouts = [];
$whoopstore_qa_offline  = false;
do_action( WHOOPSTORE_TELEGRAM_RETRY_HOOK, $order_id );
whoopstore_qa_check( 'связь вернулась — уведомление ушло с повтора', 1 === count( $whoopstore_qa_sent ) );
whoopstore_qa_check( 'повтор ждёт дольше — его никто не держит', [ WHOOPSTORE_TELEGRAM_RETRY_TIMEOUT ] === $whoopstore_qa_timeouts, implode( ',', $whoopstore_qa_timeouts ) );
whoopstore_qa_check( 'запас снят после повтора', WHOOPSTORE_TELEGRAM_TIMEOUT === (int) apply_filters( 'whoopstore_telegram_timeout', WHOOPSTORE_TELEGRAM_TIMEOUT, 'sendMessage' ) );
whoopstore_qa_check( 'отметка sent, счётчик попыток очищен', 'sent' === wc_get_order( $order_id )->get_meta( '_whoopstore_telegram_notified' ) && '' === wc_get_order( $order_id )->get_meta( WHOOPSTORE_TELEGRAM_ATTEMPTS_META ) );

$whoopstore_qa_sent    = [];
$whoopstore_qa_offline = true;
$dead                  = whoopstore_qa_order( 'cheque' );
$dead->update_meta_data( WHOOPSTORE_TELEGRAM_ATTEMPTS_META, count( whoopstore_telegram_retry_delays() ) );
$dead->save();
do_action( 'woocommerce_store_api_checkout_order_processed', $dead );
$whoopstore_qa_offline = false;
whoopstore_qa_check( 'попытки исчерпаны — отметка failed', 'failed' === wc_get_order( $dead->get_id() )->get_meta( '_whoopstore_telegram_notified' ) );

echo "\n== Константы wp-config.php важнее настроек\n";
define( 'WHOOPSTORE_TELEGRAM_CHAT_ID', '-999' );
$credentials = whoopstore_telegram_credentials();
whoopstore_qa_check( 'чат из константы, токен из настройки', '-999' === $credentials['chat_id'] && 'GOOD' === $credentials['token'] );

echo "\nИтого: $whoopstore_qa_pass ok, $whoopstore_qa_fail FAIL\n";
exit( $whoopstore_qa_fail ? 1 : 0 );
