<?php
/**
 * Title: Подписки — купить Whoop с подпиской
 * Slug: whoopstore/subscriptions-buy
 *
 * Секция "Купить Whoop с подпиской" (Figma node 1:3018, заголовок @y=2917,
 * карточки @y=2982). Это ровно те же три карточки, что в первом ряду сетки
 * устройств на Главной: сверены по raw-дампам обеих страниц — совпадают
 * названия, обе цены и бейдж "Распродажа".
 *
 * Поэтому данных здесь нет вовсе: список берётся из общего источника
 * whoopstore_annual_plan_devices() (inc/product-data.php), тот же, что
 * использует patterns/device-grid.php. Разметку секции печатает общая
 * whoopstore_catalog_section() — заголовок .card-heading + сетка
 * .catalog-section__list, как на страницах ремешков.
 */
?>
<!-- wp:html -->
<?php whoopstore_catalog_section( 'Купить Whoop с подпиской', whoopstore_annual_plan_devices(), '', 'buy' ); ?>
<!-- /wp:html -->
