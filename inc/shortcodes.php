<?php

// Funzione per registrare lo shortcode
function cheshire_plugin_register_shortcode() {
    // Shortcode per visualizzare la chat
    function cheshire_plugin_chat_shortcode() {
        // Carica lo script JavaScript
        wp_enqueue_script('cheshire-chat-script', plugins_url('/js/chat.js', __FILE__), array('jquery'), '1.0', true);

        // Passa l'URL di AJAX allo script JavaScript
        wp_localize_script('cheshire-chat-script', 'cheshire_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

        // Restituisce il codice HTML per la chat
        return '<div id="cheshire-chat-container">
                    <div id="cheshire-chat-messages"></div>
                    <input type="text" id="cheshire-chat-input" placeholder="Type your message...">
                    <button id="cheshire-chat-send">Send</button>
                </div>';
    }
    add_shortcode('cheshire-chat', 'cheshire_plugin_chat_shortcode');
}

// Registra lo shortcode quando WordPress è pronto
add_action('init', 'cheshire_plugin_register_shortcode');