<?php
/**
 * Настройка уведомлений в Telegram из админки:
 * WooCommerce → Настройки → Дополнительно → Telegram.
 *
 * Заказчику достаточно создать бота и вставить его токен: ID чата находит
 * кнопка «Найти чаты бота», работу проверяет «Отправить тестовое сообщение».
 * Поля — штатный API настроек WooCommerce, значения — в опциях
 * whoopstore_telegram_bot_token / whoopstore_telegram_chat_id (их читает
 * whoopstore_telegram_credentials() в telegram.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WHOOPSTORE_TELEGRAM_SECTION = 'whoopstore_telegram';

/**
 * Адрес раздела настроек.
 *
 * @return string
 */
function whoopstore_telegram_settings_url() {
	return admin_url( 'admin.php?page=wc-settings&tab=advanced&section=' . WHOOPSTORE_TELEGRAM_SECTION );
}

/**
 * Ссылка на действие проверки (admin-post.php с nonce).
 *
 * @param string $tool test | chats | use.
 * @param array  $args Дополнительные параметры.
 * @return string
 */
function whoopstore_telegram_tool_url( $tool, $args = [] ) {
	$url = add_query_arg(
		array_merge( [ 'action' => 'whoopstore_telegram_tool', 'tool' => $tool ], $args ),
		admin_url( 'admin-post.php' )
	);

	return wp_nonce_url( $url, 'whoopstore_telegram_tool' );
}

add_filter( 'woocommerce_get_sections_advanced', function ( $sections ) {
	$sections[ WHOOPSTORE_TELEGRAM_SECTION ] = 'Telegram';

	return $sections;
} );

add_filter( 'woocommerce_get_settings_advanced', function ( $settings, $section ) {
	if ( WHOOPSTORE_TELEGRAM_SECTION !== $section ) {
		return $settings;
	}

	// Значение из wp-config.php важнее настройки: поле показываем
	// заблокированным и не сохраняем, чтобы не затереть опцию пустым.
	$locked = function ( $constant ) {
		return defined( $constant )
			? [ 'custom_attributes' => [ 'disabled' => 'disabled' ], 'is_option' => false, 'desc' => 'Задано в wp-config.php — это поле не используется.' ]
			: [];
	};

	return [
		[
			'type'      => 'title',
			'id'        => 'whoopstore_telegram_options',
			'is_option' => false,
			'title'     => 'Уведомления о заказах в Telegram',
			'desc'      => 'В сообщении: номер заказа, имя, телефон, e-mail, Telegram покупателя, состав, доставка и адрес, комментарий, способ оплаты и сумма. При онлайн-оплате (ЮKassa) сообщение приходит после оплаты, при остальных способах — сразу после оформления.'
				. '<ol>'
				. '<li>В Telegram откройте <strong>@BotFather</strong>, отправьте <code>/newbot</code> и следуйте подсказкам. В конце он пришлёт токен бота.</li>'
				. '<li>Добавьте бота в чат, куда должны приходить заказы (или напишите боту <code>/start</code> в личном чате).</li>'
				. '<li>Вставьте токен ниже и нажмите «Сохранить изменения».</li>'
				. '<li>Нажмите «Найти чаты бота» и у нужного чата — «Выбрать».</li>'
				. '<li>Нажмите «Отправить тестовое сообщение» и проверьте чат.</li>'
				. '</ol>',
		],
		array_merge( [
			'type'              => 'password',
			'id'                => 'whoopstore_telegram_bot_token',
			'title'             => 'Токен бота',
			'desc'              => 'Вида <code>123456789:AA…</code>, его выдаёт @BotFather.',
			'css'               => 'width:100%; max-width:420px;',
			'custom_attributes' => [ 'autocomplete' => 'off' ],
		], $locked( 'WHOOPSTORE_TELEGRAM_BOT_TOKEN' ) ),
		array_merge( [
			'type'  => 'text',
			'id'    => 'whoopstore_telegram_chat_id',
			'title' => 'ID чата',
			'desc'  => 'Заполняется кнопкой «Выбрать» после поиска чатов. У групп ID начинается с минуса.',
			'css'   => 'width:100%; max-width:420px;',
		], $locked( 'WHOOPSTORE_TELEGRAM_CHAT_ID' ) ),
		[
			'type'      => 'whoopstore_telegram_tools',
			'id'        => 'whoopstore_telegram_tools',
			'is_option' => false,
		],
		[
			'type'      => 'sectionend',
			'id'        => 'whoopstore_telegram_options',
			'is_option' => false,
		],
	];
}, 10, 2 );

