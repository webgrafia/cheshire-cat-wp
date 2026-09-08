<?php

namespace webgrafia\cheshirecat\inc\admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration page callback.
 */
function cheshirecat_configuration_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cheshire-cat-chatbot'));
    }

    // Handle form submission
    if (isset($_POST['cheshire_plugin_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cheshire_plugin_nonce'])), 'cheshire_plugin_save_settings')) {
        if (isset($_POST['cheshire_plugin_global_chat'])) {
            $cheshire_plugin_global_chat = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_global_chat']));
            update_option('cheshire_plugin_global_chat', $cheshire_plugin_global_chat);
        }
        if (isset($_POST['cheshire_plugin_enable_context'])) {
            $cheshire_plugin_enable_context = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_enable_context']));
            update_option('cheshire_plugin_enable_context', $cheshire_plugin_enable_context);
        } else {
            update_option('cheshire_plugin_enable_context', 'off');
        }

        // Declarative Memory option
        if (isset($_POST['cheshire_plugin_enable_declarative_memory'])) {
            $cheshire_plugin_enable_declarative_memory = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_enable_declarative_memory']));
            update_option('cheshire_plugin_enable_declarative_memory', $cheshire_plugin_enable_declarative_memory);
        } else {
            update_option('cheshire_plugin_enable_declarative_memory', 'off');
        }
        
        // Declarative Memory: enabled post types selection
        if (isset($_POST['cheshire_plugin_declarative_memory_post_types']) && is_array($_POST['cheshire_plugin_declarative_memory_post_types'])) {
            $dm_post_types = array_map('sanitize_text_field', wp_unslash($_POST['cheshire_plugin_declarative_memory_post_types']));
            update_option('cheshire_plugin_declarative_memory_post_types', $dm_post_types);
        } else {
            // Save empty array if none selected to indicate no restriction or no selection.
            update_option('cheshire_plugin_declarative_memory_post_types', array());
        }

        // Related links options
        if (isset($_POST['cheshire_plugin_enable_related_links'])) {
            $cheshire_plugin_enable_related_links = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_enable_related_links']));
            update_option('cheshire_plugin_enable_related_links', $cheshire_plugin_enable_related_links);
        } else {
            update_option('cheshire_plugin_enable_related_links', 'off');
        }

        if (isset($_POST['cheshire_plugin_minimum_link_score'])) {
            $cheshire_plugin_minimum_link_score = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_minimum_link_score']));
            // Ensure the value is between 0 and 1
            $cheshire_plugin_minimum_link_score = max(0, min(1, floatval($cheshire_plugin_minimum_link_score)));
            update_option('cheshire_plugin_minimum_link_score', $cheshire_plugin_minimum_link_score);
        }

        if (isset($_POST['cheshire_plugin_link_text'])) {
            $cheshire_plugin_link_text = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_link_text']));
            update_option('cheshire_plugin_link_text', $cheshire_plugin_link_text);
        }


        // Default chat state (open/closed)
        if (isset($_POST['cheshire_plugin_default_state'])) {
            $cheshire_plugin_default_state = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_default_state']));
            update_option('cheshire_plugin_default_state', $cheshire_plugin_default_state);
        }

        // Logged-in users only
        if (isset($_POST['cheshire_plugin_logged_in_only'])) {
            $cheshire_plugin_logged_in_only = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_logged_in_only']));
            update_option('cheshire_plugin_logged_in_only', $cheshire_plugin_logged_in_only);
        } else {
            update_option('cheshire_plugin_logged_in_only', 'off');
        }

        // Show preview to logged-out users
        if (isset($_POST['cheshire_plugin_show_preview_logged_out'])) {
            $cheshire_plugin_show_preview_logged_out = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_show_preview_logged_out']));
            update_option('cheshire_plugin_show_preview_logged_out', $cheshire_plugin_show_preview_logged_out);
        } else {
            update_option('cheshire_plugin_show_preview_logged_out', 'off');
        }

        if (isset($_POST['cheshire_plugin_preview_text_logged_out'])) {
            // allowing html
            $cheshire_plugin_preview_text_logged_out = wp_kses_post(wp_unslash($_POST['cheshire_plugin_preview_text_logged_out']));
            update_option('cheshire_plugin_preview_text_logged_out', $cheshire_plugin_preview_text_logged_out);
        }

        // Reinforcement message
        if (isset($_POST['cheshire_plugin_enable_reinforcement'])) {
            $cheshire_plugin_enable_reinforcement = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_enable_reinforcement']));
            update_option('cheshire_plugin_enable_reinforcement', $cheshire_plugin_enable_reinforcement);
        } else {
            update_option('cheshire_plugin_enable_reinforcement', 'off');
        }

        if (isset($_POST['cheshire_plugin_reinforcement_message'])) {
            $cheshire_plugin_reinforcement_message = sanitize_textarea_field(wp_unslash($_POST['cheshire_plugin_reinforcement_message']));
            update_option('cheshire_plugin_reinforcement_message', $cheshire_plugin_reinforcement_message);
        }

        // Predefined responses
        if (isset($_POST['cheshire_plugin_predefined_responses'])) {
            $cheshire_plugin_predefined_responses = sanitize_textarea_field(wp_unslash($_POST['cheshire_plugin_predefined_responses']));
            update_option('cheshire_plugin_predefined_responses', $cheshire_plugin_predefined_responses);
        }

        // Product category predefined responses
        if (isset($_POST['cheshire_plugin_product_category_predefined_responses'])) {
            $cheshire_plugin_product_category_predefined_responses = sanitize_textarea_field(wp_unslash($_POST['cheshire_plugin_product_category_predefined_responses']));
            update_option('cheshire_plugin_product_category_predefined_responses', $cheshire_plugin_product_category_predefined_responses);
        }

        // Maximum number of predefined questions to show
        if (isset($_POST['cheshire_plugin_max_predefined_questions'])) {
            $max_predefined_questions = absint($_POST['cheshire_plugin_max_predefined_questions']);
            update_option('cheshire_plugin_max_predefined_questions', $max_predefined_questions);
        }

        // Predefined responses title
        if (isset($_POST['cheshire_plugin_predefined_responses_title'])) {
            $cheshire_plugin_predefined_responses_title = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_predefined_responses_title']));
            update_option('cheshire_plugin_predefined_responses_title', $cheshire_plugin_predefined_responses_title);
        }

        // Show predefined responses at the end of content
        if (isset($_POST['cheshire_plugin_show_predefined_in_content'])) {
            $cheshire_plugin_show_predefined_in_content = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_show_predefined_in_content']));
            update_option('cheshire_plugin_show_predefined_in_content', $cheshire_plugin_show_predefined_in_content);
        } else {
            update_option('cheshire_plugin_show_predefined_in_content', 'off');
        }

        // Post types
        if (isset($_POST['cheshire_plugin_enabled_post_types']) && is_array($_POST['cheshire_plugin_enabled_post_types'])) {
            $post_types = array_map('sanitize_text_field', wp_unslash($_POST['cheshire_plugin_enabled_post_types']));
            update_option('cheshire_plugin_enabled_post_types', $post_types);
        } else {
            update_option('cheshire_plugin_enabled_post_types', array());
        }

        // Taxonomies
        if (isset($_POST['cheshire_plugin_enabled_taxonomies']) && is_array($_POST['cheshire_plugin_enabled_taxonomies'])) {
            $taxonomies = array_map('sanitize_text_field', wp_unslash($_POST['cheshire_plugin_enabled_taxonomies']));
            update_option('cheshire_plugin_enabled_taxonomies', $taxonomies);
        } else {
            update_option('cheshire_plugin_enabled_taxonomies', array());
        }

        // Content type application mode
        if (isset($_POST['cheshire_plugin_content_type_mode'])) {
            $content_type_mode = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_content_type_mode']));
            update_option('cheshire_plugin_content_type_mode', $content_type_mode);
        }

        // Show in homepage
        if (isset($_POST['cheshire_plugin_show_in_homepage'])) {
            $show_in_homepage = sanitize_text_field(wp_unslash($_POST['cheshire_plugin_show_in_homepage']));
            update_option('cheshire_plugin_show_in_homepage', $show_in_homepage);
        } else {
            update_option('cheshire_plugin_show_in_homepage', 'off');
        }
    }

    $cheshire_plugin_global_chat = get_option('cheshire_plugin_global_chat');
    $cheshire_plugin_enable_context = get_option('cheshire_plugin_enable_context', 'off');
    $cheshire_plugin_default_state = get_option('cheshire_plugin_default_state', 'open');
    $cheshire_plugin_logged_in_only = get_option('cheshire_plugin_logged_in_only', 'off');
    $cheshire_plugin_show_preview_logged_out = get_option('cheshire_plugin_show_preview_logged_out', 'off');
    $cheshire_plugin_preview_text_logged_out = get_option('cheshire_plugin_preview_text_logged_out', '');
    $cheshire_plugin_enable_reinforcement = get_option('cheshire_plugin_enable_reinforcement', 'off');
    $cheshire_plugin_reinforcement_message = get_option('cheshire_plugin_reinforcement_message', 'reply with short sentences');
    $cheshire_plugin_content_type_mode = get_option('cheshire_plugin_content_type_mode', 'site_wide');
    $cheshire_plugin_show_in_homepage = get_option('cheshire_plugin_show_in_homepage', 'off');
    $cheshire_plugin_predefined_responses = get_option('cheshire_plugin_predefined_responses', '');
    $cheshire_plugin_product_category_predefined_responses = get_option('cheshire_plugin_product_category_predefined_responses', '');
    $cheshire_plugin_max_predefined_questions = get_option('cheshire_plugin_max_predefined_questions', 0);
    $cheshire_plugin_show_predefined_in_content = get_option('cheshire_plugin_show_predefined_in_content', 'off');
    $cheshire_plugin_predefined_responses_title = get_option('cheshire_plugin_predefined_responses_title', __('Frequently Asked Questions', 'cheshire-cat-chatbot'));
    $cheshire_plugin_enable_declarative_memory = get_option('cheshire_plugin_enable_declarative_memory', 'off');
    ?>
    <div class="wrap cheshire-admin">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post">
            <?php wp_nonce_field('cheshire_plugin_save_settings', 'cheshire_plugin_nonce'); ?>
            <div class="cheshire-section">
                <h2><?php _e('Chat Settings', 'cheshire-cat-chatbot'); ?></h2>
                <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Default Chat State', 'cheshire-cat-chatbot'); ?></th>
                    <td>
                        <select name="cheshire_plugin_default_state">
                            <option value="open" <?php selected($cheshire_plugin_default_state, 'open'); ?>><?php esc_html_e('Open', 'cheshire-cat-chatbot'); ?></option>
                            <option value="closed" <?php selected($cheshire_plugin_default_state, 'closed'); ?>><?php esc_html_e('Closed', 'cheshire-cat-chatbot'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Choose whether the chat window should be open or closed by default when a user visits your site.', 'cheshire-cat-chatbot'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Logged-in Users Only', 'cheshire-cat-chatbot'); ?></th>
                    <td>
                        <input type="checkbox" id="cheshire_plugin_logged_in_only" name="cheshire_plugin_logged_in_only" <?php checked($cheshire_plugin_logged_in_only, 'on'); ?> />
                        <label for="cheshire_plugin_logged_in_only"><?php esc_html_e('Show chat only to logged-in users', 'cheshire-cat-chatbot'); ?></label>
                        <p class="description"><?php esc_html_e('Check this box to show the chat only to users who are logged in to your WordPress site.', 'cheshire-cat-chatbot'); ?></p>

                        <div id="logged_in_only_options" style="margin-top: 15px; <?php echo $cheshire_plugin_logged_in_only !== 'on' ? 'display:none;' : ''; ?>">
                            <div style="margin-bottom: 10px;">
                                <input type="checkbox" id="cheshire_plugin_show_preview_logged_out" name="cheshire_plugin_show_preview_logged_out" <?php checked($cheshire_plugin_show_preview_logged_out, 'on'); ?> />
                                <label for="cheshire_plugin_show_preview_logged_out"><?php esc_html_e('mostra anteprima ai non registrati', 'cheshire-cat-chatbot'); ?></label>
                            </div>
                            <div id="preview_text_option" style="<?php echo $cheshire_plugin_show_preview_logged_out !== 'on' ? 'display:none;' : ''; ?>">
                                <label for="cheshire_plugin_preview_text_logged_out"><?php esc_html_e('Testo anteprima (HTML consentito):', 'cheshire-cat-chatbot'); ?></label><br>
                                <textarea name="cheshire_plugin_preview_text_logged_out" id="cheshire_plugin_preview_text_logged_out" rows="4" style="width: 100%; max-width: 400px;"><?php echo esc_textarea($cheshire_plugin_preview_text_logged_out); ?></textarea>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Context Information', 'cheshire-cat-chatbot'); ?></th>
                    <td>
                        <input type="checkbox" name="cheshire_plugin_enable_context" <?php checked($cheshire_plugin_enable_context, 'on'); ?> />
                        <label for="cheshire_plugin_enable_context"><?php esc_html_e('Enable Context Information', 'cheshire-cat-chatbot'); ?></label>
                        <p class="description"><?php esc_html_e('Check this box to send page context information (title, description, etc.) to the Cheshire Cat with each message.', 'cheshire-cat-chatbot'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Declarative Memory', 'cheshire-cat-chatbot'); ?></th>
                    <td>
                        <input type="checkbox" id="cheshire_plugin_enable_declarative_memory" name="cheshire_plugin_enable_declarative_memory" <?php checked($cheshire_plugin_enable_declarative_memory, 'on'); ?> />
                        <label for="cheshire_plugin_enable_declarative_memory"><?php esc_html_e('Enable Declarative Memory Upload', 'cheshire-cat-chatbot'); ?></label>
                        <p class="description"><?php esc_html_e('Check this box to upload content to the declarative memory of the Cheshire Cat when posts are saved.', 'cheshire-cat-chatbot'); ?></p>

                        <div id="declarative_memory_options" <?php echo $cheshire_plugin_enable_declarative_memory !== 'on' ? 'hidden' : ''; ?>>
                            <div class="configuration-margin-top">
                                <input type="checkbox" id="cheshire_plugin_enable_related_links" name="cheshire_plugin_enable_related_links" <?php checked(get_option('cheshire_plugin_enable_related_links', 'off'), 'on'); ?> />
                                <label for="cheshire_plugin_enable_related_links"><?php esc_html_e('Enable chatbot to reply with related internal links', 'cheshire-cat-chatbot'); ?></label>
                                <p class="description"><?php esc_html_e('Check this box to allow the chatbot to include relevant internal links in its responses.', 'cheshire-cat-chatbot'); ?></p>
                            </div>

                            <div class="configuration-margin-top">
                                <label for="cheshire_plugin_minimum_link_score"><?php esc_html_e('Minimum score for linking:', 'cheshire-cat-chatbot'); ?></label>
                                <input type="number" step="0.01" min="0" max="1" id="cheshire_plugin_minimum_link_score" name="cheshire_plugin_minimum_link_score" value="<?php echo esc_attr(get_option('cheshire_plugin_minimum_link_score', '0.8')); ?>" />
                                <p class="description"><?php esc_html_e('Set the minimum relevance score (0-1) required for a link to be included in responses.', 'cheshire-cat-chatbot'); ?></p>
                            </div>

                            <div class="configuration-margin-top">
                                <label for="cheshire_plugin_link_text"><?php esc_html_e('Text before link:', 'cheshire-cat-chatbot'); ?></label>
                                <input type="text" id="cheshire_plugin_link_text" name="cheshire_plugin_link_text" value="<?php echo esc_attr(get_option('cheshire_plugin_link_text', 'Related link')); ?>" />
                                <p class="description"><?php esc_html_e('This text will be displayed before the related link in the chatbot response.', 'cheshire-cat-chatbot'); ?></p>
                            </div>

                            <div class="configuration-margin-top">
                                <label><?php esc_html_e('Abilita Memoria Dichiarativa per Post Types:', 'cheshire-cat-chatbot'); ?></label>
                                <div style="margin-top: 6px;">
                                    <?php
                                    $dm_post_types_all = get_post_types(array('public' => true), 'objects');
                                    $dm_enabled = get_option('cheshire_plugin_declarative_memory_post_types', array());
                                    foreach ($dm_post_types_all as $pt) {
                                        $checked = in_array($pt->name, (array)$dm_enabled, true) ? 'checked' : '';
                                        echo '<div style="margin-bottom: 6px;">';
                                        echo '<input type="checkbox" id="cheshire_plugin_declarative_memory_post_types_' . esc_attr($pt->name) . '" name="cheshire_plugin_declarative_memory_post_types[]" value="' . esc_attr($pt->name) . '" ' . $checked . ' /> ';
                                        echo '<label for="cheshire_plugin_declarative_memory_post_types_' . esc_attr($pt->name) . '">' . esc_html($pt->labels->singular_name ?: $pt->label) . ' (' . esc_html($pt->name) . ')</label>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <p class="description"><?php esc_html_e('Seleziona i post type per i quali inviare automaticamente i contenuti alla memoria dichiarativa quando vengono pubblicati/aggiornati.', 'cheshire-cat-chatbot'); ?></p>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Reinforcement Message', 'cheshire-cat-chatbot'); ?></th>
                    <td>
                        <input type="checkbox" name="cheshire_plugin_enable_reinforcement" <?php checked($cheshire_plugin_enable_reinforcement, 'on'); ?> />
                        <label for="cheshire_plugin_enable_reinforcement"><?php esc_html_e('Enable Reinforcement Message', 'cheshire-cat-chatbot'); ?></label>
                        <p class="description"><?php esc_html_e('Check this box to add a reinforcement message to each prompt sent to the Cheshire Cat.', 'cheshire-cat-chatbot'); ?></p>
                        <div class="configuration-margin-top">
                            <label for="cheshire_plugin_reinforcement_message"><?php esc_html_e('Reinforcement Message:', 'cheshire-cat-chatbot'); ?></label>
                            <textarea name="cheshire_plugin_reinforcement_message" rows="3"><?php echo esc_textarea($cheshire_plugin_reinforcement_message); ?></textarea>
                            <p class="description"><?php esc_html_e('This message will be added to each prompt with format: "#IMPORTANT [your message]". Example: "reply with short sentences"', 'cheshire-cat-chatbot'); ?></p>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Predefined Responses', 'cheshire-cat-chatbot'); ?></th>
                    <td>
                        <div>
                            <label for="cheshire_plugin_predefined_responses"><?php esc_html_e('Predefined Responses:', 'cheshire-cat-chatbot'); ?></label>
                            <textarea name="cheshire_plugin_predefined_responses" rows="5"><?php echo esc_textarea($cheshire_plugin_predefined_responses); ?></textarea>
                            <p class="description"><?php esc_html_e('Enter one predefined response per line. These will appear as clickable tags in the chat area. When clicked, they will be sent as messages to the chatbot.', 'cheshire-cat-chatbot'); ?></p>
                        </div>
                        <div class="configuration-margin-top">
                            <label for="cheshire_plugin_predefined_responses_title"><?php esc_html_e('Predefined Responses Title:', 'cheshire-cat-chatbot'); ?></label>
                            <input type="text" name="cheshire_plugin_predefined_responses_title" id="cheshire_plugin_predefined_responses_title" value="<?php echo esc_attr($cheshire_plugin_predefined_responses_title); ?>" />
                            <p class="description"><?php esc_html_e('This title will appear above the predefined responses when displayed in content.', 'cheshire-cat-chatbot'); ?></p>
                        </div>
                        <div class="configuration-margin-top">
                            <input type="checkbox" name="cheshire_plugin_show_predefined_in_content" id="cheshire_plugin_show_predefined_in_content" <?php checked($cheshire_plugin_show_predefined_in_content, 'on'); ?> />
                            <label for="cheshire_plugin_show_predefined_in_content"><?php esc_html_e('Show predefined responses at the end of content', 'cheshire-cat-chatbot'); ?></label>
                            <p class="description"><?php esc_html_e('Check this box to display predefined responses at the end of content for enabled post types. For regular posts, they will appear at the end of the content. For WooCommerce products, they will appear after the short description in the product summary area.', 'cheshire-cat-chatbot'); ?></p>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Predefined Responses in WooCommerce product category', 'cheshire-cat-chatbot'); ?></th>
                    <td>
                        <div>
                            <label for="cheshire_plugin_product_category_predefined_responses"><?php esc_html_e('Product Category Predefined Responses:', 'cheshire-cat-chatbot'); ?></label>
                            <textarea name="cheshire_plugin_product_category_predefined_responses" rows="5"><?php echo esc_textarea(get_option('cheshire_plugin_product_category_predefined_responses', '')); ?></textarea>
                            <p class="description"><?php esc_html_e('Enter one predefined response per line. These will appear as clickable tags in the chat area when viewing a product category archive. When clicked, they will be sent as messages to the chatbot. These responses will override the general predefined responses when the chat is used within a product category archive.', 'cheshire-cat-chatbot'); ?></p>
                        </div>
                        <div class="configuration-margin-top">
                            <label for="cheshire_plugin_max_predefined_questions"><?php esc_html_e('Maximum number of predefined questions to show:', 'cheshire-cat-chatbot'); ?></label>
                            <input type="number" min="0" step="1" name="cheshire_plugin_max_predefined_questions" id="cheshire_plugin_max_predefined_questions" value="<?php echo esc_attr($cheshire_plugin_max_predefined_questions); ?>" />
                            <p class="description"><?php esc_html_e('Set the maximum number of predefined questions to show to users. If there are more questions than this number, a random selection will be shown. Set to 0 to show all questions.', 'cheshire-cat-chatbot'); ?></p>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Global Chat', 'cheshire-cat-chatbot'); ?></th>
                    <td>
                        <input type="checkbox" name="cheshire_plugin_global_chat" <?php checked($cheshire_plugin_global_chat, 'on'); ?> />
                        <label for="cheshire_plugin_global_chat"><?php esc_html_e('Enable Global Chat', 'cheshire-cat-chatbot'); ?></label>
                        <p class="description"><?php esc_html_e('Check this box to enable the chat on every page of your website.', 'cheshire-cat-chatbot'); ?></p>
                    </td>
                </tr>
            </table>

            </div><!-- End of Chat Settings section -->

            <div id="content-type-settings" class="cheshire-section" style="<?php echo $cheshire_plugin_global_chat !== 'on' ? 'display: none;' : ''; ?>">
                <h2><?php esc_html_e('Content Type Settings', 'cheshire-cat-chatbot'); ?></h2>
                <p class="description"><?php esc_html_e('Configure how the chat should be displayed across your site.', 'cheshire-cat-chatbot'); ?></p>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Display Mode', 'cheshire-cat-chatbot'); ?></th>
                        <td>
                            <div style="margin-bottom: 10px;">
                                <input type="radio" id="site_wide" name="cheshire_plugin_content_type_mode" value="site_wide" <?php checked($cheshire_plugin_content_type_mode, 'site_wide'); ?> />
                                <label for="site_wide"><?php esc_html_e('Show on all pages', 'cheshire-cat-chatbot'); ?></label>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <input type="radio" id="selected_types" name="cheshire_plugin_content_type_mode" value="selected_types" <?php checked($cheshire_plugin_content_type_mode, 'selected_types'); ?> />
                                <label for="selected_types"><?php esc_html_e('Show only on selected post types and taxonomies', 'cheshire-cat-chatbot'); ?></label>
                            </div>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Show in Homepage', 'cheshire-cat-chatbot'); ?></th>
                        <td>
                            <input type="checkbox" name="cheshire_plugin_show_in_homepage" <?php checked($cheshire_plugin_show_in_homepage, 'on'); ?> />
                            <label for="cheshire_plugin_show_in_homepage"><?php esc_html_e('Enable chat on homepage', 'cheshire-cat-chatbot'); ?></label>
                            <p class="description"><?php esc_html_e('Check this box to show the chat on your site\'s homepage.', 'cheshire-cat-chatbot'); ?></p>
                        </td>
                    </tr>
                </table>

                <div id="post-type-taxonomy-selection" style="<?php echo $cheshire_plugin_content_type_mode !== 'selected_types' ? 'display: none;' : ''; ?>">
                    <h3><?php esc_html_e('Select Content Types', 'cheshire-cat-chatbot'); ?></h3>
                    <p class="description"><?php esc_html_e('Select which post types and taxonomies should have the chat enabled.', 'cheshire-cat-chatbot'); ?></p>

                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Post Types', 'cheshire-cat-chatbot'); ?></th>
                            <td>
                                <?php
                                $post_types = get_post_types(array('public' => true), 'objects');
                                $enabled_post_types = get_option('cheshire_plugin_enabled_post_types', array('post', 'page'));

                                foreach ($post_types as $post_type) {
                                    $checked = in_array($post_type->name, $enabled_post_types) ? 'checked' : '';
                                    echo '<div style="margin-bottom: 10px;">';
                                    echo '<input type="checkbox" id="cheshire_plugin_enabled_post_types_' . esc_attr($post_type->name) . '" name="cheshire_plugin_enabled_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . ' />';
                                    echo '<label for="cheshire_plugin_enabled_post_types_' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</label>';
                                    echo '</div>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Taxonomies', 'cheshire-cat-chatbot'); ?></th>
                            <td>
                                <?php
                                $taxonomies = get_taxonomies(array('public' => true), 'objects');
                                $enabled_taxonomies = get_option('cheshire_plugin_enabled_taxonomies', array('category', 'post_tag'));

                                foreach ($taxonomies as $taxonomy) {
                                    $checked = in_array($taxonomy->name, $enabled_taxonomies) ? 'checked' : '';
                                    echo '<div style="margin-bottom: 10px;">';
                                    echo '<input type="checkbox" id="cheshire_plugin_enabled_taxonomies_' . esc_attr($taxonomy->name) . '" name="cheshire_plugin_enabled_taxonomies[]" value="' . esc_attr($taxonomy->name) . '" ' . $checked . ' />';
                                    echo '<label for="cheshire_plugin_enabled_taxonomies_' . esc_attr($taxonomy->name) . '">' . esc_html($taxonomy->label) . '</label>';
                                    echo '</div>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div><!-- End of Content Type Settings section -->

            <script>
                jQuery(document).ready(function($) {
                    // Toggle content type settings visibility based on global chat checkbox
                    $('input[name="cheshire_plugin_global_chat"]').change(function() {
                        if ($(this).is(':checked')) {
                            $('#content-type-settings').show();
                        } else {
                            $('#content-type-settings').hide();
                        }
                    });

                    // Toggle post type and taxonomy selection based on content type mode
                    $('input[name="cheshire_plugin_content_type_mode"]').change(function() {
                        if ($(this).val() === 'selected_types') {
                            $('#post-type-taxonomy-selection').show();
                        } else {
                            $('#post-type-taxonomy-selection').hide();
                        }
                    });

                    // Toggle declarative memory options visibility based on declarative memory checkbox
                    $('#cheshire_plugin_enable_declarative_memory').change(function() {
                        if ($(this).is(':checked')) {
                            $('#declarative_memory_options').show();
                        } else {
                            $('#declarative_memory_options').hide();
                        }
                    });

                    // Toggle logged-in only options
                    $('#cheshire_plugin_logged_in_only').change(function() {
                        if ($(this).is(':checked')) {
                            $('#logged_in_only_options').show();
                        } else {
                            $('#logged_in_only_options').hide();
                        }
                    });

                    // Toggle preview text option
                    $('#cheshire_plugin_show_preview_logged_out').change(function() {
                        if ($(this).is(':checked')) {
                            $('#preview_text_option').show();
                        } else {
                            $('#preview_text_option').hide();
                        }
                    });
                });
            </script>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
