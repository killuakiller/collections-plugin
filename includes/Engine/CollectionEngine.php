<?php
namespace Collections\Engine;

use Collections\Core\Registry;
use Collections\Core\Collection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CollectionEngine {
	public static function render( $collection_id, $params = [] ) {
		// 1. Lấy dữ liệu từ Registry
		$raw_collection = Registry::get( $collection_id );
		if ( ! $raw_collection ) {
			return [ 'grid' => '', 'pagination' => '', 'toolbar' => '', 'sidebar' => '' ];
		}

		// AN TOÀN TUYỆT ĐỐI: Nếu Registry trả về mảng, tự động bọc thành Collection Object
		$collection = ( $raw_collection instanceof Collection ) 
			? $raw_collection 
			: new Collection( is_array( $raw_collection ) ? $raw_collection : [ 'id' => $collection_id ] );

		// 2. Khởi tạo QueryBuilder và sinh $args
		$query_builder = new QueryBuilder();
		$args          = $query_builder->build_args( $collection, $params );
		
		// 3. Thực thi WP_Query trực tiếp
		$query = new \WP_Query( $args );

		$current_sort = $params['orderby'] ?? 'date';
		$tag          = $params['tag'] ?? '';
		$paged        = $params['paged'] ?? 1;

		ob_start();
		include COLLECTIONS_PATH . 'templates/toolbar.php';
		$toolbar_html = ob_get_clean();

		ob_start();
		include COLLECTIONS_PATH . 'templates/grid.php';
		$grid_html = ob_get_clean();

		// 3b. Xây dựng dữ liệu filter (tag/color/size...) cho sidebar
		// CHỈ lấy các tag thuộc những sản phẩm nằm trong đúng category của collection này,
		// không lấy tag của toàn bộ store.
		$filters_data = [];
		if ( ! empty( $collection->filters ) && is_array( $collection->filters ) ) {

			// Lấy toàn bộ ID sản phẩm thuộc category gốc của collection (chưa áp filter tag đang chọn)
			$scope_query = new \WP_Query( [
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => $collection->tax_query,
			] );
			$collection_product_ids = $scope_query->posts;

			if ( ! empty( $collection_product_ids ) ) {
				foreach ( $collection->filters as $param_key => $taxonomy ) {
					if ( ! taxonomy_exists( $taxonomy ) ) {
						continue;
					}

					// Chỉ lấy những term thực sự được gán cho các sản phẩm trong phạm vi collection
					$terms = get_terms( [
						'taxonomy'   => $taxonomy,
						'object_ids' => $collection_product_ids,
					] );

					if ( is_wp_error( $terms ) || empty( $terms ) ) {
						continue;
					}

					// get_terms() trả về count toàn store, nên tính lại count giới hạn trong collection
					foreach ( $terms as $term ) {
						$objects_with_term = get_objects_in_term( $term->term_id, $taxonomy );
						$term->count        = is_wp_error( $objects_with_term )
							? 0
							: count( array_intersect( $collection_product_ids, $objects_with_term ) );
					}

					$terms = array_values( array_filter( $terms, function( $t ) {
						return $t->count > 0;
					} ) );

					if ( empty( $terms ) ) {
						continue;
					}
					$priority = [
    'hoodie'     => 0,
    't-shirt'    => 1,
    'sweatshirt' => 2,
];

					usort( $terms, function ( $a, $b ) use ( $priority ) {

						$name_a = strtolower( trim( $a->name ) );
						$name_b = strtolower( trim( $b->name ) );

						$order_a = $priority[$name_a] ?? PHP_INT_MAX;
						$order_b = $priority[$name_b] ?? PHP_INT_MAX;

						if ( $order_a !== $order_b ) {
							return $order_a <=> $order_b;
						}

						return strcasecmp( $name_a, $name_b );
					} );
					

					$tax_object = get_taxonomy( $taxonomy );

					$filters_data[ $param_key ] = [
						'label' => $tax_object ? $tax_object->labels->singular_name : $param_key,
						'terms' => $terms,
					];
				}
			}
		}

		ob_start();
		include COLLECTIONS_PATH . 'templates/sidebar.php';
		$sidebar_html = ob_get_clean();

		$add_args = [];
		if ( ! empty( $tag ) ) {
			$add_args['tag'] = $tag;
		}
		if ( ! empty( $current_sort ) ) {
			$add_args['orderby'] = $current_sort;
		}

		$pagination_links = paginate_links( [
			'base'      => get_permalink( $collection_id ) . '%_%',
			'format'    => '?paged=%#%',
			'current'   => $paged,
			'total'     => $query->max_num_pages,
			'prev_text' => '&laquo; Prev',
			'next_text' => 'Next &raquo;',
			'add_args'  => $add_args,
		] );

		// Bọc trong cùng class mà collection.css / templates/pagination.php sử dụng
		// để phân trang được canh giữa và không bị vỡ layout (đứng dọc).
		$pagination_html = $pagination_links
			? '<div class="collection-pagination">' . $pagination_links . '</div>'
			: '';

		wp_reset_postdata();

		return [
			'grid'       => $grid_html,
			'pagination' => $pagination_html,
			'toolbar'    => $toolbar_html,
			'sidebar'    => $sidebar_html,
			'query'      => $query,
		];
	}
}