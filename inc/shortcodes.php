<?php

// Funzione per registrare lo shortcode
function cheshire_plugin_register_shortcode() {
    // Shortcode per visualizzare la chat
    function cheshire_plugin_shortcode() {
        ob_start();
        ?>
        <div id="cheshire-chat-container">
            <div id="cheshire-chat-messages"></div>
            <input type="text" id="cheshire-chat-input" placeholder="Type your message here...">
            <button id="cheshire-chat-send">Send</button>
        </div>
        <?php
        return ob_get_clean();
    }
    add_shortcode('cheshire_chat', 'cheshire_plugin_shortcode');
}

// Registra lo shortcode quando WordPress è pronto
add_action('init', 'cheshire_plugin_register_shortcode');