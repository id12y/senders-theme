/**
 * Sender Symposium — Text Size Toggle (normal ↔ large)
 *
 * Mirrors the theme-toggle.js pattern. The inline bootstrap in <head>
 * handles initial data-text-size application to prevent FOUC.
 * This script wires up the interactive toggle button.
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'ss-text-size';
	var ATTR        = 'data-text-size';
	var root        = document.documentElement;

	/**
	 * Return the current text size state.
	 */
	function getTextSize() {
		return root.getAttribute( ATTR ) === 'large' ? 'large' : 'normal';
	}

	/**
	 * Apply text size to <html> and update all toggle buttons.
	 */
	function applyTextSize( size ) {
		if ( size === 'large' ) {
			root.setAttribute( ATTR, 'large' );
		} else {
			root.removeAttribute( ATTR );
		}

		var toggles = document.querySelectorAll( '.text-size-toggle' );
		for ( var i = 0; i < toggles.length; i++ ) {
			toggles[ i ].setAttribute( 'aria-pressed', size === 'large' ? 'true' : 'false' );
		}
	}

	/**
	 * Toggle between normal and large, then persist.
	 */
	function toggleTextSize() {
		var next = getTextSize() === 'large' ? 'normal' : 'large';
		applyTextSize( next );

		try {
			if ( next === 'normal' ) {
				localStorage.removeItem( STORAGE_KEY );
			} else {
				localStorage.setItem( STORAGE_KEY, next );
			}
		} catch ( e ) {
			/* localStorage unavailable — graceful degradation. */
		}
	}

	/**
	 * Initialise: wire click handlers and sync ARIA to bootstrap state.
	 */
	function init() {
		var toggles = document.querySelectorAll( '.text-size-toggle' );

		for ( var i = 0; i < toggles.length; i++ ) {
			toggles[ i ].addEventListener( 'click', toggleTextSize );
		}

		/* Sync ARIA state with whatever the bootstrap set. */
		applyTextSize( getTextSize() );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
