<?php
/**
 * Общие мелочи скриптов проверки.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

/**
 * Дополняет строку пробелами по числу символов, а не байтов.
 *
 * printf() считает байты, поэтому колонки с кириллицей в отчётах разъезжаются.
 *
 * @param string $text  Текст.
 * @param int    $width Ширина колонки.
 * @return string
 */
function whoopstore_qa_pad( $text, $width ) {
	$text = mb_strimwidth( (string) $text, 0, $width, '…' );

	return $text . str_repeat( ' ', max( 0, $width - mb_strlen( $text ) ) );
}
