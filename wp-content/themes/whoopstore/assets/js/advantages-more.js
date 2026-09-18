/**
 * Раскрытие/сворачивание секции "Более 15 ключевых показателей…" кнопкой
 * под сеткой (синхронизированный паттерн «Главная — 15 показателей
 * здоровья», wp_block 299; до 2026-09-18 — patterns/advantages-sticky-scroll.php).
 *
 * До этой правки кнопка была обычной ссылкой href="#" — клик просто
 * прокручивал страницу в начало ("переносило куда-то"), а остальные
 * показатели вообще отсутствовали в разметке.
 *
 * Что от Figma, а что сверх неё: сам раскрытый состав секции — из
 * состояния "Раскрытый" (node 1:561,
 * docs/figma-design/raw/states/1-561-raskrytyy-desktop.code.tsx.txt).
 * Сворачивание — решение сверх макета, по прямой просьбе пользователя:
 * в кадре "Раскрытый" кнопки под сеткой нет вообще, то есть в Figma это
 * одноразовое раскрытие без обратного хода, и подписи "Свернуть" в макете
 * не существует. «Посмотреть ещё» — текст самой кнопки, он правится в
 * редакторе вместе с остальным текстом секции. «Свернуть» — константа ниже:
 * раньше обе подписи лежали в data-атрибутах сырого HTML, но скрытая часть
 * теперь группа блоков, а у core/group таких атрибутов нет.
 *
 * Разметка кнопки — штатный core/button (ссылка), чтобы блок остался
 * валидным для редактора; семантику раскрывающего элемента
 * (role/aria-expanded/aria-controls) навешивает этот скрипт, он же и
 * отменяет переход по href.
 *
 * Прогрессивное улучшение: часть скрыта правилом
 * .advantages__more:not(.is-open) из theme.css, которое стоит в <head> (без
 * JS карточки не видны, но они есть в DOM и индексируются). Скрипт только
 * переключает класс is-open. Наоборот — показывать всё и схлопывать
 * скриптом — нельзя: footer-скрипт выполняется после первой отрисовки, был бы
 * видимый скачок вёрстки. Раньше скрывал атрибут hidden, но у группы блоков
 * его нет.
 */
( function () {
	var toggle = document.querySelector( '.advantages__more-toggle .wp-block-button__link' );
	var more = document.getElementById( 'advantages-more' );

	if ( ! toggle || ! more ) {
		return;
	}

	var labelMore = toggle.textContent;
	var labelLess = 'Свернуть';

	toggle.setAttribute( 'role', 'button' );
	toggle.setAttribute( 'aria-controls', 'advantages-more' );
	toggle.setAttribute( 'aria-expanded', 'false' );

	toggle.addEventListener( 'click', function ( event ) {
		event.preventDefault();

		var expanded = more.classList.toggle( 'is-open' );

		toggle.textContent = expanded ? labelLess : labelMore;
		toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );

		// Фокус остаётся на кнопке (стандартное поведение disclosure) —
		// уводить его некуда, кнопка никуда не исчезает.

		// При сворачивании страница над кнопкой становится короче, и кнопка
		// уезжает вверх ровно на высоту скрытой части — вплоть до выхода за
		// верхний край экрана. Браузер сам этого не компенсирует (позиция
		// скролла сохраняется, а не содержимое под курсором), поэтому
		// возвращаем кнопку в поле зрения, но только если её действительно
		// не видно — иначе лишний рывок там, где ничего не разъехалось.
		if ( ! expanded ) {
			var box = toggle.getBoundingClientRect();
			var viewport = window.innerHeight || document.documentElement.clientHeight;

			if ( box.top < 0 || box.bottom > viewport ) {
				toggle.scrollIntoView( { block: 'center' } );
			}
		}
	} );
} )();
