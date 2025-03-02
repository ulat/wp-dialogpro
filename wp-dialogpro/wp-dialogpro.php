<?php
/**
 * Plugin Name: DialogPro
 * Plugin URI: https://yoursite.com/wp-dialogpro
 * Description: WordPress Plugin for Chatbot Frontend Integration
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Text Domain: wp-dialogpro
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.3
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DIALOGPRO_VERSION', '1.0.0');
define('DIALOGPRO_PATH', plugin_dir_path(__FILE__));
define('DIALOGPRO_URL', plugin_dir_url(__FILE__));
define('DIALOGPRO_MIN_PHP_VERSION', '8.3');
define('DIALOGPRO_MIN_WP_VERSION', '6.4');

/**
 * Class autoloader function
 */
spl_autoload_register(function ($class) {
    // Only autoload classes with our prefix
    if (strpos($class, 'DialogPro') !== 0) {
        return;
    }

    $class_path = str_replace('DialogPro', '', $class);
    $class_path = strtolower(str_replace('_', '-', $class_path));
    $file = DIALOGPRO_PATH . 'includes/class-dialogpro' . $class_path . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Verify requirements before initializing
 */
function dialogpro_check_requirements(): bool {
    if (version_compare(PHP_VERSION, DIALOGPRO_MIN_PHP_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                sprintf(
                    __('DialogPro requires PHP version %s or higher.', 'wp-dialogpro'),
                    DIALOGPRO_MIN_PHP_VERSION
                ) . 
                '</p></div>';
        });
        return false;
    }

    if (version_compare($GLOBALS['wp_version'], DIALOGPRO_MIN_WP_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                sprintf(
                    __('DialogPro requires WordPress version %s or higher.', 'wp-dialogpro'),
                    DIALOGPRO_MIN_WP_VERSION
                ) . 
                '</p></div>';
        });
        return false;
    }

    return true;
}

/**
 * Initialize the plugin
 */
function run_dialogpro() {
    // Check requirements before initializing
    if (!dialogpro_check_requirements()) {
        return;
    }

    try {
        // Initialize service container
        $container = new DialogProContainer();

        // Register core services
        $container->register('settings', function($c) {
            return DialogProSettings::get_instance();
        });

        $container->register('api', function($c) {
            return new DialogProAPI($c->get('settings'));
        });

        $container->register('session', function($c) {
            return new DialogProSession();
        });

        // Initialize core plugin
        $plugin = new DialogProCore($container);
        $plugin->run();

    } catch (Exception $e) {
        // Log error and display admin notice
        error_log('DialogPro Plugin Error: ' . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            echo '<div class="error"><p>' . 
                __('DialogPro Plugin Error: ', 'wp-dialogpro') . 
                esc_html($e->getMessage()) . 
                '</p></div>';
        });
    }
}

// Hook into WordPress init
add_action('plugins_loaded', 'run_dialogpro');

// Register activation hook
register_activation_hook(__FILE__, function() {
    try {
        // Perform activation tasks
        require_once DIALOGPRO_PATH . 'includes/class-dialogpro-activator.php';
        DialogProActivator::activate();
    } catch (Exception $e) {
        error_log('DialogPro Activation Error: ' . $e->getMessage());
        wp_die(
            esc_html__('Error activating DialogPro plugin: ', 'wp-dialogpro') . 
            esc_html($e->getMessage())
        );
    }
});

// Register deactivation hook
register_deactivation_hook(__FILE__, function() {
    try {
        require_once DIALOGPRO_PATH . 'includes/class-dialogpro-deactivator.php';
        DialogProDeactivator::deactivate();
    } catch (Exception $e) {
        error_log('DialogPro Deactivation Error: ' . $e->getMessage());
    }
});