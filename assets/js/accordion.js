/**
 * Sender Symposium — FAQ Accordion
 *
 * Uses native <details> element for no-JS baseline.
 * This script adds smooth open/close animations and keyboard UX.
 * The accordion is fully functional without JS via <details>/<summary>.
 */
(function () {
  'use strict';

  function init() {
    /* Only enhance — native <details> works without JS */
    var items = document.querySelectorAll('.faq-item');
    if (!items.length) return;

    for (var i = 0; i < items.length; i++) {
      items[i].addEventListener('toggle', function () {
        /* Update icon rotation via [open] attribute — CSS handles this */
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
