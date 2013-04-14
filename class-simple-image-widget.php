<?php
/**
 * Image widget class.
 *
 * @package SimpleImageWidget
 *
 * @since 3.0.0
 */
class Simple_Image_Widget extends WP_Widget {
	/**
	 * Setup widget options.
	 *
	 * Allows child classes to overload the defaults.
	 *
	 * @since 3.0.0
	 * @see WP_Widget::construct()
	 */
	function __construct( $id_base = false, $name = false, $widget_options = array(), $control_options = array() ) {
		$id_base = ( $id_base ) ? $id_base : 'simpleimage'; // Legacy ID.
		$name = ( $name ) ? $name : __( 'Image', 'simple-image-widget' );

		$widget_options = wp_parse_args( $widget_options, array(
			'classname'   => 'widget_simpleimage', // Legacy class name.
			'description' => __( 'Display an image', 'simple-image-widget' ),
		) );

		$control_options = wp_parse_args( $control_options, array(
			'width' => 300
		) );

		parent::__construct( $id_base, $name, $widget_options, $control_options );

		// Flush widget group cache when an attachment is saved, deleted, or the theme is switched.
		add_action( 'save_post', array( $this, 'flush_group_cache' ) );
		add_action( 'delete_attachment', array( $this, 'flush_group_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_group_cache' ) );
	}

	/**
	 * Default widget front end display method.
	 *
	 * Filters the instance data, fetches the output, displays it, then caches
	 * it. Overload or filter the render() method to modify output.
	 *
	 * @since 3.0.0
	 */
	function widget( $args, $instance ) {
		$cache = (array) wp_cache_get( 'simple_image_widget', 'widget' );

		if ( isset( $cache[ $this->id ] ) ) {
			echo $cache[ $this->id ];
			return;
		}

		// Copy the original title so it can be passed to hooks.
		$instance['title_raw'] = $instance['title'];
		$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		// Copy the original text so it can be passed to hooks.
		$instance['text_raw'] = $instance['text'];
		$instance['text'] = apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance, $this->id_base );

		// Start building the output.
		$output = '';

		// Make sure the image ID is a valid attachment.
		if ( ! empty( $instance['image_id'] ) ) {
			$image = get_post( $instance['image_id'] );
			if ( ! $image || 'attachment' != get_post_type( $image ) ) {
				$output = '<!-- Image Widget Error: Invalid Attachment ID -->';
			}
		}

		if ( empty( $output ) ) {
			$output = $this->render( $args, $instance );
		}

		echo $output;

