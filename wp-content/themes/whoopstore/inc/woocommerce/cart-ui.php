<?php
/**
 * Корзина в оформлении Figma: кнопка-корзина в шапке со счётчиком,
 * степпер "В корзине: − 1 +" на странице товара, тост, выезжающий drawer
 * и модалка "Заказ оформлен"
 * (Figma 30:283/30:1423, 30:438/30:1719, 30:841/30:1747, 30:619/30:1759,
 * 30:1006/30:1564).
 *
 * Данные корзины берём через WooCommerce Store API (/wp-json/wc/store/v1/cart)
 * — штатный REST-интерфейс корзины, тот же, которым пользуются собственные
 * блоки WooCommerce. Своих AJAX-ручек и своей модели корзины не заводим
 * (правило 7 CLAUDE.md): вся коммерческая логика (наличие, цены, купоны,
 * налоги) остаётся на стороне WooCommerce.
 *
 * Разметка drawer'а и модалки печатается в футер один раз на страницу —
 * это общие оверлеи, а не часть какой-то секции.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Степпер "В корзине: − 1 +" вместо кнопки "Добавить в корзину".
 *
 * Печатается скрытым рядом с кнопкой; переключение кнопка ↔ степпер — в
 * assets/js/cart.js, потому что зависит от выбранной вариации, которая
 * меняется без перезагрузки страницы.
 */
function whoopstore_cart_stepper() {
	?>
	<div class="cart-stepper" hidden>
		<span class="cart-stepper__label">В корзине:</span>
		<button type="button" class="cart-stepper__button cart-stepper__button--minus" aria-label="Уменьшить количество"></button>
		<span class="cart-stepper__value" aria-live="polite">1</span>
		<button type="button" class="cart-stepper__button cart-stepper__button--plus" aria-label="Увеличить количество"></button>
	</div>
	<?php
}
add_action( 'woocommerce_after_add_to_cart_button', 'whoopstore_cart_stepper' );

/**
 * Подключение скриптов и данных корзины.
 *
 * Скрипт нужен на каждой странице: кнопка-корзина в шапке и drawer —
 * общесайтовые элементы.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'WC' ) ) {
		return;
	}

	wp_enqueue_script(
		'whoopstore-cart',
		get_theme_file_uri( 'assets/js/cart.js' ),
		[],
		whoopstore_asset_version( 'assets/js/cart.js' ),
		true
	);

	// Зависит от whoopstore-cart: пользуется общим window.whoopstoreCart.
	wp_enqueue_script(
		'whoopstore-cart-drawer',
		get_theme_file_uri( 'assets/js/cart-drawer.js' ),
		[ 'whoopstore-cart' ],
		whoopstore_asset_version( 'assets/js/cart-drawer.js' ),
		true
	);

	wp_localize_script( 'whoopstore-cart', 'whoopstoreCartData', [
		// Nonce того же действия, что проверяет Store API
		// (WooCommerce\StoreApi\Routes\V1\AbstractCartRoute::check_nonce).
		'nonce' => wp_create_nonce( 'wc_store_api' ),
		'root'  => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
	] );
}, 20 );

/**
 * Оверлеи корзины: drawer «Корзина» / «Ваш заказ» и модалка «Заказ оформлен».
 *
 * Печатаются один раз на страницу в подвале — это общие слои поверх сайта,
 * а не часть какой-то секции. Тексты живут здесь (а не в JS), чтобы их можно
 * было переводить и вычитывать в PHP; наполнение списка товаров и сумм —
 * assets/js/cart-drawer.js по данным Store API.
 *
 * Форма «Ваш заказ» оформляет настоящий заказ WooCommerce (2026-09-15):
 * способы доставки и суммы приходят из корзины Store API, форма уходит в
 * POST /wc/store/v1/checkout — assets/js/cart-drawer.js. Поля, которых нет в
 * штатной схеме (Telegram, согласие с политикой), серверные проверки,
 * подписи способов доставки и уведомление в Telegram живут не в теме, а в
 * mu-plugin whoopstore-commerce: это данные заказа, они не должны зависеть
 * от темы. Плитки доставки здесь не печатаются — их строит скрипт из
 * настроенных в WooCommerce способов.
 */
