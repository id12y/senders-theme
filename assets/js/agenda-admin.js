/**
 * Agenda — Admin Interactivity
 *
 * Handles:
 * - Add/remove participant rows
 * - AJAX delete with confirmation
 *
 * Vanilla JS, no dependencies.
 *
 * @package SenderSymposium
 */
(function () {
  'use strict';

  /* -----------------------------------------------------------------------
     Participant row management
     ----------------------------------------------------------------------- */
  var addBtn = document.getElementById('ss-add-participant');
  var list = document.getElementById('ss-participants-list');
  var template = document.getElementById('ss-participant-template');

  if (addBtn && list && template) {
    var nextIndex = list.querySelectorAll('.ss-participant-row').length;

    addBtn.addEventListener('click', function () {
      var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
      var wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      var row = wrapper.firstElementChild;
      list.appendChild(row);
      nextIndex++;
      /* Focus the speaker select in the new row */
      var sel = row.querySelector('select');
      if (sel) sel.focus();
    });

    /* Remove participant row (delegated) */
    list.addEventListener('click', function (e) {
      var btn = e.target.closest('.ss-remove-participant');
      if (!btn) return;
      var row = btn.closest('.ss-participant-row');
      if (row) row.remove();
    });
  }

  /* -----------------------------------------------------------------------
     AJAX delete (optional progressive enhancement)
     ----------------------------------------------------------------------- */
  var cfg = window.ssAgendaAdmin;
  if (!cfg) return;

  document.querySelectorAll('.ss-agenda-table').forEach(function (table) {
    table.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-ajax-delete]');
      if (!btn) return;

      e.preventDefault();
      var id = btn.getAttribute('data-ajax-delete');
      if (!confirm('Delete this session?')) return;

      var row = btn.closest('tr');
      var fd = new FormData();
      fd.append('action', 'ss_delete_session_ajax');
      fd.append('nonce', cfg.nonce);
      fd.append('id', id);

      fetch(cfg.ajaxUrl, { method: 'POST', body: fd })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success && row) row.remove();
        });
    });
  });

  /* -----------------------------------------------------------------------
     Bulk delete
     ----------------------------------------------------------------------- */
  var bulkBtn = document.getElementById('ss-bulk-delete-btn');
  if (!bulkBtn) return;

  function updateBulkBtn() {
    var checked = document.querySelectorAll('.ss-session-check:checked');
    if (checked.length > 0) {
      bulkBtn.style.display = '';
      bulkBtn.textContent = 'Delete Selected (' + checked.length + ')';
    } else {
      bulkBtn.style.display = 'none';
    }
  }

  /* Select-all toggles within the same table */
  document.addEventListener('change', function (e) {
    if (e.target.classList.contains('ss-select-all')) {
      var table = e.target.closest('table');
      if (!table) return;
      var boxes = table.querySelectorAll('.ss-session-check');
      for (var i = 0; i < boxes.length; i++) {
        boxes[i].checked = e.target.checked;
      }
      updateBulkBtn();
    } else if (e.target.classList.contains('ss-session-check')) {
      updateBulkBtn();
    }
  });

  /* Bulk delete click */
  bulkBtn.addEventListener('click', function () {
    var checked = document.querySelectorAll('.ss-session-check:checked');
    if (!checked.length) return;

    var ids = [];
    for (var i = 0; i < checked.length; i++) {
      ids.push(checked[i].value);
    }

    if (!confirm('Delete ' + ids.length + ' session(s)?')) return;

    var fd = new FormData();
    fd.append('action', 'ss_bulk_delete_sessions');
    fd.append('nonce', cfg.nonce);
    fd.append('ids', ids.join(','));

    fetch(cfg.ajaxUrl, { method: 'POST', body: fd })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success) {
          for (var j = 0; j < checked.length; j++) {
            var row = checked[j].closest('tr');
            if (row) row.remove();
          }
          /* Uncheck select-all boxes */
          var selectAlls = document.querySelectorAll('.ss-select-all');
          for (var k = 0; k < selectAlls.length; k++) {
            selectAlls[k].checked = false;
          }
          updateBulkBtn();
        }
      });
  });
})();
