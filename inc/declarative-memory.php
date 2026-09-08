<?php
/**
 * Declarative Memory functionality for Cheshire Cat Chatbot
 *
 * @package CheshireCatChatbot
 */

namespace webgrafia\cheshirecat;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Send post content to Cheshire Cat declarative memory.
 *
 * This function is called when a post is saved and sends the post content
 * to the Cheshire Cat declarative memory if the feature is enabled and
 * the post is not excluded.
 *
 * @since 0.8.0
 * @param int     $post_id The post ID.
 * @param WP_Post $post    The post object.
 * @param bool    $update  Whether this is an existing post being updated.
 * @return void
 */

function cheshirecat_send_to_declarative_memory( $post_id, $post, $update ) {
    // If this is an autosave, our form has not been submitted, so we don't want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Skip Customizer contexts and its data posts
    if (
        // Customizer preview/save flows
        ( function_exists('is_customize_preview') && is_customize_preview() )
        || isset( $_POST['customize_changeset_uuid'] )
        || isset( $_POST['customized'] )
        || ( defined('DOING_CUSTOMIZE_PREVIEW') && DOING_CUSTOMIZE_PREVIEW )
        || ( defined('DOING_CUSTOMIZE_SELETIVE_REFRESH') && DOING_CUSTOMIZE_SELETIVE_REFRESH ) // safeguard
        || ( defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && strpos( (string) $_POST['action'], 'customize' ) === 0 )
        || ( defined('REST_REQUEST') && REST_REQUEST && isset($_GET['customize_changeset_uuid']) )
    ) {
        return;
    }

    // Hard filter: ignore post types used by Customizer and system internals
    $ptype = get_post_type( $post_id );
    if ( in_array( $ptype, array( 'customize_changeset', 'custom_css', 'revision', 'nav_menu_item' ), true ) ) {
        return;
    }

    // Optionale: ignora anche gli attachment (salvataggi media durante customizer)
    if ( $ptype === 'attachment' ) {
        return;
    }

    // Check if this post has been processed recently (within the last 10 seconds)
    // This helps prevent duplicate processing due to multiple save_post hooks
    $transient_name = 'cheshire_memory_processed_' . $post_id;
    if ( get_transient( $transient_name ) ) {
        // Already processed recently, skip
        return;
    }

    // Set a transient to indicate this post has been processed
    set_transient( $transient_name, true, 10 );

    // Check if declarative memory upload is enabled
    $enable_declarative_memory = get_option( 'cheshire_plugin_enable_declarative_memory', 'off' );
    if ( $enable_declarative_memory !== 'on' ) {
        return;
    }

    // Check if this post is excluded from declarative memory upload
    $exclude_from_declarative_memory = get_post_meta( $post_id, '_cheshire_exclude_from_declarative_memory', true );
    if ( $exclude_from_declarative_memory === '1' ) {
        return;
    }

    // Check if this is a revision
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Check if this is a published post
    if ( $post->post_status !== 'publish' ) {
        return;
    }

    // Check if the post type is enabled for declarative memory (if any selection is configured)
    $selected_post_types = get_option( 'cheshire_plugin_declarative_memory_post_types', array() );
    if ( is_array( $selected_post_types ) && ! empty( $selected_post_types ) ) {
        $current_post_type = get_post_type( $post_id );
        if ( ! in_array( $current_post_type, $selected_post_types, true ) ) {
            return;
        }
    }

    // Schedule asynchronous processing via WP-Cron to prevent blocking admin post saves
    wp_schedule_single_event( time(), 'cheshirecat_async_send_declarative_memory', array( (int) $post_id, (bool) $update ) );
    if ( function_exists( 'spawn_cron' ) ) {
        spawn_cron();
    }
}

/**
 * Async callback for uploading/updating post content in Cheshire Cat declarative memory.
 *
 * @since 1.0.4
 * @param int  $post_id The post ID.
 * @param bool $update  Whether this is an existing post being updated.
 * @return void
 */
