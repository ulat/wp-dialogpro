```php includes/class-dialogpro-activator.php
<?php
/**
 * Activator Class
 * 
 * Handles plugin activation and deactivation
 * @package DialogPro
 */

class DialogProActivator {
    /**
     * Activate the plugin
     */
    public static function activate(): void {
        try {
            // Check WordPress version
            if (!self::check_wp_version()) {
                throw new Exception(
                    sprintf(
                        __('WordPress %s or higher is required', 'wp-dialogpro'),
                        DIALOGPRO_MIN_WP_VERSION
                    )
                );
            }

            // Check PHP version
            if (!self::check_php_version()) {
                throw new Exception(
                    sprintf(
                        __('PHP %s or higher is required', 'wp-dialogpro'),
                        DIALOGPRO_MIN_PHP_VERSION
                    )
                );
            }

            // Create necessary database tables
            self::create_tables();

            // Set default options
            self::set_default_options();

            // Create necessary directories
            self::create_directories();

            // Schedule cleanup tasks
            self::schedule_tasks();

            // Flush rewrite rules
            flush_rewrite_rules();

        } catch (Exception $e) {
            error_log('DialogPro Activation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check WordPress version
     */
    private static function check_wp_version(): bool {
        return version_compare(
            get_bloginfo('version'),
            DIALOGPRO_MIN_WP_VERSION,
            '>='
        );
    }

    /**
     * Check PHP version
     */
    private static function check_php_version(): bool {
        return version_compare(
            PHP_VERSION,
            DIALOGPRO_MIN_PHP_VERSION,
            '>='
        );
    }

    /**
     * Create necessary database tables
     */
    private static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Chat history table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dialogpro_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(36) NOT NULL,
            message_type varchar(10) NOT NULL,
            message text NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            token_count int(11) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Set default options
     */
    private static function set_default_options(): void {
        $default_options = [
            'api_endpoint' => '',
            'api_token' => '',
            'chat_position' => 'bottom-right',
            'chat_width' => '20',
            'primary_color' => '#007bff',
            'font_family' => 'Arial, sans-serif',
            'font_size' => '14',
            'token_limit' => '8500',
            'logging_level' => 'error',
            'welcome_message' => __('Hello! How can I help you today?', 'wp-dialogpro'),
            'enabled_post_types' => ['post', 'page']
        ];

        foreach ($default_options as $key => $value) {
            if (false === get_option("dialogpro_$key")) {
                add_option("dialogpro_$key", $value);
            }
        }
    }

    /**
     * Create necessary directories
     */
    private static function create_directories(): void {
        $dirs = [
            WP_CONTENT_DIR . '/logs',
            WP_CONTENT_DIR . '/cache/dialogpro'
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }

    /**
     * Schedule cleanup tasks
     */
    private static function schedule_tasks(): void {
        if (!wp_next_scheduled('dialogpro_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'dialogpro_daily_cleanup');
        }
    }

    /**
     * Deactivate the plugin
     */
    public static function deactivate(): void {
        try {
            // Clear scheduled tasks
            wp_clear_scheduled_hook('dialogpro_daily_cleanup');

            // Clear cache
            self::clear_cache();

            // Flush rewrite rules
            flush_rewrite_rules();

        } catch (Exception $e) {
            error_log('DialogPro Deactivation Error: ' . $e->getMessage());
        }
    }

    /**
     * Clear plugin cache
     */
    private static function clear_cache(): void {
        $cache_dir = WP_CONTENT_DIR . '/cache/dialogpro';
        if (is_dir($cache_dir)) {
            array_map('unlink', glob("$cache_dir/*.*"));
        }
    }
}