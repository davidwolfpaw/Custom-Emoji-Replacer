jQuery(document).ready(function ($) {
	// Initialize the counter to keep track of the number of emoji replacer items
	var addButton = $('#add-emoji-replacer');
	var counter = $('.emoji-replacer-item').length;

	// Event handler for adding new emoji replacement fields
	if (addButton.length) {
		addButton.on('click', function () {
			var container = $('#emoji-replacer-container');
			var newItem = $('<div>', {
				class: 'emoji-replacer-item'
			});

			// Create the HTML for the new emoji replacement fields
			newItem.html(
				'<input type="text" name="emoji_replacer_data[' + counter + '][emoji]" placeholder="' + emojiReplacer.emojiPlaceholder + '">' +
				'<input type="hidden" class="emoji-replacer-image-url" name="emoji_replacer_data[' + counter + '][image_url]">' +
				'<button type="button" class="button select-image">' + emojiReplacer.selectImageButton + '</button>' +
				'<button type="button" class="button remove-emoji">' + emojiReplacer.deleteButton + '</button>' +
				'<img src="" alt="" style="display:none;">' // Placeholder for the image
			);
			container.append(newItem);
			counter++;
		});
	}

	// Event handler for selecting images from the media library
	$('#emoji-replacer-container').on('click', '.select-image', function (e) {
		e.preventDefault();
		var button = $(this);
		var input = button.prev('.emoji-replacer-image-url');

		// Open the WordPress media library frame
		var frame = wp.media({
			title: emojiReplacer.mediaTitle,
			button: {
				text: emojiReplacer.mediaButton
			},
			multiple: false
		});

		// Handle the selection of an image from the media library
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			input.val(attachment.url);

			// Update the image preview or show the existing placeholder image
			var img = button.nextAll('img');
			img.attr('src', attachment.url).show();
		});

		frame.open();
	});

	// Event handler for removing emoji replacement fields
	$('#emoji-replacer-container').on('click', '.remove-emoji', function (e) {
		e.preventDefault();
		$(this).closest('.emoji-replacer-item').remove();
	});
});
