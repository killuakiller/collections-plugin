<?php
namespace Collections\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PostType {
    public function register() {
        // 1. Đăng ký Post Type
        register_post_type('collection', array(
            'labels'      => array(
                'name'               => __('Collections', 'collections'),
                'singular_name'      => __('Collection', 'collections'),
                'add_new'            => __('Add New Collection', 'collections'),
                'add_new_item'       => __('Add New Collection', 'collections'),
                'edit_item'          => __('Edit Collection', 'collections'),
                'new_item'           => __('New Collection', 'collections'),
                'view_item'          => __('View Collection', 'collections'),
                'search_items'       => __('Search Collections', 'collections'),
                'not_found'          => __('No collections found', 'collections'),
                'not_found_in_trash' => __('No collections found in Trash', 'collections'),
            ),
            'public'              => true,
            'has_archive'         => false,
            'rewrite'             => array('slug' => 'collections', 'with_front' => false),
            'supports'            => array('title', 'editor', 'thumbnail'),
            'menu_icon'           => 'dashicons-grid-view',
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_rest'        => true,
        ));

        // 2. Đăng ký Group Collection Taxonomy
        register_taxonomy('collection_group', 'collection', array(
            'labels' => array(
    'name'                       => __('Groups', 'collections'),
    'singular_name'              => __('Group', 'collections'),

    'menu_name'                  => __('Groups', 'collections'),

    'search_items'               => __('Search Groups', 'collections'),
    'popular_items'              => __('Popular Groups', 'collections'),
    'all_items'                  => __('All Groups', 'collections'),

    'parent_item'                => __('Parent Group', 'collections'),
    'parent_item_colon'          => __('Parent Group:', 'collections'),

    'edit_item'                  => __('Edit Group', 'collections'),
    'view_item'                  => __('View Group', 'collections'),
    'update_item'                => __('Update Group', 'collections'),

    'add_new_item'               => __('Add Group', 'collections'),
    'new_item_name'              => __('New Group Name', 'collections'),

    'not_found'                  => __('No Groups found', 'collections'),
),
            'hierarchical'      => true,
            'rewrite'           => array('slug' => 'collection-group'),
            'show_admin_column' => true,
            'show_in_rest'      => true,
        ));

        // 3. Đăng ký trường thứ tự cho Taxonomy & lưu dữ liệu
        add_action('collection_group_add_form_fields', [ $this, 'add_term_order_field' ]);
        add_action('collection_group_edit_form_fields', [ $this, 'edit_term_order_field' ], 10, 2);
        add_action('created_collection_group', [ $this, 'save_term_order' ]);
        add_action('edited_collection_group', [ $this, 'save_term_order' ]);

        // Kích hoạt template
        $this->register_template();
    }

    // Hiển thị ô nhập số khi Thêm mới Group
    public function add_term_order_field($taxonomy) {
        ?>
        <div class="form-field">
            <label for="term_order">Display Order (Thứ tự)</label>
            <input type="number" name="term_order" id="term_order" value="0">
            <p>Số càng nhỏ sẽ hiển thị càng lên trên (VD: 1, 2, 3...)</p>
        </div>
        <?php
    }

    // Hiển thị ô nhập số khi Sửa Group
    public function edit_term_order_field($term, $taxonomy) {
        $order = get_term_meta($term->term_id, '_term_order', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="term_order">Display Order (Thứ tự)</label></th>
            <td>
                <input type="number" name="term_order" id="term_order" value="<?php echo esc_attr($order); ?>">
                <p class="description">Số càng nhỏ sẽ hiển thị càng lên trên (VD: 1, 2, 3...)</p>
            </td>
        </tr>
        <?php
    }

    // Lưu lại giá trị số thứ tự
    public function save_term_order($term_id) {
        if (isset($_POST['term_order'])) {
            update_term_meta($term_id, '_term_order', intval($_POST['term_order']));
        }
    }

    public function register_template() {
        add_filter( 'single_template', function( $template ) {
            global $post;
            if ( $post && 'collection' === $post->post_type ) {
                $custom_template = COLLECTIONS_PATH . 'templates/single-collection.php';
                if ( file_exists( $custom_template ) ) {
                    return $custom_template;
                }
            }
            return $template;
        });
    }
}