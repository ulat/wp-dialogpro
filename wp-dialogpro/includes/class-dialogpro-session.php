<?php
/**
 * Session Manager Class
 * 
 * Handles chat sessions and token management
 * @package DialogPro
 */

class DialogProSession {
    private $session_id;
    private $token_count = 0;
    private const SESSION_COOKIE = 'dialogpro_session';
    private const TOKEN_COOKIE = 'dialogpro_tokens';
    private const SESSION_DURATION = 86400; // 24 hours

    /**
     * Initialize session
     */
    public function initialize_session(): void {
        try {
            // Check for existing session
            $this->session_id = $this->get_session_cookie();

            if (empty($this->session_id)) {
                // Create new session
                $this->create_new_session();
            } else {
                // Validate existing session
                $this->validate_session();
            }

            // Load token count
            $this->load_token_count();

        } catch (Exception $e) {
            error_log('DialogPro Session Error: ' . $e->getMessage());
            // Create new session on error
            $this->create_new_session();
        }
    }

    /**
     * Create new session
     */
    private function create_new_session(): void {
        $this->session_id = wp_generate_uuid4();
        $this->token_count = 0;

        $this->set_session_cookie();
        $this->set_token_cookie();
    }

    /**
     * Get session cookie
     */
    private function get_session_cookie(): ?string {
        return $_COOKIE[self::SESSION_COOKIE] ?? null;
    }

    /**
     * Set session cookie
     */
    private function set_session_cookie(): void {
        setcookie(
            self::SESSION_COOKIE,
            $this->session_id,
            [
                'expires' => time() + self::SESSION_DURATION,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    /**
     * Validate session
     */
    private function validate_session(): void {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $this->session_id)) {
            throw new Exception('Invalid session ID format');
        }
    }

    /**
     * Load token count
     */
    private function load_token_count(): void {
        $token_count = $_COOKIE[self::TOKEN_COOKIE] ?? '0';
        $this->token_count = (int)$token_count;
    }

    /**
     * Set token cookie
     */
    private function set_token_cookie(): void {
        setcookie(
            self::TOKEN_COOKIE,
            (string)$this->token_count,
            [
                'expires' => time() + self::SESSION_DURATION,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    /**
     * Update token count
     */
    public function update_token_count(int $tokens): bool {
        try {
            $new_count = $this->token_count + $tokens;
            $max_tokens = (int)$this->container->get('settings')->get_option('token_limit');

            if ($new_count > $max_tokens) {
                throw new Exception('Token limit exceeded');
            }

            $this->token_count = $new_count;
            $this->set_token_cookie();

            return true;

        } catch (Exception $e) {
            error_log('DialogPro Token Update Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current session ID
     */
    public function get_session_id(): string {
        return $this->session_id;
    }

    /**
     * Get current token count
     */
    public function get_token_count(): int {
        return $this->token_count;
    }

    /**
     * Clear session
     */
    public function clear_session(): void {
        setcookie(self::SESSION_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(self::TOKEN_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        $this->session_id = null;
        $this->token_count = 0;
    }

    /**
     * Check if session is valid
     */
    public function is_valid(): bool {
        return !empty($this->session_id) && 
               $this->token_count <= (int)$this->container->get('settings')->get_option('token_limit');
    }
}