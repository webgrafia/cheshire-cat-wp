<?php

namespace CheshireCatWp\inc\admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration page callback.
 */
function cheshire_cat_configuration_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cheshire-cat-wp'));
    }

    // Handle form submission
    if (isset($_POST['cheshire_plugin_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cheshire_plugin_nonce'])), 'cheshire_plugin_save_settings')) {
        if (isset($_POST['cheshire_plugin_url'])) {
            $cheshire_plugin_url = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_url']));
            update_option('cheshire_plugin_url', esc_url_raw($cheshire_plugin_url));
        }
        if (isset($_POST['cheshire_plugin_token'])) {
            $cheshire_plugin_token = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_token']));
            update_option('cheshire_plugin_token', $cheshire_plugin_token);
        }
        if (isset($_POST['cheshire_plugin_global_chat'])) {
            $cheshire_plugin_global_chat = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_global_chat']));
            update_option('cheshire_plugin_global_chat', $cheshire_plugin_global_chat);
        }
    }

    $cheshire_plugin_url = get_option('cheshire_plugin_url');
    $cheshire_plugin_token = get_option('cheshire_plugin_token');
    $cheshire_plugin_global_chat = get_option('cheshire_plugin_global_chat');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post">
            <?php wp_nonce_field('cheshire_plugin_save_settings', 'cheshire_plugin_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Cheshire Cat URL', 'cheshire-cat-wp'); ?></th>
                    <td><input type="text" name="cheshire_plugin_url" value="<?php echo esc_attr($cheshire_plugin_url); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Cheshire Cat Token', 'cheshire-cat-wp'); ?></th>
                    <td><input type="text" name="cheshire_plugin_token" value="<?php echo esc_attr($cheshire_plugin_token); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Global Chat', 'cheshire-cat-wp'); ?></th>
                    <td>
                        <input type="checkbox" name="cheshire_plugin_global_chat" <?php checked($cheshire_plugin_global_chat, 'on'); ?> />
                        <label for="cheshire_plugin_global_chat"><?php esc_html_e('Enable Global Chat', 'cheshire-cat-wp'); ?></label>
                        <p class="description"><?php esc_html_e('Check this box to enable the chat on every page of your website.', 'cheshire-cat-wp'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}