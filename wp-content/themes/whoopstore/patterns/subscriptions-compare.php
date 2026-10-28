<?php
/**
 * Title: Подписки — сравните преимущества
 * Slug: whoopstore/subscriptions-compare
 *
 * Секция "Сравните преимущества": светлая карточка с пояснением и тремя
 * мокапами телефона + сравнительная таблица четырёх планов.
 * Источник — Figma, desktop node `1:3018` (сырой дамп
 * docs/figma-design/raw/page-subscriptions-desktop.code.tsx.txt).
 *
 * ── Светлая карточка (node 1:3118, 1120×376 @ y=1463) ────────────────────
 * Тот же компонент, что у "WHOOP непрерывно анализирует" на Главной:
 * .card--light + .card-heading--light + плашка-подложка на
 * `on-light-subtle`. Плашка в обоих местах буквально одинаковая (388px
 * ширины, паддинг 30, скругление card), поэтому .compare-card__note
 * подключён к тому же CSS-правилу, что .analytics-banner__note, а не
 * описан заново.
 * Мокапы (1:3125/1:3129 187×318 и 1:3133 243×413) прижаты к нижнему краю
 * карточки, центральный выступает на 37px выше её верхней границы —
 * подтверждено координатами: низ всех трёх = 1839 = низ карточки.
 *
 * ── Таблица (node 1:3137 и далее) ────────────────────────────────────────
 * 4 колонки планов (ONE / PEAK / LIFE / ПРОБНАЯ) × 11 строк-критериев.
 * Колонки в макете отцентрованы по x = 373.5 / 599.5 / ~824 / 1052 при
 * контентной ширине 1120 — это ровно сетка "колонка подписей 220px + 4
 * равные по 225px", поэтому таблица собрана на table-layout:fixed, а не
 * на подгонянных вручную процентах.
 * Заголовок плана — 20px SemiBold с трекингом 2px, цена — 14px,
 * под ними фото трекера 180×117.5 с растворением сверху и снизу
 * (в макете это два отдельных градиентных слоя, 1:3140/1:3141 —
 * воспроизведены одной вертикальной mask, см. theme.css).
 * Значения ячеек: 20px Medium (те же .card-heading), галочка — реальный
 * вектор из Figma (assets/icons/compare-check.svg).
 *
 * Матрица галочек/прочерков восстановлена по координатам иконок и
 * прочерков, а не на глаз (иконка size-22 в колонке = галочка, текстовый
 * узел "—" = прочерк).
 *
 * ⚠️ Опечатка в макете: последняя строка называется "Ежедневные показатели
 * **эпотребительного** давления". Такого слова нет; по смыслу — вероятно
 * "артериального". Текст выведен ДОСЛОВНО как в Figma (не выдумываю копию),
 * но это надо поправить в макете/контенте — см. также
 * docs/figma-design/page-subscriptions-desktop.md, там опечатка уже
 * отмечена.
 *
 * ── Mobile (node 1:3995 → 1:3996 и далее) ───────────────────────────────
 * На mobile та же матрица подана иначе: не таблица со сквозной колонкой
 * подписей, а горизонтально прокручиваемая лента карточек-планов шириной
 * 187px, где подписи критериев повторяются ВНУТРИ каждой карточки, а
 * «активный» план подсвечен подложкой `surface` (комментарий в макете:
 * «При пролистывании загорается тот тариф, что по центру»).
 *
 * Поэтому здесь ДВЕ разметки на ОДНИ данные ($whoopstore_compare_plans /
 * $whoopstore_compare_rows / $whoopstore_compare_cell): <table> для desktop
 * и .compare-plans для mobile, каждая скрыта в «чужом» брейкпоинте.
 * Альтернативы (перестроить ту же таблицу CSS-гридом, либо собирать
 * карточки из таблицы скриптом) отпадают: подписи в макете физически
 * повторяются в каждой колонке, а из одного DOM-узла четыре не сделать без
 * JS, без которого контент тогда не покажется вовсе. Данные при этом
 * по-прежнему описаны один раз — дублируется только подача.
 *
 * ⚠️ Фото трекера в колонке ONE: узел `XxHSGND0LxCtAUYxCMxR 2` Figma
 * отдаёт битым (пустой PNG 1.4 КБ). Взят одноимённый реальный ассет,
 * который пользователь ранее вручную перезалил для карточек Главной
 * (XxHSGND0LxCtAUYxCMxR-1.png) — те же пропорции 1.53. Колонки PEAK,
 * LIFE и ПРОБНАЯ используют ассеты, которые побайтово совпали с уже
 * лежащими в Медиатеке device-whoop-peak-annual.png и
 * device-whoop-life-annual.png (проверено md5) — новых копий не заводим.
 *
 * Цены ONE / PEAK / LIFE — из товаров-подписок (категория podpiski-whoop,
 * товар с названием плана), а не из макета: менеджер меняет цену в товаре,
 * и таблица не расходится с карточками. Товар не нашёлся — цена не
 * выводится, а не показывается устаревшая.
 */

