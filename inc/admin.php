<?php

// Aggiunge una pagina di impostazioni al menu di amministrazione
function cheshire_plugin_add_admin_menu() {
    add_options_page(
        'Cheshire Cat Settings', // Titolo della pagina
        'Cheshire Cat', // Titolo del menu
        'manage_options', // Capability richiesta
        'cheshire-cat-settings', // Slug della pagina
        'cheshire_plugin_options_page' // Funzione per visualizzare la pagina
    );
}
add_action('admin_menu', 'cheshire_plugin_add_admin_menu');

// Funzione per visualizzare la pagina di impostazioni
function cheshire_plugin_options_page() {
    ?>
    <div class="wrap">
        <h1>Cheshire Cat Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cheshire-cat-settings-group');
            do_settings_sections('cheshire-cat-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registra le impostazioni
function cheshire_plugin_register_settings() {
    register_setting(
        'cheshire-cat-settings-group', // Nome del gruppo di impostazioni
        'cheshire_plugin_url', // Nome dell'impostazione (URL)
        'esc_url_raw' // Funzione di sanitizzazione
    );
    register_setting(
        'cheshire-cat-settings-group', // Nome del gruppo di impostazioni
        'cheshire_plugin_token', // Nome dell'impostazione (Token)
        'sanitize_text_field' // Funzione di sanitizzazione
    );

    // Aggiunge una sezione alla pagina di impostazioni
    add_settings_section(
        'cheshire-cat-settings-section', // ID della sezione
        'Cheshire Cat Connection', // Titolo della sezione
        'cheshire_plugin_settings_section_callback', // Funzione di callback
        'cheshire-cat-settings' // Slug della pagina
    );

    // Aggiunge un campo per l'URL
    add_settings_field(
        'cheshire-cat-url', // ID del campo
        'Cheshire Cat URL', // Titolo del campo
        'cheshire_plugin_url_callback', // Funzione di callback
        'cheshire-cat-settings', // Slug della pagina
        'cheshire-cat-settings-section' // ID della sezione
    );

    // Aggiunge un campo per il Token
    add_settings_field(
        'cheshire-cat-token', // ID del campo
        'Cheshire Cat Token', // Titolo del campo
        'cheshire_plugin_token_callback', // Funzione di callback
        'cheshire-cat-settings', // Slug della pagina
        'cheshire-cat-settings-section' // ID della sezione
    );
}
add_action('admin_init', 'cheshire_plugin_register_settings');

// Funzione di callback per la sezione
function cheshire_plugin_settings_section_callback() {
    echo '<p>Enter your Cheshire Cat connection details below.</p>';
}

// Funzione di callback per il campo URL
function cheshire_plugin_url_callback() {
    $url = get_option('cheshire_plugin_url');
    echo '<input type="text" name="cheshire_plugin_url" value="' . esc_attr($url) . '" size="50" />';
}

// Funzione di callback per il campo Token
function cheshire_plugin_token_callback() {
    $token = get_option('cheshire_plugin_token');
    echo '<input type="text" name="cheshire_plugin_token" value="' . esc_attr($token) . '" size="50" />';
}