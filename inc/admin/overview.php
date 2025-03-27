<?php

namespace CheshireCatWp\inc\admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Overview & Usage page callback.
 */
function cheshire_cat_overview_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'cheshire-cat-wp'));
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <p><?php _e('Welcome to the Cheshire Cat WP plugin! This plugin allows you to integrate the powerful Cheshire Cat AI chatbot into your WordPress website.', 'cheshire-cat-wp'); ?></p>

        <h2><?php _e('Before You Begin', 'cheshire-cat-wp'); ?></h2>
        <p>
            <?php _e('To use this plugin, you must have a working installation of <a href="https://cheshirecat.ai/" target="_blank">Cheshire Cat AI</a>. This plugin acts as a bridge between your WordPress site and your Cheshire Cat AI instance.', 'cheshire-cat-wp'); ?>
        </p>
        <p>
            <?php _e('You will need the following information from your Cheshire Cat AI setup:', 'cheshire-cat-wp'); ?>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><?php _e('<strong>Cheshire Cat URL:</strong> The URL where your Cheshire Cat AI instance is running.', 'cheshire-cat-wp'); ?></li>
            <li><?php _e('<strong>Cheshire Cat Token:</strong> The API token for your Cheshire Cat AI instance.', 'cheshire-cat-wp'); ?></li>
        </ul>
        </p>
        <p>
            <?php _e('You can enter these details in the <a href="admin.php?page=cheshire-cat-configuration">Configuration</a> section.', 'cheshire-cat-wp'); ?>
        </p>

        <h2><?php _e('Usage', 'cheshire-cat-wp'); ?></h2>

        <h3><?php _e('Displaying the Chat with the Shortcode', 'cheshire-cat-wp'); ?></h3>
        <p>
            <?php _e('To display the chat on a specific page or post, use the following shortcode:', 'cheshire-cat-wp'); ?>
            <code>[cheshire_chat]</code>
        </p>
        <p>
            <?php _e('Simply paste this shortcode into the content area of any page or post where you want the chat to appear.', 'cheshire-cat-wp'); ?>
        </p>

        <h3><?php _e('Enabling Global Chat', 'cheshire-cat-wp'); ?></h3>
        <p>
            <?php _e('If you want the chat to appear on every page of your website, you can enable the "Global Chat" option in the <a href="admin.php?page=cheshire-cat-configuration">Configuration</a> section.', 'cheshire-cat-wp'); ?>
        </p>
        <p>
            <?php _e('When the Global Chat is enabled, the chat will be automatically added to all pages, and you <strong>do not</strong> need to use the shortcode.', 'cheshire-cat-wp'); ?>
        </p>
    </div>
    <?php
}