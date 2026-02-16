/**
 * Admin Media Picker
 *
 * Turns any `.ss-media-picker` container into a WordPress media library picker.
 *
 * Expected HTML:
 *   <div class="ss-media-picker">
 *     <input type="hidden" name="..." value="0" class="ss-media-picker__id">
 *     <div class="ss-media-picker__preview"></div>
 *     <button type="button" class="button ss-media-picker__choose">Choose Image</button>
 *     <button type="button" class="button-link ss-media-picker__remove" style="display:none;">Remove</button>
 *   </div>
 *
 * @package SenderSymposium
 */
(function ($) {
	'use strict';

	if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
		return;
	}

	$(document).on('click', '.ss-media-picker__choose', function (e) {
		e.preventDefault();

		var $wrap   = $(this).closest('.ss-media-picker');
		var $input  = $wrap.find('.ss-media-picker__id');
		var $preview = $wrap.find('.ss-media-picker__preview');
		var $remove = $wrap.find('.ss-media-picker__remove');

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

		var $wrap   = $(this).closest('.ss-media-picker');
		var $input  = $wrap.find('.ss-media-picker__id');
		var $preview = $wrap.find('.ss-media-picker__preview');

		$input.val('0');
		$preview.html('');
		$(this).hide();
	});

	/* On page load, show previews for any fields that already have an ID */
	$(function () {
		$('.ss-media-picker').each(function () {
			var $wrap   = $(this);
			var $input  = $wrap.find('.ss-media-picker__id');
			var $preview = $wrap.find('.ss-media-picker__preview');
			var $remove = $wrap.find('.ss-media-picker__remove');
			var val     = parseInt($input.val(), 10);

			if (val > 0) {
				/* Fetch attachment thumbnail via REST API */
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
