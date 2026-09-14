<?php
/**
 * Title: Advantages
 * Slug: whoopstore/advantages-sticky-scroll
 *
 * Правка 2026-09-11 (pinned-скролл слайдов — возврат механики, теперь по
 * подтверждённой аннотации). История: 2026-09-04 sticky-scroll был убран,
 * потому что на тот момент механика была моим домыслом — в присланных
 * Figma-скринах её ничто не подтверждало, а пустые scroll-триггеры давали
 * "пустую полосу" и сдвиг вёрстки. Теперь пользователь прислал скрин самой
 * аннотации автора макета: "В блоке преимуществ появляется вначале
 * заголовок, после он пропадает и при скролле фиксируется телефон и
 * подпись, при скролле меняется инфа (на телефоне и в надписях)" — то есть
 * секция залипает на весь экран, а вертикальный скролл внутри неё листает
 * слайды, а не двигает страницу. Плюс присланы 5 кадров композиции —
 * они и стали слайдами (массив ниже), строго в присланном порядке.
 *
 * Согласовано с пользователем перед реализацией (3 вопроса):
 *  1) слайдов ровно 5, из присланных скринов; прежняя подпись "Измерение
 *     ЭКГ прямо с запястья" удалена — её нет ни в одном из кадров (и она
 *     всё это время стояла в паре с чужой картинкой: Frame-6626.png — это
 *     экран STRESS MONITOR, то есть кадр 5-го слайда, "Мониторинг
 *     артериального давления");
 *  2) заголовок секции плавно исчезает при входе в пин (а не просто
 *     уезжает скроллом) — см. .advantages__title в theme.css;
 *  3) на mobile — та же механика пина, не список и не свайп-слайдер.
 *
 * Правка 2026-09-12 (по просьбе пользователя): телефон статичен,
 * листается только СОДЕРЖИМОЕ его экрана. Поэтому композиция разделена на
 * два файла вместо одного склеенного мокапа:
 *   - Silver-2 (292x403) — рамка телефона с прозрачным "стеклом" и
 *     Dynamic Island, одна и та же на всех слайдах, лежит НАД экраном;
 *   - Screen* (263x390)  — сам контент экрана, он и едет лентой.
 * (Расширения файлов — .webp: конвертация всей Медиатеки сделана отдельно
 * от этой правки; альфа и размеры при ней сохранились, проверено.)
 * Прежние склеенные мокапы (Frame-6626/6627/6627-1/6627-2/6628.png) в
 * разметке больше не используются, из Медиатеки не удалялись.
 *
 * Порядок слайдов — слева направо по макету (уточнён у пользователя
 * 2026-09-12; до этого стоял обратный, по порядку присланных в чат
 * скринов). Совпадает с порядком загрузки экранов в Медиатеку: суффиксы
 * -1..-4 у файлов проставил сам WordPress при дедупликации — все пять
 * исходников назывались Screen.png, поэтому цифра отражает именно порядок
 * загрузки. Соответствие же экрана ТЕКСТУ определено по содержимому самих
 * файлов, а не по имени:
 *   1 Давление                    — Screen.webp   (STRESS MONITOR)
 *   2 Женское здоровье            — Screen-1.webp (MY JOURNAL / MENSTRUAL
 *                                    CYCLE INSIGHTS)
 *   3 До 14 дней без подзарядки   — Screen-2.webp (дашборд WHOOP, кольца
 *                                    SLEEP/RECOVERY/STRAIN)
 *   4 Физиологический возраст     — Screen-3.webp (HEALTHSPAN / WHOOP AGE)
 *   5 Контроль здоровья 24/7      — Screen-4.webp (HEALTH MONITOR, HEART
 *                                    RATE + RESPIRATORY/SpO2/RHR/HRV)
 *
 * Рамка повторяется в каждом слайде, а не вынесена одним элементом наружу,
 * чтобы у секции сохранился рабочий вид без JS: там слайды — обычный
 * вертикальный список, и каждому нужен свой телефон целиком. В pinned-
 * режиме все слайды лежат в одной ячейке грида, поэтому рамки совпадают
 * пиксель в пиксель; рисует её при этом ровно один слайд (активный), иначе
 * пять наложенных копий дали бы заметный алиасинг по контуру — см.
 * .advantages__frame в theme.css. Источник разметки один (цикл ниже), так
 * что дублирования в коде нет.
 *
 * Почему разметка слайдов собирается PHP-циклом, а не 5 копипастами блока:
 * правило 14 (повторяющиеся элементы — единый источник), плюс подмена
 * картинок/текстов сведётся к правке данных, а не вёрстки.
 *
 * Почему весь пин целиком в wp:html, включая <h2> (раньше он был
 * отдельным wp:heading): sticky-элемент должен быть ОДНИМ узлом, внутри
 * которого лежат и заголовок, и композиция — иначе заголовок нельзя
 * схлопнуть внутри залипшего экрана. Плюс на .advantages__scroll нужен
 * inline --advantages-count (число слайдов для расчёта высоты дорожки в
 * CSS), а core-блок group произвольный style-атрибут не отдаёт.
 * .advantages__title при этом перестал быть прямым потомком
 * .is-layout-constrained — тот баг ядра WP (центрирование потомка с
 * собственным max-width) здесь больше не применим, а перенос строки по
 * Figma (node 1:175, width 514px) по-прежнему держит
 * .advantages__title-text (inline-block + max-width, центрируется
 * text-align:center родителя).
 *
 * Карточки показателей — обёрнуты в .advantages__stat как настоящую
 * карточку (фон/паддинг/радиус), а не голое изображение — по присланному
 * скрину. 4 из 8 картинок заменены пользователем (были с белым фоном) —
 * актуальные имена файлов с суффиксом "1" после повторной загрузки.
 *
 * Круглые 4 картинки (Recovery/Sleep/Strain/Stress Monitor) всё ещё имели
 * белый фон по углам вокруг кольца — обработал через ImageMagick
 * (flood-fill белого в прозрачность от всех 4 углов, кольцо — замкнутый
 * контур, риска "утечь" в центр нет), загружено в Медиатеку как
 * fixed-recovery/sleep/strain/stress.png (attachments 40-43).
 *
 * Была вторая попытка (fuzz 22% + alpha-level + shave 8px, attachments
 * 44-47, "final2-*") — пользователь попросил откатить, вернулись к первой
 * версии (attachments 40-43, "fixed-*"). Файлы 44-47 из Медиатеки не
 * удалялись, просто больше не используются в разметке.
 *
 * Правка 2026-09-05: .advantages__stat теперь сама карточка (фон/паддинг/
 * радиус — было на промежуточном .advantages__stat-card, убрал лишний
 * уровень); картинка/заголовок/текст — всё внутри одной карточки, не
 * снаружи. .advantages__stat-media — фикс-высота слот под картинку, чтобы
 * все 8 карточек были одной высоты независимо от пропорций исходных фото
 * (4 квадратных кольца vs 4 прямоугольных карточки).
 *
 * Правка 2026-09-09 (визуальный диф скрина Figma vs стейдж, карточки
 * показателей): подпись HRV переносится на 2 строки в макете — "Вариабельность
 * сердечного ритма" / "(HRV)" — подтверждено раздельными <span> в raw-дампе
 * Figma (node 1:490, homepage-desktop.partial-2.code.tsx.txt) И в мобильном
 * дампе (node 1:3919) с идентичным разбиением, то есть это осознанный перенос
 * автора макета, а не случайный wrap по ширине контейнера. На стейдже это была
 * одна строка без переноса, ломался auto-wrap браузера на другом слове.
 * Добавлен явный <br /> вместо max-width-обёртки (как у заголовков выше) —
 * этот текст не прямой потомок .is-layout-constrained, тот баг ядра WP тут не
 * применим, а <br /> проще и однозначно гарантирует тот же перенос на обеих
 * ширинах (макет ломает строку в этом месте и на desktop, и на mobile).
 * Остальные подписи карточек (Recovery/Sleep/Strain/RHR/Stress/Respiratory/
 * SpO2) сверены построчно с тем же raw-дампом — текст совпадает, у остальных
 * Figma не форсирует перенос (обычный <p> без разбиения на span), поэтому не
 * трогал.
 *
 * Правка 2026-09-10 (mobile-раскладка карточек показателей): заголовок и
 * подпись карточки обёрнуты в <div class="advantages__stat-body">. Нужно
 * только для mobile — там карточка по Figma горизонтальная (картинка слева,
 * текстовый блок справа), а значит текст должен быть ОДНОЙ колонкой рядом с
 * картинкой, а не двумя отдельными flex-элементами. На desktop обёртка
 * нейтральна: у неё display:contents, в раскладке её нет, .advantages__stat
 * остаётся той же flex-колонкой из 3 элементов с тем же gap. Все числа
 * mobile-версии и их источник в raw-дампе — в theme.css, в media-query
 * (max-width: 781px) у .advantages__stats.
 */

