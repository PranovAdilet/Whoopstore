/**
 * Редактор блока «Каталог по категориям» (whoopstore/category-catalog).
 *
 * Без JSX и сборки — чистый wp.element.createElement, как и весь остальной
 * JS темы. Превью рисует сервер (ServerSideRender) той же PHP-функцией, что
 * и фронт: в редакторе видны настоящие товары WooCommerce с настоящими
 * ценами, а не заглушки.
 *
 * Список категорий приходит из PHP инлайном — window.whoopstoreProductCategories
 * (см. whoopstore_product_category_options() в inc/blocks.php).
 *
 * В атрибутах блока хранится только упорядоченный массив slug'ов. Порядок
 * разделов на странице = порядок в этом массиве, поэтому стрелки вверх/вниз
 * меняют именно его.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;

	var ALL_CATEGORIES = window.whoopstoreProductCategories || [];

	function categoryName( slug ) {
		for ( var i = 0; i < ALL_CATEGORIES.length; i++ ) {
			if ( ALL_CATEGORIES[ i ].slug === slug ) {
				return ALL_CATEGORIES[ i ].name + ' (' + ALL_CATEGORIES[ i ].count + ')';
			}
		}

		// Категорию могли переименовать или удалить уже после вставки блока —
		// показываем сам slug, чтобы было видно, что чинить.
		return slug + ' — категория не найдена';
	}

	function move( list, from, to ) {
		var copy = list.slice();
		var item = copy.splice( from, 1 )[ 0 ];
		copy.splice( to, 0, item );
		return copy;
	}

	wp.blocks.registerBlockType( 'whoopstore/category-catalog', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			var selected = props.attributes.categories || [];

			function setCategories( next ) {
				props.setAttributes( { categories: next } );
			}

			var rows = selected.map( function ( slug, index ) {
				return el(
					'div',
					{
						key: slug,
						style: {
							display: 'flex',
							alignItems: 'center',
							gap: '4px',
							marginBottom: '6px'
						}
					},
					el( 'span', { style: { flex: '1 1 auto', fontSize: '12px' } }, categoryName( slug ) ),
					el( wp.components.Button, {
						size: 'small',
						variant: 'secondary',
						disabled: 0 === index,
						label: 'Выше',
						showTooltip: true,
						onClick: function () {
							setCategories( move( selected, index, index - 1 ) );
						}
					}, '↑' ),
					el( wp.components.Button, {
						size: 'small',
						variant: 'secondary',
						disabled: index === selected.length - 1,
						label: 'Ниже',
						showTooltip: true,
						onClick: function () {
							setCategories( move( selected, index, index + 1 ) );
						}
					}, '↓' ),
					el( wp.components.Button, {
						size: 'small',
						isDestructive: true,
						label: 'Убрать раздел',
						showTooltip: true,
						onClick: function () {
							setCategories( selected.filter( function ( item ) {
								return item !== slug;
							} ) );
						}
					}, '✕' )
				);
			} );

			var available = ALL_CATEGORIES.filter( function ( category ) {
				return -1 === selected.indexOf( category.slug );
			} ).map( function ( category ) {
				return {
					label: category.name + ' (' + category.count + ')',
					value: category.slug
				};
			} );

			available.unshift( { label: '— добавить раздел —', value: '' } );

			return el(
				wp.element.Fragment,
				null,
				el(
					wp.blockEditor.InspectorControls,
					null,
					el(
						wp.components.PanelBody,
						{ title: 'Разделы каталога' },
						selected.length
							? rows
							: el( 'p', { style: { fontSize: '12px' } }, 'Ни одного раздела не выбрано.' ),
						el( wp.components.SelectControl, {
							label: 'Добавить раздел',
							value: '',
							options: available,
							onChange: function ( slug ) {
								if ( slug ) {
									setCategories( selected.concat( [ slug ] ) );
								}
							}
						} ),
						el(
							'p',
							{ style: { fontSize: '12px', color: '#757575' } },
							'Заголовок раздела — название категории, карточки — её товары. Цены и фото берутся из товаров.'
						)
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'whoopstore/category-catalog',
						attributes: props.attributes
					} )
				)
			);
		},

		// Блок динамический: в post_content сохраняются только slug'и.
		save: function () {
			return null;
		}
	} );
} )( window.wp );
