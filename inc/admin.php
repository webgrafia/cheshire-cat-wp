<?php
// Add the menu item to the admin panel
function cheshire_plugin_menu()
{
    add_options_page(__('Cheshire Cat Settings', 'cheshire-cat-wp'), __('Cheshire Cat', 'cheshire-cat-wp'), 'manage_options', 'cheshire-cat-settings', 'cheshire_plugin_options');
}
add_action('admin_menu', 'cheshire_plugin_menu');
// Create the options page
function cheshire_plugin_options()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'cheshire-cat-wp'));
    }
    // Handle form submission
    if (isset($_POST['cheshire_plugin_submit'])) {
        update_option('cheshire_plugin_url', sanitize_text_field($_POST['cheshire_plugin_url']));
        update_option('cheshire_plugin_token', sanitize_text_field($_POST['cheshire_plugin_token']));
        update_option('cheshire_plugin_global_chat', isset($_POST['cheshire_plugin_global_chat']) ? 'on' : 'off');
        echo '<div class="updated"><p>' . __('Settings saved.', 'cheshire-cat-wp') . '</p></div>';
    }
    // Get current settings
    $cheshire_url = get_option('cheshire_plugin_url');
    $cheshire_token = get_option('cheshire_plugin_token');
    $cheshire_global_chat = get_option('cheshire_plugin_global_chat');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Cheshire Cat Settings', 'cheshire-cat-wp'); ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Cheshire Cat URL', 'cheshire-cat-wp'); ?></th>
                    <td><input type="text" name="cheshire_plugin_url" value="<?php echo esc_attr($cheshire_url); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Cheshire Cat Token', 'cheshire-cat-wp'); ?></th>
                    <td>
                        <input type="text" name="cheshire_plugin_token" value="<?php echo esc_attr($cheshire_token); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Enable Global Chat', 'cheshire-cat-wp'); ?></th>
                    <td>
                        <input type="checkbox" name="cheshire_plugin_global_chat" <?php checked($cheshire_global_chat, 'on'); ?> />
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="cheshire_plugin_submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'cheshire-cat-wp'); ?>" />
            </p>
        </form>
        <h2><?php esc_html_e('Cheshire Cat Debug', 'cheshire-cat-wp'); ?></h2>
        <?php
        // Debug section
        $cheshire_url = get_option('cheshire_plugin_url');
        $cheshire_token = get_option('cheshire_plugin_token');
        if (!empty($cheshire_url) && !empty($cheshire_token)) {
            try {
                $cheshire = new \CheshireCatWp\classes\CustomCheshireCat($cheshire_url, $cheshire_token);
                $status = $cheshire->getStatus(); // Use the public method
                //$plugins = $cheshire->getAvailablePlugins(); // Use the public method
                if (!empty($status['status'])) {
                    echo '<p><strong>' . __('Connection Status:', 'cheshire-cat-wp') . '</strong> <span style="color: green;">' . esc_html($status['status']) . '</span></p>';
                } else {
                    echo '<p><strong>' . __('Connection Status:', 'cheshire-cat-wp') . '</strong> <span style="color: red;">' . __('Error', 'cheshire-cat-wp') . '</span></p>';
                }
                $plugins = $cheshire->getAvailablePlugins();
                if (!empty($plugins['installed'])) {
                    echo '<h3>' . __('Cheshire Available Plugins:', 'cheshire-cat-wp') . '</h3>';
                    echo '<ul>';
                    foreach ($plugins['installed'] as $plugin) {
                        echo '<li>' . esc_html($plugin['id']) . '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p><strong>' . __('No plugins found.', 'cheshire-cat-wp') . '</strong></p>';
                }
            } catch (\Exception $e) {
                echo '<p><strong>' . __('Connection Status:', 'cheshire-cat-wp') . '</strong> <span style="color: red;">' . __('Error', 'cheshire-cat-wp') . '</span></p>';
                echo '<p><strong>' . __('Error:', 'cheshire-cat-wp') . '</strong> ' . esc_html($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p>' . __('Cheshire Cat URL or token not configured.', 'cheshire-cat-wp') . '</p>';
        }
        ?>
    </div>
    <?php
}