<?php
namespace Collections\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Settings
 * 
 * Loads and retrieves general plugin settings with static caching.
 */
class Settings {

	/**
	 * @var array|null
	 */
	private static $settings = null;

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		if ( self::$settings === null ) {
			$this->load_settings();
		}
	}

	/**
	 * Load settings from the config file.
	 */
	private function load_settings() {
		$file = COLLECTIONS_PATH . 'config/settings.php';
		if ( file_exists( $file ) ) {
			self::$settings = include $file;
		} else {
			self::$settings = [];
		}
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		if ( isset( self::$settings[ $key ] ) ) {
			return self::$settings[ $key ];
		}

		return $default;
	}
}