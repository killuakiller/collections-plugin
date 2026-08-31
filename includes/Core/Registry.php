<?php
namespace Collections\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry {

	public static function get( $collection_id ) {
		$post = get_post( $collection_id );
		if ( ! $post || 'collection' !== $post->post_type ) {
			return null;
		}

		$posts_per_page = 12;
		$tax_query      = [];

		// Lấy dữ liệu serialized từ meta _collection_cats
		$raw_cats = Meta::get( $post->ID, '_collection_cats' );
		
		// Xử lý giải mã serialized array hoặc mảng thông thường
		if ( ! empty( $raw_cats ) ) {
			$cat_ids = is_array( $raw_cats ) ? $raw_cats : maybe_unserialize( $raw_cats );
			
			if ( ! empty( $cat_ids ) && is_array( $cat_ids ) ) {
				$tax_query[] = [
					'taxonomy' => 'product_cat',
					'field'    => 'term_id', // Dùng term_id thay vì slug vì database lưu ID 237
					'terms'    => array_map( 'intval', $cat_ids ),
					'operator' => 'IN',
				];
			}
		}

		// Lấy _collection_tags (chuỗi tên tag, phân cách bởi dấu phẩy)
		$raw_tags = Meta::get( $post->ID, '_collection_tags' );

		if ( ! empty( $raw_tags ) && is_string( $raw_tags ) ) {
			$tag_slugs = array_values( array_filter( array_map(
				'sanitize_title',
				explode( ',', $raw_tags )
			) ) );

			if ( ! empty( $tag_slugs ) ) {
				$tax_query[] = [
					'taxonomy' => 'product_tag',
					'field'    => 'slug',
					'terms'    => $tag_slugs,
					'operator' => 'IN',
				];
			}
		}

		// Fallback nếu không có cả category lẫn tag thì dùng slug của collection post
		if ( empty( $tax_query ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => [ $post->post_name ],
				'operator' => 'IN',
			];
		}

		$filters = [
			'tag'   => 'product_tag',
			'color' => 'pa_color',
			'size'  => 'pa_size',
		];

		return new Collection( [
			'id'             => $post->ID,
			'posts_per_page' => $posts_per_page,
			'tax_query'      => $tax_query,
			'filters'        => $filters,
		] );
	}
}