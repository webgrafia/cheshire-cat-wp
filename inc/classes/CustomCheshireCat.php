<?php
/**
 * Custom Cheshire Cat client implementation
 *
 * @package CheshireCatChatbot
 */

namespace webgrafia\cheshirecat\inc\classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use CheshireCatSdk\CheshireCat;

/**
 * Custom implementation of the Cheshire Cat client.
 *
 * This class extends the base CheshireCat class from the SDK and provides
 * custom functionality for the WordPress plugin.
 *
 * @since 0.1
 */
class Custom_Cheshire_Cat extends CheshireCat {
    /**
     * Page ID for context information.
     *
     * @since 0.4.2
     * @var int
     */
    protected $page_id = 0;

    /**
     * Page URL for context information.
     *
     * @since 0.4.2
     * @var string
     */
    protected $page_url = '';

    /**
     * Flag to indicate if the request is coming from the editor.
     *
     * @since 0.6.0
     * @var bool
     */
    protected $from_editor = false;

    /**
     * Set the page context information.
     *
     * @since 0.4.2
     * @param int    $page_id  The ID of the current page.
     * @param string $page_url The URL of the current page.
     * @return void
     */
    public function setPageContext($page_id, $page_url) {
        $this->page_id = $page_id;
        $this->page_url = $page_url;
    }

    /**
     * Set whether the request is coming from the editor.
     *
     * @since 0.6.0
     * @param bool $from_editor Whether the request is coming from the editor.
     * @return void
     */
    public function setFromEditor($from_editor) {
        $this->from_editor = $from_editor;
    }

    /**
     * Public method to get context information about the current WordPress page.
     * This is a wrapper for the protected get_context_information method.
     *
     * @since 0.6.5
     * @return string The context information formatted as a string.
     */
    public function getContextInformation() {
        return $this->get_context_information();
    }

