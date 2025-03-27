<?php
// shortcode for chat
function cheshire_plugin_shortcode()
{
    ob_start();
    ?>
    <div id="cheshire-chat-container">
        <div id="cheshire-chat-messages"></div>
        <div id="cheshire-chat-input-container">
            <input type="text" id="cheshire-chat-input" placeholder="<?php esc_attr_e('Type your message here...', 'cheshire-cat-wp'); ?>">
            <button id="cheshire-chat-send"><?php esc_html_e('Send', 'cheshire-cat-wp'); ?></button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cheshire_chat', 'cheshire_plugin_shortcode');
// Add global chat if enabled
function cheshire_add_global_chat($content)
{
    if (is_singular() && get_option('cheshire_plugin_global_chat') == 'on') {
        $content .= do_shortcode('[cheshire_chat]');
    }
    return $content;
}
add_filter('the_content', 'cheshire_add_global_chat');