<?php
namespace Collections\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ShopLoop
 *
 * Replicates exactly what WooCommerce's own archive-product.php does when
 * it renders the product loop, so that any theme built for native
 * WooCommerce archives (Blocksy, Astra, GeneratePress, Kadence,
 * Storefront, Flatsome...) renders the grid identically here.
 *
 * Without wc_setup_loop(), wc_get_loop_prop('columns') returns null and
 * WooCommerce's own loop/loop-start.php template outputs an EMPTY
 * "columns-" class (instead of "columns-4"). Most modern themes target
 * ".products.columns-4" in their CSS Grid rules, so that missing number
 * is what breaks the grid — not a missing stylesheet.
 */
class ShopLoop {

	/**
	 * Default WooCommerce callbacks we temporarily detach while this
	 * plugin's own toolbar/pagination markup is on screen, so we don't
	 * end up with two sorting dropdowns or two pagination widgets.
	 *
	 * This is NOT a plugin-specific hack: it is the exact same pattern
	 * WooCommerce Core itself uses in class-wc-shortcode-products.php
	 * (the [products] shortcode) — remove_action() right before firing
	 * woocommerce_before_shop_loop / woocommerce_after_shop_loop, then
	 * add_action() immediately after. Any theme or plugin callback
	 * hooked at a different priority than these three still fires
	 * normally. Because remove/add happens within the same PHP request
	 * before control returns to WordPress, native Shop/category pages
	 * rendered later in the same request (or on the next request) are
	 * completely unaffected.
	 */
	private static $suspended_before = [
		[ 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 ],
		[ 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 ],
	];

	private static $suspended_after = [
		[ 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 ],
	];

	/**
	 * Open the loop: mirrors woocommerce_before_shop_loop() +
	 * woocommerce_product_loop_start() from archive-product.php.
	 *
	 * Only call this when $query actually has posts — see render_loop()
	 * below, which is the entry point templates/grid.php uses. Core's own
	 * archive-product.php (and its own [products] shortcode class,
	 * WC_Shortcode_Products) never fires woocommerce_before_shop_loop /
	 * woocommerce_after_shop_loop at all when there are zero results —
	 * it fires woocommerce_no_products_found instead. Firing an empty
	 * "columns-4" <ul> with no products, and no message, is NOT what
	 * Core does and is NOT what themes are styled to expect.
	 *
	 * @param \WP_Query $query
	 * @param bool      $is_filtered Whether an active filter (e.g. tag) narrowed this query.
	 */
	public static function open( \WP_Query $query, $is_filtered = false ) {
		wc_setup_loop( [
			'name'         => 'product',
			'is_shortcode' => true, // This is a plugin-driven loop, not the main WP query.
			'is_paginated' => true,
			'is_filtered'  => (bool) $is_filtered,
			'total'        => (int) $query->found_posts,
			'total_pages'  => (int) $query->max_num_pages,
			'per_page'     => (int) $query->get( 'posts_per_page' ),
			'current_page' => max( 1, (int) $query->get( 'paged', 1 ) ),
			// wc_setup_loop() already defaults 'columns' to
			// wc_get_default_products_per_row() when omitted, which reads
			// the active theme's own add_theme_support('woocommerce')
			// product_grid config. Passed explicitly here only for
			// clarity/auditability, not to override anything.
			'columns'      => wc_get_default_products_per_row(),
		] );

		self::detach( self::$suspended_before );
		do_action( 'woocommerce_before_shop_loop' );
		self::reattach( self::$suspended_before );

		woocommerce_product_loop_start();
	}

	/**
	 * Render a single product card. Call this once per post, after
	 * $query->the_post(), exactly like archive-product.php's own loop.
	 */
	public static function render_product() {
		do_action( 'woocommerce_shop_loop' );
		wc_get_template_part( 'content', 'product' );
	}

	/**
	 * Close the loop: mirrors woocommerce_product_loop_end() +
	 * woocommerce_after_shop_loop() + wc_reset_loop() from
	 * archive-product.php.
	 */
	public static function close() {
		woocommerce_product_loop_end();

		self::detach( self::$suspended_after );
		do_action( 'woocommerce_after_shop_loop' );
		self::reattach( self::$suspended_after );

		wc_reset_loop();
	}

	/**
	 * Full loop lifecycle in one call — this is what templates/grid.php
	 * uses. Branches exactly like archive-product.php / WC_Shortcode_Products:
	 * - has posts  -> before_shop_loop, loop_start, each item, loop_end, after_shop_loop
	 * - no posts   -> woocommerce_no_products_found only (matches Core, lets
	 *                 the active theme style the "no products" message the
	 *                 same way it styles an empty Shop/category page).
	 *
	 * @param \WP_Query $query
	 * @param bool      $is_filtered
	 */
	public static function render_loop( \WP_Query $query, $is_filtered = false ) {
		if ( $query->have_posts() ) {
			self::open( $query, $is_filtered );

			while ( $query->have_posts() ) {
				$query->the_post();
				self::render_product();
			}

			self::close();
		} else {
			do_action( 'woocommerce_no_products_found' );
		}

		wp_reset_postdata();
	}

	private static function detach( array $callbacks ) {
		foreach ( $callbacks as [ $hook, $callback, $priority ] ) {
			remove_action( $hook, $callback, $priority );
		}
	}

	private static function reattach( array $callbacks ) {
		foreach ( $callbacks as [ $hook, $callback, $priority ] ) {
			add_action( $hook, $callback, $priority );
		}
	}
}
