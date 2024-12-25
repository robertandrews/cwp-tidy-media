<?php
function my_trigger_notice($key = '')
{
    /**
     * Notice Query Sender
     *
     * my_trigger_notice - Adds a query arg to the redirect post location URL to trigger a notice.
     *
     *  This function adds a filter to the 'redirect_post_location' hook that modifies the URL of the post redirect location by adding a query arg with the specified notice key. This is useful for triggering notices after a post has been updated or created.
     *
     * @param string $key The notice key to add to the URL. Default is an empty string.
     *
     * @return void
     */

    global $pagenow;

    if ($pagenow === 'post.php') {

        add_filter(
            'redirect_post_location',
            function ($location) use ($key) {
                $key = sanitize_text_field($key);
                return add_query_arg(array('notice_key' => rawurlencode(sanitize_key($key))), $location);
            }
        );

    }

}

function my_admin_notices()
{
    /**
     * Notify Post Moved.
     *
     * Displays an admin notice with a specific message based on the notice key provided in the URL parameter.
     *
     * @since 1.0.0
     *
     * @return void
     */

    if (!isset($_GET['notice_key'])) {
        return;
    }
    $notice_key = wp_unslash(sanitize_text_field($_GET['notice_key']));
    $all_notices = [
        1 => 'Moved attached image to preferred folder',
        2 => 'Could not move attached image to preferred folder',
        3 => 'Attached image already in preferred media path - not moved',
        4 => 'Converted src for local img/s from absolute to relative URL',
    ];
    if (empty($all_notices[$notice_key])) {
        return;
    }
    if ($notice_key == 1) {
        $notice_class = "success";
    } elseif ($notice_key == 2) {
        $notice_class = "error";
    } elseif ($notice_key == 3) {
        $notice_class = "info";
    } elseif ($notice_key == 4) {
        $notice_class = "success";
    }
    ?>
<div class="notice notice-<?php echo $notice_class; ?> is-dismissible">
    <p><?php echo esc_html($all_notices[$notice_key]); ?>
    </p>
</div>
<?php
}
add_action('admin_notices', 'my_admin_notices');