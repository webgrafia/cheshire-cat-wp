<?php

use CheshireCatSdk\CheshireCat;

// ajax for chat
function cheshire_plugin_ajax() {
    // Recupera il valore del messaggio
    $message = $_POST['message'];

    // Recupera l'URL del server da options
    $cheshire_url = get_option('cheshire_plugin_url');
    $cheshire_token = get_option('cheshire_plugin_token');

    // Verifica se l'URL è impostato
    if (empty($cheshire_url) || empty($cheshire_token)) {
        wp_send_json_error('Cheshire Cat URL or token not configured.');
        wp_die();
    }

    // Crea un'istanza di CheshireCat
    $cheshire = new CheshireCat($cheshire_url, $cheshire_token);

    try {
        // Invia il messaggio tramite il metodo send del CheshireCat
        $response = $cheshire->sendMessage($message);

        // Restituisce la risposta come JSON
        wp_send_json_success($response);
    } catch (\Exception $e) {
        // Gestisci eventuali errori
        wp_send_json_error($e->getMessage());
    }

    // Termina l'esecuzione dello script
    wp_die();
}

// Registra la funzione AJAX
add_action('wp_ajax_cheshire_plugin_ajax', 'cheshire_plugin_ajax');
add_action('wp_ajax_nopriv_cheshire_plugin_ajax', 'cheshire_plugin_ajax');