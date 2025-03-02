<?php
/**
 * Internationalization Handler Class
 * 
 * Manages translations and localization
 * @package DialogPro
 */

class DialogProI18n {
    private const TEXT_DOMAIN = 'wp-dialogpro';
    private const LANGUAGES_PATH = 'languages';

    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain(): void {
        try {
            load_plugin_textdomain(
                self::TEXT_DOMAIN,
                false,
                dirname(plugin_basename(DIALOGPRO_PATH)) . '/' . self::LANGUAGES_PATH
            );
        } catch (Exception $e) {
            error_log('DialogPro I18n Error: ' . $e->getMessage());
        }
    }

    /**
     * Register translation strings
     */
    public function register_strings(): void {
        // Common strings
        __('Send Message', self::TEXT_DOMAIN);
        __('Type your message...', self::TEXT_DOMAIN);
        __('Connecting...', self::TEXT_DOMAIN);
        __('Connected', self::TEXT_DOMAIN);
        __('Disconnected', self::TEXT_DOMAIN);
        __('Error', self::TEXT_DOMAIN);
        __('Close', self::TEXT_DOMAIN);
        __('Settings', self::TEXT_DOMAIN);
        __('Chat Window', self::TEXT_DOMAIN);

        // Error messages
        __('Failed to send message', self::TEXT_DOMAIN);
        __('Connection lost', self::TEXT_DOMAIN);
        __('Token limit reached', self::TEXT_DOMAIN);
        __('Invalid response from server', self::TEXT_DOMAIN);
        __('Session expired', self::TEXT_DOMAIN);

        // Settings strings
        __('General Settings', self::TEXT_DOMAIN);
        __('API Settings', self::TEXT_DOMAIN);
        __('Appearance Settings', self::TEXT_DOMAIN);
        __('Advanced Settings', self::TEXT_DOMAIN);
    }

    /**
     * Get available languages
     */
    public function get_available_languages(): array {
        try {
            $languages_dir = DIALOGPRO_PATH . self::LANGUAGES_PATH;
            $language_files = glob($languages_dir . '/*.mo');
            
            $languages = ['en_US' => 'English'];
            foreach ($language_files as $file) {
                $locale = basename($file, '.mo');
                $languages[$locale] = $this->get_language_name($locale);
            }

            return $languages;

        } catch (Exception $e) {
            error_log('DialogPro Language Detection Error: ' . $e->getMessage());
            return ['en_US' => 'English'];
        }
    }

    /**
     * Get language name from locale
     */
    private function get_language_name(string $locale): string {
        $languages = [
            'de_DE' => 'Deutsch',
            'en_US' => 'English',
            'fr_FR' => 'Français',
            'es_ES' => 'Español'
        ];

        return $languages[$locale] ?? $locale;
    }
}