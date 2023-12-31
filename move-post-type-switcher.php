<?php
/*
 * Post Type Switcher
 *
 * Plugin Name: Post Type Switcher
 * Plugin URI:  https://alphawebcreation.com/plugins/
 * Description: Allows users to switch a post to a custom post type or back to default posts.
 * Version: 1.0.1
 * Author: Alpha Web Creation
 * Author URI:  https://alphawebcreation.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 4.9
 * Requires PHP: 5.2.4
 * Tested up to: 6.3
*/

// Create a custom admin page for post type switching
function custom_post_type_switcher_page() {
    add_menu_page(
        'Custom Post Type Switcher',
        'Post Type Switcher',
        'edit_posts',
        'custom-post-type-switcher',
        'custom_post_type_switcher_page_content'
    );
}

add_action('admin_menu', 'custom_post_type_switcher_page');

// Enqueue custom CSS for the plugin admin page
function enqueue_custom_post_type_switcher_css() {
    // Adjust the path to your CSS file based on its location in your plugin directory
    $css_file_url = plugins_url('custom-post-type-switcher.css', __FILE__);

    // Enqueue the CSS file only on the plugin's admin page
    if (isset($_GET['page']) && $_GET['page'] === 'custom-post-type-switcher') {
        wp_enqueue_style('custom-post-type-switcher-css', $css_file_url);
    }
}

add_action('admin_enqueue_scripts', 'enqueue_custom_post_type_switcher_css');

// Create the content for the custom admin page
function custom_post_type_switcher_page_content() {
    if (isset($_POST['submit'])) {
        $selected_post_type = sanitize_text_field($_POST['custom-post-type-dropdown']);
        $post_ids = isset($_POST['post_ids']) ? $_POST['post_ids'] : array();

        if (!empty($post_ids)) {
            foreach ($post_ids as $post_id) {
                set_post_type($post_id, $selected_post_type);
            }
            echo '<div class="updated"><p>' . count($post_ids) . ' posts updated.</p></div>';
        } else {
            echo '<div class="error"><p>No posts selected. Please select one or more posts to switch.</p></div>';
        }
    }

    $post_types = get_post_types(array('public' => true), 'objects');
    $post_type_slugs = array_keys($post_types);
    
    echo '<div class="wrap">';
    echo '<h1 class="pts-title">Post Type Switcher</h1>';
    echo '<form method="post">';
    echo '<label for="custom-post-type-dropdown">Select a post type:</label>';
    echo '<select name="custom-post-type-dropdown" id="custom-post-type-dropdown">';

    foreach ($post_types as $post_type) {
        echo "<option value='{$post_type->name}'>{$post_type->label}</option>";
    }

    echo '</select>';

    echo '<h2>Select Posts to Update</h2>';
    echo '<label><input type="checkbox" id="select-all-checkbox"> Select All</label>';
    echo '<ul>';

    // Retrieve both "posts" and custom post type "posts"
    $post_types_to_query = array('post', 'your_custom_post_type_slug');

    foreach ($post_types_to_query as $post_type_to_query) {
        $posts = get_posts(array('post_type' => $post_type_to_query, 'posts_per_page' => -1));

        foreach ($posts as $post) {
            echo '<li><input type="checkbox" class="post-checkbox" name="post_ids[]" value="' . $post->ID . '"> ' . $post->post_title . ' (' . $post_type_to_query . ')</li>';
        }
    }

    echo '</ul>';

    echo '<input type="submit" name="submit" class="button button-primary" value="Update Posts">';
    echo '</form>';
    echo '</div>';

    // JavaScript to handle the "Select All" functionality
    echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            const selectAllCheckbox = document.getElementById("select-all-checkbox");
            const postCheckboxes = document.querySelectorAll(".post-checkbox");

            selectAllCheckbox.addEventListener("change", function () {
                postCheckboxes.forEach((checkbox) => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            });
        });
    </script>';
}

// Add the custom dropdown to the post editor
function custom_dropdown_metabox() {
    // Get all registered post types, including custom ones
    $post_types = get_post_types(array('public' => true), 'objects');
    
    // Create an array of post type slugs
    $post_type_slugs = array_keys($post_types);
    
    add_meta_box(
        'custom-dropdown-metabox',
        'Custom Post Type Switcher',
        'custom_dropdown_content',
        $post_type_slugs, // Display the metabox for all post types
        'side', // Display the metabox in the side panel
        'high'
    );
}

add_action('add_meta_boxes', 'custom_dropdown_metabox');

// Populate the custom dropdown with post types
function custom_dropdown_content($post) {
    $selected = get_post_type($post);

    // Get all registered post types, including custom ones
    $post_types = get_post_types(array('public' => true), 'objects');

    echo '<label for="custom-post-type-dropdown">Select a post type:</label>';
    echo '<select name="custom-post-type-dropdown" id="custom-post-type-dropdown">';

    foreach ($post_types as $post_type) {
        $selected_option = selected($selected, $post_type->name, false);
        echo "<option value='{$post_type->name}' $selected_option>{$post_type->label}</option>";
    }

    echo '</select>';
}

// Save the selected post type when the post is saved or updated
function save_custom_post_type($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['custom-post-type-dropdown'])) {
        $new_post_type = sanitize_text_field($_POST['custom-post-type-dropdown']);
        set_post_type($post_id, $new_post_type);
    }
}

add_action('save_post', 'save_custom_post_type');

// Add a filter to check for updates
add_filter('pre_set_site_transient_update_plugins', 'check_for_plugin_update');

function check_for_plugin_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    // Your plugin folder name (change it to match your folder structure)
    $plugin_slug = 'post-type-switcher/post-type-switcher.php';

    // Get the current version of the installed plugin
    $current_version = $transient->checked[$plugin_slug];

    // Define the URL of your GitHub release ZIP archive
    $github_release_url = 'https://github.com/SaifullahQadeer/post-type-switcher/archive/refs/tags/post-switcher.zip';

    // Get the latest version number from the ZIP archive's URL
    preg_match('/\/([^\/]+)\.zip$/', $github_release_url, $matches);
    $latest_version = isset($matches[1]) ? $matches[1] : false;

    // Check if a newer version is available
    if ($latest_version && version_compare($current_version, $latest_version, '<')) {
        $transient->response[$plugin_slug] = (object) array(
            'new_version' => $latest_version,
            'package' => $github_release_url,
            'url' => 'https://alphawebcreation.com/plugins', // Replace with your plugin info page URL
        );
    }

    return $transient;
}