/*
 * Слайды pinned-скролла — порядок строго как в присланных кадрах,
 * соответствие картинок — см. шапку файла.
 */
$whoopstore_advantages_slides = [
	[
		'screen' => '/wp-content/uploads/2026/09/Screen.webp',
		'alt'   => '',
		'title' => 'Мониторинг артериального давления',
		'text'  => 'WHOOP Life с трекером MG отслеживает давление в реальном времени, помогая замечать отклонения и лучше понимать влияние стресса, сна и тренировок на сердечно-сосудистую систему',
	],
	[
		'screen' => '/wp-content/uploads/2026/09/Screen-1.webp',
		'alt'   => '',
		'title' => 'Поддержка женского здоровья',
		'text'  => 'WHOOP учитывает гормональные изменения, менструальные циклы и беременность, адаптируя рекомендации по сну, тренировкам и восстановлению под физиологические особенности женщины',
	],
	[
		'screen' => '/wp-content/uploads/2026/09/Screen-2.webp',
		'alt'   => '',
		'title' => 'До 14 дней без подзарядки',
		'text'  => 'WHOOP 5.0 работает до 14 дней на одном заряде — без экрана и лишних отвлечений, только постоянный сбор данных и максимум удобства в повседневной жизни',
	],
	[
		'screen' => '/wp-content/uploads/2026/09/Screen-3.webp',
		'alt'   => '',
		'title' => 'Оценка физиологического возраста и темпа старения',
		'text'  => 'Новая функция WHOOP Healthspan дает более чёткое представление о том, как образ жизни влияет на продолжительность и качество жизни',
	],
	[
		'screen' => '/wp-content/uploads/2026/09/Screen-4.webp',
		'alt'   => '',
		'title' => 'Контроль здоровья 24/7',
		'text'  => 'Сенсоры WHOOP 5.0 и Whoop MG собирают данные круглосуточно — отслеживают пульс, HRV, стресс, давление и восстановление без перерывов, даже во сне и в душе',
	],
];
?>
<!-- wp:group {"anchor":"advantages","className":"advantages","layout":{"type":"constrained"}} -->
<div class="wp-block-group advantages" id="advantages">

	<!-- wp:html -->
	<div class="advantages__scroll" style="--advantages-count:<?php echo count( $whoopstore_advantages_slides ); ?>">
		<div class="advantages__sticky">
			<h2 class="wp-block-heading advantages__title section-heading section-heading--center"><span class="advantages__title-text">Преимущества новых трекеров <strong>Whoop 5.0</strong></span></h2>
			<div class="advantages__slides">