/**
 * Строка «Проверка»: состояние, кнопки и результат последнего действия.
 */
add_action( 'woocommerce_admin_field_whoopstore_telegram_tools', function () {
	$config = whoopstore_telegram_credentials();
	$result = get_transient( 'whoopstore_telegram_tool_' . get_current_user_id() );

	if ( false !== $result ) {
		delete_transient( 'whoopstore_telegram_tool_' . get_current_user_id() );
	}

	if ( '' === $config['token'] ) {
		$status = 'Уведомления не отправляются: не задан токен бота. Текст каждого заказа пишется в журнал (WooCommerce → Статус → Журналы, источник whoopstore-telegram).';
	} elseif ( '' === $config['chat_id'] ) {
		$status = 'Уведомления не отправляются: не выбран чат. Нажмите «Найти чаты бота».';
	} else {
		$status = 'Уведомления о новых заказах отправляются в чат ' . $config['chat_id'] . '.';
	}

	$type_labels = [
		'private'    => 'личный чат',
		'group'      => 'группа',
		'supergroup' => 'группа',
		'channel'    => 'канал',
	];
	?>
	<tr valign="top">
		<th scope="row" class="titledesc">Проверка</th>
		<td class="forminp">
			<p><?php echo esc_html( $status ); ?></p>

			<?php if ( is_array( $result ) && ! empty( $result['text'] ) ) : ?>
				<div class="notice notice-<?php echo 'error' === $result['type'] ? 'error' : 'success'; ?> inline"><p><?php echo esc_html( $result['text'] ); ?></p></div>
			<?php endif; ?>

			<?php if ( is_array( $result ) && ! empty( $result['chats'] ) ) : ?>
				<table class="widefat striped" style="max-width:640px; margin:8px 0;">
					<thead><tr><th>Чат</th><th>Тип</th><th>ID</th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $result['chats'] as $chat ) : ?>
							<tr>
								<td><?php echo esc_html( $chat['name'] ); ?></td>
								<td><?php echo esc_html( $type_labels[ $chat['type'] ] ?? $chat['type'] ); ?></td>
								<td><code><?php echo esc_html( $chat['id'] ); ?></code></td>
								<td>
									<?php if ( ! defined( 'WHOOPSTORE_TELEGRAM_CHAT_ID' ) ) : ?>
										<a class="button button-small" href="<?php echo esc_url( whoopstore_telegram_tool_url( 'use', [ 'chat' => $chat['id'] ] ) ); ?>">Выбрать</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<p>
				<a class="button" href="<?php echo esc_url( whoopstore_telegram_tool_url( 'chats' ) ); ?>">Найти чаты бота</a>
				<a class="button" href="<?php echo esc_url( whoopstore_telegram_tool_url( 'test' ) ); ?>">Отправить тестовое сообщение</a>
			</p>
			<p class="description">Кнопки работают с сохранёнными значениями: после изменения полей сначала нажмите «Сохранить изменения».</p>
		</td>
	</tr>
	<?php
} );

/**
 * Понятное объяснение ошибки Bot API.
 *
 * @param WP_Error $error Ошибка из whoopstore_telegram_api().
 * @return string
 */
function whoopstore_telegram_error_text( $error ) {
	$data    = $error->get_error_data();
	$status  = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 0;
	$message = $error->get_error_message();

	if ( 'whoopstore_telegram_no_token' === $error->get_error_code() ) {
		return 'Сначала вставьте токен бота и сохраните настройки.';
	}

	if ( 401 === $status || 404 === $status ) {
		$hint = 'Telegram не принял токен — проверьте, что он скопирован целиком.';
	} elseif ( false !== stripos( $message, 'chat not found' ) ) {
		$hint = 'Чат не найден — проверьте ID чата и что бот добавлен в этот чат.';
	} elseif ( 403 === $status ) {
		$hint = 'Бот не может писать в этот чат: его удалили из чата или заблокировали. Добавьте бота снова.';
	} elseif ( 409 === $status ) {
		$hint = 'У бота настроен webhook, поэтому поиск чатов недоступен. Впишите ID чата вручную.';
	} elseif ( 0 === $status ) {
		$hint = 'Сайт не смог связаться с Telegram.';
	} else {
		$hint = 'Telegram вернул ошибку.';
	}

	return $hint . ' Ответ: ' . $message;
}