function cheshirecat_async_send_to_declarative_memory( $post_id, $update ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_status !== 'publish' ) {
        return;
    }

    // Get Cheshire Cat configuration
    $cheshire_plugin_url = get_option( 'cheshire_plugin_url' );
    $cheshire_plugin_url_v2 = get_option( 'cheshire_plugin_url_v2', '' );
    $cheshire_plugin_token = get_option( 'cheshire_plugin_token' );
    $cheshire_plugin_cat_version = get_option( 'cheshire_plugin_cat_version', 'v1' );
    $cheshire_plugin_api_key = get_option( 'cheshire_plugin_api_key', '' );

    $active_cheshire_url = ( $cheshire_plugin_cat_version === 'v2' ) ? $cheshire_plugin_url_v2 : $cheshire_plugin_url;

    // Validate configuration
    if ( empty( $active_cheshire_url ) || ( $cheshire_plugin_cat_version === 'v1' && empty( $cheshire_plugin_token ) ) || ( $cheshire_plugin_cat_version === 'v2' && empty( $cheshire_plugin_api_key ) ) ) {
        return;
    }

    // Initialize Cheshire Cat client
    $cheshire_cat = new inc\classes\Custom_Cheshire_Cat( $active_cheshire_url, $cheshire_plugin_token, $cheshire_plugin_cat_version, $cheshire_plugin_api_key );

    // If this is an update, first delete the existing content from declarative memory
    if ( $update ) {
        cheshirecat_delete_from_declarative_memory( $post_id, $cheshire_cat );
    }

    // Send the content to declarative memory
    cheshirecat_upload_to_declarative_memory( $post_id, $post, $cheshire_cat );
}

/**
 * Delete post content from Cheshire Cat declarative memory.
 *
 * @since 0.8.0
 * @param int                      $post_id      The post ID.
 * @param inc\classes\Custom_Cheshire_Cat $cheshire_cat The Cheshire Cat client.
 * @return bool Whether the deletion was successful.
 */
