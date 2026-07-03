/**
 * Settings screen behaviour: add/remove connection rows, test connection.
 * Vanilla JS, no jQuery.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var table = document.getElementById( 'eex-connections' );
		var addButton = document.getElementById( 'eex-add-connection' );

		if ( ! table ) {
			return;
		}

		function reindexRows() {
			var rows = table.querySelectorAll( 'tbody tr.eex-connection-row' );
			rows.forEach( function ( row, index ) {
				row.querySelectorAll( 'input[name]' ).forEach( function ( input ) {
					input.name = input.name.replace( /eex_connections\[\d+\]/, 'eex_connections[' + index + ']' );
				} );
			} );
		}

		if ( addButton ) {
			addButton.addEventListener( 'click', function () {
				var tbody = table.querySelector( 'tbody' );
				var index = tbody.querySelectorAll( 'tr.eex-connection-row' ).length;
				var row = document.createElement( 'tr' );
				row.className = 'eex-connection-row';
				row.innerHTML =
					'<td><input type="hidden" name="eex_connections[' + index + '][id]" value="" />' +
					'<input type="text" class="regular-text" name="eex_connections[' + index + '][label]" /></td>' +
					'<td><input type="password" class="regular-text" name="eex_connections[' + index + '][api_key]" autocomplete="new-password" /></td>' +
					'<td><button type="button" class="button-link-delete eex-remove-connection">Remove</button></td>';
				tbody.appendChild( row );
			} );
		}

		table.addEventListener( 'click', function ( event ) {
			var removeButton = event.target.closest( '.eex-remove-connection' );
			if ( removeButton ) {
				removeButton.closest( 'tr' ).remove();
				reindexRows();
				return;
			}

			var testButton = event.target.closest( '.eex-test-connection' );
			if ( ! testButton || typeof window.eexSettings === 'undefined' ) {
				return;
			}

			var resultEl = testButton.parentElement.querySelector( '.eex-test-result' );
			testButton.disabled = true;
			if ( resultEl ) {
				resultEl.textContent = window.eexSettings.i18n.testing;
			}

			var body = new URLSearchParams();
			body.set( 'action', 'eex_test_connection' );
			body.set( '_ajax_nonce', window.eexSettings.testNonce );
			body.set( 'connection', testButton.getAttribute( 'data-connection' ) );

			window
				.fetch( window.eexSettings.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: body.toString(),
				} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					if ( resultEl ) {
						resultEl.textContent =
							json && json.data && json.data.message
								? json.data.message
								: window.eexSettings.i18n.testError;
					}
				} )
				.catch( function () {
					if ( resultEl ) {
						resultEl.textContent = window.eexSettings.i18n.testError;
					}
				} )
				.finally( function () {
					testButton.disabled = false;
				} );
		} );
	} );
} )();
