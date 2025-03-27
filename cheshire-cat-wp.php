<?php
/*
Plugin Name: Cheshire Cat WP
Description: A WordPress plugin to integrate the Cheshire Cat AI chatbot, offering seamless conversational AI for your site.
Version: 0.1
Author: Marco Buttarini
Author URI: https://bititup.it/
License: GPL3
Text Domain: cheshire-cat-wp
Domain Path: /languages
*/
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/shortcodes.php';
require_once __DIR__ . '/inc/ajax.php';
require_once __DIR__ . '/classes/CustomCheshireCatClient.php';
require_once __DIR__ . '/classes/CustomCheshireCat.php';
function cheshire_enqueue_scripts()
{
    wp_enqueue_script('cheshire-chat-js', plugins_url('/assets/js/chat.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_style('cheshire-chat-css', plugins_url('/assets/css/chat.css', __FILE__), array(), '1.0');
    wp_localize_script('cheshire-chat-js', 'cheshire_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'cheshire_enqueue_scripts');
function cheshire_load_textdomain() {
    load_plugin_textdomain( 'cheshire-cat-wp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'cheshire_load_textdomain' );