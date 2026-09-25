<?php
/**
 * "Вам может понравиться" — блок похожих товаров (Figma 30:185…30:243
 * desktop, 30:1248…30:1274 mobile).
 *
 * Переопределение woocommerce/templates/single-product/related.php.
 * Вместо штатного цикла content-product.php выводим ту же карточку
 * .device-card, что и все каталожные секции сайта (inc/card-component.php):
 * в макете это буквально та же карточка — фон .surface, скругление 20,
 * название 20px SemiBold, цена, пилюля "Купить" (правило 14 CLAUDE.md).
 * Лента с горизонтальной прокруткой и стрелками — потому что в макете
 * рядом с карточками стоят стрелки-навигация и на mobile карточки
 * выходят за край экрана.
 *
 * @package Whoopstore
 * @version 10.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $related_products ) ) {
	return;
}

// Из шаблона ядра (10.3.0): секция идёт ниже первого экрана, поэтому её
// картинки не должны попадать в порог «грузить сразу».
if ( function_exists( 'wp_increase_content_media_count' ) ) {
	$whoopstore_media_count = wp_increase_content_media_count( 0 );

	if ( $whoopstore_media_count < wp_omit_loading_attr_threshold() ) {
		wp_increase_content_media_count( wp_omit_loading_attr_threshold() - $whoopstore_media_count );
	}
}
?>
<section class="related products related-carousel">

	<?php /* Заголовок — в обычном потоке секции, слева в контентной колонке
	        (уточнение заказчика 2026-09-25), а не над первой карточкой
	        центрируемой ленты. */ ?>
	<h2 class="card-heading related-carousel__title">Вам может понравиться</h2>

	<div class="related-carousel__stage">

		<div class="related-carousel__nav">
			<button type="button" class="carousel-arrow carousel-arrow--prev related-carousel__arrow" aria-label="Предыдущие товары"></button>
			<button type="button" class="carousel-arrow carousel-arrow--next related-carousel__arrow" aria-label="Следующие товары"></button>
		</div>

		<div class="related-carousel__track">
			<?php foreach ( $related_products as $related_product ) : ?>
				<?php
				// Данные товара (название, цена, ссылка, фото, бейдж) — общий
				// адаптер inc/woocommerce/product-card.php. Текст кнопки — решение
				// секции по макету (node 30:217). Переменные товары ведут на свою
				// страницу — выбрать размер всё равно нужно там.
				whoopstore_device_card( array_merge( whoopstore_product_card_data( $related_product ), [
					'button_text' => 'Купить',
				] ) );
				?>
			<?php endforeach; ?>
		</div>

	</div>

</section>
