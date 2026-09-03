<?php
/**
 * Title: Advantages sticky-scroll
 * Slug: whoopstore/advantages-sticky-scroll
 *
 * По Figma-аннотации: заголовок появляется вначале и пропадает; при скролле
 * телефон и подпись фиксируются (position: sticky), содержимое подписи
 * меняется по мере прохождения соответствующего пункта.
 * Единственный подтверждённый потребитель этой механики — секция
 * "Преимущества" на Главной (см. sanity-check pilot-архитектуры),
 * поэтому реализовано как часть паттерна Главной, а не отдельным блоком.
 */
?>
<!-- wp:group {"className":"advantages","layout":{"type":"constrained"}} -->
<div class="wp-block-group advantages">

	<!-- wp:heading {"fontSize":"xl","className":"advantages__title"} -->
	<h2 class="wp-block-heading advantages__title has-xl-font-size">Преимущества новых трекеров Whoop 5.0</h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="advantages__scroller" data-advantages>
		<div class="advantages__sticky">
			<div class="advantages__phone" aria-hidden="true"></div>
			<div class="advantages__caption">
				<h3 class="advantages__caption-title" data-advantages-title>Измерение ЭКГ прямо с запястья</h3>
				<p class="advantages__caption-text" data-advantages-text>Измеряйте ЭКГ в любое время — без проводов и клиник. Помогает выявлять отклонения и делиться данными с врачом. Доступно с трекером WHOOP MG</p>
			</div>
		</div>
		<div class="advantages__items">
			<div class="advantages__item is-active" data-advantages-item data-title="Измерение ЭКГ прямо с запястья" data-text="Измеряйте ЭКГ в любое время — без проводов и клиник. Помогает выявлять отклонения и делиться данными с врачом. Доступно с трекером WHOOP MG"></div>
			<div class="advantages__item" data-advantages-item data-title="Мониторинг артериального давления" data-text="WHOOP Life с трекером MG отслеживает давление в реальном времени, помогая замечать отклонения и лучше понимать влияние стресса, сна и тренировок на сердечно-сосудистую систему"></div>
		</div>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
