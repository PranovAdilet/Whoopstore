/**
 * Клиент корзины поверх WooCommerce Store API + состояния из макета:
 * кнопка-корзина в шапке со счётчиком, степпер "В корзине: − 1 +" на
 * странице товара и тост "Товар добавлен в Корзину".
 *
 * Своей модели корзины здесь нет: единственный источник истины —
 * /wp-json/wc/store/v1/cart (тот же REST-интерфейс, которым пользуются
 * блоки WooCommerce). Наличие, цены и правила остаются на стороне
 * WooCommerce. Чтобы «+/−» и «Очистить» откликались сразу, скрипт до ответа
 * показывает местную копию корзины с новым количеством, а ответ сервера её
 * заменяет (см. «Изменение корзины: мгновенный отклик» ниже).
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

	/* ---------- Изменение корзины: мгновенный отклик ---------- */

	// Правка 2026-09-18 (QA корзины). Раньше интерфейс менялся только после
	// ответа Store API (~0,5 с на стенде), а быстрые нажатия терялись: «+»
	// отправлял «количество из последнего ответа + 1», и три быстрых нажатия на
	// 1 давали три запроса «сделай 2». Ответы к тому же могли прийти не по
	// порядку и перезаписать более новое состояние.
	//
	// Теперь:
	//  - количество, суммы и счётчик меняются сразу — публикуется местная копия
	//    корзины (withQuantity), ответ сервера её уточняет;
	//  - по каждой позиции уходит один запрос через DEBOUNCE после последнего
	//    нажатия, с итоговым количеством (0 — удаление);
	//  - запросы, меняющие корзину, идут строго по одному (enqueue): иначе
	//    параллельные записи в сессию WooCommerce могли затереть друг друга;
	//  - ответ применяется, только если после его отправки ничего не нажимали
	//    (mutationToken) и нет неотправленных изменений — иначе за ним придёт
	//    следующий, он и принесёт итог.
	// Выбор доставки, оформление и перечитывание корзины сначала отправляют
	// все отложенные изменения (flush), чтобы заказ не ушёл со старым количеством.
	var DEBOUNCE = 300;
	var queue = Promise.resolve();
	var mutationToken = 0;
	var pendingQty = {};
	var timers = {};

	function enqueue( task ) {
		var run = queue.then( task, task );

		queue = run.catch( function () {} );

		return run;
	}

	function hasPending() {
		return Object.keys( pendingQty ).length > 0;
	}

	// Ответ сервера применяется, только если он всё ещё последний.
	function applyIfCurrent( token ) {
		return function ( cart ) {
			if ( token === mutationToken && ! hasPending() ) {
				setState( cart );
			}

			return cart;
		};
	}

	// Ошибка Store API при изменении корзины (товар закончился, превышен
	// лимит количества, истекла сессия) отдельным сообщением не показывается —
	// такого состояния в макете нет. Корзина просто перечитывается, и степпер,
	// счётчик и drawer возвращаются к фактическому состоянию WooCommerce.
	// Вызывается изнутри задачи очереди, поэтому запрос идёт напрямую, мимо
	// очереди (через очередь задача ждала бы саму себя).
	function recover( token ) {
		return request( 'cart' ).then( applyIfCurrent( token ) ).catch( function () {} );
	}

	function refresh() {
		flush();

		return enqueue( function () {
			return request( 'cart' ).then( applyIfCurrent( mutationToken ) );
		} );
	}

	function clone( cart ) {
		return JSON.parse( JSON.stringify( cart ) );
	}

	function money( value ) {
		return parseInt( value, 10 ) || 0;
	}

	/**
	 * Местная копия корзины с новым количеством позиции (0 — позиция убрана):
	 * количество, сумма строки, счётчик и итог сдвигаются на разницу по цене
	 * единицы. Доставку и прочее не трогаем — это уточнит ответ сервера.
	 */
	function withQuantity( cart, key, quantity ) {
		var next = clone( cart );
		var delta = 0;
		var countDelta = 0;

		next.items = ( next.items || [] ).filter( function ( item ) {
			if ( item.key !== key ) {
				return true;
			}

			var unit = money( item.prices && item.prices.price );

			delta = ( quantity - item.quantity ) * unit;
			countDelta = quantity - item.quantity;

			if ( quantity < 1 ) {
				return false;
			}

			item.quantity = quantity;

			if ( item.totals ) {
				item.totals.line_subtotal = String( unit * quantity );
				item.totals.line_total = String( unit * quantity );
			}

			return true;
		} );

		next.items_count = Math.max( 0, ( next.items_count || 0 ) + countDelta );

		if ( next.totals ) {
			next.totals.total_items = String( money( next.totals.total_items ) + delta );
			next.totals.total_price = String( money( next.totals.total_price ) + delta );
		}

		return next;
	}

	function send( key ) {
		window.clearTimeout( timers[ key ] );
		delete timers[ key ];

		if ( ! ( key in pendingQty ) ) {
			return queue;
		}

		var quantity = pendingQty[ key ];

		delete pendingQty[ key ];

		return enqueue( function () {
			var token = mutationToken;
			var call = quantity < 1 ?
				request( 'cart/remove-item', { method: 'POST', body: { key: key } } ) :
				request( 'cart/update-item', { method: 'POST', body: { key: key, quantity: quantity } } );

			return call.then( applyIfCurrent( token ), function () {
				return recover( token );
			} );
		} );
	}

	// Отправить все отложенные изменения сейчас, не дожидаясь паузы.
	function flush() {
		Object.keys( pendingQty ).forEach( send );
	}

	function setQuantity( key, quantity ) {
		if ( ! state ) {
			return Promise.resolve();
		}

		mutationToken++;
		setState( withQuantity( state, key, quantity ) );
		pendingQty[ key ] = quantity;
		window.clearTimeout( timers[ key ] );
		timers[ key ] = window.setTimeout( function () {
			send( key );
		}, DEBOUNCE );

		return Promise.resolve();
	}

	function updateItem( key, quantity ) {
		return setQuantity( key, quantity );
	}

	function removeItem( key ) {
		return setQuantity( key, 0 );
	}

	function addItem( id, quantity ) {
		flush();
		mutationToken++;

		return enqueue( function () {
			var token = mutationToken;

			return request( 'cart/add-item', {
				method: 'POST',
				body: { id: id, quantity: quantity || 1 },
			} ).then( function ( cart ) {
				applyIfCurrent( token )( cart );
				showToast();

				return cart;
			} );
		} );
	}

	function clear() {
		Object.keys( timers ).forEach( function ( key ) {
			window.clearTimeout( timers[ key ] );
		} );
		timers = {};
		pendingQty = {};
		mutationToken++;

		if ( state ) {
			var empty = clone( state );

			empty.items = [];
			empty.items_count = 0;

			if ( empty.totals ) {
				empty.totals.total_items = '0';
				empty.totals.total_price = '0';
			}

			setState( empty );
		}

		return enqueue( function () {
			var token = mutationToken;

			return request( 'cart/items', { method: 'DELETE' } )
				.then( function () {
					return request( 'cart' );
				} )
				.then( applyIfCurrent( token ), function () {
					return recover( token );
				} );
		} );
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

	/* ---------- Оформление заказа ---------- */

	// Те же маршруты Store API, что у блока Checkout WooCommerce. В отличие от
	// изменения корзины, ошибки здесь не гасятся: форма должна показать
	// покупателю, что пошло не так (нет адреса, тариф недоступен и т.п.).
	function selectShippingRate( packageId, rateId ) {
		flush();

		return enqueue( function () {
			return request( 'cart/select-shipping-rate', {
				method: 'POST',
				body: { package_id: packageId, rate_id: rateId },
			} ).then( applyIfCurrent( mutationToken ) );
		} );
	}

	function checkout( data ) {
		flush();

		return enqueue( function () {
			return request( 'checkout', {
				method: 'POST',
				body: data,
			} );
		} );
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
		selectShippingRate: selectShippingRate,
		checkout: checkout,
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
