<?php

/**
 * Function: Get all media elements in post body
 *
 * This function searches for specific HTML tags (e.g., 'img', 'source', 'embed') within the post
 * content and extracts their attributes. It returns an array of media elements, each containing
 * the tag name and relevant attributes (e.g., 'src', 'alt', 'title', 'href').
 *
 * @param int $post_id The ID of the post to retrieve media elements from.
 * @return array An array of media elements, each represented as an associative array
 *               with the tag name and its attributes.
 */
function get_post_body_media_elements($post_id)
{
    // 1. Get the post content as DOM
    $content = get_post_field('post_content', $post_id);
    if (!$content) {
        return;
    }
    $dom = tidy_get_content_dom($content);

    // 2. Get specified elements from the post content

    // Initialize an array to hold all media elements
    $body_media_elements = array();

    // Define the tags to search for
    $tags = array('img', 'source', 'embed'); // img, source and embed have 'src' attributes
    // Define the attributes to search for
    $attributes = array('src', 'alt', 'title', 'href'); //eg. ['src', 'alt', 'title', 'href']

    // Iterate over each tag type
    foreach ($tags as $tag) {
        // Find all elements of the current tag type
        $elements = $dom->getElementsByTagName($tag);

        // Add each element's attributes to the body_media_elements array
        foreach ($elements as $element) {
            $media_item = array('tag' => $tag);

            // Add common attributes if they exist
            foreach ($attributes as $attr) {
                if ($element->hasAttribute($attr)) {
                    $media_item[$attr] = $element->getAttribute($attr);
                }
            }

            $body_media_elements[] = $media_item;
        }
    }

    // Log the results
    do_my_log("👉🏻 " . __FUNCTION__ . ":");
    // : 🔍 Found " . count($body_media_elements) . " media elements in post body");
    if (!empty($body_media_elements)) {
        foreach ($body_media_elements as $position => $element) {
            do_my_log($position + 1 . ": " . $element['tag'] . " with src " . $element['src']);
        }
    }

    // Return the results
    return $body_media_elements;
}

/**
 * Get Media Objects from Post Body
 *
 * Extracts and retrieves all media objects that are found and referenced
 * within a post's content body. This function scans the post content for media
 * elements and verifies if they exist as WordPress media items.
 *
 * @param int $post_id The ID of the post to search for media objects
 * @return array An array of WP_Post objects representing the media attachments found in the post body
 */
function get_post_body_media_objects($post_id)
{

    // First, get the media elements from the post body
    $body_media_elements = get_post_body_media_elements($post_id);

    // Initialize an array to hold all attachments
    $body_media_objects = array();

    if (!empty($body_media_elements)) {
        foreach ($body_media_elements as $media_element) {
            // Check whether the element src is actually a stored media object in our WordPress
            $media_object = get_media_object_from_filepath($media_element['src']);
            if ($media_object) {
                // Add to attachments array
                $body_media_objects[] = $media_object;
            }
        }
    }

    // Log the results
    do_my_log(__FUNCTION__ . ": 🔍 Found " . count($body_media_objects) . " media objects in post body");
    if (!empty($body_media_objects)) {
        foreach ($body_media_objects as $position => $media_object) {
            do_my_log($position . ": " . $media_object->post_title . " with src " . $media_object->guid);
        }
    }

    return $body_media_objects;
}

/**
 * Get Media Object From Filepath
 *
 * Attempts to find the attachment object for a file given its URL path, which can be either:
 * - relative URL path (e.g. /wp-content/uploads/post/client/ghost-foundation/2020/09/rafat-ali-skift.jpg)
 * - absolute URL from current site (e.g. http://mysite.com/wp-content/uploads/2020/09/image.jpg)
 *
 * @param string $found_media_src The URL path of the file
 * @return WP_Post|void The WP_Post object representing the attachment, or void if the attachment ID was not found.
 */
function get_media_object_from_filepath($found_media_src)
{
    do_my_log(__FUNCTION__ . ":");

    // 1. Generate an initial relative path

    // If this is an absolute URL from another site, return early
    if (is_absolute_url($found_media_src) && !is_url_from_current_site($found_media_src)) {
        return;
    }

    // If we get here and it's an absolute URL, and from our site, convert it to relative
    if (is_absolute_url($found_media_src) && is_url_from_current_site($found_media_src)) {
        $found_media_src = convert_to_relative_url($found_media_src);
    }

    // 2. Generate a shortened filepath to match WordPress's database format in
    // wp_attached_file, which looks like 2023/12/image.jpg

    // Get the uploads directory information
    $uploads = wp_upload_dir();
    $uploads_base_url = trailingslashit($uploads['baseurl']);

    // Extract the path relative to uploads directory
    $relative_path = ltrim($found_media_src, '/');
    if (strpos($relative_path, 'wp-content/uploads/') === 0) {
        $shortened_relative_path = substr($relative_path, strlen('wp-content/uploads/')); // eg. /2020/09/image.jpg
    }

    // 3. Look for a wp_postmeta record with _wp_attached_file matching the shortened path
    // eg. /2020/09/image.jpg - use direct database query for better performance
    global $wpdb;
    $attachment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta
        WHERE meta_key = '_wp_attached_file'
        AND meta_value LIKE %s",
        '%' . $wpdb->esc_like($shortened_relative_path)
    ));

    // 4. If found, return the attachment object
    if ($attachment_id) {
        $post_attachment = get_post($attachment_id);
        // NEW: Double check file still exists
        $attached_file = get_attached_file($attachment_id);
        if (!file_exists($attached_file)) {
            do_my_log("❌ Attachment exists in database but file missing at: " . $attached_file);
            return null;
        }
        do_my_log("Found attachment ID " . $attachment_id . " with title " . $post_attachment->post_title . " and src " . $post_attachment->guid);
        return $post_attachment;
    } else {
        do_my_log("❌ No attachment ID found for path: " . $shortened_relative_path);
        return null;
    }
}

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
function do_get_post_media_everything($post_id)
{
    // Initialize an array to hold all attachments
    $body_media_objects = array();

    // 1. Get local media found in post body content, add to array
    $body_media_objects = get_post_body_media_objects($post_id);

    // 2. Get featured image
    $featured_image_id = get_post_thumbnail_id($post_id);
    $featured_img_obj = get_post($featured_image_id);
    if ($featured_img_obj) {
        // Add featured image to media array
        $body_media_objects[] = $featured_img_obj;
    }

    // 3. Get post attachments
    $post_attachments = get_attached_media('image', $post_id);
    if ($post_attachments) {
        foreach ($post_attachments as $post_attachment) {
            // Add to media array
            $body_media_objects[] = $post_attachment;
        }
    }

    // 4. Deduplicate and return

    if ($body_media_objects) {
        $body_media_objects_unique = deduplicate_array_by_key($body_media_objects, "ID");
        return $body_media_objects_unique;
    }

}
