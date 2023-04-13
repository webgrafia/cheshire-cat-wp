<?php

/**
 * Add the link to the admin page
 */
function cheshire_plugin_menu() {
    add_menu_page(
        'Cheshire Cat WP', // Title of the page
        'Cheshire Cat WP', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'cheshire-plugin-page', // The 'slug' - file to display when clicking the link
        'cheshire_plugin_main_page', // The function to be called to output the content for this page.
        'dashicons-smiley' // The icon to be used for this menu
    );

    // add a submenu page
    add_submenu_page(
        'cheshire-plugin-page', // slug of the parent page
        'Cheshire Chat', // The title of the page
        'Chat', // The text to be displayed in the menu
        'manage_options', // The capability required for this menu to be displayed to the user.
        'cheshire-plugin-chat', // The slug of this submenu page
        'cheshire_plugin_chat_page' // The function to be called to output the content for this page.
    );

    add_submenu_page(
        'cheshire-plugin-page', // slug of the parent page
        'Cheshire Setup', // The title of the page
        'Setup', // The text to be displayed in the menu
        'manage_options', // The capability required for this menu to be displayed to the user.
        'cheshire-plugin-setup', // The slug of this submenu page
        'cheshire_plugin_setup_page' // The function to be called to output the content for this page.
    );


}

// Add the menu
add_action( 'admin_menu', 'cheshire_plugin_menu' );

// Show the main page
function cheshire_plugin_main_page() {
    include("admin/main.php");
}

// Show the chat page
function cheshire_plugin_chat_page()
{
    include("admin/chat.php");
}

// Show the setup page
function cheshire_plugin_setup_page()
{
    include("admin/setup.php");
}


// Register the settings
add_action('admin_init', 'cheshire_plugin_register_websocket_settings');
function cheshire_plugin_register_websocket_settings() {
    register_setting(
        'cheshire-plugin-websocket-group', // Il nome del gruppo di impostazioni
        'cheshire_plugin_websocket_url' // Il nome dell'opzione da salvare
    );
}


/**
 * Register the style and the script
 */
function cheshire_register_style() {
    wp_register_style('cheshire-style', plugins_url('src/admin-style.css',__FILE__ ));
    wp_enqueue_style('cheshire-style');
}

add_action( 'admin_init','cheshire_register_style');

