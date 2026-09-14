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
 * ВАЖНО (согласовано 2026-09-11): форма чекаута — ВЁРСТКА ПО МАКЕТУ.
 * Она не подключена к WooCommerce checkout, не создаёт заказ и не проводит
 * оплату; способы доставки и их цены — статические значения из Figma, а не
 * настроенные shipping methods. Штатный /checkout/ не тронут. Подключение
 * к WooCommerce (поля billing/shipping, СДЭК/ПВЗ/экспресс/самовывоз,
 * YooMoney) — отдельная задача, см. открытые вопросы в
 * docs/figma-design/states-and-components.md.
 */
function whoopstore_cart_overlays() {
	if ( is_admin() ) {
		return;
	}

	// Способы доставки из макета (node 30:818…30:825). Порядок один и тот же
	// на desktop (сетка 2×2) и mobile (4 подряд) — раскладку задаёт CSS.
	$shipping_options = [
		[ 'title' => 'Доставка курьером', 'text' => 'СДЭК доставит заказ по адресу' ],
		[ 'title' => 'ПВЗ CDEK', 'text' => 'Выберите пункт выдачи на карте' ],
		[ 'title' => 'Экспресс доставка', 'text' => '1 500 ₽ · день в день по Москве' ],
		[ 'title' => 'Самовывоз', 'text' => 'Бесплатно: Москва, ул. Олеко Дундича, 3' ],
	];

	// Поля адреса: подпись => модификатор ширины (половина строки — «Дом» и
	// «Квартира / офис», node 30:799/30:802).
	$address_fields = [
		[ 'placeholder' => 'Город', 'half' => false ],
		[ 'placeholder' => 'Индекс', 'half' => false ],
		[ 'placeholder' => 'Улица', 'half' => false ],
		[ 'placeholder' => 'Дом', 'half' => true ],
		[ 'placeholder' => 'Квартира / офис', 'half' => true ],
		[ 'placeholder' => 'Комментарий к заказу', 'half' => false ],
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
					<input type="text" class="checkout-field__input" name="whoopstore_name" placeholder="ФИО" autocomplete="name" />
				</div>

				<?php /* Телефон: флаг РФ + «+7» внутри поля (node 30:815/30:816). */ ?>
				<div class="checkout-field checkout-field--phone">
					<span class="checkout-field__flag" aria-hidden="true"></span>
					<span class="checkout-field__caret" aria-hidden="true"></span>
					<span class="checkout-field__prefix">+7</span>
					<input type="tel" class="checkout-field__input" name="whoopstore_phone" placeholder="(000) 000-00-00" autocomplete="tel" />
				</div>

				<div class="checkout-field">
					<input type="email" class="checkout-field__input" name="whoopstore_email" placeholder="E-mail" autocomplete="email" />
				</div>

				<div class="checkout-field">
					<input type="text" class="checkout-field__input" name="whoopstore_telegram" placeholder="Telegram" />
				</div>

				<p class="checkout-form__label checkout-form__label--shipping">Доставка</p>

				<div class="checkout-shipping">
					<?php foreach ( $shipping_options as $index => $option ) : ?>
						<label class="checkout-shipping__option">
							<input type="radio" name="whoopstore_shipping" value="<?php echo esc_attr( $index ); ?>" <?php checked( 0, $index ); ?> />
							<span class="checkout-shipping__tile">
								<span class="checkout-shipping__title"><?php echo esc_html( $option['title'] ); ?></span>
								<span class="checkout-shipping__text"><?php echo esc_html( $option['text'] ); ?></span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>

				<div class="checkout-form__address">
					<?php foreach ( $address_fields as $field ) : ?>
						<div class="checkout-field<?php echo $field['half'] ? ' checkout-field--half' : ''; ?>">
							<input type="text" class="checkout-field__input" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" />
						</div>
					<?php endforeach; ?>
				</div>

				<label class="checkout-consent">
					<input type="checkbox" class="checkout-consent__input" />
					<span class="checkout-consent__box" aria-hidden="true"></span>
					<span class="checkout-consent__text">Я согласен(-а) с политикой конфиденциальности</span>
				</label>

				<div class="checkout-totals">
					<p class="checkout-totals__row"><span>Товары</span><span class="checkout-totals__items">₽ 0</span></p>
					<?php /* Значения из макета: реальных shipping methods в магазине ещё нет. */ ?>
					<p class="checkout-totals__row"><span>Доставка</span><span class="checkout-totals__shipping">₽ 100,00</span></p>
					<p class="checkout-totals__row"><span>Срок доставки</span><span>1-2 дня</span></p>
					<p class="checkout-totals__row checkout-totals__row--grand"><span>Итого</span><span class="checkout-totals__grand">₽ 0</span></p>
				</div>

				<button type="submit" class="cart-button cart-button--primary">Перейти к оплате</button>
			</form>
		</div>
	</aside>

	<div class="order-modal" role="dialog" aria-modal="true" aria-labelledby="whoopstore-order-modal-title" hidden>
		<div class="order-modal__window">
			<button type="button" class="cart-drawer__close order-modal__close" aria-label="Закрыть"></button>

			<h2 class="order-modal__title" id="whoopstore-order-modal-title">Заказ оформлен</h2>

			<p class="order-modal__text">Спасибо за покупку! Ваш заказ успешно принят. Мы уже получили данные заказа и скоро начнём его обработку. Информация о статусе и доставке будет доступна после оформления.</p>

			<p class="cart-plate order-modal__number">
				<span>Номер заказа</span>
				<?php /* Заглушка из макета: заказ здесь не создаётся (см. шапку файла). */ ?>
				<span class="order-modal__number-value">48291</span>
			</p>

			<button type="button" class="cart-button cart-button--primary order-modal__close">Продолжить покупки</button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'whoopstore_cart_overlays' );
