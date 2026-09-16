/**
 * Фильтр «Все устройства / Whoop 5.0 / Whoop MG» в сетке трекеров на Главной.
 *
 * Пилюли есть в макете, но состояний отфильтрованной сетки в нём нет, поэтому
 * поведение минимальное: клик оставляет карточки выбранной модели и переносит
 * подсветку на нажатую пилюлю. Ничего не перезагружается, порядок карточек не
 * меняется, скрытие — штатным атрибутом hidden (см. [hidden] в theme.css).
 *
 * Модель карточки приходит из разметки: data-model ставит PHP по названию
 * товара (whoopstore_device_model()). Карточка без модели показывается всегда —
 * она не относится ни к 5.0, ни к MG.
 */
( function () {
	function initFilter( grid ) {
		var buttons = grid.querySelectorAll( '.device-grid__filter' );
		var cards = grid.querySelectorAll( '.device-card' );

		if ( buttons.length < 2 || ! cards.length ) {
			return;
		}

		function apply( model ) {
			Array.prototype.forEach.call( cards, function ( card ) {
				var cardModel = card.dataset ? card.dataset.model : '';

				card.hidden = !! model && !! cardModel && cardModel !== model;
			} );

			Array.prototype.forEach.call( buttons, function ( button ) {
				var active = ( button.dataset.model || '' ) === model;

				button.classList.toggle( 'is-active', active );
				button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );
		}

		Array.prototype.forEach.call( buttons, function ( button ) {
			button.addEventListener( 'click', function () {
				apply( button.dataset.model || '' );
			} );
		} );
	}

	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( '.device-grid' ), initFilter );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
