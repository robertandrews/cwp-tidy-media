<?php

// Define a constant for the database table suffix
define('TIDY_MEDIA_TABLE_SUFFIX', 'tidy_media_organizer');

function tidy_db_table_create()
{
    /**
     * Database Setup.
     *
     * Creates a new database table for storing Tidy Media Organizer plugin settings.
     *
     * @global object $wpdb The WordPress database object.
     * @return void
     */
    global $wpdb;
    $table_name = $wpdb->prefix . TIDY_MEDIA_TABLE_SUFFIX;

    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Get the WordPress database character set
        $charset_collate = $wpdb->get_charset_collate();

        // Define table structure with three columns: ID, setting name, and value
        $sql = "CREATE TABLE $table_name (
            setting_id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_name varchar(50) NOT NULL,
            setting_value varchar(300) NOT NULL,
            PRIMARY KEY (setting_id)
        ) $charset_collate;";

        // Include WordPress upgrade functions for dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Define default settings for the plugin
        $default_settings = array(
            // System settings
            'use_log' => '1', // Enable logging
            'run_on_save' => '1', // Run organization on every save_post hook
            // Main functions
            'tmo_do_localise_images' => '1', // Pull down remote body images
            'tmo_do_relativise_urls' => '1', // Convert local body image URLs from absolute to relative
            'tmo_do_delete_attachments_on_post_delete' => '1', // Enable orphaned attachment deletion
            'tmo_do_reorg_body_media' => '1', // Reorganise media found in post content
            'tmo_do_reorg_post_attachments' => '1', // Reorganise post attachments
            // Path organisation
            'path_inc_post_type' => '1', // Media filepath should use post_type folder (only for post attachments)
            'path_inc_tax_term' => '1', // Media filepath should use taxonomy folder (only for term attachments)
        );

        // Prepare values and placeholders for bulk insert
        $values = array();
        $placeholders = array();
        foreach ($default_settings as $name => $value) {
            $values[] = $name;
            $values[] = $value;
            $placeholders[] = "(%s, %s)";
        }

        // Build and execute bulk insert query
        $query = $wpdb->prepare(
            "INSERT INTO $table_name (setting_name, setting_value) VALUES " . implode(', ', $placeholders),
            $values
        );
        $wpdb->query($query);
    }
}

