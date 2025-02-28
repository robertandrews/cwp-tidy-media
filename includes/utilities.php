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

function search_for_uploaded_file($filename)
{
    /**
     * Search For File
     *
     * Searches for a file in the WordPress uploads directory and its subdirectories.
     *
     * @param string $filename The name of the file to search for.
     * @return string|false The absolute path to the first occurrence of the file found, or false if the file was not found.
     */
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
