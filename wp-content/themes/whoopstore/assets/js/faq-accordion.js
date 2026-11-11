/**
 * «Частые вопросы»: раскрыт только один ответ — при открытии вопроса
 * остальные в том же списке закрываются (правка заказчика 2026-11-11).
 *
 * Сами вопросы — штатные core/details, раскрытие ведёт браузер; скрипт только
 * закрывает соседей. Без JS аккордеон работает как раньше, просто открытых
 * ответов может быть несколько. Событие toggle не всплывает, поэтому
 * слушатель — на фазе перехвата.
 */
( function () {
	document.querySelectorAll( '.faq__list' ).forEach( function ( list ) {
		list.addEventListener( 'toggle', function ( event ) {
			var item = event.target;

			if ( ! item.open || ! item.classList.contains( 'faq__item' ) ) {
				return;
			}

			list.querySelectorAll( '.faq__item[open]' ).forEach( function ( other ) {
				if ( other !== item ) {
					other.open = false;
				}
			} );
		}, true );
	} );
} )();
