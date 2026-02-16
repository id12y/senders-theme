/**
 * Geek Layer — Sender Symposium
 *
 * Rewards technically curious visitors with a subtle console message.
 * No DOM manipulation. No network calls. No dependencies.
 *
 * @package SenderSymposium
 */
(function () {
	'use strict';
	var GL = window.SS_GEEK_LAYER_ENABLED;
	if (typeof GL === 'undefined' || !GL) return;
	console.log(
		'%cYou read source. We respect that.',
		'font-weight:bold'
	);
	console.log(
		'Emailexpert is built for operators who care about pipeline discipline,\n' +
		'lifecycle architecture, and systems that compound.'
	);
	console.log(
		'If you hold a ticket, reply to your order confirmation with\n' +
		'"Closed Won" for access to a private networking experience.'
	);
	console.log(
		'Curious minds tend to run better pipelines. Welcome.'
	);
})();
