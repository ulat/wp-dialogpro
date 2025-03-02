<?php
/**
 * Styles Manager Class
 * 
 * Handles custom styling and theme integration
 * @package DialogPro
 */

class DialogProStyles {
    private $settings;
    private const CACHE_KEY = 'dialogpro_custom_css';
    private const CACHE_TIME = 3600; // 1 hour

    /**
     * Constructor
     */
    public function __construct(DialogProSettings $settings) {
        $this->settings = $settings;
    }

    /**
     * Output custom CSS
     */
    public function output_custom_css(): void {
        try {
            $css = $this->get_cached_css();

            if (false === $css) {
                $css = $this->generate_custom_css();
                $this->cache_css($css);
            }

            if (!empty($css)) {
                echo "\n<!-- DialogPro Custom Styles -->\n<style type='text/css'>\n";
                echo esc_html($css);
                echo "\n</style>\n";
            }

        } catch (Exception $e) {
            error_log('DialogPro Styles Error: ' . $e->getMessage());
        }
    }

    /**
     * Generate custom CSS
     */
    private function generate_custom_css(): string {
        $css_parts = [];

        // Chat window styles
        $css_parts[] = $this->generate_chat_window_css();

        // Message styles
        $css_parts[] = $this->generate_message_css();

        // Button styles
        $css_parts[] = $this->generate_button_css();

        // Animation styles
        $css_parts[] = $this->generate_animation_css();

        // Media queries
        $css_parts[] = $this->generate_responsive_css();

        // Theme integration
        $css_parts[] = $this->generate_theme_integration_css();

        return implode("\n", array_filter($css_parts));
    }

    /**
     * Generate chat window CSS
     */
    private function generate_chat_window_css(): string {
        $position = $this->settings->get_option('chat_position', 'bottom-right');
        $width = $this->settings->get_option('chat_width', '20');
        $background = $this->settings->get_option('chat_bg_color', '#ffffff');

        $position_css = $this->get_position_css($position);

        return sprintf(
            '.dialogpro-chat-window {
                position: fixed;
                %s
                width: %d%%;
                max-width: 400px;
                background-color: %s;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                z-index: 999999;
                transition: all 0.3s ease;
            }',
            $position_css,
            (int)$width,
            esc_attr($background)
        );
    }

    /**
     * Generate message CSS
     */
    private function generate_message_css(): string {
        $font_family = $this->settings->get_option('font_family', 'inherit');
        $font_size = $this->settings->get_option('font_size', '14');
        $primary_color = $this->settings->get_option('primary_color', '#007bff');

        return sprintf(
            '.dialogpro-message {
                font-family: %s;
                font-size: %dpx;
                line-height: 1.5;
                margin: 8px 0;
                padding: 8px 12px;
                border-radius: 4px;
            }
            .dialogpro-message-user {
                background-color: %s;
                color: #ffffff;
                align-self: flex-end;
            }
            .dialogpro-message-bot {
                background-color: #f1f1f1;
                color: #333333;
                align-self: flex-start;
            }',
            esc_attr($font_family),
            (int)$font_size,
            esc_attr($primary_color)
        );
    }

    /**
     * Generate button CSS
     */
    private function generate_button_css(): string {
        $primary_color = $this->settings->get_option('primary_color', '#007bff');
        
        return sprintf(
            '.dialogpro-button {
                background-color: %1$s;
                color: #ffffff;
                border: none;
                border-radius: 50%%;
                width: 50px;
                height: 50px;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .dialogpro-button:hover {
                background-color: %2$s;
                transform: scale(1.1);
            }',
            esc_attr($primary_color),
            $this->adjust_brightness($primary_color, -20)
        );
    }

    /**
     * Generate animation CSS
     */
    private function generate_animation_css(): string {
        return '
            @keyframes dialogproFadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .dialogpro-animate {
                animation: dialogproFadeIn 0.3s ease forwards;
            }
        ';
    }

    /**
     * Generate responsive CSS
     */
    private function generate_responsive_css(): string {
        return '
            @media screen and (max-width: 768px) {
                .dialogpro-chat-window {
                    width: 90% !important;
                    max-width: none !important;
                    margin: 10px;
                }
            }
        ';
    }

    /**
     * Get position CSS
     */
    private function get_position_css(string $position): string {
        $positions = [
            'bottom-right' => 'bottom: 20px; right: 20px;',
            'bottom-left' => 'bottom: 20px; left: 20px;',
            'top-right' => 'top: 20px; right: 20px;',
            'top-left' => 'top: 20px; left: 20px;'
        ];

        return $positions[$position] ?? $positions['bottom-right'];
    }

    /**
     * Adjust color brightness
     */
    private function adjust_brightness(string $hex, int $steps): string {
        $hex = ltrim($hex, '#');
        
        $r = max(min(hexdec(substr($hex, 0, 2)) + $steps, 255), 0);
        $g = max(min(hexdec(substr($hex, 2, 2)) + $steps, 255), 0);
        $b = max(min(hexdec(substr($hex, 4, 2)) + $steps, 255), 0);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Get cached CSS
     */
    private function get_cached_css() {
        return get_transient(self::CACHE_KEY);
    }

    /**
     * Cache CSS
     */
    private function cache_css(string $css): void {
        set_transient(self::CACHE_KEY, $css, self::CACHE_TIME);
    }

    /**
     * Clear CSS cache
     */
    public function clear_cache(): void {
        delete_transient(self::CACHE_KEY);
    }
}