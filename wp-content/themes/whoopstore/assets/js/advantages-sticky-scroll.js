/**
 * Sticky-scroll storytelling секции "Преимущества" на Главной.
 * Локальный скрипт для одного конкретного паттерна (см. sanity-check —
 * единственный подтверждённый потребитель этой механики сейчас).
 * Подключается только на Главной (is_front_page()).
 */
( function () {
	var root = document.querySelector( '[data-advantages]' );
	if ( ! root || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}

	var items = root.querySelectorAll( '[data-advantages-item]' );
	var titleEl = root.querySelector( '[data-advantages-title]' );
	var textEl = root.querySelector( '[data-advantages-text]' );

	if ( ! items.length || ! titleEl || ! textEl ) {
		return;
	}

	var observer = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}

				items.forEach( function ( item ) {
					item.classList.remove( 'is-active' );
				} );
				entry.target.classList.add( 'is-active' );
				titleEl.textContent = entry.target.getAttribute( 'data-title' ) || '';
				textEl.textContent = entry.target.getAttribute( 'data-text' ) || '';
			} );
		},
		{ threshold: 0.5 }
	);

	items.forEach( function ( item ) {
		observer.observe( item );
	} );
} )();
