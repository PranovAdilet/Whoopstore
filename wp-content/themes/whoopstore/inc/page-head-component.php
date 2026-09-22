<?php
/**
 * Шапка контента подстраницы (.page-head) — крошки + H1.
 *
 * Один и тот же узел встречается во всех подстраницах-каталогах Figma
 * ("Ремешки для WHOOP 5.0", "Ремешки для WHOOP MG", "Подписки"): строка
 * хлебных крошек, под ней H1 в две строки, где вторая — акцентная
 * (Onest SemiBold тем же кеглем). Отличаются только два текста, поэтому
 * разметка живёт здесь одной функцией, а паттерн каждой страницы её
 * вызывает со своими строками — иначе на каждую страницу пришлось бы
 * копировать одинаковый блок HTML (правило 14 CLAUDE.md).
 *
 * Почему PHP-функция, а не Synced Pattern / собственный блок — см. то же
 * обоснование в шапке inc/card-component.php.
 *
 * Стили: .page-head* в assets/css/theme.css.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Печатает хлебные крошки подстраницы (.page-head__crumbs).
 *
 * Вынесены из whoopstore_page_head() отдельной функцией: тот же узел нужен
 * блоку контента страницы, где H1 редактируется в Gutenberg отдельно от
 * крошек, а сами крошки остаются за кодом — иконки лежат в теме, и путь к
 * ним не должен попадать в контент страницы.
 *
 * Отступы разметки намеренно сохранены такими, какими они были внутри
 * whoopstore_page_head() (2 таба у <nav>), а вызов в самой page_head стоит
 * с начала строки: вывод обеих функций вместе обязан совпадать со старым
 * побайтово, иначе сверка дампов "до/после" перестаёт что-либо доказывать.
 *
 * @param string $label Текст последней (текущей) крошки, напр. "Whoop MG".
 */
function whoopstore_breadcrumbs( $label ) {
	?>
		<nav class="page-head__crumbs" aria-label="Хлебные крошки">
			<a class="page-head__crumb-home" href="/" aria-label="Главная">
				<img src="<?php echo esc_url( get_theme_file_uri( 'assets/icons/breadcrumb-home.svg' ) ); ?>" alt="" width="15" height="15" />
			</a>
			<img class="page-head__crumb-sep" src="<?php echo esc_url( get_theme_file_uri( 'assets/icons/breadcrumb-separator.svg' ) ); ?>" alt="" width="15" height="15" />
			<span class="page-head__crumb-current" aria-current="page"><?php echo esc_html( $label ); ?></span>
		</nav>
<?php
}

/**
 * Печатает шапку контента подстраницы.
 *
 * @param array $args {
 *     @type string $crumb  Текст последней (текущей) крошки, напр. "Whoop MG".
 *                          Печатается whoopstore_breadcrumbs().
 *     @type string $title  Первая строка H1, напр. "Ремешки для WHOOP MG".
 *     @type string $accent Вторая, акцентная строка H1, напр. "(более 25 цветов)".
 *                          Если пусто — H1 остаётся однострочным.
 *     @type bool   $inline Акцент идёт продолжением той же строки, без
 *                          переноса (так на странице "Подписки": "…выберите
 *                          оптимальный план **для себя**"). По умолчанию
 *                          false — акцент со своей строки, как на страницах
 *                          ремешков.
 *     @type string $class  Дополнительный класс на обёртку .page-head, напр.
 *                          "page-head--in-hero" (шапка лежит внутри карточки
 *                          hero, а не отдельной секцией страницы).
 * }
 */
function whoopstore_page_head( array $args ) {
	$crumb  = isset( $args['crumb'] ) ? $args['crumb'] : '';
	$title  = isset( $args['title'] ) ? $args['title'] : '';
	$accent = isset( $args['accent'] ) ? $args['accent'] : '';
	$inline = ! empty( $args['inline'] );
	$class  = isset( $args['class'] ) ? ' ' . $args['class'] : '';
	?>
	<div class="page-head<?php echo esc_attr( $class ); ?>">
<?php whoopstore_breadcrumbs( $crumb ); ?>

		<h1 class="page-head__title section-heading">
			<?php echo esc_html( $title ); ?>
			<?php if ( '' !== $accent ) : ?>
				<?php echo $inline ? ' ' : '<br />'; ?><strong><?php echo esc_html( $accent ); ?></strong>
			<?php endif; ?>
		</h1>
	</div>
	<?php
}
