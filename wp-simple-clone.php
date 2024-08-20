<?php
/*
Plugin Name: Simple Clone
Plugin URI: https://github.com/codearachnid/wp-simple-clone/
Description: Allows you to clone an existing post including all meta data within defined post types.
Author: Timothy Wood (@codearachnid)
Version: 1.0.0
Requires at least: 6.0
Requires PHP: 7.3
Tested up to: 6.1.1
Author URI: https://codearachnid.com
License: GPL-3.0+
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: wp_simple_clone
*/

/*
	Copyright 2022 Timothy Wood

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Exit if accessed directly
if (!defined('ABSPATH') )
    exit;

// Add the 'Clone' link to post actions
function wpsc_add_clone_link($actions, $post) {
    // Define the post types where you want the clone functionality
    $post_types = apply_filters( 'wpsc_clonable_types', ['post', 'page'] ); // Add your desired post types here

    if (in_array($post->post_type, $post_types)) {
        $actions['clone'] = '<a href="' . wp_nonce_url('admin.php?action=wpsc_clone_post&post=' . $post->ID, basename(__FILE__), 'wpsc_nonce') . '" title="Clone this item" rel="permalink">Clone</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'wpsc_add_clone_link', 10, 2);
add_filter('page_row_actions', 'wpsc_add_clone_link', 10, 2);

// Handle the clone action
function wpsc_clone_post() {
    // Check the nonce for security
    if (!isset($_GET['wpsc_nonce']) || !wp_verify_nonce($_GET['wpsc_nonce'], basename(__FILE__))) {
        return;
    }

    // Get the post ID
    $post_id = (isset($_GET['post']) ? absint($_GET['post']) : 0);
    $post = get_post($post_id);

    // Check if the post exists
    if (!$post) {
        wp_die('Post does not exist!');
    }

    // Check if the current user has permission to edit posts
    if (!current_user_can('edit_posts')) {
        wp_die('You do not have permission to clone this post.');
    }

    // Prepare the post data for cloning
    $new_post = [
        'post_title'    => $post->post_title . ' (Clone)',
        'post_content'  => $post->post_content,
        'post_status'   => 'draft',
        'post_type'     => $post->post_type,
        'post_author'   => $post->post_author,
        'post_category' => wp_get_post_categories($post_id),
        'post_excerpt'  => $post->post_excerpt,
    ];

    // Insert the cloned post
    $new_post_id = wp_insert_post($new_post);

    // Copy post meta data
    $post_meta = get_post_meta($post_id);
    foreach ($post_meta as $key => $value) {
        if (is_array($value)) {
            update_post_meta($new_post_id, $key, $value[0]);
        } else {
            update_post_meta($new_post_id, $key, $value);
        }
    }

    // Redirect to the edit screen of the cloned post
    wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
    exit;
}
add_action('admin_action_wpsc_clone_post', 'wpsc_clone_post');
