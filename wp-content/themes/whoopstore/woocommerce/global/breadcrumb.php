<?php
/**
 * Хлебные крошки WooCommerce в оформлении темы.
 *
 * Переопределение woocommerce/templates/global/breadcrumb.php.
 * Разметка и классы — те же, что у крошек подстраниц-каталогов
 * (inc/page-head-component.php, стили .page-head__crumb* в theme.css):
 * иконка "домой" → наклонный разделитель → текст. Отдельного набора
 * классов под крошки товара не заводим — у них та же визуальная роль
 * (правило 14 CLAUDE.md), отличается только цвет последней крошки
 * (в макете товара она белая, на каталогах — приглушённая), это
 * модификатор .page-head__crumbs--current-bright.
 *
 * По Figma (node 30:182/30:183 desktop, 30:1206/30:1207 mobile):
 * промежуточные крошки rgba(255,255,255,0.8), последняя — белая,
 * 14px desktop / 13px mobile.
 *
 * @package Whoopstore
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $breadcrumb ) ) {
	return;
}

$whoopstore_icons = [
	'home'      => get_theme_file_uri( 'assets/icons/breadcrumb-home.svg' ),
	'separator' => get_theme_file_uri( 'assets/icons/breadcrumb-separator.svg' ),
];
$whoopstore_last  = count( $breadcrumb ) - 1;
?>
<nav class="page-head__crumbs page-head__crumbs--current-bright woocommerce-breadcrumb" aria-label="<?php esc_attr_e( 'Хлебные крошки', 'whoopstore' ); ?>">
	<?php foreach ( $breadcrumb as $whoopstore_key => $whoopstore_crumb ) : ?>
		<?php if ( $whoopstore_key > 0 ) : ?>
			<img class="page-head__crumb-sep" src="<?php echo esc_url( $whoopstore_icons['separator'] ); ?>" alt="" width="15" height="15" />
		<?php endif; ?>

		<?php if ( 0 === $whoopstore_key && ! empty( $whoopstore_crumb[1] ) ) : ?>
			<?php /* Первая крошка ("Главная") в макете — иконка домика, без текста. */ ?>
			<a class="page-head__crumb-home" href="<?php echo esc_url( $whoopstore_crumb[1] ); ?>" aria-label="<?php echo esc_attr( $whoopstore_crumb[0] ); ?>">
				<img src="<?php echo esc_url( $whoopstore_icons['home'] ); ?>" alt="" width="15" height="15" />
			</a>
		<?php elseif ( ! empty( $whoopstore_crumb[1] ) && $whoopstore_key !== $whoopstore_last ) : ?>
			<a class="page-head__crumb-link" href="<?php echo esc_url( $whoopstore_crumb[1] ); ?>"><?php echo esc_html( $whoopstore_crumb[0] ); ?></a>
		<?php else : ?>
			<span class="page-head__crumb-current" aria-current="page"><?php echo esc_html( $whoopstore_crumb[0] ); ?></span>
		<?php endif; ?>
	<?php endforeach; ?>
</nav>
