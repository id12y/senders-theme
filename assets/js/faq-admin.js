/**
 * FAQ Admin — Sender Symposium
 *
 * @package SenderSymposium
 */
(function () {
	'use strict';

	/* Auto-expand textarea height on focus */
	document.querySelectorAll('.ss-faq-item-fieldset textarea').forEach(function (el) {
		el.addEventListener('focus', function () {
			if (this.scrollHeight > this.clientHeight) {
				this.style.minHeight = this.scrollHeight + 'px';
			}
		});
	});
})();
