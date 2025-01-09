<?php
function do_my_log($log_message)
{
    /**
     * Do My Log.
     *
     * Write a log message to a log file.
     * This function writes a log message to a log file, with the current timestamp and the message.
     *
     * @param string $log_message The log message to be written to the log file.
     * @return void
     */
    // $logging = true;
    // Retrieve current settings from database
    $settings = tidy_db_get_settings();

    if ($settings['use_log'] == 1) {
        $log_file = plugin_dir_path(dirname(__FILE__)) . 'wp-tidy-media.log';
        $log_timestamp = gmdate('d-M-Y H:i:s T');

        $log_entry = "[$log_timestamp] $log_message\n";
        error_log($log_entry, 3, $log_file);
    }
}

/**
 * Search For File
 *
 * Searches for a file in the WordPress uploads directory and its subdirectories.
 *
 * @param string $filename The name of the file to search for.
 * @return string|false The absolute path to the first occurrence of the file found, or false if the file was not found.
 */

function search_for_uploaded_file($filename)
{
    $wp_upload_dir = wp_upload_dir();
    $dirs = array($wp_upload_dir['basedir']);

    while (!empty($dirs)) {
        $dir = array_shift($dirs);
        $files = glob($dir . DIRECTORY_SEPARATOR . $filename);

        if (count($files) > 0) {
            return $files[0];
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $subdir) {
            $dirs[] = $subdir;
        }
    }

    return false;
}

function deduplicate_array_by_key($array, $key)
{
    /**
     * Deduplicate Array Items By Key
     *
     * This function deduplicates an array of objects based on a specified object property.
     * Designed so that an array of post items does not carry a post item twice, using the ID field.
     *
     * It iterates over the array and checks if each object's specified property is already in the temporary array.
     * If the property value is not found, it is added to the temporary array and the object is added to the result array.
     * If the property value is found, the object is not added to the result array.
     * Finally, the result array is returned, containing only unique objects.
     *
     * @param array $array The input array of objects to deduplicate.
     * @param string $key The name of the property to check for duplication.
     * @return array The array of objects with duplicates removed.
     */
    $temp_array = array();
    $result_array = array();

    foreach ($array as $item) {
        if (!isset($temp_array[$item->$key])) {
            $temp_array[$item->$key] = true;
            $result_array[] = $item;
        }
    }

    return $result_array;
}

function tidy_get_our_post_types()
{
    /**
     * Our Post Types.
     *
     * Generates an array of post types which various other functions can use, eg. in post queries
     * and the main catch_saved_post(). Strips out some built-in types like "attachment". Only uses
     * "post", "page" and any custom post types.
     *
     * @return array An array of available post types, with the default post types added back in
     */

    // Available post-like post types, strip out defaults
    $args = array(
        'public' => true,
        '_builtin' => false, // strip out attachment, revision, nav_menu_item, custom_css, oembed_cache, user_request, wp_block
    );
    $post_types = get_post_types($args);
    // Add back 2x default post types - 'post' and 'page'
    array_push($post_types, 'post', 'page');

    return $post_types;

}

function is_id_attachment($number_found)
{
    /**
     * Check If ID Is Attachment
     *
     * @param int $number_found The ID to check
     * @return bool True if the ID is an attachment; false otherwise.
     */

    do_my_log("is_id_attachment()");

    // check if an attachment (post of type 'attachment') exists with $number_found as its ID
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'post__in' => array($number_found),
    );
    $query = new WP_Query($args);
    // If there are any results, the number is of an attachment
    if ($query->have_posts()) {
        return true;
        // If not, this is not an attachment
    } else {
        return false;
    }
}

function is_absolute_url($url)
{
    // Parse the URL and retrieve its components
    $parsed_url = wp_parse_url($url);

    // Check if both scheme and host are present
    return isset($parsed_url['scheme']) && isset($parsed_url['host']);
}

/**
 * Convert an absolute URL to its relative form
 *
 * Takes any absolute URL and converts it to a relative path by removing the scheme and host.
 * If the URL is already relative, it will be returned unchanged.
 *
 * Examples:
 * - http://mysite.com/wp-content/uploads/image.jpg -> /wp-content/uploads/image.jpg
 * - https://othersite.com/images/photo.jpg -> /images/photo.jpg
 * - /wp-content/uploads/image.jpg -> /wp-content/uploads/image.jpg (unchanged)
 *
 * @param string $url The URL to convert
 * @return string The URL in relative form if applicable, otherwise unchanged
 */
