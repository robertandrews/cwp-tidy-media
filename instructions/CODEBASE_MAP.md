# WP Tidy Media Plugin - Codebase Map

## Plugin Entry Point

`wp-tidy-media.php`

- Plugin initialization and core includes
- Hooks:
  - `register_activation_hook` → `tidy_db_table_create`

## Core Functionality Flow

### Post Processing Flow

`includes/origin/post.php`

- `catch_saved_post($post_id)`
  ↓ Triggers post processing functions:
  - → `tidy_do_localise_images($post_id)`
  - → `tidy_do_relativise_urls($post_id)`
  - → `tidy_do_reorg_body_media($post_id)`
  - → `tidy_do_reorg_post_attachments($post_id)`

### Media Management Flow

`includes/media.php`

- `get_attachment_obj_from_filepath($found_img_src)`
  - Used by: `tidy_do_reorg_body_media`
- `do_delete_attachment($attachment_id)`
  - Used by: `tidy_do_delete_attachments_on_post_delete`
- `move_main_file($attachment_id, $old_image_details, $new_image_details, $post_id)`
  - Called by: `term_img_move_controller`, `custom_path_controller`
- `move_sizes_files($attachment_id, $old_image_details, $new_image_details, $post_id)`
  - Called by: `term_img_move_controller`, `custom_path_controller`
- `move_original_file($attachment_id, $old_image_details, $new_image_details, $post_id)`
  - Called by: `term_img_move_controller`, `custom_path_controller`
- `is_attachment_used_elsewhere($attachment_id, $main_post_id)`
  - Used by: `tidy_do_delete_attachments_on_post_delete`
- `custom_path_controller($post_id, $post_attachment)`
  - Called by: `tidy_do_reorg_body_media`, `tidy_do_reorg_post_attachments`

### Term Processing Flow

`includes/origin/term.php`

- `catch_edit_term($term_id, $tt_id, $taxonomy)`
  ↓ Processes term attachments:
  - → `is_id_attachment($number_found)`
  - → `term_img_move_controller($term_attachment, $term, $key)`
    ↓ Manages file moves:
    - → `move_main_file()`
    - → `move_sizes_files()`
    - → `move_original_file()`

### Content Processing

`includes/content.php`

- `tidy_get_content_dom($content)`
  - Used by: Multiple functions for DOM manipulation
- `tidy_update_body_media_urls($post_id, $post_att_id, $old_image_details, $new_image_details)`
  - Updates image URLs across posts

### Database Management

`includes/plugin/wpdb.php`

- `tidy_db_table_create()`
  - Called on plugin activation
- `tidy_db_table_delete()`
  - Called on plugin uninstall
- `tidy_db_get_settings()`
  - Used throughout for plugin settings

### Admin Interface

`includes/plugin/admin.php`

- `tidy_admin_menu_item()`
- `tidy_admin_enqueue($hook)`
- `tidy_admin_options_page()`

### Frontend JavaScript

`assets/js/admin.js`

- `updatePreviewPaths()`
  - Updates UI path preview
  - Triggered by: DOM events, form changes
- `toggleRelativeUrlsBox()`
  - Controls UI visibility
  - Triggered by: Checkbox changes

### Utility Functions

`includes/utilities.php`

- `do_my_log($log_message)`
  - Used throughout for logging
- `search_for_uploaded_file($filename)`
  - Used by: `tidy_do_reorg_body_media`
- `deduplicate_array_by_key($array, $key)`
- `tidy_get_our_post_types()`
  - Used by: Multiple functions for post type filtering

### Notifications

`includes/notices.php`

- `my_trigger_notice($key)`
- `my_admin_notices()`

## Key Processing Flows

1. Post Save Flow:

```php
catch_saved_post
├── tidy_do_localise_images
│   └── tidy_get_content_dom
├── tidy_do_relativise_urls
│   └── tidy_get_content_dom
├── tidy_do_reorg_body_media
│   ├── get_attachment_obj_from_filepath
│   ├── custom_path_controller
│   │   ├── old_image_details
│   │   ├── new_image_details
│   │   ├── move_main_file
│   │   │   └── wp_mkdir_p
│   │   ├── move_sizes_files
│   │   │   ├── wp_get_attachment_metadata
│   │   │   └── wp_update_attachment_metadata
│   │   └── move_original_file
│   └── tidy_update_body_media_urls
└── tidy_do_reorg_post_attachments
    └── custom_path_controller
        [same subtree as above]
```

2. Term Edit Flow:

```php
catch_edit_term
├── is_id_attachment
└── term_img_move_controller
    ├── old_image_details
    ├── new_term_image_details
    ├── move_main_file
    │   └── wp_mkdir_p
    ├── move_sizes_files
    │   ├── wp_get_attachment_metadata
    │   └── wp_update_attachment_metadata
    └── move_original_file
```

3. Media Deletion Flow:

```php
tidy_do_delete_attachments_on_post_delete
├── tidy_db_get_settings
├── do_get_all_attachments
├── is_attachment_used_elsewhere
│   ├── get_post
│   ├── old_image_details
│   └── tidy_get_our_post_types
└── do_delete_attachment
    ├── wp_attachment_is_image
    ├── get_post
    ├── get_attached_file
    ├── do_delete_img_sizes
    │   ├── get_intermediate_image_sizes
    │   ├── wp_get_attachment_metadata
    │   ├── wp_get_attachment_image_src
    │   └── wp_delete_attachment_file
    ├── wp_delete_attachment_files
    ├── wp_delete_attachment
    └── rmdir (if directory empty)
``` 