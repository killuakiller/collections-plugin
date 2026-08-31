<?php
namespace Collections\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CollectionDuplicator
 *
 * Adds a "Duplicate" row action and bulk action to the Collections admin
 * list, modeled directly on how WooCommerce duplicates a Product:
 * - Row action triggers admin_action_{action}, then redirects straight
 *   into the edit screen of the new draft (same UX as
 *   admin_action_woocommerce_duplicate_product).
 * - Bulk action duplicates every selected row and redirects back to the
 *   list with a success notice.
 *
 * Duplicated: title, content, featured image, all plugin meta (SEO,
 * Collection Rules, Banner, Filter Rules, custom fields) and taxonomies
 * (including the "Groups" taxonomy). The duplicate is always created as
 * a Draft. Runtime-only data (edit locks, old-slug redirects) is
 * explicitly excluded — see $excluded_meta_keys.
 */
class CollectionDuplicator {

	const POST_TYPE    = 'collection';
	const ROW_ACTION    = 'collections_duplicate';
	const BULK_ACTION   = 'collections_duplicate';
	const NONCE_ACTION  = 'collections_duplicate_nonce';

	/**
	 * Meta keys that must never be copied onto the duplicate: WordPress
	 * editor-lock/runtime bookkeeping, not actual collection data.
	 *
	 * @var string[]
	 */
	private static $excluded_meta_keys = [
		'_thumbnail_id', // Copied separately via set_post_thumbnail().
		'_edit_lock',
		'_edit_last',
		'_wp_old_slug',
		'_wp_old_date',
	];

	public function register() {
		add_filter( 'post_row_actions', [ $this, 'add_row_action' ], 10, 2 );
		add_action( 'admin_action_' . self::ROW_ACTION, [ $this, 'handle_row_action' ] );

		add_filter( 'bulk_actions-edit-' . self::POST_TYPE, [ $this, 'add_bulk_action' ] );
		add_filter( 'handle_bulk_actions-edit-' . self::POST_TYPE, [ $this, 'handle_bulk_action' ], 10, 3 );

		add_action( 'admin_notices', [ $this, 'render_bulk_notice' ] );
	}

	/**
	 * @param string[] $actions
	 * @param \WP_Post $post
	 * @return string[]
	 */
	public function add_row_action( $actions, $post ) {
		if ( self::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			add_query_arg(
				[
					'action' => self::ROW_ACTION,
					'post'   => $post->ID,
				],
				admin_url( 'admin.php' )
			),
			self::NONCE_ACTION
		);

		$actions['duplicate'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $url ),
			esc_attr__( 'Duplicate this collection', 'collections' ),
			esc_html__( 'Duplicate', 'collections' )
		);

		return $actions;
	}

	/**
	 * Handles the single-row "Duplicate" link (admin.php?action=collections_duplicate&post=ID).
	 */
	public function handle_row_action() {
		if ( empty( $_GET['post'] ) ) {
			wp_die( esc_html__( 'No collection specified.', 'collections' ) );
		}

		$post_id = absint( $_GET['post'] );
		check_admin_referer( self::NONCE_ACTION );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to duplicate this collection.', 'collections' ) );
		}

		$new_id = self::duplicate( $post_id );

		if ( ! $new_id ) {
			wp_die( esc_html__( 'Could not duplicate this collection.', 'collections' ) );
		}

		// Same UX as WooCommerce's own product duplicate: go straight to
		// editing the new draft.
		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
		exit;
	}

	/**
	 * @param string[] $actions
	 * @return string[]
	 */
	public function add_bulk_action( $actions ) {
		$actions[ self::BULK_ACTION ] = __( 'Duplicate', 'collections' );
		return $actions;
	}

	/**
	 * @param string $redirect_to
	 * @param string $doaction
	 * @param int[]  $post_ids
	 * @return string
	 */
	public function handle_bulk_action( $redirect_to, $doaction, $post_ids ) {
		if ( self::BULK_ACTION !== $doaction ) {
			return $redirect_to;
		}

		$count = 0;
		foreach ( $post_ids as $post_id ) {
			if ( current_user_can( 'edit_post', $post_id ) && self::duplicate( $post_id ) ) {
				$count++;
			}
		}

		return add_query_arg( 'collections_duplicated', $count, $redirect_to );
	}

	public function render_bulk_notice() {
		if ( empty( $_GET['collections_duplicated'] ) ) {
			return;
		}

		$count = absint( $_GET['collections_duplicated'] );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of duplicated collections */
					_n( '%d collection duplicated.', '%d collections duplicated.', $count, 'collections' ),
					$count
				)
			)
		);
	}

	/**
	 * Duplicate a single Collection post.
	 *
	 * @param int $post_id Source collection ID.
	 * @return int|false New post ID on success, false on failure.
	 */
	public static function duplicate( $post_id ) {
		$original = get_post( $post_id );

		if ( ! $original || self::POST_TYPE !== $original->post_type ) {
			return false;
		}

		/* translators: %s: original collection title */
		$new_title = sprintf( __( '%s (Copy)', 'collections' ), $original->post_title );

		$new_id = wp_insert_post(
			[
				'post_title'     => $new_title,
				'post_name'      => sanitize_title( $new_title ), // wp_insert_post() de-duplicates this automatically if it already exists.
				'post_content'   => $original->post_content,
				'post_excerpt'   => $original->post_excerpt,
				'post_status'    => 'draft',
				'post_type'      => self::POST_TYPE,
				'post_author'    => get_current_user_id() ? get_current_user_id() : $original->post_author,
				'post_parent'    => $original->post_parent,
				'menu_order'     => $original->menu_order,
				'comment_status' => $original->comment_status,
				'ping_status'    => $original->ping_status,
			],
			true
		);

		if ( is_wp_error( $new_id ) || ! $new_id ) {
			return false;
		}

		// Featured image.
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $new_id, $thumbnail_id );
		}

		// All plugin meta: SEO, Collection Rules, Banner, Filter Rules,
		// custom fields — everything except the excluded runtime keys.
		$meta = get_post_meta( $post_id );
		foreach ( $meta as $key => $values ) {
			if ( in_array( $key, self::$excluded_meta_keys, true ) ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
			}
		}

		// Taxonomies, including the "Groups" (collection_group) taxonomy.
		foreach ( get_object_taxonomies( self::POST_TYPE ) as $taxonomy ) {
			$term_ids = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );
			if ( ! is_wp_error( $term_ids ) && ! empty( $term_ids ) ) {
				wp_set_object_terms( $new_id, $term_ids, $taxonomy );
			}
		}

		return $new_id;
	}
}
