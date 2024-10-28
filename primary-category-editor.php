<?php
/*
Plugin Name: Primary Category Editor
Description: Adds a column to the post list to view and edit the primary category, using Rank Math's primary category, and enables bulk editing.
Version: 1.1
Author: Your Name
*/

// Add Primary Category column to the post list
add_filter('manage_posts_columns', 'pce_add_primary_category_column');
function pce_add_primary_category_column($columns) {
    $columns['primary_category'] = 'Primary Category';
    return $columns;
}

// Display the Primary Category in the custom column
add_action('manage_posts_custom_column', 'pce_display_primary_category_column', 10, 2);
function pce_display_primary_category_column($column, $post_id) {
    if ($column == 'primary_category') {
        $primary_category = get_post_meta($post_id, 'rank_math_primary_category', true);
        
        // Display current primary category with a dropdown for inline editing
        $categories = get_categories(['hide_empty' => false]);
        echo '<select class="pce-primary-category-selector" data-post-id="' . esc_attr($post_id) . '">';
        foreach ($categories as $category) {
            $selected = ($category->term_id == $primary_category) ? 'selected' : '';
            echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
        }
        echo '</select>';
    }
}

// Add AJAX to update the primary category
add_action('wp_ajax_pce_update_primary_category', 'pce_update_primary_category');
function pce_update_primary_category() {
    check_ajax_referer('pce-primary-category-nonce', 'nonce');

    if (!current_user_can('edit_posts') || !isset($_POST['post_id']) || !isset($_POST['category_id'])) {
        wp_send_json_error('Invalid permissions or data');
    }

    $post_id = intval($_POST['post_id']);
    $category_id = intval($_POST['category_id']);

    // Update Rank Math's primary category
    update_post_meta($post_id, 'rank_math_primary_category', $category_id);

    wp_send_json_success();
}

// Enqueue AJAX script for inline editing
add_action('admin_enqueue_scripts', 'pce_enqueue_admin_scripts');
function pce_enqueue_admin_scripts($hook) {
    if ($hook != 'edit.php') {
        return;
    }
    wp_enqueue_script('pce-primary-category-edit', plugin_dir_url(__FILE__) . 'primary-category-edit.js', ['jquery'], null, true);
    wp_localize_script('pce-primary-category-edit', 'pcePrimaryCategoryAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pce-primary-category-nonce')
    ]);
}

// Add bulk action for setting primary category
add_action('bulk_actions-edit-post', 'pce_register_bulk_primary_category_action');
function pce_register_bulk_primary_category_action($bulk_actions) {
    $bulk_actions['set_primary_category'] = 'Set Primary Category';
    return $bulk_actions;
}

// Handle the bulk action for setting primary category
add_action('handle_bulk_actions-edit-post', 'pce_handle_bulk_primary_category_action', 10, 3);
function pce_handle_bulk_primary_category_action($redirect_to, $action, $post_ids) {
    if ($action !== 'set_primary_category') {
        return $redirect_to;
    }

    if (!isset($_REQUEST['bulk_primary_category']) || empty($_REQUEST['bulk_primary_category'])) {
        return $redirect_to;
    }

    $new_primary_category = intval($_REQUEST['bulk_primary_category']);

    foreach ($post_ids as $post_id) {
        update_post_meta($post_id, 'rank_math_primary_category', $new_primary_category);
    }

    $redirect_to = add_query_arg('bulk_primary_category_updated', count($post_ids), $redirect_to);
    return $redirect_to;
}

// Add a notice after bulk update
add_action('admin_notices', 'pce_bulk_action_admin_notice');
function pce_bulk_action_admin_notice() {
    if (!empty($_REQUEST['bulk_primary_category_updated'])) {
        $updated_count = intval($_REQUEST['bulk_primary_category_updated']);
        printf('<div id="message" class="updated notice is-dismissible"><p>' .
            _n('%s post\'s primary category updated.', '%s posts\' primary categories updated.', $updated_count, 'primary-category-editor') .
            '</p></div>', $updated_count);
    }
}

// Add the bulk primary category dropdown in the bulk edit area
add_action('admin_footer-edit.php', 'pce_bulk_primary_category_dropdown');
function pce_bulk_primary_category_dropdown() {
    global $post_type;
    if ($post_type === 'post') {
        $categories = get_categories(['hide_empty' => false]);
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var bulkActionsSelectTop = $("select[name='action']");
                var bulkActionsSelectBottom = $("select[name='action2']");

                // Create the dropdown and add it to both bulk action bars
                var categoryDropdown = $("<select name='bulk_primary_category'></select>");
                categoryDropdown.append($("<option>").val("").text("Select Primary Category"));
                <?php foreach ($categories as $category) : ?>
                    categoryDropdown.append($("<option>").val("<?php echo esc_attr($category->term_id); ?>").text("<?php echo esc_html($category->name); ?>"));
                <?php endforeach; ?>

                // Append the dropdown to both bulk action bars
                bulkActionsSelectTop.after(categoryDropdown.clone());
                bulkActionsSelectBottom.after(categoryDropdown.clone());
            });
        </script>
        <?php
    }
}
