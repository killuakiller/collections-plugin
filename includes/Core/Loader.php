<?php
namespace Collections\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Loader
 * 
 * Simple SPL autoloader for the plugin.
 */
class Loader {

	/**
	 * Register the autoloader.
	 */
	public static function register_autoloader() {
		spl_autoload_register( [ __CLASS__, 'autoload' ] );
	}

	/**
	 * Autoload callback.
	 *
	 * @param string $class The fully-qualified class name.
	 */
	public static function autoload( $class ) {
		// Plugin namespace prefix
		$prefix = 'Collections\\';

		// Base directory for the namespace prefix
		$base_dir = COLLECTIONS_PATH . 'includes/';

		// Does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			// No, move to the next registered autoloader
			return;
		}

		// Get the relative class name
		$relative_class = substr( $class, $len );

		// Replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// If the file exists, require it
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
}