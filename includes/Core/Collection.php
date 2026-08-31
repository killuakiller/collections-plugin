<?php
namespace Collections\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Collection {
	public readonly int $id;
	public readonly int $posts_per_page;
	public readonly array $tax_query;
	public readonly array $filters;

	public function __construct( array $data ) {
		$this->id             = isset( $data['id'] ) ? intval( $data['id'] ) : 0;
		$this->posts_per_page = isset( $data['posts_per_page'] ) ? intval( $data['posts_per_page'] ) : 12;
		$this->tax_query      = isset( $data['tax_query'] ) && is_array( $data['tax_query'] ) ? $data['tax_query'] : [];
		$this->filters        = isset( $data['filters'] ) && is_array( $data['filters'] ) ? $data['filters'] : [];
	}
}