<?php
/**
 * DialogPro Uninstaller
 *
 * This file runs when the plugin is uninstalled via the WordPress admin.
 * It ensures complete cleanup of all plugin data.
 *
 * @package DialogPro
 * @version 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define constants for cleanup
define('DIALOGPRO_DELETE_LIMIT', 100);
define('DIALOGPRO_TABLE_PREFIX', 'dialogpro_');

/**
 * Main uninstall class
 */
class DialogPro_Uninstaller {
    /**
     * Run the uninstaller
     */
    public static function run() {
        try {
            // Get global wpdb object
            global $wpdb;

            // Start transaction
            $wpdb->query('START TRANSACTION');

            try {
                // Remove plugin options
                self::remove_options();

                // Remove plugin tables
                self::remove_tables();

                // Remove plugin user meta
                self::remove_user_meta();

                // Remove plugin files and directories
                self::remove_files();

                // Remove transients
                self::remove_transients();

                // Remove scheduled tasks
                self::remove_scheduled_tasks();

                // Clear any remaining cache
                self::clear_cache();

                // Commit transaction
                $wpdb->query('COMMIT');

                // Log successful uninstall
                error_log('DialogPro: Plugin successfully uninstalled');

            } catch (Exception $e) {
                // Rollback on error
                $wpdb->query('ROLLBACK');
                error_log('DialogPro Uninstall Error: ' . $e->getMessage());
                throw $e;
            }

        } catch (Exception $e) {
            error_log('DialogPro Uninstall Fatal Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove plugin options
     */
    private static function remove_options() {
        global $wpdb;

        // Delete all options with dialogpro prefix
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                'dialogpro_%'
            )
        );

        // Delete specific known options
        $options = [
            'dialogpro_settings',
            'dialogpro_api_token',
            'dialogpro_version',
            'dialogpro_installed_time'
        ];

        foreach ($options as $option) {
            delete_option($option);
            delete_site_option($option);
        }
    }

    /**
     * Remove plugin tables
     */
    private static function remove_tables() {
        global $wpdb;

        // List of plugin tables
        $tables = [
            'dialogpro_chat_history',
            'dialogpro_sessions',
            'dialogpro_tokens'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
    }

    /**
     * Remove plugin user meta
     */
    private static function remove_user_meta() {
        global $wpdb;

        // Delete user meta with dialogpro prefix
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
                'dialogpro_%'
            )
        );
    }

    /**
     * Remove plugin files and directories
     */
    private static function remove_files() {
        // Get WordPress upload directory
        $upload_dir = wp_upload_dir();

        // Directories to remove
        $directories = [
            $upload_dir['basedir'] . '/dialogpro',
            WP_CONTENT_DIR . '/cache/dialogpro',
            WP_CONTENT_DIR . '/logs/dialogpro'
        ];

        foreach ($directories as $directory) {
            if (is_dir($directory)) {
                self::remove_directory($directory);
            }
        }
    }

    /**
     * Recursively remove a directory
     */
    private static function remove_directory($directory) {
        if (!is_dir($directory)) {
            return;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory . '/' . $file;
            if (is_dir($path)) {
                self::remove_directory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }

    /**
     * Remove transients
     */
    private static function remove_transients() {
        global $wpdb;

        // Delete transients and their timeouts
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_dialogpro_%',
                '_transient_timeout_dialogpro_%'
            )
        );

        // Delete network transients if in multisite
        if (is_multisite()) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                    '_site_transient_dialogpro_%',
                    '_site_transient_timeout_dialogpro_%'
                )
            );
        }
    }

    /**
     * Remove scheduled tasks
     */
    private static function remove_scheduled_tasks() {
        // Clear all scheduled hooks
        wp_clear_scheduled_hook('dialogpro_daily_cleanup');
        wp_clear_scheduled_hook('dialogpro_session_cleanup');
        wp_clear_scheduled_hook('dialogpro_token_reset');

        // Clear any other scheduled tasks
        $tasks = _get_cron_array();
        if (is_array($tasks)) {
            foreach ($tasks as $timestamp => $cron) {
                foreach ($cron as $hook => $task) {
                    if (strpos($hook, 'dialogpro_') === 0) {
                        wp_unschedule_event($timestamp, $hook);
                    }
                }
            }
        }
    }

    /**
     * Clear cache
     */
    private static function clear_cache() {
        // Clear WordPress cache
        wp_cache_flush();

        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // Clear any page cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all(); // W3 Total Cache
        }
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache(); // WP Super Cache
        }
        if (class_exists('WpeCommon')) {
            WpeCommon::purge_memcached(); // WP Engine
            WpeCommon::clear_all_caches();
        }

        // Delete plugin cache directory
        $cache_dir = WP_CONTENT_DIR . '/cache/dialogpro';
        if (is_dir($cache_dir)) {
            self::remove_directory($cache_dir);
        }
    }

    /**
     * Verify uninstall is safe
     */
    private static function verify_uninstall(): bool {
        // Check if it's a multisite uninstall
        if (is_multisite() && !is_network_admin()) {
            return false;
        }

        // Verify user has proper permissions
        if (!current_user_can('activate_plugins')) {
            return false;
        }

        return true;
    }
}

// Run uninstaller if verification passes
if (DialogPro_Uninstaller::verify_uninstall()) {
    DialogPro_Uninstaller::run();
}