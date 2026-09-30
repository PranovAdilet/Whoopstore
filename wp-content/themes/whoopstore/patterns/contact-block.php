<?php
/**
 * Title: Contact block
 * Slug: whoopstore/contact-block
 *
 * Первый проход по всей Главной (2026-09-07), источник — Figma MCP get_metadata
 * (node 1:3464, "Мобилка") — см. accessories-grid.php.
 *
 * Под FAQ — 2 карточки: карта (Rectangle 100226, 320×320 в мобильной
 * раскладке — реальной карты/embed нет, placeholder) и инфо-карточка
 * (Rectangle 100227) с подтверждёнными текстами: телефон, часы работы,
 * адрес, иконки мессенджеров (Telegram + "MAX") и юр. реквизиты. Телефон
 * здесь ("+7 (499) 653-51-71") отличается от телефона в футере
 * ("+7 (495) 824-70-54", см. parts/footer.html) — оба взяты из реальных,
 * разных текстовых слоёв Figma, это НЕ опечатка и не унифицировано
 * намеренно.
 *
 * Обновление 2026-09-09 (round 12): реальные SVG-логотипы Telegram/MAX
 * получены от пользователя (готовые ассеты из Figma, узлы 1:387/1:391,
 * оба чистый вектор — без встроенного растра, поэтому копия из вставленного
 * текста надёжна, см. общий урок про base64-блобы в памяти сессии) —
 * заменили инлайн-SVG-глиф Telegram и текстовую заглушку "MAX". Оба файла
 * уже содержат собственный цветной фон (круг/скруглённый квадрат) — серый
 * кружок-подложка `.contact-block__messenger` больше не нужен, убран.
 *
 * Аудит 2026-09-07 (Critical): подтверждено get_design_context
 * (supplementary блок 4, node 1:382) — инфо-карточка светлая (bg-white),
 * не .card — используется .card--light. Юр.текст — Onest:Light 13px
 * (не sm-токен 14px), см. .contact-block__legal в theme.css.
 *
 * QA раунд 2 (2026-09-07): карта была пустой градиент-заглушкой — в Figma
 * это реальное растровое изображение (не embed-карта), найден и скачан
 * настоящий ассет (node 1:380, get_design_context, homepage-desktop
 * partial-1 raw-дамп).
 *
 * Визуальный дифф 2026-09-09 (скриншот Figma vs stage от пользователя),
 * сверено с raw-дампом (docs/figma-design/raw/homepage-desktop.partial-1.
 * code.tsx.txt строки 986-1017 + metadata.xml): 3 подтверждённых
 * расхождения, все исправлены:
 * 1) Порядок карточек на desktop был перепутан — карта x=610 (справа),
 *    инфо-карточка x=40 (слева); DOM-порядок (карта первым ребёнком)
 *    оставлен как есть (на mobile карта ДЕЙСТВИТЕЛЬНО идёт первой/сверху —
 *    node 1:3835 y=15505 < node 1:3836 y=15837), визуальный свап только
 *    через `.contact-block__info{order:-1}` на desktop (theme.css).
 * 2) Текст часов работы (node 1:385) имеет фиксированную ширину 216px —
 *    это принудительный перенос на 2 строки ("Мы работаем по будням" /
 *    "с 11:00 до 20:00 по адресу:"), не auto-wrap на всю ширину карточки.
 * 3) Порядок иконок мессенджеров был неверным: MAX (node 1:391, x=70) —
 *    ПЕРВАЯ (левее), Telegram (node 1:387, x=110) — вторая; то же на
 *    mobile (node 1:3845 MAX x=50 < node 1:3841 Telegram x=90-ish).
 *    Разметка ниже переставлена местами.
 *
 * Технический нюанс (важно для будущих похожих паттернов): у обёртки
 * .contact-block раньше было "layout":{"type":"constrained"} — WP-ядро
 * (wp_render_layout_support_flag, wp-includes/block-supports/layout.php)
 * ищет "последний тег с class= перед первым вложенным блоком" в сыром
 * HTML группы и сажает is-layout-constrained/has-global-padding ИМЕННО
 * туда, а не на саму группу — у нас это оказался НЕ .contact-block, а
 * вложенный .contact-block__info (див между картой и первым wp:paragraph).
 * Тот же баг уже был найден и исправлен для .faq__list (round 5, см.
 * комментарий в theme.css у .faq__list) — то же решение: атрибут
 * "layout" убран целиком (ширина/flex тут и так все свои CSS-правила,
 * автоматика Gutenberg не нужна). Без него WP сажает более безобидный
 * is-layout-flow (только margin-block-start:24px у некрайних детей, без
 * форсящего margin:auto) — остаточный эффект (лишний отступ у
 * .contact-block__address) погашен явным margin-top в theme.css.
 */
