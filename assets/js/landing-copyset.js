/**
 * Landing Page Copy Set Import / Export
 *
 * Reads/writes form fields inside #ss_landing_meta_box to enable
 * bulk JSON import/export of landing page copy.
 *
 * @package SenderSymposium
 */
(function () {
	'use strict';

	/* Bail if the landing meta box is not on this page */
	var metaBox = document.getElementById( 'ss_landing_meta_box' );
	if ( ! metaBox ) {
		return;
	}

	/* Sections included in the copy set (order matches meta box) */
	var COPY_SECTIONS = [
		'hero', 'event', 'offer', 'why',
		'value_stack', 'audience', 'notes', 'closing'
	];

	/* Fields to exclude from the copy set */
	var EXCLUDED_FIELDS = {
		hero: [ 'partner_image_id', 'partner_logo_id', 'partner_logo_dark_id' ]
	};

	/* Sections to exclude entirely */
	var EXCLUDED_SECTIONS = [ 'tickettailor' ];

	/* Checkbox fields (need boolean handling) */
	var CHECKBOX_FIELDS = {
		hero:        [ 'show_scarcity', 'show_micro_trust' ],
		event:       [ 'show' ],
		offer:       [ 'show' ],
		why:         [ 'show' ],
		value_stack: [ 'show' ],
		audience:    [ 'show' ],
		notes:       [ 'show' ],
		closing:     [ 'show' ]
	};

	/* ── Helpers ── */

	function getField( section, field ) {
		return metaBox.querySelector(
			'[name="ss_landing[' + section + '][' + field + ']"]'
		);
	}

	function isExcluded( section, field ) {
		if ( EXCLUDED_SECTIONS.indexOf( section ) !== -1 ) return true;
		if ( EXCLUDED_FIELDS[ section ] &&
		     EXCLUDED_FIELDS[ section ].indexOf( field ) !== -1 ) return true;
		return false;
	}

	function isCheckbox( section, field ) {
		return CHECKBOX_FIELDS[ section ] &&
		       CHECKBOX_FIELDS[ section ].indexOf( field ) !== -1;
	}

	function showStatus( html, type ) {
		var el = document.getElementById( 'ss-landing-copyset-status' );
		if ( ! el ) return;
		var colors = {
			success: { bg: '#edfaef', border: '#00a32a', text: '#00450b' },
			error:   { bg: '#fcf0f1', border: '#d63638', text: '#8a2424' },
			warning: { bg: '#fef8ee', border: '#dba617', text: '#614200' }
		};
		var c = colors[ type ] || colors.success;
		el.innerHTML = '<div style="padding:8px 12px;border-left:4px solid '
			+ c.border + ';background:' + c.bg + ';color:' + c.text
			+ ';margin-top:8px;">' + html + '</div>';
	}

	function clearStatus() {
		var el = document.getElementById( 'ss-landing-copyset-status' );
		if ( el ) el.innerHTML = '';
	}

	/* ── Export ── */

	var exportBtn = document.getElementById( 'ss-landing-copyset-export' );
	if ( exportBtn ) {
		exportBtn.addEventListener( 'click', function () {
			clearStatus();
			var data = {};

			COPY_SECTIONS.forEach( function ( section ) {
				data[ section ] = {};
				var fields = metaBox.querySelectorAll(
					'[name^="ss_landing[' + section + ']"]'
				);
				fields.forEach( function ( el ) {
					var match = el.name.match( /^ss_landing\[\w+\]\[(\w+)\]$/ );
					if ( ! match ) return;
					var fieldName = match[1];
					if ( isExcluded( section, fieldName ) ) return;

					if ( el.type === 'checkbox' ) {
						data[ section ][ fieldName ] = el.checked;
					} else {
						data[ section ][ fieldName ] = el.value;
					}
				} );
			} );

			var json = JSON.stringify( data, null, 2 );
			var textarea = document.getElementById( 'ss-landing-copyset-json' );

			navigator.clipboard.writeText( json ).then( function () {
				showStatus( 'Copied to clipboard.', 'success' );
			} ).catch( function () {
				/* Fallback: put JSON in textarea for manual copy */
				if ( textarea ) {
					textarea.value = json;
					textarea.select();
				}
				showStatus(
					'Could not copy to clipboard. The JSON has been placed in the textarea — copy it manually.',
					'warning'
				);
			} );
		} );
	}

	/* ── Import ── */

	var importBtn = document.getElementById( 'ss-landing-copyset-import' );
	if ( importBtn ) {
		importBtn.addEventListener( 'click', function () {
			clearStatus();
			var textarea = document.getElementById( 'ss-landing-copyset-json' );
			var raw = ( textarea ? textarea.value : '' ).trim();

			if ( ! raw ) {
				showStatus( 'Paste a JSON copy set into the textarea above.', 'error' );
				return;
			}

			var data;
			try {
				data = JSON.parse( raw );
			} catch ( e ) {
				showStatus( 'Invalid JSON: ' + e.message, 'error' );
				return;
			}

			if ( typeof data !== 'object' || data === null || Array.isArray( data ) ) {
				showStatus(
					'JSON must be an object with section keys (hero, event, offer, etc.).',
					'error'
				);
				return;
			}

			var populated = 0;
			var skipped   = 0;
			var warnings  = [];

			COPY_SECTIONS.forEach( function ( section ) {
				if ( ! data[ section ] || typeof data[ section ] !== 'object' ) return;

				Object.keys( data[ section ] ).forEach( function ( fieldName ) {
					if ( isExcluded( section, fieldName ) ) {
						skipped++;
						return;
					}

					var el = getField( section, fieldName );
					if ( ! el ) {
						warnings.push( section + '.' + fieldName + ' — field not found' );
						return;
					}

					var value = data[ section ][ fieldName ];

					if ( el.type === 'checkbox' ) {
						el.checked = !! value;
					} else if ( el.tagName === 'SELECT' ) {
						var valid = false;
						for ( var i = 0; i < el.options.length; i++ ) {
							if ( el.options[i].value === String( value ) ) {
								valid = true;
								break;
							}
						}
						if ( valid ) {
							el.value = String( value );
						} else {
							warnings.push(
								section + '.' + fieldName
								+ ' — invalid select value "' + value + '"'
							);
							return;
						}
					} else {
						el.value = ( value === null || value === undefined )
							? '' : String( value );
					}

					populated++;
				} );
			} );

			/* Warn about unknown sections */
			Object.keys( data ).forEach( function ( key ) {
				if ( key === '_copyset_version' ) return;
				if ( COPY_SECTIONS.indexOf( key ) === -1
				     && EXCLUDED_SECTIONS.indexOf( key ) === -1 ) {
					warnings.push( 'Unknown section "' + key + '" — ignored' );
				}
			} );

			var msg = 'Imported ' + populated + ' field'
				+ ( populated !== 1 ? 's' : '' ) + '.';
			if ( skipped > 0 ) {
				msg += ' ' + skipped + ' excluded field'
					+ ( skipped !== 1 ? 's' : '' ) + ' skipped.';
			}
			if ( warnings.length > 0 ) {
				msg += '<br><strong>Warnings:</strong><br>— '
					+ warnings.join( '<br>— ' );
			}

			showStatus( msg, warnings.length > 0 ? 'warning' : 'success' );
		} );
	}

})();
