<?php
/**
Plugin Name: Taro Programmable Search
Plugin URI: https://wordpress.org/plugins/taro-programmable-search/
Description: Replace search feature with Google Programmable Search engine.
Author: Tarosky INC.
Version: nightly
Author URI: https://tarosky.co.jp/
License: GPL3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: tps
Domain Path: /languages
 */

defined( 'ABSPATH' ) or die();

/**
 * Init plugins.
 */
function tps_init() {
	// Register translations.
	load_plugin_textdomain( 'tps', false, basename( __DIR__ ) . '/languages' );
	// Composer.
	$composer = __DIR__ . '/vendor/autoload.php';
	if ( file_exists( $composer ) ) {
		// Boostrap.
		require $composer;
	}
	// Load hooks.
	require_once __DIR__ . '/includes/functions.php';
	require_once __DIR__ . '/includes/settings.php';
	require_once __DIR__ . '/includes/template.php';
	require_once __DIR__ . '/includes/widget.php';
}


// Register hooks.
add_action( 'plugins_loaded', 'tps_init' );
