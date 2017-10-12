<?php
/**
 * The image widget.
 *
 * @package   SimpleImageWidget
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 * @since     3.0.0
 */

/**
 * Image widget class.
 *
 * @package SimpleImageWidget
 * @since   3.0.0
 */
class Simple_Image_Widget extends WP_Widget {
	/**
	 * Setup widget options.
	 *
	 * Child classes may override the defaults.
	 *
	 * @since 3.0.0
	 * @see   WP_Widget::construct()
	 *
	 * @param string $id_base Optional Base ID for the widget, lower case, if
	 *     left empty a portion of the widget's class name will be used. Must be unique.
	 * @param string $name Name for the widget displayed on the configuration page.
	 * @param array  $widget_options {
	 *     Widget options. Passed to wp_register_sidebar_widget(). Optional.
	 *
	 *	   @type string $description Widget description. Shown on the configuration page.
	 *	   @type string $classname   HTML class.
	 * }
	 * @param array $control_options {
	 *     Passed to wp_register_widget_control(). Optional.
	 *
	 *	   @type int $width  Width of the widget edit form.
	 * )
	 */
	public function __construct( $id_base = false, $name = false, $widget_options = array(), $control_options = array() ) {
		$id_base = ( $id_base ) ? $id_base : 'simpleimage'; // Legacy ID.
		$name    = ( $name ) ? $name : __( 'Image (Simple)', 'simple-image-widget' );

		$widget_options = wp_parse_args(
			$widget_options,
			array(
				'classname'                   => 'widget_simpleimage', // Legacy class name.
				'customize_selective_refresh' => true,
				'description'                 => __( 'An image from your Media Library.', 'simple-image-widget' ),
			)
		);

		$control_options = wp_parse_args( $control_options, array() );

		parent::__construct( $id_base, $name, $widget_options, $control_options );

		// Flush widget group cache when an attachment is saved, deleted, or the theme is switched.
		add_action( 'save_post', array( $this, 'flush_group_cache' ) );
		add_action( 'delete_attachment', array( $this, 'flush_group_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_group_cache' ) );
	}

	/**
	 * Display the widget.
	 *
	 * Filters the instance data, fetches the output, displays it, then caches
	 * it. Overload or filter the render() method to modify output.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args     Registered sidebar arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The widget instance settings.
	 */
	public function widget( $args, $instance ) {
		$cache    = (array) wp_cache_get( 'simple_image_widget', 'widget' );
		$cache_id = $this->siw_get_cache_key( $args, $instance );

		if ( ! $this->is_preview() && isset( $cache[ $cache_id ] ) ) {
			echo $cache[ $cache_id ];
			return;
		}

		// Copy the original values so they can be used in hooks.
		$instance['text_raw']  = empty( $instance['text'] ) ? '' : $instance['text'];
		$instance['title_raw'] = empty( $instance['title'] ) ? '' : $instance['title'];
		$instance['text']      = apply_filters( 'widget_text', $instance['text_raw'], $instance, $this->id_base );
		$instance['title']     = apply_filters( 'widget_title', $instance['title_raw'], $instance, $this->id_base );

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
			$instance['link_open']       = '';
			$instance['link_close']      = '';
			$instance['text_link_open']  = '';
			$instance['text_link_close'] = '';

			if ( ! empty ( $instance['link'] ) ) {
				$target = ( empty( $instance['new_window'] ) ) ? '' : ' target="_blank"';

				$instance['link_open']  = '<a href="' . esc_url( $instance['link'] ) . '"' . $target . '>';
				$instance['link_close'] = '</a>';

				// This is to differentiate between the image link and text link.
				$instance['text_link_open']  = $instance['link_open'];
				$instance['text_link_close'] = $instance['link_close'];

				// The link classes should only be added to the text link.
				if ( ! empty( $instance['link_classes'] ) ) {
					$instance['text_link_open'] = sprintf(
						'<a href="%1$s" class="%3$s"%2$s>',
						esc_url( $instance['link'] ),
						$target,
						esc_attr( $instance['link_classes'] )
					);
				}
			}

			$output = $this->render( $args, $instance );
		}

		echo $output;

