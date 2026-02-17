/**
 * Speakers Admin — drag-and-drop reorder, AJAX toggles, delete, media picker.
 */
(function () {
  'use strict';

  var tbody = document.getElementById('ss-speakers-tbody');

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
    fetch(ssSpeakersAdmin.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=ss_reorder_speakers&nonce=' + encodeURIComponent(ssSpeakersAdmin.nonce) + '&order=' + ids.join(',')
    });
  }

  /* ─── Delegated click handler ─── */
  document.addEventListener('click', function (e) {
    var statusBtn = e.target.closest('.ss-status-toggle');
    var featBtn   = e.target.closest('.ss-featured-toggle');
    var delBtn    = e.target.closest('.ss-delete-speaker');
    var mediaBtn  = e.target.closest('.ss-speaker-media-choose');
    var removeBtn = e.target.closest('.ss-speaker-media-remove');

    if (statusBtn) {
      toggleField(statusBtn, 'status');
    } else if (featBtn) {
      toggleField(featBtn, 'featured');
    } else if (delBtn) {
      deleteSpeaker(delBtn);
    } else if (mediaBtn) {
      openMediaPicker(mediaBtn);
    } else if (removeBtn) {
      removeMedia(removeBtn);
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

  /* ─── WP Media Library picker ─── */
  function openMediaPicker(btn) {
    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') return;

    var field   = btn.closest('.ss-speaker-media-field');
    var idInput = field.querySelector('.ss-speaker-media-id');
    var preview = field.querySelector('.ss-speaker-media-preview');
    var removeB = field.querySelector('.ss-speaker-media-remove');

    var frame = wp.media({
      title: 'Choose Speaker Image',
      button: { text: 'Use this image' },
      multiple: false,
      library: { type: 'image' }
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      idInput.value = attachment.id;
      var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
      preview.innerHTML = '<img src="' + url + '" alt="" style="max-height:80px;max-width:150px;object-fit:cover;border-radius:4px;" />';
      if (removeB) removeB.style.display = '';
    });

    frame.open();
  }

  function removeMedia(btn) {
    var field   = btn.closest('.ss-speaker-media-field');
    var idInput = field.querySelector('.ss-speaker-media-id');
    var preview = field.querySelector('.ss-speaker-media-preview');
    idInput.value = '0';
    preview.innerHTML = '';
    btn.style.display = 'none';
  }
})();
