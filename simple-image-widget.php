<?php
/**
 * Simple Image Widget
 *
 * @package   SimpleImageWidget
 * @author    Brady Vercher
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Simple Image Widget
 * Plugin URI:  https://wordpress.org/plugins/simple-image-widget/
 * Description: A simple image widget utilizing the new WordPress media manager.
 * Version:     4.4.2
 * Author:      Cedaro
 * Author URI:  https://www.cedaro.com/?utm_source=wordpress-plugin&utm_medium=link&utm_content=simple-image-widget-author-uri&utm_campaign=plugins
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-image-widget
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin instance.
 *
 * @since 4.0.0
 * @type Simple_Image_Widget $simple_image_widget
 */
global $simple_image_widget;

if ( ! defined( 'SIW_DIR' ) ) {
	/**
	 * Plugin directory path.
	 *
	 * @since 4.0.0
	 * @type string SIW_DIR
	 */
	define( 'SIW_DIR', plugin_dir_path( __FILE__ ) );
}

/**
 * Check if the installed version of WordPress supports the new media manager.
 *
 * @since 3.0.0
 */
function is_simple_image_widget_legacy() {
	/**
	 * Whether the installed version of WordPress supports the new media manager.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $is_legacy
	 */
	return apply_filters( 'is_simple_image_widget_legacy', version_compare( get_bloginfo( 'version' ), '3.4.2', '<=' ) );
}

/**
 * Include functions and libraries.
 */
require_once( SIW_DIR . 'includes/class-simple-image-widget.php' );
require_once( SIW_DIR . 'includes/class-simple-image-widget-legacy.php' );
require_once( SIW_DIR . 'includes/class-simple-image-widget-plugin.php' );
require_once( SIW_DIR . 'includes/class-simple-image-widget-template-loader.php' );

/**
 * Deprecated main plugin class.
 *
 * @since      3.0.0
 * @deprecated 4.0.0
 */
class Simple_Image_Widget_Loader extends Simple_Image_Widget_Plugin {}

// Initialize and load the plugin.
$simple_image_widget = new Simple_Image_Widget_Plugin();
add_action( 'plugins_loaded', array( $simple_image_widget, 'load' ) );
