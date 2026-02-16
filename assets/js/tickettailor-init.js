/**
 * TicketTailor — Reliable Init
 *
 * Loads after widget.js (enqueued via wp_enqueue_script).
 * Finds containers with [data-tt-checkout-url], waits for the widget
 * to render, and reveals a fallback if it doesn't within a timeout.
 *
 * @package SenderSymposium
 */
(function () {
	'use strict';

	var containers = document.querySelectorAll('[data-tt-checkout-url]');
	if (!containers.length) return;

	containers.forEach(function (wrap) {
		/* Prevent double-init */
		if (wrap.getAttribute('data-tt-loaded') === '1') return;

		var url       = wrap.getAttribute('data-tt-checkout-url');
		var minimal   = wrap.getAttribute('data-tt-minimal') !== 'false';
		var showLogo  = wrap.getAttribute('data-tt-show-logo') === 'true';
		var fallback  = wrap.querySelector('.ss-tt-fallback');

		if (!url) {
			if (fallback) fallback.hidden = false;
			return;
		}

		/* Build the widget div that widget.js looks for */
		var widget = document.createElement('div');
		widget.className = 'tt-widget';
		widget.setAttribute('data-url', url);
		widget.setAttribute('data-type', 'inline');
		widget.setAttribute('data-inline-minimal', minimal ? 'true' : 'false');
		widget.setAttribute('data-inline-show-logo', showLogo ? 'true' : 'false');

		/* Insert before fallback (if present) or append */
		if (fallback) {
			wrap.insertBefore(widget, fallback);
		} else {
			wrap.appendChild(widget);
		}

		wrap.setAttribute('data-tt-loaded', '1');

		/* Check widget rendered: look for iframe injected by widget.js */
		var checkCount = 0;
		var maxChecks  = 10;         /* 10 × 500ms = 5s total wait */
		var checkInterval = 500;

		var checker = setInterval(function () {
			checkCount++;
			var iframe = wrap.querySelector('iframe');
			if (iframe) {
				clearInterval(checker);
				return;
			}
			if (checkCount >= maxChecks) {
				clearInterval(checker);
				/* widget.js didn't render — show fallback */
				if (fallback) {
					fallback.hidden = false;
				}
			}
		}, checkInterval);
	});
})();