		if ( ! $this->is_preview() ) {
			$cache[ $cache_id ] = $output;
			wp_cache_set( 'simple_image_widget', array_filter( $cache ), 'widget' );
		}
	}

	/**
	 * Generate the widget output.
	 *
	 * This is typically done in the widget() method, but moving it to a
	 * separate method allows for the routine to be easily overloaded by a class
	 * extending this one without having to reimplement all the caching and
	 * filtering, or resorting to adding a filter, calling the parent method,
	 * then removing the filter.
	 *
	 * @since 3.0.0
	 *
	 * @param array   $args     Registered sidebar arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array   $instance The widget instance settings.
	 * @return string HTML output.
	 */
	public function render( $args, $instance ) {
		$output = $args['before_widget'];

		/**
		 * Widget HTML output.
		 *
		 * @since 3.0.0
		 *
		 * @param string $output   Widget output.
		 * @param array  $args     Registered sidebar arguments including before_title, after_title, before_widget, and after_widget.
		 * @param array  $instance The widget instance settings.
		 * @param string $id_base  Widget type id.
		 */
		$inside = apply_filters( 'simple_image_widget_output', '', $args, $instance, $this->id_base );

		if ( $inside ) {
			$output .= $inside;
		} else {
			$data = array();
			$data['args'] = $args;
			$data['after_title'] = $args['after_title'];
			$data['before_title'] = $args['before_title'];
			$data['image_size'] = $image_size = ( ! empty( $instance['image_size'] ) ) ? $instance['image_size'] : apply_filters( 'simple_image_widget_output_default_size', 'medium', $this->id_base );
			$data['title'] = ( empty( $instance['title'] ) ) ? '' : $instance['title'];
			$data = array_merge( $instance, $data );
			$data = apply_filters( 'simple_image_widget_template_data', $data );

			ob_start();
			$templates = $this->get_template_names( $args, $instance );

			$template_loader = new Simple_Image_Widget_Template_Loader();
			$template = $template_loader->locate_template( $templates );
			$template_loader->load_template( $template, $data );
			$output .= ob_get_clean();
		}

		$output .= $args['after_widget'];

		return $output;
	}

	/**
	 * Display the form to edit widget settings.
	 *
	 * @since 3.0.0
	 *
	 * @param array $instance The widget settings.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'alt'          => '', // Legacy.
				'image'        => '', // Legacy URL field.
				'image_id'     => '',
				'image_size'   => 'full',
				'link'         => '',
				'link_classes' => '',
				'link_text'    => '',
				'new_window'   => '',
				'title'        => '',
				'text'         => '',
			)
		);

		$instance['image_id'] = absint( $instance['image_id'] );
		$instance['title']    = wp_strip_all_tags( $instance['title'] );

		$button_class = array( 'button', 'button-hero', 'simple-image-widget-control-choose' );
		$image_id     = $instance['image_id'];

		/**
		 * The list of fields to display.
		 *
		 * The order of fields can be modified, new fields can be registered, or
		 * existing fields can be removed here. Use the widget type id to limit
		 * fields to a particular type of widget.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $fields  List of field ids.
		 * @param string $id_base Widget type id.
		 */
		$fields = (array) apply_filters( 'simple_image_widget_fields', $this->form_fields(), $this->id_base );
		?>

		<div class="simple-image-widget-form">

			<?php
			/**
			 * Display additional information or HTML before the widget edit form.
			 *
			 * @since 3.0.0
			 *
			 * @param array  $instance The widget setttings.
			 * @param string $id_base  Widget type id.
			 */
			do_action( 'simple_image_widget_form_before', $instance, $this->id_base );
			?>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'simple-image-widget' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat">
			</p>

			<?php if ( ! is_simple_image_widget_legacy() ) : ?>
				<p class="simple-image-widget-control<?php echo ( $image_id ) ? ' has-image' : ''; ?>"
					data-title="<?php esc_attr_e( 'Choose an Image', 'simple-image-widget' ); ?>"
					data-update-text="<?php esc_attr_e( 'Update Image', 'simple-image-widget' ); ?>"
					data-target=".image-id">
					<?php
					if ( $image_id ) {
						echo wp_get_attachment_image( $image_id, 'medium', false );
						unset( $button_class[ array_search( 'button-hero', $button_class ) ] );
					}
					?>
					<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'image_id' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'image_id' ) ); ?>" value="<?php echo absint( $image_id ); ?>" class="image-id simple-image-widget-control-target">
					<a href="#" class="<?php echo esc_attr( join( ' ', $button_class ) ); ?>"><?php _e( 'Choose an Image', 'simple-image-widget' ); ?></a>
				</p>
			<?php endif; ?>

			<?php
			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					switch ( $field ) {
						case 'image_size' :
							$sizes = $this->get_image_sizes( $image_id );
							?>
							<p class="<?php echo esc_attr( $this->siw_field_class( 'image_size' ) ); ?>">
								<label for="<?php echo esc_attr( $this->get_field_id( 'image_size' ) ); ?>"><?php _e( 'Size:', 'simple-image-widget' ); ?></label>
								<select name="<?php echo esc_attr( $this->get_field_name( 'image_size' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'image_size' ) ); ?>" class="widefat image-size"<?php echo ( sizeof( $sizes ) < 2 ) ? ' disabled="disabled"' : ''; ?>>
									<?php
									foreach ( $sizes as $id => $label ) {
										printf(
											'<option value="%s"%s>%s</option>',
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
							<p class="<?php echo esc_attr( $this->siw_field_class( 'link' ) ); ?>">
								<label for="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>"><?php _e( 'Link:', 'simple-image-widget' ); ?></label>
								<span class="simple-image-widget-input-group">
									<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'link' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>" value="<?php echo esc_url( $instance['link'] ); ?>" class="simple-image-widget-input-group-field">
									<button class="simple-image-widget-find-posts-button simple-image-widget-input-group-button dashicons dashicons-search"></button>
								</span>
							</p>
							<p class="<?php echo esc_attr( $this->siw_field_class( 'new_window' ) ); ?>" style="margin-top: -0.75em; padding-left: 2px">
								<label for="<?php echo esc_attr( $this->get_field_id( 'new_window' ) ); ?>">
									<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'new_window' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'new_window' ) ); ?>" <?php checked( $instance['new_window'] ); ?>>
									<?php _e( 'Open in new window?', 'simple-image-widget' ); ?>
								</label>
							</p>
							<?php
							break;

						case 'link_classes' :
							?>
							<p class="<?php echo esc_attr( $this->siw_field_class( 'link_classes' ) ); ?>">
								<label for="<?php echo esc_attr( $this->get_field_id( 'link_classes' ) ); ?>"><?php _e( 'Link Classes:', 'simple-image-widget' ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'link_classes' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'link_classes' ) ); ?>" value="<?php echo esc_attr( $instance['link_classes'] ); ?>" class="widefat">
							</p>
							<?php
							break;

						case 'link_text' :
							?>
							<p class="<?php echo esc_attr( $this->siw_field_class( 'link_text' ) ); ?>">
								<label for="<?php echo esc_attr( $this->get_field_id( 'link_text' ) ); ?>"><?php _e( 'Link Text:', 'simple-image-widget' ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'link_text' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'link_text' ) ); ?>" value="<?php echo esc_attr( $instance['link_text'] ); ?>" class="widefat">
							</p>
							<?php
							break;

						case 'text' :
							?>
							<p class="<?php echo esc_attr( $this->siw_field_class( 'text' ) ); ?>">
								<label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php _e( 'Text:', 'simple-image-widget' ); ?></label>
								<textarea name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" rows="4" class="widefat"><?php echo esc_textarea( $instance['text'] ); ?></textarea>
							</p>
							<?php
							break;

						default :
							/**
							 * Display a custom field.
							 *
							 * This action will fire for custom fields
							 * registered with the 'simple_image_widget_fields'
							 * filter.
							 *
							 * @since 3.0.0
							 *
							 * @param array  $instance The widget setttings.
							 * @param string $widget   Widget instance.
							 */
							do_action( 'simple_image_widget_field-' . sanitize_key( $field ), $instance, $this );
					}
				}
			}

			/**
			 * Display additional information or HTML after the widget edit form.
			 *
			 * @since 3.0.0
			 *
			 * @param array  $instance The widget setttings.
			 * @param string $id_base  Widget type id.
			 */
			do_action( 'simple_image_widget_form_after', $instance, $this->id_base );
			?>

		</div><!-- /.simple-image-widget-form -->
		<?php
	}

	/**
	 * The list of extra fields that should be shown in the widget form.
	 *
	 * Can be easily overloaded by a child class.
	 *
	 * @since 3.0.0
	 *
	 * @return string List of field ids.
	 */
	public function form_fields() {
		return array( 'image_size', 'link', 'link_text', 'link_classes', 'text' );
	}

	/**
	 * Save and sanitize widget settings.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $new_instance New widget settings.
	 * @param array  $old_instance Previous widget settings.
	 * @return array Sanitized settings.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = wp_parse_args( $new_instance, $old_instance );

		$instance = apply_filters( 'simple_image_widget_instance', $instance, $new_instance, $old_instance, $this->id_base );

		$instance['title']      = wp_strip_all_tags( $new_instance['title'] );
		$instance['image_id']   = absint( $new_instance['image_id'] );
		$instance['new_window'] = isset( $new_instance['new_window'] );

		// Optional field that can be removed via a filter.
		foreach ( array( 'link', 'link_classes', 'link_text', 'text' ) as $key ) {
			if ( ! isset( $new_instance[ $key ] ) ) {
				continue;
			}

			switch ( $key ) {
				case 'link' :
					$instance['link'] = esc_url_raw( $new_instance['link'] );
					break;
				case 'link_classes' :
					$instance['link_classes'] = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $new_instance['link_classes'] ) ) );
					break;
				case 'link_text' :
					$instance['link_text'] = wp_kses_data( $new_instance['link_text'] );
					break;
				case 'text' :
					$instance['text'] = stripslashes( wp_filter_post_kses( addslashes( $new_instance['text'] ) ) );
					break;
			}
		}

		$this->flush_group_cache();

		return $instance;
	}

	/**
	 * Determine if the widget is being displayed in the customizer.
	 *
	 * @since 4.0.1
	 * @link  https://core.trac.wordpress.org/ticket/27538
	 *
	 * @return bool
	 */
	public function is_preview() {
		global $wp_customize;

		if ( method_exists( get_parent_class( $this ), 'is_preview' ) ) {
			return parent::is_preview();
		}

		return ( isset( $wp_customize ) && $wp_customize->is_preview() ) ;
	}

	/**
	 * Get the various sizes of an image.
	 *
	 * @since 3.0.0
	 *
	 * @param  int   $image_id Image attachment ID.
	 * @return array List of image size keys and their localized labels.
	 */
	public function get_image_sizes( $image_id ) {
		$sizes = array( 'full' => __( 'Full Size', 'simple-image-widget' ) );

		$imagedata = wp_get_attachment_metadata( $image_id );
		if ( isset( $imagedata['sizes'] ) ) {
			$size_names = Simple_Image_Widget_Plugin::get_image_size_names();

			$sizes['full'] .= ( isset( $imagedata['width'] ) && isset( $imagedata['height'] ) ) ? sprintf( ' (%d&times;%d)', $imagedata['width'], $imagedata['height'] ) : '';

			foreach ( $imagedata['sizes'] as $_size => $data ) {
				$label  = ( isset( $size_names[ $_size ] ) ) ? $size_names[ $_size ] : ucwords( $_size );
				$label .= sprintf( ' (%d&times;%d)', $data['width'], $data['height'] );

				$sizes[ $_size ] = $label;
			}
		}

		return $sizes;
	}

	/**
	 * Flush the cache for all image widgets.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	public function flush_group_cache( $post_id = null ) {
		if ( 'save_post' == current_filter() && 'attachment' != get_post_type( $post_id ) ) {
			return;
		}

		wp_cache_delete( 'simple_image_widget', 'widget' );
	}

	/**
	 * Retrieve a list of templates to look up.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $args     Registered sidebar arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array  $instance The widget instance settings.
	 * @return array List of template names.
	 */
	protected function get_template_names( $args, $instance ) {
		$templates = array();
		if ( ! empty( $args['id'] ) ) {
			$templates[] = $args['id'] . '_widget.php';
		}
		$templates[] = 'widget.php';

		/**
		 * List of template names to look up to render output.
		 *
		 * Child widgets should consider adding a new template using the widget type id ($this->id_base).
		 *
		 * @since 4.0.0
		 *
		 * @param array  $templates List of template names.
		 * @param array  $args     Registered sidebar arguments including before_title, after_title, before_widget, and after_widget.
		 * @param array  $instance The widget instance settings.
		 * @param string $id_base  Widget type id.
		 */
		return apply_filters(
			'simple_image_widget_templates',
			$templates,
			$args,
			$instance,
			$this->id_base
		);
	}

	/**
	 * Retrieve HTML classes for a field container.
	 *
	 * @since 4.1.0
	 *
	 * @param string $id Field id.
	 * @return string
	 */
	protected function siw_field_class( $id ) {
		$classes = array( 'simple-image-widget-field', 'simple-image-widget-field-' . sanitize_html_class( $id ) );

		$hidden_fields = Simple_Image_Widget_Plugin::get_hidden_fields();
		if ( in_array( $id, $hidden_fields ) ) {
			$classes[] = 'is-hidden';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Retrieve a cache key based on a hash of passed parameters.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	protected function siw_get_cache_key() {
		$data = array();
		foreach ( func_get_args() as $arg ) {
			$data = array_merge( $data, (array) $arg );
		}
		ksort( $data );
		return 'siw_' . md5( json_encode( $data ) );
	}

	/**
	 * Remove a single image widget from the cache.
	 *
	 * @since 3.0.0
	 * @deprecated 4.2.0
	 */
	public function flush_widget_cache() {}
}
