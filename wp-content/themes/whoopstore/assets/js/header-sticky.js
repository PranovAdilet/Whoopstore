/**
 * Липкая шапка (правка заказчика 2026-11-11).
 *
 * position:sticky здесь не работает: на Главной и «Подписках» шапка лежит
 * внутри обёртки первого экрана (.home-hero-wrap), у которой overflow:hidden,
 * и залипла бы только в её пределах. Поэтому, как только страница уходит
 * вниз, шапке ставится класс .is-stuck (position:fixed, см. theme.css), а её
 * обёртка шаблонной части получает высоту шапки — контент под ней не
 * прыгает.
 */
( function () {
	var header = document.querySelector( '.site-header' );
	var slot = header ? header.parentElement : null;

	if ( ! header || ! slot ) {
		return;
	}

	var stuck = false;

	function update() {
		var shouldStick = window.scrollY > 0;

		if ( shouldStick === stuck ) {
			return;
		}

		stuck = shouldStick;
		slot.style.minHeight = stuck ? header.offsetHeight + 'px' : '';
		header.classList.toggle( 'is-stuck', stuck );
	}

	// Высота шапки — для отступа прокрутки к якорям (:target в theme.css):
	// фиксированная шапка не должна закрывать верх секции.
	function setHeight() {
		document.documentElement.style.setProperty( '--site-header-height', header.offsetHeight + 'px' );
	}

	window.addEventListener( 'scroll', update, { passive: true } );
	window.addEventListener( 'resize', setHeight, { passive: true } );
	setHeight();
	update();
} )();
