<?php
function tidy_media_enqueue_scripts()
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
        wp_enqueue_script('tidy-media', plugin_dir_url(__FILE__) . 'js/origin-posts.js', array('jquery'), '1.0', true);
        wp_localize_script('tidy-media', 'tidy_media_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tidy_media_nonce'),
        ));
    }
}
add_action('admin_enqueue_scripts', 'tidy_media_enqueue_scripts');
