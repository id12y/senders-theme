/**
 * Speakers Admin — drag-and-drop reorder, AJAX toggles, delete.
 */
(function () {
  'use strict';

  var tbody = document.getElementById('ss-speakers-tbody');
  if (!tbody) return;

  var dragRow = null;

  /* ─── Drag-and-drop reorder ─── */
  tbody.addEventListener('dragstart', function (e) {
    dragRow = e.target.closest('tr');
    if (!dragRow) return;
    dragRow.classList.add('ss-dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', '');
  });

  tbody.addEventListener('dragover', function (e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    var target = e.target.closest('tr');
    if (!target || target === dragRow) return;
    var rect = target.getBoundingClientRect();
    if (e.clientY < rect.top + rect.height / 2) {
      tbody.insertBefore(dragRow, target);
    } else {
      tbody.insertBefore(dragRow, target.nextSibling);
    }
  });

  tbody.addEventListener('dragend', function () {
    if (dragRow) {
      dragRow.classList.remove('ss-dragging');
      dragRow = null;
      saveOrder();
    }
  });

  function saveOrder() {
    var rows = tbody.querySelectorAll('tr[data-id]');
    var ids = [];
    for (var i = 0; i < rows.length; i++) {
      ids.push(rows[i].getAttribute('data-id'));
    }
    fetch(ssSpeakersAdmin.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=ss_reorder_speakers&nonce=' + encodeURIComponent(ssSpeakersAdmin.nonce) + '&order=' + ids.join(',')
    });
  }

  /* ─── Delegated click handler ─── */
  document.addEventListener('click', function (e) {
    var statusBtn = e.target.closest('.ss-status-toggle');
    var featBtn = e.target.closest('.ss-featured-toggle');
    var delBtn = e.target.closest('.ss-delete-speaker');

    if (statusBtn) {
      toggleField(statusBtn, 'status');
    } else if (featBtn) {
      toggleField(featBtn, 'featured');
    } else if (delBtn) {
      deleteSpeaker(delBtn);
    }
  });

  function toggleField(btn, field) {
    var id = btn.getAttribute('data-id');
    fetch(ssSpeakersAdmin.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=ss_toggle_speaker&nonce=' + encodeURIComponent(ssSpeakersAdmin.nonce) + '&id=' + encodeURIComponent(id) + '&field=' + field
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) return;
      if (field === 'status') {
        var pub = data.data.status === 'published';
        btn.textContent = pub ? 'Published' : 'Unconfirmed';
        btn.className = 'ss-status-toggle ss-status--' + data.data.status;
      } else {
        var active = data.data.featured;
        btn.classList.toggle('ss-featured--active', active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      }
    });
  }

  function deleteSpeaker(btn) {
    if (!confirm('Delete this speaker?')) return;
    var id = btn.getAttribute('data-id');
    var row = btn.closest('tr');
    fetch(ssSpeakersAdmin.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=ss_delete_speaker_ajax&nonce=' + encodeURIComponent(ssSpeakersAdmin.nonce) + '&id=' + encodeURIComponent(id)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success && row) row.remove();
    });
  }
})();
