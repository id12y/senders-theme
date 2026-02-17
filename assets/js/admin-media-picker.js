/**
 * Admin Media Picker
 *
 * Turns any `.ss-media-picker` container into a WordPress media library picker.
 * Also supports gallery mode (multi-select) via `.ss-gallery-picker__choose`.
 *
 * @package SenderSymposium
 */
(function ($) {
	'use strict';

	if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
		return;
	}

	/* ── Single Image Picker ── */

	$(document).on('click', '.ss-media-picker__choose', function (e) {
		e.preventDefault();

		var $wrap    = $(this).closest('.ss-media-picker');
		var $input   = $wrap.find('.ss-media-picker__id');
		var $preview = $wrap.find('.ss-media-picker__preview');
		var $remove  = $wrap.find('.ss-media-picker__remove');

		var frame = wp.media({
			title: $wrap.data('title') || 'Select Image',
			button: { text: $wrap.data('button') || 'Use this image' },
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			var thumb = attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;

			$input.val(attachment.id);
			$preview.html('<img src="' + thumb + '" style="max-width:150px;max-height:80px;display:block;margin-bottom:8px;border-radius:4px;">');
			$remove.show();
		});

		frame.open();
	});

	$(document).on('click', '.ss-media-picker__remove', function (e) {
		e.preventDefault();

		var $wrap    = $(this).closest('.ss-media-picker');
		var $input   = $wrap.find('.ss-media-picker__id');
		var $preview = $wrap.find('.ss-media-picker__preview');

		$input.val('0');
		$preview.html('');
		$(this).hide();
	});

	/* ── Gallery Picker (multi-select) ── */

	$(document).on('click', '.ss-gallery-picker__choose', function (e) {
		e.preventDefault();

		var $wrap    = $(this).closest('.ss-media-picker');
		var targetField = $wrap.find('.ss-media-picker__id').data('target-field');
		var $textInput  = $('input[name="' + targetField + '"]');
		var $preview = $wrap.find('.ss-media-picker__preview');

		var frame = wp.media({
			title: $wrap.data('title') || 'Select Images',
			button: { text: $wrap.data('button') || 'Add to gallery' },
			multiple: true,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var selection = frame.state().get('selection');
			var ids = [];
			var html = '';

			selection.each(function (attachment) {
				var att = attachment.toJSON();
				ids.push(att.id);
				var thumb = att.sizes && att.sizes.thumbnail
					? att.sizes.thumbnail.url
					: att.url;
				html += '<img src="' + thumb + '" style="width:60px;height:60px;object-fit:cover;border-radius:4px;">';
			});

			if ($textInput.length) {
				$textInput.val(ids.join(', '));
			}
			$preview.html(html);
		});

		frame.open();
	});

	/* ── Font File Upload Picker ── */

	$(document).on('click', '.ss-font-upload__choose', function (e) {
		e.preventDefault();

		var $wrap  = $(this).closest('.ss-font-upload');
		var $input = $wrap.find('.ss-font-upload__url');
		var $name  = $wrap.find('.ss-font-upload__filename');

		var frame = wp.media({
			title: 'Upload or Select Font File',
			button: { text: 'Use this font' },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$input.val(attachment.url);
			$name.text(attachment.filename || '').show();
		});

		frame.open();
	});

	/* ── On page load: show previews for fields with existing IDs ── */

	$(function () {
		$('.ss-media-picker').each(function () {
			var $wrap    = $(this);
			var isGallery = $wrap.data('gallery');
			var $input   = $wrap.find('.ss-media-picker__id');
			var $preview = $wrap.find('.ss-media-picker__preview');
			var $remove  = $wrap.find('.ss-media-picker__remove');

			if (isGallery) {
				/* Gallery previews are rendered server-side */
				return;
			}

			var val = parseInt($input.val(), 10);
			if (val > 0) {
				wp.media.attachment(val).fetch().then(function () {
					var att = wp.media.attachment(val).toJSON();
					var thumb = att.sizes && att.sizes.thumbnail
						? att.sizes.thumbnail.url
						: att.url;
					$preview.html('<img src="' + thumb + '" style="max-width:150px;max-height:80px;display:block;margin-bottom:8px;border-radius:4px;">');
					$remove.show();
				});
			}
		});
	});

})(jQuery);
