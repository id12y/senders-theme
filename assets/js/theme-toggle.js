/**
 * Sender Symposium — Theme Toggle (dark/light mode)
 *
 * Tiny, deferred script. The inline bootstrap in <head> handles
 * initial theme application to prevent FOUC. This script wires up
 * the interactive toggle button.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'ss-theme';
  var ATTR = 'data-theme';
  var root = document.documentElement;

  /**
   * Return the resolved theme: stored pref > system pref > 'light'.
   */
  function getResolvedTheme() {
    var stored = null;
    try {
      stored = localStorage.getItem(STORAGE_KEY);
    } catch (e) {
      /* localStorage unavailable — fall through */
    }
    if (stored === 'dark' || stored === 'light') return stored;
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return 'dark';
    return 'light';
  }

  /**
   * Apply theme to <html> and update toggle buttons.
   */
  function applyTheme(theme) {
    root.setAttribute(ATTR, theme);
    var toggles = document.querySelectorAll('.theme-toggle');
    for (var i = 0; i < toggles.length; i++) {
      toggles[i].setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    }
  }

  /**
   * Toggle between light ↔ dark and persist.
   */
  function toggleTheme() {
    var current = root.getAttribute(ATTR) || getResolvedTheme();
    var next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    try {
      localStorage.setItem(STORAGE_KEY, next);
    } catch (e) {
      /* localStorage unavailable */
    }
  }

  /* Wire up all toggle buttons */
  function init() {
    var toggles = document.querySelectorAll('.theme-toggle');
    for (var i = 0; i < toggles.length; i++) {
      toggles[i].addEventListener('click', toggleTheme);
    }

    /* Apply current resolved theme (inline bootstrap may have already done this) */
    applyTheme(getResolvedTheme());

    /* Listen for system-level changes */
    if (window.matchMedia) {
      var mq = window.matchMedia('(prefers-color-scheme: dark)');
      mq.addEventListener('change', function () {
        var stored = null;
        try {
          stored = localStorage.getItem(STORAGE_KEY);
        } catch (e) { /* noop */ }
        if (!stored) {
          applyTheme(getResolvedTheme());
        }
      });
    }
  }

  /* Run on DOMContentLoaded if document not ready, else immediately */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