function convert_to_relative_url($url)
{
    // If not an absolute URL or empty, return as is
    if (!$url || !is_absolute_url($url)) {
        return $url;
    }

    // Parse the URL to get its path component
    $parsed_url = wp_parse_url($url);
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';

    // Add query string if it exists
    if (isset($parsed_url['query'])) {
        $path .= '?' . $parsed_url['query'];
    }

    // Add fragment if it exists
    if (isset($parsed_url['fragment'])) {
        $path .= '#' . $parsed_url['fragment'];
    }

    // Ensure path starts with a forward slash
    return '/' . ltrim($path, '/');
}

/**
 * Check if URL belongs to current site
 *
 * Takes a URL and checks whether it belongs to the current WordPress site
 * by comparing the hostnames.
 *
 * @param string $url The URL to check
 * @return bool True if URL belongs to current site, false otherwise
 */
function is_url_from_current_site($url)
{
    // Get site's host
    $site_host = parse_url(home_url(), PHP_URL_HOST);
    // Get input URL's host
    $input_host = parse_url($url, PHP_URL_HOST);

    // If input URL has no host (relative URL), it's from our site
    if (empty($input_host)) {
        return true;
    }

    // Check if input URL's host matches our site's host
    return $input_host === $site_host;
}

/**
 * Check if URL points to an image file
 *
 * Takes a URL and checks if it points to an image file by examining
 * the file extension. Supports jpg, jpeg, png, gif, and webp formats.
 *
 * @param string $url The URL to check
 * @return bool True if URL points to an image file, false otherwise
 */
function is_image_url($url)
{
    return (bool) preg_match('/\.(jpe?g|png|gif|webp)$/i', $url);
}

function is_valid_image_data($image_data)
{
    /**
     * Check if the given binary data represents a valid image
     *
     * @param string $image_data Binary string data of the potential image file
     * @return bool True if the data represents a valid image, false otherwise
     */
    return (bool) getimagesizefromstring($image_data);
}

function is_valid_media_data($file_data, $allowed_types = ['image', 'video', 'audio', 'application/pdf'])
{
    /**
     * Check if the given binary data represents a valid media file
     *
     * @param string $file_data Binary string data of the potential media file
     * @param array $allowed_types Array of allowed media types/MIME prefixes
     * @return bool True if the data represents a valid media file of allowed type, false otherwise
     */
    if (empty($file_data)) {
        return false;
    }

    // Create a finfo instance
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    // Get the MIME type of the data
    $mime_type = $finfo->buffer($file_data);

    if (!$mime_type) {
        return false;
    }

    // Check if the MIME type matches any of our allowed types
    foreach ($allowed_types as $type) {
        // Handle full MIME types (like application/pdf)
        if (strpos($type, '/') !== false) {
            if ($mime_type === $type) {
                return true;
            }
        }
        // Handle type prefixes (like 'image', 'video', 'audio')
        else if (strpos($mime_type, $type . '/') === 0) {
            return true;
        }
    }

    return false;
}

function tidy_store_media_file($file_data, $file_name, $post_id)
{
    /**
     * Store Media File
     *
     * Stores a media file to disk and creates a WordPress attachment for it.
     * The file will be stored in the WordPress uploads directory and properly registered
     * in the WordPress media library.
     *
     * @param string $file_data The binary data of the file to store
     * @param string $file_name The name to give the file (including extension)
     * @param int    $post_id   The ID of the post to attach this media to
     * @return int|false        The attachment ID if successful, false if failed
     */

    // Generate default uploads destination directory path
    $upload_dir = wp_upload_dir(); // eg. /wp-content/uploads/YEAR/MONTH/
    $file_path = $upload_dir['path'] . '/' . $file_name; // eg. /wp-content/uploads/YEAR/MONTH/myfile.jpg

    // Try to store the file
    if (file_put_contents($file_path, $file_data) === false) {
        do_my_log("❌ File save failed.");
        return false;
    }

    do_my_log("Saved file to " . $file_path);

    // Get the post date of the parent post
    $post_date = get_post_field('post_date', $post_id);

    // Create attachment post object
    $attachment = array(
        'post_title' => $file_name,
        'post_mime_type' => wp_check_filetype($file_name)['type'],
        'post_content' => '',
        'post_status' => 'inherit',
        'post_parent' => $post_id,
        'post_date' => $post_date,
        'post_date_gmt' => get_gmt_from_date($post_date),
    );

    // Insert the attachment into the media library
    $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
    if (!$attach_id) {
        do_my_log("❌ Failed to create attachment.");
        return false;
    }

    // Set the attachment metadata
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    do_my_log("Created attachment ID " . $attach_id);
    return $attach_id;
}
