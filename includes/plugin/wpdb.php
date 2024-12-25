<?php

function tidy_media_organizer_create_table()
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
    $table_name = $wpdb->prefix . 'tidy_media_organizer';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            setting_id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_name varchar(50) NOT NULL,
            setting_value varchar(300) NOT NULL,
            PRIMARY KEY (setting_id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Pre-specify values
        $pre_specified_values = array(
            array('setting_name' => 'organize_post_img_by_type', 'setting_value' => '1'),
            array('setting_name' => 'use_tidy_attachments', 'setting_value' => '1'),
            array('setting_name' => 'use_tidy_body_media', 'setting_value' => '1'),
            array('setting_name' => 'use_relative', 'setting_value' => '1'),
            array('setting_name' => 'use_localise', 'setting_value' => '1'),
            array('setting_name' => 'use_delete', 'setting_value' => '1'),
            array('setting_name' => 'use_log', 'setting_value' => '1'),
            array('setting_name' => 'run_on_save', 'setting_value' => '1'),
            array('setting_name' => 'organize_term_attachments', 'setting_value' => '1'),
        );
        foreach ($pre_specified_values as $value) {
            $wpdb->insert($table_name, $value);
        }

    }
}
register_activation_hook(__FILE__, 'tidy_media_organizer_create_table');

function tidy_media_organizer_delete_table()
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

    $table_name = $wpdb->prefix . 'tidy_media_organizer';

    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_uninstall_hook(__FILE__, 'tidy_media_organizer_delete_table');

function get_tidy_media_settings()
{
/**
 * Get Plugin  Settings.
 *
 * @global object $wpdb WordPress database access object.
 * @return array Returns an array containing tidy media settings. If the tidy media organizer table is not found, an empty array is returned.
 *
 * The returned array contains the following keys:
 * - organize_post_img_by_type: A boolean indicating whether to organize post images by type.
 * - organize_post_img_by_taxonomy: A string indicating the taxonomy to organize post images by.
 * - organize_post_img_by_post_slug: A boolean indicating whether to organize post images by post slug.
 * - domains_to_replace: A string containing domains to replace with the local site's URL.
 * - use_tidy_attachments: A boolean indicating whether to use the Tidy Attachments feature.
 * - use_tidy_body_media: A boolean indicating whether to use the Tidy Body Media feature.
 * - use_relative: A boolean indicating whether to use relative URLs.
 * - use_localise: A boolean indicating whether to localize URLs.
 * - use_delete: A boolean indicating whether to delete orphaned attachments.
 * - use_log: A boolean indicating whether to log Tidy Media Organizer activity.
 * - run_on_save: A boolean indicating whether to run Tidy Media Organizer on post save.
 */
    global $wpdb;
    $table_name = $wpdb->prefix . 'tidy_media_organizer';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $settings = $wpdb->get_results("SELECT * FROM $table_name");
        $settings_arr = array();
        foreach ($settings as $setting) {
            $settings_arr[$setting->setting_name] = $setting->setting_value;
        }
        $organize_post_img_by_type = isset($settings_arr['organize_post_img_by_type']) ? $settings_arr['organize_post_img_by_type'] : 0;
        $organize_post_img_by_taxonomy = isset($settings_arr['organize_post_img_by_taxonomy']) ? $settings_arr['organize_post_img_by_taxonomy'] : '';
        $organize_post_img_by_post_slug = isset($settings_arr['organize_post_img_by_post_slug']) ? $settings_arr['organize_post_img_by_post_slug'] : 0;
        $domains_to_replace = isset($settings_arr['domains_to_replace']) ? $settings_arr['domains_to_replace'] : '';
        $use_tidy_attachments = isset($settings_arr['use_tidy_attachments']) ? $settings_arr['use_tidy_attachments'] : 0;
        $use_tidy_body_media = isset($settings_arr['use_tidy_body_media']) ? $settings_arr['use_tidy_body_media'] : 0;
        $use_relative = isset($settings_arr['use_relative']) ? $settings_arr['use_relative'] : 0;
        $use_localise = isset($settings_arr['use_localise']) ? $settings_arr['use_localise'] : 0;
        $use_delete = isset($settings_arr['use_delete']) ? $settings_arr['use_delete'] : 0;
        $use_log = isset($settings_arr['use_log']) ? $settings_arr['use_log'] : 0;
        $run_on_save = isset($settings_arr['run_on_save']) ? $settings_arr['run_on_save'] : 0;
        $organize_term_attachments = isset($settings_arr['organize_term_attachments']) ? $settings_arr['organize_term_attachments'] : 0;

        return array(
            'organize_post_img_by_type' => $organize_post_img_by_type,
            'organize_post_img_by_taxonomy' => $organize_post_img_by_taxonomy,
            'organize_post_img_by_post_slug' => $organize_post_img_by_post_slug,
            'domains_to_replace' => $domains_to_replace,
            'use_tidy_attachments' => $use_tidy_attachments,
            'use_tidy_body_media' => $use_tidy_body_media,
            'use_relative' => $use_relative,
            'use_localise' => $use_localise,
            'use_delete' => $use_delete,
            'use_log' => $use_log,
            'run_on_save' => $run_on_save,
            'organize_term_attachments' => $organize_term_attachments,
        );
    } else {
        // Show an error message
        echo '<div class="notice notice-error"><p>Plugin issue: <code>' . $table_name . '</code> not found in database. Cannot store settings. Try reactivating the plugin.</p></div>';
        return array();
    }
}
