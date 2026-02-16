/**
 * TicketTailor — Fallback Detection
 *
 * Runs after widget.js (declared as dependency via wp_enqueue_script).
 * Polls for an iframe inside each [data-ss-tt-fallback] container.
 * If widget.js fails to inject one within the timeout (e.g. ad blocker,
 * CDN down, third-party cookie block), reveals the hidden .ss-tt-fallback.
 *
 * Does NOT create or modify the .tt-widget div — that must exist in the
 * server-rendered HTML so widget.js can find it during its one-time scan.
 *
 * @package SenderSymposium
 */
(function () {
	'use strict';

	var containers = document.querySelectorAll('[data-ss-tt-fallback]');
	if (!containers.length) return;

	containers.forEach(function (wrap) {
		var fallback = wrap.querySelector('.ss-tt-fallback');
		if (!fallback) return;

		var checkCount    = 0;
		var maxChecks     = 10;   /* 10 × 500ms = 5 s total wait */
		var checkInterval = 500;

		var checker = setInterval(function () {
			checkCount++;

			/* widget.js injects an iframe inside .tt-widget */
			if (wrap.querySelector('iframe')) {
				clearInterval(checker);
				return;
			}

			if (checkCount >= maxChecks) {
				clearInterval(checker);
				/* Widget didn't render — show fallback content */
				fallback.hidden = false;
			}
		}, checkInterval);
	});
})();
