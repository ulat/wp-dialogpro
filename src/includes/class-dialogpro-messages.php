<?php
/**
 * Message Handler Class
 * 
 * Manages message processing and routing
 * @package DialogPro
 */

class DialogProMessages {
    private $container;
    private $api;
    private $session;
    private const MAX_MESSAGE_LENGTH = 1000;

    /**
     * Constructor
     */
    public function __construct(DialogProContainer $container) {
        $this->container = $container;
        $this->api = $container->get('api');
        $this->session = $container->get('session');
    }

    /**
     * Handle incoming message
     * 
     * @param string $message User message
     * @return array Response data
     * @throws Exception On processing error
     */
    public function handle_message(string $message): array {
        try {
            // Validate message
            $this->validate_message($message);

            // Check session validity
            if (!$this->session->is_valid()) {
                throw new Exception(__('Invalid session', 'wp-dialogpro'));
            }

            // Process message
            $processed_message = $this->process_message($message);

            // Send to API and get response
            $response = $this->api->send_message(
                $processed_message,
                $this->session->get_session_id()
            );

            // Update token count
            $token_count = $this->calculate_tokens($message . ($response['response'] ?? ''));
            if (!$this->session->update_token_count($token_count)) {
                throw new Exception(__('Token limit exceeded', 'wp-dialogpro'));
            }

            // Format response
            return $this->format_response($response);

        } catch (Exception $e) {
            error_log('DialogPro Message Handler Error: ' . $e->getMessage());
            throw new Exception(
                __('Failed to process message: ', 'wp-dialogpro') . $e->getMessage()
            );
        }
    }

    /**
     * Validate message
     */
    private function validate_message(string $message): void {
        if (empty(trim($message))) {
            throw new Exception(__('Message cannot be empty', 'wp-dialogpro'));
        }

        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            throw new Exception(
                sprintf(
                    __('Message exceeds maximum length of %d characters', 'wp-dialogpro'),
                    self::MAX_MESSAGE_LENGTH
                )
            );
        }

        // Check for potential XSS
        if ($this->contains_xss($message)) {
            throw new Exception(__('Invalid message content', 'wp-dialogpro'));
        }
    }

    /**
     * Process message before sending
     */
    private function process_message(string $message): string {
        // Sanitize input
        $message = sanitize_text_field($message);
        
        // Convert emoticons to text
        $message = $this->convert_emoticons($message);
        
        // Apply filters
        $message = apply_filters('dialogpro_process_message', $message);
        
        return $message;
    }

    /**
     * Check for potential XSS content
     */
    private function contains_xss(string $message): bool {
        $patterns = [
            '/<[^>]*>/',            // HTML tags
            '/javascript:/i',       // JavaScript protocol
            '/on\w+\s*=/i',        // Event handlers
            '/data:\s*\w+/i'       // Data URIs
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert emoticons to text
     */
    private function convert_emoticons(string $message): string {
        $emoticons = [
            ':)' => 'smile',
            ':(' => 'sad',
            ';)' => 'wink',
            ':D' => 'grin'
        ];

        return str_replace(
            array_keys($emoticons),
            array_values($emoticons),
            $message
        );
    }

    /**
     * Calculate approximate token count
     */
    private function calculate_tokens(string $text): int {
        // Rough estimation: 1 token ≈ 4 characters
        return (int)ceil(mb_strlen($text) / 4);
    }

    /**
     * Format API response
     */
    private function format_response(array $response): array {
        return [
            'message' => wp_kses_post($response['response'] ?? ''),
            'timestamp' => current_time('timestamp'),
            'token_count' => $this->session->get_token_count(),
            'session_valid' => $this->session->is_valid()
        ];
    }

    /**
     * Get message history
     */
    public function get_message_history(): array {
        try {
            return get_transient(
                'dialogpro_history_' . $this->session->get_session_id()
            ) ?: [];
        } catch (Exception $e) {
            error_log('DialogPro History Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Save message to history
     */
    public function save_to_history(array $message_data): void {
        try {
            $history = $this->get_message_history();
            $history[] = $message_data;

            // Keep only last 50 messages
            if (count($history) > 50) {
                array_shift($history);
            }

            set_transient(
                'dialogpro_history_' . $this->session->get_session_id(),
                $history,
                DAY_IN_SECONDS
            );
        } catch (Exception $e) {
            error_log('DialogPro History Save Error: ' . $e->getMessage());
        }
    }
}