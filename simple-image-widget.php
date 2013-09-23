<?php
/**
 * Plugin Name: Simple Image Widget
 * Plugin URI: https://wordpress.org/extend/plugins/simple-image-widget/
 * Description: A simple image widget utilizing the new WordPress media manager.
 * Version: 3.0.4
 * Author: Blazer Six
 * Author URI: http://www.blazersix.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-image-widget
 * Domain Path: /languages
 *
 * @package SimpleImageWidget
 * @author Brady Vercher <brady@blazersix.com>
 * @copyright Copyright (c) 2012, Blazer Six, Inc.
 * @license GPL-2.0+
 */

/**
 * Include the image widget class early to make it easy to extend.
 */
require_once( plugin_dir_path( __FILE__ ) . 'class-simple-image-widget.php' );

/**
 * The main plugin class for loading the widget and attaching necessary hooks.
 *
 * @since 3.0.0
 */
class Simple_Image_Widget_Loader {
	/**
	 * Setup functionality needed by the widget.
	 *
	 * @since 3.0.0
	 */
	public static function load() {
		self::load_textdomain();
		add_action( 'widgets_init', array( __CLASS__, 'register_widget' ) );

		if ( is_simple_image_widget_legacy() ) {
			return;
		}

		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );
		add_action( 'admin_head-widgets.php', array( __CLASS__, 'admin_head_widgets' ) );
		add_action( 'admin_footer-widgets.php', array( __CLASS__, 'admin_footer_widgets' ) );
	}

	/**
	 * Plugin localization support.
	 *
	 * @since 3.0.0
	 */
	public static function load_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'simple-image-widget' );
		load_textdomain( 'simple-image-widget', WP_LANG_DIR . '/simple-image-widget/' . $locale . '.mo' );
		load_plugin_textdomain( 'simple-image-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Register and localize generic script libraries.
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
	public static function init() {
		wp_register_script( 'simple-image-widget', plugin_dir_url( __FILE__ ) . 'js/simple-image-widget.js', array( 'media-upload', 'media-views' ) );

		wp_localize_script( 'simple-image-widget', 'SimpleImageWidget', array(
			'frameTitle'      => __( 'Choose an Attachment', 'simple-image-widget' ),
			'frameUpdateText' => __( 'Update Attachment', 'simple-image-widget' ),
			'fullSizeLabel'   => __( 'Full Size', 'simple-image-widget' ),
			'imageSizeNames'  => self::get_image_size_names(),
		) );
	}

	/**
	 * Register the image widget.
	 *
	 * @since 3.0.0
	 */
	public static function register_widget() {
		register_widget( 'Simple_Image_Widget' );
	}

	/**
	 * Enqueue scripts needed for selecting media.
	 *
	 * @since 3.0.0
	 */
	public static function admin_scripts( $hook_suffix ) {
		if ( 'widgets.php' == $hook_suffix ) {
			wp_enqueue_media();
			wp_enqueue_script( 'simple-image-widget' );
		}
	}

	/**
	 * Output CSS for styling the image widget in the dashboard.
	 *
	 * @since 3.0.0
	 */
	public static function admin_head_widgets() {
		?>
		<style type="text/css">
		.widget .widget-inside .simple-image-widget-form .simple-image-widget-control { padding: 20px 0; text-align: center; border: 1px dashed #aaa;}
		.widget .widget-inside .simple-image-widget-form .simple-image-widget-control.has-image { padding: 10px; text-align: left; border: 1px dashed #aaa;}
		.widget .widget-inside .simple-image-widget-form .simple-image-widget-control img { display: block; margin-bottom: 10px; max-width: 100%; height: auto;}

		.simple-image-widget-legacy-fields { margin-bottom: 1em; padding: 10px; background-color: #e0e0e0; border-radius: 3px;}
		.simple-image-widget-legacy-fields p:last-child { margin-bottom: 0;}
		</style>
		<?php
	}

	/**
	 * Output custom handler for when an image is selected in the media manager.
	 *
	 * @since 3.0.0
	 */
	public static function admin_footer_widgets() {
		?>
		<script type="text/javascript">
		jQuery(function($) {
			$('#wpbody').on('selectionChange.simpleimagewidget', '.simple-image-widget-control', function( e, selection ) {
				var $control = $( e.target ),
					$sizeField = $control.closest('.simple-image-widget-form').find('select.image-size'),
					model = selection.first(),
					sizes = model.get('sizes'),
					size, image;

				if ( sizes ) {
					// The image size to display in the widget.
					size = sizes['post-thumbnail'] || sizes.medium;
				}

				if ( $sizeField.length ) {
					// Builds the option elements for the size dropdown.
					SimpleImageWidget.updateSizeDropdownOptions( $sizeField, sizes );
				}

				size = size || model.toJSON();

				image = $( '<img />', { src: size.url, width: size.width } );

				$control.find('img').remove().end()
					.prepend( image )
					.addClass('has-image')
					.find('a.simple-image-widget-control-choose').removeClass('button-hero');
			});
		});
		</script>
		<?php
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
add_action( 'plugins_loaded', array( 'Simple_Image_Widget_Loader', 'load' ) );

/**
 * Check to see if the current version of WordPress supports the new media manager.
 */
function is_simple_image_widget_legacy() {
	return version_compare( get_bloginfo( 'version' ), '3.4.2', '<=' );
}
