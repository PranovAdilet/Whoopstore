/**
 * Редактор блока «Сетка товаров» (whoopstore/product-grid).
 *
 * Без JSX и сборки — чистый wp.element.createElement, как и весь остальной
 * JS темы. Превью рисует сервер (ServerSideRender) той же PHP-функцией, что
 * и фронт: в редакторе видны настоящие товары с настоящими ценами.
 *
 * Список категорий приходит из PHP инлайном — window.whoopstoreProductCategories
 * (см. whoopstore_product_category_options() в inc/blocks.php).
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;

	var ALL_CATEGORIES = window.whoopstoreProductCategories || [];

	var LAYOUTS = [
		{ label: 'Обычная сетка', value: 'grid' },
		{ label: 'Карточки тарифов (водяной знак и мокап)', value: 'plan' }
	];

	function categoryOptions() {
		var options = [ { label: '— выберите категорию —', value: '' } ];

		ALL_CATEGORIES.forEach( function ( category ) {
			options.push( {
				label: category.name + ' (' + category.count + ')',
				value: category.slug
			} );
		} );

		return options;
	}

	wp.blocks.registerBlockType( 'whoopstore/product-grid', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			var attributes = props.attributes;

			function set( key ) {
				return function ( value ) {
					var patch = {};
					patch[ key ] = value;
					props.setAttributes( patch );
				};
			}

			return el(
				wp.element.Fragment,
				null,
				el(
					wp.blockEditor.InspectorControls,
					null,
					el(
						wp.components.PanelBody,
						{ title: 'Товары' },
						el( wp.components.SelectControl, {
							label: 'Категория',
							value: attributes.category,
							options: categoryOptions(),
							onChange: set( 'category' )
						} ),
						el( wp.components.TextControl, {
							label: 'Заголовок секции',
							value: attributes.title,
							help: 'Пусто — берётся название категории.',
							onChange: set( 'title' )
						} ),
						el( wp.components.TextControl, {
							label: 'Якорь',
							value: attributes.anchor,
							help: 'Нужен, если на секцию ведут ссылки внутри страницы. Например: buy.',
							onChange: set( 'anchor' )
						} ),
						el( wp.components.SelectControl, {
							label: 'Оформление',
							value: attributes.layout,
							options: LAYOUTS,
							onChange: set( 'layout' )
						} ),
						el(
							'p',
							{ style: { fontSize: '12px', color: '#757575' } },
							'Порядок карточек — «Порядок в меню» у товара. Названия, цены и фото берутся из самих товаров.'
						)
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'whoopstore/product-grid',
						attributes: attributes
					} )
				)
			);
		},

		// Блок динамический: в контенте сохраняются только настройки секции.
		save: function () {
			return null;
		}
	} );
} )( window.wp );
