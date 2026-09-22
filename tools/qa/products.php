<?php
/**
 * Отчёт о полноте данных каталога.
 *
 * ТЗ требует проверить весь каталог перед приёмкой. Товары заведены из данных
 * макета, поэтому часть полей заполнить было нечем — выдумывать цены, описания
 * и артикулы нельзя. Скрипт показывает, чего именно не хватает, чтобы запрос
 * к заказчику был точным списком, а не «пришлите описания».
 *
 * Запуск:
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/products.php
 *   /usr/local/php/cgi/8.2/bin/php tools/qa/products.php --list   (поимённо)
 *
 * Код возврата 1 — есть товары, которые нельзя продать (нет цены).
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

require __DIR__ . '/lib.php';
require dirname( __DIR__, 2 ) . '/wp-load.php';

/**
 * Что значит незаполненное поле. Порядок — по тяжести последствий.
 */
const WHOOPSTORE_PRODUCT_GAPS = [
	'цена'     => 'товар нельзя купить',
	'вес'      => 'курьерская служба не посчитает тариф',
	'габариты' => 'курьерская служба не посчитает тариф',
	'фото'     => 'в карточке остаётся заглушка',
	'описание' => 'пустая карточка товара, нет description для поиска',
	'артикул'  => 'нужен, только если заказчик ведёт учёт по артикулам',
];

$list = in_array( '--list', $argv, true );

/**
 * Чего не хватает конкретному товару.
 *
 * Вес и габариты спрашиваются только у физических товаров: подписки и
 * активацию не везут. Они нужны не для витрины, а для расчёта тарифа
 * доставки — без них курьерская служба не посчитает стоимость.
 *
 * @param WC_Product $product Товар.
 * @return string[] Названия недостающих полей.
 */
function whoopstore_product_gaps( $product ) {
	$gaps = [];

	if ( '' === (string) $product->get_price() ) {
		$gaps[] = 'цена';
	}

	if ( ! $product->get_image_id() ) {
		$gaps[] = 'фото';
	}

	if ( '' === trim( $product->get_description() ) ) {
		$gaps[] = 'описание';
	}

	if ( ! $product->get_category_ids() ) {
		$gaps[] = 'категория';
	}

	if ( '' === (string) $product->get_sku() ) {
		$gaps[] = 'артикул';
	}

	if ( ! $product->is_virtual() ) {
		if ( '' === (string) $product->get_weight() ) {
			$gaps[] = 'вес';
		}

		if ( ! $product->has_dimensions() ) {
			$gaps[] = 'габариты';
		}
	}

	return $gaps;
}

$products = wc_get_products( [ 'limit' => -1, 'status' => 'any', 'orderby' => 'title', 'order' => 'ASC' ] );
$totals   = [];
$rows     = [];
$unsable  = [];

foreach ( $products as $product ) {
	$gaps = whoopstore_product_gaps( $product );

	foreach ( $gaps as $gap ) {
		$totals[ $gap ] = ( $totals[ $gap ] ?? 0 ) + 1;
	}

	if ( in_array( 'цена', $gaps, true ) ) {
		$unsable[] = $product;
	}

	if ( $gaps ) {
		$rows[] = [ $product->get_id(), $product->get_name(), implode( ', ', $gaps ) ];
	}
}

printf( "Товаров в каталоге: %d\n", count( $products ) );
printf( "Со всеми заполненными полями: %d\n\n", count( $products ) - count( $rows ) );

echo whoopstore_qa_pad( 'Поле', 10 ) . ' ' . whoopstore_qa_pad( 'Нет у', 6 ) . " Что это значит\n";

foreach ( WHOOPSTORE_PRODUCT_GAPS as $gap => $meaning ) {
	if ( empty( $totals[ $gap ] ) ) {
		continue;
	}

	echo whoopstore_qa_pad( $gap, 10 ) . ' ' . whoopstore_qa_pad( $totals[ $gap ], 6 ) . ' ' . $meaning . "\n";
}

if ( $unsable ) {
	echo "\nНельзя купить (нет цены):\n";

	foreach ( $unsable as $product ) {
		printf( "  #%d %s\n", $product->get_id(), $product->get_name() );
	}
}

if ( $list ) {
	echo "\nПоимённо:\n";

	foreach ( $rows as $row ) {
		echo '  #' . whoopstore_qa_pad( $row[0], 5 ) . $row[1] . "\n";
		echo '         → ' . $row[2] . "\n";
	}
} else {
	echo "\nПоимённый список — с флагом --list.\n";
}

echo "\nВес и габариты спрашиваются только у физических товаров: подписки и\n";
echo "активацию не везут.\n";

exit( $unsable ? 1 : 0 );