$whoopstore_compare_price = static function ( $plan ) {
	foreach ( whoopstore_category_products( 'podpiski-whoop' ) as $product ) {
		if ( false !== mb_stripos( $product->get_name(), $plan ) ) {
			return wp_strip_all_tags( wc_price( wc_get_price_to_display( $product ) ) );
		}
	}

	return '';
};

$whoopstore_compare_plans = [
	[
		'name'  => 'ONE',
		'price' => $whoopstore_compare_price( 'One' ),
		'image' => '/wp-content/uploads/2026/09/XxHSGND0LxCtAUYxCMxR-1.webp',
	],
	[
		'name'  => 'PEAK',
		'price' => $whoopstore_compare_price( 'Peak' ),
		'image' => '/wp-content/uploads/2026/09/device-whoop-peak-annual.webp',
	],
	[
		'name'  => 'LIFE',
		'price' => $whoopstore_compare_price( 'Life' ),
		'image' => '/wp-content/uploads/2026/09/device-whoop-life-annual.webp',
	],
	[
		'name'  => 'ПРОБНАЯ',
		'price' => 'на 1 месяц',
		'image' => '/wp-content/uploads/2026/09/device-whoop-peak-annual.webp',
	],
];

// true — галочка, false — прочерк, строка — текстовое значение.
$whoopstore_compare_rows = [
	[ 'Поддерживаемый трекер', [ '5.0', '5.0', 'MG', '4.0' ] ],
	[ 'Сон, нагрузка, восстановление', [ true, true, true, true ] ],
	[ 'Шаги и мониторинг активности', [ true, true, true, true ] ],
	[ 'Женские гормональные показатели', [ true, true, true, true ] ],
	[ 'VO₂ Max и зоны пульса', [ true, true, true, true ] ],
	[ 'Мониторинг здоровья', [ false, true, true, true ] ],
	[ 'Мониторинг стресса', [ false, true, true, true ] ],
	[ 'Биологический возраст и темп старения', [ false, true, true, true ] ],
	[ 'Измерение ЭКГ', [ false, false, true, false ] ],
	[ 'Уведомления о нерегулярном сердечном ритме', [ false, false, true, false ] ],
	[ 'Ежедневные показатели эпотребительного давления', [ false, false, true, false ] ],
];

$whoopstore_compare_check = get_theme_file_uri( 'assets/icons/compare-check.svg' );

/**
 * Значение ячейки — одинаково для desktop-таблицы и mobile-карточек,
 * поэтому вынесено, а не продублировано в двух местах разметки.
 */
$whoopstore_compare_cell = function ( $value ) use ( $whoopstore_compare_check ) {
	if ( true === $value ) {
		printf(
			'<img class="compare-check" src="%s" alt="есть" width="22" height="22" loading="lazy" />',
			esc_url( $whoopstore_compare_check )
		);
	} elseif ( false === $value ) {
		echo '<span aria-label="нет">—</span>';
	} else {
		echo esc_html( $value );
	}
};

