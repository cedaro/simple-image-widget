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
			dirname( plugin_dir_url( __FILE__ ) ) . '/assets/styles/simple-image-widget.css'
		);

		wp_register_script(
			'simple-image-widget-admin',
			dirname( plugin_dir_url( __FILE__ ) ) . '/assets/scripts/simple-image-widget.js',
			array( 'media-upload', 'media-views' )
		);

		wp_localize_script( 'simple-image-widget-admin', 'SimpleImageWidget', array(
			'l10n' => array(
				'frameTitle'      => __( 'Choose an Attachment', 'simple-image-widget' ),
				'frameUpdateText' => __( 'Update Attachment', 'simple-image-widget' ),
				'fullSizeLabel'   => __( 'Full Size', 'simple-image-widget' ),
				'imageSizeNames'  => self::get_image_size_names(),
			),
		) );
	}

	/**
	 * Enqueue scripts needed for selecting media.
	 *
	 * @since 3.0.0
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'widgets.php' == $hook_suffix ) {
			wp_enqueue_media();
		}

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
		return apply_filters( 'image_size_names_choose', array(
			'thumbnail' => __( 'Thumbnail', 'simple-image-widget' ),
			'medium'    => __( 'Medium', 'simple-image-widget' ),
			'large'     => __( 'Large', 'simple-image-widget' ),
			'full'      => __( 'Full Size', 'simple-image-widget' ),
		) );
	}
}
