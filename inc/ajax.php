<?php
/**
 * AJAX handlers for Cheshire Cat Chatbot
 *
 * @package CheshireCatChatbot
 */

namespace webgrafia\cheshirecat;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle AJAX request for sending a message to Cheshire Cat.
 *
 * This function processes both 'cheshire_send_message' and 'cheshire_plugin_ajax' actions.
 *
 * @since 0.1
 * @return void
 */
function cheshirecat_process_message() {
    // Verify nonce for security.
    check_ajax_referer( 'cheshire_ajax_nonce', 'nonce' );

    // Check if message is provided.
    if ( ! isset( $_POST['message'] ) ) {
        wp_send_json_error( __( 'Message not provided.', 'cheshire-cat-chatbot' ) );
        return;
    }

    // Sanitize the message.
    $message = sanitize_text_field( wp_unslash( $_POST['message'] ) );

    // Get page information if provided
    $page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;
    $page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';

    // Check if request is coming from the editor or prompt tester
    // Explicitly check for various true values to handle different types
    $from_editor = false;
    if (isset($_POST['from_editor'])) {
        $value = $_POST['from_editor'];
        if ($value === true || $value === 'true' || $value === '1' || $value === 1 || $value === 'yes' || $value === 'on') {
            $from_editor = true;
        }
    }


    // Get Cheshire Cat configuration.
    $cheshire_plugin_url = get_option('cheshire_plugin_url');
    $cheshire_plugin_token = get_option('cheshire_plugin_token');
    $cheshire_plugin_cat_version = get_option('cheshire_plugin_cat_version', 'v1');
    $cheshire_plugin_api_key = get_option('cheshire_plugin_api_key', '');
    $cheshire_plugin_url_v2 = get_option('cheshire_plugin_url_v2', '');
    // Select active URL based on version
    $active_cheshire_url = ($cheshire_plugin_cat_version === 'v2') ? $cheshire_plugin_url_v2 : $cheshire_plugin_url;

    // Validate configuration.
    if (empty($active_cheshire_url) || ($cheshire_plugin_cat_version === 'v1' && empty($cheshire_plugin_token)) || ($cheshire_plugin_cat_version === 'v2' && empty($cheshire_plugin_api_key))) {
        wp_send_json_error(__('Cheshire Cat URL or Authentication credentials not set.', 'cheshire-cat-chatbot'));
        return;
    }

    // Initialize Cheshire Cat client.
    $cheshire_cat = new inc\classes\Custom_Cheshire_Cat($active_cheshire_url, $cheshire_plugin_token, $cheshire_plugin_cat_version, $cheshire_plugin_api_key);

    // Set page context information
    $cheshire_cat->setPageContext($page_id, $page_url);

    // Set whether the request is coming from the editor
    $cheshire_cat->setFromEditor($from_editor);

    try {
        // Send message and get response.
        $response = $cheshire_cat->sendMessage( $message );
        wp_send_json_success( $response );
    } catch ( \Exception $e ) {
        // Handle errors.
        wp_send_json_error( $e->getMessage() );
    }
}

/**
 * Handle AJAX request for getting the welcome message.
 *
 * @since 0.5.4
 * @return void
 */
function cheshirecat_get_welcome_message() {
    // Verify nonce for security.
    check_ajax_referer( 'cheshire_ajax_nonce', 'nonce' );

    ob_start();
    cheshirecat_display_welcome_message();
    $welcome_message = ob_get_clean();

    wp_send_json_success( $welcome_message );
}

/**
 * Handle AJAX request for getting predefined responses.
 *
 * @since 0.6.1
 * @return void
 */
