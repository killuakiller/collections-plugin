<?php
namespace Collections\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Meta
 *
 * Reads post meta using the new "_collection_" prefix, transparently
 * falling back to the legacy "_meowik_" prefix used by older content
 * and migrating it to the new key the first time it is read.
 */
class Meta {

	/**
	 * Get post meta, migrating from the legacy "_meowik_" key if needed.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $key      New meta key, e.g. '_collection_hero_image'.
	 * @param bool   $single   Whether to return a single value.
	 * @return mixed
	 */
	public static function get( $post_id, $key, $single = true ) {
		$value = get_post_meta( $post_id, $key, $single );

		if ( ! self::is_empty( $value ) ) {
			return $value;
		}

		$legacy_key = str_replace( '_collection_', '_meowik_', $key );

		if ( $legacy_key === $key ) {
			return $value;
		}

		$legacy_value = get_post_meta( $post_id, $legacy_key, $single );

		if ( self::is_empty( $legacy_value ) ) {
			return $value;
		}

		// Migrate: write under the new key, remove the legacy one.
		update_post_meta( $post_id, $key, $legacy_value );
		delete_post_meta( $post_id, $legacy_key );

		return $legacy_value;
	}

	private static function is_empty( $value ) {
		return $value === '' || $value === false || $value === null || $value === array();
	}
}
