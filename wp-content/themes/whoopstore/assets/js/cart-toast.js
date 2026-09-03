/**
 * Тост "Товар добавлен в Корзину" — реагирует на нативное событие
 * WooCommerce Blocks "wc-blocks_added_to_cart" (document.body), без
 * кастомного AJAX/PHP (см. sanity-check pilot-архитектуры).
 */
( function () {
	var toast;

	function showToast() {
		if ( ! toast ) {
			toast = document.createElement( 'div' );
			toast.className = 'whoopstore-toast';
			toast.setAttribute( 'role', 'status' );
			toast.textContent = 'Товар добавлен в Корзину';
			document.body.appendChild( toast );
		}

		toast.classList.add( 'is-visible' );
		window.clearTimeout( showToast._timer );
		showToast._timer = window.setTimeout( function () {
			toast.classList.remove( 'is-visible' );
		}, 3000 );
	}

	document.body.addEventListener( 'wc-blocks_added_to_cart', showToast );
} )();
