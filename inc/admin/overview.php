<?php

namespace webgrafia\cheshirecat\inc\admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Overview & Usage page callback.
 */
function cheshirecat_overview_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cheshire-cat-chatbot'));
    }

        // Handle form submission
    if (isset($_POST['cheshire_overview_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cheshire_overview_nonce'])), 'cheshire_overview_save_settings')) {
        if (isset($_POST['cheshire_plugin_url'])) {
            $cheshire_plugin_url = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_url']));
            update_option('cheshire_plugin_url', esc_url_raw($cheshire_plugin_url));
        }
        if (isset($_POST['cheshire_plugin_url_v2'])) {
            $cheshire_plugin_url_v2 = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_url_v2']));
            update_option('cheshire_plugin_url_v2', esc_url_raw($cheshire_plugin_url_v2));
        }
        if (isset($_POST['cheshire_plugin_token'])) {
            $cheshire_plugin_token = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_token']));
            update_option('cheshire_plugin_token', $cheshire_plugin_token);
        }

        // Cheshire Cat Version
        if (isset($_POST['cheshire_plugin_cat_version'])) {
            $cheshire_plugin_cat_version = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_cat_version']));
            update_option('cheshire_plugin_cat_version', $cheshire_plugin_cat_version);
        }

        // API Key for v2
        if (isset($_POST['cheshire_plugin_api_key'])) {
            $cheshire_plugin_api_key = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_api_key']));
            update_option('cheshire_plugin_api_key', $cheshire_plugin_api_key);
        }

        // WebSocket communication
        if (isset($_POST['cheshire_plugin_enable_websocket'])) {
            $cheshire_plugin_enable_websocket = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_enable_websocket']));
            update_option('cheshire_plugin_enable_websocket', $cheshire_plugin_enable_websocket);
        } else {
            update_option('cheshire_plugin_enable_websocket', 'off');
        }

        // WebSocket URL
        if (isset($_POST['cheshire_plugin_websocket_url'])) {
            $cheshire_plugin_websocket_url = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_websocket_url']));
            update_option('cheshire_plugin_websocket_url', $cheshire_plugin_websocket_url);
        }

        add_settings_error(
            'cheshire_cat_overview_options',
            'cheshire_cat_settings_updated',
            __('Settings saved successfully.', 'cheshire-cat-chatbot'),
            'success'
        );
        }

    $cheshire_plugin_url = get_option('cheshire_plugin_url');
    $cheshire_plugin_url_v2 = get_option('cheshire_plugin_url_v2', '');
    $cheshire_plugin_token = get_option('cheshire_plugin_token');
    $cheshire_plugin_cat_version = get_option('cheshire_plugin_cat_version', 'v1');
    $cheshire_plugin_api_key = get_option('cheshire_plugin_api_key', '');
    $cheshire_plugin_enable_websocket = get_option('cheshire_plugin_enable_websocket', 'off');
    $cheshire_plugin_websocket_url = get_option('cheshire_plugin_websocket_url', '');

    // Visibilità iniziale dei campi (evita flash al caricamento della pagina)
    $is_v2          = ( $cheshire_plugin_cat_version === 'v2' );
    $is_ws_on       = ( $cheshire_plugin_enable_websocket === 'on' );
    $hide_api_key   = ! $is_v2;                      // visibile solo in v2
    $hide_ws_row    = $is_v2;                        // nascosta in v2
    $hide_token     = $is_v2 || ! $is_ws_on;        // nascosta in v2 o se WS è OFF
    $hide_ws_notice = ! $is_ws_on;                   // nascosta se WS è OFF
    $hide_ws_url    = ! $is_ws_on;                   // nascosta se WS è OFF

    ?>
    <div class="wrap cheshire-admin">
        <h1><?php if (function_exists('get_admin_page_title')) {
                echo esc_html(get_admin_page_title());
            } ?></h1>

        <p><?php _e('Welcome to the Cheshire Cat Chatbot plugin! This plugin allows you to integrate the powerful Cheshire Cat AI chatbot into your WordPress website.', 'cheshire-cat-chatbot'); ?></p>

        <div class="cheshire-section">
            <h2><?php _e('Connection Settings', 'cheshire-cat-chatbot'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('cheshire_overview_save_settings', 'cheshire_overview_nonce'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Cheshire Cat Version', 'cheshire-cat-chatbot'); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e('Cheshire Cat Version', 'cheshire-cat-chatbot'); ?></span></legend>
                                <label for="cheshire_plugin_cat_version_v1">
                                    <input type="radio" name="cheshire_plugin_cat_version" id="cheshire_plugin_cat_version_v1" value="v1" <?php checked($cheshire_plugin_cat_version, 'v1'); ?> />
                                    <?php esc_html_e('v1 (Legacy)', 'cheshire-cat-chatbot'); ?>
                                </label><br />
                                <label for="cheshire_plugin_cat_version_v2">
                                    <input type="radio" name="cheshire_plugin_cat_version" id="cheshire_plugin_cat_version_v2" value="v2" <?php checked($cheshire_plugin_cat_version, 'v2'); ?> />
                                    <?php esc_html_e('v2 (Current)', 'cheshire-cat-chatbot'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Select the version of your Cheshire Cat instance. v1 uses Bearer token authentication. v2 uses API Key authentication.', 'cheshire-cat-chatbot'); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top" id="cheshire_plugin_url_row_v1" <?php echo $is_v2 ? 'hidden' : ''; ?>>
                        <th scope="row"><?php esc_html_e('Cheshire Cat URL (v1)', 'cheshire-cat-chatbot'); ?></th>
                        <td>
                            <input type="text" name="cheshire_plugin_url" value="<?php echo esc_attr($cheshire_plugin_url); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('The URL where your Cheshire Cat AI v1 instance is running (e.g. http://localhost:1865).', 'cheshire-cat-chatbot'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top" id="cheshire_plugin_url_row_v2" <?php echo ! $is_v2 ? 'hidden' : ''; ?>>
                        <th scope="row"><?php esc_html_e('Cheshire Cat URL (v2)', 'cheshire-cat-chatbot'); ?></th>
                        <td>
                            <input type="text" name="cheshire_plugin_url_v2" value="<?php echo esc_attr($cheshire_plugin_url_v2); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('The URL where your Cheshire Cat AI v2 instance is running (e.g. http://localhost:1865). This is used for v2 API calls.', 'cheshire-cat-chatbot'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top" id="api_key_field" <?php echo $hide_api_key ? 'hidden' : ''; ?>>
                        <th scope="row"><?php esc_html_e('API Key (v2)', 'cheshire-cat-chatbot'); ?></th>
                        <td>
                            <input type="text" name="cheshire_plugin_api_key" value="<?php echo esc_attr($cheshire_plugin_api_key); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('API Key for Cheshire Cat v2 authentication. Used as x-api-key header in server-side REST calls. When WebSocket mode is enabled it is also passed to the browser as a query parameter — in that case anyone who can inspect the page source will be able to read it.', 'cheshire-cat-chatbot'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top" id="websocket_row" <?php echo $hide_ws_row ? 'hidden' : ''; ?>>
                        <th scope="row"><?php esc_html_e('WebSocket Communication', 'cheshire-cat-chatbot'); ?></th>
                        <td>
                            <input type="checkbox" id="cheshire_plugin_enable_websocket" name="cheshire_plugin_enable_websocket" <?php checked($cheshire_plugin_enable_websocket, 'on'); ?> />
                            <label for="cheshire_plugin_enable_websocket"><?php esc_html_e('Enable WebSocket Communication', 'cheshire-cat-chatbot'); ?></label>
                            <p class="description"><?php esc_html_e('Check this box to use WebSocket instead of HTTP for communication with the Cheshire Cat. This option does not apply to requests from the editor or prompt tester.', 'cheshire-cat-chatbot'); ?></p>

                            <div id="websocket_security_notice" style="margin-top:8px;padding:10px 14px;background:#fff3cd;border-left:4px solid #e6a817;border-radius:3px;" <?php echo $hide_ws_notice ? 'hidden' : ''; ?>>
                                <strong>⚠️ <?php esc_html_e('Security notice:', 'cheshire-cat-chatbot'); ?></strong>
                                <?php esc_html_e('When WebSocket mode is active, the authentication credentials (Token / API Key) are injected into the page source so the browser can connect directly to Cheshire Cat. Anyone who can inspect the HTML will be able to read them. Make sure the credentials you use have limited, non-administrative permissions.', 'cheshire-cat-chatbot'); ?>
                            </div>

                            <div id="websocket_url_field" style="margin-top:10px;" <?php echo $hide_ws_url ? 'hidden' : ''; ?>>
                                <label for="cheshire_plugin_websocket_url"><?php esc_html_e('WebSocket URL', 'cheshire-cat-chatbot'); ?></label>
                                <input type="text" id="cheshire_plugin_websocket_url" name="cheshire_plugin_websocket_url" value="<?php echo esc_attr($cheshire_plugin_websocket_url); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('Optional: Enter a custom WebSocket URL. If left empty, the plugin will automatically convert the Cheshire Cat URL from HTTP to WebSocket.', 'cheshire-cat-chatbot'); ?></p>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top" id="token_field" <?php echo $hide_token ? 'hidden' : ''; ?>>
                        <th scope="row"><?php esc_html_e('Authentication Token (v1)', 'cheshire-cat-chatbot'); ?></th>
                        <td>
                            <input type="text" name="cheshire_plugin_token" value="<?php echo esc_attr($cheshire_plugin_token); ?>"
                                class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Bearer token for Cheshire Cat v1 authentication. Required only when WebSocket mode is enabled, because the browser connects directly to Cheshire Cat and must authenticate itself. In REST/AJAX mode the PHP backend handles authentication server-side and the token is never exposed to the browser.', 'cheshire-cat-chatbot'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <script>
                jQuery(document).ready(function($) {
                    /**
                     * Recomputes which fields are visible.
                     *
                     * Rules:
                     *  - v1 + WS OFF  → nascondi api_key, nascondi token
                     *  - v1 + WS ON   → nascondi api_key, mostra token
                     *  - v2           → nascondi websocket_row, nascondi token, nascondi api_key
                     */
                    function toggleAuthFields() {
                        var version = $('input[name="cheshire_plugin_cat_version"]:checked').val();
                        var wsOn    = $('#cheshire_plugin_enable_websocket').is(':checked');

                        if (version === 'v2') {
                            // v2: mostra URL v2, nasconde URL v1, WebSocket, Token; mostra API Key
                            $('#cheshire_plugin_url_row_v1').hide().attr('hidden', 'hidden');
                            $('#cheshire_plugin_url_row_v2').show().removeAttr('hidden');
                            $('#websocket_row').hide().attr('hidden', 'hidden');
                            $('#token_field').hide().attr('hidden', 'hidden');
                            $('#api_key_field').show().removeAttr('hidden');
                        } else {
                            // v1: mostra URL v1, nasconde URL v2 e API Key; gestisce WebSocket
                            $('#cheshire_plugin_url_row_v1').show().removeAttr('hidden');
                            $('#cheshire_plugin_url_row_v2').hide().attr('hidden', 'hidden');
                            $('#websocket_row').show().removeAttr('hidden');
                            $('#api_key_field').hide().attr('hidden', 'hidden');

                            // Token: visibile solo se WebSocket è attivo
                            if (wsOn) {
                                $('#token_field').show().removeAttr('hidden');
                            } else {
                                $('#token_field').hide().attr('hidden', 'hidden');
                            }

                            // Security notice + WS URL: visibili solo se WS è attivo
                            if (wsOn) {
                                $('#websocket_security_notice').removeAttr('hidden');
                                $('#websocket_url_field').removeAttr('hidden');
                            } else {
                                $('#websocket_security_notice').attr('hidden', 'hidden');
                                $('#websocket_url_field').attr('hidden', 'hidden');
                            }
                        }
                    }

                    // Stato iniziale
                    toggleAuthFields();

                    // Reagisce ai cambiamenti
                    $('input[name="cheshire_plugin_cat_version"]').on('change', toggleAuthFields);
                    $('#cheshire_plugin_enable_websocket').on('change', toggleAuthFields);
                });
            </script>
        </div>

        <div class="cheshire-section">
            <h2><?php _e('Before You Begin', 'cheshire-cat-chatbot'); ?></h2>
            <p>
                <?php _e('To use this plugin, you must have a working installation of', 'cheshire-cat-chatbot'); ?> <a href="https://cheshirecat.ai/" target="_blank">Cheshire Cat AI</a>. <?php esc_html_e('This plugin acts as a bridge between your WordPress site and your Cheshire Cat AI instance.', 'cheshire-cat-chatbot'); ?>
            </p>
            <p>
                <?php esc_html_e('Make sure you have entered the correct URL and authentication credentials in the settings above.', 'cheshire-cat-chatbot'); ?>
            </p>
        </div>

        <div class="cheshire-section">
            <h2><?php _e('Usage', 'cheshire-cat-chatbot'); ?></h2>

            <div class="settings-section">
                <h3 class="settings-section-title"><?php _e('Displaying the Chat with the Shortcode', 'cheshire-cat-chatbot'); ?></h3>
                <p>
                    <?php _e('To display the chat on a specific page or post, use the following shortcode:', 'cheshire-cat-chatbot'); ?>
                    <code>[cheshire_chat]</code>
                </p>
                <p>
                    <?php _e('Simply paste this shortcode into the content area of any page or post where you want the chat to appear.', 'cheshire-cat-chatbot'); ?>
                </p>
            </div>

            <div class="settings-section">
                <h3 class="settings-section-title"><?php _e('Enabling Global Chat', 'cheshire-cat-chatbot'); ?></h3>
                <p>
                    <?php _e('If you want the chat to appear on every page of your website, you can enable the "Global Chat" option in the', 'cheshire-cat-chatbot'); ?> <a href="admin.php?page=cheshire-cat-configuration"><?php esc_html_e('Configuration', 'cheshire-cat-chatbot'); ?></a> <?php esc_html_e('section.', 'cheshire-cat-chatbot'); ?>
                </p>
                <p>
                    <?php _e('When the Global Chat is enabled, the chat will be automatically added to all pages, and you', 'cheshire-cat-chatbot'); ?> <strong><?php esc_html_e('do not', 'cheshire-cat-chatbot'); ?></strong> <?php esc_html_e('need to use the shortcode.', 'cheshire-cat-chatbot'); ?>
                </p>
            </div>
        </div>
    </div>
    <?php
}
