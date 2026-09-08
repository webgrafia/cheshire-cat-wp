<?php
namespace webgrafia\cheshirecat\inc\admin;
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Meowww page callback.
 *
 * This page displays information about the Cheshire Cat AI installation, including
 * a list of installed plugins and their details.
 */
function cheshirecat_meowww_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cheshire-cat-chatbot'));
    }
    // Check if the Cheshire Cat URL is configured
    $cheshire_plugin_cat_version = get_option('cheshire_plugin_cat_version', 'v1');
    $cheshire_plugin_url = get_option('cheshire_plugin_url', '');
    $cheshire_plugin_url_v2 = get_option('cheshire_plugin_url_v2', '');
    $cheshire_token = get_option('cheshire_plugin_token', '');
    $cheshire_api_key = get_option('cheshire_plugin_api_key', '');

    $cheshire_url = ($cheshire_plugin_cat_version === 'v2') ? $cheshire_plugin_url_v2 : $cheshire_plugin_url;

    if (empty($cheshire_url)) {
        $error_message = __('Cheshire Cat URL is not configured. Please go to the Configuration page and set the Cheshire Cat URL.', 'cheshire-cat-chatbot');
        $active_plugins = [];
        $llm_settings = [];
    } else {
        try {
            // Get the Cheshire Cat client
            $client = new \webgrafia\cheshirecat\inc\classes\Custom_Cheshire_Cat_Client(
                $cheshire_url,
                $cheshire_token,
                $cheshire_plugin_cat_version,
                $cheshire_api_key
            );

            // Get the list of installed plugins
            try {
                $response = $client->getAvailablePlugins();
                $body = $response->getBody()->getContents();
                $plugins_data = json_decode($body);
                // Get the installed plugins
                $plugins = isset($plugins_data->installed) ? $plugins_data->installed : [];
                // Filter to show only active plugins
                $active_plugins = array_filter($plugins, function ($plugin) {
                    return isset($plugin->active) && $plugin->active === true;
                });

                // Initialize variables to avoid PHP notices
                $llm_settings = [];
                $llm_raw_response = null;
                $llm_raw_body = null;
                $llm_error_message = null;

                // Get the LLM settings
                try {
                    $llm_response = $client->getLlmsSettings();
                    $llm_body = $llm_response->getBody()->getContents();

                    // Store the raw response for debugging
                    $llm_raw_response = $llm_response;
                    $llm_raw_body = $llm_body;

                    $llm_settings = json_decode($llm_body);
                } catch (\Exception $e) {
                    $llm_error_message = $e->getMessage();
                }
            } catch (\Exception $e) {
                $active_plugins = [];
                $llm_settings = [];
                $llm_raw_response = null;
                $llm_raw_body = null;
                $error_message = $e->getMessage();
            }
        } catch (\Exception $e) {
            $active_plugins = [];
            $llm_settings = [];
            $llm_raw_response = null;
            $llm_raw_body = null;
            $error_message = __('Could not connect to Cheshire Cat AI. Please check that the URL is correct and the service is running.', 'cheshire-cat-chatbot');
        }
    }
    ?>
    <div class="wrap cheshire-admin">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="cheshire-section">
            <p><?php esc_html_e('Welcome to the Cheshire Cat Chatbot Meowww page! This page displays information about your Cheshire Cat AI installation.', 'cheshire-cat-chatbot'); ?></p>
        </div>
        <div class="cheshire-section">
            <h2><?php esc_html_e('Active Plugins', 'cheshire-cat-chatbot'); ?></h2>
            <?php if (isset($error_message)): ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html($error_message); ?></p>
                </div>
            <?php elseif (empty($active_plugins)): ?>
                <p><?php esc_html_e('No active plugins found.', 'cheshire-cat-chatbot'); ?></p>
            <?php else: ?>
                <div class="cc-plugins-list">
                    <?php foreach ($active_plugins as $plugin): 
                        // Get plugin details
                        try {
                            $settings_response = $client->getPluginSettings($plugin->id);
                            $settings_body = $settings_response->getBody()->getContents();
                            $plugin_settings = json_decode($settings_body);
                        } catch (\Exception $e) {
                            $plugin_settings = null;
                        }
                        ?>
                        <div class="cc-plugin-card">
                            <div class="cc-plugin-header">
                                <h3><?php echo esc_html($plugin->name); ?></h3>
                            </div>
                            <div class="cc-plugin-details">
                                <?php if (!empty($plugin->description)): ?>
                                    <p class="cc-plugin-description"><?php echo esc_html($plugin->description); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($plugin->author_name)): ?>
                                    <p class="cc-plugin-author">
                                        <strong><?php esc_html_e('Author:', 'cheshire-cat-chatbot'); ?></strong>
                                        <?php echo esc_html($plugin->author_name); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($plugin->version)): ?>
                                    <p class="cc-plugin-version">
                                        <strong><?php esc_html_e('Version:', 'cheshire-cat-chatbot'); ?></strong>
                                        <?php echo esc_html($plugin->version); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($plugin_settings)): ?>
                                    <div class="cc-plugin-settings">
                                        <h4><?php esc_html_e('Settings', 'cheshire-cat-chatbot'); ?></h4>
                                        <?php if (isset($plugin_settings->value) && !empty((array)$plugin_settings->value)): ?>
                                            <div class="cc-settings-list">
                                                <?php foreach ($plugin_settings->value as $key => $value): ?>
                                                    <div class="cc-setting-item">
                                                        <span class="cc-setting-key"><?php echo esc_html($key); ?>:</span>
                                                        <span class="cc-setting-value">
                                                            <?php 
                                                            if (is_object($value) || is_array($value)) {
                                                                echo '<pre>' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
                                                            } else {
                                                                echo esc_html(is_bool($value) ? ($value ? 'true' : 'false') : $value);
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="cc-no-settings"><?php esc_html_e('No settings available for this plugin.', 'cheshire-cat-chatbot'); ?></p>
                                        <?php endif; ?>

                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            </div><!-- End of Active Plugins section -->

            <div class="cheshire-section">
                <h2 class="cc-section-title">
                    <?php esc_html_e('LLM Settings', 'cheshire-cat-chatbot'); ?>
                    <button id="toggle-debug" class="button button-small"><?php esc_html_e('Toggle Debug Info', 'cheshire-cat-chatbot'); ?></button>
                </h2>

            <!-- Debug output for LLM settings -->
            <div id="debug-output" class="debug-output" style="display: none;">
                <div class="debug-notice">
                    <p><strong><?php esc_html_e('Note:', 'cheshire-cat-chatbot'); ?></strong> <?php esc_html_e('This debug information is for development purposes only. It shows the raw data structure of the LLM settings to help understand how to properly handle the data.', 'cheshire-cat-chatbot'); ?></p>
                    <button id="copy-debug" class="button button-small"><?php esc_html_e('Copy Debug Info', 'cheshire-cat-chatbot'); ?></button>
                </div>

                <h3>Debug: LLM Settings Raw Data</h3>
                <pre><?php 
                    // If it's an object or array, pretty print it as JSON
                    if (is_object($llm_settings) || is_array($llm_settings)) {
                        echo esc_html(json_encode($llm_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    } else {
                        print_r($llm_settings);
                    }
                ?></pre>

                <?php if (isset($llm_raw_response)): ?>
                <h3>Debug: LLM Raw Response Object</h3>
                <pre><?php 
                    // Extract useful information from the response object
                    $response_info = [
                        'statusCode' => method_exists($llm_raw_response, 'getStatusCode') ? $llm_raw_response->getStatusCode() : 'N/A',
                        'reasonPhrase' => method_exists($llm_raw_response, 'getReasonPhrase') ? $llm_raw_response->getReasonPhrase() : 'N/A',
                        'protocol' => method_exists($llm_raw_response, 'getProtocolVersion') ? $llm_raw_response->getProtocolVersion() : 'N/A',
                        'headers' => method_exists($llm_raw_response, 'getHeaders') ? $llm_raw_response->getHeaders() : 'N/A',
                    ];

                    echo esc_html(json_encode($response_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                ?></pre>
                <?php endif; ?>

                <?php if (isset($llm_raw_body)): ?>
                <h3>Debug: LLM Raw Response Body</h3>
                <pre><?php 
                    // Try to parse the body as JSON to see if it's valid JSON
                    $json_test = json_decode($llm_raw_body);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // It's valid JSON, so pretty print it
                        echo "Response Body (JSON):\n";
                        echo esc_html(json_encode($json_test, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    } else {
                        // It's not valid JSON, so just display it as is
                        echo "Response Body: " . esc_html($llm_raw_body);
                    }
                ?></pre>

                <h3>Debug: Decoded Data</h3>
                <pre><?php 
                    // Display the data that was actually used in the application
                    echo "Decoded Data (used in application):\n";
                    echo esc_html(json_encode($llm_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                ?></pre>
                <?php endif; ?>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    // Toggle debug output
                    $('#toggle-debug').on('click', function() {
                        $('#debug-output').toggle();
                    });

                    // Copy debug info to clipboard
                    $('#copy-debug').on('click', function() {
                        // Create a temporary textarea to hold the debug info
                        var $temp = $('<textarea>');
                        $('body').append($temp);

                        // Get all the debug info
                        var debugText = '';
                        $('#debug-output h3').each(function() {
                            debugText += $(this).text() + "\n\n";
                            debugText += $(this).next('pre').text() + "\n\n";
                        });

                        // Set the textarea value and select it
                        $temp.val(debugText).select();

                        // Copy the text
                        document.execCommand('copy');

                        // Remove the temporary textarea
                        $temp.remove();

                        // Show feedback
                        var $button = $(this);
                        var originalText = $button.text();
                        $button.text('<?php esc_html_e('Copied!', 'cheshire-cat-chatbot'); ?>');

                        // Reset button text after 2 seconds
                        setTimeout(function() {
                            $button.text(originalText);
                        }, 2000);
                    });
                });
            </script>

            <?php if (isset($error_message)): ?>
                <!-- Error message already displayed above -->
            <?php elseif (isset($llm_error_message)): ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html($llm_error_message); ?></p>
                </div>
            <?php elseif (empty($llm_settings)): ?>
                <p><?php esc_html_e('No LLM settings found.', 'cheshire-cat-chatbot'); ?></p>
            <?php else: ?>
                <?php if (isset($llm_settings->selected_configuration)): ?>
                    <div class="cc-selected-llm">
                        <p><strong><?php esc_html_e('Selected LLM:', 'cheshire-cat-chatbot'); ?></strong> <?php echo esc_html($llm_settings->selected_configuration); ?></p>
                    </div>
                <?php endif; ?>

                <div class="cc-llm-list">
                    <?php if (isset($llm_settings->settings) && is_array($llm_settings->settings)): ?>
                        <?php foreach ($llm_settings->settings as $llm_config): ?>
                            <?php 
                            $is_selected = isset($llm_settings->selected_configuration) && $llm_settings->selected_configuration === $llm_config->name;
                            $card_class = $is_selected ? 'cc-llm-card cc-llm-card-selected' : 'cc-llm-card';
                            ?>
                            <div class="<?php echo esc_attr($card_class); ?>">
                                <div class="cc-llm-header">
                                    <h3>
                                        <?php echo esc_html($llm_config->schema->humanReadableName ?? $llm_config->name); ?>
                                        <?php if ($is_selected): ?>
                                            <span class="cc-selected-badge"><?php esc_html_e('Selected', 'cheshire-cat-chatbot'); ?></span>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                                <div class="cc-llm-details">
                                    <?php if (isset($llm_config->schema->description)): ?>
                                        <p class="cc-llm-description"><?php echo esc_html($llm_config->schema->description); ?></p>
                                    <?php endif; ?>

                                    <?php if (isset($llm_config->schema->link) && !empty($llm_config->schema->link)): ?>
                                        <p class="cc-llm-link">
                                            <a href="<?php echo esc_url($llm_config->schema->link); ?>" target="_blank" rel="noopener noreferrer">
                                                <?php esc_html_e('Learn more', 'cheshire-cat-chatbot'); ?> <span class="dashicons dashicons-external"></span>
                                            </a>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($llm_config->value)): ?>
                                        <div class="cc-llm-settings">
                                            <h4><?php esc_html_e('Current Settings', 'cheshire-cat-chatbot'); ?></h4>
                                            <div class="cc-settings-list">
                                                <?php foreach ($llm_config->value as $key => $value): ?>
                                                    <div class="cc-setting-item">
                                                        <span class="cc-setting-key"><?php echo esc_html($key); ?>:</span>
                                                        <span class="cc-setting-value">
                                                            <?php 
                                                            if (is_object($value) || is_array($value)) {
                                                                echo '<pre>' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
                                                            } elseif ($key === 'openai_api_key' || $key === 'api_key' || $key === 'google_api_key' || $key === 'cohere_api_key' || $key === 'huggingfacehub_api_token' || strpos($key, 'key') !== false) {
                                                                // Mask API keys for security
                                                                echo esc_html('********');
                                                            } else {
                                                                echo esc_html(is_bool($value) ? ($value ? 'true' : 'false') : $value);
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="cc-no-settings"><?php esc_html_e('No settings configured for this LLM.', 'cheshire-cat-chatbot'); ?></p>
                                    <?php endif; ?>

                                    <?php if (isset($llm_config->schema->properties) && !empty($llm_config->schema->properties)): ?>
                                        <div class="cc-llm-properties">
                                            <h4><?php esc_html_e('Available Properties', 'cheshire-cat-chatbot'); ?></h4>
                                            <div class="cc-settings-list">
                                                <?php foreach ($llm_config->schema->properties as $prop_name => $prop_data): ?>
                                                    <div class="cc-setting-item">
                                                        <span class="cc-setting-key"><?php echo esc_html($prop_data->title ?? $prop_name); ?>:</span>
                                                        <span class="cc-setting-value">
                                                            <?php 
                                                            if (isset($prop_data->default)) {
                                                                echo '<span class="cc-default-label">' . esc_html__('Default:', 'cheshire-cat-chatbot') . '</span> ';
                                                                if (is_object($prop_data->default) || is_array($prop_data->default)) {
                                                                    echo '<pre>' . esc_html(json_encode($prop_data->default, JSON_PRETTY_PRINT)) . '</pre>';
                                                                } else {
                                                                    echo esc_html(is_bool($prop_data->default) ? ($prop_data->default ? 'true' : 'false') : $prop_data->default);
                                                                }
                                                            }
                                                            if (isset($prop_data->type)) {
                                                                echo ' <span class="cc-prop-type">(' . esc_html($prop_data->type) . ')</span>';
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php esc_html_e('No LLM configurations found.', 'cheshire-cat-chatbot'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div><!-- End of LLM Settings section -->
    </div>

    <?php
}
