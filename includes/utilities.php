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

function tidy_delete_empty_dir($dir)
{
    /**
     * Delete Empty Directory
     *
     * Attempts to delete a directory if it exists and is empty. The function performs
     * several checks:
     * 1. Verifies if directory is within WordPress uploads directory
     * 2. Verifies if the directory exists
     * 3. Checks if the directory is empty
     * 4. Attempts to delete the directory if conditions are met
     *
     * @param string $dir The absolute path to the directory to be deleted
     * @return boolean Returns true if directory was successfully deleted, false otherwise
     */

    // Get WordPress uploads directory
    $upload_dir = wp_upload_dir();
    $uploads_basedir = $upload_dir['basedir'];

    // Check if directory is within WordPress uploads directory
    if (strpos($dir, $uploads_basedir) !== 0) {
        do_my_log("❌ Security check failed: Directory " . $dir . " is not within WordPress uploads directory.");
        return false;
    }

    // Check if directory exists
    if (!is_dir($dir)) {
        do_my_log("Directory " . $dir . " does not exist.");
        return false;
    }

    // Check if directory is empty
    if (count(glob("$dir/*")) === 0) {
        // Attempt deletion
        if (@rmdir($dir)) {
            do_my_log("Directory " . $dir . " successfully deleted.");
            return true;
        } else {
            do_my_log("Failed to delete directory " . $dir . " due to permissions or other error.");
            return false;
        }
    }

    do_my_log("Directory " . $dir . " is not empty, will not delete.");
    return false;
}
