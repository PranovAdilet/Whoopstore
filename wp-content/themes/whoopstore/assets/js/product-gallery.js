/**
 * Точки-пагинация галереи товара (Figma node 30:269 desktop / 30:1210 mobile).
 *
 * Почему это отдельный скрипт, а не настройка flexslider: у flexslider ровно
 * ОДИН controlNav, и WooCommerce штатно занимает его миниатюрами
 * (controlNav: 'thumbnails' — колонка 120×120 слева в макете). Точки в макете
 * есть одновременно с миниатюрами и на desktop, и на mobile, то есть это
 * вторая навигация по тем же слайдам, а не альтернатива миниатюрам.
 *
 * Скрипт не лезет во внутренности flexslider: переключение — клик по
 * соответствующей миниатюре (публичный DOM WooCommerce), синхронизация
 * активной точки — MutationObserver на классе flex-active, который flexslider
 * сам проставляет миниатюрам. Поэтому обновление WooCommerce/flexslider
 * ничего здесь не ломает, пока жива штатная разметка миниатюр.
 */
( function () {
	var ACTIVE = 'flex-active';

	function initGallery( gallery ) {
		var thumbs = gallery.querySelectorAll( '.flex-control-thumbs li img' );

		// Одно фото — переключать нечего, точек в макете тоже нет.
		if ( thumbs.length < 2 ) {
			return;
		}

		var dots = document.createElement( 'ol' );
		dots.className = 'product-gallery__dots';

		Array.prototype.forEach.call( thumbs, function ( thumb, index ) {
			var item = document.createElement( 'li' );
			var dot = document.createElement( 'button' );

			dot.type = 'button';
			dot.className = 'product-gallery__dot';
			dot.setAttribute( 'aria-label', 'Фото ' + ( index + 1 ) );
			dot.addEventListener( 'click', function () {
				thumb.click();
			} );

			item.appendChild( dot );
			dots.appendChild( item );
		} );

		gallery.appendChild( dots );

		function sync() {
			Array.prototype.forEach.call( thumbs, function ( thumb, index ) {
				var dot = dots.children[ index ].firstChild;
				var isActive = thumb.classList.contains( ACTIVE );

				dot.classList.toggle( 'is-active', isActive );
				dot.setAttribute( 'aria-current', isActive ? 'true' : 'false' );
			} );
		}

		sync();

		new window.MutationObserver( sync ).observe( gallery.querySelector( '.flex-control-thumbs' ), {
			attributes: true,
			attributeFilter: [ 'class' ],
			subtree: true,
		} );
	}

	function boot() {
		var galleries = document.querySelectorAll( '.woocommerce-product-gallery' );

		Array.prototype.forEach.call( galleries, function ( gallery ) {
			// Миниатюры создаёт flexslider уже после своей инициализации,
			// поэтому ждём их появления, а не готовности DOM.
			if ( gallery.querySelector( '.flex-control-thumbs' ) ) {
				initGallery( gallery );
				return;
			}

			var observer = new window.MutationObserver( function () {
				if ( ! gallery.querySelector( '.flex-control-thumbs' ) ) {
					return;
				}

				observer.disconnect();
				initGallery( gallery );
			} );

			observer.observe( gallery, { childList: true } );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
