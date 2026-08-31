<?php
namespace Collections\Admin;

use Collections\Core\Meta;

class MetaBox {
    public function register() {
        add_action('add_meta_boxes', array($this, 'add_collection_metabox'));
        add_action('save_post', array($this, 'save_collection_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_uploader'));
    }

    public function enqueue_media_uploader($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'collection') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script('tags-suggest');
    }

    public function add_collection_metabox() {
        add_meta_box(
            'collection_rules',
            __('Collection Rules & SEO Content Settings', 'collections'),
            array($this, 'render_metabox'),
            'collection',
            'normal',
            'high'
        );
    }

    public function render_metabox($post) {
        $categories  = Meta::get($post->ID, '_collection_cats') ?: array();
        $tags        = Meta::get($post->ID, '_collection_tags') ?: '';
        $top_text    = Meta::get($post->ID, '_collection_top_text') ?: '';
        $bottom_text = Meta::get($post->ID, '_collection_bottom_text') ?: '';
        $hero_id     = Meta::get($post->ID, '_collection_hero_image') ?: '';
        $hero_url    = $hero_id ? wp_get_attachment_image_url($hero_id, 'medium') : '';

        wp_nonce_field('collection_save_meta', 'collection_meta_nonce');

        $woo_cats = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        ?>
        <div class="collection-metabox-wrapper" style="padding: 10px 0;">
            <h3 style="margin-top:0;">1. Hero Banner (Optional)</h3>
            <p class="description">Ảnh banner lớn hiển thị phía trên tiêu đề của trang Collection.</p>
            <div id="collection-hero-preview" style="margin-bottom:10px; max-width:400px;">
                <?php if ($hero_url): ?>
                    <img src="<?php echo esc_url($hero_url); ?>" style="max-width:100%; height:auto; border:1px solid #ccc; border-radius:4px;">
                <?php endif; ?>
            </div>
            <input type="hidden" name="collection_hero_image" id="collection_hero_image" value="<?php echo esc_attr($hero_id); ?>">
            <button type="button" class="button" id="collection-hero-upload-btn"><?php echo $hero_url ? 'Change Image' : 'Upload Image'; ?></button>
            <button type="button" class="button" id="collection-hero-remove-btn" style="<?php echo $hero_url ? '' : 'display:none;'; ?>">Remove Image</button>

            <hr style="margin: 20px 0;">
            <h3>2. Choose Product Filter Rules</h3>
            <p><strong>Select WooCommerce Categories:</strong></p>
            <input type="text" id="collection-cats-search" class="regular-text" style="width:100%; box-sizing:border-box; margin-bottom:8px;" placeholder="<?php echo esc_attr__( 'Search categories...', 'collections' ); ?>">
            <div style="max-height: 160px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #fff;" id="collection-cats-list">
                <?php if (!empty($woo_cats) && !is_wp_error($woo_cats)): ?>
                    <?php foreach($woo_cats as $cat): ?>
                        <label class="collection-cat-option" data-cat-name="<?php echo esc_attr( strtolower( $cat->name ) ); ?>" style="display:block; margin-bottom:4px;">
                            <input type="checkbox" name="collection_cats[]" value="<?php echo $cat->term_id; ?>" <?php checked(in_array($cat->term_id, $categories)); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </label>
                    <?php endforeach; ?>
                    <p style="margin:4px 0 0; color:#888; display:none;" id="collection-cats-no-match"><?php echo esc_html__( 'No matching categories.', 'collections' ); ?></p>
                <?php else: ?>
                    <p style="margin:0; color:#888;">No WooCommerce categories found.</p>
                <?php endif; ?>
            </div>

            <p style="margin-top:15px;"><strong>Product Tags (comma separated):</strong></p>
            <input type="text" name="collection_tags" id="collection-tags-input" data-wp-taxonomy="product_tag" value="<?php echo esc_attr($tags); ?>" style="width:100%;" placeholder="e.g. christmas, mama, veteran">
            <p class="description">Gõ để xem gợi ý các tag đã có sẵn trên site.</p>

            <hr style="margin: 20px 0;">
            <h3>3. SEO Content Areas</h3>
            <p><strong>Top Intro Text (Displays under Page Title & Hero Banner):</strong></p>
            <?php wp_editor($top_text, 'collection_top_text', array('textarea_rows' => 4)); ?>

            <p style="margin-top:15px;"><strong>Bottom SEO Article (Displays under Product Grid):</strong></p>
            <?php wp_editor($bottom_text, 'collection_bottom_text', array('textarea_rows' => 8)); ?>
        </div>
        <script>
        jQuery(function($) {
            if ($.fn.wpTagsSuggest) {
                $('#collection-tags-input').wpTagsSuggest();
            }

            var frame;
            $('#collection-hero-upload-btn').on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title: 'Select Hero Banner Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#collection_hero_image').val(attachment.id);
                    $('#collection-hero-preview').html('<img src="' + attachment.url + '" style="max-width:100%; height:auto; border:1px solid #ccc; border-radius:4px;">');
                    $('#collection-hero-upload-btn').text('Change Image');
                    $('#collection-hero-remove-btn').show();
                });
                frame.open();
            });
            $('#collection-hero-remove-btn').on('click', function(e) {
                e.preventDefault();
                $('#collection_hero_image').val('');
                $('#collection-hero-preview').html('');
                $('#collection-hero-upload-btn').text('Upload Image');
                $(this).hide();
            });

            $('#collection-cats-search').on('keyup input', function() {
                var term = $.trim($(this).val()).toLowerCase();
                var $options = $('#collection-cats-list .collection-cat-option');
                var visibleCount = 0;

                $options.each(function() {
                    var isMatch = $(this).data('cat-name').indexOf(term) !== -1;
                    $(this).toggle(isMatch);
                    if (isMatch) { visibleCount++; }
                });

                $('#collection-cats-no-match').toggle(visibleCount === 0);
            });
        });
        </script>
        <?php
    }

    public function save_collection_meta($post_id) {
        if (!isset($_POST['collection_meta_nonce']) || !wp_verify_nonce($_POST['collection_meta_nonce'], 'collection_save_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (isset($_POST['collection_hero_image'])) {
            $hero_id = intval($_POST['collection_hero_image']);
            if ($hero_id > 0) {
                update_post_meta($post_id, '_collection_hero_image', $hero_id);
            } else {
                delete_post_meta($post_id, '_collection_hero_image');
            }
        }

        if (isset($_POST['collection_cats'])) {
            update_post_meta($post_id, '_collection_cats', array_map('intval', $_POST['collection_cats']));
        } else {
            delete_post_meta($post_id, '_collection_cats');
        }

        if (isset($_POST['collection_tags'])) {
            update_post_meta($post_id, '_collection_tags', sanitize_text_field($_POST['collection_tags']));
        }

        if (isset($_POST['collection_top_text'])) {
            update_post_meta($post_id, '_collection_top_text', wp_kses_post($_POST['collection_top_text']));
        }

        if (isset($_POST['collection_bottom_text'])) {
            update_post_meta($post_id, '_collection_bottom_text', wp_kses_post($_POST['collection_bottom_text']));
        }
    }
}