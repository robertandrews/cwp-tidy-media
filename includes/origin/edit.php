<?php

function tidy_origin_edit_add_button()
{
    /**
     * Add Tidy Media Button
     *
     * Adds the Tidy Media button to the post list table navigation if the user is on the edit post screen or on any edit screen for a custom post type.
     *
     * @return void
     */

    $current_screen = get_current_screen();
    if ($current_screen->id === 'edit-post' || ($current_screen->base === 'edit' && isset($_GET['post_type']))) {
        ?>
<div class="alignleft actions">
    <button id="tidy-media-button" class="button"><?php _e('Tidy Media', 'tidy-media');?></button>
</div>
<?php
}
}
add_action('manage_posts_extra_tablenav', 'tidy_origin_edit_add_button');

function tidy_origin_edit_enqueue()
{
/**
 * Enqueue Posts Screen Scripts
 *
 * Enqueues the Tidy Media JavaScript and sets up localization for AJAX requests.
 * Only enqueues the script on the edit post screen or on any edit screen for a custom post type.
 *
 * @return void
 */
    $current_screen = get_current_screen();
    if ($current_screen->id === 'edit-post' || ($current_screen->base === 'edit' && isset($_GET['post_type']))) {
        wp_enqueue_script('tidy-media', plugins_url('assets/js/origin-edit.js', dirname(dirname(__FILE__))), array('jquery'), '1.0', true);
        wp_localize_script('tidy-media', 'tidy_media_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tidy_media_nonce'),
        ));
    }
}
add_action('admin_enqueue_scripts', 'tidy_origin_edit_enqueue');

function tidy_origin_edit_ajax_handler()
{
    /**
     * Button AJAX Handler
     *
     * Handles the AJAX request to tidy up media for selected posts.
     * Checks the nonce to ensure security and passes an array of post IDs to catch_saved_post() function.
     *
     * @return void
     */

    check_ajax_referer('tidy_media_nonce', 'nonce');
    $post_ids = $_POST['post_ids'];
    foreach ($post_ids as $post_id) {
        catch_saved_post($post_id);
    }
    die();
}
add_action('wp_ajax_tidy_media', 'tidy_origin_edit_ajax_handler');