    /**
     * Get context information about the current WordPress page.
     *
     * @since 0.5
     * @return string The context information formatted as a string.
     */
    protected function get_context_information() {
        $context = "## Origin of request: \n";
        $context .= "website: " . get_bloginfo('name') . "\n";
        $context .= "If the user asks a question, assume that he is talking about the page he is on (the origin of request) and simulate you can read it online\n";

        // Get the post object based on page_id if available
        $post = null;
        $post_type = '';
        $is_woocommerce_product = false;

        if ($this->page_id > 0) {
            $post = get_post($this->page_id);
            if ($post) {
                $post_type = $post->post_type;
                $is_woocommerce_product = function_exists('wc_get_product') && $post_type === 'product';
            }
        } else {
            // Try to get the current post if we're not in an AJAX context
            global $post;
        }

        // Add page URL if available
        if (!empty($this->page_url)) {
            $context .= "url: " . $this->page_url . "\n";
        }

        // Determine page type
        if ($post) {
            if ($post_type === 'post') {
                $context .= "pagetype: post\n";
            } elseif ($post_type === 'page') {
                // Check if it's the front page
                if (get_option('page_on_front') == $post->ID) {
                    $context .= "pagetype: homepage\n";
                } else {
                    $context .= "pagetype: page\n";
                }
            } elseif ($is_woocommerce_product) {
                $context .= "pagetype: woocommerce_product\n";
            } else {
                $context .= "pagetype: " . $post_type . "\n";
            }

            // Get title - use post title directly
            $title = $post->post_title;
            $context .= "title: " . wp_strip_all_tags($title) . "\n";

            // Get content/description
            if ($post_type === 'post' || $post_type === 'page') {
                // For posts and pages, get excerpt or content
                if (!empty($post->post_excerpt)) {
                    $context .= "content: " . wp_strip_all_tags($post->post_excerpt) . "\n";
                } else if (!empty($post->post_content)) {
                    // Get the full content
                    $content = $post->post_content;

                    // Remove shortcodes
                    $content = strip_shortcodes($content);

                    // Trim to a reasonable length
                    $excerpt = wp_trim_words($content, 100, '...');
                    $context .= "content: " . wp_strip_all_tags($excerpt) . "\n";
                }

                // Add categories and tags for posts
                if ($post_type === 'post') {
                    $categories = get_the_category($post->ID);
                    if (!empty($categories)) {
                        $category_names = array_map(function($cat) {
                            return $cat->name;
                        }, $categories);
                        $context .= "categories: " . implode(', ', $category_names) . "\n";
                    }

                    $tags = get_the_tags($post->ID);
                    if (!empty($tags)) {
                        $tag_names = array_map(function($tag) {
                            return $tag->name;
                        }, $tags);
                        $context .= "tags: " . implode(', ', $tag_names) . "\n";
                    }
                }
            }

            // For WooCommerce products, add additional information
            if ($is_woocommerce_product) {
                $product = wc_get_product($post->ID);
                if ($product) {
                    // Get product description
                    $product_description = $product->get_description();
                    if (empty($product_description)) {
                        $product_description = $product->get_short_description();
                    }
                    if (!empty($product_description)) {
                        $context .= "content: " . wp_strip_all_tags($product_description) . "\n";
                    }

                    // Get price
                    $context .= "price: " . wp_strip_all_tags($product->get_price_html()) . "\n";

                    // Get product categories
                    $product_categories = wc_get_product_category_list($post->ID);
                    if (!empty($product_categories)) {
                        $context .= "product_categories: " . wp_strip_all_tags($product_categories) . "\n";
                    }

                    // Get custom tabs from woocommerce-product-tabs plugin
                    if (class_exists('Barn2\Plugin\WC_Product_Tabs_Free\Product_Tabs')) {
                        $custom_tabs = get_posts([
                            'post_type'      => 'woo_product_tab',
                            'posts_per_page' => -1,
                            'orderby'        => 'menu_order',
                            'order'          => 'asc',
                            'suppress_filters' => 0
                        ]);

                        if (!empty($custom_tabs)) {
                            $tabs_content = "";
                            foreach ($custom_tabs as $tab) {
                                $tab_id = $tab->post_name;
                                $tab_title = $tab->post_title;

                                // Check if tab is overridden for this product
                                $override_meta = get_post_meta($post->ID, '_wpt_override_' . $tab_id, true);

                                // The _wpt_override key doesn't exist in older versions of the plugin
                                // Check for the _wpt_field_ meta for the product as a fallback
                                if (empty($override_meta) && get_post_meta($post->ID, '_wpt_field_' . $tab_id, true)) {
                                    $override_meta = 'yes';
                                }

                                $override_content = $override_meta === 'yes';

                                if ($override_content) {
                                    $tab_content = get_post_meta($post->ID, '_wpt_field_' . $tab_id, true);
                                } else {
                                    $tab_content = $tab->post_content;
                                }

                                if (!empty($tab_content)) {
                                    $tabs_content .= $tab_title . ": " . wp_strip_all_tags($tab_content) . "\n";
                                }
                            }

                            if (!empty($tabs_content)) {
                                $context .= "custom_tabs: \n" . $tabs_content;
                            }
                        }
                    }

                    // Get product variations if it's a variable product
                    if ($product->is_type('variable')) {
                        $variations = $product->get_available_variations();
                        $variation_info = "";
                        foreach ($variations as $variation) {
                            $variation_product = wc_get_product($variation['variation_id']);
                            $attributes = $variation_product->get_variation_attributes();
                            $variation_info .= "- ";

                            // Add variation SKU
                            $sku = $variation_product->get_sku();
                            if (!empty($sku)) {
                                $variation_info .= "SKU: " . $sku . ", ";
                            }

                            // Add variation ID
                            $variation_info .= "ID: " . $variation_product->get_id() . ", ";

                            // Add attributes
                            foreach ($attributes as $attribute_name => $attribute_value) {
                                $taxonomy = str_replace('attribute_', '', $attribute_name);
                                $term = get_term_by('slug', $attribute_value, $taxonomy);
                                $attribute_label = wc_attribute_label($taxonomy);
                                $variation_info .= $attribute_label . ": " . ($term ? $term->name : $attribute_value) . ", ";
                            }

                            // Add price
                            $variation_info .= "Price: " . $variation_product->get_price_html();

                            // Add stock status and quantity if available
                            if ($variation_product->managing_stock()) {
                                $stock_quantity = $variation_product->get_stock_quantity();
                                $variation_info .= ", Stock: " . ($stock_quantity !== null ? $stock_quantity : 'N/A');
                            } else {
                                $variation_info .= ", Stock: " . ($variation_product->is_in_stock() ? 'In Stock' : 'Out of Stock');
                            }

                            // Add any custom meta fields that might contain codes
                            $meta_data = $variation_product->get_meta_data();
                            foreach ($meta_data as $meta) {
                                // Filter for relevant meta keys that might contain codes
                                // Adjust this condition based on your specific meta field naming conventions
                                if (strpos($meta->key, 'code') !== false || strpos($meta->key, 'cod') !== false) {
                                    $variation_info .= ", " . ucfirst($meta->key) . ": " . $meta->value;
                                }
                            }

                            $variation_info .= "\n";
                        }
                        $context .= "variants: \n" . $variation_info;
                    }
                }
            }
        } else {
            // Try to determine page type using WordPress conditional functions
            // These may not work in AJAX context, but we'll try anyway

            if (function_exists('is_archive') && is_archive()) {
                $context .= "pagetype: archive\n";

                // Get archive title
                if (function_exists('get_the_archive_title')) {
                    $title = get_the_archive_title();
                    if (!empty($title)) {
                        $context .= "title: " . wp_strip_all_tags($title) . "\n";
                    }
                }

                // Get archive description
                if (function_exists('get_the_archive_description')) {
                    $description = get_the_archive_description();
                    if (!empty($description)) {
                        $context .= "content: " . wp_strip_all_tags($description) . "\n";
                    }
                }
            } else if (function_exists('is_product_category') && is_product_category()) {
                $context .= "pagetype: product_category\n";
                $term = get_queried_object();
                if ($term) {
                    $context .= "title: " . wp_strip_all_tags($term->name) . "\n";
                    if (!empty($term->description)) {
                        $context .= "content: " . wp_strip_all_tags($term->description) . "\n";
                    }
                }
            } else if (function_exists('is_search') && is_search()) {
                $context .= "pagetype: search\n";
                $context .= "title: " . sprintf(__('Search Results for: %s', 'cheshire-cat-chatbot'), get_search_query()) . "\n";
                $context .= "search_query: " . get_search_query() . "\n";
            } else if (function_exists('is_front_page') && is_front_page()) {
                $context .= "pagetype: homepage\n";
                $context .= "title: " . wp_strip_all_tags(get_bloginfo('name')) . "\n";
                $context .= "content: " . wp_strip_all_tags(get_bloginfo('description')) . "\n";
            } else {
                // Fallback for when we can't determine the page type
                $context .= "pagetype: unknown\n";

                // Try to get title from current page
                $title = wp_get_document_title();
                if (!empty($title)) {
                    $context .= "title: " . wp_strip_all_tags($title) . "\n";
                }

                // Try to extract information from URL
                if (!empty($this->page_url)) {
                    $parsed_url = parse_url($this->page_url);
                    if (isset($parsed_url['path'])) {
                        $path = trim($parsed_url['path'], '/');
                        $path_parts = explode('/', $path);
                        if (!empty($path_parts)) {
                            $context .= "path: " . implode('/', $path_parts) . "\n";
                        }
                    }
                }
            }
        }

        return $context;
    }

