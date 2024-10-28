<?php
/*
Plugin Name: Primary Category Editor
Description: Adds a column to the post list to view and edit the primary category, using Rank Math's primary category.
Version: 1.0
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
