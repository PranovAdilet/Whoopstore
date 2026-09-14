/**
 * Стрелки ленты "Вам может понравиться" (patterns: woocommerce/single-product/
 * related.php, стили .related-carousel* в assets/css/woocommerce.css).
 *
 * Сама прокрутка — нативный CSS scroll-snap, свайп и колесо работают без
 * скрипта (тот же приём, что у мобильного слайдера преимуществ на Главной,
 * см. assets/js/benefits-slider.js). Здесь только то, чего CSS не умеет:
 * прокрутка на одну карточку по клику на стрелку и блокировка стрелки,
 * когда лента упёрлась в край.
 */
( function () {
	function initCarousel( carousel ) {
		var track = carousel.querySelector( '.related-carousel__track' );
		var prev = carousel.querySelector( '.carousel-arrow--prev' );
		var next = carousel.querySelector( '.carousel-arrow--next' );

		if ( ! track || ! prev || ! next ) {
			return;
		}

		var cards = track.children;

		function step() {
			if ( cards.length < 2 ) {
				return track.clientWidth;
			}

			// Ширина карточки вместе с зазором — берём по реальным
			// координатам соседей, чтобы не дублировать значения из CSS.
			return cards[ 1 ].offsetLeft - cards[ 0 ].offsetLeft;
		}

		function scrollBy( direction ) {
			track.scrollBy( { left: direction * step(), behavior: 'smooth' } );
		}

		function sync() {
			// 1px допуск: дробные ширины после scroll-snap иначе не дают
			// признать, что лента доехала до конца.
			var maxScroll = track.scrollWidth - track.clientWidth;

			prev.disabled = track.scrollLeft <= 1;
			next.disabled = track.scrollLeft >= maxScroll - 1;
		}

		prev.addEventListener( 'click', function () {
			scrollBy( -1 );
		} );

		next.addEventListener( 'click', function () {
			scrollBy( 1 );
		} );

		var scheduled = false;

		track.addEventListener( 'scroll', function () {
			if ( scheduled ) {
				return;
			}

			scheduled = true;

			window.requestAnimationFrame( function () {
				scheduled = false;
				sync();
			} );
		}, { passive: true } );

		window.addEventListener( 'resize', sync );
		sync();
	}

	function boot() {
		Array.prototype.forEach.call(
			document.querySelectorAll( '.related-carousel' ),
			initCarousel
		);
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
