/**
 * Цена вариативного товара показывается один раз (правка 2026-11-11).
 *
 * Если у вариантов разные цены («Продление подписки Whoop 4.0»: 12 и 24 мес.),
 * WooCommerce печатает вверху диапазон «27 990 – 45 990 ₽», а после выбора —
 * ещё одну строку с ценой варианта под переключателем. Вместо этого здесь
 * меняется сама цена вверху: при выборе варианта — на его цену, при сбросе —
 * обратно на диапазон. Строка под переключателем скрыта в woocommerce.css.
 *
 * Слушаем штатные события формы WooCommerce (found_variation / reset_data),
 * подбор варианта не трогаем. Если у вариантов цена одна, WooCommerce не
 * отдаёт price_html — цена вверху и так верная, и она не меняется. Без JS
 * остаётся штатный диапазон.
 */
( function ( $ ) {
	if ( ! $ ) {
		return;
	}

	$( 'form.variations_form' ).each( function () {
		var $form = $( this );
		var $price = $form.closest( '.product-page__summary' ).children( '.price' ).first();

		if ( ! $price.length ) {
			return;
		}

		var initial = $price.html();

		$form.on( 'found_variation', function ( event, variation ) {
			var html = variation && variation.price_html ? $( '<div>' ).html( variation.price_html ).find( '.price' ).html() : '';

			$price.html( html || initial );
		} );

		$form.on( 'reset_data', function () {
			$price.html( initial );
		} );
	} );
} )( window.jQuery );
