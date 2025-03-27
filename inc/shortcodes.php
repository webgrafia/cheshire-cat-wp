<?php
// Shortcode to display the chat
function cheshire_chat_shortcode()
{
    ob_start();
    ?>
    <div id="cheshire-chat-container">
        <div id="cheshire-chat-messages">
            <?php \CheshireCatWp\cheshire_display_welcome_message(); ?>
        </div>
        <div id="cheshire-chat-input-container">
            <input type="text" id="cheshire-chat-input" placeholder="<?php _e('Type your message...', 'cheshire-cat-wp'); ?>">
            <button id="cheshire-chat-send"></button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cheshire_chat', 'cheshire_chat_shortcode');

// Add the chat to all pages if the option is enabled
function cheshire_add_global_chat()
{
    $cheshire_global_chat = get_option('cheshire_plugin_global_chat');
    if ($cheshire_global_chat === 'on') {
        echo do_shortcode('[cheshire_chat]');
    }
}
add_action('wp_footer', 'cheshire_add_global_chat');