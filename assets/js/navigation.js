/**
 * Sender Symposium — Accessible Navigation
 *
 * Handles mobile menu toggle with ARIA states, keyboard support,
 * focus trapping, click-outside-to-close, viewport resize cleanup,
 * and dropdown sub-menu toggles for nested menu items.
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
     * Check if we're in mobile breakpoint.
     */
    function isMobile() {
      return window.innerWidth <= 991;
    }

    /**
     * Close the mobile menu and return focus to toggle.
     */
    function closeMenu() {
      nav.setAttribute('data-open', 'false');
      toggle.setAttribute('aria-expanded', 'false');
      /* Close all open dropdowns */
      var openDropdowns = nav.querySelectorAll('.ss-dropdown-open');
      for (var i = 0; i < openDropdowns.length; i++) {
        openDropdowns[i].classList.remove('ss-dropdown-open');
      }
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
        /* Clean up mobile dropdown classes on desktop */
        if (window.innerWidth > 991) {
          var openDropdowns = nav.querySelectorAll('.ss-dropdown-open');
          for (var i = 0; i < openDropdowns.length; i++) {
            openDropdowns[i].classList.remove('ss-dropdown-open');
          }
        }
      }, 150);
    });

    /* ── Dropdown sub-menus ── */

    var parentItems = nav.querySelectorAll('.menu-item-has-children');
    for (var i = 0; i < parentItems.length; i++) {
      setupDropdown(parentItems[i]);
    }

    function setupDropdown(item) {
      var link = item.querySelector(':scope > a');
      if (!link) return;

      /* On mobile: toggle dropdown on click of parent link */
      link.addEventListener('click', function (e) {
        if (!isMobile()) return;

        /* If this is a "#" link or same-page link, toggle dropdown */
        var href = link.getAttribute('href');
        if (!href || href === '#' || href === '') {
          e.preventDefault();
          item.classList.toggle('ss-dropdown-open');
          return;
        }

        /* If the link has a real URL and dropdown is closed, show dropdown first */
        if (!item.classList.contains('ss-dropdown-open')) {
          e.preventDefault();
          /* Close sibling dropdowns */
          var siblings = item.parentNode.querySelectorAll('.ss-dropdown-open');
          for (var j = 0; j < siblings.length; j++) {
            if (siblings[j] !== item) {
              siblings[j].classList.remove('ss-dropdown-open');
            }
          }
          item.classList.add('ss-dropdown-open');
        }
        /* Second tap navigates to the link's URL */
      });

      /* Keyboard: Enter/Space toggles dropdown on mobile */
      link.addEventListener('keydown', function (e) {
        if (!isMobile()) return;
        if (e.key === 'Enter' || e.key === ' ') {
          var href = link.getAttribute('href');
          if (!href || href === '#' || href === '') {
            e.preventDefault();
            item.classList.toggle('ss-dropdown-open');
          }
        }
      });

      /* Desktop keyboard: Arrow down opens sub-menu */
      link.addEventListener('keydown', function (e) {
        if (isMobile()) return;
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          item.classList.add('ss-dropdown-open');
          var firstSubLink = item.querySelector('.sub-menu a');
          if (firstSubLink) firstSubLink.focus();
        }
      });

      /* Sub-menu keyboard: Escape closes dropdown */
      var subMenu = item.querySelector('.sub-menu');
      if (subMenu) {
        subMenu.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            e.stopPropagation();
            item.classList.remove('ss-dropdown-open');
            link.focus();
          }
        });
      }
    }

    /* Close dropdowns when clicking outside (desktop) */
    document.addEventListener('click', function (e) {
      if (isMobile()) return;
      var openDropdowns = nav.querySelectorAll('.ss-dropdown-open');
      for (var i = 0; i < openDropdowns.length; i++) {
        if (!openDropdowns[i].contains(e.target)) {
          openDropdowns[i].classList.remove('ss-dropdown-open');
        }
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
