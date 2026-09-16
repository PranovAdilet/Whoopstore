/**
 * Редактор блока «Результаты поиска» (whoopstore/search-results).
 *
 * Настроек у блока нет: и заголовок, и карточки зависят только от поискового
 * запроса. Превью рисует сервер той же функцией, что и фронт; в редакторе
 * запроса нет, поэтому превью пустое — это ожидаемо.
 */
( function ( wp ) {
	var el = wp.element.createElement;

	wp.blocks.registerBlockType( 'whoopstore/search-results', {
		edit: function () {
			return el(
				'div',
				wp.blockEditor.useBlockProps(),
				el(
					wp.components.Placeholder,
					{
						icon: 'search',
						label: 'Результаты поиска',
						instructions: 'Найденные товары карточками каталога. Виден только на странице результатов поиска.'
					}
				)
			);
		},

		// Блок динамический: разметку печатает render.php.
		save: function () {
			return null;
		}
	} );
} )( window.wp );
