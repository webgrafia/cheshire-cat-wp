<?php

namespace CheshireCatWp\inc\admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Style page callback.
 */
function cheshire_cat_style_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cheshire-cat-wp'));
    }

    // Handle form submission
    if (isset($_POST['cheshire_style_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cheshire_style_nonce'])), 'cheshire_style_save_settings')) {
        if (isset($_POST['cheshire_chat_background_color'])) {
            $cheshire_chat_background_color = sanitize_hex_color(wp_unslash($_POST['cheshire_chat_background_color']));
            update_option('cheshire_chat_background_color', $cheshire_chat_background_color);
        }
        if (isset($_POST['cheshire_chat_text_color'])) {
            $cheshire_chat_text_color = sanitize_hex_color(wp_unslash($_POST['cheshire_chat_text_color']));
            update_option('cheshire_chat_text_color', $cheshire_chat_text_color);
        }
        if (isset($_POST['cheshire_chat_user_message_color'])) {
            $cheshire_chat_user_message_color = sanitize_hex_color(wp_unslash($_POST['cheshire_chat_user_message_color']));
            update_option('cheshire_chat_user_message_color', $cheshire_chat_user_message_color);
        }
        if (isset($_POST['cheshire_chat_bot_message_color'])) {
            $cheshire_chat_bot_message_color = sanitize_hex_color(wp_unslash($_POST['cheshire_chat_bot_message_color']));
            update_option('cheshire_chat_bot_message_color', $cheshire_chat_bot_message_color);
        }
        if (isset($_POST['cheshire_chat_button_color'])) {
            $cheshire_chat_button_color = sanitize_hex_color(wp_unslash($_POST['cheshire_chat_button_color']));
            update_option('cheshire_chat_button_color', $cheshire_chat_button_color);
        }
        if (isset($_POST['cheshire_chat_font_family'])) {
            $cheshire_chat_font_family = sanitize_text_field(wp_unslash($_POST['cheshire_chat_font_family']));
            update_option('cheshire_chat_font_family', $cheshire_chat_font_family);
        }
        if (isset($_POST['cheshire_chat_welcome_message'])) {
            $cheshire_chat_welcome_message = sanitize_textarea_field(wp_unslash($_POST['cheshire_chat_welcome_message']));
            update_option('cheshire_chat_welcome_message', $cheshire_chat_welcome_message);
        }
    }

    $cheshire_chat_background_color = get_option('cheshire_chat_background_color', '#ffffff');
    $cheshire_chat_text_color = get_option('cheshire_chat_text_color', '#333333');
    $cheshire_chat_user_message_color = get_option('cheshire_chat_user_message_color', '#4caf50');
    $cheshire_chat_bot_message_color = get_option('cheshire_chat_bot_message_color', '#ffffff');
    $cheshire_chat_button_color = get_option('cheshire_chat_button_color', '#0078d7');
    $cheshire_chat_font_family = get_option('cheshire_chat_font_family', 'Arial, sans-serif');
    $cheshire_chat_welcome_message = get_option('cheshire_chat_welcome_message', __('Hello! How can I help you?', 'cheshire-cat-wp'));
    ?>
    <div class="wrap">
        <h1><?php if (function_exists('get_admin_page_title')) {
                echo esc_html(get_admin_page_title());
            } ?></h1>
        <form method="post">
            <?php wp_nonce_field('cheshire_style_save_settings', 'cheshire_style_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Chat Background Color', 'cheshire-cat-wp'); ?></th>
                    <td><input type="color" name="cheshire_chat_background_color" value="<?php echo esc_attr($cheshire_chat_background_color); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Chat Text Color', 'cheshire-cat-wp'); ?></th>
                    <td><input type="color" name="cheshire_chat_text_color" value="<?php echo esc_attr($cheshire_chat_text_color); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Chat User Message Color', 'cheshire-cat-wp'); ?></th>
                    <td><input type="color" name="cheshire_chat_user_message_color" value="<?php echo esc_attr($cheshire_chat_user_message_color); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Chat Bot Message Color', 'cheshire-cat-wp'); ?></th>
                    <td><input type="color" name="cheshire_chat_bot_message_color" value="<?php echo esc_attr($cheshire_chat_bot_message_color); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Chat Button Color', 'cheshire-cat-wp'); ?></th>
                    <td><input type="color" name="cheshire_chat_button_color" value="<?php echo esc_attr($cheshire_chat_button_color); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Chat Font Family', 'cheshire-cat-wp'); ?></th>
                    <td><input type="text" name="cheshire_chat_font_family" value="<?php echo esc_attr($cheshire_chat_font_family); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Welcome Message', 'cheshire-cat-wp'); ?></th>
                    <td><textarea name="cheshire_chat_welcome_message" rows="5" cols="50"><?php echo esc_textarea($cheshire_chat_welcome_message); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}