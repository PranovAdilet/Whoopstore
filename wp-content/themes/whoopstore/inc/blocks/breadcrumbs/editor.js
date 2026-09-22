/**
 * Редактор блока «Хлебные крошки» (whoopstore/breadcrumbs).
 *
 * Без JSX и сборки — чистый wp.element.createElement, как и весь остальной
 * JS темы. Превью рисует сервер (ServerSideRender) той же PHP-функцией, что
 * и фронт, поэтому в редакторе видно настоящую разметку, а не её копию.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'whoopstore/breadcrumbs', {
		edit: function ( props ) {
			// Обёртка нужна только редактору — она даёт блоку рамку выделения
			// и не попадает на фронт: save возвращает null, разметку печатает
			// render.php.
			var blockProps = useBlockProps();

			return el(
				wp.element.Fragment,
				null,
				el(
					wp.blockEditor.InspectorControls,
					null,
					el(
						wp.components.PanelBody,
						{ title: 'Хлебные крошки' },
						el( wp.components.TextControl, {
							label: 'Текущий раздел',
							value: props.attributes.label,
							help: 'Последняя крошка. Пусто — подставится заголовок страницы.',
							onChange: function ( value ) {
								props.setAttributes( { label: value } );
							}
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'whoopstore/breadcrumbs',
						attributes: props.attributes
					} )
				)
			);
		},

		// Блок динамический: в post_content сохраняется только тег с
		// атрибутами, разметку каждый раз печатает сервер.
		save: function () {
			return null;
		}
	} );
} )( window.wp );
