<?php

function get_media_objects_from_post_body($post_id)
{
    echo __FUNCTION__ . "\n";
    $body_media_elements = get_post_body_media_elements($post_id);

    // Initialize an array to hold all attachments
    $all_post_media = array();

    if (!empty($body_media_elements)) {
        foreach ($body_media_elements as $media_element) {
            // Check whether the element src is actually a stored media object in our WordPress
            $media_object = get_media_object_from_filepath($media_element['src']);
            if ($media_object) {
                // Add to attachments array
                $all_post_media[] = $media_object;
            }
        }
    }

    return $all_post_media;
}

function get_media_object_from_filepath($found_img_src)
{
    echo __FUNCTION__ . "\n";

    /**
     * Get Attachment From Filepath
     *
     * Gets the attachment object for a file given its URL path, which can be either:
     * - relative URL path (e.g. /wp-content/uploads/post/client/ghost-foundation/2020/09/rafat-ali-skift.jpg)
     * - absolute URL from current site (e.g. http://mysite.com/wp-content/uploads/2020/09/image.jpg)
     *
     * @param string $found_img_src The URL path of the file
     * @return WP_Post|void The WP_Post object representing the attachment, or void if the attachment ID was not found.
     */

    // If this is an absolute URL, verify it's from our site and convert to relative
    if (filter_var($found_img_src, FILTER_VALIDATE_URL)) {
        // Get site URL without protocol
        $site_url = preg_replace('#^https?://#', '', untrailingslashit(home_url()));
        // Get input URL without protocol
        $input_url = preg_replace('#^https?://#', '', $found_img_src);

        // Check if URL is from our site
        if (strpos($input_url, $site_url) !== 0) {
            do_my_log("❌ URL is not from current site: " . $found_img_src);
            return;
        }

        // Convert to relative by removing site URL
        $found_img_src = str_replace(home_url(), '', $found_img_src);
    }

    // Upload folder parts, used to generate attachment
    $uploads_base = trailingslashit(wp_upload_dir()['baseurl']); // http://context.local:8888/wp-content/uploads/
    $uploads_folder = str_replace(trailingslashit(home_url()), '', $uploads_base); // /wp-content/uploads/

    // Get file's attachment object
    $found_img_url = trailingslashit(get_site_url()) . ltrim($found_img_src, '/'); // http://context.local:8888/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg

    // Correct for double-slash that happens when an abolute URL was input
    $found_img_url = str_replace('//wp-content', '/wp-content', $found_img_url);
    // Remove the start to just work with a local child of /uploads/
    $img_path_no_base = str_replace($uploads_base, '', $found_img_url);

    // Use DB metadata to find attachment object
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_query' => array(
            array(
                'value' => $img_path_no_base,
                'compare' => 'LIKE',
                'key' => '_wp_attached_file',
            ),
        ),
    );

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $query->the_post();
        $attachment_id = get_the_ID();
        do_my_log("Found attachment ID " . $attachment_id . ".");
        wp_reset_postdata();
        $post_attachment = get_post($attachment_id);
        return $post_attachment;
    } else {
        do_my_log("❌ No attachment ID found.");
    }
}

function do_get_post_media_everything($post_id)
{
    /**
     * Get All Post Media
     *
     * Clever function to get a combined array of *all* media associated with a post.
     * WordPress is limited in this regard. While a featured image is stored against a post in WP_Posts
     * with _thumbnail_id, in-line use of media may not be recorded in those items because an
     * attachment can only attach to a single post.
     * This function gets a) any featured image and b) any attachments inserted into body content.
     * The result is combined.
     *
     * @param int $post_id The ID of the post to search.
     * @return array|null An array of media objects if media are found, or null if none are found.
     */

    echo __FUNCTION__ . "\n";

    // Initialize an array to hold all attachments
    $all_post_media = array();

    // 1. Get local media found in post content, add to array
    // TODO: Not working ok
    $all_post_media = get_media_objects_from_post_body($post_id);

    // 2. Get featured image
    // TODO: Works

    $featured_image_id = get_post_thumbnail_id($post_id);
    $featured_img_obj = get_post($featured_image_id);
    if ($featured_img_obj) {
        // Add featured image to media array
        $all_post_media[] = $featured_img_obj;
    }

    // 3. Get post attachments
    // TODO: Works
    $post_attachments = get_attached_media('image', $post_id);
    if ($post_attachments) {
        foreach ($post_attachments as $post_attachment) {
            // Add to media array
            $all_post_media[] = $post_attachment;
        }
    }

    // 4. Deduplicate and return

    if ($all_post_media) {
        $all_post_media_unique = deduplicate_array_by_key($all_post_media, "ID");
        return $all_post_media_unique;
    }

}