function whoopstore_cart_overlays() {
	if ( is_admin() ) {
		return;
	}

	// Поля адреса в порядке из макета. half — половина строки («Дом» и
	// «Квартира / офис», node 30:799/30:802); required — обязательно, когда
	// адрес вообще нужен (доставка по адресу). При самовывозе и заказе из
	// одних виртуальных товаров скрипт прячет эти поля и снимает required.
	$address_fields = [
		[ 'name' => 'city', 'placeholder' => 'Город', 'autocomplete' => 'address-level2', 'half' => false, 'required' => true ],
		[ 'name' => 'postcode', 'placeholder' => 'Индекс', 'autocomplete' => 'postal-code', 'half' => false, 'required' => true ],
		[ 'name' => 'street', 'placeholder' => 'Улица', 'autocomplete' => 'address-line1', 'half' => false, 'required' => true ],
		[ 'name' => 'house', 'placeholder' => 'Дом', 'autocomplete' => 'off', 'half' => true, 'required' => true ],
		[ 'name' => 'apartment', 'placeholder' => 'Квартира / офис', 'autocomplete' => 'address-line2', 'half' => true, 'required' => false ],
	];
	?>
	<div class="cart-overlay" hidden></div>

	<aside class="cart-drawer" role="dialog" aria-modal="true" aria-label="Корзина" hidden>
		<button type="button" class="cart-drawer__close" aria-label="Закрыть"></button>

		<div class="cart-drawer__view cart-drawer__view--cart">
			<div class="cart-drawer__head">
				<h2 class="cart-drawer__title">Корзина</h2>
				<button type="button" class="cart-drawer__clear">Очистить корзину</button>
			</div>

			<div class="cart-drawer__body">
				<div class="cart-drawer__items"></div>
				<p class="cart-drawer__empty cart-plate" hidden>Корзина пока пуста</p>
			</div>

			<div class="cart-drawer__footer">
				<p class="cart-total">
					<span>Итого</span>
					<span class="cart-total__value">₽ 0</span>
				</p>
				<button type="button" class="cart-button cart-button--primary cart-drawer__checkout">Оформить заказ</button>
				<button type="button" class="cart-button cart-button--ghost cart-drawer__continue">Продолжить покупки</button>
			</div>
		</div>

		<div class="cart-drawer__view cart-drawer__view--checkout" hidden>
			<div class="cart-drawer__head">
				<h2 class="cart-drawer__title">Ваш заказ</h2>
			</div>

			<form class="checkout-form" novalidate>
				<div class="checkout-form__summary">
					<div class="checkout-form__items"></div>
				</div>

				<p class="checkout-form__label">Данные заказа</p>

				<div class="checkout-field">
					<input type="text" class="checkout-field__input" name="full_name" placeholder="ФИО" autocomplete="name" required />
				</div>

				<?php /* Телефон: флаг РФ + «+7» внутри поля (node 30:815/30:816). */ ?>
				<div class="checkout-field checkout-field--phone">
					<span class="checkout-field__flag" aria-hidden="true"></span>
					<span class="checkout-field__caret" aria-hidden="true"></span>
					<span class="checkout-field__prefix">+7</span>
					<input type="tel" class="checkout-field__input" name="phone" placeholder="(000) 000-00-00" autocomplete="tel-national" inputmode="tel" required />
				</div>

				<div class="checkout-field">
					<input type="email" class="checkout-field__input" name="email" placeholder="E-mail" autocomplete="email" required />
				</div>

				<div class="checkout-field">
					<input type="text" class="checkout-field__input" name="telegram" placeholder="Telegram" autocomplete="off" />
				</div>

				<?php /* Плитки доставки строит скрипт из тарифов корзины; у заказа из
				        одних виртуальных товаров блок остаётся скрытым. */ ?>
				<p class="checkout-form__label checkout-form__label--shipping" hidden>Доставка</p>

				<div class="checkout-shipping" role="radiogroup" aria-label="Способ доставки" hidden></div>

				<div class="checkout-form__address">
					<?php foreach ( $address_fields as $field ) : ?>
						<div class="checkout-field<?php echo $field['half'] ? ' checkout-field--half' : ''; ?>" data-address-field hidden>
							<input type="text" class="checkout-field__input" name="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" autocomplete="<?php echo esc_attr( $field['autocomplete'] ); ?>"<?php echo 'postcode' === $field['name'] ? ' inputmode="numeric"' : ''; ?><?php echo $field['required'] ? ' data-required' : ''; ?> />
						</div>
					<?php endforeach; ?>

					<div class="checkout-field">
						<input type="text" class="checkout-field__input" name="customer_note" placeholder="Комментарий к заказу" autocomplete="off" />
					</div>
				</div>

				<label class="checkout-consent">
					<input type="checkbox" class="checkout-consent__input" name="privacy_consent" required />
					<span class="checkout-consent__box" aria-hidden="true"></span>
					<?php
					// Ссылка на политику появляется сама, когда страница
					// политики опубликована (Настройки → Конфиденциальность).
					// Пока это черновик, ссылки нет: вести покупателя на 404
					// хуже, чем оставить текст как в макете.
					$privacy_url = get_privacy_policy_url();
					$consent     = 'Я согласен(-а) с ';
					$consent    .= $privacy_url
						? '<a class="checkout-consent__link" href="' . esc_url( $privacy_url ) . '" target="_blank" rel="noopener">политикой конфиденциальности</a>'
						: 'политикой конфиденциальности';
					?>
					<span class="checkout-consent__text"><?php echo wp_kses_post( $consent ); ?></span>
				</label>

				<div class="checkout-totals">
					<p class="checkout-totals__row"><span>Товары</span><span class="checkout-totals__items">₽ 0</span></p>
					<p class="checkout-totals__row checkout-totals__row--shipping" hidden><span>Доставка</span><span class="checkout-totals__shipping">₽ 0</span></p>
					<p class="checkout-totals__row checkout-totals__row--delivery" hidden><span>Срок доставки</span><span class="checkout-totals__delivery"></span></p>
					<p class="checkout-totals__row checkout-totals__row--grand"><span>Итого</span><span class="checkout-totals__grand">₽ 0</span></p>
				</div>

				<p class="checkout-form__error" role="alert" hidden></p>

				<button type="submit" class="cart-button cart-button--primary">Перейти к оплате</button>
			</form>
		</div>
	</aside>

	<?php $paid_order_number = whoopstore_returned_order_number(); ?>
	<div class="order-modal" role="dialog" aria-modal="true" aria-labelledby="whoopstore-order-modal-title"<?php echo '' !== $paid_order_number ? ' data-placed-order="' . esc_attr( $paid_order_number ) . '"' : ''; ?> hidden>
		<div class="order-modal__window">
			<button type="button" class="cart-drawer__close order-modal__close" aria-label="Закрыть"></button>

			<h2 class="order-modal__title" id="whoopstore-order-modal-title">Заказ оформлен</h2>

			<p class="order-modal__text">Спасибо за покупку! Ваш заказ успешно принят. Мы уже получили данные заказа и скоро начнём его обработку. Информация о статусе и доставке будет доступна после оформления.</p>

			<p class="cart-plate order-modal__number">
				<span>Номер заказа</span>
				<?php /* Номер подставляет скрипт из ответа оформления заказа или из data-placed-order. */ ?>
				<span class="order-modal__number-value"></span>
			</p>

			<button type="button" class="cart-button cart-button--primary order-modal__close">Продолжить покупки</button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'whoopstore_cart_overlays' );

