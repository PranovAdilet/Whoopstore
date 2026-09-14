/**
 * Точки-пагинация мобильного слайдера преимуществ в секции "Отсутствие
 * дисплея дает несколько преимуществ" (patterns/no-display.php).
 *
 * Сама прокрутка — нативный CSS scroll-snap (см. .no-display__benefits в
 * @media(max-width:781px)), поэтому свайп работает и без этого скрипта.
 * Здесь только две вещи, которые CSS сделать не может: подсветить точку
 * текущего слайда и проскроллить к слайду по клику на точку.
 *
 * Проверки брейкпоинта нет намеренно: на desktop список — вертикальная
 * колонка без горизонтальной прокрутки, scroll там не происходит, а сами
 * точки скрыты (display:none) — обработчики просто ни на что не влияют.
 * Благодаря этому поворот экрана/ресайз не требуют переинициализации.
 */
( function () {
	var track = document.querySelector( '.no-display__benefits' );
	var dotsBox = document.querySelector( '.no-display__benefits-dots' );

	if ( ! track || ! dotsBox ) {
		return;
	}

	var slides = track.querySelectorAll( '.no-display__benefit' );
	var dots = dotsBox.querySelectorAll( '.no-display__benefits-dot' );

	if ( ! slides.length || dots.length !== slides.length ) {
		return;
	}

	// offsetLeft отсчитывается от ближайшего позиционированного предка
	// (.no-display__benefits-row), а не от самого трека — сравнивать со
	// scrollLeft можно только сняв этот общий сдвиг.
	function offsetInTrack( slide ) {
		return slide.offsetLeft - slides[ 0 ].offsetLeft;
	}

	// Ближайший к центру видимой области слайд, а не floor(scrollLeft/width):
	// устойчиво к зазору между слайдами и к остановке прокрутки между снапами.
	function currentIndex() {
		var center = track.scrollLeft + track.clientWidth / 2;
		var best = 0;
		var bestDistance = Infinity;

		for ( var i = 0; i < slides.length; i++ ) {
			var slideCenter = offsetInTrack( slides[ i ] ) + slides[ i ].offsetWidth / 2;
			var distance = Math.abs( slideCenter - center );

			if ( distance < bestDistance ) {
				bestDistance = distance;
				best = i;
			}
		}

		return best;
	}

	function sync() {
		var active = currentIndex();

		for ( var i = 0; i < dots.length; i++ ) {
			dots[ i ].classList.toggle( 'is-active', i === active );
		}
	}

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

	for ( var i = 0; i < dots.length; i++ ) {
		( function ( index ) {
			dots[ index ].addEventListener( 'click', function () {
				track.scrollTo( {
					left: offsetInTrack( slides[ index ] ),
					behavior: 'smooth'
				} );
			} );
		} )( i );
	}

	sync();
} )();
