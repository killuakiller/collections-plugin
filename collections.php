<?php
/**
 * Plugin Name: Collections
 * Description: Generic reusable Collections plugin.
 * Version: 1.0.1
 * Author: ClothesVibes
 * Text Domain: collections
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Plugin Constants
define( 'COLLECTIONS_VERSION', '1.0.1' );
define( 'COLLECTIONS_PATH', plugin_dir_path( __FILE__ ) );
define( 'COLLECTIONS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare compatibility with WooCommerce features (e.g., HPOS).
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

// Require the autoloader manually since we are not using Composer
require_once COLLECTIONS_PATH . 'includes/Core/Loader.php';

/**
 * Initialize the plugin.
 */
function collections_init() {
	// Ensure WooCommerce is active before doing anything
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// Register the SPL autoloader
	\Collections\Core\Loader::register_autoloader();

	// Boot the core plugin class
	$plugin = new \Collections\Core\Plugin();
	$plugin->init();
}
add_action( 'plugins_loaded', 'collections_init' );