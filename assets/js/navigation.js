/**
 * Sender Symposium — Accessible Navigation
 *
 * Handles mobile menu toggle with ARIA states, keyboard support,
 * focus trapping, click-outside-to-close, viewport resize cleanup,
 * and dropdown sub-menu toggles for nested menu items (up to 2 levels
 * of submenus on desktop, 1 level accordion on mobile).
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
        syncDropdownAria(openDropdowns[i]);
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
        closeMenu();
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
            syncDropdownAria(openDropdowns[i]);
          }
        }
      }, 150);
    });

    /* ── Dismiss hover-triggered dropdowns on Escape (WCAG 1.4.13) ── */
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape' || isMobile()) return;
      var hoveredParents = nav.querySelectorAll('.menu-item-has-children:hover');
      for (var i = 0; i < hoveredParents.length; i++) {
        hoveredParents[i].setAttribute('data-hover-dismissed', '');
        hoveredParents[i].classList.remove('ss-dropdown-open');
        syncDropdownAria(hoveredParents[i]);
      }
    });

    nav.addEventListener('mouseenter', function (e) {
      var item = e.target.closest('.menu-item-has-children[data-hover-dismissed]');
      if (item) item.removeAttribute('data-hover-dismissed');
    }, true);

    /* ── Dropdown sub-menus ── */

    /**
     * Determine if an item is a top-level parent (direct child of .site-nav__list).
     */
    function isTopLevel(item) {
      var parent = item.parentNode;
      return parent && parent.classList.contains('site-nav__list');
    }

    var parentItems = nav.querySelectorAll('.menu-item-has-children');
    for (var i = 0; i < parentItems.length; i++) {
      /* Initialize ARIA attributes on parent links */
      var parentLink = parentItems[i].querySelector(':scope > a');
      if (parentLink) {
        parentLink.setAttribute('aria-haspopup', 'true');
        parentLink.setAttribute('aria-expanded', 'false');
      }
      setupDropdown(parentItems[i]);
    }

    /**
     * Sync aria-expanded on a dropdown parent link.
     */
    function syncDropdownAria(item) {
      var link = item.querySelector(':scope > a');
      if (link) {
        link.setAttribute('aria-expanded', item.classList.contains('ss-dropdown-open') ? 'true' : 'false');
      }
    }

    function setupDropdown(item) {
      var link = item.querySelector(':scope > a');
      if (!link) return;

      var topLevel = isTopLevel(item);

      /* On mobile: toggle dropdown on click of parent link (top-level only) */
      link.addEventListener('click', function (e) {
        if (!isMobile()) return;

        /* On mobile, only top-level parents get toggle behavior.
           Sub-submenu parents are always visible — no toggle needed. */
        if (!topLevel) return;

        /* If this is a "#" link or same-page link, toggle dropdown */
        var href = link.getAttribute('href');
        if (!href || href === '#' || href === '') {
          e.preventDefault();
          item.classList.toggle('ss-dropdown-open');
          syncDropdownAria(item);
          return;
        }

        /* If the link has a real URL and dropdown is closed, show dropdown first */
        if (!item.classList.contains('ss-dropdown-open')) {
          e.preventDefault();
          /* Close sibling dropdowns */
          var siblings = item.parentNode.querySelectorAll(':scope > .ss-dropdown-open');
          for (var j = 0; j < siblings.length; j++) {
            if (siblings[j] !== item) {
              siblings[j].classList.remove('ss-dropdown-open');
              syncDropdownAria(siblings[j]);
            }
          }
          item.classList.add('ss-dropdown-open');
          syncDropdownAria(item);
        }
        /* Second tap navigates to the link's URL */
      });

      /* Keyboard: Enter/Space toggles dropdown on mobile (top-level only) */
      link.addEventListener('keydown', function (e) {
        if (!isMobile()) return;
        if (!topLevel) return;
        if (e.key === 'Enter' || e.key === ' ') {
          var href = link.getAttribute('href');
          if (!href || href === '#' || href === '') {
            e.preventDefault();
            item.classList.toggle('ss-dropdown-open');
            syncDropdownAria(item);
          }
        }
      });

      /* Desktop keyboard: Arrow down opens sub-menu for top-level items */
      link.addEventListener('keydown', function (e) {
        if (isMobile()) return;

        if (topLevel && e.key === 'ArrowDown') {
          e.preventDefault();
          item.classList.add('ss-dropdown-open');
          syncDropdownAria(item);
          var firstSubLink = item.querySelector(':scope > .sub-menu > li > a');
          if (firstSubLink) firstSubLink.focus();
        }

        /* Desktop keyboard: ArrowRight opens sub-submenu for nested parents */
        if (!topLevel && e.key === 'ArrowRight') {
          e.preventDefault();
          item.classList.add('ss-dropdown-open');
          syncDropdownAria(item);
          var firstSubSubLink = item.querySelector(':scope > .sub-menu > li > a');
          if (firstSubSubLink) firstSubSubLink.focus();
        }

        /* Desktop keyboard: ArrowLeft closes sub-submenu and returns focus to parent */
        if (!topLevel && e.key === 'ArrowLeft') {
          e.preventDefault();
          item.classList.remove('ss-dropdown-open');
          syncDropdownAria(item);
          link.focus();
        }
      });

      /* Sub-menu keyboard: Escape closes dropdown */
      var subMenu = item.querySelector(':scope > .sub-menu');
      if (subMenu) {
        subMenu.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            e.stopPropagation();
            item.classList.remove('ss-dropdown-open');
            syncDropdownAria(item);
            link.focus();
          }

          /* Arrow navigation within sub-menu */
          if (!isMobile() && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
            var links = subMenu.querySelectorAll(':scope > li > a');
            if (!links.length) return;

            var currentIdx = -1;
            for (var k = 0; k < links.length; k++) {
              if (links[k] === document.activeElement) {
                currentIdx = k;
                break;
              }
            }

            if (currentIdx === -1) return;

            e.preventDefault();
            if (e.key === 'ArrowDown' && currentIdx < links.length - 1) {
              links[currentIdx + 1].focus();
            } else if (e.key === 'ArrowUp' && currentIdx > 0) {
              links[currentIdx - 1].focus();
            } else if (e.key === 'ArrowUp' && currentIdx === 0) {
              /* At first item, go up to parent link */
              link.focus();
            }
          }

          /* ArrowLeft from sub-submenu items returns to parent sub-menu */
          if (!isMobile() && e.key === 'ArrowLeft' && !topLevel) {
            e.stopPropagation();
            item.classList.remove('ss-dropdown-open');
            syncDropdownAria(item);
            link.focus();
          }

          /* ArrowRight on sub-menu item that has children: open sub-submenu */
          if (!isMobile() && e.key === 'ArrowRight') {
            var focused = document.activeElement;
            if (!focused) return;
            var parentLi = focused.closest('.menu-item-has-children');
            if (parentLi && parentLi !== item && subMenu.contains(parentLi)) {
              e.stopPropagation();
              parentLi.classList.add('ss-dropdown-open');
              syncDropdownAria(parentLi);
              var firstChild = parentLi.querySelector(':scope > .sub-menu > li > a');
              if (firstChild) firstChild.focus();
            }
          }
        });
      }

      /* Desktop: close sub-submenu on click of parent link (for navigation) */
      if (!topLevel) {
        link.addEventListener('click', function (e) {
          if (isMobile()) return;

          var href = link.getAttribute('href');
          if (!href || href === '#' || href === '') {
            e.preventDefault();
            item.classList.toggle('ss-dropdown-open');
            syncDropdownAria(item);
            if (item.classList.contains('ss-dropdown-open')) {
              var firstChild = item.querySelector(':scope > .sub-menu > li > a');
              if (firstChild) firstChild.focus();
            }
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
          syncDropdownAria(openDropdowns[i]);
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
