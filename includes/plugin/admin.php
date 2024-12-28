<?php

function tidy_admin_menu_item()
{
    /**
     * Admin Menus.
     *
     * Registers the Tidy Media Organizer admin menu and sub-menu pages in the WordPress dashboard.
     *
     * @return void
     */

    add_submenu_page(
        'upload.php', // Change the parent slug to 'upload.php' for Media menu
        'Tidy Media',
        'Tidy Media',
        'manage_options',
        'tidy-media-organizer-options',
        'tidy_admin_options_page'
    );
}
add_action('admin_menu', 'tidy_admin_menu_item');

function tidy_admin_enqueue($hook)
{
    /**
     * Enqueue admin scripts and styles.
     */

    if ($hook !== 'media_page_tidy-media-organizer-options') {
        return;
    }

    wp_enqueue_style(
        'tidy-media-admin',
        plugins_url('assets/css/admin.css', dirname(dirname(__FILE__))),
        array(),
        filemtime(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/css/admin.css')
    );

    wp_enqueue_script(
        'tidy-media-admin',
        plugins_url('assets/js/admin.js', dirname(dirname(__FILE__))),
        array('jquery'),
        filemtime(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/js/admin.js'),
        true
    );
}
add_action('admin_enqueue_scripts', 'tidy_admin_enqueue');

function tidy_admin_options_page()
{
    /**
     * Admin Options Page.
     *
     * This function creates the options page for the Tidy Media Organizer plugin.
     *
     * It checks if the user is authorized to access the options page and saves form data when the Save button is clicked.
     *
     * It retrieves the current settings from the database and outputs the form HTML.
     *
     * @since 1.0.0
     */
    // Check if the user is authorized to access the options page
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    // Save form data when Save button is clicked
    if (isset($_POST['tidy_media_organizer_save'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tidy_media_organizer';

        // Update or create settings
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $settings = array(
                array('setting_name' => 'organize_post_img_by_type', 'setting_value' => isset($_POST['organize_post_img_by_type']) ? 1 : 0),
                array('setting_name' => 'organize_post_img_by_taxonomy', 'setting_value' => isset($_POST['organize_post_img_by_taxonomy']) ? sanitize_text_field($_POST['organize_post_img_by_taxonomy']) : ''),
                array('setting_name' => 'organize_post_img_by_post_slug', 'setting_value' => isset($_POST['organize_post_img_by_post_slug']) ? sanitize_text_field($_POST['organize_post_img_by_post_slug']) : ''),
                array('setting_name' => 'domains_to_replace', 'setting_value' => isset($_POST['domains_to_replace']) ? json_encode(array_filter($_POST['domains_to_replace'])) : ''),
                array('setting_name' => 'use_tidy_attachments', 'setting_value' => isset($_POST['use_tidy_attachments']) ? sanitize_text_field($_POST['use_tidy_attachments']) : ''),
                array('setting_name' => 'use_tidy_body_media', 'setting_value' => isset($_POST['use_tidy_body_media']) ? sanitize_text_field($_POST['use_tidy_body_media']) : ''),
                array('setting_name' => 'use_relative', 'setting_value' => isset($_POST['use_relative']) ? sanitize_text_field($_POST['use_relative']) : ''),
                array('setting_name' => 'use_localise', 'setting_value' => isset($_POST['use_localise']) ? sanitize_text_field($_POST['use_localise']) : ''),
                array('setting_name' => 'use_delete', 'setting_value' => isset($_POST['use_delete']) ? sanitize_text_field($_POST['use_delete']) : ''),
                array('setting_name' => 'use_log', 'setting_value' => isset($_POST['use_log']) ? sanitize_text_field($_POST['use_log']) : ''),
                array('setting_name' => 'run_on_save', 'setting_value' => isset($_POST['run_on_save']) ? sanitize_text_field($_POST['run_on_save']) : ''),
                array('setting_name' => 'organize_term_attachments', 'setting_value' => isset($_POST['organize_term_attachments']) ? sanitize_text_field($_POST['organize_term_attachments']) : ''),
            );
            foreach ($settings as $setting) {
                $existing_row = $wpdb->get_row("SELECT * FROM $table_name WHERE setting_name = '{$setting['setting_name']}'");
                if ($existing_row) {
                    $wpdb->query("UPDATE $table_name SET setting_value = '{$setting['setting_value']}' WHERE setting_name = '{$setting['setting_name']}'");
                } else {
                    $wpdb->insert($table_name, $setting);
                }
            }
            echo '<div class="updated"><p>Settings saved.</p></div>';
        } else {
            // Show an error message
            echo '<div class="notice notice-error"><p>Save failed: <code>' . $table_name . '</code> not found in database. Cannot store settings. Try reactivating the plugin.</p></div>';
        }
    }

    // Get data for template
    $settings = tidy_db_get_settings();
    $domains = json_decode($settings['domains_to_replace'], true) ?: array();
    $post_types = tidy_get_our_post_types();
    $taxonomies = get_taxonomies(array('public' => true));

    // Load template
    require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/options-page.php';
}
