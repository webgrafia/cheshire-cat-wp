<?php
use \WebSocket\Client as WebSocket;

// ajax for chat
function cheshire_plugin_ajax() {
    // Recupera il valore del messaggio
    $message = $_POST['message'];


    // Connette al server WebSocket
    $server = new WebSocket(get_option('cheshire_plugin_websocket_url'), [
        'timeout' => 100, // Imposta il timeout a 10 secondi
    ]);
    $server->send($message);

    // Attende la risposta
    $response = $server->receive();

    // Restituisce la risposta come testo semplice
    echo esc_html($response);

    // Termina la connessione al server WebSocket
    $server->close();

    // Termina l'esecuzione dello script
    wp_die();
}

// Registra la funzione AJAX
add_action('wp_ajax_cheshire_plugin_ajax', 'cheshire_plugin_ajax');
add_action('wp_ajax_nopriv_cheshire_plugin_ajax', 'cheshire_plugin_ajax');
