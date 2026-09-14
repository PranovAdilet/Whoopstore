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
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $related_products ) ) {
	return;
}
?>
<section class="related products related-carousel">

	<div class="related-carousel__stage">

		<?php /* Заголовок лежит внутри ряда карусели, а не рядом с ним: лента
		        центрируется по экрану, и заголовок должен ехать вместе с ней,
		        оставаясь ровно над первой карточкой (как в макете, node
		        30:186/30:211). Раскладку задаёт grid — см. .related-carousel__stage. */ ?>
		<h2 class="card-heading related-carousel__title">Вам может понравиться</h2>

		<div class="related-carousel__nav">
			<button type="button" class="carousel-arrow carousel-arrow--prev related-carousel__arrow" aria-label="Предыдущие товары"></button>
			<button type="button" class="carousel-arrow carousel-arrow--next related-carousel__arrow" aria-label="Следующие товары"></button>
		</div>

		<div class="related-carousel__track">
			<?php foreach ( $related_products as $related_product ) : ?>
				<?php
				whoopstore_device_card( [
					'name'        => $related_product->get_name(),
					'price_html'  => $related_product->get_price_html(),
					// woocommerce_single (600px), а не woocommerce_thumbnail (300px):
					// медиа-зона карточки — 320px, на retina 300px заметно мылит.
					// Если у фото такого размера нет (оригинал мельче 600px),
					// WordPress сам отдаст оригинал.
					'image'       => wp_get_attachment_image_url( $related_product->get_image_id(), 'woocommerce_single' ),
					'href'        => $related_product->get_permalink(),
					// Текст кнопки из макета (node 30:217). Переменные товары
					// ведут на свою страницу — выбрать размер всё равно нужно там.
					'button_text' => 'Купить',
				] );
				?>
			<?php endforeach; ?>
		</div>

	</div>

</section>