function tidy_db_table_delete()
{
    /**
     * Clean On Deletion.
     *
     * Deletes the database table used by the Tidy Media Organizer plugin.
     *
     * This function deletes the database table used by the Tidy Media Organizer plugin when the plugin is uninstalled.
     *
     * @since 1.0.0
     */
    global $wpdb;
    global $table_name;

    // Set the full table name with prefix
    $table_name = $wpdb->prefix . 'tidy_media_organizer';

    // Drop the table if it exists
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
// Register the uninstall hook to clean up the database when plugin is uninstalled
register_uninstall_hook(__FILE__, 'tidy_db_table_delete');

function tidy_db_get_settings()
{
/**
 * Get Plugin  Settings.
 *
 * @global object $wpdb WordPress database access object.
 * @return array Returns an array containing tidy media settings. If the tidy media organizer table is not found, an empty array is returned.
 *
 * The returned array contains the following keys:
 * - use_log: A boolean indicating whether to log Tidy Media Organizer activity.
 * - run_on_save: A boolean indicating whether to run Tidy Media Organizer on post save.
 * - tmo_do_localise_images: A boolean indicating whether to localize URLs.
 * - tmo_do_relativise_urls: A boolean indicating whether to use relative URLs.
 * - tmo_do_delete_attachments_on_post_delete: A boolean indicating whether to delete orphaned attachments.
 * - tmo_do_reorg_body_media: A boolean indicating whether to use the Tidy Body Media feature.
 * - tmo_do_reorg_post_attachments: A boolean indicating whether to use the Tidy Attachments feature.
 * - path_inc_post_type: A boolean indicating whether to organize post images by type.
 * - folder_item_taxonomy: A string indicating the taxonomy to organize post images by.
 * - folder_item_post_identifier: A boolean indicating whether to organize post images by post slug.
 * - domains_to_replace: A string containing domains to replace with the local site's URL.
 */
    global $wpdb;
    $table_name = $wpdb->prefix . 'tidy_media_organizer';

    // Check if our settings table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        // Fetch all settings from the database
        $settings = $wpdb->get_results("SELECT * FROM $table_name");
        $settings_arr = array();

        // Convert database results into associative array
        foreach ($settings as $setting) {
            $settings_arr[$setting->setting_name] = $setting->setting_value;
        }

        // Get each setting with fallback default values if not set
        // System settings
        $use_log = isset($settings_arr['use_log']) ? $settings_arr['use_log'] : 0;
        $run_on_save = isset($settings_arr['run_on_save']) ? $settings_arr['run_on_save'] : 0;
        // Main functions
        $tmo_do_localise_images = isset($settings_arr['tmo_do_localise_images']) ? $settings_arr['tmo_do_localise_images'] : 0;
        $tmo_do_relativise_urls = isset($settings_arr['tmo_do_relativise_urls']) ? $settings_arr['tmo_do_relativise_urls'] : 0;
        $tmo_do_delete_attachments_on_post_delete = isset($settings_arr['tmo_do_delete_attachments_on_post_delete']) ? $settings_arr['tmo_do_delete_attachments_on_post_delete'] : 0;
        $tmo_do_reorg_body_media = isset($settings_arr['tmo_do_reorg_body_media']) ? $settings_arr['tmo_do_reorg_body_media'] : 0;
        $tmo_do_reorg_post_attachments = isset($settings_arr['tmo_do_reorg_post_attachments']) ? $settings_arr['tmo_do_reorg_post_attachments'] : 0;
        // Path organisation
        $path_inc_post_type = isset($settings_arr['path_inc_post_type']) ? $settings_arr['path_inc_post_type'] : 0;
        $path_inc_tax_term = isset($settings_arr['path_inc_tax_term']) ? $settings_arr['path_inc_tax_term'] : 0;
        // Folder item organisation
        $folder_item_taxonomy = isset($settings_arr['folder_item_taxonomy']) ? $settings_arr['folder_item_taxonomy'] : '';
        $folder_item_post_identifier = isset($settings_arr['folder_item_post_identifier']) ? $settings_arr['folder_item_post_identifier'] : 0;
        // Domains to replace
        $domains_to_replace = isset($settings_arr['domains_to_replace']) ? $settings_arr['domains_to_replace'] : '';

        // Return all settings in a structured array
        return array(
            // System settings
            'use_log' => $use_log,
            'run_on_save' => $run_on_save,
            // Main functions
            'tmo_do_localise_images' => $tmo_do_localise_images,
            'tmo_do_relativise_urls' => $tmo_do_relativise_urls,
            'tmo_do_delete_attachments_on_post_delete' => $tmo_do_delete_attachments_on_post_delete,
            'tmo_do_reorg_body_media' => $tmo_do_reorg_body_media,
            'tmo_do_reorg_post_attachments' => $tmo_do_reorg_post_attachments,
            // Path organisation
            'path_inc_post_type' => $path_inc_post_type,
            'path_inc_tax_term' => $path_inc_tax_term,
            // Folder item organisation
            'folder_item_taxonomy' => $folder_item_taxonomy,
            'folder_item_post_identifier' => $folder_item_post_identifier,
            // Domains to replace
            'domains_to_replace' => $domains_to_replace,
        );
    } else {
        // If table doesn't exist, add an admin notice and return empty settings
        add_action('admin_notices', function () use ($table_name) {
            echo '<div class="notice notice-error"><p>Plugin issue: <code>' . esc_html($table_name) . '</code> not found in database. Cannot store settings. Try reactivating the plugin.</p></div>';
        });
        return array();
    }
}
