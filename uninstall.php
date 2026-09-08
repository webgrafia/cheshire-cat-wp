<?php
/**
 * Fired when the Cheshire Cat Chatbot plugin is uninstalled.
 *
 * This file is responsible for cleaning up plugin data from the database.
 *
 * @package   CheshireCatWp
 * @since     0.5
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$option_names = array(
    'cheshire_plugin_url',
    'cheshire_plugin_url_v2',
    'cheshire_plugin_token',
    'cheshire_plugin_cat_version',
    'cheshire_plugin_api_key',
    'cheshire_plugin_global_chat',
    'cheshire_plugin_enable_avatar',
    'cheshire_plugin_enable_context',
    'cheshire_chat_background_color',
    'cheshire_chat_text_color',
    'cheshire_chat_user_message_color',
    'cheshire_chat_bot_message_color',
    'cheshire_chat_button_color',
    'cheshire_chat_font_family',
    'cheshire_chat_welcome_message',
    'cheshire_chat_avatar_image'
);

// Loop through the option names and delete them from the database.
foreach ( $option_names as $option_name ) {
    if ( ! empty( $option_name ) ) {
        delete_option( $option_name );
    }
}
