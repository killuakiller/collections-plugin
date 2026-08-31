<?php
namespace Collections\Engine;

use Collections\Core\Collection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QueryBuilder {

	private const SORTS = [
		'date' => [
			'orderby' => 'date',
			'order'   => 'DESC',
		],
		'price' => [
			'orderby'  => 'meta_value_num',
			'meta_key' => '_price',
			'order'    => 'ASC',
		],
		'price-desc' => [
			'orderby'  => 'meta_value_num',
			'meta_key' => '_price',
			'order'    => 'DESC',
		],
		'popularity' => [
			'orderby'  => 'meta_value_num',
			'meta_key' => 'total_sales',
			'order'    => 'DESC',
		],
		'rating' => [
			'orderby'  => 'meta_value_num',
			'meta_key' => '_wc_average_rating',
			'order'    => 'DESC',
		],
		'title' => [
			'orderby' => 'title',
			'order'   => 'ASC',
		],
	];

	public function build_args( $collection, array $params = [] ): array {
		// Ép kiểu sang Collection object nếu lỡ nhận vào mảng
		if ( is_array( $collection ) ) {
			$collection = new Collection( $collection );
		}

		$posts_per_page = $collection->posts_per_page ?? 12;

		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged'          => max( 1, intval( $params['paged'] ?? 1 ) ),
		];

		$tax_queries = $collection->tax_query ?? [];

		if ( ! empty( $collection->filters ) ) {
			foreach ( $collection->filters as $param_key => $taxonomy ) {
				if ( ! empty( $params[ $param_key ] ) ) {
					$terms = array_filter(
						array_map(
							'sanitize_text_field',
							explode( ',', wp_unslash( $params[ $param_key ] ) )
						)
					);

					if ( ! empty( $terms ) ) {
						$tax_queries[] = [
							'taxonomy' => $taxonomy,
							'field'    => 'slug',
							'terms'    => $terms,
							'operator' => 'IN',
						];
					}
				}
			}
		}

		if ( ! empty( $tax_queries ) ) {
			$args['tax_query'] = [];
			if ( count( $tax_queries ) > 1 ) {
				$args['tax_query']['relation'] = 'AND';
			}
			foreach ( $tax_queries as $tq ) {
				$args['tax_query'][] = $tq;
			}
		}

		$orderby = $params['orderby'] ?? 'date';
		$sorting = self::SORTS[ $orderby ] ?? self::SORTS['date'];
		
		foreach ( $sorting as $key => $value ) {
			$args[ $key ] = $value;
		}

		return $args;
	}
}