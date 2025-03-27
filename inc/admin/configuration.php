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
        wp_die(__('You do not have sufficient permissions to access this page.', 'cheshire-cat-wp'));
    }

    // Handle form submission
    if (isset($_POST['cheshire_plugin_nonce']) && wp_verify_nonce($_POST['cheshire_plugin_nonce'], 'cheshire_plugin_save_settings')) {
        // Save the options
        update_option('cheshire_plugin_url', sanitize_text_field($_POST['cheshire_plugin_url']));
        update_option('cheshire_plugin_token', sanitize_text_field($_POST['cheshire_plugin_token']));
        update_option('cheshire_plugin_global_chat', isset($_POST['cheshire_plugin_global_chat']) ? 'on' : 'off');

        // Display a success message
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved.', 'cheshire-cat-wp') . '</p></div>';
    }

    // Get the current options
    $cheshire_url = get_option('cheshire_plugin_url');
    $cheshire_token = get_option('cheshire_plugin_token');
    $cheshire_global_chat = get_option('cheshire_plugin_global_chat');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('cheshire_plugin_save_settings', 'cheshire_plugin_nonce'); ?>
            <div class="cheshire-cat-settings-table-wrapper">
                <table class="form-table widefat striped cheshire-cat-settings-table">
                    <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="cheshire_plugin_url"><?php _e('Cheshire Cat URL', 'cheshire-cat-wp'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cheshire_plugin_url" name="cheshire_plugin_url" value="<?php echo esc_attr($cheshire_url); ?>" class="regular-text" placeholder="https://example.com" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="cheshire_plugin_token"><?php _e('Cheshire Cat Token', 'cheshire-cat-wp'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cheshire_plugin_token" name="cheshire_plugin_token" value="<?php echo esc_attr($cheshire_token); ?>" class="regular-text" placeholder="Your API Token" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="cheshire_plugin_global_chat"><?php _e('Enable Global Chat', 'cheshire-cat-wp'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="cheshire_plugin_global_chat" name="cheshire_plugin_global_chat" <?php checked($cheshire_global_chat, 'on'); ?> />
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <?php submit_button(__('Save Changes', 'cheshire-cat-wp')); ?>
        </form>

        <div class="cheshire-cat-debug-section">
            <h2><?php _e('Cheshire Cat Debug', 'cheshire-cat-wp'); ?></h2>
            <?php
            // Check the connection status
            $cheshire = new \CheshireCatWp\inc\classes\CustomCheshireCat($cheshire_url, $cheshire_token);
            $status = $cheshire->getStatus();
            if ($status) {
                echo '<p>' . __('Connection Status:', 'cheshire-cat-wp') . ' <span style="color: green; font-weight: bold;">OK</span></p>';
            } else {
                echo '<p>' . __('Connection Status:', 'cheshire-cat-wp') . ' <span style="color: red; font-weight: bold;">' . __('Error', 'cheshire-cat-wp') . '</span></p>';
            }

            // Get the available plugins
            $plugins = $cheshire->getAvailablePlugins();
            if ($plugins && isset($plugins["installed"]) && is_array($plugins["installed"])) {
                echo '<h3>' . __('Cheshire Available Plugins:', 'cheshire-cat-wp') . '</h3>';
                echo '<ul style="list-style: disc; margin-left: 20px;">';
                foreach ($plugins["installed"] as $plugin) {
                    echo '<li style="margin-bottom: 10px;">' . esc_html($plugin['name']);
                    if ($plugin['active'] == 1) {
                        echo ' <span style="color: green; font-weight: bold;">(' . __('enabled', 'cheshire-cat-wp') . ')</span>';
                    }
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>' . __('No plugins found.', 'cheshire-cat-wp') . '</p>';
            }
            ?>
        </div>
    </div>
    <style>
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

        .cheshire-cat-debug-section {
            margin-top: 40px;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            background-color: #fff;
        }

        .cheshire-cat-debug-section h2,
        .cheshire-cat-debug-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
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

            .cheshire-cat-settings-table .regular-text {
                max-width: 100%;
            }
        }
    </style>
    <?php
}