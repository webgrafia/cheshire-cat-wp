<?php

namespace webgrafia\cheshirecat;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode to display the chat
function cheshirecat_chat_shortcode()
{
    // Check if chat should only be shown to logged-in users
    $logged_in_only = get_option('cheshire_plugin_logged_in_only', 'off');
    $show_preview_logged_out = get_option('cheshire_plugin_show_preview_logged_out', 'off');
    $is_preview = false;

    if ($logged_in_only === 'on' && !is_user_logged_in()) {
        if ($show_preview_logged_out === 'on') {
            $is_preview = true;
        } else {
            return '';
        }
    }

    // Ensure frontend scripts and styles are enqueued when shortcode is executed
    if ( ! wp_script_is( 'cheshire-chat-js', 'enqueued' ) ) {
        cheshirecat_enqueue_scripts();
    }

    ob_start();

    // Avatar is always enabled
    $avatar_class = 'with-avatar';

    // Check default state
    $default_state = get_option('cheshire_plugin_default_state', 'open');
    // Always start with closed state in HTML, JavaScript will open it if needed
    $state_class = 'cheshire-chat-closed';

    // Combine classes
    $container_classes = trim($avatar_class . ' ' . $state_class);
    if ($is_preview) {
        $container_classes .= ' cheshire-preview-only';
    }

    $avatar_image = get_option('cheshire_chat_avatar_image', '');
    $default_avatar = CHESHIRE_CAT_PLUGIN_URL . 'assets/img/default-avatar.svg';

    ?>
    <div id="cheshire-chat-container" class="<?php echo esc_attr($container_classes); ?>">
        <div id="cheshire-chat-header">
            <button id="cheshire-chat-new" aria-label="<?php esc_attr_e('New conversation', 'cheshire-cat-chatbot'); ?>" title="<?php esc_attr_e('Start a new conversation', 'cheshire-cat-chatbot'); ?>">
                <i class="fas fa-plus"></i>
            </button>
            <button id="cheshire-chat-close" aria-label="<?php esc_attr_e('Close chat', 'cheshire-cat-chatbot'); ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="cheshire-chat-messages">
            <?php cheshirecat_display_welcome_message(); ?>
        </div>
        <div id="cheshire-chat-input-container">
            <input type="text" id="cheshire-chat-input" placeholder="<?php echo esc_attr(get_option('cheshire_plugin_input_placeholder', __('Type your message...', 'cheshire-cat-chatbot'))); ?>">
            <button id="cheshire-chat-send"></button>
        </div>
    </div>
    <div id="cheshire-chat-avatar">
        <img src="<?php echo esc_url(!empty($avatar_image) ? $avatar_image : $default_avatar); ?>" alt="Chat Avatar">
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cheshire_chat', __NAMESPACE__ . '\cheshirecat_chat_shortcode');

// Add the chat to all pages if the option is enabled
function cheshirecat_add_global_chat()
{
    $cheshire_global_chat = get_option('cheshire_plugin_global_chat');

    // Check if global chat is enabled
    if ($cheshire_global_chat !== 'on') {
        return;
    }

    // Check if chat should only be shown to logged-in users
    $logged_in_only = get_option('cheshire_plugin_logged_in_only', 'off');
    $show_preview_logged_out = get_option('cheshire_plugin_show_preview_logged_out', 'off');

    if ($logged_in_only === 'on' && !is_user_logged_in()) {
        if ($show_preview_logged_out !== 'on') {
            return;
        }
    }

    // Get content type mode
    $content_type_mode = get_option('cheshire_plugin_content_type_mode', 'site_wide');

    // Check if we're on the homepage
    $show_in_homepage = get_option('cheshire_plugin_show_in_homepage', 'off');
    $is_homepage = is_front_page() || is_home();

    // If we're on the homepage and it's enabled, show the chat
    if ($is_homepage && $show_in_homepage === 'on') {
        echo do_shortcode('[cheshire_chat]');
        return;
    }

    // If content type mode is site_wide, show the chat on all pages
    if ($content_type_mode === 'site_wide') {
        echo do_shortcode('[cheshire_chat]');
        return;
    }

    // Otherwise, check if current post type or taxonomy is enabled
    $enabled_post_types = get_option('cheshire_plugin_enabled_post_types', array('post', 'page'));
    $current_post_type = get_post_type();

    // Check if current taxonomy is enabled
    $enabled_taxonomies = get_option('cheshire_plugin_enabled_taxonomies', array('category', 'post_tag'));
    $is_taxonomy_page = false;
    $current_taxonomy = '';

    // Check if we're on a taxonomy archive page
    if (is_tax() || is_category() || is_tag()) {
        $is_taxonomy_page = true;
        $queried_object = get_queried_object();
        if (isset($queried_object->taxonomy)) {
            $current_taxonomy = $queried_object->taxonomy;
        }
    }

    // Only show chat if we're on an enabled post type or taxonomy
    if (
        // For all post types, only show on singular pages
        ($current_post_type && is_singular($current_post_type) && in_array($current_post_type, $enabled_post_types)) ||
        // For taxonomies, only show on taxonomy term pages (detail pages)
        ($is_taxonomy_page && in_array($current_taxonomy, $enabled_taxonomies))
    ) {
        echo do_shortcode('[cheshire_chat]');
    }
}
add_action('wp_footer', __NAMESPACE__ . '\cheshirecat_add_global_chat');
