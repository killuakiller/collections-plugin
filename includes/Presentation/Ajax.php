<?php
namespace Collections\Presentation;

use Collections\Engine\CollectionEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {
	public static function handle_filter() {
		check_ajax_referer( 'collections_filter_nonce', 'nonce' );

		$collection_id = isset( $_POST['collection_id'] ) ? intval( $_POST['collection_id'] ) : 0;
		if ( ! $collection_id ) {
			wp_send_json_error( [ 'message' => 'Invalid collection ID' ] );
		}

		// Đóng gói request thành mảng tham số tường minh
		$params = [
			'paged'   => isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1,
			'orderby' => isset( $_POST['orderby'] ) ? sanitize_text_field( $_POST['orderby'] ) : '',
			'tag'     => isset( $_POST['tag'] ) ? sanitize_text_field( $_POST['tag'] ) : '',
		];

		// Gọi trực tiếp CollectionEngine với mảng params sạch sẽ
		$result = CollectionEngine::render( $collection_id, $params );

		wp_send_json_success( [
			'grid'       => $result['grid'],
			'pagination' => $result['pagination'],
			'toolbar'    => $result['toolbar'],
		] );
	}
}