function cheshirecat_delete_from_declarative_memory( $post_id, $cheshire_cat ) {
    try {
        // Get the client for API requests
        $client = $cheshire_cat->getClient();

        // Set up the filter to find the point by wp_id
        $metadata = [
            'wp_id' => (string) $post_id
        ];

        // Use the deleteMemoryPointsByMetadata method to delete points by metadata
        $response = $client->deleteMemoryPointsByMetadata('declarative', $metadata);

        // Log the response for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cheshire Cat declarative memory deletion response: ' .
                ($response ? $response->getStatusCode() : 'null') .
                ' - Metadata: ' . json_encode($metadata));
        }

        // Check if the request was successful
        if ($response && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return true;
        }

        return false;
    } catch (\Exception $e) {
        // Log the error if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cheshire Cat declarative memory deletion error: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Upload post content to Cheshire Cat declarative memory.
 *
 * @since 0.8.0
 * @param int                      $post_id      The post ID.
 * @param WP_Post                  $post         The post object.
 * @param inc\classes\Custom_Cheshire_Cat $cheshire_cat The Cheshire Cat client.
 * @return bool Whether the upload was successful.
 */
function cheshirecat_upload_to_declarative_memory( $post_id, $post, $cheshire_cat ) {
    try {
        // Get the client for API requests
        $client = $cheshire_cat->getClient();

        // Get the post content
        $content = $post->post_content;

        // Strip shortcodes
        $content = strip_shortcodes($content);

        // Strip HTML tags
        $content = wp_strip_all_tags($content);

        // Get the post URL
        $post_url = get_permalink($post_id);

        // Set up the metadata
        $metadata = [
            'origin' => 'WordPress',
            'url' => $post_url,
            'wp_id' => (string) $post_id,
            'title' => $post->post_title
        ];

        // Check if this is a WooCommerce product
        if ($post->post_type === 'product' && function_exists('wc_get_product')) {
            $product = wc_get_product($post_id);
            if ($product) {
                // Add short description if available
                $short_description = $product->get_short_description();
                if (!empty($short_description)) {
                    $short_description = wp_strip_all_tags($short_description);
                    $content = "Short Description: " . $short_description . "\n\nFull Description: " . $content;
                    $metadata['short_description'] = $short_description;
                }

                // Add product attributes
                $attributes = $product->get_attributes();
                if (!empty($attributes)) {
                    $attributes_text = "Product Characteristics:\n";
                    foreach ($attributes as $attribute) {
                        if ($attribute->is_taxonomy()) {
                            $attribute_taxonomy = $attribute->get_taxonomy_object();
                            $attribute_values = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
                            if (!empty($attribute_values)) {
                                $attributes_text .= $attribute_taxonomy->attribute_label . ": " . implode(', ', $attribute_values) . "\n";
                                $metadata['attribute_' . $attribute_taxonomy->attribute_label] = implode(', ', $attribute_values);
                            }
                        } else {
                            $attributes_text .= $attribute->get_name() . ": " . implode(', ', $attribute->get_options()) . "\n";
                            $metadata['attribute_' . $attribute->get_name()] = implode(', ', $attribute->get_options());
                        }
                    }
                    $content .= "\n\n" . $attributes_text;
                }
            }
        }

        // Set up the data to send
        $data = [
            'content' => $content,
            'metadata' => $metadata
        ];

        // Use the createMemoryPoint method to create a new memory point
        $response = $client->createMemoryPoint('declarative', $data);

        // Log the response for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cheshire Cat declarative memory upload response: ' .
                ($response ? $response->getStatusCode() : 'null') .
                ' - Data: ' . json_encode($data));
        }

        // Check if the request was successful
        if ($response && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return true;
        }

        return false;
    } catch (\Exception $e) {
        // Log the error if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cheshire Cat declarative memory upload error: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Handle post deletion and remove content from Cheshire Cat declarative memory.
 *
 * This function is called when a post is deleted and removes the post content
 * from the Cheshire Cat declarative memory if the feature is enabled.
 *
 * @since 0.8.0
 * @param int $post_id The post ID.
 * @return void
 */
function cheshirecat_handle_post_deletion( $post_id ) {
    // Skip Customizer contexts and its data posts
    if (
        ( function_exists('is_customize_preview') && is_customize_preview() )
        || isset( $_POST['customize_changeset_uuid'] )
        || isset( $_POST['customized'] )
        || ( defined('DOING_CUSTOMIZE_PREVIEW') && DOING_CUSTOMIZE_PREVIEW )
        || ( defined('DOING_CUSTOMIZE_SELETIVE_REFRESH') && DOING_CUSTOMIZE_SELETIVE_REFRESH )
        || ( defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && strpos( (string) $_POST['action'], 'customize' ) === 0 )
        || ( defined('REST_REQUEST') && REST_REQUEST && isset($_GET['customize_changeset_uuid']) )
    ) {
        return;
    }

    // Check if declarative memory upload is enabled
    $enable_declarative_memory = get_option( 'cheshire_plugin_enable_declarative_memory', 'off' );
    if ( $enable_declarative_memory !== 'on' ) {
        return;
    }

    // Check if this is a revision
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Hard filter: ignore post types used by Customizer and system internals
    $ptype = get_post_type( $post_id );
    if ( in_array( $ptype, array( 'customize_changeset', 'custom_css', 'revision', 'nav_menu_item' ), true ) ) {
        return;
    }
    if ( $ptype === 'attachment' ) {
        return;
    }

    // If post type filtering is configured, ensure this post type is allowed
    $selected_post_types = get_option( 'cheshire_plugin_declarative_memory_post_types', array() );
    if ( is_array( $selected_post_types ) && ! empty( $selected_post_types ) ) {
        if ( ! in_array( $ptype, $selected_post_types, true ) ) {
            return;
        }
    }

    // Schedule asynchronous processing via WP-Cron to prevent blocking admin post deletion
    wp_schedule_single_event( time(), 'cheshirecat_async_delete_declarative_memory', array( (int) $post_id ) );
    if ( function_exists( 'spawn_cron' ) ) {
        spawn_cron();
    }
}

/**
 * Async callback for removing post content from Cheshire Cat declarative memory on post deletion.
 *
 * @since 1.0.4
 * @param int $post_id The post ID.
 * @return void
 */
function cheshirecat_async_delete_from_declarative_memory( $post_id ) {
    // Get Cheshire Cat configuration
    $cheshire_plugin_url = get_option( 'cheshire_plugin_url' );
    $cheshire_plugin_url_v2 = get_option( 'cheshire_plugin_url_v2', '' );
    $cheshire_plugin_token = get_option( 'cheshire_plugin_token' );
    $cheshire_plugin_cat_version = get_option( 'cheshire_plugin_cat_version', 'v1' );
    $cheshire_plugin_api_key = get_option( 'cheshire_plugin_api_key', '' );

    $active_cheshire_url = ( $cheshire_plugin_cat_version === 'v2' ) ? $cheshire_plugin_url_v2 : $cheshire_plugin_url;

    // Validate configuration
    if ( empty( $active_cheshire_url ) || ( $cheshire_plugin_cat_version === 'v1' && empty( $cheshire_plugin_token ) ) || ( $cheshire_plugin_cat_version === 'v2' && empty( $cheshire_plugin_api_key ) ) ) {
        return;
    }

    // Initialize Cheshire Cat client
    $cheshire_cat = new inc\classes\Custom_Cheshire_Cat( $active_cheshire_url, $cheshire_plugin_token, $cheshire_plugin_cat_version, $cheshire_plugin_api_key );

    // Delete the content from declarative memory
    cheshirecat_delete_from_declarative_memory( $post_id, $cheshire_cat );

    // Log the deletion for debugging
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'Cheshire Cat declarative memory: Deleted content for post ID ' . $post_id . ' due to post deletion' );
    }
}