    /**
     * Base URL for the Cheshire Cat API.
     *
     * @since 0.1
     * @var string
     */
    protected $base_url;

    /**
     * Authentication token for the Cheshire Cat API.
     *
     * @since 0.1
     * @var string
     */
    protected $token;

    /**
     * API Key for the Cheshire Cat API (v2).
     *
     * @since 1.1.0
     * @var string
     */
    protected $api_key;

    /**
     * Cheshire Cat version (v1 or v2).
     *
     * @since 1.1.0
     * @var string
     */
    protected $cat_version;

    /**
     * HTTP client instance.
     *
     * @since 0.1
     * @var Custom_Cheshire_Cat_Client
     */
    protected $client;

    /**
     * Constructor.
     *
     * @since 0.1
     * @param string $base_url     The base URL for the Cheshire Cat API.
     * @param string $token        The authentication token (v1).
     * @param string $cat_version  Cheshire Cat version: 'v1' or 'v2'. Default 'v1'.
     * @param string $api_key      API Key for v2 authentication. Default ''.
     */
    public function __construct( $base_url, $token, $cat_version = 'v1', $api_key = '' ) {
        $this->base_url    = $base_url;
        $this->token       = $token;
        $this->cat_version = $cat_version;
        $this->api_key     = $api_key;
        $this->client      = $this->create_client();
    }

