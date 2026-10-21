<?php
/**
 * Title: No display
 * Slug: whoopstore/no-display
 *
 * Первый проход по всей Главной (2026-09-07), источник — Figma MCP get_metadata
 * (node 1:3464, "Мобилка") — см. accessories-grid.php.
 *
 * Две смежные подсекции из Figma, объединены в один паттерн (идут подряд
 * без разделителя между ними в макете):
 *  1. "Почему у WHOOP нет экрана?" + заголовок-подзаголовок + абзац.
 *  2. "Отсутствие дисплея дает несколько преимуществ".
 *
 * Визуальный аудит 2026-09-09 (round 5, скриншот пользователя Figma vs
 * stage + сверка с docs/figma-design/raw/homepage-desktop.partial-2.code.tsx.txt
 * узлы 1:281-1:294 и homepage-mobile.code.tsx.txt узлы 1:3743-1:3752):
 * подтверждение подозрения, оставленного 2026-09-07 в комментарии выше
 * (см. git-историю) — подзаголовок и абзац на самом деле лежат в СВЕТЛОЙ
 * карточке (node 1:281, bg-white, 1120×369, radius 20), а не прямо на
 * тёмном фоне секции. Тот же переиспользуемый компонент, что у
 * analytics-banner ("WHOOP непрерывно анализирует" — .card--light +
 * серая плашка-заметка), см. patterns/analytics-banner.php. Внутри
 * карточки: подзаголовок по центру (card-heading--light), серая плашка
 * rgba(15,18,23,0.05) (node 1:285, 388×128) с абзацем внутри (a на
 * mobile, node 1:3744-1:3746, этой плашки НЕТ — текст лежит прямо на
 * карточке, см. .no-display__note override в мобильном медиа-запросе).
 * Мокап телефона заменён с самодельного композита (Silver-рамка +
 * Screen-скриншот раздельно, без ремешка) на ГОТОВЫЙ экспорт из Figma —
 * Group-1000010609.png (425×406, уже содержит ремешок + телефон вместе,
 * см. node 1:288 "Group 1000010609" — сам файл уже лежит в Медиатеке,
 * см. docs/figma-design/raw). Позиционирование мокапа — абсолютное
 * внутри карточки (right:68px, bottom:0), т.к. в Figma он реально
 * выступает НАД верхним краем карточки на 37px (369-406), а не вписан
 * в неё целиком — подтверждено: низ мокапа (top 9244 + h 406 = 9650)
 * ТОЧНО совпадает с низом карточки (top 9281 + h 369 = 9650).
 *
 * QA раунд 3 (2026-09-07): декоративная фотография устройства (node 1:294,
 * "videoframe_7765") изначально была наложена на мокап телефона "по
 * смыслу", без точного офсета из Figma. **Round 5 (2026-09-09), по
 * подтверждению пользователя**: по обоим дампам (desktop y=9880, ощутимо
 * ниже карточки 9281-9650 и вертикально центрировано на ряду "Отсутствие
 * дисплея"/y=9956-10158; mobile — тот же узел 1:3756 буквально
 * сгруппирован с "Отсутствие дисплея", node 1:3754, а не с мокапом
 * 1:3747) это фото на самом деле относится к ряду преимуществ, не к
 * мокапу — перенесено туда, см. .no-display__benefits-row ниже.
 *
 * Аудит 2026-09-07 (Critical): в первом проходе здесь была только 1
 * карточка ("Длительная автономность"), т.к. mobile-дамп отдавал этот
 * пункт продублированным дважды с ИДЕНТИЧНЫМ текстом — по описанию это
 * выглядело как нерасшифрованный дубль. get_design_context по Desktop
 * (supplementary блок 2, node 1:296) подтвердил: это РЯД ИЗ 4 РАЗНЫХ
 * пунктов ("Длительная автономность" / "Компактность" / "Датчики работают
 * постоянно" / "Никаких отвлекающих факторов"), разделённых вертикальными
 * линиями, из которых активен (белый текст) только первый — остальные три
 * приглушены (rgba(255,255,255,0.2)). Реализовано визуальным
 * активным/приглушённым состоянием без JS-переключения (переключение по
 * клику в Figma не подтверждено, только статичный кадр с одним активным
 * пунктом).
 *
 * Визуальный аудит 2026-09-09 (round 8, скриншот пользователя Figma vs
 * stage для ряда "Отсутствие дисплея..." + сверка с raw-дампом
 * homepage-desktop.partial-2.code.tsx.txt:786-887 узлы 1:294-1:315,
 * homepage-mobile.code.tsx.txt:756-809 узлы 1:3754-1:3776):
 * 1. Пункты — ВЕРТИКАЛЬНЫЙ список (левый акцентный бордер на активном
 *    пункте), а не горизонтальный ряд с разделителями-бордерами справа —
 *    неверно понято при первом проходе. Координаты (item1 top=9961,
 *    item2 top=10040, item3 top=10086, item4 top=10132, все на одном x)
 *    подтверждают столбец, не строку.
 * 2. Фото устройства (node 1:294) — не "примерно 73% слева поверх текста"
 *    (старое приближение), а отдельная колонка СПРАВА от списка внутри
 *    общей ширины 780px (список 436px + фото 266px, оба выровнены по тем
 *    же x=210/x=990, что и карточка ниже) — реализовано flex-row с
 *    justify-content:space-between вместо absolute-оверлея.
 * 3. Под рядом преимуществ в Figma — ТЁМНАЯ карточка (node 1:310,
 *    #1b1e23, 780×246, radius 20) с фото-миниатюрой слева (node 1:311,
 *    196×186) и текстом+кнопкой справа — раньше текст/кнопка лежали
 *    прямо на фоне секции без карточки и без фото вообще, это и была
 *    главная визуальная дыра со скриншота пользователя.
 * 4. Текст кнопки различается по брейкпоинтам в самом Figma (не ошибка
 *    дампа — подтверждено независимо в обоих файлах): desktop node 1:314
 *    = "Купить WHOOP", mobile node 1:3776 = "Подробнее о WHOOP" —
 *    реализовано двумя кнопками, переключаемыми через
 *    .no-display__cta--desktop/--mobile (тот же приём display:none по
 *    брейкпоинту, что .audience__tag--desktop-only).
 * Фото-миниатюра в карточке (imgRectangle100252) изначально шла без
 * реального ассета из Figma (MCP-квота была исчерпана) — временный
 * SVG-плейсхолдер. Round 9 (2026-09-09): пользователь прислал оба реальных
 * файла напрямую через Медиатеку (тот же надёжный путь, что описан в
 * [[whoopstore-homepage-first-pass]] для google-play.png/whoop-app-ru.png) —
 * Rectangle-100252.png (196×186, фото-миниатюра в карточке) и
 * videoframe_7765-no-bg-HD-carve.photos-1.png (266×354, фото устройства
 * в ряду преимуществ, заменяет старый no-display-videoframe.png с
 * вплавленным в пиксели зеленоватым бликом вокруг светодиода). Оба файла
 * побайтово совпадают по размеру с соответствующими узлами Figma
 * (1:311 и 1:294) — не масштабировались.
 *
 * Раунд 12 (2026-09-10), по скриншоту пользователя Figma-мобилка vs стейдж:
 * ряд преимуществ на mobile — это не тот же столбец пунктов, что на desktop,
 * а ГОРИЗОНТАЛЬНЫЙ СЛАЙДЕР карточек с точками-пагинацией (mobile-дамп,
 * узлы 1:3757-1:3765: карточка #1b1e23 320×118 radius 10, соседний слайд
 * за краем экрана с зазором 12px, под ней группа точек 41×8). Тексты
 * слайдов — те же 4 пункта из desktop-узлов 1:297-1:308 (в mobile-дампе
 * Figma отдала два слайда с одинаковым текстом первого пункта — это кадр
 * "текущий+следующий", а не 4 разных). Описание есть только у первого
 * пункта — в макете (на обоих брейкпоинтах) у остальных трёх только
 * заголовок, ничего не дописывал. Разметка общая для обоих брейкпоинтов,
 * различается только CSS (см. @media(max-width:781px) в theme.css) —
 * desktop не затронут. Прокрутка — нативный scroll-snap, скрипт
 * assets/js/benefits-slider.js отвечает только за подсветку точки и клик
 * по ней.
 */