/**
 * Чаты, в которых бот получал сообщения или куда его добавили (getUpdates).
 *
 * Telegram хранит эти события 24 часа, поэтому чат ищется сразу после того,
 * как бота добавили или ему написали.
 *
 * @param array $updates Результат getUpdates.
 * @return array[] Список [id, name, type].
 */
function whoopstore_telegram_chats_from_updates( $updates ) {
	$chats = [];

	foreach ( (array) $updates as $update ) {
		foreach ( [ 'message', 'edited_message', 'channel_post', 'edited_channel_post', 'my_chat_member', 'chat_member' ] as $key ) {
			if ( empty( $update[ $key ]['chat']['id'] ) ) {
				continue;
			}

			$chat = $update[ $key ]['chat'];
			$name = isset( $chat['title'] ) ? (string) $chat['title'] : trim( ( $chat['first_name'] ?? '' ) . ' ' . ( $chat['last_name'] ?? '' ) );

			if ( '' === $name && ! empty( $chat['username'] ) ) {
				$name = '@' . $chat['username'];
			}

			$chats[ (string) $chat['id'] ] = [
				'id'   => (string) $chat['id'],
				'name' => $name,
				'type' => (string) ( $chat['type'] ?? '' ),
			];
		}
	}

	return array_values( $chats );
}

add_action( 'admin_post_whoopstore_telegram_tool', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Недостаточно прав.', '', [ 'response' => 403 ] );
	}

	check_admin_referer( 'whoopstore_telegram_tool' );

	$tool   = isset( $_GET['tool'] ) ? sanitize_key( wp_unslash( $_GET['tool'] ) ) : '';
	$result = [ 'type' => 'error', 'text' => 'Неизвестное действие.' ];

	if ( 'test' === $tool ) {
		if ( '' === whoopstore_telegram_credentials()['chat_id'] ) {
			$result['text'] = 'Сначала выберите чат: «Найти чаты бота» → «Выбрать».';
		} else {
			$sent   = whoopstore_telegram_send( 'Проверка связи: уведомления о заказах с сайта ' . wp_parse_url( home_url(), PHP_URL_HOST ) . ' будут приходить в этот чат.' );
			$result = is_wp_error( $sent )
				? [ 'type' => 'error', 'text' => whoopstore_telegram_error_text( $sent ) ]
				: [ 'type' => 'success', 'text' => 'Тестовое сообщение отправлено — проверьте чат.' ];
		}
	} elseif ( 'chats' === $tool ) {
		$updates = whoopstore_telegram_api( 'getUpdates', [ 'limit' => 100 ] );

		if ( is_wp_error( $updates ) ) {
			$result['text'] = whoopstore_telegram_error_text( $updates );
		} else {
			$chats  = whoopstore_telegram_chats_from_updates( $updates );
			$result = $chats
				? [ 'type' => 'success', 'text' => 'Найдено чатов: ' . count( $chats ) . '. Выберите чат для уведомлений.', 'chats' => $chats ]
				: [ 'type' => 'error', 'text' => 'Бот пока не видит ни одного чата. Добавьте бота в чат (или напишите ему /start), затем нажмите «Найти чаты бота» ещё раз.' ];
		}
	} elseif ( 'use' === $tool && ! defined( 'WHOOPSTORE_TELEGRAM_CHAT_ID' ) ) {
		$chat = isset( $_GET['chat'] ) ? sanitize_text_field( wp_unslash( $_GET['chat'] ) ) : '';

		if ( preg_match( '/^-?\d+$/', $chat ) ) {
			update_option( 'whoopstore_telegram_chat_id', $chat );
			$result = [ 'type' => 'success', 'text' => 'Чат ' . $chat . ' выбран. Нажмите «Отправить тестовое сообщение».' ];
		} else {
			$result['text'] = 'Неверный ID чата.';
		}
	}

	set_transient( 'whoopstore_telegram_tool_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
	wp_safe_redirect( whoopstore_telegram_settings_url() );
	exit;
} );
