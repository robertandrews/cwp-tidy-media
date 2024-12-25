<?php
function tidy_media_add_button()
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
add_action('manage_posts_extra_tablenav', 'tidy_media_add_button');

function tidy_media_ajax_handler()
{
    /**
     * Button AJAX Handler
     *
     * Handles the AJAX request to tidy up media for selected posts.
     * Checks the nonce to ensure security and passes an array of post IDs to do_saved_post() function.
     *
     * @return void
     */

    check_ajax_referer('tidy_media_nonce', 'nonce');
    $post_ids = $_POST['post_ids'];
    foreach ($post_ids as $post_id) {
        do_saved_post($post_id);
    }
    die();
}
add_action('wp_ajax_tidy_media', 'tidy_media_ajax_handler');
?>