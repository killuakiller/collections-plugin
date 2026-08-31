<?php
namespace Collections\Presentation;

use Collections\Core\Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CollectionBreadcrumb
 *
 * Renders a native "Home > Collections > Current Collection" breadcrumb
 * on Collection pages, automatically — no Gutenberg block required.
 *
 * Integration strategy: WooCommerce Core itself hooks its own
 * woocommerce_breadcrumb() callback onto 'woocommerce_before_main_content'
 * at priority 20 (see wc-template-hooks.php). single-collection.php already
 * fires that same action (added during the theme-independence refactor).
 * So instead of hardcoding breadcrumb HTML inside single-collection.php,
 * this class swaps ITSELF in at the exact same hook + priority, but only
 * when the current request is a Collection page. Everywhere else on the
 * site, WooCommerce's native breadcrumb (or the active theme's own
 * replacement for it) is left completely untouched.
 */
class CollectionBreadcrumb {

	const HUB_URL_TRANSIENT = 'collections_hub_page_url';

	/**
	 * Wire up this class's own hooks. Called once from Plugin::init().
	 */
	public static function register() {
		add_action( 'wp', [ __CLASS__, 'maybe_override_default_breadcrumb' ] );

		// The hub URL is cached (see get_hub_url()); invalidate it whenever
		// a Page is saved, in case the [collections_hub] shortcode moved.
		add_action( 'save_post_page', [ __CLASS__, 'flush_hub_url_cache' ] );
	}

	/**
	 * On Collection requests only, replace WooCommerce's default breadcrumb
	 * callback with this class's render() method, at the same hook and
	 * priority WooCommerce Core itself uses.
	 */
	public static function maybe_override_default_breadcrumb() {
		if ( ! Context::is_collection_request() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		add_action( 'woocommerce_before_main_content', [ __CLASS__, 'render' ], 20 );
	}

	/**
	 * Echo the breadcrumb markup. Structure:
	 * Home > Collections > Current Collection Title
	 * "Collections" links to the Collections Hub page (auto-detected).
	 * The current collection is never a link.
	 */
	public static function render() {
		$items = apply_filters( 'collections_breadcrumb_items', self::build_items() );

		if ( empty( $items ) ) {
			return;
		}

		echo self::to_html( $items ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped per-item inside to_html().
	}

	/**
	 * Build the breadcrumb trail as a plain array, so it stays easy to
	 * filter/extend later without touching markup logic.
	 *
	 * @return array<int, array{label: string, url: string}>
	 */
	public static function build_items() {
		$items   = [];
		$items[] = [
			'label' => __( 'Home', 'collections' ),
			'url'   => home_url( '/' ),
		];
		$items[] = [
			'label' => __( 'Collections', 'collections' ),
			'url'   => self::get_hub_url(), // May be '' if no hub page is found yet — renders unlinked.
		];
		$items[] = [
			'label' => get_the_title(),
			'url'   => '', // Current collection: never clickable.
		];

		return $items;
	}

	/**
	 * Find the URL of the page that hosts the [collections_hub] shortcode
	 * (or its legacy [meowik_collections_hub] alias), cached for 12 hours.
	 *
	 * @return string Empty string if no hub page is found.
	 */
	protected static function get_hub_url() {
		$cached = get_transient( self::HUB_URL_TRANSIENT );
		if ( false !== $cached ) {
			return $cached;
		}

		$url      = '';
		$page_ids = get_posts( [
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		foreach ( $page_ids as $page_id ) {
			$content = get_post_field( 'post_content', $page_id );
			foreach ( [ 'collections_hub', 'meowik_collections_hub' ] as $tag ) {
				if ( has_shortcode( $content, $tag ) ) {
					$url = get_permalink( $page_id );
					break 2;
				}
			}
		}

		set_transient( self::HUB_URL_TRANSIENT, $url, 12 * HOUR_IN_SECONDS );

		return $url;
	}

	public static function flush_hub_url_cache() {
		delete_transient( self::HUB_URL_TRANSIENT );
	}

	/**
	 * Render the items array as HTML. The separator is filterable
	 * (`collections_breadcrumb_separator`) so this stays easy to
	 * restyle later without editing PHP.
	 *
	 * @param array $items
	 * @return string
	 */
	protected static function to_html( array $items ) {
		$separator  = apply_filters( 'collections_breadcrumb_separator', '<span class="collection-breadcrumb-sep">/</span>' );
		$last_index = count( $items ) - 1;
		$parts      = [];

		foreach ( $items as $index => $item ) {
			$is_current = ( $index === $last_index );
			$label      = esc_html( $item['label'] );

			if ( ! empty( $item['url'] ) && ! $is_current ) {
				$parts[] = sprintf( '<a href="%s">%s</a>', esc_url( $item['url'] ), $label );
			} else {
				$parts[] = sprintf(
					'<span class="collection-breadcrumb-current"%s>%s</span>',
					$is_current ? ' aria-current="page"' : '',
					$label
				);
			}
		}

		return sprintf(
			'<nav class="collection-breadcrumb woocommerce-breadcrumb" aria-label="%s">%s</nav>',
			esc_attr__( 'Breadcrumb', 'collections' ),
			implode( $separator, $parts )
		);
	}
}