<?php foreach ( $whoopstore_advantages_slides as $whoopstore_advantages_index => $whoopstore_advantages_slide ) : ?>
				<div class="advantages__slide<?php echo 0 === $whoopstore_advantages_index ? ' is-active' : ''; ?>">
					<div class="advantages__phone">
						<div class="advantages__screen">
							<img src="<?php echo esc_url( $whoopstore_advantages_slide['screen'] ); ?>" alt="<?php echo esc_attr( $whoopstore_advantages_slide['alt'] ); ?>" width="263" height="390" loading="lazy" />
						</div>
						<img class="advantages__frame" src="/wp-content/uploads/2026/09/Silver-2.webp" alt="" width="292" height="403" loading="lazy" />
					</div>
					<div class="advantages__caption">
						<h3 class="advantages__caption-title"><?php echo esc_html( $whoopstore_advantages_slide['title'] ); ?></h3>
						<p class="advantages__caption-text section-subtext"><?php echo esc_html( $whoopstore_advantages_slide['text'] ); ?></p>
					</div>
				</div>
<?php endforeach; ?>
			</div>
		</div>
	</div>
	<!-- /wp:html -->

	<!-- Аудит 2026-09-07 (единая типографика): было has-lg-font-size (22px)
	— по raw-дампу Figma (node 1:498) это section-heading (32px), слева, с
	ведущей фразой ("Более 15 ключевых показателей") полужирным, а не всей
	строкой целиком, как раньше (наследовала глобальный вес 600). -->
	<!-- wp:heading {"level":3,"className":"advantages__stats-title section-heading"} -->
	<h3 class="wp-block-heading advantages__stats-title section-heading"><span><strong>Более 15 ключевых показателей</strong> здоровья, сна и восстановления</span></h3>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="advantages__stats">
		<div class="advantages__stat card">
			<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/IMG_9252-3.webp" alt="Recovery 97%" loading="lazy" /></div>
			<div class="advantages__stat-body"><span class="advantages__stat-title">Recovery</span><span class="advantages__stat-text section-subtext">Уровень восстановления организма</span></div>
		</div>
		<div class="advantages__stat card">
			<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100254.webp" alt="Sleep Performance 82%" loading="lazy" /></div>
			<div class="advantages__stat-body"><span class="advantages__stat-title">Sleep Performance</span><span class="advantages__stat-text section-subtext">Продолжительность <br /> и качество сна</span></div>
		</div>
		<div class="advantages__stat card">
			<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/IMG_9252-1.webp" alt="Strain 4.8" loading="lazy" /></div>
			<div class="advantages__stat-body"><span class="advantages__stat-title">Strain</span><span class="advantages__stat-text section-subtext">Дневная физиологическая нагрузка</span></div>
		</div>
		<div class="advantages__stat card">
			<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100255.webp" alt="HRV 48ms" loading="lazy" /></div>
			<div class="advantages__stat-body"><span class="advantages__stat-title">Heart Rate Variability</span><span class="advantages__stat-text section-subtext">Вариабельность <br /> сердечного ритма (HRV)</span></div>
		</div>
		<div class="advantages__stat card">
			<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100285.webp" alt="RHR 67bpm" loading="lazy" /></div>
			<div class="advantages__stat-body"><span class="advantages__stat-title">Resting Heart Rate</span><span class="advantages__stat-text section-subtext">Пульс в покое</span></div>
		</div>
		<div class="advantages__stat card">
			<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100274.webp" alt="Stress Monitor 1.0" loading="lazy" /></div>
			<div class="advantages__stat-body"><span class="advantages__stat-title">Stress Monitor</span><span class="advantages__stat-text section-subtext">Уровень физиологического стресса</span></div>
		</div>
		<div class="advantages__stat card">
			<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100277.webp" alt="Respiratory Rate 13.7rpm" loading="lazy" /></div>
			<div class="advantages__stat-body"><span class="advantages__stat-title">Respiratory Rate</span><span class="advantages__stat-text section-subtext">Частота дыхания во сне</span></div>
		</div>
		<div class="advantages__stat card">
			<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100275.webp" alt="Blood Oxygen 91%" loading="lazy" /></div>
			<div class="advantages__stat-body"><span class="advantages__stat-title">Blood Oxygen (SpO&#8322;)</span><span class="advantages__stat-text section-subtext">Уровень насыщения <br /> крови кислородом</span></div>
		</div>
	</div>

	<!-- Правка 2026-09-11 (раскрытие секции показателей): скрытая часть,
	которую раскрывает и сворачивает кнопка под сеткой
	(assets/js/advantages-more.js).
	Источник — состояние "Раскрытый" из Figma (fileKey fUlpHpWsnxx78nCZKPxkkj,
	node 1:561, дамп docs/figma-design/raw/states/1-561-raskrytyy-desktop.code.tsx.txt):
	3-й ряд из 4 карточек + подзаголовок "Дополнительно в WHOOP MG:" + ещё 3
	карточки. Порядок и тексты — построчно по дампу (узлы 1:805…1:866).
	Сетка та же (.advantages__stats), чтобы раскрытые карточки попадали в те
	же 4 колонки и читались как продолжение первых восьми, а не как отдельный
	блок (правило 14 — один источник стилей карточки).
	hidden отдаётся сервером, а не навешивается скриптом: иначе при загрузке
	карточки успевали бы отрисоваться и схлопнуться уже после footer-скрипта
	(видимый скачок вёрстки). -->
	<div class="advantages__more" id="advantages-more" data-label-more="Посмотреть ещё" data-label-less="Свернуть" hidden>
		<div class="advantages__stats">
			<div class="advantages__stat card">
				<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100289.webp" alt="Skin Temp -0,3 &deg;C" width="157" height="110" loading="lazy" /></div>
				<div class="advantages__stat-body"><span class="advantages__stat-title">Skin Temperature</span><span class="advantages__stat-text section-subtext">Изменения температуры кожи относительно вашей нормы</span></div>
			</div>
			<!-- Единственная карточка с высокой картинкой (183×168 против
			127-128px у остальных) — в макете она так и нарисована крупнее,
			см. node 1:865. Модификатор только меняет размер слота, высота
			самой карточки и позиция заголовка остаются общими. -->
			<div class="advantages__stat advantages__stat--media-tall card">
				<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100290-1.webp" alt="VO&#8322; Max" width="183" height="168" loading="lazy" /></div>
				<div class="advantages__stat-body"><span class="advantages__stat-title">VO&#8322; Max</span><span class="advantages__stat-text section-subtext">Оценка максимального потребления кислорода</span></div>
			</div>
			<div class="advantages__stat card">
				<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100290-2.webp" alt="Menstrual Cycle Insights" width="203" height="110" loading="lazy" /></div>
				<div class="advantages__stat-body"><span class="advantages__stat-title">Women&#8217;s Hormonal Insights</span><span class="advantages__stat-text section-subtext">Влияние менструального цикла на показатели организма</span></div>
			</div>
			<div class="advantages__stat card">
				<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/IMG_9252-5.webp" alt="WHOOP Age 30.6" width="128" height="127" loading="lazy" /></div>
				<div class="advantages__stat-body"><span class="advantages__stat-title">WHOOP Age и Pace of Aging</span><span class="advantages__stat-text section-subtext">Оценка биологического возраста и скорости старения</span></div>
			</div>
		</div>

		<h4 class="advantages__stats-subtitle">Дополнительно в WHOOP MG:</h4>

		<div class="advantages__stats">
			<div class="advantages__stat card">
				<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100290.webp" alt="Heart Screener" width="204" height="85" loading="lazy" /></div>
				<div class="advantages__stat-body"><span class="advantages__stat-title">Heart Screener (ЭКГ)</span><span class="advantages__stat-text section-subtext">Запись электрокардиограммы прямо с браслета</span></div>
			</div>
			<div class="advantages__stat card">
				<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100291-1.webp" alt="Blood Pressure Insights 109/71" width="204" height="91" loading="lazy" /></div>
				<div class="advantages__stat-body"><span class="advantages__stat-title">Blood Pressure Insights</span><span class="advantages__stat-text section-subtext">Ежедневная оценка артериального давления</span></div>
			</div>
			<div class="advantages__stat card">
				<div class="advantages__stat-media"><img src="/wp-content/uploads/2026/09/Rectangle-100291.webp" alt="Normal Sinus Rhythm" width="204" height="91" loading="lazy" /></div>
				<div class="advantages__stat-body"><span class="advantages__stat-title">Уведомления</span><span class="advantages__stat-text section-subtext">о нерегулярном сердечном ритме</span></div>
			</div>
		</div>
	</div>
	<!-- /wp:html -->

	<!-- className на самом блоке core/button (а не ручные атрибуты на <a>) —
	чтобы разметка осталась валидной для блочного редактора: скрипт находит
	кнопку по этому классу и сам навешивает role/aria-expanded.
	Правка 2026-09-11 (2): по прямой просьбе пользователя кнопка работает как
	тумблер и подписью "Свернуть" сворачивает раскрытую часть обратно. В Figma
	такого состояния нет (в кадре "Раскрытый" кнопки под сеткой нет вообще) —
	это осознанное решение сверх макета, а не находка в дампе. Обе подписи
	лежат в data-атрибутах на самом .advantages__more (он внутри wp:html, то
	есть сырая разметка) — вешать их на <a> внутри core/button нельзя: лишний
	атрибут в core-блоке ломает валидацию блока в редакторе. -->
	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"className":"advantages__more-toggle"} -->
		<div class="wp-block-button advantages__more-toggle"><a class="wp-block-button__link wp-element-button" href="#advantages-more">Посмотреть ещё</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
