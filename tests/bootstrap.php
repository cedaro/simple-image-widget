<?php
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( false === $_tests_dir ) {
	$_tests_dir = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/tests/phpunit';
}

define( 'SIW_DIR_TESTDATA', dirname( __FILE__ ) . '/data/' );

$GLOBALS['wp_tests_options'] = array(
    'active_plugins'  => array(
        'simple-image-widget/simple-image-widget.php',
    ),
    'timezone_string' => 'America/Los_Angeles',
);

function _manually_load_plugin() {
	require( dirname( __FILE__ ) . '/../simple-image-widget.php' );
}

require_once( $_tests_dir . '/includes/functions.php' );
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
require( $_tests_dir . '/includes/bootstrap.php' );
