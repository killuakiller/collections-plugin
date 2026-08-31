<?php
namespace Collections\Core;

use Collections\Presentation\CollectionShortcode;
use Collections\Presentation\CollectionBreadcrumb;
use Collections\Presentation\Ajax;
use Collections\Presentation\Schema;
use Collections\Core\PostType;
use Collections\Core\Context;
use Collections\Admin\MetaBox;
use Collections\Admin\CollectionDuplicator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Plugin
 */
class Plugin {

	/**
	 * Initialize plugin hooks and shortcodes.
	 */
	public function init() {
		// 1. Đăng ký CPT và Matrix Group Taxonomy
		$post_type = new PostType();
		add_action( 'init', [ $post_type, 'register' ] );

		// 2. Đăng ký Admin MetaBox cho CPT
		if ( is_admin() ) {
			$metabox = new MetaBox();
			$metabox->register();

			// FEATURE: "Duplicate" row/bulk action on the Collections admin list.
			$duplicator = new CollectionDuplicator();
			$duplicator->register();
		}

		// Register shortcodes (new, official names)
		$shortcode = new CollectionShortcode();
		add_shortcode( 'collection', [ $shortcode, 'render' ] );
		add_shortcode( 'collections_hub', [ $shortcode, 'render_hub' ] ); // <-- Thêm dòng này để nhận diện Hub Page

		// Backward compatibility: keep old shortcode names working on existing content
		add_shortcode( 'meowik_collection', [ $shortcode, 'render' ] );
		add_shortcode( 'meowik_collections_hub', [ $shortcode, 'render_hub' ] );

		// Enqueue scripts & styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Register AJAX actions
		add_action( 'wp_ajax_collection_filter_collection', [ Ajax::class, 'handle_filter' ] );
		add_action( 'wp_ajax_nopriv_collection_filter_collection', [ Ajax::class, 'handle_filter' ] );

		// FEATURE: native "Home > Collections > Current Collection" breadcrumb.
		CollectionBreadcrumb::register();

		// FEATURE: ItemList schema (products in the collection) merged into
		// RankMath's JSON-LD graph on single Collection pages. No-ops when
		// RankMath isn't active.
		Schema::register();

		// -----------------------------------------------------------------
		// ITEM #6 FINDING (self-correction from a previous refactor pass):
		// A prior version of this plugin added a custom `body_class` filter
		// that manually appended 'woocommerce' / 'woocommerce-page' classes.
		// That was a duplicate re-implementation of logic WooCommerce Core
		// already owns (wc_body_class() in wc-template-functions.php), and
		// it only solved HALF the problem: many themes (Blocksy included)
		// decide whether to even ENQUEUE their WooCommerce grid CSS/JS
		// based on is_woocommerce() — a body class alone does not affect
		// that decision, since it runs long before body_class() is called.
		//
		// WooCommerce Core exposes exactly the extension point this plugin
		// needs for this: is_woocommerce() is defined as
		//     apply_filters( 'woocommerce_is_woocommerce_page', is_shop() || is_product_taxonomy() || ... )
		// Hooking this filter is the officially supported way third-party
		// code tells WooCommerce (and everything that reads is_woocommerce(),
		// including theme conditional asset loading, wc_body_class(), and
		// any other WooCommerce-aware plugin on the site) "treat this
		// request as a WooCommerce page." It is not specific to any one
		// theme, and it makes WooCommerce's own body-class logic apply
		// naturally instead of us reimplementing it.
		add_filter( 'woocommerce_is_woocommerce_page', [ $this, 'mark_collection_as_woocommerce_page' ] );
	}

	/**
	 * @param bool $is_woocommerce_page
	 * @return bool
	 */
	public function mark_collection_as_woocommerce_page( $is_woocommerce_page ) {
		if ( $is_woocommerce_page ) {
			return true;
		}

		return Context::is_collection_request();
	}

	/**
	 * Enqueue assets on frontend with automatic cache busting.
	 */
	public function enqueue_assets() {
		$css_path = COLLECTIONS_PATH . 'assets/css/collection.css';
		$js_path  = COLLECTIONS_PATH . 'assets/js/collection.js';

		// Auto versioning based on file modification time to bust browser & server cache
		$css_ver = file_exists( $css_path ) ? filemtime( $css_path ) : COLLECTIONS_VERSION;
		$js_ver  = file_exists( $js_path ) ? filemtime( $js_path ) : COLLECTIONS_VERSION;

		wp_enqueue_style(
			'collections-style',
			COLLECTIONS_URL . 'assets/css/collection.css',
			[],
			$css_ver
		);

		wp_enqueue_script(
			'collections-script',
			COLLECTIONS_URL . 'assets/js/collection.js',
			[],
			$js_ver,
			true
		);

		wp_localize_script(
			'collections-script',
			'collectionsData',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'collections_filter_nonce' ),
			]
		);
	}
}