?>
<!-- wp:group {"className":"no-display","layout":{"type":"constrained"}} -->
<div class="wp-block-group no-display">

	<!-- Аудит 2026-09-07 (единая типографика): было has-xl-font-size (35px)
	— по raw-дампу Figma (node 1:287) это section-heading (32px). Остаётся
	на тёмном фоне секции, НАД карточкой (не внутри неё) — подтверждено
	координатами (heading top 9216, карточка top 9281).
	Аудит 2026-09-10: текст обёрнут в <span> — на mobile заголовок в Figma
	ограничен по ширине (node 1:3743, w=244px), а вешать max-width на сам
	<h2> нельзя: ядро WP центрирует прямого потомка .is-layout-constrained
	с собственным max-width через margin-left/right:auto !important (тот же
	приём и та же причина, что у .advantages__stats-title span, см.
	patterns/advantages-sticky-scroll.php). -->
	<!-- wp:heading {"level":2,"className":"no-display__title section-heading"} -->
	<h2 class="wp-block-heading no-display__title section-heading"><span>Почему у WHOOP нет экрана?</span></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"no-display__card card--light","layout":{"type":"constrained"}} -->
	<div class="wp-block-group no-display__card card--light">

		<div class="no-display__card-content">
			<!-- wp:heading {"level":3,"className":"no-display__subtitle card-heading card-heading--light"} -->
			<h3 class="wp-block-heading no-display__subtitle card-heading card-heading--light">WHOOP — это осознанный подход к здоровью без постоянных уведомлений</h3>
			<!-- /wp:heading -->

			<!-- Серая плашка на desktop (node 1:285, rgba(15,18,23,0.05)) —
			на mobile её нет (текст лежит прямо на карточке), см. override в
			@media(max-width:781px) в theme.css. -->
			<!-- wp:paragraph {"className":"no-display__note section-subtext section-subtext--light"} -->
			<p class="no-display__note section-subtext section-subtext--light">В отличие от большинства умных часов, WHOOP не оснащён экраном. Такое решение позволяет сосредоточиться не на постоянной проверке показателей, а на их анализе в приложении</p>
			<!-- /wp:paragraph -->
		</div>

		<div class="no-display__media-wrap" aria-hidden="true">
			<img class="no-display__media" src="/wp-content/uploads/2026/09/Group-1000010609-2x.webp" alt="" width="425" height="406" loading="lazy" />
		</div>

	</div>
	<!-- /wp:group -->

	<!-- Аудит 2026-09-07 (единая типографика): было has-lg-font-size (22px),
	слева — по raw-дампу Figma (node 1:295) это section-heading (32px) по
	центру. -->
	<!-- wp:heading {"level":2,"className":"no-display__benefits-title section-heading section-heading--center"} -->
	<h2 class="wp-block-heading no-display__benefits-title section-heading section-heading--center">Отсутствие дисплея дает несколько преимуществ</h2>
	<!-- /wp:heading -->

	<!-- Round 5 (2026-09-09): фото устройства (node 1:294, "videoframe_7765")
	перенесено сюда из верхнего блока с мокапом телефона — по координатам
	Figma оно относится к этому ряду, не к телефону (desktop: y=9880,
	mobile: тот же узел 1:3756 буквально сгруппирован с "Отсутствие
	дисплея", node 1:3754, не с мокапом 1:3747).
	Round 8 (2026-09-09): горизонтальный офсет уточнён — фото не "примерно
	73% слева", а отдельная колонка справа от списка в общей flex-раскладке
	(см. .no-display__benefits-row в theme.css), симметричный нависающий
	отступ 76px сверху/снизу — следствие align-items:center, а не ручной
	позиции. Свечение — та же техника radial-gradient, что у
	straps-carousel--compact::before (замена недоступному Figma-блюру). -->
	<!-- Round 9 (2026-09-09): фото устройства заменено на реальный экспорт
	из Figma, присланный пользователем через Медиатеку —
	videoframe_7765-no-bg-HD-carve.photos-1.png, 266×354 (побайтово
	совпадает с node 1:294 из raw-дампа). Старый файл
	no-display-videoframe.png имел вплавленный в пиксели зеленоватый
	блик/ореол вокруг светодиода, не соответствующий Figma (там свечение —
	отдельный слой-блюр, воспроизводится через .no-display__benefits-glow::before
	ниже, а не часть самого фото устройства). -->
	<!-- wp:html -->
	<div class="no-display__benefits-row">
		<div class="no-display__benefits-glow" aria-hidden="true">
			<img class="no-display__benefits-device" src="/wp-content/uploads/2026/09/videoframe_7765-no-bg-HD-carve.photos-1-2x.webp" alt="" width="266" height="354" loading="lazy" />
		</div>
		<div class="no-display__benefits">
			<div class="no-display__benefit is-active">
				<span class="no-display__benefit-title">Длительная автономность</span>
				<span class="no-display__benefit-text">До 14 дней автономной работы без подзарядки</span>
			</div>
			<div class="no-display__benefit">
				<span class="no-display__benefit-title">Компактность</span>
			</div>
			<div class="no-display__benefit">
				<span class="no-display__benefit-title">Датчики работают постоянно</span>
			</div>
			<div class="no-display__benefit">
				<span class="no-display__benefit-title">Никаких отвлекающих факторов</span>
			</div>
		</div>
		<!-- Раунд 12 (2026-09-10): точки-пагинация мобильного слайдера
		(node 1:3765). Лежат в самой разметке, а не создаются скриптом:
		это часть макета, и без JS первая точка всё равно показывает
		верное состояние (слайдер стартует с первого слайда). На desktop
		контейнер выключен через display:none — в desktop-макете точек
		нет, там все 4 пункта видны сразу столбцом. -->
		<div class="no-display__benefits-dots">
			<button class="no-display__benefits-dot is-active" type="button" aria-label="Длительная автономность"></button>
			<button class="no-display__benefits-dot" type="button" aria-label="Компактность"></button>
			<button class="no-display__benefits-dot" type="button" aria-label="Датчики работают постоянно"></button>
			<button class="no-display__benefits-dot" type="button" aria-label="Никаких отвлекающих факторов"></button>
		</div>
	</div>
	<!-- /wp:html -->

	<!-- Раунд 8 (2026-09-09): раньше текст+кнопка лежали прямо на фоне
	секции без карточки — по raw-дампу (node 1:310, #1b1e23 780×246
	radius 20) под рядом преимуществ карточка с фото-миниатюрой (node
	1:311, 196×186) слева и текстом+кнопкой справа. Текст — text-label
	(20px, сплошной белый, часть фразы полужирным), подтверждено node 1:315.
	Раунд 9: фото-миниатюра — реальный экспорт из Figma, присланный
	пользователем через Медиатеку (Rectangle-100252.png, 196×186,
	побайтово совпадает с node 1:311) — заменил временный SVG-плейсхолдер. -->
	<!-- wp:html -->
	<div class="no-display__closing-card">
		<div class="no-display__closing-media" aria-hidden="true">
			<img src="/wp-content/uploads/2026/09/Rectangle-100252.webp" alt="" width="196" height="186" loading="lazy" />
		</div>
		<div class="no-display__closing-content">
			<!-- wp:paragraph {"className":"no-display__closing text-label"} -->
			<p class="no-display__closing text-label">Все показатели доступны в мобильном приложении WHOOP, где они <strong>автоматически анализируются</strong> и объединяются <br/> в персональные рекомендации</p>
			<!-- /wp:paragraph -->

			<!-- Раунд 10 (2026-09-09), по прямому уточнению пользователя:
			второй вариант кнопки ("Подробнее о WHOOP", раньше показывался
			на mobile по тексту node 1:3776 из mobile-дампа) в дизайне
			отсутствует — убран. Одна кнопка на всех брейкпоинтах. -->
			<!-- wp:buttons {"className":"no-display__cta","layout":{"type":"flex","justifyContent":"left"}} -->
			<div class="wp-block-buttons no-display__cta">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/#devices">Купить WHOOP</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
