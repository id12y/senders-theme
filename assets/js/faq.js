/**
 * FAQ — Accordion Behaviour
 *
 * Enhances native <details><summary> elements with:
 * - Single-open mode: only one item open per section at a time
 * - Respects prefers-reduced-motion
 *
 * Without JS the accordion still works — <details> is natively
 * supported in all modern browsers.
 *
 * @package SenderSymposium
 */
(function () {
	'use strict';

	var page = document.querySelector('.faq-page');
	if (!page) return;

	var singleOpen = page.getAttribute('data-single-open') === 'true';
	if (!singleOpen) return;

	/* Listen for toggle events (fires when <details> opens or closes) */
	page.addEventListener('toggle', function (e) {
		var details = e.target;

		/* Only act on our FAQ items opening */
		if (details.tagName !== 'DETAILS' || !details.open) return;
		if (!details.classList.contains('faq-item')) return;

		var accordion = details.closest('.faq-accordion');
		if (!accordion) return;

		/* Close all other open items in this accordion */
		var siblings = accordion.querySelectorAll('details.faq-item');
		for (var i = 0; i < siblings.length; i++) {
			if (siblings[i] !== details && siblings[i].open) {
				siblings[i].removeAttribute('open');
			}
		}
	}, true);
})();
