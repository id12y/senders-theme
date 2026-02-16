/**
 * Sender Symposium — Accessible Navigation
 *
 * Handles mobile menu toggle with ARIA states, keyboard support,
 * focus trapping, click-outside-to-close, and viewport resize cleanup.
 */
(function () {
  'use strict';

  function init() {
    var toggle = document.querySelector('.menu-toggle');
    var nav = document.querySelector('.site-nav');
    if (!toggle || !nav) return;

    /**
     * Check if mobile nav is currently open.
     */
    function isOpen() {
      return nav.getAttribute('data-open') === 'true';
    }

    /**
     * Close the mobile menu and return focus to toggle.
     */
    function closeMenu() {
      nav.setAttribute('data-open', 'false');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.focus();
    }

    /**
     * Open the mobile menu and focus first link.
     */
    function openMenu() {
      nav.setAttribute('data-open', 'true');
      toggle.setAttribute('aria-expanded', 'true');
      var firstLink = nav.querySelector('a');
      if (firstLink) firstLink.focus();
    }

    /* Toggle on click */
    toggle.addEventListener('click', function () {
      if (isOpen()) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    /* Close on Escape */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen()) {
        closeMenu();
      }
    });

    /* Focus trap: Tab cycles within nav when open */
    nav.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab' || !isOpen()) return;

      var focusable = nav.querySelectorAll('a[href], button, [tabindex]:not([tabindex="-1"])');
      if (!focusable.length) return;

      var first = focusable[0];
      var last = focusable[focusable.length - 1];

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    });

    /* Close on click outside nav and toggle */
    document.addEventListener('click', function (e) {
      if (isOpen() && !nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.setAttribute('data-open', 'false');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });

    /* Reset on resize past mobile breakpoint */
    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if (window.innerWidth > 991 && isOpen()) {
          nav.setAttribute('data-open', 'false');
          toggle.setAttribute('aria-expanded', 'false');
        }
      }, 150);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
