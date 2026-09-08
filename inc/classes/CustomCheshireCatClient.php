<?php
/**
 * Custom Cheshire Cat HTTP client
 *
 * @package CheshireCatChatbot
 */

namespace webgrafia\cheshirecat\inc\classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mock Guzzle Response to keep backward compatibility with existing code.
 */
class Custom_Cheshire_Cat_Response {
    private $status_code;
    private $body;

    public function __construct($status_code, $body) {
        $this->status_code = $status_code;
        $this->body = $body;
    }

    public function getStatusCode() {
        return $this->status_code;
    }

    public function getBody() {
        return $this;
    }

    public function getContents() {
        return $this->body;
    }
}

/**
 * Custom implementation of the Cheshire Cat HTTP client.
 *
 * This class provides custom functionality for the WordPress plugin.
 * It supports both Cheshire Cat v1 (Bearer token) and v2 (API Key via x-api-key header) authentication.
 * It implements methods natively using wp_remote_* to completely remove the SDK dependency.
 *
 * @since 0.1
 */
class Custom_Cheshire_Cat_Client {
    protected $base_url;
    protected $token;
    protected $api_key;
    protected $cat_version;
    protected $timeout = 15;

    public function __construct( $base_url, $token, $cat_version = 'v1', $api_key = '' ) {
        $this->cat_version = $cat_version;
        $this->base_url    = $this->normalizeUrl( $base_url );
        $this->token       = $token;
        $this->api_key     = $api_key;
    }

    protected function normalizeUrl( $url ) {
        $normalized = rtrim($url, '/');
        // Aggiungiamo lo slash finale (richiesto da FastAPI) SOLO per la v2
        if ($this->cat_version === 'v2') {
            $normalized .= '/';
        }
        return $normalized;
    }

    public function getBaseUrl(): string {
        return $this->base_url;
    }

    public function getApiKey(): string {
        return $this->api_key;
    }

    public function getCatVersion(): string {
        return $this->cat_version;
    }

    public function getToken( array $credentials = [] ): string {
        if ( $this->cat_version === 'v2' ) {
            return $this->api_key;
        }
        return $this->token;
    }

    protected function getHeaders(array $additional_headers = []): array {
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], $additional_headers);

        if ( $this->cat_version === 'v2' && ! empty( $this->api_key ) ) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        } else {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return $headers;
    }

    private function handleRequest($method, $uri, $options = []) {
        $url = rtrim($this->getBaseUrl(), '/') . '/' . ltrim($uri, '/');
        
        $args = [
            'method'  => strtoupper($method),
            'timeout' => $this->timeout,
            'headers' => $this->getHeaders(isset($options['headers']) ? $options['headers'] : []),
        ];

        if (isset($options['json'])) {
            $args['body'] = wp_json_encode($options['json']);
            $args['data_format'] = 'body';
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Cheshire Cat API Error (' . $method . ' ' . $uri . '): ' . $response->get_error_message());
            }
            return null;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log("Cheshire Request: " . $method . " " . $url . " payload: " . wp_json_encode($args)); return new Custom_Cheshire_Cat_Response($status_code, $body);
    }

    public function getStatus() {
        return $this->handleRequest('GET', '/');
    }

    public function sendMessage(array $payload, array $headers = []) {
        if ($this->cat_version === 'v2') {
            $endpoint = 'agents/default/message';
            
            // Translate v1 payload to v2 schema
            $text = isset($payload['text']) ? $payload['text'] : '';
            $v2_payload = [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $text
                            ]
                        ]
                    ]
                ],
                'stream' => false
            ];
            
            $response = $this->handleRequest('POST', $endpoint, [
                'json' => $v2_payload,
                'headers' => $headers
            ]);
            
            if ($response && $response->getStatusCode() === 200) {
                $body = json_decode($response->getContents(), true);
                
                // Extract the assistant's response text from v2 structure
                $reply_text = '';
                if (isset($body['messages']) && is_array($body['messages'])) {
                    $last_msg = end($body['messages']);
                    if (isset($last_msg['text'])) {
                        $reply_text = $last_msg['text'];
                    }
                }
                
                // Reconstruct a v1-like response for the plugin
                $v1_body = [
                    'type' => 'chat',
                    'text' => $reply_text,
                ];
                
                return new Custom_Cheshire_Cat_Response(200, wp_json_encode($v1_body));
            }
            
            return $response;
        }

        // v1 behavior
        return $this->handleRequest('POST', 'message', [
            'json' => $payload,
            'headers' => $headers
        ]);
    }

    public function getAvailablePlugins() {
        return $this->handleRequest('GET', '/plugins/');
    }

    public function getLlmsSettings() {
        return $this->handleRequest('GET', '/llm/settings');
    }

    public function getPluginSettings(string $pluginId) {
        return $this->handleRequest('GET', "/plugins/settings/{$pluginId}");
    }

    public function deleteMemoryPointsByMetadata(string $collectionId, array $metadata = []) {
        return $this->handleRequest('DELETE', "/memory/collections/{$collectionId}/points", ['json' => $metadata]);
    }

    public function createMemoryPoint(string $collectionId, array $pointData) {
        return $this->handleRequest('POST', "/memory/collections/{$collectionId}/points", ['json' => $pointData]);
    }
}
