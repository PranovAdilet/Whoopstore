/**
 * Выезжающая корзина, форма оформления заказа и модалка "Заказ оформлен"
 * (Figma 30:438/30:1719, 30:841/30:1747, 30:619/30:1759, 30:1006/30:1564).
 *
 * Разметка — inc/woocommerce/cart-ui.php (печатается в подвал), данные —
 * общий модуль window.whoopstoreCart поверх WooCommerce Store API
 * (assets/js/cart.js).
 *
 * Оформление заказа — штатный WooCommerce: способы доставки и суммы приходят
 * из корзины Store API, форма уходит в POST /wc/store/v1/checkout, заказ
 * создаёт WooCommerce. Поля вне штатной схемы (Telegram, согласие) и
 * серверные проверки — mu-plugin whoopstore-commerce.
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
	var shippingLabel = drawer.querySelector( '.checkout-form__label--shipping' );
	var shippingBox = drawer.querySelector( '.checkout-shipping' );
	var addressFields = drawer.querySelectorAll( '[data-address-field]' );
	var totalsItems = drawer.querySelector( '.checkout-totals__items' );
	var shippingRow = drawer.querySelector( '.checkout-totals__row--shipping' );
	var totalsShipping = drawer.querySelector( '.checkout-totals__shipping' );
	var deliveryRow = drawer.querySelector( '.checkout-totals__row--delivery' );
	var totalsDelivery = drawer.querySelector( '.checkout-totals__delivery' );
	var totalsGrand = drawer.querySelector( '.checkout-totals__grand' );
	var errorBox = drawer.querySelector( '.checkout-form__error' );
	var submitButton = checkoutForm.querySelector( '[type="submit"]' );
	var orderNumber = modal.querySelector( '.order-modal__number-value' );

	// Последнее состояние корзины и способ доставки, выбранный покупателем, но
	// ещё не подтверждённый сервером (см. selectRate).
	var currentState = null;
	var pendingRateId = '';
	var rateRequestToken = 0;

	/* ---------- Форматирование ---------- */

	function currency( totals ) {
		return {
			minor: totals && typeof totals.currency_minor_unit === 'number' ? totals.currency_minor_unit : 2,
			thousands: totals && totals.currency_thousand_separator ? totals.currency_thousand_separator : ' ',
			decimal: totals && totals.currency_decimal_separator ? totals.currency_decimal_separator : ',',
			symbol: totals && totals.currency_symbol ? totals.currency_symbol : '',
		};
	}

	function formatNumber( amount, format, digits ) {
		var value = ( parseInt( amount, 10 ) || 0 ) / Math.pow( 10, format.minor );
		var parts = value.toFixed( digits ).split( '.' );

		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, format.thousands );

		return parts.join( format.decimal );
	}

	/**
	 * Store API отдаёт суммы целыми числами в минорных единицах вместе с
	 * настройками валюты магазина, поэтому формат собираем из ответа, а не
	 * зашиваем. Порядок символа — как в макете ("₽ 24 990,00"), он там
	 * отличается от карточки товара ("24 990,00 ₽").
	 */
	function formatPrice( amount, totals ) {
		var format = currency( totals );

		return ( format.symbol + ' ' + formatNumber( amount, format, format.minor ) ).trim();
	}

	/**
	 * Цена на плитке доставки — как в макете: "1 500 ₽", без копеек у целой
	 * суммы и с символом после числа.
	 */
	function formatTilePrice( amount, totals ) {
		var format = currency( totals );
		var whole = ( parseInt( amount, 10 ) || 0 ) % Math.pow( 10, format.minor ) === 0;

		return ( formatNumber( amount, format, whole ? 0 : format.minor ) + ' ' + format.symbol ).trim();
	}

	/**
	 * Строковые поля Store API (названия, описания тарифов) приходят
	 * HTML-экранированными — в textContent нужен обычный текст.
	 */
	function decodeHtml( html ) {
		return new window.DOMParser().parseFromString( String( html || '' ), 'text/html' ).documentElement.textContent;
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
		delete modal.dataset.order;

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
			title.textContent = decodeHtml( item.name );
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

	/* ---------- Доставка ---------- */

	// В корзине WooCommerce один «пакет» доставки на все товары — берём его.
	function shippingPackage( state ) {
		return state && state.shipping_rates && state.shipping_rates.length ? state.shipping_rates[ 0 ] : null;
	}

	function findRate( state, match ) {
		var pack = shippingPackage( state );
		var found = null;

		if ( pack ) {
			pack.shipping_rates.forEach( function ( rate ) {
				if ( match( rate ) ) {
					found = rate;
				}
			} );
		}

		return found;
	}

	function selectedRate( state ) {
		return findRate( state, function ( rate ) {
			return !! rate.selected;
		} );
	}

	/**
	 * Способ доставки, который сейчас должен видеть покупатель: выбранный им,
	 * даже если сервер ещё не подтвердил выбор (см. selectRate), иначе —
	 * выбранный на сервере.
	 */
	function effectiveRate( state ) {
		var pending = pendingRateId
			? findRate( state, function ( rate ) {
				return rate.rate_id === pendingRateId;
			} )
			: null;

		return pending || selectedRate( state );
	}

	// Штатный «Самовывоз» WooCommerce: адрес покупателя не нужен.
	function isPickup( rate ) {
		return !! rate && 'local_pickup' === rate.method_id;
	}

	function needsAddress( state ) {
		return !! ( state && state.needs_shipping ) && ! isPickup( effectiveRate( state ) );
	}

	/**
	 * Вторая строка плитки, как в макете: "1 500 ₽ · день в день по Москве",
	 * "Бесплатно: Москва, ул. Олеко Дундича, 3". Цена — из тарифа, текст — из
	 * подписи способа доставки (настройка в зоне доставки WooCommerce).
	 */
	function rateCaption( rate, totals ) {
		var description = decodeHtml( rate.description );
		var free = 0 === ( parseInt( rate.price, 10 ) || 0 );

		if ( free ) {
			return description ? 'Бесплатно: ' + description : 'Бесплатно';
		}

		return formatTilePrice( rate.price, totals ) + ( description ? ' · ' + description : '' );
	}

	/**
	 * Выбор способа доставки.
	 *
	 * Плитку и поля адреса переключаем сразу, не дожидаясь ответа Store API:
	 * иначе, пока ответ идёт, покупатель видит рассинхрон — выбран самовывоз, а
	 * поля адреса и сумма доставки ещё от прошлого способа (QA 2026-09-16).
	 * Суммы уточняет ответ сервера. Токен отбрасывает ответы на устаревшие
	 * клики, если способ переключили несколько раз подряд.
	 */
	function selectRate( packageId, rateId ) {
		var token = ++rateRequestToken;

		hideError();
		pendingRateId = rateId;
		renderCheckout( currentState );

		cart.selectShippingRate( packageId, rateId )
			.catch( function ( error ) {
				if ( token === rateRequestToken ) {
					showError( error.message );
					cart.refresh().catch( function () {} );
				}
			} )
			.then( function () {
				if ( token !== rateRequestToken ) {
					return;
				}

				pendingRateId = '';
				renderCheckout( currentState );
			} );
	}

	function renderShipping( state ) {
		var visible = !! ( state && state.needs_shipping );
		var pack = shippingPackage( state );

		shippingLabel.hidden = ! visible;
		shippingBox.hidden = ! visible;
		shippingBox.textContent = '';

		if ( visible && pack ) {
			pack.shipping_rates.forEach( function ( rate ) {
				var option = document.createElement( 'label' );
				option.className = 'checkout-shipping__option';

				var input = document.createElement( 'input' );
				input.type = 'radio';
				input.name = 'shipping_rate';
				input.value = rate.rate_id;
				input.checked = pendingRateId ? rate.rate_id === pendingRateId : !! rate.selected;
				input.addEventListener( 'change', function () {
					selectRate( pack.package_id, rate.rate_id );
				} );

				var tile = document.createElement( 'span' );
				tile.className = 'checkout-shipping__tile';

				var title = document.createElement( 'span' );
				title.className = 'checkout-shipping__title';
				title.textContent = decodeHtml( rate.name );

				var text = document.createElement( 'span' );
				text.className = 'checkout-shipping__text';
				text.textContent = rateCaption( rate, state.totals );

				tile.appendChild( title );
				tile.appendChild( text );
				option.appendChild( input );
				option.appendChild( tile );
				shippingBox.appendChild( option );
			} );
		}

		// Адрес — только для доставки по адресу. Скрытое обязательное поле
		// заблокировало бы отправку формы, поэтому required снимается вместе
		// с видимостью.
		var withAddress = needsAddress( state );

		Array.prototype.forEach.call( addressFields, function ( field ) {
			var input = field.querySelector( 'input' );

			field.hidden = ! withAddress;
			input.required = withAddress && input.hasAttribute( 'data-required' );
		} );
	}

	/* ---------- Отрисовка чекаута ---------- */

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
			name.textContent = decodeHtml( item.name ) + ' × ' + item.quantity;

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

		renderShipping( state );

		if ( ! state ) {
			return;
		}

		var rate = effectiveRate( state );
		var itemsTotal = parseInt( state.totals.total_items, 10 ) || 0;
		// Пока сервер не подтвердил выбор способа, показываем стоимость выбранной
		// плитки, а не прошлую: иначе сумма спорит с тем, что отмечено.
		var shippingTotal = pendingRateId && rate
			? ( parseInt( rate.price, 10 ) || 0 )
			: ( parseInt( state.totals.total_shipping, 10 ) || 0 );

		totalsItems.textContent = formatPrice( itemsTotal, state.totals );
		shippingRow.hidden = ! state.needs_shipping;
		totalsShipping.textContent = formatPrice( shippingTotal, state.totals );

		// «Срок доставки» — только если он задан у тарифа. Выдумывать срок,
		// которого нет в настройках доставки, нельзя.
		deliveryRow.hidden = ! ( rate && rate.delivery_time );
		totalsDelivery.textContent = rate && rate.delivery_time ? decodeHtml( rate.delivery_time ) : '';

		totalsGrand.textContent = formatPrice( pendingRateId ? itemsTotal + shippingTotal : state.totals.total_price, state.totals );
	}

	/* ---------- Ошибки ---------- */

	function showError( message ) {
		var text = decodeHtml( Array.isArray( message ) ? message.join( ' ' ) : message );

		errorBox.textContent = text && 'Store API error' !== text ? text : 'Не удалось оформить заказ. Попробуйте ещё раз.';
		errorBox.hidden = false;
	}

	function hideError() {
		errorBox.hidden = true;
		errorBox.textContent = '';
	}

	/* ---------- Отправка заказа ---------- */

	function fieldValue( name ) {
		var field = checkoutForm.elements[ name ];

		return field ? field.value.trim() : '';
	}

	/**
	 * Телефон в макете — "+7" отдельно и 10 цифр в поле. Если покупатель
	 * ввёл номер целиком (с 7 или 8 в начале), лишнюю первую цифру убираем.
	 */
	function phoneDigits() {
		var digits = fieldValue( 'phone' ).replace( /\D/g, '' );

		if ( 11 === digits.length && ( '7' === digits.charAt( 0 ) || '8' === digits.charAt( 0 ) ) ) {
			digits = digits.slice( 1 );
		}

		return digits;
	}

	function buildOrder( state ) {
		var withAddress = needsAddress( state );
		var address = {
			first_name: fieldValue( 'full_name' ),
			last_name: '',
			company: '',
			address_1: withAddress ? [ fieldValue( 'street' ), fieldValue( 'house' ) ].filter( Boolean ).join( ', ' ) : '',
			address_2: withAddress ? fieldValue( 'apartment' ) : '',
			city: withAddress ? fieldValue( 'city' ) : '',
			state: '',
			postcode: withAddress ? fieldValue( 'postcode' ) : '',
			country: 'RU',
			phone: '+7' + phoneDigits(),
		};
		var methods = ( state && state.payment_methods ) || [];

		return {
			billing_address: Object.assign( {}, address, { email: fieldValue( 'email' ) } ),
			shipping_address: address,
			customer_note: fieldValue( 'customer_note' ),
			// Выбора способа оплаты в макете нет: берём первый доступный.
			payment_method: methods.length ? methods[ 0 ] : '',
			additional_fields: {
				'whoopstore/telegram': fieldValue( 'telegram' ),
				'whoopstore/privacy-consent': !! checkoutForm.elements.privacy_consent.checked,
			},
		};
	}

	checkoutForm.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		hideError();

		var phone = checkoutForm.elements.phone;
		var digits = phoneDigits();

		phone.setCustomValidity( digits.length && 10 !== digits.length ? 'Введите номер полностью: 10 цифр после +7.' : '' );

		if ( ! checkoutForm.reportValidity() ) {
			return;
		}

		submitButton.disabled = true;

		cart.checkout( buildOrder( cart.getState() ) )
			.then( function ( order ) {
				var redirect = order.payment_result && order.payment_result.redirect_url;

				// Оплата с переходом на страницу платёжной системы (ЮKassa) —
				// уводим туда. Оплата без перехода возвращает ссылку на штатную
				// страницу «Заказ получен» — вместо неё модалка из макета.
				if ( redirect && -1 === redirect.indexOf( 'order-received' ) ) {
					window.location.href = redirect;
					return null;
				}

				var number = order.order_number || order.order_id;

				checkoutForm.reset();
				pendingRateId = '';
				orderNumber.textContent = number;
				// data-order — признак того, что модалку открыл код, получивший
				// ответ оформления. Без него CSS её не покажет (см.
				// woocommerce.css): на стенде ловили «Заказ оформлен» с пустым
				// номером и без запроса к API из устаревшей копии этого скрипта.
				modal.dataset.order = number;
				close();
				modal.hidden = false;
				document.body.classList.add( 'has-cart-drawer' );

				// Store API уже очистил корзину на сервере — подтягиваем её.
				return cart.refresh();
			} )
			.catch( function ( error ) {
				showError( error && error.message );
				cart.refresh().catch( function () {} );
			} )
			.then( function () {
				submitButton.disabled = false;
			} );
	} );

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
		hideError();
		open( 'checkout' );

		// Тарифы доставки считаются на сервере — перед показом формы берём
		// свежую корзину.
		cart.refresh().catch( function () {} );
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
		currentState = state;
		renderCart( state );
		renderCheckout( state );
	} );
} )();
