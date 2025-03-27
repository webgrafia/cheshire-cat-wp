<?php


// Adds a settings page to the admin menu
function cheshire_plugin_add_admin_menu() {
    add_options_page(
        'Cheshire Cat Settings',
        'Cheshire Cat',
        'manage_options',
        'cheshire-cat-settings',
        'cheshire_plugin_options_page'
    );
}
add_action('admin_menu', 'cheshire_plugin_add_admin_menu');

// Custom function for sanitizing the URL

function cheshire_sanitize_url($url) {
    if (empty($url)) {
        return '';
    }

    // Save the ws:// protocol if present
    $is_websocket = false;
    if (strpos($url, 'ws://') === 0) {
        $is_websocket = true;
        $url = str_replace('ws://', 'http://', $url);
    } else if (strpos($url, 'wss://') === 0) {
        $is_websocket = true;
        $url = str_replace('wss://', 'https://', $url);
    }

    // Sanitizza l'URL temporaneamente convertito
    $sanitized = esc_url_raw(trim($url));

    // Restore the websocket protocol if necessary
    if ($is_websocket) {
        $sanitized = str_replace('http://', 'ws://', $sanitized);
        $sanitized = str_replace('https://', 'wss://', $sanitized);
    }

    return $sanitized;
}
// Registra le impostazioni
function cheshire_plugin_register_settings() {
    // Register settings with custom callback
    register_setting(
        'cheshire-cat-settings-group',  // Group
        'cheshire_plugin_url',          // Option name
        array(
            'sanitize_callback' => 'cheshire_sanitize_url',
            'default' => ''
        )
    );

    register_setting(
        'cheshire-cat-settings-group',  // Group
        'cheshire_plugin_token',        // Option name
        array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        )
    );

    add_settings_section(
        'cheshire-cat-settings-section',
        'Cheshire Cat Connection Settings',
        'cheshire_plugin_settings_section_callback',
        'cheshire-cat-settings'
    );

    add_settings_field(
        'cheshire_plugin_url',
        'Cheshire Cat URL',
        'cheshire_plugin_url_callback',
        'cheshire-cat-settings',
        'cheshire-cat-settings-section'
    );

    add_settings_field(
        'cheshire_plugin_token',
        'Cheshire Cat Token',
        'cheshire_plugin_token_callback',
        'cheshire-cat-settings',
        'cheshire-cat-settings-section'
    );
}
add_action('admin_init', 'cheshire_plugin_register_settings');

// Function to display the settings page
function cheshire_plugin_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php
        // Debug messages
        $url = get_option('cheshire_plugin_url');
        $token = get_option('cheshire_plugin_token');
     //   error_log('DEBUG - Current values - URL: ' . $url . ', Token: ' . $token);
        ?>
        <form action="options.php" method="post">
            <?php
            settings_fields('cheshire-cat-settings-group');
            do_settings_sections('cheshire-cat-settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Callback per la sezione
function cheshire_plugin_settings_section_callback() {
    echo '<p>Enter your Cheshire Cat connection settings below.</p>';
}

function cheshire_plugin_url_callback() {
    $url = get_option('cheshire_plugin_url');
    ?>
    <input
            type="text"
            id="cheshire_plugin_url"
            name="cheshire_plugin_url"
            value="<?php echo esc_attr($url); ?>"
            class="regular-text"
            pattern="^(ws|wss|http|https):\/\/.*$"
            placeholder="http://localhost:1865/"
            required
    />
    <p class="description">Enter the Cheshire Cat service URL (supports ws://, wss://, http://, or https://)</p>
    <?php
}


// Callback for the Token field
function cheshire_plugin_token_callback() {
    $token = get_option('cheshire_plugin_token');
  //  error_log('DEBUG - Retrieving Token from database: ' . $token);
    ?>
    <input
            type="text"
            id="cheshire_plugin_token"
            name="cheshire_plugin_token"
            value="<?php echo esc_attr($token); ?>"
            class="regular-text"
            required
    />
    <p class="description">Enter your access token</p>
    <?php
}


// Add a custom pre-save validation
add_filter('pre_update_option_cheshire_plugin_url', function($value, $old_value) {
    error_log('DEBUG - Pre-save validation - Received value: ' . $value);

    // Check if the URL is empty
    if (empty($value)) {
        return $old_value;
    }

    // Check if the URL has a valid protocol
    if (!preg_match('/^(ws|wss|http|https):\/\//i', $value)) {
        // Add ws:// as default if missing
        $value = 'ws://' . $value;
    }

   // error_log('DEBUG - Pre-save validation - Final value: ' . $value);
    return $value;
}, 10, 2);

?>
