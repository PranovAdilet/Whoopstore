/**
 * Редактор блока «Статьи блога» (whoopstore/post-grid).
 *
 * Настроек у блока нет: список статей собирает сервер из опубликованных
 * записей. Сами статьи правятся в разделе «Записи».
 */
( function ( wp ) {
	var el = wp.element.createElement;

	wp.blocks.registerBlockType( 'whoopstore/post-grid', {
		edit: function () {
			return el(
				'div',
				wp.blockEditor.useBlockProps(),
				el(
					wp.components.Placeholder,
					{
						icon: 'grid-view',
						label: 'Статьи блога',
						instructions: 'Опубликованные статьи плитками. Статьи добавляются и правятся в разделе «Записи».'
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
