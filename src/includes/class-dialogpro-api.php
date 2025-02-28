<?php
/**
 * API Handler Class
 * 
 * Manages all communication with the backend API
 * @package DialogPro
 */

class DialogProAPI {
    private $settings;
    private $cache;
    private const CACHE_TTL = 3600; // 1 hour
    private const TIMEOUT = 15; // seconds

    /**
     * Constructor
     */
    public function __construct(DialogProSettings $settings) {
        $this->settings = $settings;
        $this->initialize_cache();
    }

    /**
     * Initialize cache
     */
    private function initialize_cache(): void {
        $this->cache = wp_cache_get_multiple(['dialogpro_api']);
    }

    /**
     * Send message to API
     * 
     * @param string $message User message
     * @param string $session_id Session identifier
     * @return array Response data
     * @throws Exception On API error
     */
    public function send_message(string $message, string $session_id): array {
        try {
            // Validate input
            if (empty($message)) {
                throw new Exception(__('Message cannot be empty', 'wp-dialogpro'));
            }

            // Check cache
            $cache_key = md5($message . $session_id);
            $cached_response = wp_cache_get($cache_key, 'dialogpro_api');
            if (false !== $cached_response) {
                return $cached_response;
            }

            // Prepare request
            $endpoint = $this->settings->get_option('api_endpoint');
            $token = $this->settings->get_option('api_token');

            if (empty($endpoint) || empty($token)) {
                throw new Exception(__('API configuration is incomplete', 'wp-dialogpro'));
            }

            // Make API request
            $response = wp_remote_post($endpoint, [
                'timeout' => self::TIMEOUT,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'X-Session-ID' => $session_id
                ],
                'body' => json_encode([
                    'message' => $message,
                    'timestamp' => time()
                ])
            ]);

            // Handle response
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                throw new Exception(
                    sprintf(
                        __('API returned error code: %d', 'wp-dialogpro'),
                        $status_code
                    )
                );
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new Exception(__('Invalid JSON response from API', 'wp-dialogpro'));
            }

            // Cache successful response
            wp_cache_set($cache_key, $body, 'dialogpro_api', self::CACHE_TTL);

            return $body;

        } catch (Exception $e) {
            // Log error
            error_log('DialogPro API Error: ' . $e->getMessage());
            
            // Rethrow with user-friendly message
            throw new Exception(
                __('Error communicating with chat service. Please try again later.', 'wp-dialogpro')
            );
        }
    }

    /**
     * Check API connection
     * 
     * @return bool Connection status
     */
    public function check_connection(): bool {
        try {
            $endpoint = $this->settings->get_option('api_endpoint');
            $token = $this->settings->get_option('api_token');

            $response = wp_remote_get($endpoint . '/status', [
                'timeout' => 5,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            return !is_wp_error($response) && 
                   wp_remote_retrieve_response_code($response) === 200;

        } catch (Exception $e) {
            error_log('DialogPro API Connection Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear API cache
     */
    public function clear_cache(): void {
        wp_cache_delete('dialogpro_api');
    }
}