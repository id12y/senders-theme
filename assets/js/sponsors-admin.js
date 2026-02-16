/**
 * Sponsors Admin — drag-and-drop reorder, AJAX toggles, delete, media picker, dismiss.
 */
(function () {
  'use strict';

  var tbody = document.getElementById('ss-sponsors-tbody');

  /* ─── Drag-and-drop reorder ─── */
  if (tbody) {
    var dragRow = null;

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
  }

  function saveOrder() {
    if (!tbody) return;
    var rows = tbody.querySelectorAll('tr[data-id]');
    var ids = [];
    for (var i = 0; i < rows.length; i++) {
      ids.push(rows[i].getAttribute('data-id'));
    }
    fetch(ssSponsorsAdmin.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=ss_reorder_sponsors&nonce=' + encodeURIComponent(ssSponsorsAdmin.nonce) + '&order=' + ids.join(',')
    });
  }

  /* ─── Delegated click handler ─── */
  document.addEventListener('click', function (e) {
    var statusBtn  = e.target.closest('.ss-status-toggle');
    var featBtn    = e.target.closest('.ss-featured-toggle');
    var delBtn     = e.target.closest('.ss-delete-sponsor');
    var dismissBtn = e.target.closest('.ss-dismiss-dark-reco');
    var mediaBtn   = e.target.closest('.ss-sponsor-media-choose');
    var removeBtn  = e.target.closest('.ss-sponsor-media-remove');

    if (statusBtn) {
      toggleField(statusBtn, 'status');
    } else if (featBtn) {
      toggleField(featBtn, 'featured');
    } else if (delBtn) {
      deleteSponsor(delBtn);
    } else if (dismissBtn) {
      dismissDarkReco(dismissBtn);
    } else if (mediaBtn) {
      openMediaPicker(mediaBtn);
    } else if (removeBtn) {
      removeMedia(removeBtn);
    }
  });

  function toggleField(btn, field) {
    var id = btn.getAttribute('data-id');
    fetch(ssSponsorsAdmin.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=ss_toggle_sponsor&nonce=' + encodeURIComponent(ssSponsorsAdmin.nonce) + '&id=' + encodeURIComponent(id) + '&field=' + field
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) return;
      if (field === 'status') {
        var conf = data.data.status === 'confirmed';
        btn.textContent = conf ? 'Confirmed' : 'Unconfirmed';
        btn.className = 'ss-status-toggle ss-status--' + data.data.status;
      } else {
        var active = data.data.featured;
        btn.classList.toggle('ss-featured--active', active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      }
    });
  }

  function deleteSponsor(btn) {
    if (!confirm('Delete this sponsor?')) return;
    var id = btn.getAttribute('data-id');
    var row = btn.closest('tr');
    fetch(ssSponsorsAdmin.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=ss_delete_sponsor_ajax&nonce=' + encodeURIComponent(ssSponsorsAdmin.nonce) + '&id=' + encodeURIComponent(id)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success && row) row.remove();
    });
  }

  function dismissDarkReco(btn) {
    var id = btn.getAttribute('data-id');
    var wrap = btn.closest('.ss-dark-logo-reco');
    fetch(ssSponsorsAdmin.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=ss_dismiss_dark_logo_reco&nonce=' + encodeURIComponent(ssSponsorsAdmin.nonce) + '&id=' + encodeURIComponent(id)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success && wrap) {
        wrap.innerHTML = '—';
        wrap.className = 'description';
      }
    });
  }

  /* ─── WP Media Library picker ─── */
  function openMediaPicker(btn) {
    var field   = btn.closest('.ss-sponsor-media-field');
    var idInput = field.querySelector('.ss-sponsor-media-id');
    var preview = field.querySelector('.ss-sponsor-media-preview');
    var removeB = field.querySelector('.ss-sponsor-media-remove');

    var frame = wp.media({
      title: 'Choose Logo',
      button: { text: 'Use this image' },
      multiple: false,
      library: { type: 'image' }
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      idInput.value = attachment.id;
      var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
      preview.innerHTML = '<img src="' + url + '" alt="" style="max-height:48px;max-width:120px;object-fit:contain;" />';
      if (removeB) removeB.style.display = '';
    });

    frame.open();
  }

  function removeMedia(btn) {
    var field   = btn.closest('.ss-sponsor-media-field');
    var idInput = field.querySelector('.ss-sponsor-media-id');
    var preview = field.querySelector('.ss-sponsor-media-preview');
    idInput.value = '0';
    preview.innerHTML = '';
    btn.style.display = 'none';
  }
})();
