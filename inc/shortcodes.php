<?php

/**
 * Shortcode to show the chat
 * @return false|string
 */
function cheshire_chat_shortcode(){
    if(!is_admin()){
        require_once(ABSPATH . 'wp-admin/includes/template.php');
    }
    ob_start();
    ?>
    <script src='<?php echo plugins_url(); ?>/cheshire-cat-wp/src/chat.js?ver=6.1.1' id='cheshire-js-js'></script>
    <link rel='stylesheet' id='cheshire-chat-style-css' href='<?php echo plugins_url(); ?>/cheshire-cat-wp/src/chat.css' media='all' />
    <script type="text/javascript">
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    </script>
    <div id="chat-container">
        <div id="cheshire-plugin-result">
        </div>
        <form id="cheshire-plugin-form">
            <textarea id="message" name="message" placeholder="Scrivi qui il tuo messaggio"></textarea>
            <?php echo get_submit_button(); ?>
        </form>
    </div>

    <div id="loader-container">
        <div id="loader"></div>
    </div>
<?php
    $output_string = ob_get_contents();
    ob_end_clean();
    return $output_string;
}

add_shortcode('cheshire_chat', 'cheshire_chat_shortcode');