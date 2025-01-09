<?php
/*
Plugin Name: CWP Media Tidy
Plugin URI: https:/www.robertandrews.co.uk
Description: A plugin to organize media by post type or taxonomy.
Version: 1.1.0
Author: Robert Andrews
Author URI: https:/www.robertandrews.co.uk
 */

// Exit if accessed directly - prevents direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TIDY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TIDY_INCLUDES_DIR', TIDY_PLUGIN_DIR . 'includes/');

// Include plugin database functions
require_once TIDY_INCLUDES_DIR . 'plugin/wpdb.php';

// Register activation hook
register_activation_hook(__FILE__, 'tidy_db_table_create');

// Include plugin admin functions
require_once TIDY_INCLUDES_DIR . 'plugin/admin.php';

// Include plugin utilities functions
require_once TIDY_INCLUDES_DIR . 'utilities.php';

// Include plugin posts functions
require_once TIDY_INCLUDES_DIR . 'origin/post.php';

// Include plugin edit functions
require_once TIDY_INCLUDES_DIR . 'origin/edit.php';

// Include plugin term functions
require_once TIDY_INCLUDES_DIR . 'origin/term.php';

// Include plugin main functions
require_once TIDY_INCLUDES_DIR . 'main.php';

// Include plugin media functions
require_once TIDY_INCLUDES_DIR . 'media.php';

// Include plugin notices functions
require_once TIDY_INCLUDES_DIR . 'notices.php';

// Include plugin content functions
require_once TIDY_INCLUDES_DIR . 'content.php';
