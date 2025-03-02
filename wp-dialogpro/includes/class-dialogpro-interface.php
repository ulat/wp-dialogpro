<?php
/**
 * Chat Interface Manager Class
 * 
 * Handles the frontend chat interface rendering and interactions
 * @package DialogPro
 */

class DialogProInterface {
    private $container;
    private $settings;
    private const SCRIPT_VERSION = '1.0.0';

    /**
     * Constructor
     */
    public function __construct(DialogProContainer $container) {
        $this->container = $container;
        $this->settings = $container->get('settings');
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets(): void {
        try {
            // Register and enqueue styles
            wp_register_style(
                'dialogpro-styles',
                DIALOGPRO_URL . 'assets/css/dialogpro.css',
                [],
                self::SCRIPT_VERSION
            );
            wp_enqueue_style('dialogpro-styles');

            // Register and enqueue scripts
            wp_register_script(
                'dialogpro-scripts',
                DIALOGPRO_URL . 'assets/js/dialogpro.js',
                ['jquery'],
                self::SCRIPT_VERSION,
                true
            );

            // Localize script with necessary data
            wp_localize_script(
                'dialogpro-scripts',
                'dialogProData',
                $this->get_localized_data()
            );

            wp_enqueue_script('dialogpro-scripts');

            // Enqueue Material Icons if enabled
            if ($this->settings->get_option('use_material_icons')) {
                wp_enqueue_style(
                    'material-icons',
                    'https://fonts.googleapis.com/icon?family=Material+Icons'
                );
            }

        } catch (Exception $e) {
            error_log('DialogPro Asset Loading Error: ' . $e->getMessage());
        }
    }

    /**
     * Get localized data for JavaScript
     */
    private function get_localized_data(): array {
        return [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dialogpro_message_nonce'),
            'settings' => [
                'position' => $this->settings->get_option('chat_position'),
                'width' => $this->settings->get_option('chat_width'),
                'welcomeMessage' => $this->settings->get_option('welcome_message'),
                'tokenLimit' => (int)$this->settings->get_option('token_limit'),
            ],
            'i18n' => [
                'sending' => __('Sending...', 'wp-dialogpro'),
                'error' => __('Error sending message', 'wp-dialogpro'),
                'tokenLimitReached' => __('Token limit reached', 'wp-dialogpro'),
            ]
        ];
    }

    /**
     * Render chat window
     */
    public function render_chat_window(): void {
        try {
            // Check if chat should be displayed
            if (!$this->should_display_chat()) {
                return;
            }

            // Get template
            $template_path = $this->get_template_path('chat-window.php');
            if (!file_exists($template_path)) {
                throw new Exception('Chat window template not found');
            }

            // Start output buffering
            ob_start();
            
            // Include template
            include $template_path;

            // Get and clean buffer
            $output = ob_get_clean();

            // Add accessibility attributes
            $output = $this->add_accessibility_attributes($output);

            // Echo the final HTML
            echo $output;

        } catch (Exception $e) {
            error_log('DialogPro Render Error: ' . $e->getMessage());
            
            if (current_user_can('manage_options')) {
                echo '<!-- DialogPro Error: ' . esc_html($e->getMessage()) . ' -->';
            }
        }
    }

    /**
     * Check if chat should be displayed
     */
    private function should_display_chat(): bool {
        // Don't show in admin
        if (is_admin()) {
            return false;
        }

        // Check if enabled for current page type
        $current_post_type = get_post_type();
        $enabled_post_types = $this->settings->get_option('enabled_post_types', ['post', 'page']);

        return in_array($current_post_type, $enabled_post_types);
    }

    /**
     * Get template path
     */
    private function get_template_path(string $template): string {
        // Check theme directory first
        $theme_path = get_stylesheet_directory() . '/dialogpro/' . $template;
        if (file_exists($theme_path)) {
            return $theme_path;
        }

        // Fall back to plugin directory
        return DIALOGPRO_PATH . 'templates/' . $template;
    }

    /**
     * Add accessibility attributes to chat window
     */
    private function add_accessibility_attributes(string $html): string {
        $accessibility_attrs = [
            'role="complementary"',
            'aria-label="' . esc_attr__('Chat Window', 'wp-dialogpro') . '"',
            'aria-live="polite"'
        ];

        return str_replace(
            '<div class="dialogpro-chat-window"',
            '<div class="dialogpro-chat-window" ' . implode(' ', $accessibility_attrs),
            $html
        );
    }
}
