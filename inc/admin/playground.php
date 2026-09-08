<?php

namespace webgrafia\cheshirecat\inc\admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Playground page callback.
 * 
 * This page provides a full-page chat interface for administrators to test the Cheshire Cat chatbot.
 */
function cheshirecat_playground_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cheshire-cat-chatbot'));
    }

    // Avatar is always enabled
    $avatar_class = 'with-avatar';
    $avatar_image = get_option('cheshire_chat_avatar_image', '');
    $default_avatar = CHESHIRE_CAT_PLUGIN_URL . 'assets/img/default-avatar.svg';

    // Scripts and styles are now enqueued via the admin_enqueue_scripts hook in the main plugin file
    ?>
    <div class="wrap cheshire-admin">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div class="cheshire-section">
            <div class="playground-header">
                <p><?php esc_html_e('Welcome to the Cheshire Cat Chatbot Playground! This is a full-page chat interface where you can test the chatbot as an administrator.', 'cheshire-cat-chatbot'); ?></p>
                <p><?php esc_html_e('Use this playground to test your chatbot configuration and responses before making it available to your users.', 'cheshire-cat-chatbot'); ?></p>
            </div>
        </div><!-- End of intro section -->

        <div class="cheshire-section">
            <!-- Tabs -->
            <div class="nav-tab-wrapper">
                <a href="#chat-tab" class="nav-tab nav-tab-active"><?php esc_html_e('Chat', 'cheshire-cat-chatbot'); ?></a>
                <a href="#prompt-tester-tab" class="nav-tab"><?php esc_html_e('Prompt Tester', 'cheshire-cat-chatbot'); ?></a>
            </div>

            <!-- Chat Tab -->
            <div id="chat-tab" class="tab-content active">
                <h2><?php _e('Chat Interface', 'cheshire-cat-chatbot'); ?></h2>
            <div id="cheshire-chat-container" class="<?php echo esc_attr($avatar_class . ' playground'); ?>">
                <div id="cheshire-chat-messages">
                    <?php \webgrafia\cheshirecat\cheshirecat_display_welcome_message(); ?>
                </div>
                <div id="cheshire-chat-input-container">
                    <input type="text" id="cheshire-chat-input" placeholder="<?php echo esc_attr(get_option('cheshire_plugin_input_placeholder', __('Type your message...', 'cheshire-cat-chatbot'))); ?>">
                    <button id="cheshire-chat-send"></button>
                </div>
                <div id="cheshire-chat-avatar">
                    <img src="<?php echo esc_url(!empty($avatar_image) ? $avatar_image : $default_avatar); ?>" alt="Chat Avatar">
                </div>
            </div>
        </div>

            <!-- Prompt Tester Tab -->
            <div id="prompt-tester-tab" class="tab-content">
                <h2><?php _e('Prompt Tester', 'cheshire-cat-chatbot'); ?></h2>
                <p class="description"><?php esc_html_e('Test specific prompts and see the raw responses from the Cheshire Cat AI.', 'cheshire-cat-chatbot'); ?></p>
                <div class="prompt-tester-container">
                    <div class="prompt-input-container">
                        <label for="prompt-input"><?php esc_html_e('Enter your prompt:', 'cheshire-cat-chatbot'); ?></label>
                        <textarea id="prompt-input" rows="10" placeholder="<?php esc_html_e('Type your prompt here...', 'cheshire-cat-chatbot'); ?>"></textarea>
                        <button id="prompt-send" class="button button-primary"><?php esc_html_e('Send', 'cheshire-cat-chatbot'); ?></button>
                    </div>
                    <div class="prompt-response-container">
                        <div class="response-header">
                            <label><?php esc_html_e('Response:', 'cheshire-cat-chatbot'); ?></label>
                            <button id="copy-response" class="button" title="<?php esc_attr_e('Copy to clipboard', 'cheshire-cat-chatbot'); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                        <textarea id="prompt-response" rows="10" readonly></textarea>
                    </div>
                </div>
            </div>
        </div><!-- End of tabs section -->
    </div>


    <script>
        jQuery(document).ready(function($) {
            // Tab functionality
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();

                // Remove active class from all tabs and content
                $('.nav-tab').removeClass('nav-tab-active');
                $('.tab-content').removeClass('active');

                // Add active class to clicked tab
                $(this).addClass('nav-tab-active');

                // Show corresponding content
                $($(this).attr('href')).addClass('active');
            });

            // Prompt tester functionality
            $('#prompt-send').on('click', function() {
                var prompt = $('#prompt-input').val().trim();

                if (prompt === '') {
                    return;
                }

                // Disable button and show loading state
                var $button = $(this);
                var originalText = $button.text();
                $button.prop('disabled', true).addClass('loading');

                // Clear previous response
                $('#prompt-response').val('');

                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cheshire_plugin_ajax',
                        nonce: cheshire_ajax_object.nonce,
                        message: prompt,
                        from_editor: true
                    },
                    success: function(response) {
                        // Re-enable button and remove loading state
                        $button.prop('disabled', false).removeClass('loading').text(originalText);

                        if (response.success && response.data) {
                            // Display the response
                            var content = '';

                            var data = response.data;
                            if (typeof data === 'object') {
                                // Handle AgentOutput format as shown in the issue description
                                if (data.output) {
                                    content = data.output;
                                }
                                // Try to find content in other nested structures
                                else if (data.content) {
                                    content = data.content;
                                }
                                // Handle other possible response formats
                                else if (data.text) {
                                    content = data.text;
                                }
                                else if (data.message) {
                                    content = data.message;
                                }
                                else if (data.response) {
                                    content = data.response;
                                }
                                else {
                                    // If we can't find a specific content field, convert the object to a string
                                    try {
                                        content = JSON.stringify(data);
                                    } catch (e) {
                                        content = 'Unable to parse response';
                                    }
                                }
                            } else {
                                content = data;
                            }

                            $('#prompt-response').val(content);
                        } else {
                            // Show error message
                            $('#prompt-response').val('<?php esc_html_e('Error: ', 'cheshire-cat-chatbot'); ?>' + (response.data || '<?php esc_html_e('Unknown error occurred.', 'cheshire-cat-chatbot'); ?>'));
                        }
                    },
                    error: function(xhr, status, error) {
                        // Re-enable button and remove loading state
                        $button.prop('disabled', false).removeClass('loading').text(originalText);

                        // Show error message
                        $('#prompt-response').val('<?php esc_html_e('Error: ', 'cheshire-cat-chatbot'); ?>' + error);
                    }
                });
            });

            // Copy response functionality
            $('#copy-response').on('click', function() {
                var responseText = $('#prompt-response').val();

                if (responseText.trim() === '') {
                    return;
                }

                // Create a temporary textarea element to copy from
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(responseText).select();

                // Execute copy command
                document.execCommand('copy');

                // Remove temporary element
                $temp.remove();

                // Show feedback
                var $button = $(this);
                var $icon = $button.find('.dashicons');
                $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes');

                setTimeout(function() {
                    $icon.removeClass('dashicons-yes').addClass('dashicons-clipboard');
                }, 2000);
            });
        });
    </script>
    <?php
}
