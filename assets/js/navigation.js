/**
 * Sender Symposium — Accessible Navigation
 *
 * Handles mobile menu toggle with ARIA states and keyboard support.
 */
(function () {
  'use strict';

  function init() {
    var toggle = document.querySelector('.menu-toggle');
    var nav = document.querySelector('.site-nav');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function () {
      var isOpen = nav.getAttribute('data-open') === 'true';
      var nextState = !isOpen;
      nav.setAttribute('data-open', nextState ? 'true' : 'false');
      toggle.setAttribute('aria-expanded', nextState ? 'true' : 'false');

      if (nextState) {
        /* Focus first link when opening */
        var firstLink = nav.querySelector('.site-nav__link');
        if (firstLink) firstLink.focus();
      }
    });

    /* Close on Escape */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.getAttribute('data-open') === 'true') {
        nav.setAttribute('data-open', 'false');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
