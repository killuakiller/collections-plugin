<?php
namespace Collections\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Context
 *
 * Detects whether the current request is rendering collection content,
 * either via the single "collection" CPT template or via the
 * [collection] / [collections_hub] shortcodes (and their legacy
 * meowik_* aliases) embedded in an arbitrary page/post.
 */
class Context {

	public static function is_collection_request() {
		if ( is_singular( 'collection' ) ) {
			return true;
		}

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				foreach ( [ 'collection', 'collections_hub', 'meowik_collection', 'meowik_collections_hub' ] as $tag ) {
					if ( has_shortcode( $post->post_content, $tag ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}
}
