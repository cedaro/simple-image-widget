var SimpleImageWidget;

(function($) {
	SimpleImageWidget.updateSizeDropdownOptions = function( field, sizes ) {
		var currentValue = field.val(),
			options;

		if ( sizes ) {
			$.each( sizes, function( key, size ) {
				var name;

				if ( key in SimpleImageWidget.imageSizeNames ) {
					name = SimpleImageWidget.imageSizeNames[ key ];
				}

				options += '<option value="' + key + '">' + name + ' (' + size.width + '&times;' + size.height + ')</option>';
			});
		}

		if ( ! options ) {
			name = SimpleImageWidget.imageSizeNames['full'] || SimpleImageWidget.fullSizeLabel;
			options = '<option value="full">' + name + '</option>';
		}

		// Try to maintain the previously selected size if it still exists.
		field.html( options ).val( currentValue ).removeAttr('disabled');
	};
})(jQuery);

/**
 * Media control frame popup.
 */
jQuery(function($) {
	var Attachment = wp.media.model.Attachment,
		$control, $controlTarget, mediaControl;

	mediaControl = {
		// Initialize a new media manager or return an existing frame.
		// @see wp.media.featuredImage.frame()
		frame: function() {
			if ( this._frame )
				return this._frame;

			this._frame = wp.media({
				title: $control.data('title') || SimpleImageWidget.frameTitle,
				library: {
					type: $control.data('media-type') || 'image'
				},
				button: {
					text: $control.data('update-text') || SimpleImageWidget.frameUpdateText
				},
				multiple: $control.data( 'select-multiple' ) || false
			});

			this._frame.on( 'open', this.updateLibrarySelection ).state('library').on( 'select', this.select );

			return this._frame;
		},

		// Update the control when an image is selected from the media library.
		select: function() {
			var selection = this.get('selection'),
				returnProperty = $control.data('return-property') || 'id';

			// Insert the selected attachment ids into the target element.
			if ( $controlTarget.length ) {
				$controlTarget.val( selection.pluck( returnProperty ) );
			}

			// Trigger an event on the control to allow custom updates.
			$control.trigger( 'selectionChange.simpleimagewidget', [ selection ] );
		},

		// Update the selected image in the media library based on the image in the control.
		updateLibrarySelection: function() {
			var selection = this.get('library').get('selection'),
				attachment, selectedIds;

			if ( $controlTarget.length ) {
				selectedIds = $controlTarget.val();
				if ( selectedIds && '' !== selectedIds && -1 !== selectedIds && '0' !== selectedIds ) {
					attachment = Attachment.get( selectedIds );
					attachment.fetch();
				}
			}

			selection.reset( attachment ? [ attachment ] : [] );
		},

		init: function() {
			$('#wpbody').on('click', '.simple-image-widget-control-choose', function(e) {
				var targetSelector;

				e.preventDefault();

				$control = $(this).closest('.simple-image-widget-control');

				targetSelector = $control.data('target') || '.simple-image-widget-control-target';
				if ( 0 === targetSelector.indexOf('#') ) {
					// Context doesn't matter if the selector is an ID.
					$controlTarget = $( targetSelector );
				} else {
					// Search for other selectors within the context of the control.
					$controlTarget = $control.find( targetSelector );
				}

				mediaControl.frame().open();
			});
		}
	};

	mediaControl.init();
});