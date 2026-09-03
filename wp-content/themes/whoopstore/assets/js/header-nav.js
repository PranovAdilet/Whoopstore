/**
 * Мобильное бургер-меню header'а. Локальный скрипт, без зависимостей —
 * подключается на каждой странице (Header есть везде).
 */
( function () {
	var burger = document.querySelector( '.site-header__burger' );
	var nav = document.querySelector( '.site-header__nav' );

	if ( ! burger || ! nav ) {
		return;
	}

	burger.addEventListener( 'click', function () {
		var isOpen = nav.classList.toggle( 'is-open' );
		burger.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
	} );
} )();
