<?php
/*
Plugin Name: Cheshire Cat WP
Description: A WordPress plugin to integrate the Cheshire Cat AI chatbot, offering seamless conversational AI for your site.
Version: 0.2
Author: Marco Buttarini
Author URI: https://bititup.it/
License: GPL3
Text Domain: cheshire-cat-wp
Domain Path: /languages
*/

namespace CheshireCatWp;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/shortcodes.php';
require_once __DIR__ . '/inc/ajax.php';
require_once __DIR__ . '/inc/classes/CustomCheshireCatClient.php';
require_once __DIR__ . '/inc/classes/CustomCheshireCat.php';

/**
 * Enqueue scripts and styles.
 */
function cheshire_enqueue_scripts()
{
    wp_enqueue_script('cheshire-chat-js', plugins_url('/assets/js/chat.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_style('cheshire-chat-css', plugins_url('/assets/css/chat.css', __FILE__), array(), '1.0');
    wp_localize_script('cheshire-chat-js', 'cheshire_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');

    // Add dynamic CSS
    wp_add_inline_style('cheshire-chat-css', cheshire_generate_dynamic_css());
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\cheshire_enqueue_scripts');

/**
 * Load plugin textdomain.
 */
function cheshire_load_textdomain()
{
    load_plugin_textdomain('cheshire-cat-wp', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', __NAMESPACE__ . '\cheshire_load_textdomain');

/**
 * Generate dynamic CSS based on the saved style settings.
 */
function cheshire_generate_dynamic_css()
{
    $chat_background_color = get_option('cheshire_chat_background_color', '#ffffff');
    $chat_text_color = get_option('cheshire_chat_text_color', '#333333');
    $chat_user_message_color = get_option('cheshire_chat_user_message_color', '#4caf50');
    $chat_bot_message_color = get_option('cheshire_chat_bot_message_color', '#ffffff');
    $chat_button_color = get_option('cheshire_chat_button_color', '#0078d7');
    $chat_font_family = get_option('cheshire_chat_font_family', 'Arial, sans-serif');

    $custom_css = "
        #cheshire-chat-container {
            background-color: {$chat_background_color};
            font-family: {$chat_font_family};
        }
        #cheshire-chat-messages {
            background-color: {$chat_background_color};
        }
        .user-message {
            background-color: {$chat_user_message_color};
        }
        .bot-message {
            background-color: {$chat_bot_message_color};
        }
        #cheshire-chat-send {
            color: {$chat_button_color};
        }
        #cheshire-chat-input {
            color: {$chat_text_color};
        }
        .bot-message, .error-message {
            color: {$chat_text_color};
        }
        .user-message {
            color: #fff;
        }
    ";

    return $custom_css;
}

/**
 * Display the welcome message.
 */
function cheshire_display_welcome_message()
{
    $welcome_message = get_option('cheshire_chat_welcome_message', __('Hello! How can I help you?', 'cheshire-cat-wp'));
    echo '<div class="bot-message"><p>' . esc_html($welcome_message) . '</p></div>';
}