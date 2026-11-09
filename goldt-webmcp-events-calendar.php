<?php
/**
 * Plugin Name: Goldnat for The Events Calendar
 * Plugin URI: https://goldnat.ai/plugins/goldt-webmcp-events-calendar
 * Description: The Events Calendar integration for Goldnat — exposes events, venues and organizers as AI tools via the Goldnat WebMCP Bridge.
 * Version: 1.0.1
 * Author: chgold
 * Author URI: https://goldnat.ai/
 * License: Proprietary
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: goldt-webmcp-bridge, the-events-calendar
 * Text Domain: goldt-webmcp-events-calendar
 * @package GoldtWebMCP\Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GOLDTWMCP_EVENTS_VERSION', '1.0.1' );
define( 'GOLDTWMCP_EVENTS_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Verify the free Bridge core + The Events Calendar are present.
 *
 * @return bool
 */
function goldtwmcp_events_check_dependencies() {
	if ( ! defined( 'GOLDTWMCP_VERSION' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>Goldnat for The Events Calendar:</strong> Requires the free Goldnat WebMCP Bridge plugin.</p></div>';
		} );
		return false;
	}
	if ( ! class_exists( 'Tribe__Events__Main' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>Goldnat for The Events Calendar:</strong> Requires The Events Calendar plugin.</p></div>';
		} );
		return false;
	}
	return true;
}

add_action( 'plugins_loaded', function () {
	if ( ! goldtwmcp_events_check_dependencies() ) {
		return;
	}
	add_action( 'goldtwmcp_register_modules', 'goldtwmcp_events_register_modules' );
}, 30 );

/**
 * Instantiate + register the Events module.
 *
 * @param \GoldtWebMCP\Core\GoldtWebMCP $goldtwmcp Bridge instance.
 * @return void
 */
function goldtwmcp_events_register_modules( $goldtwmcp ) {
	require_once GOLDTWMCP_EVENTS_PATH . 'includes/modules/class-events-module.php';
	$manifest = $goldtwmcp->get_manifest_instance();
	$module   = new \GoldtWebMCP\Modules\Events_Module( $manifest );
	$goldtwmcp->register_external_module( 'events', $module );
}
