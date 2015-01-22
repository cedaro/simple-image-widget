/*global _:false, ajaxurl:false, Backbone:false, wp:false */

window.SimpleImageWidget = window.SimpleImageWidget || {};

(function( window, $, _, Backbone, wp, undefined ) {
	'use strict';

	var Control, l10n,
		SimpleImageWidget = window.SimpleImageWidget,
		Attachment = wp.media.model.Attachment,
		finder = { view: {} },
		frames = [];

	// Link any localized strings.
	l10n = SimpleImageWidget.l10n = SimpleImageWidget.l10n || {};

	/**
	 * Control module object.
	 */
	Control = function( el, options ) {
		var defaults, selector, settings;

		this.$el = $( el );

		selector = this.$el.data( 'target' ) || '.simple-image-widget-control-target';
		if ( 0 === selector.indexOf( '#' ) ) {
			this.$target = $( selector );
		} else {
			// Search within the context of the control.
			this.$target = this.$el.find( selector );
		}

		defaults = {
			frame: {
				id: 'simple-image-widget',
				title: this.$el.data( 'title' ) || l10n.frameTitle,
				updateText: this.$el.data( 'update-text' ) || l10n.frameUpdateText,
				multiple: this.$el.data( 'select-multiple' ) || false
			},
			mediaType: this.$el.data( 'media-type' ) || 'image',
			returnProperty: this.$el.data( 'return-property' ) || 'id'
		};

		options = options || {};
		options.frame = options.frame || {};
		this.settings = _.extend( {}, defaults, options );
		this.settings.frame = _.extend( {}, defaults.frame, options.frame );

		/**
		 * Initialize a media frame.
		 *
		 * @returns {wp.media.view.MediaFrame.Select}
		 */
		this.frame = function() {
			var frame = frames[ this.settings.frame.id ];

			if ( frame ) {
				frame.control = this;
				return frame;
			}

			frame = wp.media({
				title: this.settings.frame.title,
				library: {
					type: this.settings.mediaType
				},
				button: {
					text: this.settings.frame.updateText
				},
				multiple: this.settings.frame.multiple
			});

			frame.control = this;
			frames[ this.settings.frame.id ] = frame;

			// Update the selected image in the media library based on the image in the control.
			frame.on( 'open', function() {
				var selection = this.get( 'library' ).get( 'selection' ),
					attachment, ids;

				if ( frame.control.$target.length ) {
					ids = frame.control.$target.val();
					// @todo Make sure the ids aren't already in the selection.
					if ( ids && '' !== ids && -1 !== ids && '0' !== ids ) {
						attachment = Attachment.get( ids );
						attachment.fetch();
					}
				}

				selection.reset( attachment ? [ attachment ] : [] );
			});

			// Update the control when an image is selected from the media library.
			frame.state( 'library' ).on( 'select', function() {
				var selection = this.get( 'selection' );
				frame.control.setAttachments( selection );
				frame.control.$el.trigger( 'selectionChange.simpleimagewidget', [ selection ] );
			});

			return frame;
		};

		/**
		 * Set the control's attachments.
		 *
		 * @param {Array} attachments An array of wp.media.model.Attachment objects.
		 */
		this.setAttachments = function( attachments ) {
			var prop = this.$el.data( 'return-property' ) || 'id';

			// Insert the selected attachment ids into the target element.
			if ( this.$target.length ) {
				this.$target.val( attachments.pluck( prop ) ).trigger( 'change' );
			}
		};
	};

	_.extend( SimpleImageWidget, {
		/**
		 * Retrieve a media selection control object.
		 *
		 * @param {Object} el HTML element.
		 *
		 * @returns {Control}
		 */
		getControl: function( el ) {
			var control, $control;

			$control = $( el ).closest( '.simple-image-widget-control' );
			control = $control.data( 'media-control' );

			if ( ! control ) {
				control = new Control( $control );
				$control.data( 'media-control', control );
			}

			return control;
		},

		/**
		 * Update a dropdown field with size options.
		 *
		 * @param {Object} field Dropdown field element.
		 * @param {Array} sizes
		 */
		updateSizeDropdownOptions: function( field, sizes ) {
			var $field = field,
				currentValue, name, options;

			if ( ! ( $field instanceof $ ) ) {
				$field = $( $field );
			}

			if ( sizes ) {
				_.each( sizes, function( size, key ) {
					var name = l10n.imageSizeNames[ key ] || '';
					options += '<option value="' + key + '">' + name + ' (' + size.width + '&times;' + size.height + ')</option>';
				});
			}

			if ( ! options ) {
				name = l10n.imageSizeNames['full'] || l10n.fullSizeLabel;
				options = '<option value="full">' + name + '</option>';
			}

			// Try to maintain the previously selected size if it still exists.
			currentValue = $field.val();
			$field.html( options ).val( currentValue ).removeAttr( 'disabled' );
		}
	});

	finder.view.Page = wp.Backbone.View.extend({
		events: {
			'click .simple-image-widget-find-posts-button' : 'openModal'
		},

		initialize: function( options ) {
			this.modal = options.modal;
			this.render();
		},

		render: function() {
			this.$el.append( this.modal.$el );
			return this;
		},

		openModal: function( e ) {
			var $target = $( e.target).closest( '.simple-image-widget-input-group' ).find( 'input[type="text"]' );
			e.preventDefault();
			this.modal.open( $target );
		}
	});

	finder.view.Modal = wp.Backbone.View.extend({
		tagName: 'div',
		className: 'find-box',
		template: wp.template( 'simple-image-widget-modal' ),

		events : {
			'click .simple-image-widget-modal-search-button': 'findPosts',
			'keypress .simple-image-widget-modal-search-field': 'routeKey',
			'keyup .simple-image-widget-modal-search-field': 'routeKey',
			'click tr': 'select',
			'click .js-close': 'close'
		},

		initialize: function( options ) {
			this.$pageWrapper = options.pageWrapper;
			this.postTypes = options.postTypes;
			this.$overlay = false;
			this.$target = $();
			this.render();
		},

		render: function() {
			this.$el.hide().html( this.template() );
			this.$field = this.$el.find( '.simple-image-widget-modal-search-field' );
			this.$overlay = $( '.ui-find-overlay' );
			this.$response = this.$el.find( '.simple-image-widget-modal-response' );
			this.$spinner = this.$el.find( '.simple-image-widget-modal-search .spinner' );
			return this;
		},

		close: function() {
			this.$overlay.hide();
			this.$el.hide();
			this.$target = $();
		},

		findPosts: function() {
			var self = this;
			self.$spinner.show();

			wp.ajax.post( 'simple_image_widget_find_posts', {
				s: self.$field.val(),
				post_types: this.postTypes,
				nonce: $( '#siw-find-posts-ajax-nonce' ).val()
			}).always(function() {
				self.$spinner.hide();
			}).done(function( response ) {
				self.$response.html( response );
			}).fail(function() {
				self.$response.text( l10n.responseError );
			});
		},

		open: function( $target ) {
			this.$target = $target;
			this.$response.html( '' );
			this.$el.show();
			this.$field.focus();

			if ( ! this.$overlay.length ) {
				this.$pageWrapper.append( '<div class="ui-find-overlay"></div>' );
				this.$overlay  = $( '.ui-find-overlay' );
			}

			this.$overlay.show();
			this.findPosts();
		},

		routeKey: function( e ) {
			if ( e.which && 13 === e.which ) {
				this.findPosts();
			} else if ( e.which && 27 === e.which ) {
				this.close();
			}
		},

		select: function( e ) {
			var value = $( e.target ).closest( 'tr' ).find( 'input' ).val();
			this.$target.val( value );
			this.close();
		}
	});

	// Document ready.
	$( document ).ready(function() {
		var modal,
			$body = $( 'body' ),
			$page = $( '.wp-full-overlay' );

		if ( $page.length < 1 ) {
			$page = $body;
		}

		// Open the media frame when the choose button or image are clicked.
		$body.on( 'click', '.simple-image-widget-control-choose, .simple-image-widget-form img', function( e ) {
			e.preventDefault();
			SimpleImageWidget.getControl( this ).frame().open();
		});

		// Update the image preview and size dropdown in a widget when an image is selected.
		$body.on( 'selectionChange.simpleimagewidget', '.simple-image-widget-control', function( e, selection ) {
			var $control = $( e.target ),
				$sizeField = $control.closest( '.simple-image-widget-form' ).find( 'select.image-size' ),
				model = selection.first(),
				sizes = model.get( 'sizes' ),
				size, image;

			if ( sizes ) {
				size = sizes['post-thumbnail'] || sizes.medium;
			}

			if ( $sizeField.length ) {
				SimpleImageWidget.updateSizeDropdownOptions( $sizeField, sizes );
			}

			size = size || model.toJSON();
			image = $( '<img />', { src: size.url });

			$control.find( 'img' ).remove().end()
				.prepend( image )
				.addClass( 'has-image' )
				.find( 'a.simple-image-widget-control-choose' ).removeClass( 'button-hero' );
		});

		// Wire up the toggle checkboxes in the screen options tab.
		$( '.simple-image-widget-field-toggle' ).on( 'click', function() {
			var $this = $( this ),
				field = $this.val(),
				$hiddenFields = $( '.simple-image-widget-field-toggle:not(:checked)' );

			$( '.simple-image-widget-field-' + field ).toggleClass( 'is-hidden', ! $this.prop( 'checked' ) );

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'simple_image_widget_preferences',
					hidden: $hiddenFields.map(function() { return this.value; }).get().join( ',' ),
					nonce: SimpleImageWidget.screenOptionsNonce
				},
				success: function( data ) {
					if ( 'nonce' in data ) {
						SimpleImageWidget.screenOptionsNonce = data.nonce;
					}
				}
			});
		});

		$body.on( 'focus', '.simple-image-widget-input-group-field', function() {
			$( this ).parent().addClass( 'is-focused' );
		}).on( 'blur', '.simple-image-widget-input-group-field', function() {
			$( this ).parent().removeClass( 'is-focused' );
		});

		modal = new finder.view.Modal({
			postTypes: ['any'],
			pageWrapper: $page
		});

		new finder.view.Page({
			el: $page.get( 0 ),
			modal: modal
		});
	});

})( this, jQuery, _, Backbone, wp );
