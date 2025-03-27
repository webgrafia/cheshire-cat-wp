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
        wp_die(__('You do not have sufficient permissions to access this page.', 'cheshire-cat-wp'));
    }

    // Handle form submission
    if (isset($_POST['cheshire_style_nonce']) && wp_verify_nonce($_POST['cheshire_style_nonce'], 'cheshire_style_save_settings')) {
        // Save the options
        update_option('cheshire_chat_background_color', sanitize_text_field($_POST['cheshire_chat_background_color']));
        update_option('cheshire_chat_text_color', sanitize_text_field($_POST['cheshire_chat_text_color']));
        update_option('cheshire_chat_user_message_color', sanitize_text_field($_POST['cheshire_chat_user_message_color']));
        update_option('cheshire_chat_bot_message_color', sanitize_text_field($_POST['cheshire_chat_bot_message_color']));
        update_option('cheshire_chat_button_color', sanitize_text_field($_POST['cheshire_chat_button_color']));
        update_option('cheshire_chat_font_family', sanitize_text_field($_POST['cheshire_chat_font_family']));
        update_option('cheshire_chat_welcome_message', sanitize_textarea_field($_POST['cheshire_chat_welcome_message'])); // Save the welcome message

        // Display a success message
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Style settings saved.', 'cheshire-cat-wp') . '</p></div>';
    }

    // Get the current options
    $chat_background_color = get_option('cheshire_chat_background_color', '#ffffff'); // Default white
    $chat_text_color = get_option('cheshire_chat_text_color', '#333333'); // Default dark gray
    $chat_user_message_color = get_option('cheshire_chat_user_message_color', '#4caf50'); // Default green
    $chat_bot_message_color = get_option('cheshire_chat_bot_message_color', '#ffffff'); // Default white
    $chat_button_color = get_option('cheshire_chat_button_color', '#0078d7'); // Default blue
    $chat_font_family = get_option('cheshire_chat_font_family', 'Arial, sans-serif'); // Default Arial
    $cheshire_chat_welcome_message = get_option('cheshire_chat_welcome_message', __('Hello! How can I help you?', 'cheshire-cat-wp')); // Default welcome message
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('cheshire_style_save_settings', 'cheshire_style_nonce'); ?>
            <div class="cheshire-cat-style-settings-wrapper">
                <div class="cheshire-cat-style-settings-section">
                    <h2><?php _e('Colors', 'cheshire-cat-wp'); ?></h2>
                    <div class="cheshire-cat-settings-table-wrapper">
                        <table class="form-table widefat striped cheshire-cat-settings-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="cheshire_chat_background_color"><?php _e('Chat Background Color', 'cheshire-cat-wp'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="cheshire_chat_background_color" name="cheshire_chat_background_color" value="<?php echo esc_attr($chat_background_color); ?>" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="cheshire_chat_text_color"><?php _e('Chat Text Color', 'cheshire-cat-wp'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="cheshire_chat_text_color" name="cheshire_chat_text_color" value="<?php echo esc_attr($chat_text_color); ?>" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="cheshire_chat_user_message_color"><?php _e('User Message Color', 'cheshire-cat-wp'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="cheshire_chat_user_message_color" name="cheshire_chat_user_message_color" value="<?php echo esc_attr($chat_user_message_color); ?>" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="cheshire_chat_bot_message_color"><?php _e('Bot Message Color', 'cheshire-cat-wp'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="cheshire_chat_bot_message_color" name="cheshire_chat_bot_message_color" value="<?php echo esc_attr($chat_bot_message_color); ?>" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="cheshire_chat_button_color"><?php _e('Button Color', 'cheshire-cat-wp'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="cheshire_chat_button_color" name="cheshire_chat_button_color" value="<?php echo esc_attr($chat_button_color); ?>" />
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="cheshire-cat-style-settings-section">
                    <h2><?php _e('Typography', 'cheshire-cat-wp'); ?></h2>
                    <div class="cheshire-cat-settings-table-wrapper">
                        <table class="form-table widefat striped cheshire-cat-settings-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="cheshire_chat_font_family"><?php _e('Font Family', 'cheshire-cat-wp'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="cheshire_chat_font_family" name="cheshire_chat_font_family" value="<?php echo esc_attr($chat_font_family); ?>" class="regular-text" />
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="cheshire-cat-style-settings-section">
                    <h2><?php _e('Welcome Message', 'cheshire-cat-wp'); ?></h2>
                    <div class="cheshire-cat-settings-table-wrapper">
                        <table class="form-table widefat striped cheshire-cat-settings-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="cheshire_chat_welcome_message"><?php _e('Welcome Message', 'cheshire-cat-wp'); ?></label>
                                </th>
                                <td>
                                    <textarea id="cheshire_chat_welcome_message" name="cheshire_chat_welcome_message" class="large-text"><?php echo esc_textarea($cheshire_chat_welcome_message); ?></textarea>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php submit_button(__('Save Style', 'cheshire-cat-wp')); ?>
        </form>
    </div>
    <style>
        .cheshire-cat-style-settings-wrapper {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .cheshire-cat-style-settings-section {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            background-color: #fff;
        }

        .cheshire-cat-style-settings-section h2 {
            margin-top: 0;
            margin-bottom: 15px;
        }

        .cheshire-cat-settings-table-wrapper {
            margin-bottom: 20px;
        }

        .cheshire-cat-settings-table th,
        .cheshire-cat-settings-table td {
            padding: 10px;
            vertical-align: middle;
        }

        .cheshire-cat-settings-table th {
            width: 30%;
            font-weight: bold;
        }

        .cheshire-cat-settings-table .regular-text {
            width: 100%;
            max-width: 400px;
        }

        .cheshire-cat-settings-table input[type="color"] {
            width: 60px;
            height: 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .cheshire-cat-settings-table .large-text {
            width: 100%;
            max-width: 600px;
            height: 100px;
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 768px) {
            .cheshire-cat-settings-table th,
            .cheshire-cat-settings-table td {
                display: block;
                width: 100%;
                padding: 5px;
            }

            .cheshire-cat-settings-table th {
                margin-bottom: 5px;
            }

            .cheshire-cat-settings-table .regular-text,
            .cheshire-cat-settings-table .large-text {
                max-width: 100%;
            }
        }
    </style>
    <?php
}