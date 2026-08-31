<?php
use Collections\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var \WP_Query $query */

$current_orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'date';
$settings        = new Settings();
$sorting_options = $settings->get( 'sorting', [] );
?>
<div class="collection-toolbar" style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
	<form method="get" class="woocommerce-ordering">
		<select name="orderby" id="collection-orderby" class="orderby" aria-label="<?php esc_attr_e( 'Shop order', 'woocommerce' ); ?>">
			<?php foreach ( $sorting_options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_orderby, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		
		<?php 
		// Giữ lại các tham số GET khác, hỗ trợ cả trường hợp value là mảng (array) an toàn tuyệt đối
		foreach ( $_REQUEST as $key => $val ) {
			if ( in_array( $key, [ 'orderby', 'page', 'paged', 'action', 'collection_id' ], true ) ) {
				continue;
			}
			
			if ( is_array( $val ) ) {
				foreach ( $val as $nested_val ) {
					echo '<input type="hidden" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( sanitize_text_field( wp_unslash( $nested_val ) ) ) . '" />';
				}
			} else {
				echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $val ) ) ) . '" />';
			}
		}
		?>
	</form>
</div>