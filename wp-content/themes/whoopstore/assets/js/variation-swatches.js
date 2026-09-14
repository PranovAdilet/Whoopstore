/**
 * Визуальный pill-переключатель для атрибута вариации (напр. "Размер" S/L),
 * поверх штатной WooCommerce variations_form — без переопределения логики
 * подбора/цены вариации (P1-1).
 *
 * Принцип: нативный <select> остаётся единственным источником истины.
 * Клик по pill-кнопке лишь ставит select.value и диспатчит нативное
 * событие 'change' — весь дальнейший разбор (подбор variation_id, цена,
 * доступность "Добавить в корзину") выполняет штатный JS WooCommerce
 * (wc-add-to-cart-variation), который уже слушает это событие.
 * Если скрипт не выполнится — форма остаётся обычным <select> WooCommerce,
 * а не ломается.
 */
( function () {
	function decorate( form ) {
		var table = form.querySelector( 'table.variations' );
		var select = form.querySelector( 'table.variations select' );
		if ( ! table || ! select ) {
			return;
		}

		var options = Array.prototype.filter.call( select.options, function ( o ) {
			return o.value !== '';
		} );
		if ( ! options.length ) {
			return;
		}

		var attributeName =
			( table.querySelector( 'label' ) && table.querySelector( 'label' ).textContent.trim() ) || '';

		var wrapper = document.createElement( 'div' );
		wrapper.className = 'variation-swatches';
		wrapper.setAttribute( 'role', 'group' );
		wrapper.setAttribute( 'aria-label', attributeName );

		// Строка "Размер  S" над самими свотчами (Figma 30:250/30:251):
		// название атрибута полужирным + текущее выбранное значение.
		var label = document.createElement( 'p' );
		label.className = 'variation-swatches__label';

		var labelName = document.createElement( 'span' );
		labelName.className = 'variation-swatches__name';
		labelName.textContent = attributeName;

		var labelValue = document.createElement( 'span' );
		labelValue.className = 'variation-swatches__value';

		label.appendChild( labelName );
		label.appendChild( labelValue );
		wrapper.appendChild( label );

		var list = document.createElement( 'div' );
		list.className = 'variation-swatches__options';
		wrapper.appendChild( list );

		function syncActive() {
			Array.prototype.forEach.call( list.children, function ( btn ) {
				var isActive = btn.dataset.value === select.value;
				btn.classList.toggle( 'is-active', isActive );
				btn.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
			} );

			labelValue.textContent = select.selectedOptions.length ? select.selectedOptions[ 0 ].textContent : '';
		}

		options.forEach( function ( opt ) {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'variation-swatches__option';
			btn.textContent = opt.textContent;
			btn.dataset.value = opt.value;
			btn.setAttribute( 'aria-pressed', 'false' );
			btn.addEventListener( 'click', function () {
				select.value = opt.value;
				select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				syncActive();
			} );
			list.appendChild( btn );
		} );

		// Если значение select поменяется не через pill (напр. ссылка "Clear"),
		// держим кнопки в синхронизированном состоянии.
		select.addEventListener( 'change', syncActive );

		table.insertAdjacentElement( 'afterend', wrapper );
		table.classList.add( 'variations--visually-replaced' );

		var resetLink = form.querySelector( '.reset_variations' );
		if ( resetLink ) {
			resetLink.classList.add( 'reset_variations--hidden' );
		}

		// В Figma на странице товара всегда показан выбранный размер —
		// по умолчанию выбираем первый вариант штатным событием 'change'.
		select.value = options[ 0 ].value;
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		syncActive();
	}

	document.querySelectorAll( 'form.variations_form' ).forEach( decorate );
} )();