		$cache[ $this->id ] = $output;
		wp_cache_set( 'simple_image_widget', array_filter( $cache ), 'widget' );
	}

	/**
	 * Generate the widget output.
	 *
	 * This is typically done in the widget() method, but moving it to a
	 * separate method allows for the routine to be easily overloaded by a
	 * class extending this one without having to reimplement all the caching
	 * and filtering, or resorting to adding a filter, calling the parent
	 * method, then removing the filter.
	 *
	 * @since 3.0.0
	 */
	function render( $args, $instance ) {
		$instance['link_open'] = '';
		$instance['link_close'] = '';
		if ( ! empty ( $instance['link'] ) ) {
			$target = ( empty( $instance['new_window'] ) ) ? '' : ' target="_blank"';
			$instance['link_open'] = '<a href="' . esc_url( $instance['link'] ) . '"' . $target . '>';
			$instance['link_close'] = '</a>';
		}

		$output = $args['before_widget'];

			// Allow custom output to override the default HTML.
			if ( $inside = apply_filters( 'simple_image_widget_output', '', $args, $instance, $this->id_base ) ) {
				$output .= $inside;
			} else {
				$output .= ( empty( $instance['title'] ) ) ? '' : $args['before_title']. $instance['title'] . $args['after_title'];

				// Add the image.
				if ( ! empty( $instance['image_id'] ) ) {
					$image_size = ( ! empty( $instance['image_size'] ) ) ? $instance['image_size'] : apply_filters( 'simple_image_widget_output_default_size', 'medium', $this->id_base );

					$output .= sprintf( '<p class="simple-image">%s%s%s</p>',
						$instance['link_open'],
						wp_get_attachment_image( $instance['image_id'], $image_size ),
						$instance['link_close']
					);
				} elseif ( ! empty( $instance['image'] ) ) {
					// Legacy output.
					$output .= sprintf( '%s<img src="%s" alt="%s">%s',
						$instance['link_open'],
						esc_url( $instance['image'] ),
						( empty( $instance['alt'] ) ) ? '' : esc_attr( $instance['alt'] ),
						$instance['link_close']
					);
				}

				// Add the text.
				if ( ! empty( $instance['text'] ) ) {
					$output .= apply_filters( 'the_content', $instance['text'] );
				}

				// Add a more link.
				if ( ! empty( $instance['link_open'] ) && ! empty( $instance['link_text'] ) ) {
					$output .= '<p class="more">' . $instance['link_open'] . $instance['link_text'] . $instance['link_close'] . '</p>';
				}
			}

		$output .= $args['after_widget'];

		return $output;
	}

	/**
	 * Form for modifying widget settings.
	 *
	 * @since 3.0.0
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array(
			'alt'        => '', // Legacy.
			'image'      => '', // Legacy URL field.
			'image_id'   => '',
			'image_size' => 'full',
			'link'       => '',
			'link_text'  => '',
			'new_window' => '',
			'title'      => '',
			'text'       => '',
		) );

		$instance['image_id'] = absint( $instance['image_id'] );
		$instance['title'] = wp_strip_all_tags( $instance['title'] );

		$button_class = array( 'button', 'button-hero', 'simple-image-widget-control-choose' );
		$image_id = $instance['image_id'];

		// The order of fields can be modified, new fields can be registered, or existing fields can be removed here.
		$fields = (array) apply_filters( 'simple_image_widget_fields', $this->form_fields(), $this->id_base );
		?>

		<div class="simple-image-widget-form">

			<?php do_action( 'simple_image_widget_form_before', $instance, $this->id_base ); ?>

			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'simple-image-widget' ); ?></label>
				<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" id="<?php echo $this->get_field_id( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat">
			</p>

			<?php if ( ! is_simple_image_widget_legacy() ) : ?>
				<p class="simple-image-widget-control<?php echo ( $image_id ) ? ' has-image' : ''; ?>"
					data-title="<?php esc_attr_e( 'Choose an Image for the Widget', 'simple-image-widget' ); ?>"
					data-update-text="<?php esc_attr_e( 'Update Image', 'simple-image-widget' ); ?>"
					data-target=".image-id">
					<?php
					if ( $image_id ) {
						echo wp_get_attachment_image( $image_id, 'medium', false );
						unset( $button_class[ array_search( 'button-hero', $button_class ) ] );
					}
					?>
					<input type="hidden" name="<?php echo $this->get_field_name( 'image_id' ); ?>" id="<?php echo $this->get_field_id( 'image_id' ); ?>" value="<?php echo $image_id; ?>" class="image-id simple-image-widget-control-target">
					<a href="#" class="<?php echo join( ' ', $button_class ); ?>"><?php _e( 'Choose an Image', 'simple-image-widget' ); ?></a>
				</p>
			<?php endif; ?>

			<?php if ( is_simple_image_widget_legacy() || ! empty( $instance['image'] ) ) : ?>
				<div class="simple-image-widget-legacy-fields">
					<?php if ( ! is_simple_image_widget_legacy() ) : ?>
						<p>
							<em><?php _e( 'These fields are here to maintain your data from an earlier version.', 'simple-image-widget' ); ?></em>
						</p>
						<p>
							<em><?php _e( 'Select an image, then clear these values, and they will disappear when you save the widget.', 'simple-image-widget' ); ?></em>
						</p>
					<?php endif; ?>

					<p>
						<label for="<?php echo $this->get_field_id( 'image' ); ?>"><?php _e( 'Image URL:', 'simple-image-widget' ); ?></label>
						<input type="text" name="<?php echo $this->get_field_name( 'image' ); ?>" id="<?php echo $this->get_field_id( 'image' ); ?>" value="<?php echo esc_url( $instance['image'] ); ?>" class="widefat">
					</p>
					<p>
						<label for="<?php echo $this->get_field_id( 'alt' ); ?>"><?php _e( 'Alternate Text:', 'simple-image-widget' ); ?></label>
						<input type="text" name="<?php echo $this->get_field_name( 'alt' ); ?>" id="<?php echo $this->get_field_id( 'alt' ); ?>" value="<?php echo esc_attr( $instance['alt'] ); ?>" class="widefat">
					</p>
				</div>
			<?php endif; ?>

			<?php
			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					switch ( $field ) {
						case 'image_size' :
							$sizes = $this->get_image_sizes( $image_id );
							?>
							<p>
								<label for="<?php echo $this->get_field_id( 'image_size' ); ?>"><?php _e( 'Size:', 'simple-image-widget' ); ?></label>
								<select name="<?php echo $this->get_field_name( 'image_size' ); ?>" id="<?php echo $this->get_field_id( 'image_size' ); ?>" class="widefat image-size"<?php echo ( sizeof( $sizes ) < 2 ) ? ' disabled="disabled"' : ''; ?>>
									<?php
									foreach ( $sizes as $id => $label ) {
										printf( '<option value="%s"%s>%s</option>',
											esc_attr( $id ),
											selected( $instance['image_size'], $id, false ),
											esc_html( $label )
										);
									}
									?>
								</select>
							</p>
							<?php
							break;

						case 'link' :
							?>
							<p style="margin-bottom: 0.25em">
								<label for="<?php echo $this->get_field_id( 'link' ); ?>"><?php _e( 'Link:', 'simple-image-widget' ); ?></label>
								<input type="text" name="<?php echo $this->get_field_name( 'link' ); ?>" id="<?php echo $this->get_field_id( 'link' ); ?>" value="<?php echo esc_url( $instance['link'] ); ?>" class="widefat">
							</p>
							<p style="padding-left: 2px">
								<label for="<?php echo $this->get_field_id( 'new_window' ); ?>">
									<input type="checkbox" name="<?php echo $this->get_field_name( 'new_window' ); ?>" id="<?php echo $this->get_field_id( 'new_window' ); ?>" <?php checked( $instance['new_window'] ); ?>>
									<?php _e( 'Open in new window?', 'simple-image-widget' ); ?>
								</label>
							</p>
							<?php
							break;

						case 'link_text' :
							?>
							<p>
								<label for="<?php echo $this->get_field_id( 'link_text' ); ?>"><?php _e( 'Link Text:', 'simple-image-widget' ); ?></label>
								<input type="text" name="<?php echo $this->get_field_name( 'link_text' ); ?>" id="<?php echo $this->get_field_id( 'link_text' ); ?>" value="<?php echo esc_attr( $instance['link_text'] ); ?>" class="widefat">
							</p>
							<?php
							break;

						case 'text' :
							?>
							<p>
								<label for="<?php echo $this->get_field_id( 'text' ); ?>"><?php _e( 'Text:', 'simple-image-widget' ); ?></label>
								<textarea name="<?php echo $this->get_field_name( 'text' ); ?>" id="<?php echo $this->get_field_id( 'text' ); ?>" rows="4" class="widefat"><?php echo esc_textarea( $instance['text'] ); ?></textarea>
							</p>
							<?php
							break;

						default :
							// Custom fields can be added using this action.
							do_action( 'simple_image_widget_field-' . sanitize_key( $field ), $instance, $this );
					}
				}
			}

			do_action( 'simple_image_widget_form_after', $instance, $this->id_base );
			?>

		</div>
		<?php
	}

	/**
	 * The list of extra fields that should be shown in the widget form.
	 *
	 * Can be easily overloaded by a child class.
	 *
	 * @since 3.0.0
	 */
	function form_fields() {
		$fields = array( 'link', 'link_text', 'text' );

		// Don't show the image size field for users with older WordPress versions.
		if ( ! is_simple_image_widget_legacy() ) {
			array_unshift( $fields, 'image_size' );
		}

		return $fields;
	}

	/**
	 * Save widget settings.
	 *
	 * @since 3.0.0
	 */
	function update( $new_instance, $old_instance ) {
		$instance = wp_parse_args( $new_instance, $old_instance );

		$instance = apply_filters( 'simple_image_widget_instance', $instance, $new_instance, $old_instance, $this->id_base );

		$instance['title'] = wp_strip_all_tags( $new_instance['title'] );
		$instance['image_id'] = absint( $new_instance['image_id'] );
		$instance['link'] = esc_url_raw( $new_instance['link'] );
		$instance['link_text'] = wp_kses_data( $new_instance['link_text'] );
		$instance['new_window'] = isset( $new_instance['new_window'] );
		$instance['text'] = wp_kses_data( $new_instance['text'] );

		$instance['image'] = esc_url_raw( $new_instance['image'] ); // Legacy image URL.
		if ( empty( $instance['image'] ) ) {
			unset( $instance['image'] );
		}

		$instance['alt'] = wp_strip_all_tags( $instance['alt'] ); // Legacy alt text.
		if ( empty( $instance['alt'] ) ) {
			unset( $instance['alt'] );
		}

		$this->flush_widget_cache();

		return $instance;
	}

	/**
	 * Get the various sizes of an images.
	 *
	 * @since 3.0.0
	 *
	 * @param int $image_id Image attachment ID.
	 * @return array List of image size keys and their localized labels.
	 */
	function get_image_sizes( $image_id ) {
		$sizes = array( 'full' => __( 'Full Size', 'simple-image-widget' ) );

		$imagedata = wp_get_attachment_metadata( $image_id );
		if ( isset( $imagedata['sizes'] ) ) {
			$size_names = Simple_Image_Widget_Loader::get_image_size_names();

			$sizes['full'] .= ( isset( $imagedata['width'] ) && isset( $imagedata['height'] ) ) ? sprintf( ' (%d&times;%d)', $imagedata['width'], $imagedata['height'] ) : '';

			foreach( $imagedata['sizes'] as $_size => $data ) {
				$label  = ( isset( $size_names[ $_size ] ) ) ? $size_names[ $_size ] : ucwords( $_size );
				$label .= sprintf( ' (%d&times;%d)', $data['width'], $data['height'] );

				$sizes[ $_size ] = $label;
			}
		}

		return $sizes;
	}

	/**
	 * Remove a single image widget from the cache.
	 *
	 * @since 3.0.0
	 */
	function flush_widget_cache() {
		$cache = (array) wp_cache_get( 'simple_image_widget', 'widget' );

		if ( isset( $cache[ $this->id ] ) ) {
			unset( $cache[ $this->id ] );
		}

		wp_cache_set( 'simple_image_widget', array_filter( $cache ), 'widget' );
	}

	/**
	 * Flush the cache for all image widgets.
	 *
	 * @since 3.0.0
	 */
	function flush_group_cache( $post_id = null ) {
		if ( 'save_post' == current_filter() && 'attachment' != get_post_type( $post_id ) ) {
			return;
		}

		wp_cache_delete( 'simple_image_widget', 'widget' );
	}
}