/**
 * Handle post status transition and remove content from Cheshire Cat declarative memory when trashed.
 *
 * This function is called when a post's status changes and removes the post content
 * from the Cheshire Cat declarative memory if the post is being moved to trash.
 *
 * @since 0.8.0
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 * @return void
 */
function cheshirecat_handle_post_trash( $new_status, $old_status, $post ) {
    // Only proceed if the post is being moved to trash from a published state
    if ( $new_status !== 'trash' || $old_status !== 'publish' ) {
        return;
    }

    // Skip Customizer contexts and its data posts
    if (
        ( function_exists('is_customize_preview') && is_customize_preview() )
        || isset( $_POST['customize_changeset_uuid'] )
        || isset( $_POST['customized'] )
        || ( defined('DOING_CUSTOMIZE_PREVIEW') && DOING_CUSTOMIZE_PREVIEW )
        || ( defined('DOING_CUSTOMIZE_SELETIVE_REFRESH') && DOING_CUSTOMIZE_SELETIVE_REFRESH )
        || ( defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && strpos( (string) $_POST['action'], 'customize' ) === 0 )
        || ( defined('REST_REQUEST') && REST_REQUEST && isset($_GET['customize_changeset_uuid']) )
    ) {
        return;
    }

    // Check if declarative memory upload is enabled
    $enable_declarative_memory = get_option( 'cheshire_plugin_enable_declarative_memory', 'off' );
    if ( $enable_declarative_memory !== 'on' ) {
        return;
    }

    // Get post ID
    $post_id = $post->ID;

    // Check if this is a revision
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Schedule asynchronous processing via WP-Cron to prevent blocking admin post trashing
    wp_schedule_single_event( time(), 'cheshirecat_async_trash_declarative_memory', array( (int) $post_id ) );
    if ( function_exists( 'spawn_cron' ) ) {
        spawn_cron();
    }
}