?>
<!-- wp:group {"anchor":"contacts","className":"contact-block"} -->
<div class="wp-block-group contact-block" id="contacts">

	<img class="contact-block__map" src="/wp-content/uploads/2026/09/contact-map.webp" alt="" loading="lazy" />

	<div class="contact-block__info card card--light">
		<!-- Аудит 2026-09-07 (единая типографика): телефон был has-md-font-size
		(20px) — по raw-дампу Figma (node 1:383) это 32px, т.е. тот же
		section-heading, что и H2 секций (переиспользован здесь на обычном
		<p>, не на заголовке — так и в самой Figma: это не семантический
		заголовок, а крупный текст той же типографической роли). Адрес был
		sm=14px — по Figma (node 1:384) это card-heading (20px Medium,
		"плотный" трекинг), light-вариант под белую карточку. -->
		<!-- wp:paragraph {"className":"contact-block__phone section-heading"} -->
		<p class="contact-block__phone section-heading"><a href="tel:+74996535171">+7 (499) 653-51-71</a></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"contact-block__hours section-subtext section-subtext--light section-subtext--tight"} -->
		<p class="contact-block__hours section-subtext section-subtext--light section-subtext--tight">Мы работаем по будням с 11:00 до 20:00 по адресу:</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"contact-block__address card-heading card-heading--tight card-heading--light"} -->
		<p class="contact-block__address card-heading card-heading--tight card-heading--light">Москва, ул. Олеко Дундича, 3</p>
		<!-- /wp:paragraph -->
	
		

		<div class="contact-block__messengers-wrapper">
		

			<!-- wp:paragraph {"className":"contact-block__legal"} -->
			<p class="contact-block__legal">ИП Гуменюк Сергей Александрович ОГРН/ОГРНИП 326508100066255 ИНН 500407393045</p>
			<!-- /wp:paragraph -->

			<?php /* Правка 2026-09-25 (Telegram) и 2026-09-30 (MAX): заказчик прислал
			        адреса, обе иконки стали ссылками. Скрывать их от скринридеров
			        нельзя — ссылка получает фокус, — поэтому aria-hidden снят,
			        а назначение каждой ссылки несёт alt картинки внутри. */ ?>
			<div class="contact-block__messengers">
				<a class="contact-block__messenger-link" href="https://max.ru/u/f9LHodD0cOLfvmodkx5uYwC1o__5uB8sZoI1D5Fxxs39CNKkZrfX2_cHKYw" target="_blank" rel="noopener">
					<img class="contact-block__messenger" src="<?php echo esc_url( get_theme_file_uri( 'assets/icons/max-logo.svg' ) ); ?>" width="30" height="30" alt="Whoopstore в мессенджере MAX" loading="lazy" />
				</a>
				<a class="contact-block__messenger-link" href="https://t.me/whooppstore" target="_blank" rel="noopener">
					<img class="contact-block__messenger" src="<?php echo esc_url( get_theme_file_uri( 'assets/icons/telegram-logo.svg' ) ); ?>" width="30" height="30" alt="Телеграм-канал Whoopstore" loading="lazy" />
				</a>
			</div>
		</div>
	</div>

</div>
<!-- /wp:group -->
