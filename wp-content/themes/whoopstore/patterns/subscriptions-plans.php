<?php
/**
 * Title: Подписки — выберите подписку
 * Slug: whoopstore/subscriptions-plans
 *
 * Секция "Выберите подписку": заголовок + 3 карточки тарифов.
 * Источник — Figma, desktop node `1:3018` (сырой дамп
 * docs/figma-design/raw/page-subscriptions-desktop.code.tsx.txt, узлы
 * 1:3019 LIFE / 1:3034 ONE / 1:3049 PEAK; заголовок 1:3117 @y=737,
 * карточки @y=789, x = 40/420/800 — та же сетка 3×360 с шагом 20px, что и
 * в каталогах ремешков).
 *
 * Разметку секции печатает общая функция whoopstore_catalog_section()
 * (inc/card-component.php) — та же, что на страницах "Ремешки 5.0/MG":
 * заголовок .card-heading + сетка .catalog-section__list. Карточка — та же
 * whoopstore_device_card() с модификатором `plan` (см. .device-card--plan
 * в theme.css): отличается только медиа-зона, а заголовок/цена/кнопка —
 * общие.
 *
 * Ассеты:
 *  - мокап телефона — собран из двух слоёв самой Figma (node 1:3024
 *    "Screen" + 1:3025 "Silver") в один PNG 314×642 (=2× от 156.743×320.446
 *    в макете), скачаны напрямую по ссылкам из raw-дампа;
 *  - фото браслета — узел "XxHSGND0LxCtAUYxCMxR 2". ⚠️ Экспорт этого узла
 *    Figma отдаёт битым (716×467, 1.4 КБ — пустой PNG, та же поломанная
 *    заливка, что уже встречалась на Главной). Взят одноимённый реальный
 *    ассет, который пользователь ранее вручную перезалил в Медиатеку для
 *    карточек Главной — XxHSGND0LxCtAUYxCMxR-2.png (194×230, пропорции
 *    совпадают с узлом 154×182). Это осознанная подстановка того же
 *    исходника, а не случайное совпадение имени.
 *
 * Данные (названия, цены) — из макета дословно. Товаров WooCommerce под
 * них нет, кнопки ведут на "#" — по тем же причинам, что и на страницах
 * ремешков (см. straps-50-catalog.php).
 */

$whoopstore_subscription_plans = [
	[
		'name'      => 'Подписка Whoop Life (подключение/продление)',
		'price'     => '34 990,00 ₽',
		'watermark' => 'LIFE',
		'modifier'  => 'plan',
		'phone'     => '/wp-content/uploads/2026/09/subscriptions-plan-phone.webp',
		'image'     => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp',
	],
	[
		'name'      => 'Подписка Whoop One (подключение/продление)',
		'price'     => '21 990,00 ₽',
		'watermark' => 'ONE',
		'modifier'  => 'plan',
		'phone'     => '/wp-content/uploads/2026/09/subscriptions-plan-phone.webp',
		'image'     => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp',
	],
	[
		'name'      => 'Подписка Whoop Peak (подключение/продление)',
		'price'     => '27 990,00 ₽',
		'watermark' => 'PEAK',
		'modifier'  => 'plan',
		'phone'     => '/wp-content/uploads/2026/09/subscriptions-plan-phone.webp',
		'image'     => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp',
	],
];
?>
<!-- wp:html -->
<?php whoopstore_catalog_section( 'Выберите подписку', $whoopstore_subscription_plans ); ?>
<!-- /wp:html -->
