<?php

namespace CheshireCatWp;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle AJAX request for sending a message to Cheshire Cat.
 */
function cheshire_send_message()
{
    check_ajax_referer('cheshire_ajax_nonce', 'nonce');

    if (isset($_POST['message'])) {
        $message = sanitize_text_field(wp_unslash($_POST['message']));

        $cheshire_plugin_url = get_option('cheshire_plugin_url');
        $cheshire_plugin_token = get_option('cheshire_plugin_token');

        if (empty($cheshire_plugin_url) || empty($cheshire_plugin_token)) {
            wp_send_json_error(__('Cheshire Cat URL or Token not set.', 'cheshire-cat-wp'));
        }

        $cheshire_cat = new CustomCheshireCat($cheshire_plugin_url, $cheshire_plugin_token);

        try {
            $response = $cheshire_cat->sendMessage($message);
            wp_send_json_success($response);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    } else {
        wp_send_json_error(__('Message not provided.', 'cheshire-cat-wp'));
    }
}
add_action('wp_ajax_cheshire_send_message', __NAMESPACE__ . '\cheshire_send_message');
add_action('wp_ajax_nopriv_cheshire_send_message', __NAMESPACE__ . '\cheshire_send_message');