<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$collection_id = get_queried_object_id();

// Đọc tham số từ URL hiện tại
$params = [
	'paged'   => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1 ),
	'orderby' => isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : '',
	'tag'     => isset( $_GET['tag'] ) ? sanitize_text_field( $_GET['tag'] ) : '',
];

// Gọi qua CollectionEngine an toàn
$result = \Collections\Engine\CollectionEngine::render( $collection_id, $params );

// Nội dung SEO / Hero Banner được nhập ở Meta Box "Collection Rules & SEO Content Settings"
$hero_image_id = \Collections\Core\Meta::get( $collection_id, '_collection_hero_image' );
$top_text      = \Collections\Core\Meta::get( $collection_id, '_collection_top_text' );
$bottom_text   = \Collections\Core\Meta::get( $collection_id, '_collection_bottom_text' );

// Nội dung nhập trong Block Editor (Gutenberg) của chính bài Collection này.
// Nếu có, dùng nó thay cho tiêu đề mặc định, để người dùng toàn quyền
// chỉnh H1/căn lề bằng block Heading của Gutenberg.
$raw_editor_content = get_post_field( 'post_content', $collection_id );
$editor_content     = trim( $raw_editor_content ) !== '' ? apply_filters( 'the_content', $raw_editor_content ) : '';

// Mirrors archive-product.php: fire woocommerce_before_main_content so the
// active theme (Blocksy, Astra, Flatsome, GeneratePress, Kadence,
// Storefront...) prints its OWN content wrapper markup here, exactly as it
// would on a real Shop/product-archive page. Themes hook their container
// div/grid classes onto this action; a hand-rolled <div> here bypasses that
// entirely and is what caused the grid to fall back to browser defaults on
// themes that rely on it.
do_action( 'woocommerce_before_main_content' );
?>

	<?php if ( ! empty( $hero_image_id ) ) : ?>
		<div class="collection-hero">
			<?php echo wp_get_attachment_image( $hero_image_id, 'full', false, [ 'style' => 'width:100%; height:auto; display:block; border-radius:8px;' ] ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $editor_content ) ) : ?>
		<header class="collection-header collection-editor-content woocommerce-products-header" style="margin-bottom: 20px;">
			<?php echo $editor_content; ?>
		</header>
	<?php else : ?>
		<header class="collection-header woocommerce-products-header" style="margin-bottom: 20px;">
			<h1 class="collection-title woocommerce-products-header__title page-title" style="margin: 0; font-size: 28px; font-weight: bold;"><?php single_post_title(); ?></h1>
		</header>
	<?php endif; ?>

	<?php if ( ! empty( $top_text ) ) : ?>
		<div class="collection-top-intro" style="margin-bottom: 30px; font-size: 1.05em; color: #444;">
			<?php echo wp_kses_post( $top_text ); ?>
		</div>
	<?php endif; ?>

	<div class="collection-layout" data-collection-id="<?php echo esc_attr( $collection_id ); ?>">

		<!-- 0. Sidebar (Filter theo Tag/Color/Size) -->
		<?php if ( ! empty( $result['sidebar'] ) ) : ?>
			<div class="collection-sidebar">
				<?php echo $result['sidebar']; ?>
			</div>
		<?php endif; ?>

		<div class="collection-main">

			<!-- 1. Toolbar (Sắp xếp) -->
			<div class="collection-toolbar-container" style="margin-bottom: 20px; clear: both;">
				<?php echo isset( $result['toolbar'] ) ? $result['toolbar'] : ''; ?>
			</div>

			<!-- 2. Grid Sản Phẩm -->
			<div class="collection-grid-container" style="clear: both; width: 100%;">
				<?php echo isset( $result['grid'] ) ? $result['grid'] : ''; ?>
			</div>

			<!-- 3. Phân trang -->
			<div class="collection-pagination-container" style="margin-top: 30px; clear: both;">
				<?php echo isset( $result['pagination'] ) ? $result['pagination'] : ''; ?>
			</div>

		</div>

	</div>

	<!-- 4. Bottom SEO Article -->
	<?php if ( ! empty( $bottom_text ) ) : ?>
		<section class="collection-bottom-seo" style="margin-top: 60px; border-top: 1px solid #eee; padding-top: 30px;">
			<?php echo wp_kses_post( $bottom_text ); ?>
		</section>
	<?php endif; ?>

<?php
// Mirrors archive-product.php: closes whatever wrapper markup the theme
// opened on woocommerce_before_main_content above.
do_action( 'woocommerce_after_main_content' );

get_footer();
