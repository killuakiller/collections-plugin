<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $filters_data */
if ( empty( $filters_data ) ) {
	return;
}
?>
<div class="collection-filter-sidebar-wrapper">
	<h3 class="collection-sidebar-heading">FILTER</h3>

	<?php foreach ( $filters_data as $param => $filter_group ) : ?>
		<?php
		$selected_terms = [];
		if ( ! empty( $_REQUEST[ $param ] ) ) {
			$raw_param      = wp_unslash( $_REQUEST[ $param ] );
			$selected_terms = array_filter( explode( ',', sanitize_text_field( $raw_param ) ) );
		}
		
		$total_terms    = count( $filter_group['terms'] );
		// Trên mobile chỉ hiện đúng 1 dòng đầu tiên (2-3 tag tùy độ rộng màn hình). Nếu nhiều hơn thì bật hiệu ứng Collapse
		$is_collapsible = $total_terms > 3;
		?>
		<div class="collection-filter-group<?php echo $is_collapsible ? ' is-collapsible is-collapsed' : ''; ?>" data-filter-param="<?php echo esc_attr( $param ); ?>">
			<h4 class="collection-filter-title"><?php echo esc_html( $filter_group['label'] ); ?></h4>
			
			<div class="collection-filter-list-wrapper">
				<ul class="collection-filter-list">
					<?php foreach ( $filter_group['terms'] as $index => $term ) : ?>
						<?php $is_checked = in_array( $term->slug, $selected_terms, true ); ?>
						<li class="collection-filter-item<?php echo $index >= 12 ? ' collection-desktop-hidden' : ''; ?>">
							<label class="collection-filter-label">
								<input 
									type="checkbox" 
									class="collection-filter-checkbox" 
									data-param="<?php echo esc_attr( $param ); ?>" 
									value="<?php echo esc_attr( $term->slug ); ?>"
									<?php checked( $is_checked ); ?>
								/>
								<span class="collection-filter-term-name"><?php echo esc_html( $term->name ); ?></span>
								<span class="collection-filter-term-count">(<?php echo esc_html( $term->count ); ?>)</span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<?php if ( $is_collapsible ) : ?>
				<button type="button" class="collection-filter-more-toggle">
					<span class="collection-toggle-text-more">Show More (<?php echo ( $total_terms - 3 ); ?>) +</span>
					<span class="collection-toggle-text-less" style="display:none;">Show Less -</span>
				</button>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>