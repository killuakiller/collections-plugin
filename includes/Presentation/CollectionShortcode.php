<?php
namespace Collections\Presentation;

use Collections\Engine\CollectionEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CollectionShortcode {
	public static function render( $atts ) {
		$atts = shortcode_atts( [
			'id' => 0,
		], $atts );

		$collection_id = intval( $atts['id'] );
		if ( ! $collection_id ) {
			return '';
		}

		// Đọc params từ URL ($_GET) khi tải trang lần đầu
		$params = [
			'paged'   => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1 ),
			'orderby' => isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : '',
			'tag'     => isset( $_GET['tag'] ) ? sanitize_text_field( $_GET['tag'] ) : '',
		];

		$result = CollectionEngine::render( $collection_id, $params );

		ob_start();
		?>
		<div class="collection-layout" data-collection-id="<?php echo esc_attr( $collection_id ); ?>">
			<?php if ( ! empty( $result['sidebar'] ) ) : ?>
				<div class="collection-sidebar">
					<?php echo $result['sidebar']; ?>
				</div>
			<?php endif; ?>
			<div class="collection-main">
				<div class="collection-toolbar-container">
					<?php echo $result['toolbar']; ?>
				</div>
				<div class="collection-grid-container">
					<?php echo $result['grid']; ?>
				</div>
				<div class="collection-pagination-container">
					<?php echo $result['pagination']; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the Collections Hub page: lists all published collections,
	 * grouped by "collection_group" taxonomy (Matrix Group), ordered by
	 * the _term_order meta set on each group.
	 */
	public static function render_hub( $atts ) {
		$groups = get_terms( [
			'taxonomy'   => 'collection_group',
			'hide_empty' => false,
		] );

		if ( is_wp_error( $groups ) ) {
			$groups = [];
		}

		usort( $groups, function( $a, $b ) {
			$order_a = (int) get_term_meta( $a->term_id, '_term_order', true );
			$order_b = (int) get_term_meta( $b->term_id, '_term_order', true );
			return $order_a <=> $order_b;
		} );

		$rendered_ids = [];

		ob_start();
		?>
		<div class="collections-hub">
			<?php foreach ( $groups as $group ) :
				$collections = get_posts( [
					'post_type'      => 'collection',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'tax_query'      => [
						[
							'taxonomy' => 'collection_group',
							'field'    => 'term_id',
							'terms'    => $group->term_id,
						],
					],
				] );

				if ( empty( $collections ) ) {
					continue;
				}

				foreach ( $collections as $c ) {
					$rendered_ids[] = $c->ID;
				}
				?>
				<div class="collection-hub-group">
					<h2 class="collection-hub-group-title"><?php echo esc_html( $group->name ); ?></h2>
					<div class="collection-hub-grid">
						<?php foreach ( $collections as $collection_post ) : ?>
							<a class="collection-hub-card" href="<?php echo esc_url( get_permalink( $collection_post ) ); ?>">
								<?php if ( has_post_thumbnail( $collection_post ) ) : ?>
									<span class="collection-hub-card-image">
										<?php echo get_the_post_thumbnail( $collection_post, 'medium' ); ?>
									</span>
								<?php endif; ?>
								<span class="collection-hub-card-title"><?php echo esc_html( get_the_title( $collection_post ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>

			<?php
			$ungrouped = get_posts( [
				'post_type'      => 'collection',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post__not_in'   => ! empty( $rendered_ids ) ? $rendered_ids : [ 0 ],
			] );

			if ( ! empty( $ungrouped ) ) :
				?>
				<div class="collection-hub-group">
					<?php if ( ! empty( $groups ) ) : ?>
						<h2 class="collection-hub-group-title"><?php esc_html_e( 'Other Collections', 'collections' ); ?></h2>
					<?php endif; ?>
					<div class="collection-hub-grid">
						<?php foreach ( $ungrouped as $collection_post ) : ?>
							<a class="collection-hub-card" href="<?php echo esc_url( get_permalink( $collection_post ) ); ?>">
								<?php if ( has_post_thumbnail( $collection_post ) ) : ?>
									<span class="collection-hub-card-image">
										<?php echo get_the_post_thumbnail( $collection_post, 'medium' ); ?>
									</span>
								<?php endif; ?>
								<span class="collection-hub-card-title"><?php echo esc_html( get_the_title( $collection_post ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( empty( $groups ) && empty( $ungrouped ) ) : ?>
				<p><?php esc_html_e( 'No collections found.', 'collections' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