function cheshirecat_get_predefined_responses() {
    // Verify nonce for security.
    check_ajax_referer( 'cheshire_ajax_nonce', 'nonce' );

    // Get page ID if provided
    $page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;

    // Get product category information if provided
    $is_product_category = isset( $_POST['is_product_category'] ) && $_POST['is_product_category'] === '1';
    $product_category_id = isset( $_POST['product_category_id'] ) ? absint( $_POST['product_category_id'] ) : 0;

    // Check if predefined responses should be shown in content
    $show_predefined_in_content = get_option( 'cheshire_plugin_show_predefined_in_content', 'off' );

    // If predefined responses are shown in content, don't show them in chat
    if (( $show_predefined_in_content === 'on' ) && ($page_id)) {
        wp_send_json_success( array() );
        return;
    }

    // Get predefined responses with post override support
    $responses = cheshirecat_get_predefined_responses_with_override( $page_id, $is_product_category, $product_category_id );

    // If empty, return empty array
    if ( empty( $responses ) ) {
        wp_send_json_success( array() );
        return;
    }

    wp_send_json_success( $responses );
}

/**
 * Handle AJAX request for getting context information.
 *
 * @since 0.6.5
 * @return void
 */
function cheshirecat_get_context_information() {
    // Verify nonce for security.
    check_ajax_referer( 'cheshire_ajax_nonce', 'nonce' );

    // Get page information if provided
    $page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;
    $page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';

    // Get Cheshire Cat configuration.
    $cheshire_plugin_url   = get_option( 'cheshire_plugin_url' );
    $cheshire_plugin_token = get_option( 'cheshire_plugin_token' );
    $cheshire_plugin_cat_version = get_option('cheshire_plugin_cat_version', 'v1');
    $cheshire_plugin_api_key = get_option('cheshire_plugin_api_key', '');
    $cheshire_plugin_url_v2 = get_option('cheshire_plugin_url_v2', '');
    // Select active URL based on version
    $active_cheshire_url = ($cheshire_plugin_cat_version === 'v2') ? $cheshire_plugin_url_v2 : $cheshire_plugin_url;

    // Create an instance of Custom_Cheshire_Cat
    $cheshire_cat = new inc\classes\Custom_Cheshire_Cat( $active_cheshire_url, $cheshire_plugin_token, $cheshire_plugin_cat_version, $cheshire_plugin_api_key );

    // Set page context information
    $cheshire_cat->setPageContext($page_id, $page_url);

    // Set from_editor to false since this is for WebSocket context
    $cheshire_cat->setFromEditor(false);

    // Get the context information
    // Since get_context_information is protected, we need to expose it through a public method
    $context_info = $cheshire_cat->getContextInformation();

    wp_send_json_success( $context_info );
}

// Register AJAX handlers for both logged-in and non-logged-in users.
// Support both action names for backward compatibility.
add_action( 'wp_ajax_cheshire_send_message', __NAMESPACE__ . '\cheshirecat_process_message' );
add_action( 'wp_ajax_nopriv_cheshire_send_message', __NAMESPACE__ . '\cheshirecat_process_message' );
add_action( 'wp_ajax_cheshire_plugin_ajax', __NAMESPACE__ . '\cheshirecat_process_message' );
add_action( 'wp_ajax_nopriv_cheshire_plugin_ajax', __NAMESPACE__ . '\cheshirecat_process_message' );
add_action( 'wp_ajax_cheshire_get_welcome_message', __NAMESPACE__ . '\cheshirecat_get_welcome_message' );
add_action( 'wp_ajax_nopriv_cheshire_get_welcome_message', __NAMESPACE__ . '\cheshirecat_get_welcome_message' );
add_action( 'wp_ajax_cheshire_get_predefined_responses', __NAMESPACE__ . '\cheshirecat_get_predefined_responses' );
add_action( 'wp_ajax_nopriv_cheshire_get_predefined_responses', __NAMESPACE__ . '\cheshirecat_get_predefined_responses' );
add_action( 'wp_ajax_cheshire_get_context_information', __NAMESPACE__ . '\cheshirecat_get_context_information' );
add_action( 'wp_ajax_nopriv_cheshire_get_context_information', __NAMESPACE__ . '\cheshirecat_get_context_information' );
