/**
 * Подсветка активного тарифа в мобильной ленте сравнения
 * (patterns/subscriptions-compare.php, .compare-plans).
 *
 * В макете к этой секции есть прямое указание: «При пролистывании
 * загорается тот тариф, что по центру». Сама прокрутка — нативная
 * (overflow-x у .compare-plans__track), скрипт делает только то, чего CSS
 * не умеет: помечает классом .is-active карточку, чей центр ближе всего к
 * центру видимой области, и снимает правое растворение на конце ленты.
 *
 * Логика «ближайшая к центру» — та же, что у точек слайдера преимуществ
 * (assets/js/benefits-slider.js): устойчива к остановке прокрутки между
 * карточками и не зависит от их числа.
 *
 * Проверки брейкпоинта нет намеренно: на desktop вся лента скрыта
 * (display:none), прокрутки в ней не происходит, обработчик ни на что не
 * влияет — поворот экрана и ресайз не требуют переинициализации. Стартовое
 * состояние (.is-active на первой карточке) проставлено в разметке, чтобы
 * подсветка была видна и до первого запуска скрипта.
 */
( function () {
	var box = document.querySelector( '.compare-plans' );
	var track = box && box.querySelector( '.compare-plans__track' );

	if ( ! track ) {
		return;
	}

	var cards = track.querySelectorAll( '.compare-plans__card' );

	if ( ! cards.length ) {
		return;
	}

	// offsetLeft отсчитывается от ближайшего позиционированного предка
	// (.compare-plans), а не от самого трека — сравнивать со scrollLeft
	// можно только сняв этот общий сдвиг.
	function offsetInTrack( card ) {
		return card.offsetLeft - cards[ 0 ].offsetLeft;
	}

	function currentIndex() {
		var center = track.scrollLeft + track.clientWidth / 2;
		var best = 0;
		var bestDistance = Infinity;

		for ( var i = 0; i < cards.length; i++ ) {
			var cardCenter = offsetInTrack( cards[ i ] ) + cards[ i ].offsetWidth / 2;
			var distance = Math.abs( cardCenter - center );

			if ( distance < bestDistance ) {
				bestDistance = distance;
				best = i;
			}
		}

		return best;
	}

	function sync() {
		var active = currentIndex();

		for ( var i = 0; i < cards.length; i++ ) {
			cards[ i ].classList.toggle( 'is-active', i === active );
		}

		// 1px допуска: при дробном scrollLeft (масштабирование, HiDPI)
		// сумма ровно в scrollWidth не достигается.
		var atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
		box.classList.toggle( 'is-end', atEnd );
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

	sync();
} )();
