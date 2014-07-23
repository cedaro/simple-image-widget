<?php
/**
 * Simple Image Widget
 *
 * @package   SimpleImageWidget
 * @copyright Copyright (c) 2014, Blazer Six, Inc.
 * @license   GPL-2.0+
 * @since     3.0.0
 */

/**
 * The main plugin class for loading the widget and attaching hooks.
 *
 * @package SimpleImageWidget
 * @since   3.0.0
 */
class Simple_Image_Widget_Plugin {
	/**
	 * Set up the widget.
	 *
	 * @since 3.0.0
	 */
	public function load() {
		self::load_textdomain();
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		$compat = new Simple_Image_Widget_Legacy();
		$compat->load();

		if ( is_simple_image_widget_legacy() ) {
			return;
		}

		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'sidebar_admin_setup', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'screen_settings', array( $this, 'widgets_screen_settings' ), 10, 2 );
		add_action( 'wp_ajax_simple_image_widget_preferences', array( $this, 'ajax_save_user_preferences' ) );
	}

	/**
	 * Localize the plugin strings.
	 *
	 * @since 3.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'simple-image-widget', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages' );
	}

	/**
	 * Register the image widget.
	 *
	 * @since 3.0.0
	 */
	public function register_widget() {
		register_widget( 'Simple_Image_Widget' );
	}

	/**
	 * Register and localize generic scripts and styles.
	 *
	 * A preliminary attempt has been made to abstract the
	 * 'simple-image-widget-control' script a bit in order to allow it to be
	 * re-used anywhere a similiar media selection feature is needed.
	 *
	 * Custom image size labels need to be added using the
	 * 'image_size_names_choose' filter.
	 *
	 * @since 3.0.0
	 */
	public function register_assets() {
		wp_register_style(
			'simple-image-widget-admin',
			dirname( plugin_dir_url( __FILE__ ) ) . '/assets/css/simple-image-widget.css'
		);

		wp_register_script(
			'simple-image-widget-admin',
			dirname( plugin_dir_url( __FILE__ ) ) . '/assets/js/simple-image-widget.js',
			array( 'media-upload', 'media-views' )
		);

		wp_localize_script(
			'simple-image-widget-admin',
			'SimpleImageWidget',
			array(
				'l10n' => array(
					'frameTitle'      => __( 'Choose an Attachment', 'simple-image-widget' ),
					'frameUpdateText' => __( 'Update Attachment', 'simple-image-widget' ),
					'fullSizeLabel'   => __( 'Full Size', 'simple-image-widget' ),
					'imageSizeNames'  => self::get_image_size_names(),
				),
				'screenOptionsNonce' => wp_create_nonce( 'save-siw-preferences' ),
			)
		);
	}

	/**
	 * Add checkboxes to the screen options tab on the Widgets screen for
	 * togglable fields.
	 *
	 * @since 4.1.0
	 *
	 * @param string    $settings Screen options output.
	 * @param WP_Screen $screen   Current screen.
	 * @return string
	 */
	public function widgets_screen_settings( $settings, $screen ) {
		if ( 'widgets' !== $screen->id ) {
			return $settings;
		}

		$settings .= sprintf( '<h5>%s</h5>', __( 'Simple Image Widget', 'simple-image-widget' ) );

		$fields = array(
			'image_size'   => __( 'Image Size', 'simple-image-widget' ),
			'link'         => __( 'Link', 'simple-image-widget' ),
			'link_classes' => __( 'Link Classes', 'simple-image-widget' ),
			'link_text'    => __( 'Link Text', 'simple-image-widget' ),
			'new_window'   => __( 'New Window', 'simple-image-widget' ),
			'text'         => __( 'Text', 'simple-image-widget' ),
		);

		/**
		 * List of hideable fields.
		 *
		 * @since 4.1.0
		 *
		 * @param array $fields List of fields with ids as keys and labels as values.
		 */
		$fields = apply_filters( 'simple_image_widget_hideable_fields', $fields );
		$hidden_fields = $this->get_hidden_fields();

		foreach ( $fields as $id => $label ) {
			$settings .= sprintf(
				'<label><input type="checkbox" value="%1$s"%2$s class="simple-image-widget-field-toggle"> %3$s</label>',
				esc_attr( $id ),
				checked( in_array( $id, $hidden_fields ), false, false ),
				esc_html( $label )
			);
		}

		return $settings;
	}

	/**
	 * Enqueue scripts needed for selecting media.
	 *
	 * @since 3.0.0
	 *
	 * @param string $hook_suffix Screen id.
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_media();
		wp_enqueue_script( 'simple-image-widget-admin' );
		wp_enqueue_style( 'simple-image-widget-admin' );
	}

	/**
	 * Get localized image size names.
	 *
	 * The 'image_size_names_choose' filter exists in core and should be
	 * hooked by plugin authors to provide localized labels for custom image
	 * sizes added using add_image_size().
	 *
	 * @see image_size_input_fields()
	 * @see http://core.trac.wordpress.org/ticket/20663
	 *
	 * @since 3.0.0
	 *
	 * @return array Array of thumbnail sizes.
	 */
	public static function get_image_size_names() {
		return apply_filters(
			'image_size_names_choose',
			array(
				'thumbnail' => __( 'Thumbnail', 'simple-image-widget' ),
				'medium'    => __( 'Medium', 'simple-image-widget' ),
				'large'     => __( 'Large', 'simple-image-widget' ),
				'full'      => __( 'Full Size', 'simple-image-widget' ),
			)
		);
	}

	/**
	 * Retrieve a list of hidden fields.
	 *
	 * @since 4.1.0
	 *
	 * @return array List of field ids.
	 */
	public static function get_hidden_fields() {
		$hidden_fields = get_user_option( 'siw_hidden_fields', get_current_user_id() );

		// Fields that are hidden by default.
		if ( false === $hidden_fields ) {
			$hidden_fields = array( 'link_classes' );
		}

		/**
		 * List of hidden field ids.
		 *
		 * @since 4.1.0
		 *
		 * @param array $hidden_fields List of hidden field ids.
		 */
		return (array) apply_filters( 'simple_image_widget_hidden_fields', $hidden_fields );
	}

	/**
	 * AJAX callback to save the user's hidden fields.
	 *
	 * @since 4.1.0
	 */
	public function ajax_save_user_preferences() {
		$nonce_action = 'save-siw-preferences';
		check_ajax_referer( $nonce_action, 'nonce' );
		$data = array( 'nonce' => wp_create_nonce( $nonce_action ) );

		if ( ! $user = wp_get_current_user() ) {
			wp_send_json_error( $data );
		}

		$hidden = isset( $_POST['hidden'] ) ? explode( ',', $_POST['hidden'] ) : array();
		if ( is_array( $hidden ) ) {
			update_user_option( $user->ID, 'siw_hidden_fields', $hidden );
		}

		wp_send_json_success( $data );
	}
}
