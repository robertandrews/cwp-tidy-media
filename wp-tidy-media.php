<?php
/*
Plugin Name: Tidy Media Organizer
Plugin URI: https://example.com/
Description: A plugin to organize media by post type or taxonomy.
Version: 1.0
Author: Your Name
Author URI: https://example.com/
 */







// Create custom database table on plugin activation
register_activation_hook(__FILE__, 'tidy_media_organizer_create_table');
function tidy_media_organizer_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'tidy_media_organizer';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            setting_id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_name varchar(50) NOT NULL,
            setting_value varchar(50) NOT NULL,
            PRIMARY KEY (setting_id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}








// Display admin page
add_action('admin_menu', 'tidy_media_organizer_admin_page');
function tidy_media_organizer_admin_page()
{
    add_options_page(
        'Tidy Media Organizer',
        'Tidy Media Organizer',
        'manage_options',
        'tidy-media-organizer',
        'tidy_media_organizer_options_page'
    );
}





















function tidy_media_organizer_options_page()
{
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
            array('setting_name' => 'organize_by_post_type', 'setting_value' => isset($_POST['organize_by_post_type']) ? 1 : 0),
            array('setting_name' => 'organize_by_taxonomy', 'setting_value' => isset($_POST['organize_by_taxonomy']) ? sanitize_text_field($_POST['organize_by_taxonomy']) : ''),
        );
        foreach ($settings as $setting) {
            $existing_row = $wpdb->get_row("SELECT * FROM $table_name WHERE setting_name = '{$setting['setting_name']}'");
            if ($existing_row) {
                $wpdb->query("UPDATE $table_name SET setting_value = '{$setting['setting_value']}' WHERE setting_name = '{$setting['setting_name']}'");
            } else {
                $wpdb->insert($table_name, $setting);
            }
        }
        echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';
    } else {
        // Show an error message
        echo '<div class="notice notice-error"><p><strong>Save failed</strong>: <code>' . $table_name . '</code> not found in database. Cannot store settings. Try reactivating the plugin.</p></div>';
    }

}











    // Retrieve current settings from database
    global $wpdb;
    $table_name = $wpdb->prefix . 'tidy_media_organizer';
    $settings = $wpdb->get_results("SELECT * FROM $table_name");
    $settings_arr = array();
    foreach ($settings as $setting) {
        $settings_arr[$setting->setting_name] = $setting->setting_value;
    }
    $organize_by_post_type = isset($settings_arr['organize_by_post_type']) ? $settings_arr['organize_by_post_type'] : 0;
    $organize_by_taxonomy = isset($settings_arr['organize_by_taxonomy']) ? $settings_arr['organize_by_taxonomy'] : '';











    
    // Output form HTML
    ?>
    <div class="wrap">
        <h1>Tidy Media Organizer</h1>




        <form method="post">
            <table class="form-table">
                <tbody>




                    <tr>
                        <th scope="row">
                            <label for="organize_by_post_type">Organize by post type?</label>
                        </th>
                        <td>
                            <input type="checkbox" name="organize_by_post_type" id="organize_by_post_type" value="1" <?php checked($organize_by_post_type, 1);?>>
                                <?php
                                // Show post types
                                $args = array(
                                    'public' => true,
                                    '_builtin' => false, // exclude default post types
                                );
                                $post_types = get_post_types($args);
                                // add back default post types 'post' and 'page'
                                array_push($post_types, 'post', 'page');
                                echo '('.implode(', ', array_map(function ($post_type) {
                                    return '<code>' . $post_type . '</code>';
                                }, $post_types)).')';
                                ?>
                        </td>
                    </tr>


                    
                    <tr>
                        <th scope="row">
                            <label>Organize by taxonomy:</label>
                        </th>
                        <td>
<?php
                            $taxonomies = get_taxonomies(array('public' => true));
                            foreach ($taxonomies as $taxonomy) {
                            ?>
                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                    <input type="radio" name="organize_by_taxonomy" value="<?php echo esc_attr($taxonomy); ?>" <?php checked($organize_by_taxonomy, $taxonomy);?>>
                                    <code><?php echo esc_html($taxonomy); ?></code>
                                </label>
                                <br>
                                <?php
                            }
                            ?>

                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label>Preview:</label>
                        </th>
                        <td><div id="planned-path"></div></td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="tidy_media_organizer_save" class="button-primary" value="Save Changes">
            </p>
        </form>
    </div>
<?php

}
?>