/**
 * Async callback for removing post content from Cheshire Cat declarative memory when trashed.
 *
 * @since 1.0.4
 * @param int $post_id The post ID.
 * @return void
 */
function cheshirecat_async_trash_from_declarative_memory( $post_id ) {
    // Get Cheshire Cat configuration
    $cheshire_plugin_url = get_option( 'cheshire_plugin_url' );
    $cheshire_plugin_url_v2 = get_option( 'cheshire_plugin_url_v2', '' );
    $cheshire_plugin_token = get_option( 'cheshire_plugin_token' );
    $cheshire_plugin_cat_version = get_option( 'cheshire_plugin_cat_version', 'v1' );
    $cheshire_plugin_api_key = get_option( 'cheshire_plugin_api_key', '' );

    $active_cheshire_url = ( $cheshire_plugin_cat_version === 'v2' ) ? $cheshire_plugin_url_v2 : $cheshire_plugin_url;

    // Validate configuration
    if ( empty( $active_cheshire_url ) || ( $cheshire_plugin_cat_version === 'v1' && empty( $cheshire_plugin_token ) ) || ( $cheshire_plugin_cat_version === 'v2' && empty( $cheshire_plugin_api_key ) ) ) {
        return;
    }

    // Initialize Cheshire Cat client
    $cheshire_cat = new inc\classes\Custom_Cheshire_Cat( $active_cheshire_url, $cheshire_plugin_token, $cheshire_plugin_cat_version, $cheshire_plugin_api_key );

    // Delete the content from declarative memory
    cheshirecat_delete_from_declarative_memory( $post_id, $cheshire_cat );

    // Log the deletion for debugging
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'Cheshire Cat declarative memory: Deleted content for post ID ' . $post_id . ' due to post being trashed' );
    }
}

// Hook into post save to send content to declarative memory
add_action('save_post', __NAMESPACE__ . '\cheshirecat_send_to_declarative_memory', 10, 3);

// Hook into post deletion to remove content from declarative memory
add_action('before_delete_post', __NAMESPACE__ . '\cheshirecat_handle_post_deletion', 10, 1);

// Hook into post status transition to handle trashing
add_action('transition_post_status', __NAMESPACE__ . '\cheshirecat_handle_post_trash', 10, 3);

// Async WP-Cron action hooks for non-blocking execution
add_action('cheshirecat_async_send_declarative_memory', __NAMESPACE__ . '\cheshirecat_async_send_to_declarative_memory', 10, 2);
add_action('cheshirecat_async_delete_declarative_memory', __NAMESPACE__ . '\cheshirecat_async_delete_from_declarative_memory', 10, 1);
add_action('cheshirecat_async_trash_declarative_memory', __NAMESPACE__ . '\cheshirecat_async_trash_from_declarative_memory', 10, 1);

/**
 * AJAX handler for getting the count of posts to process.
 *
 * @since 0.8.2
 * @return void
 */
