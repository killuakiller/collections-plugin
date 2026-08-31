<?php
use Collections\Engine\ShopLoop;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var \WP_Query $query */
/** @var string $tag */

// Mirrors WooCommerce's own archive-product.php loop exactly — including
// its "no products found" branch — so every theme renders the grid (or the
// empty state) the same way it renders a native product archive.
// See ShopLoop.php for the full explanation of each step.
//
// NOTE: the previous hardcoded `<div class="collection-grid woocommerce">`
// wrapper has been removed. loop/loop-start.php already outputs the real
// `<ul class="products columns-X">` element that theme CSS grids target;
// wrapping it in an extra unrelated div only risked breaking a theme's
// CSS Grid/Flexbox parent-child relationship.
ShopLoop::render_loop( $query, ! empty( $tag ) );