    /**
     * Create a new client instance.
     *
     * @since 0.1
     * @return Custom_Cheshire_Cat_Client The client instance.
     */
    protected function create_client() {
        return new Custom_Cheshire_Cat_Client( $this->base_url, $this->token, $this->cat_version, $this->api_key );
    }

    /**
     * Send a message to the Cheshire Cat API.
     *
     * @since 0.1
     * @param string $message The message to send.
     * @param array  $options Additional options for the request.
     * @return array The response from the API.
     */
    public function sendMessage( string $message, array $options = [] ): array {
        // Get user_id: if user is logged in, use username, otherwise use a cookie-based identifier
        $user_id = 'wp';

        // Check if user is logged in
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $user_id = $current_user->user_login;
        } else {
            // For non-logged in users, use a cookie-based identifier
            $cookie_name = 'cheshire_cat_user_id';

            if ( isset( $_COOKIE[$cookie_name] ) ) {
                $user_id = sanitize_text_field( $_COOKIE[$cookie_name] );
            } else {
                // Generate a unique ID
                $user_id = 'guest_' . uniqid();

                // Set cookie to expire in 30 days
                setcookie( $cookie_name, $user_id, time() + ( 86400 * 30 ), '/' );
            }
        }

        // Only add context and reinforcement if not coming from the editor
        // The from_editor property should be a boolean value at this point
        // Simply negate it to determine if we should add context and reinforcement
        $should_add_context_and_reinforcement = !$this->from_editor;

        // if the user is editor create a random user to avoid cheshire memory
        if($this->from_editor){
            $user_id = 'editor_' . uniqid();
        }

        if ($should_add_context_and_reinforcement) {

            // Check if context information is enabled
            $enable_context = get_option('cheshire_plugin_enable_context', 'off');

            // Append context information to the message if enabled
            if ($enable_context === 'on') {
                $context_info = $this->get_context_information();
                $message .= "\n\n" . $context_info;
            }

            // Check if reinforcement message is enabled
            $enable_reinforcement = get_option('cheshire_plugin_enable_reinforcement', 'off');

            // Append reinforcement message to the message if enabled
            // Per issue requirement: do NOT use reinforcement message in WYSIWYG editor or prompt tester
            if ($enable_reinforcement === 'on') {
                $reinforcement_message = get_option('cheshire_plugin_reinforcement_message', '');
                if (!empty($reinforcement_message)) {
                    $message .= "\n\n#IMPORTANT\n" . $reinforcement_message . "\n";
                }
            }
        }

        $payload = [
            'text' => $message,
        ];

        // Add user_id to headers instead of payload
        $headers = [
            'user_id' => $user_id,
        ];

        try {
            $response = $this->client->sendMessage( $payload, $headers );

            if ( is_null( $response ) ) {
                // Log the error if WP_DEBUG is enabled.
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Cheshire Cat API returned null response' );
                }
                return [];
            }

            return json_decode( $response->getBody()->getContents(), true );
        } catch ( \Exception $e ) {
            // Log the error if WP_DEBUG is enabled.
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Cheshire Cat API error: ' . $e->getMessage() );
            }
            return [];
        }
    }

    /**
     * Get the status of the Cheshire Cat API.
     *
     * @since 0.1
     * @return array The status information.
     */
    public function getStatus(): array {
        try {
            $response = $this->client->getStatus();

            if ( is_null( $response ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Cheshire Cat API status check returned null response' );
                }
                return [];
            }

            return json_decode( $response->getBody()->getContents(), true );
        } catch ( \Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Cheshire Cat API status check error: ' . $e->getMessage() );
            }
            return [];
        }
    }

    /**
     * Get available plugins from the Cheshire Cat API.
     *
     * @since 0.1
     * @return array The available plugins.
     */
    public function getAvailablePlugins(): array {
        try {
            $response = $this->client->getAvailablePlugins();

            if ( is_null( $response ) ) {
                return [];
            }

            return json_decode( $response->getBody()->getContents(), true );
        } catch ( \Exception $e ) {
            return [];
        }
    }

    /**
     * Get the HTTP client instance.
     *
     * @since 0.8.0
     * @return Custom_Cheshire_Cat_Client The HTTP client instance.
     */
    public function getClient() {
        return $this->client;
    }
}
