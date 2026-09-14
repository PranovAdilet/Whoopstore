# Figma — Главная (Whoopstore): что уже забрано из MCP

Этот файл — чтобы **новая сессия не тратила лимит Figma MCP заново** на то, что уже
подтверждено. Прочитай его перед любым новым вызовом `mcp__figma__*` по Главной.

## Источник

- Figma-файл: `https://www.figma.com/design/MAGGfC2v8q7afPFdxBZWpK/Whoopstore--Copy-?node-id=1-3464&t=TnfBxlYaTp3yXHUD-0`
- `fileKey`: `MAGGfC2v8q7afPFdxBZWpK`
- Нода, которая опрошена: `1:3464`, имя слоя — **"Мобилка"** (мобильная композиция Главной, 360×17251).
- Вызов: `mcp__figma__get_metadata` (структура + текстовые слои + геометрия, **без** скриншотов и без `get_design_context`).
- Десктопная нода (соседний top-level фрейм на странице `0:1`) **не опрошена** — лимит
  MCP (Starter plan) исчерпался сразу после одного вызова `get_metadata`. Если лимит
  снова доступен — в первую очередь стоит: 1) `get_metadata(nodeId: "0:1")`, чтобы найти
  id десктоп-фрейма; 2) `get_design_context` по нему и/или по отдельным мобильным
  секциям ниже, где отмечено "нет get_design_context".
- Сырой XML-дамп той метадаты лежит рядом: [figma-homepage-mobile-metadata.xml](figma-homepage-mobile-metadata.xml)
  (полное дерево node id/name/x/y/width/height — можно грепать вместо повторного вызова MCP).

## Статус реализации (2026-09-07, первый полный проход)

Все 16 секций ниже реализованы как WP block patterns в `wp-content/themes/whoopstore/patterns/`
и подключены по порядку в `templates/front-page.html`. У каждого файла в шапке — свой
комментарий с деталями и пометками "подтверждено / placeholder".

| # | Секция (рус. заголовок из Figma) | y (mobile) | Pattern slug | Заметка |
|---|---|---|---|---|
| 1 | Hero ("Купить фитнес-трекер Whoop в России") | 92–247 | `whoopstore/hero-panel` | реализовано раньше |
| 2 | Device grid (фильтр + 6 карточек) | 756–2939 | `whoopstore/device-grid` | реализовано раньше, тексты/цены сверены — совпадают |
| 3 | "Аксессуары" (2 карточки) | 2939–3644 | `whoopstore/accessories-grid` | новое |
| 4 | "Активация и продление устройства" (4 карточки) | 3856–5655 | `whoopstore/activation-grid` | новое; **переделано 2026-09-10** — это карточки тарифа (`.device-card--plan`), а не фото+бейдж модели; разбор в `figma-design/homepage-desktop.md` |
| 5 | "Ремешки для WHOOP 5.0 / MG" (компакт) | 5655–6212 | `whoopstore/straps-promo` | новое, свотчи-заглушка |
| 6 | Help block ("Подключение Whoop 5.0...") | 6212–6961 | `whoopstore/help-block` | реализовано раньше |
| 7 | Advantages (ECG + 8 показателей) | 6961–8940 | `whoopstore/advantages-sticky-scroll` | реализовано раньше |
| 8 | "WHOOP непрерывно анализирует..." | 9116–9728 | `whoopstore/analytics-banner` | новое, фон-плейсхолдер |
| 9 | "Персонализируйте свой WHOOP" (полная) | 9728–10510 | `whoopstore/straps-personalize` | новое, "CoreKnit Color: Royal Blue" — реальный текстовый слой |
| 10 | "Следите за здоровьем в приложении" (3 карточки) | 10510–11330 | `whoopstore/app-promo` | новое, иконки App Store/Google Play — placeholder (нет ассетов) |
| 11 | "Кому подойдет фитнес-браслет WHOOP?" (marquee) | 11330–12071 | `whoopstore/audience-marquee` | новое; в Figma ряд буквально задублирован смещённой копией → реализовано как бесконечная CSS-лента |
| 12 | "WHOOP помогает находить баланс между..." | 12071–12517 | `whoopstore/balance-banner` | новое |
| 13 | "Почему у WHOOP нет экрана?" + "Отсутствие дисплея..." | 12517–14257 | `whoopstore/no-display` | новое; **не подтверждено**, есть ли 2-й пункт преимущества (см. комментарий в файле) |
| 14 | "Частые вопросы" (9 пунктов) | 14493–15306 | `whoopstore/faq-accordion` | новое; **ответы аккордеона не подтверждены** (нужен `get_design_context` по раскрытому состоянию) |
| 15 | Контакты (карта + инфо-карточка) | 15505–16071 | `whoopstore/contact-block` | новое; телефон "+7 (499) 653-51-71" — другой, чем в футере ("+7 (495) 824-70-54"), оба реальные, разные |
| 16 | "Подпишитесь на нас в Телеграм!" | 16263–16441 | `whoopstore/telegram-subscribe` | новое, фото-плейсхолдер |
| — | Footer (nav/legal/тел.) | 16987–17181 | `parts/footer.html` | реализовано раньше, не трогалось |

