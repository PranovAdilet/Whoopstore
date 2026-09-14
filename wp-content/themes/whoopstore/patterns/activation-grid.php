<?php
/**
 * Title: Activation & renewal grid
 * Slug: whoopstore/activation-grid
 *
 * Секция "Активация и продление устройства" на Главной: подзаголовок + 4
 * карточки — продление подписки для ONE/Peak/Life и отдельная карточка
 * активации (привязка иностранной карты). Цены — из Figma дословно.
 *
 * Правка 2026-09-10 (по замечанию пользователя: "на Главной эта секция
 * выглядит не как в дизайне, а на /podpiski/ — правильно"). Сверка
 * raw-дампов показала, что это ОДНА И ТА ЖЕ карточка, что в секции
 * "Выберите подписку" на странице "Подписки": мокап телефона с
 * приложением, фото браслета справа, слово-подложка ONE/PEAK/LIFE и
 * растворение низа сцены — а не фото устройства с маленьким бейджем
 * модели, как было здесь раньше (первый проход по Главной делался только
 * по get_metadata мобильного фрейма, где эти слои читались как бейдж).
 * Узлы: homepage-desktop 1:531 ONE / 1:546 PEAK / 1:516 LIFE / 1:142
 * активация; homepage-mobile 1:3967 / 1:3981 / 1:3995 / 1:3603.
 *
 * Поэтому карточки печатает та же whoopstore_device_card() с модификатором
 * `plan`, что и подписки, плюс два уточнения геометрии из макета Главной
 * (см. .device-card--plan-compact / --plan-solo в theme.css):
 *  - `plan-compact` — карточка Главной 360×464, а не 497: сцена 221 вместо
 *    246, фото браслета 153 вместо 182, отступ до названия 15 вместо 30.
 *    На mobile сжатия нет — там обе секции в макете совпадают до десятых.
 *  - `plan-solo` — четвёртая карточка: только телефон, шире и по центру,
 *    без слова-подложки и без фото браслета (так в макете).
 *
 * Ассеты — те же, что у карточек тарифов, новых не потребовалось:
 *  - мокап телефона `subscriptions-plan-phone.png` — сверено с узлом
 *    1:536 "Screen" Главной (f2ccf532…): тот же экран Stress Monitor, тот
 *    же кадр, отличается только разрешением экспорта;
 *  - фото браслета XxHSGND0LxCtAUYxCMxR-2.png — узел "XxHSGND0LxCtAUYxCMxR
 *    2" Главной (e1b0b1ae…) Figma снова отдаёт битым (716×467, 1.4 КБ,
 *    пустой PNG), поэтому взят тот же реальный ассет, что уже подставлен
 *    в карточки тарифов; пропорции узла совпадают.
 */

$whoopstore_activation_phone = '/wp-content/uploads/2026/09/subscriptions-plan-phone.webp';
$whoopstore_activation_band  = '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-2.webp';

$whoopstore_activation = [
	[
		'name'      => 'Подписка Whoop One (подключение/продление)',
		'price'     => '21 990,00 ₽',
		'watermark' => 'ONE',
		'modifier'  => [ 'plan', 'plan-compact' ],
		'phone'     => $whoopstore_activation_phone,
		'image'     => $whoopstore_activation_band,
	],
	[
		'name'      => 'Подписка Whoop Peak (подключение/продление)',
		'price'     => '27 990,00 ₽',
		'watermark' => 'PEAK',
		'modifier'  => [ 'plan', 'plan-compact' ],
		'phone'     => $whoopstore_activation_phone,
		'image'     => $whoopstore_activation_band,
	],
	[
		'name'      => 'Подписка Whoop Life (подключение/продление)',
		'price'     => '34 990,00 ₽',
		'watermark' => 'LIFE',
		'modifier'  => [ 'plan', 'plan-compact' ],
		'phone'     => $whoopstore_activation_phone,
		'image'     => $whoopstore_activation_band,
	],
	[
		'name'     => 'Активация Whoop (привязка иностранной карты)',
		'price'    => '2 990,00 ₽',
		'modifier' => [ 'plan', 'plan-compact', 'plan-solo' ],
		'phone'    => $whoopstore_activation_phone,
	],
];
?>
<!-- wp:group {"className":"activation-grid","layout":{"type":"constrained"}} -->
<div class="wp-block-group activation-grid">

	<!-- wp:heading {"level":2,"className":"activation-grid__title card-heading"} -->
	<h2 class="wp-block-heading activation-grid__title card-heading">Активация и продление устройства</h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="activation-grid__list">
		<?php foreach ( $whoopstore_activation as $item ) : whoopstore_device_card( $item ); endforeach; ?>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
