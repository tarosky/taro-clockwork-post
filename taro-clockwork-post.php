<?php
/*
Plugin Name: Taro Clockwork Post
Plugin URI: https://wordpress.org/plugins/taro-clockwork-post
Description: You can expire post with specified date.
Author: TAROSKY INC. <mng_wpcom@tarosky.co.jp>
Version: nightly
Author URI: https://tarosky.co.jp
Text Domain: tscp
Domain Path: /languages/
License: GPL v3 or later.
*/

defined( 'ABSPATH' ) or die();

// Register after plugins loaded
add_action( 'plugins_loaded', 'tscp_plugins_loaded' );

/**
 * Plugin bootstrap
 *
 * @internal
 * @package tscp
 */
function tscp_plugins_loaded() {
	load_plugin_textdomain( 'tscp', false, basename( dirname( __FILE__ ) ) . '/languages' );
	if ( version_compare( phpversion(), '5.6.0', '<' ) ) {
		add_action( 'admin_notices', 'tscp_plugin_notice' );
	} else {
		// Load all includes.
		require_once __DIR__ . '/includes/functions.php';
		require_once __DIR__ . '/includes/setting.php';
		require_once __DIR__ . '/includes/cron.php';
		require_once __DIR__ . '/includes/meta-box.php';
		require_once __DIR__ . '/includes/block-editor.php';
	}
}

/**
 * PHP version error
 *
 * @internal
 * @package tscp
 */
function tscp_plugin_notice() {
	/* translators: %s current php version */
	$message = sprintf( __( '[Taro Clockwork Post] This plugin requires PHP 5.6.0 and over but your %s.', 'tscp' ), phpversion() );
	printf( '<div class="error"><p>%s</p></div>', esc_html( $message ) );
}

/**
 * Get plugin version
 *
 * @package tscp
 * @return string
 */
function tscp_version() {
	static $info = null;
	if ( is_null( $info ) ) {
		$info = get_file_data( __FILE__, array(
			'version' => 'Version',
		) );
	}
	return $info['version'];
}

/**
 * Get plugin URL
 *
 * @package tscp
 * @param string $path Path to file. e.g. `css/admin.css`
 * @return string
 */
function tscp_asset_url( $path ) {
	return plugin_dir_url( __FILE__ ) . 'dist/' . ltrim( $path, '/' );
}