function cheshirecat_get_posts_count() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'cheshire_declarative_memory_sync_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
        return;
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Get post types
    $post_types = isset($_POST['post_types']) ? array_map('sanitize_text_field', wp_unslash($_POST['post_types'])) : array('post');

    // Get post status
    $post_status = isset($_POST['post_status']) ? sanitize_text_field(wp_unslash($_POST['post_status'])) : 'publish';

    // Get date range
    $date_range = isset($_POST['date_range']) ? sanitize_text_field(wp_unslash($_POST['date_range'])) : 'all';

    // Build query args
    $args = array(
        'post_type' => $post_types,
        'post_status' => $post_status === 'any' ? array('publish', 'draft', 'pending', 'private', 'future') : $post_status,
        'posts_per_page' => -1,
        'fields' => 'ids',
    );

    // Add date query if needed
    if ($date_range !== 'all') {
        $date_query = array();
        $now = current_time('mysql');

        switch ($date_range) {
            case 'last_day':
                $date_query = array(
                    'after' => date('Y-m-d H:i:s', strtotime('-1 day', strtotime($now))),
                );
                break;
            case 'last_week':
                $date_query = array(
                    'after' => date('Y-m-d H:i:s', strtotime('-1 week', strtotime($now))),
                );
                break;
            case 'last_month':
                $date_query = array(
                    'after' => date('Y-m-d H:i:s', strtotime('-1 month', strtotime($now))),
                );
                break;
            case 'last_year':
                $date_query = array(
                    'after' => date('Y-m-d H:i:s', strtotime('-1 year', strtotime($now))),
                );
                break;
        }

        if (!empty($date_query)) {
            $args['date_query'] = array($date_query);
        }
    }

    // Get posts
    $query = new \WP_Query($args);
    $post_ids = $query->posts;

    // Send response
    wp_send_json_success(array(
        'count' => count($post_ids),
        'post_ids' => $post_ids,
    ));
}
add_action('wp_ajax_cheshire_get_posts_count', __NAMESPACE__ . '\cheshirecat_get_posts_count');

/**
 * AJAX handler for processing a batch of posts.
 *
 * @since 0.8.2
 * @return void
 */
function cheshirecat_process_posts_batch() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'cheshire_declarative_memory_sync_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
        return;
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Get post IDs
    $post_ids = isset($_POST['post_ids']) ? array_map('intval', wp_unslash($_POST['post_ids'])) : array();

    if (empty($post_ids)) {
        wp_send_json_error(array('message' => 'No posts to process'));
        return;
    }

        // Get Cheshire Cat configuration
    $cheshire_plugin_url = get_option('cheshire_plugin_url');
    $cheshire_plugin_url_v2 = get_option('cheshire_plugin_url_v2', '');
    $cheshire_plugin_token = get_option('cheshire_plugin_token');
    $cheshire_plugin_cat_version = get_option('cheshire_plugin_cat_version', 'v1');
    $cheshire_plugin_api_key = get_option('cheshire_plugin_api_key', '');

    // Select active URL based on version
    $active_cheshire_url = ($cheshire_plugin_cat_version === 'v2') ? $cheshire_plugin_url_v2 : $cheshire_plugin_url;

    // Validate configuration
    if (empty($active_cheshire_url) || ($cheshire_plugin_cat_version === 'v1' && empty($cheshire_plugin_token)) || ($cheshire_plugin_cat_version === 'v2' && empty($cheshire_plugin_api_key))) {
        wp_send_json_error(array('message' => 'Cheshire Cat is not properly configured'));
        return;
    }

    // Initialize Cheshire Cat client
    $cheshire_cat = new inc\classes\Custom_Cheshire_Cat($active_cheshire_url, $cheshire_plugin_token, $cheshire_plugin_cat_version, $cheshire_plugin_api_key);

    // Process each post
    $processed = 0;
    $success = 0;
    $error = 0;
    $current_title = '';

    foreach ($post_ids as $post_id) {
        $post = get_post($post_id);

        if (!$post) {
            $error++;
            continue;
        }

        // First delete any existing content for this post
        cheshirecat_delete_from_declarative_memory($post_id, $cheshire_cat);

        // Then upload the content
        $result = cheshirecat_upload_to_declarative_memory($post_id, $post, $cheshire_cat);

        if ($result) {
            $success++;
        } else {
            $error++;
        }

        $processed++;
        $current_title = $post->post_title;
    }

    // Send response
    wp_send_json_success(array(
        'processed' => $processed,
        'success' => $success,
        'error' => $error,
        'current_title' => $current_title,
    ));
}
add_action('wp_ajax_cheshire_process_posts_batch', __NAMESPACE__ . '\cheshirecat_process_posts_batch');
