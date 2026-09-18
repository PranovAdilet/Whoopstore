/**
 * Pinned-скролл секции «Преимущества» на Главной.
 *
 * Механика по аннотации автора макета (см. шапку
 * patterns/advantages-sticky-scroll.php): секция залипает на весь экран,
 * заголовок плавно исчезает, а дальнейший вертикальный скролл внутри неё
 * листает слайды «телефон + подпись», не двигая страницу.
 *
 * Прогрессивное улучшение: базовая CSS-раскладка секции — обычный
 * вертикальный список слайдов. Пин включает ИМЕННО этот скрипт, добавляя
 * .is-pinned, поэтому без JS (и при prefers-reduced-motion) секция
 * остаётся полностью читаемой, без «пустой полосы» на месте неработающего
 * эффекта — ошибка прошлой реализации, из-за которой её и сняли.
 *
 * Длины фаз (заголовок / один слайд) не продублированы здесь числами, а
 * читаются из CSS-переменных --advantages-intro / --advantages-step: там
 * же они задают реальную высоту дорожки, и расходиться двум источникам
 * нельзя.
 */
( function () {
	var root = document.querySelector( '.advantages__scroll' );

	if ( ! root ) {
		return;
	}

	var sticky = root.querySelector( '.advantages__sticky' );
	var title = root.querySelector( '.advantages__title' );
	var titleText = root.querySelector( '.advantages__title-text' );
	var stage = root.querySelector( '.advantages__slides' );
	var slides = root.querySelectorAll( '.advantages__slide' );

	if ( ! sticky || ! title || ! titleText || ! stage || slides.length < 2 ) {
		return;
	}

	if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	root.classList.add( 'is-pinned' );

	var activeIndex = -1;
	var ticking = false;
	var pinHeight = 0;
	var pinTop = 0;
	var introShare = 0;

	// Залипающая коробка ровно по высоте композиции, поэтому её высоту и
	// смещения для центровки в окне знает только рантайм: всё это зависит
	// от реального переноса строк и от высоты окна. CSS получает числа
	// переменными и уже сам собирает из них высоту дорожки, top пина и
	// max-height заголовка — правила остаются в таблице стилей, сюда
	// уходят только величины.
	//
	// Замеряются именно составляющие, а не sticky.offsetHeight целиком:
	// measure() вызывается и на resize, то есть запросто в состоянии, когда
	// заголовок уже схлопнут, и высота коробки тогда занижена.
	//
	// Высоту заголовка даёт внутренний span: max-height режет его только
	// визуально, на собственный offsetHeight span это не влияет — значит
	// значение верно в любом состоянии. Через title.scrollHeight было бы
	// короче, но он в части движков не учитывает нижний padding, когда
	// содержимое переполнено, то есть ровно в схлопнутом состоянии; отступ
	// до композиции (padding-bottom) поэтому читается отдельно — он не
	// анимируется и одинаков в обоих состояниях.
	//
	// Центруется коробка не по window.innerHeight, а по высоте окна в svh
	// (правка 2026-09-18, баг на mobile): innerHeight меняется, когда
	// браузер прячет/показывает адресную строку, это приходит событием
	// resize посреди прокрутки — и телефон съезжал на 40–50px, а вместе с
	// ним сдвигались точки переключения слайдов. svh от адресной строки не
	// зависит, и дорожка в CSS считается в той же единице.
	function centerOffset( height, viewport ) {
		var free = viewport - height;

		// Композиция выше окна (короткие мобильные экраны) — тогда пин
		// прижимается к верху: обрезать её сверху центровкой нельзя.
		return free > 0 ? Math.round( free / 2 ) : 0;
	}

	function measure() {
		// Длины фаз читаются здесь, а не один раз при старте: на mobile шаг
		// слайда другой (см. @media в theme.css), и поворот экрана может
		// перевести страницу через брейкпоинт.
		var styles = window.getComputedStyle( root );
		var introLength = parseFloat( styles.getPropertyValue( '--advantages-intro' ) ) || 30;
		var stepLength = parseFloat( styles.getPropertyValue( '--advantages-step' ) ) || 40;
		var pathLength = introLength + slides.length * stepLength;

		// Доля пути, которую занимает фаза заголовка; остальное делится
		// поровну между слайдами.
		introShare = introLength / pathLength;

		var titleSpace = titleText.offsetHeight +
			( parseFloat( window.getComputedStyle( title ).paddingBottom ) || 0 );
		var stageHeight = stage.offsetHeight;

		pinHeight = stageHeight + titleSpace;
		root.style.setProperty( '--advantages-pin-height', pinHeight + 'px' );

		// Высота окна в svh: сам JS эту единицу не знает, но её уже
		// применил CSS — дорожка = pinHeight + pathLength svh. Поэтому
		// читаем высоту дорожки после установки pinHeight выше. Без svh
		// (старые браузеры) CSS берёт vh, и эта же формула вернёт vh.
		var viewport = ( root.offsetHeight - pinHeight ) / pathLength * 100;

		pinTop = centerOffset( pinHeight, viewport );

		root.style.setProperty( '--advantages-pin-title-space', titleSpace + 'px' );
		root.style.setProperty( '--advantages-pin-top', pinTop + 'px' );

		// Состояние после схлопывания заголовка: коробка короче ровно на
		// его место, центр экрана для неё другой.
		root.style.setProperty( '--advantages-pin-top-collapsed', centerOffset( stageHeight, viewport ) + 'px' );
	}

	function render() {
		ticking = false;

		// Длина пина = высота дорожки минус высота залипающей коробки, а
		// точка отсчёта — не верх окна, а top, на котором коробка залипает.
		// pinHeight и pinTop берём из measure(), а не читаем заново: это и
		// избавляет от layout-чтения на каждом кадре скролла, и оставляет
		// прогресс линейным, когда заголовок схлопывается и коробка в
		// середине пина становится ниже. Реально она при этом отлипает чуть
		// позже расчётного, то есть последний слайд держится на полсотни
		// пикселей дольше — это заметно лучше, чем скачок слайда на
		// пересчёте.
		var rect = root.getBoundingClientRect();
		var distance = rect.height - pinHeight;

		if ( distance <= 0 ) {
			return;
		}

		var progress = Math.min( 1, Math.max( 0, ( pinTop - rect.top ) / distance ) );

		// Заголовок уходит в самом начале пина, а не тянется всю фазу:
		// по заданию он «появляется вначале, после пропадает».
		root.classList.toggle( 'is-scrolled', progress > introShare * 0.3 );

		var slideProgress = ( progress - introShare ) / ( 1 - introShare );
		var index = slideProgress <= 0
			? 0
			: Math.min( slides.length - 1, Math.floor( slideProgress * slides.length ) );

		if ( index === activeIndex ) {
			return;
		}

		var previousIndex = activeIndex;

		activeIndex = index;

		// .is-past/.is-active задают направление перехода: уходящий экран
		// уезжает вверх, следующий приезжает снизу (см. «ленту» телефонов в
		// theme.css). Без деления на «до» и «после» при скролле вверх
		// картинка возвращалась бы с той же стороны, что и при скролле вниз,
		// и движение перестало бы читаться как прокрутка.
		//
		// .is-leaving — только что ушедший слайд: анимируются лишь он и
		// новый активный. Быстрый свайп на mobile перескакивает через
		// несколько слайдов за кадр, и без этого пропущенные экраны
		// пролетали сквозь стекло вместе с уходящим и приходящим.
		for ( var i = 0; i < slides.length; i++ ) {
			slides[ i ].classList.toggle( 'is-active', i === index );
			slides[ i ].classList.toggle( 'is-past', i < index );
			slides[ i ].classList.toggle( 'is-leaving', i === previousIndex );
		}
	}

	function onScroll() {
		if ( ticking ) {
			return;
		}

		ticking = true;
		window.requestAnimationFrame( render );
	}

	function onResize() {
		measure();
		onScroll();
	}

	window.addEventListener( 'scroll', onScroll, { passive: true } );
	window.addEventListener( 'resize', onResize );

	measure();
	render();

	// Картинки отдают размер ещё до загрузки (у всех проставлены width/height),
	// поэтому measure() выше уже точен. Пересчёт после load — страховка на
	// случай позднего шрифта: заголовок может перенестись иначе и изменить
	// высоту коробки.
	window.addEventListener( 'load', onResize );
}() );
