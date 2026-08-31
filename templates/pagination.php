<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var \WP_Query $query */

if ( $query->max_num_pages <= 1 ) {
	return;
}

$current_page = isset( $_REQUEST['paged'] ) ? max( 1, intval( wp_unslash( $_REQUEST['paged'] ) ) ) : max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );
?>
<div class="collection-pagination" style="margin: 30px 0; text-align: center; clear: both;">
	<?php
	echo paginate_links( [
		'base'      => add_query_arg( 'paged', '%#%' ),
		'format'    => '',
		'current'   => $current_page,
		'total'     => $query->max_num_pages,
		'prev_text' => '&larr; Previous',
		'next_text' => 'Next &rarr;',
		'type'      => 'plain',
	] );
	?>
</div>
<style>
	.collection-pagination .page-numbers {
		display: inline-block;
		padding: 8px 14px;
		margin: 0 3px;
		border: 1px solid #ddd;
		text-decoration: none;
		color: #333;
		border-radius: 4px;
		background: #fff;
	}
	.collection-pagination .page-numbers.current {
		background-color: #222;
		color: #fff;
		border-color: #222;
	}
	.collection-pagination .page-numbers:hover {
		background-color: #f5f5f5;
		color: #111;
	}
</style>