<?php
/*
Plugin Name: Taro Clockwork Post
Plugin URI: https://wordpress.org/plugins/taro-clockwork-post
Description: You can expire post with specified date.
Author: TAROSKY INC. <mng_wpcom@tarosky.co.jp>
Version: nightly
Requires at least: 5.9
Requires PHP: 7.4
Author URI: https://tarosky.co.jp
Text Domain: taro-clockwork-post
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
	if ( version_compare( phpversion(), '7.4.0', '<' ) ) {
		add_action( 'admin_notices', 'tscp_plugin_notice' );
	} else {
		// Load all includes.
		require_once __DIR__ . '/includes/functions.php';
		require_once __DIR__ . '/includes/setting.php';
		require_once __DIR__ . '/includes/cron.php';
		require_once __DIR__ . '/includes/meta-box.php';
		require_once __DIR__ . '/includes/block-editor.php';
		// Register asset hook registration.
		add_action( 'init', 'tscp_register_assets' );
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
	$message = sprintf( __( '[Taro Clockwork Post] This plugin requires PHP 5.6.0 and over but your %s.', 'taro-clockwork-post' ), phpversion() );
	printf( '<div class="error"><p>%s</p></div>', esc_html( $message ) );
}

/**
 * Register all assets from wp-dependencies.json.
 *
 * @return void
 */
function tscp_register_assets() {
	$json = __DIR__ . '/wp-dependencies.json';
	if ( ! file_exists( $json ) ) {
		return;
	}
	$dependencies = json_decode( file_get_contents( $json ), true );
	if ( empty( $dependencies ) ) {
		return;
	}
	$base = trailingslashit( plugin_dir_url( __FILE__ ) );
	foreach ( $dependencies as $dep ) {
		if ( empty( $dep['path'] ) ) {
			continue;
		}
		$url = $base . $dep['path'];
		switch ( $dep['ext'] ) {
			case 'css':
				wp_register_style( $dep['handle'], $url, $dep['deps'], $dep['hash'], $dep['media'] );
				break;
			case 'js':
				$footer = [ 'in_footer' => $dep['footer'] ];
				if ( in_array( $dep['strategy'], [ 'defer', 'async' ], true ) ) {
					$footer['strategy'] = $dep['strategy'];
				}
				wp_register_script( $dep['handle'], $url, $dep['deps'], $dep['hash'], $footer );
				if ( in_array( 'wp-i18n', $dep['deps'], true ) ) {
					wp_set_script_translations( $dep['handle'], 'taro-clockwork-post' );
				}
				break;
		}
	}
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
