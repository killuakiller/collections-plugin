<?php
namespace Collections\Presentation;

use Collections\Core\Registry;
use Collections\Engine\QueryBuilder;
use Collections\Presentation\CollectionBreadcrumb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects an ItemList schema (products in the collection) into RankMath's
 * JSON-LD graph on single Collection pages, so it ships as one coherent
 * @graph alongside RankMath's own Webpage/Breadcrumb entities instead of
 * a second, competing <script type="application/ld+json"> block.
 */
class Schema {
	public static function register() {
		if ( ! defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}

		add_filter( 'rank_math/json_ld', [ __CLASS__, 'add_collection_item_list' ], 20, 2 );
		add_filter( 'rank_math/json_ld', [ __CLASS__, 'add_breadcrumb_list' ], 20, 2 );
	}

	public static function add_collection_item_list( $data, $jsonld ) {
		if ( ! is_singular( 'collection' ) || ! class_exists( 'WooCommerce' ) ) {
			return $data;
		}

		$collection_id = get_queried_object_id();
		$collection    = Registry::get( $collection_id );
		if ( ! $collection ) {
			return $data;
		}

		$query_builder = new QueryBuilder();
		$args          = $query_builder->build_args( $collection, [] );

		// Schema is a summary of the collection, not a paginated listing —
		// cap it well above the default page size instead of following $paged.
		$args['posts_per_page'] = 20;
		$args['no_found_rows']  = true;

		$query = new \WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return $data;
		}

		$items    = [];
		$position = 1;

		foreach ( $query->posts as $product_post ) {
			$product = wc_get_product( $product_post );
			if ( ! $product ) {
				continue;
			}

			$image_id  = $product->get_image_id();
			$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';

			$items[] = [
				'@type'    => 'ListItem',
				'position' => $position++,
				'url'      => get_permalink( $product_post ),
				'item'     => array_filter( [
					'@type'  => 'Product',
					'name'   => $product->get_name(),
					'url'    => get_permalink( $product_post ),
					'description' => $product->get_description(),
					'image'  => $image_url,
					'offers' => [
						'@type'         => 'Offer',
						'url'           => get_permalink( $product_post ),
						'price'         => $product->get_price(),
						'priceCurrency' => get_woocommerce_currency(),
						'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
					],
				] ),
			];
		}

		wp_reset_postdata();

		if ( empty( $items ) ) {
			return $data;
		}

		$data['collection-item-list'] = [
			'@type'           => 'ItemList',
			'name'            => get_the_title( $collection_id ),
			'url'             => get_permalink( $collection_id ),
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		];

		return $data;
	}

	/**
	 * Mirrors the visual "Home > Collections > Current Collection" trail
	 * from CollectionBreadcrumb into a BreadcrumbList entity, independent
	 * of RankMath's own global Breadcrumbs setting (which controls RankMath's
	 * own visual breadcrumb markup, not this plugin's).
	 */
	public static function add_breadcrumb_list( $data, $jsonld ) {
		if ( ! is_singular( 'collection' ) ) {
			return $data;
		}

		$items = CollectionBreadcrumb::build_items();
		if ( empty( $items ) ) {
			return $data;
		}

		$list_items = [];
		foreach ( $items as $index => $item ) {
			$list_items[] = array_filter( [
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $item['label'],
				'item'     => $item['url'] ?: null,
			] );
		}

		$data['collection-breadcrumb-list'] = [
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list_items,
		];

		return $data;
	}
}
