<?php

namespace webgrafia\cheshirecat\inc\admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Declarative Memory Sync page callback.
 */
function cheshirecat_declarative_memory_sync_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cheshire-cat-chatbot'));
    }

    // Get all public post types
    $post_types = get_post_types(array('public' => true), 'objects');

    // Get Cheshire Cat configuration
    $cheshire_plugin_cat_version = get_option('cheshire_plugin_cat_version', 'v1');
    $cheshire_plugin_url = get_option('cheshire_plugin_url');
    $cheshire_plugin_url_v2 = get_option('cheshire_plugin_url_v2', '');
    $cheshire_plugin_token = get_option('cheshire_plugin_token');
    $cheshire_plugin_api_key = get_option('cheshire_plugin_api_key', '');
    $enable_declarative_memory = get_option('cheshire_plugin_enable_declarative_memory', 'off');

    // Check if declarative memory is enabled
    $is_declarative_memory_enabled = ($enable_declarative_memory === 'on');

    // Check if Cheshire Cat is configured
    $active_cheshire_url = ($cheshire_plugin_cat_version === 'v2') ? $cheshire_plugin_url_v2 : $cheshire_plugin_url;
    $is_cheshire_cat_configured = ($cheshire_plugin_cat_version === 'v2')
        ? (!empty($active_cheshire_url) && !empty($cheshire_plugin_api_key))
        : (!empty($active_cheshire_url) && !empty($cheshire_plugin_token));

    ?>
    <div class="wrap cheshire-admin">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <?php if (!$is_declarative_memory_enabled): ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('Declarative Memory Upload is not enabled. Please enable it in the Configuration page.', 'cheshire-cat-chatbot'); ?></p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=cheshire-cat-configuration')); ?>" class="button"><?php esc_html_e('Go to Configuration', 'cheshire-cat-chatbot'); ?></a></p>
            </div>
        <?php endif; ?>

        <?php if (!$is_cheshire_cat_configured): ?>
            <div class="notice notice-error">
                <p><?php esc_html_e('Cheshire Cat is not properly configured. Please set the URL and token in the Configuration page.', 'cheshire-cat-chatbot'); ?></p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=cheshire-cat-configuration')); ?>" class="button"><?php esc_html_e('Go to Configuration', 'cheshire-cat-chatbot'); ?></a></p>
            </div>
        <?php endif; ?>

        <?php if ($is_declarative_memory_enabled && $is_cheshire_cat_configured): ?>
            <div class="cheshire-section">
                <h2><?php _e('Declarative Memory Synchronization', 'cheshire-cat-chatbot'); ?></h2>
                <p><?php esc_html_e('Use this page to process posts and send their content to the Cheshire Cat declarative memory.', 'cheshire-cat-chatbot'); ?></p>

                <form id="declarative-memory-sync-form">
                    <h2><?php esc_html_e('Select Post Types', 'cheshire-cat-chatbot'); ?></h2>
                    <p class="description"><?php esc_html_e('Choose which post types you want to process.', 'cheshire-cat-chatbot'); ?></p>

                    <?php $dm_enabled = get_option('cheshire_plugin_declarative_memory_post_types', array()); ?>
                    <div class="post-types-container">
                        <?php foreach ($post_types as $post_type): ?>
                            <?php $is_checked = empty($dm_enabled) ? true : in_array($post_type->name, (array)$dm_enabled, true); ?>
                            <div class="configuration-margin-bottom">
                                <input type="checkbox" id="post_type_<?php echo esc_attr($post_type->name); ?>" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                                <label for="post_type_<?php echo esc_attr($post_type->name); ?>"><?php echo esc_html($post_type->label); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h2><?php esc_html_e('Additional Filters', 'cheshire-cat-chatbot'); ?></h2>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Post Status', 'cheshire-cat-chatbot'); ?></th>
                            <td>
                                <select name="post_status" id="post_status">
                                    <option value="publish" selected><?php esc_html_e('Published Only', 'cheshire-cat-chatbot'); ?></option>
                                    <option value="any"><?php esc_html_e('Any Status', 'cheshire-cat-chatbot'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e('Date Range', 'cheshire-cat-chatbot'); ?></th>
                            <td>
                                <select name="date_range" id="date_range">
                                    <option value="all" selected><?php esc_html_e('All Time', 'cheshire-cat-chatbot'); ?></option>
                                    <option value="last_day"><?php esc_html_e('Last 24 Hours', 'cheshire-cat-chatbot'); ?></option>
                                    <option value="last_week"><?php esc_html_e('Last Week', 'cheshire-cat-chatbot'); ?></option>
                                    <option value="last_month"><?php esc_html_e('Last Month', 'cheshire-cat-chatbot'); ?></option>
                                    <option value="last_year"><?php esc_html_e('Last Year', 'cheshire-cat-chatbot'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <div class="sync-actions">
                        <button type="button" id="start-sync" class="button button-primary"><?php esc_html_e('Start Synchronization', 'cheshire-cat-chatbot'); ?></button>
                        <button type="button" id="stop-sync" class="button" hidden><?php esc_html_e('Stop Synchronization', 'cheshire-cat-chatbot'); ?></button>
                    </div>
                </form>
            </div><!-- End of Declarative Memory Synchronization section -->

            <div id="sync-progress-container" class="cheshire-section" hidden>
                <h2><?php esc_html_e('Synchronization Progress', 'cheshire-cat-chatbot'); ?></h2>
                    <div class="progress-bar-container">
                        <div id="sync-progress-bar"></div>
                    </div>
                    <div id="sync-progress-text">
                        <span id="sync-processed">0</span> / <span id="sync-total">0</span> <?php esc_html_e('posts processed', 'cheshire-cat-chatbot'); ?>
                    </div>
                    <div id="sync-current-item"></div>
                    <div id="sync-results">
                        <h3><?php esc_html_e('Results', 'cheshire-cat-chatbot'); ?></h3>
                        <div id="sync-success-count">0 <?php esc_html_e('posts successfully synchronized', 'cheshire-cat-chatbot'); ?></div>
                        <div id="sync-error-count">0 <?php esc_html_e('posts failed', 'cheshire-cat-chatbot'); ?></div>
                    </div>
                </div>
            </div><!-- End of Synchronization Progress section -->

            <script>
                jQuery(document).ready(function($) {
                    let isSyncing = false;
                    let shouldStop = false;
                    let processedCount = 0;
                    let totalCount = 0;
                    let successCount = 0;
                    let errorCount = 0;
                    let postIds = [];
                    let currentIndex = 0;

                    // Start synchronization
                    $('#start-sync').on('click', function() {
                        if (isSyncing) return;

                        // Get selected post types
                        const selectedPostTypes = [];
                        $('input[name="post_types[]"]:checked').each(function() {
                            selectedPostTypes.push($(this).val());
                        });

                        if (selectedPostTypes.length === 0) {
                            alert('<?php esc_html_e('Please select at least one post type.', 'cheshire-cat-chatbot'); ?>');
                            return;
                        }

                        // Get other filters
                        const postStatus = $('#post_status').val();
                        const dateRange = $('#date_range').val();

                        // Reset counters
                        processedCount = 0;
                        successCount = 0;
                        errorCount = 0;
                        postIds = [];
                        currentIndex = 0;
                        shouldStop = false;

                        // Show progress container
                        $('#sync-progress-container').show();
                        $('#sync-progress-bar').css('width', '0%');
                        $('#sync-processed').text('0');
                        $('#sync-total').text('0');
                        $('#sync-success-count').text('0 <?php esc_html_e('posts successfully synchronized', 'cheshire-cat-chatbot'); ?>');
                        $('#sync-error-count').text('0 <?php esc_html_e('posts failed', 'cheshire-cat-chatbot'); ?>');
                        $('#sync-current-item').text('');

                        // Toggle buttons
                        $('#start-sync').hide();
                        $('#stop-sync').show();

                        // Set syncing flag
                        isSyncing = true;

                        // First, get the total count of posts to process
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'cheshire_get_posts_count',
                                nonce: '<?php echo wp_create_nonce('cheshire_declarative_memory_sync_nonce'); ?>',
                                post_types: selectedPostTypes,
                                post_status: postStatus,
                                date_range: dateRange
                            },
                            success: function(response) {
                                if (response.success) {
                                    totalCount = response.data.count;
                                    postIds = response.data.post_ids;
                                    $('#sync-total').text(totalCount);

                                    // Start processing posts
                                    processNextBatch();
                                } else {
                                    alert('<?php esc_html_e('Error: Could not get posts count.', 'cheshire-cat-chatbot'); ?>');
                                    resetSyncUI();
                                }
                            },
                            error: function() {
                                alert('<?php esc_html_e('Error: Could not connect to the server.', 'cheshire-cat-chatbot'); ?>');
                                resetSyncUI();
                            }
                        });
                    });

                    // Stop synchronization
                    $('#stop-sync').on('click', function() {
                        shouldStop = true;
                        $(this).prop('disabled', true).text('<?php esc_html_e('Stopping...', 'cheshire-cat-chatbot'); ?>');
                    });

                    // Process posts in batches
                    function processNextBatch() {
                        if (shouldStop || currentIndex >= postIds.length) {
                            // Synchronization complete or stopped
                            resetSyncUI();
                            return;
                        }

                        // Get the next batch of posts (process 5 at a time)
                        const batchSize = 5;
                        const endIndex = Math.min(currentIndex + batchSize, postIds.length);
                        const batchIds = postIds.slice(currentIndex, endIndex);

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'cheshire_process_posts_batch',
                                nonce: '<?php echo wp_create_nonce('cheshire_declarative_memory_sync_nonce'); ?>',
                                post_ids: batchIds
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Update counters
                                    processedCount += response.data.processed;
                                    successCount += response.data.success;
                                    errorCount += response.data.error;

                                    // Update UI
                                    const progressPercentage = (processedCount / totalCount) * 100;
                                    $('#sync-progress-bar').css('width', progressPercentage + '%');
                                    $('#sync-processed').text(processedCount);
                                    $('#sync-success-count').text(successCount + ' <?php esc_html_e('posts successfully synchronized', 'cheshire-cat-chatbot'); ?>');
                                    $('#sync-error-count').text(errorCount + ' <?php esc_html_e('posts failed', 'cheshire-cat-chatbot'); ?>');

                                    if (response.data.current_title) {
                                        $('#sync-current-item').text('<?php esc_html_e('Last processed:', 'cheshire-cat-chatbot'); ?> ' + response.data.current_title);
                                    }

                                    // Move to the next batch
                                    currentIndex = endIndex;
                                    setTimeout(processNextBatch, 500);
                                } else {
                                    alert('<?php esc_html_e('Error: Could not process posts batch.', 'cheshire-cat-chatbot'); ?>');
                                    resetSyncUI();
                                }
                            },
                            error: function() {
                                alert('<?php esc_html_e('Error: Could not connect to the server.', 'cheshire-cat-chatbot'); ?>');
                                resetSyncUI();
                            }
                        });
                    }

                    // Reset the UI after synchronization
                    function resetSyncUI() {
                        isSyncing = false;
                        $('#start-sync').show();
                        $('#stop-sync').hide().prop('disabled', false).text('<?php esc_html_e('Stop Synchronization', 'cheshire-cat-chatbot'); ?>');
                    }
                });
            </script>
        <?php endif; ?>
    </div>
    <?php
}
