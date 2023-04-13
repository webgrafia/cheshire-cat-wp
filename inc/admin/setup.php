<?php
?>
    <div class="wrap">
        <h1>WebSocket Cheshire Cat</h1>
        <p>Here you can setup the WebSocket Cheshire Cat over WP</p>
        <form method="post" action="options.php">
            <?php settings_fields('cheshire-plugin-websocket-group'); ?>
            <?php do_settings_sections('cheshire-plugin-websocket'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">URL WebSocket</th>
                    <td><input placeholder="ws://localhost:1865/ws" type="text" name="cheshire_plugin_websocket_url" value="<?php echo esc_attr(get_option('cheshire_plugin_websocket_url')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php