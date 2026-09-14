/**
 * Клиент корзины поверх WooCommerce Store API + состояния из макета:
 * кнопка-корзина в шапке со счётчиком, степпер "В корзине: − 1 +" на
 * странице товара и тост "Товар добавлен в Корзину".
 *
 * Своей модели корзины здесь нет: единственный источник истины —
 * /wp-json/wc/store/v1/cart (тот же REST-интерфейс, которым пользуются
 * блоки WooCommerce). Скрипт только читает его ответ и перерисовывает
 * интерфейс; наличие, цены и правила остаются на стороне WooCommerce.
 *
 * Без JS страница остаётся рабочей: форма "Добавить в корзину" отправляется
 * обычным POST'ом WooCommerce, корзина открывается по /cart/.
 *
 * window.whoopstoreCart — общая точка входа для остальных модулей
 * интерфейса корзины (drawer, чекаут) — см. assets/js/cart-drawer.js.
 */
( function () {
	var settings = window.whoopstoreCartData || {};

	if ( ! settings.root ) {
		return;
	}

	var nonce = settings.nonce;
	var state = null;
	var listeners = [];

	/* ---------- Store API ---------- */

	function request( path, options ) {
		var opts = options || {};
		var headers = { 'Content-Type': 'application/json' };

		if ( nonce ) {
			headers.Nonce = nonce;
		}

		return window
			.fetch( settings.root + path, {
				method: opts.method || 'GET',
				credentials: 'same-origin',
				headers: headers,
				body: opts.body ? JSON.stringify( opts.body ) : undefined,
			} )
			.then( function ( response ) {
				// Store API отдаёт свежий nonce в каждом ответе — подхватываем,
				// иначе после истечения текущего запросы начнут падать.
				var fresh = response.headers.get( 'Nonce' );

				if ( fresh ) {
					nonce = fresh;
				}

				return response.json().then( function ( body ) {
					if ( ! response.ok ) {
						throw new Error( ( body && body.message ) || 'Store API error' );
					}

					return body;
				} );
			} );
	}

	function setState( cart ) {
		state = cart;

		listeners.forEach( function ( listener ) {
			listener( state );
		} );
	}

	function refresh() {
		return request( 'cart' ).then( setState );
	}

	function addItem( id, quantity ) {
		return request( 'cart/add-item', {
			method: 'POST',
			body: { id: id, quantity: quantity || 1 },
		} ).then( function ( cart ) {
			setState( cart );
			showToast();
			return cart;
		} );
	}

	// Ошибка Store API при изменении корзины (товар закончился, превышен
	// лимит количества, истекла сессия) отдельным сообщением не показывается —
	// такого состояния в макете нет. Корзина просто перечитывается, и степпер,
	// счётчик и drawer возвращаются к фактическому состоянию WooCommerce.
	// Без этого промис падал необработанным в консоль, а интерфейс оставался
	// в рассинхроне с корзиной.
	function recover() {
		return refresh().catch( function () {} );
	}

	function updateItem( key, quantity ) {
		return request( 'cart/update-item', {
			method: 'POST',
			body: { key: key, quantity: quantity },
		} ).then( setState ).catch( recover );
	}

	function removeItem( key ) {
		return request( 'cart/remove-item', {
			method: 'POST',
			body: { key: key },
		} ).then( setState ).catch( recover );
	}

	function clear() {
		return request( 'cart/items', { method: 'DELETE' } ).then( function () {
			return refresh();
		} ).catch( recover );
	}

	function findItem( id ) {
		if ( ! state || ! state.items ) {
			return null;
		}

		for ( var i = 0; i < state.items.length; i++ ) {
			// id элемента Store API — id вариации, если товар вариативный,
			// иначе id самого товара.
			if ( String( state.items[ i ].id ) === String( id ) ) {
				return state.items[ i ];
			}
		}

		return null;
	}

	/* ---------- Тост "Товар добавлен в Корзину" ---------- */

	var toast;
	var toastTimer;

	function showToast() {
		if ( ! toast ) {
			toast = document.createElement( 'div' );
			toast.className = 'cart-toast';
			toast.setAttribute( 'role', 'status' );
			toast.textContent = 'Товар добавлен в Корзину';
			document.body.appendChild( toast );
		}

		toast.classList.add( 'is-visible' );
		window.clearTimeout( toastTimer );
		toastTimer = window.setTimeout( function () {
			toast.classList.remove( 'is-visible' );
		}, 3000 );
	}

	/* ---------- Кнопка-корзина в шапке ---------- */

	function initHeaderButton() {
		var button = document.querySelector( '.site-header__cart' );

		if ( ! button ) {
			return;
		}

		var badge = button.querySelector( '.site-header__cart-badge' );

		listeners.push( function ( cart ) {
			var count = cart && cart.items_count ? cart.items_count : 0;

			// В макете кнопки в шапке нет, пока корзина пуста.
			button.hidden = count < 1;
			badge.textContent = count;
		} );

		button.addEventListener( 'click', function () {
			document.dispatchEvent( new CustomEvent( 'whoopstore-cart-open' ) );
		} );
	}

	/* ---------- Страница товара: кнопка ↔ степпер ---------- */

	function initProductForm() {
		var form = document.querySelector( 'form.cart' );

		if ( ! form ) {
			return;
		}

		var button = form.querySelector( '.single_add_to_cart_button' );
		var stepper = form.querySelector( '.cart-stepper' );

		if ( ! button || ! stepper ) {
			return;
		}

		var value = stepper.querySelector( '.cart-stepper__value' );
		var minus = stepper.querySelector( '.cart-stepper__button--minus' );
		var plus = stepper.querySelector( '.cart-stepper__button--plus' );

		// Вариативный товар кладёт id выбранной вариации в скрытое поле
		// variation_id (id родителя — в input[name=product_id], см.
		// variation-add-to-cart-button.php). У простого товара такого поля нет:
		// штатная разметка simple.php держит id только в самой кнопке
		// button[name=add-to-cart].
		function currentId() {
			var variation = form.querySelector( 'input.variation_id' );

			if ( variation && variation.value && '0' !== variation.value ) {
				return variation.value;
			}

			var product = form.querySelector( 'input[name="product_id"]' ) || form.querySelector( 'button[name="add-to-cart"]' );

			return product ? product.value : null;
		}

		function currentItem() {
			var id = currentId();

			return id ? findItem( id ) : null;
		}

		function sync() {
			var item = currentItem();

			stepper.hidden = ! item;
			button.hidden = !! item;

			if ( item ) {
				value.textContent = item.quantity;
			}
		}

		listeners.push( sync );

		// Смена размера меняет вариацию. id вариации в скрытое поле кладёт
		// штатный обработчик WooCommerce, который слушает то же событие
		// 'change' — setTimeout(0) гарантирует, что мы прочитаем поле уже
		// после него, независимо от того, чей обработчик навесился первым.
		form.addEventListener( 'change', function () {
			window.setTimeout( sync, 0 );
		} );

		form.addEventListener( 'submit', function ( event ) {
			var id = currentId();

			if ( ! id ) {
				return;
			}

			event.preventDefault();
			button.classList.add( 'is-busy' );

			addItem( id, 1 ).catch( function () {
				// Если запрос не прошёл — отправляем форму обычным способом,
				// чтобы пользователь всё равно добавил товар.
				// form.submit() не передаёт имя и значение нажатой кнопки, а у
				// простого товара id лежит именно в button[name=add-to-cart] —
				// без скрытого поля WooCommerce получил бы форму без
				// add-to-cart и ничего бы не добавил.
				if ( ! form.querySelector( 'input[name="add-to-cart"]' ) ) {
					var field = document.createElement( 'input' );

					field.type = 'hidden';
					field.name = 'add-to-cart';
					field.value = id;
					form.appendChild( field );
				}

				form.submit();
			} ).then( function () {
				button.classList.remove( 'is-busy' );
			} );
		} );

		minus.addEventListener( 'click', function () {
			var item = currentItem();

			if ( ! item ) {
				return;
			}

			if ( item.quantity <= 1 ) {
				removeItem( item.key );
				return;
			}

			updateItem( item.key, item.quantity - 1 );
		} );

		plus.addEventListener( 'click', function () {
			var item = currentItem();

			if ( item ) {
				updateItem( item.key, item.quantity + 1 );
			}
		} );

		sync();
	}

	/* ---------- Публичный интерфейс для остальных модулей ---------- */

	window.whoopstoreCart = {
		getState: function () {
			return state;
		},
		subscribe: function ( listener ) {
			listeners.push( listener );

			if ( state ) {
				listener( state );
			}
		},
		refresh: refresh,
		addItem: addItem,
		updateItem: updateItem,
		removeItem: removeItem,
		clear: clear,
	};

	function boot() {
		initHeaderButton();
		initProductForm();

		// Не удалось загрузить корзину — интерфейс остаётся в исходном
		// состоянии (кнопка корзины скрыта), а форма товара при отправке
		// уйдёт запасным обычным POST (см. обработчик submit).
		refresh().catch( function () {} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
