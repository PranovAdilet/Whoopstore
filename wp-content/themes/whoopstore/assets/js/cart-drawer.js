/**
 * Выезжающая корзина, форма чекаута и модалка "Заказ оформлен"
 * (Figma 30:438/30:1719, 30:841/30:1747, 30:619/30:1759, 30:1006/30:1564).
 *
 * Разметка — inc/woocommerce/cart-ui.php (печатается в подвал), данные —
 * общий модуль window.whoopstoreCart поверх WooCommerce Store API
 * (assets/js/cart.js). Здесь только отрисовка и переключение состояний.
 *
 * Чекаут — вёрстка по макету: форма ничего не отправляет и заказ не создаёт,
 * кнопка "Перейти к оплате" просто показывает модалку "Заказ оформлен"
 * (подключение к WooCommerce checkout — отдельная согласованная задача,
 * см. шапку inc/woocommerce/cart-ui.php).
 */
( function () {
	var cart = window.whoopstoreCart;
	var drawer = document.querySelector( '.cart-drawer' );
	var overlay = document.querySelector( '.cart-overlay' );
	var modal = document.querySelector( '.order-modal' );

	if ( ! cart || ! drawer || ! overlay || ! modal ) {
		return;
	}

	var views = {
		cart: drawer.querySelector( '.cart-drawer__view--cart' ),
		checkout: drawer.querySelector( '.cart-drawer__view--checkout' ),
	};

	var itemsBox = drawer.querySelector( '.cart-drawer__items' );
	var emptyBox = drawer.querySelector( '.cart-drawer__empty' );
	var clearLink = drawer.querySelector( '.cart-drawer__clear' );
	var totalValue = drawer.querySelector( '.cart-total__value' );
	var checkoutButton = drawer.querySelector( '.cart-drawer__checkout' );
	var checkoutItems = drawer.querySelector( '.checkout-form__items' );
	var checkoutForm = drawer.querySelector( '.checkout-form' );
	var totalsItems = drawer.querySelector( '.checkout-totals__items' );
	var totalsShipping = drawer.querySelector( '.checkout-totals__shipping' );
	var totalsGrand = drawer.querySelector( '.checkout-totals__grand' );

	/* ---------- Форматирование цен ---------- */

	/**
	 * Store API отдаёт суммы целыми числами в минорных единицах вместе с
	 * настройками валюты магазина, поэтому формат собираем из ответа, а не
	 * зашиваем. Порядок символа — как в макете ("₽ 24 990,00"), он там
	 * отличается от карточки товара ("24 990,00 ₽").
	 */
	function formatPrice( amount, totals ) {
		var minor = totals && typeof totals.currency_minor_unit === 'number' ? totals.currency_minor_unit : 2;
		var thousands = totals && totals.currency_thousand_separator ? totals.currency_thousand_separator : ' ';
		var decimal = totals && totals.currency_decimal_separator ? totals.currency_decimal_separator : ',';
		var symbol = totals && totals.currency_symbol ? totals.currency_symbol : '';
		var value = ( parseInt( amount, 10 ) || 0 ) / Math.pow( 10, minor );
		var parts = value.toFixed( minor ).split( '.' );

		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, thousands );

		return ( symbol + ' ' + parts.join( decimal ) ).trim();
	}

	/* ---------- Открытие/закрытие ---------- */

	function showView( name ) {
		Object.keys( views ).forEach( function ( key ) {
			views[ key ].hidden = key !== name;
		} );

		drawer.setAttribute( 'aria-label', 'checkout' === name ? 'Ваш заказ' : 'Корзина' );
	}

	function open( view ) {
		showView( view || 'cart' );
		drawer.hidden = false;
		overlay.hidden = false;
		document.body.classList.add( 'has-cart-drawer' );
	}

	function close() {
		drawer.hidden = true;
		overlay.hidden = true;
		document.body.classList.remove( 'has-cart-drawer' );
	}

	function closeModal() {
		modal.hidden = true;

		if ( drawer.hidden ) {
			document.body.classList.remove( 'has-cart-drawer' );
		}
	}

	/* ---------- Отрисовка корзины ---------- */

	function buildStepper( item ) {
		var stepper = document.createElement( 'div' );
		stepper.className = 'cart-stepper cart-stepper--plain';

		var minus = document.createElement( 'button' );
		minus.type = 'button';
		minus.className = 'cart-stepper__button cart-stepper__button--minus';
		minus.setAttribute( 'aria-label', 'Уменьшить количество' );
		minus.addEventListener( 'click', function () {
			if ( item.quantity <= 1 ) {
				cart.removeItem( item.key );
				return;
			}

			cart.updateItem( item.key, item.quantity - 1 );
		} );

		var value = document.createElement( 'span' );
		value.className = 'cart-stepper__value';
		value.textContent = item.quantity;

		var plus = document.createElement( 'button' );
		plus.type = 'button';
		plus.className = 'cart-stepper__button cart-stepper__button--plus';
		plus.setAttribute( 'aria-label', 'Увеличить количество' );
		plus.addEventListener( 'click', function () {
			cart.updateItem( item.key, item.quantity + 1 );
		} );

		stepper.appendChild( minus );
		stepper.appendChild( value );
		stepper.appendChild( plus );

		return stepper;
	}

	/**
	 * Строка вариации из ответа Store API ("Размер: S").
	 */
	function variationText( item ) {
		if ( ! item.variation || ! item.variation.length ) {
			return '';
		}

		return item.variation
			.map( function ( pair ) {
				return pair.attribute + ': ' + pair.value;
			} )
			.join( ', ' );
	}

	function renderCart( state ) {
		var items = ( state && state.items ) || [];

		itemsBox.textContent = '';

		items.forEach( function ( item ) {
			var row = document.createElement( 'div' );
			row.className = 'cart-item cart-plate';

			var info = document.createElement( 'div' );
			info.className = 'cart-item__info';

			var title = document.createElement( 'p' );
			title.className = 'cart-item__title';
			title.textContent = item.name;
			info.appendChild( title );

			var meta = variationText( item );

			if ( meta ) {
				var metaEl = document.createElement( 'p' );
				metaEl.className = 'cart-item__meta';
				metaEl.textContent = meta;
				info.appendChild( metaEl );
			}

			row.appendChild( info );
			row.appendChild( buildStepper( item ) );
			itemsBox.appendChild( row );
		} );

		// Пустая корзина: плашка "Корзина пока пуста", без ссылки
		// "Очистить корзину" и без кнопки "Оформить заказ" (node 30:841).
		var isEmpty = ! items.length;

		emptyBox.hidden = ! isEmpty;
		clearLink.hidden = isEmpty;
		checkoutButton.hidden = isEmpty;

		totalValue.textContent = state ? formatPrice( state.totals.total_price, state.totals ) : '';
	}

	function renderCheckout( state ) {
		var items = ( state && state.items ) || [];

		checkoutItems.textContent = '';

		items.forEach( function ( item ) {
			var row = document.createElement( 'div' );
			row.className = 'checkout-summary';

			var head = document.createElement( 'div' );
			head.className = 'checkout-summary__row';

			var name = document.createElement( 'p' );
			name.className = 'checkout-summary__name';
			name.textContent = item.name + ' × ' + item.quantity;

			var price = document.createElement( 'p' );
			price.className = 'checkout-summary__price';
			price.textContent = formatPrice( item.totals.line_total, state.totals );

			head.appendChild( name );
			head.appendChild( price );
			row.appendChild( head );

			var meta = variationText( item );

			if ( meta ) {
				var metaEl = document.createElement( 'p' );
				metaEl.className = 'checkout-summary__meta';
				metaEl.textContent = meta;
				row.appendChild( metaEl );
			}

			checkoutItems.appendChild( row );
		} );

		if ( ! state ) {
			return;
		}

		var itemsTotal = parseInt( state.totals.total_items, 10 ) || 0;
		// Доставка — статическая величина из макета: настроенных shipping
		// methods в магазине пока нет (см. шапку inc/woocommerce/cart-ui.php).
		var shipping = 100 * Math.pow( 10, state.totals.currency_minor_unit );

		totalsItems.textContent = formatPrice( itemsTotal, state.totals );
		totalsShipping.textContent = formatPrice( shipping, state.totals );
		totalsGrand.textContent = formatPrice( itemsTotal + shipping, state.totals );
	}

	/* ---------- События ---------- */

	document.addEventListener( 'whoopstore-cart-open', function () {
		open( 'cart' );
	} );

	drawer.querySelector( '.cart-drawer__close' ).addEventListener( 'click', close );
	drawer.querySelector( '.cart-drawer__continue' ).addEventListener( 'click', close );
	overlay.addEventListener( 'click', close );

	clearLink.addEventListener( 'click', function () {
		cart.clear();
	} );

	checkoutButton.addEventListener( 'click', function () {
		open( 'checkout' );
	} );

	checkoutForm.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		close();
		modal.hidden = false;
		document.body.classList.add( 'has-cart-drawer' );
	} );

	Array.prototype.forEach.call( modal.querySelectorAll( '.order-modal__close' ), function ( button ) {
		button.addEventListener( 'click', closeModal );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' !== event.key ) {
			return;
		}

		if ( ! modal.hidden ) {
			closeModal();
			return;
		}

		if ( ! drawer.hidden ) {
			close();
		}
	} );

	cart.subscribe( function ( state ) {
		renderCart( state );
		renderCheckout( state );
	} );
} )();