/**
 * Возврат из ЮKassa после успешной оплаты — на Главную с модалкой
 * «Заказ оформлен» (Figma 30:1006/30:1564).
 *
 * Экрана «Заказ получен» в макете нет: конечная точка оформления — эта
 * модалка. При оплате без перехода её открывает скрипт по ответу оформления,
 * а при оплате в ЮKassa покупатель уходит с сайта. Плагин ЮKassa, проверив
 * платёж, отправляет его на «страницу успеха» — в настройках выбрана штатная
 * «Заказ получен», адрес которой берётся из
 * WC_Order::get_checkout_order_received_url(). Здесь этот адрес подменяется
 * только на запросе возврата из ЮKassa (query var yookassa-order-id с ключом
 * этого же заказа) — остальные ссылки на «Заказ получен» не меняются.
 * Без темы останется штатная страница WooCommerce.
 *
 * @param string   $url   Адрес страницы «Заказ получен».
 * @param WC_Order $order Заказ.
 * @return string
 */
add_filter( 'woocommerce_get_checkout_order_received_url', function ( $url, $order ) {
	$return_key = (string) get_query_var( 'yookassa-order-id' );

	if ( '' === $return_key || ! $order instanceof WC_Order
		|| ! hash_equals( $order->get_order_key(), wc_clean( wp_unslash( $return_key ) ) ) ) {
		return $url;
	}

	return add_query_arg(
		[
			'order-placed' => $order->get_id(),
			'key'          => $order->get_order_key(),
		],
		home_url( '/' )
	);
}, 10, 2 );

/**
 * Номер заказа для модалки после возврата из оплаты.
 *
 * Номер отдаётся, только если ключ в адресе совпал с ключом заказа: по одному
 * id из адреса чужой номер не показать.
 *
 * @return string Номер заказа или пустая строка.
 */
function whoopstore_returned_order_number() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- ссылка из редиректа, проверка — ключ заказа.
	if ( empty( $_GET['order-placed'] ) || empty( $_GET['key'] ) ) {
		return '';
	}

	$order = wc_get_order( absint( $_GET['order-placed'] ) );
	$key   = wc_clean( wp_unslash( $_GET['key'] ) );
	// phpcs:enable

	if ( ! $order instanceof WC_Order || ! is_string( $key ) || ! hash_equals( $order->get_order_key(), $key ) ) {
		return '';
	}

	return (string) $order->get_order_number();
}
