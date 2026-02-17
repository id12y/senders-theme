/**
 * Sender Symposium — Theme Toggle (dark/light mode)
 *
 * Tiny, deferred script. The inline bootstrap in <head> handles
 * initial theme application to prevent FOUC. This script wires up
 * the interactive toggle button.
 *
 * The PHP bootstrap may set a data-theme-forced attribute on <html>.
 * When forced, the toggle is disabled and localStorage is ignored.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'ss-theme';
  var ATTR = 'data-theme';
  var FORCED_ATTR = 'data-theme-forced';
  var root = document.documentElement;

  /**
   * Check if admin has forced a specific mode.
   */
  function isForced() {
    return root.hasAttribute(FORCED_ATTR);
  }

  /**
   * Return the resolved theme: stored pref > system pref > 'light'.
   */
  function getResolvedTheme() {
    if (isForced()) {
      return root.getAttribute(ATTR) || 'light';
    }
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
   * Sync hero background image to match theme when a dark variant exists.
   * Works with the <picture> + data-attribute markup from render.php.
   *
   * @param {string}  theme     - 'light' or 'dark'
   * @param {boolean} crossfade - true for toggle clicks (fade-out/in),
   *                              false for init (immediate swap)
   */
  function syncHeroBg(theme, crossfade) {
    var img = document.querySelector('.ss-hero__bg[data-dark-src]');
    if (!img) return;
    var source = img.closest && img.closest('picture');
    source = source && source.querySelector('source[media]');
    if (source) source.media = 'not all';

    var newSrc = theme === 'dark' ? img.dataset.darkSrc : img.dataset.lightSrc;
    if (crossfade) {
      img.style.opacity = '0';
      setTimeout(function () {
        img.src = newSrc;
        img.style.opacity = '';
      }, 300);
    } else {
      img.src = newSrc;
    }
  }

  /**
   * Apply theme to <html> and update toggle buttons.
   */
  function applyTheme(theme) {
    root.setAttribute(ATTR, theme);
    syncHeroBg(theme, true);
    var toggles = document.querySelectorAll('.theme-toggle');
    for (var i = 0; i < toggles.length; i++) {
      toggles[i].setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    }
  }

  /**
   * Toggle between light and dark and persist.
   */
  function toggleTheme() {
    if (isForced()) return; /* Admin has locked the mode */
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

    /*
     * Sync ARIA state and hero background to match the theme already
     * set by the bootstrap. Do NOT re-resolve the theme — trust the
     * bootstrap's decision to avoid overwriting a forced mode or
     * flickering.
     */
    var currentTheme = root.getAttribute(ATTR);
    if (currentTheme) {
      syncHeroBg(currentTheme, false);
      root.classList.remove('ss-hero-mismatch');
      for (var j = 0; j < toggles.length; j++) {
        toggles[j].setAttribute('aria-pressed', currentTheme === 'dark' ? 'true' : 'false');
      }
    } else {
      /* Bootstrap didn't run (shouldn't happen, but be safe) */
      applyTheme(getResolvedTheme());
    }

    /* Listen for system-level changes (only when not forced) */
    if (window.matchMedia && !isForced()) {
      var mq = window.matchMedia('(prefers-color-scheme: dark)');
      var handler = function () {
        var stored = null;
        try {
          stored = localStorage.getItem(STORAGE_KEY);
        } catch (e) { /* noop */ }
        if (!stored) {
          applyTheme(getResolvedTheme());
        }
      };
      /* Safari < 14 support: addListener is deprecated but has wider compat */
      if (mq.addEventListener) {
        mq.addEventListener('change', handler);
      } else if (mq.addListener) {
        mq.addListener(handler);
      }
    }
  }

  /* Run on DOMContentLoaded if document not ready, else immediately */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
