/**
 * Hero — Lightweight Countdown
 *
 * Updates once per minute. No animations. No ticking.
 * If JS disabled, <noscript> inside the countdown element shows a static date.
 *
 * @package SenderSymposium
 */
(function () {
	'use strict';

	var el = document.querySelector('.ss-hero__countdown[data-target]');
	if (!el) return;

	var target = new Date(el.getAttribute('data-target'));
	if (isNaN(target.getTime())) return;

	function render() {
		var now  = new Date();
		var diff = target - now;

		if (diff <= 0) {
			el.innerHTML = '<span class="ss-hero__countdown-ended">Event has started</span>';
			return false;
		}

		var days    = Math.floor(diff / 86400000);
		var hours   = Math.floor((diff % 86400000) / 3600000);
		var minutes = Math.floor((diff % 3600000) / 60000);

		el.innerHTML =
			'<div class="ss-hero__countdown-unit">' +
				'<span class="ss-hero__countdown-number">' + days + '</span>' +
				'<span class="ss-hero__countdown-label">Days</span>' +
			'</div>' +
			'<div class="ss-hero__countdown-unit">' +
				'<span class="ss-hero__countdown-number">' + hours + '</span>' +
				'<span class="ss-hero__countdown-label">Hours</span>' +
			'</div>' +
			'<div class="ss-hero__countdown-unit">' +
				'<span class="ss-hero__countdown-number">' + minutes + '</span>' +
				'<span class="ss-hero__countdown-label">Minutes</span>' +
			'</div>';

		return true;
	}

	/* Render immediately, then update once per minute */
	if (render()) {
		setInterval(render, 60000);
	}
})();
