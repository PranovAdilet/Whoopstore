/**
 * Лента «Персонализируйте свой WHOOP» на Главной: листание по кругу и плашка
 * с названием выбранного ремешка (ТЗ: «ремешки листаются свайпом и кнопками;
 * название выбранного ремешка — по центру»).
 *
 * Почему не обычная прокрутка (правка 2026-09-16). Лента центрируется
 * padding'ом `max(0px, calc(50vw - 681px))`, то есть её видимая область на
 * любом экране шире 1362px равна ровно ширине семи кадров с нахлёстом —
 * прокручивать нечего, стрелки упирались в край сразу, и центральный кадр не
 * менялся. Поэтому лента листается по кругу: крайний кадр переезжает в
 * противоположный конец. Ширина контента при этом не меняется, центрирование
 * и размеры из макета сохраняются.
 *
 * Свайп, колесо и клавиатура остаются нативными (у ленты overflow-x:auto).
 * На узком экране прокрутка реальная, и на краю лента доворачивается со
 * сдвигом scrollLeft на тот же шаг — визуально незаметно, но листание не
 * упирается в край.
 *
 * На широком экране прокручивать нечего, поэтому события scroll там не
 * возникают вовсе — из-за этого лента не листалась жестом, а плашка не
 * менялась (правка 2026-09-16). Там же горизонтальный жест — колесо/трекпад
 * по горизонтали, колесо с Shift, перетаскивание мышью или пальцем —
 * переводится в тот же проворот, что и клик по стрелке. Вертикальное движение
 * не перехватывается: страница прокручивается как обычно.
 *
 * Выбранным считается кадр, ближайший к центру ленты: так плашка совпадает с
 * тем, что видно по центру, и после свайпа, и после клика по стрелке. Данные
 * ремешка берутся из разметки — data-family/data-color ставит PHP из товара
 * WooCommerce (см. patterns/straps-personalize.php). Кадр без данных плашку не
 * меняет.
 */