$whoopstore_compare_phone = '/wp-content/uploads/2026/09/subscriptions-compare-phone-side.webp';
$whoopstore_compare_main  = '/wp-content/uploads/2026/09/subscriptions-compare-phone-main.webp';

// Лента карточек на mobile подсвечивает план по центру — это единственное,
// чего не умеет CSS. Скрипт подключаем из самого паттерна: страница
// «Подписки» может получить свой шаблон и через явное назначение, и через
// иерархию блочных шаблонов, поэтому условие по странице в inc/setup.php
// было бы менее надёжным, чем привязка к месту использования.
wp_enqueue_script(
	'whoopstore-compare-plans',
	get_theme_file_uri( 'assets/js/compare-plans.js' ),
	[],
	whoopstore_asset_version( 'assets/js/compare-plans.js' ),
	true
);
?>
<!-- wp:html -->
<section class="compare">

	<div class="compare-card card--light">
		<div class="compare-card__intro">
			<h2 class="compare-card__title card-heading card-heading--light">Сравните преимущества</h2>
			<p class="compare-card__note section-subtext section-subtext--light">WHOOP предлагает три плана подписки, каждый из которых включает устройство WHOOP 5.0 или медицинскую версию WHOOP MG в плане Life, и доступ к мобильному приложению с расширенными метриками и рекомендациями. Ниже — наглядная таблица для быстрого сравнения.</p>
		</div>

		<div class="compare-card__phones" aria-hidden="true">
			<img class="compare-card__phone" src="<?php echo esc_url( $whoopstore_compare_phone ); ?>" alt="" width="187" height="318" loading="lazy" />
			<img class="compare-card__phone compare-card__phone--main" src="<?php echo esc_url( $whoopstore_compare_main ); ?>" alt="" width="243" height="413" loading="lazy" />
			<img class="compare-card__phone" src="<?php echo esc_url( $whoopstore_compare_phone ); ?>" alt="" width="187" height="318" loading="lazy" />
		</div>
	</div>

	<table class="compare-table">
		<caption class="screen-reader-text">Сравнение планов подписки WHOOP</caption>
		<thead>
			<tr>
				<td></td>
				<?php foreach ( $whoopstore_compare_plans as $plan ) : ?>
					<th scope="col">
						<span class="compare-plan"><?php echo esc_html( $plan['name'] ); ?></span>
						<span class="compare-price"><?php echo esc_html( $plan['price'] ); ?></span>
						<span class="compare-device"><img src="<?php echo esc_url( $plan['image'] ); ?>" alt="" loading="lazy" /></span>
					</th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $whoopstore_compare_rows as $row ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $row[0] ); ?></th>
					<?php foreach ( $row[1] as $value ) : ?>
						<td><?php $whoopstore_compare_cell( $value ); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div class="compare-plans">
		<div class="compare-plans__track">
			<?php foreach ( $whoopstore_compare_plans as $whoopstore_plan_index => $plan ) : ?>
				<article class="compare-plans__card<?php echo 0 === $whoopstore_plan_index ? ' is-active' : ''; ?>">
					<h3 class="compare-plan"><?php echo esc_html( $plan['name'] ); ?></h3>
					<span class="compare-price"><?php echo esc_html( $plan['price'] ); ?></span>
					<span class="compare-device"><img src="<?php echo esc_url( $plan['image'] ); ?>" alt="" loading="lazy" /></span>

					<?php foreach ( $whoopstore_compare_rows as $row ) : ?>
						<div class="compare-plans__row">
							<span class="compare-plans__label"><?php echo esc_html( $row[0] ); ?></span>
							<span class="compare-plans__value"><?php $whoopstore_compare_cell( $row[1][ $whoopstore_plan_index ] ); ?></span>
						</div>
					<?php endforeach; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>

</section>
<!-- /wp:html -->
