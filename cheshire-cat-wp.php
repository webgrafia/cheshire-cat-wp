<?php
/*
Plugin Name: Cheshire Cat WP
Description: Plugin to integrate Cheshire Cat with WordPress
Version: 0.1
Author: Marco Buttarini
Author URI: https://bititup.it/
License: GPL2
*/

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ .'/inc/admin.php';
require_once __DIR__ .'/inc/shortcodes.php';
require_once __DIR__ .'/inc/ajax.php';
require_once __DIR__ .'/inc/CustomCheshireCatClient.php';
require_once __DIR__ .'/inc/CustomCheshireCat.php';


function cheshire_enqueue_scripts() {
    wp_enqueue_script('cheshire-chat-js', plugins_url('/assets/js/chat.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_style('cheshire-chat-css', plugins_url('/assets/css/chat.css', __FILE__), array(), '1.0');

    wp_localize_script('cheshire-chat-js', 'cheshire_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'cheshire_enqueue_scripts');