( function () {
	function initCarousel( carousel ) {
		var media = carousel.querySelector( '.straps-carousel__media' );
		var prev = carousel.querySelector( '.straps-carousel__arrow--prev' );
		var next = carousel.querySelector( '.straps-carousel__arrow--next' );
		var badge = carousel.querySelector( '.straps-carousel__badge' );

		if ( ! media || ! media.children.length ) {
			return;
		}

		// Живая коллекция: порядок кадров меняется при листании по кругу.
		var photos = media.children;
		var familyLine = badge ? badge.querySelector( '.straps-carousel__badge-line--strong' ) : null;
		var colorLine = badge ? badge.querySelector( '.straps-carousel__badge-line:not(.straps-carousel__badge-line--strong) strong' ) : null;

		// Шаг — по реальным координатам соседних кадров, чтобы не дублировать
		// нахлёст из CSS (на mobile он другой).
		function step() {
			return photos.length > 1 ? photos[ 1 ].offsetLeft - photos[ 0 ].offsetLeft : media.clientWidth;
		}

		function scrollable() {
			return media.scrollWidth - media.clientWidth > 1;
		}

		function selectedPhoto() {
			var center = media.scrollLeft + media.clientWidth / 2;
			var best = null;
			var bestDistance = Infinity;

			Array.prototype.forEach.call( photos, function ( photo ) {
				var distance = Math.abs( photo.offsetLeft + photo.offsetWidth / 2 - center );

				if ( distance < bestDistance ) {
					bestDistance = distance;
					best = photo;
				}
			} );

			return best;
		}

		function syncBadge() {
			var photo = selectedPhoto();

			if ( ! badge || ! photo || ! photo.dataset || ! photo.dataset.family ) {
				return;
			}

			if ( familyLine ) {
				familyLine.textContent = photo.dataset.family;
			}

			if ( colorLine ) {
				colorLine.textContent = photo.dataset.color;
			}
		}

		/**
		 * Проворот на один кадр: вперёд — первый уходит в конец, назад —
		 * последний встаёт в начало. Когда лента прокручивается, сдвигаем
		 * scrollLeft на тот же шаг: кадры остаются на своих местах на экране,
		 * просто появляется запас в нужную сторону.
		 */
		function rotate( direction ) {
			var moved = direction > 0 ? media.firstElementChild : media.lastElementChild;

			if ( ! moved || photos.length < 2 ) {
				return;
			}

			var shift = step();

			if ( direction > 0 ) {
				media.appendChild( moved );
			} else {
				media.insertBefore( moved, media.firstElementChild );
			}

			if ( scrollable() ) {
				media.scrollLeft += direction > 0 ? -shift : shift;
			}
		}

		// Длительность проворота: лента переезжает, а не прыгает. Сдвигаем сами
		// кадры, а не контейнер: на контейнере лежит маска затухания по краям,
		// вместе с ним она бы поехала. Раскладка от transform не зависит,
		// поэтому размеры и нахлёст остаются прежними.
		var duration = 320;
		var animating = false;

		function reducedMotion() {
			return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
		}

		function setShift( offset, animated ) {
			Array.prototype.forEach.call( photos, function ( photo ) {
				photo.style.transition = animated ? 'transform ' + duration + 'ms ease' : '';
				photo.style.transform = offset ? 'translateX(' + offset + 'px)' : '';
			} );
		}

		/**
		 * Проворот с анимацией: кадр переставляем сразу, затем сдвигаем ленту
		 * на шаг назад без перехода — визуально она остаётся на прежнем месте —
		 * и плавно возвращаем на ноль. Так приезжающий кадр выезжает из-за
		 * края, а не возникает скачком.
		 */
		function rotateSmooth( direction ) {
			if ( animating ) {
				return;
			}

			var shift = step();

			rotate( direction );
			syncBadge();

			if ( reducedMotion() ) {
				return;
			}

			animating = true;
			setShift( direction > 0 ? shift : -shift, false );

			// Принудительный пересчёт раскладки: иначе браузер объединит оба
			// присваивания и перехода не будет.
			void media.offsetWidth;

			setShift( 0, true );

			window.setTimeout( function () {
				setShift( 0, false );
				animating = false;
			}, duration );
		}

		function goTo( direction ) {
			if ( ! scrollable() ) {
				rotateSmooth( direction );
				return;
			}

			// У края доворачиваем ленту, чтобы было куда ехать дальше.
			var maxScroll = media.scrollWidth - media.clientWidth;

			if ( direction > 0 && media.scrollLeft >= maxScroll - 1 ) {
				rotate( 1 );
			} else if ( direction < 0 && media.scrollLeft <= 1 ) {
				rotate( -1 );
			}

			media.scrollBy( { left: direction * step(), behavior: 'smooth' } );
		}

		// Тот же доворот, когда до края докрутили свайпом, а не стрелкой.
		function loopAtEdges() {
			if ( ! scrollable() ) {
				return;
			}

			var maxScroll = media.scrollWidth - media.clientWidth;

			if ( media.scrollLeft >= maxScroll - 1 ) {
				rotate( 1 );
			} else if ( media.scrollLeft <= 1 ) {
				rotate( -1 );
			}
		}

		/**
		 * Горизонтальный жест там, где прокручивать нечего: накапливаем
		 * смещение и на каждые полшага проворачиваем ленту на один кадр.
		 * Полшага — чтобы трекпад откликался сразу, а не после длинного
		 * движения.
		 */
		var gestureDelta = 0;

		function handleGesture( deltaX ) {
			if ( scrollable() || ! deltaX ) {
				return false;
			}

			var threshold = step() / 2;

			gestureDelta += deltaX;

			// Пока лента едет, новый проворот не начинаем: иначе жест
			// проскакивает несколько кадров рывками.
			while ( ! animating && Math.abs( gestureDelta ) >= threshold ) {
				rotateSmooth( gestureDelta > 0 ? 1 : -1 );
				gestureDelta += gestureDelta > 0 ? -threshold : threshold;
			}

			// Остаток, накопленный за время анимации, не копим сверх одного
			// шага — иначе после паузы лента отмотает всё разом.
			if ( Math.abs( gestureDelta ) > threshold ) {
				gestureDelta = gestureDelta > 0 ? threshold : -threshold;
			}

			return true;
		}

		media.addEventListener( 'wheel', function ( event ) {
			// Горизонтальным считаем только явно горизонтальное движение
			// (трекпад) или колесо с Shift. Вертикальное — прокрутка страницы,
			// её не трогаем.
			var deltaX = Math.abs( event.deltaX ) > Math.abs( event.deltaY ) ? event.deltaX : ( event.shiftKey ? event.deltaY : 0 );

			if ( handleGesture( deltaX ) ) {
				event.preventDefault();
			}
		}, { passive: false } );

		// Перетаскивание мышью или пальцем — там же, где нет прокрутки.
		var dragFrom = null;
		var dragged = false;

		media.addEventListener( 'pointerdown', function ( event ) {
			if ( scrollable() ) {
				return;
			}

			dragFrom = event.clientX;
			dragged = false;
			gestureDelta = 0;
		} );

		media.addEventListener( 'pointermove', function ( event ) {
			if ( null === dragFrom ) {
				return;
			}

			var delta = dragFrom - event.clientX;

			// Мелкое дрожание при клике по кадру жестом не считаем.
			if ( ! dragged && Math.abs( delta ) < 8 ) {
				return;
			}

			dragged = true;
			dragFrom = event.clientX;
			handleGesture( delta );
		} );

		function endDrag() {
			dragFrom = null;
		}

		media.addEventListener( 'pointerup', endDrag );
		media.addEventListener( 'pointercancel', endDrag );
		media.addEventListener( 'pointerleave', endDrag );

		// Ленту листали перетаскиванием — товар по этому клику не открываем.
		media.addEventListener( 'click', function ( event ) {
			if ( dragged ) {
				event.preventDefault();
				dragged = false;
			}
		}, true );

		// Иначе браузер начнёт перетаскивать саму картинку.
		media.addEventListener( 'dragstart', function ( event ) {
			event.preventDefault();
		} );

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				goTo( -1 );
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				goTo( 1 );
			} );
		}

		var scheduled = false;

		media.addEventListener( 'scroll', function () {
			if ( scheduled ) {
				return;
			}

			scheduled = true;

			window.requestAnimationFrame( function () {
				scheduled = false;
				loopAtEdges();
				syncBadge();
			} );
		}, { passive: true } );

		/**
		 * Пальцем лента листается только там, где нет нативной прокрутки:
		 * pan-y оставляет браузеру вертикальный жест страницы, а горизонтальный
		 * отдаёт нам. Где прокрутка есть — не вмешиваемся, работает обычный свайп.
		 */
		function syncTouchAction() {
			media.style.touchAction = scrollable() ? '' : 'pan-y';
		}

		function sync() {
			syncTouchAction();
			syncBadge();
		}

		window.addEventListener( 'resize', sync );
		sync();
	}

	function boot() {
		Array.prototype.forEach.call(
			document.querySelectorAll( '.straps-carousel--personalize' ),
			initCarousel
		);
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