## Что ещё нужно подтвердить через Figma (если лимит MCP снова будет доступен)

1. **Десктопная раскладка** новых 12 секций — сейчас раскладка достроена по аналогии с
   уже готовыми desktop-секциями (grid/flex + `@media (max-width: 781px)`), а не сверена
   с реальной desktop-нодой.
2. **no-display.php**: второй "Group 1000010789" (x=352, за пределами мобильного экрана)
   с тем же текстом, что первый ("Длительная автономность") — возможно horizontal-scroll
   карточек с РАЗНЫМ контентом, но `get_metadata` мог просто не отдать override текста
   инстанса компонента. Нужен `get_design_context` по фрейму `1:3757`/`1:3761`.
3. **faq-accordion.php**: тексты ответов (тела `<details>`) — не читаются в свёрнутом
   состоянии через `get_metadata`, сейчас плейсхолдер "Информация уточняется".
4. **Реальные assets**: фото/картинки для analytics-banner, no-display (мокап телефона),
   telegram-subscribe, карта в contact-block, палитра цветов ремешков, лого App
   Store/Google Play/"MAX".
5. Изображение самого баннера в device-card/accessories-grid/activation-grid — везде
   плейсхолдер-градиент (реальных фото товаров не было и раньше, см. device-grid.php).

## Технический нюанс, на который наткнулись при первом проходе

WordPress кэширует список файлов `patterns/` темы в site transient
(`wp_theme_files_patterns-*`, см. `WP_Theme::get_pattern_cache()` /
`wp-includes/class-wp-theme.php`). После добавления новых `.php` в `patterns/` кэш
не обновляется сам — новые паттерны просто не рендерятся (`<!-- wp:pattern --/>`
молча выводит пустоту, без ошибки). Если после добавления/переименования нового
pattern-файла он не появляется на странице — сначала:

```
wp eval 'wp_get_theme()->delete_pattern_cache();'
```

## Технический нюанс #2 (2026-09-08): margin-top у .section-canvas-секций обнулялся WP-ядром

Симптом: явный `margin-top: var(--wp--custom--section-gap--default, 140px)`
в theme.css есть, значение верное (сходится с raw-дампом Figma), но на
странице отступа сверху нет — визуально секция "прилипает" к предыдущей.
Затронуты все 4 секции, обёрнутые в `<div class="section-canvas">`
(straps-promo, straps-personalize, analytics-banner, telegram-subscribe) —
остальные секции (без .section-canvas) не задеты.

Причина найдена не гаданием, а прямым чтением сгенерированного WP CSS:

```
wp eval 'echo wp_get_global_stylesheet();' > /tmp/global-styles.css
```

В нём есть правило ядра (Gutenberg layout support, всегда включено для
блоков с `"layout":{"type":"constrained"}`):

```
:root :where(.is-layout-constrained) > :first-child{margin-block-start: 0;}
```

`.section-canvas` получает класс `is-layout-constrained` (у него в паттерне
`"layout":{"type":"constrained"}`), а карточка секции внутри — его
единственный ребёнок, то есть тот самый `:first-child`. Специфичность этого
правила — (0,2,0): `:where()` даёт 0, но сам `:root` и `:first-child` — по
(0,1,0) каждый. Наше `margin-top` на `.straps-carousel`/`.analytics-banner`/
`.telegram-subscribe` — обычный одиночный класс, (0,1,0), и WP-правило
всегда выигрывает по специфичности независимо от порядка подключения
стилей.

Фикс — не `!important`, а более специфичный селектор (0,3,0), однозначно
выше WP-правила, см. `.section-canvas > .wp-block-group.straps-carousel` и
соседние правила в theme.css (там же — mobile-override для `--compact`,
у него та же проблема была отдельно). Если margin-top на новой секции,
обёрнутой в `.section-canvas`, снова "не работает" — сначала проверить
именно это, а не структуру разметки.

а не искать баг в разметке.
