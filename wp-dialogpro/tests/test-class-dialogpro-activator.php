<?php
/**
 * Class DialogProActivatorTest
 *
 * @package DialogPro
 */

class DialogProActivatorTest extends WP_UnitTestCase {
    private static $test_db_prefix;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::$test_db_prefix = $GLOBALS['wpdb']->prefix . 'test_';
    }

    public function setUp(): void {
        parent::setUp();
        // Mock WordPress functions if needed
        if (!defined('DIALOGPRO_MIN_WP_VERSION')) {
            define('DIALOGPRO_MIN_WP_VERSION', '6.4');
        }
        if (!defined('DIALOGPRO_MIN_PHP_VERSION')) {
            define('DIALOGPRO_MIN_PHP_VERSION', '8.3');
        }
    }

    public function tearDown(): void {
        // Clean up after each test
        $this->clean_up_test_data();
        parent::tearDown();
    }

    /**
     * Test version checking methods
     */
    public function test_version_checks() {
        $reflection = new ReflectionClass('DialogProActivator');
        
        $check_wp_version = $reflection->getMethod('check_wp_version');
        $check_wp_version->setAccessible(true);
        
        $check_php_version = $reflection->getMethod('check_php_version');
        $check_php_version->setAccessible(true);

        // Test WordPress version check
        $this->assertTrue(
            $check_wp_version->invoke(null),
            'WordPress version check should pass with current version'
        );

        // Test PHP version check
        $this->assertTrue(
            $check_php_version->invoke(null),
            'PHP version check should pass with current version'
        );
    }

    /**
     * Test database table creation
     */
    public function test_create_tables() {
        global $wpdb;
        $reflection = new ReflectionClass('DialogProActivator');
        $create_tables = $reflection->getMethod('create_tables');
        $create_tables->setAccessible(true);

        // Execute table creation
        $create_tables->invoke(null);

        // Check if table exists
        $table_name = $wpdb->prefix . 'dialogpro_history';
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )
        );

        $this->assertEquals(
            $table_name,
            $table_exists,
            'History table should be created'
        );

        // Check table structure
        $columns = $wpdb->get_col("DESC {$table_name}");
        $expected_columns = [
            'id',
            'session_id',
            'message_type',
            'message',
            'timestamp',
            'token_count'
        ];

        foreach ($expected_columns as $column) {
            $this->assertContains(
                $column,
                $columns,
                "Column {$column} should exist in table"
            );
        }
    }

    /**
     * Test default options setup
     */
    public function test_set_default_options() {
        $reflection = new ReflectionClass('DialogProActivator');
        $set_default_options = $reflection->getMethod('set_default_options');
        $set_default_options->setAccessible(true);

        // Execute default options setup
        $set_default_options->invoke(null);

        // Check if options are set
        $expected_options = [
            'api_endpoint',
            'api_token',
            'chat_position',
            'chat_width',
            'primary_color',
            'font_family',
            'font_size',
            'token_limit',
            'logging_level',
            'welcome_message',
            'enabled_post_types'
        ];

        foreach ($expected_options as $option) {
            $this->assertNotFalse(
                get_option("dialogpro_{$option}"),
                "Option dialogpro_{$option} should be set"
            );
        }

        // Check specific values
        $this->assertEquals(
            'bottom-right',
            get_option('dialogpro_chat_position'),
            'Default chat position should be bottom-right'
        );

        $this->assertEquals(
            '8500',
            get_option('dialogpro_token_limit'),
            'Default token limit should be 8500'
        );
    }

    /**
     * Test directory creation
     */
    public function test_create_directories() {
        $reflection = new ReflectionClass('DialogProActivator');
        $create_directories = $reflection->getMethod('create_directories');
        $create_directories->setAccessible(true);

        // Execute directory creation
        $create_directories->invoke(null);

        // Check if directories exist
        $expected_dirs = [
            WP_CONTENT_DIR . '/logs',
            WP_CONTENT_DIR . '/cache/dialogpro'
        ];

        foreach ($expected_dirs as $dir) {
            $this->assertTrue(
                is_dir($dir),
                "Directory {$dir} should be created"
            );
        }
    }

    /**
     * Test task scheduling
     */
    public function test_schedule_tasks() {
        $reflection = new ReflectionClass('DialogProActivator');
        $schedule_tasks = $reflection->getMethod('schedule_tasks');
        $schedule_tasks->setAccessible(true);

        // Execute task scheduling
        $schedule_tasks->invoke(null);

        // Check if task is scheduled
        $this->assertTrue(
            wp_next_scheduled('dialogpro_daily_cleanup') !== false,
            'Cleanup task should be scheduled'
        );
    }

    /**
     * Test plugin activation
     */
    public function test_activate() {
        try {
            DialogProActivator::activate();
            $this->assertTrue(true, 'Plugin activation should complete without errors');
        } catch (Exception $e) {
            $this->fail('Plugin activation threw an exception: ' . $e->getMessage());
        }

        // Verify all components are properly set up
        $this->verify_plugin_activation();
    }

    /**
     * Test plugin deactivation
     */
    public function test_deactivate() {
        // Schedule a task first
        wp_schedule_event(time(), 'daily', 'dialogpro_daily_cleanup');
        
        // Create some cache files
        $cache_dir = WP_CONTENT_DIR . '/cache/dialogpro';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        file_put_contents($cache_dir . '/test.txt', 'test');

        // Execute deactivation
        DialogProActivator::deactivate();

        // Verify cleanup
        $this->assertFalse(
            wp_next_scheduled('dialogpro_daily_cleanup'),
            'Cleanup task should be unscheduled'
        );

        $this->assertEmpty(
            glob($cache_dir . '/*'),
            'Cache directory should be empty'
        );
    }

    /**
     * Helper method to verify plugin activation
     */
    private function verify_plugin_activation() {
        global $wpdb;
        
        // Check table existence
        $table_name = $wpdb->prefix . 'dialogpro_history';
        $this->assertTrue(
            $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name,
            'History table should exist'
        );

        // Check options
        $this->assertNotFalse(
            get_option('dialogpro_chat_position'),
            'Chat position option should exist'
        );

        // Check directories
        $this->assertTrue(
            is_dir(WP_CONTENT_DIR . '/cache/dialogpro'),
            'Cache directory should exist'
        );

        // Check scheduled tasks
        $this->assertNotFalse(
            wp_next_scheduled('dialogpro_daily_cleanup'),
            'Cleanup task should be scheduled'
        );
    }

    /**
     * Helper method to clean up test data
     */
    private function clean_up_test_data() {
        global $wpdb;
        
        // Remove test table
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}dialogpro_history");

        // Remove test options
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dialogpro_%'");

        // Remove test directories
        $dirs = [
            WP_CONTENT_DIR . '/logs',
            WP_CONTENT_DIR . '/cache/dialogpro'
        ];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $this->recursive_rmdir($dir);
            }
        }

        // Clear scheduled tasks
        wp_clear_scheduled_hook('dialogpro_daily_cleanup');
    }

    /**
     * Helper method to recursively remove directories
     */
    private function recursive_rmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursive_rmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}