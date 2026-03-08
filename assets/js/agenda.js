/**
 * Agenda — Frontend Interactivity
 *
 * Handles:
 * - Disclosure/accordion for session details
 * - Lightweight filtering by day/room/track
 *
 * Vanilla JS, no dependencies, deferred.
 *
 * @package SenderSymposium
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-agenda-root]');
  if (!root) return;

  /* -----------------------------------------------------------------------
     Disclosure toggles (session detail expand/collapse)
     ----------------------------------------------------------------------- */
  root.addEventListener('click', function (e) {
    var btn = e.target.closest('.ss-agenda__card-toggle');
    if (!btn) return;

    var panelId = btn.getAttribute('aria-controls');
    var panel = panelId ? document.getElementById(panelId) : null;
    if (!panel) return;

    var expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    panel.hidden = expanded;
  });

  /* Keyboard: Enter and Space already work on <button>, but ensure Escape
     closes an open panel and returns focus to the toggle. */
  root.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;

    var panel = e.target.closest('.ss-agenda__card-details');
    if (!panel) return;

    var btn = root.querySelector('[aria-controls="' + panel.id + '"]');
    if (btn) {
      btn.setAttribute('aria-expanded', 'false');
      panel.hidden = true;
      btn.focus();
    }
  });

  /* -----------------------------------------------------------------------
     Filters
     ----------------------------------------------------------------------- */
  var filters = root.querySelectorAll('[data-filter]');
  if (!filters.length) return;

  function applyFilters() {
    var dayVal = '';
    var roomVal = '';
    var trackVal = '';

    filters.forEach(function (sel) {
      var type = sel.getAttribute('data-filter');
      if (type === 'day') dayVal = sel.value;
      if (type === 'room') roomVal = sel.value;
      if (type === 'track') trackVal = sel.value;
    });

    /* Day sections */
    var daySections = root.querySelectorAll('.ss-agenda__day');
    daySections.forEach(function (section) {
      if (dayVal && section.getAttribute('data-day') !== dayVal) {
        section.hidden = true;
      } else {
        section.hidden = false;
      }
    });

    /* Cards within visible days */
    var cards = root.querySelectorAll('.ss-agenda__card');
    cards.forEach(function (card) {
      var cardRoom = card.getAttribute('data-room') || '';
      var cardTrack = card.getAttribute('data-track') || '';
      var matchRoom = !roomVal || cardRoom === roomVal;
      var matchTrack = !trackVal || cardTrack === trackVal;
      card.hidden = !(matchRoom && matchTrack);
    });

    /* Hide time slots where all cards are hidden */
    var slots = root.querySelectorAll('.ss-agenda__time-slot');
    slots.forEach(function (slot) {
      var visibleCards = slot.querySelectorAll('.ss-agenda__card:not([hidden])');
      slot.hidden = visibleCards.length === 0;
    });

    /* Hide day sections where all slots are hidden */
    daySections.forEach(function (section) {
      if (section.hidden) return;
      var visibleSlots = section.querySelectorAll('.ss-agenda__time-slot:not([hidden])');
      if (visibleSlots.length === 0) {
        section.hidden = true;
      }
    });
  }

  filters.forEach(function (sel) {
    sel.addEventListener('change', applyFilters);
  });
})();
