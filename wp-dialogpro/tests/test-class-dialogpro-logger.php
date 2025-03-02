<?php
/**
 * Class DialogProLoggerTest
 *
 * @package DialogPro
 */

class DialogProLoggerTest extends WP_UnitTestCase {
    private $logger;
    private $settings_mock;
    private $test_log_dir;
    private $test_log_file;
    private $error_log_content = [];

    public function setUp(): void {
        parent::setUp();

        // Setup test log directory
        $this->test_log_dir = WP_CONTENT_DIR . '/logs/test';
        $this->test_log_file = $this->test_log_dir . '/dialogpro-debug.log';
        
        if (!is_dir($this->test_log_dir)) {
            mkdir($this->test_log_dir, 0777, true);
        }

        // Mock settings
        $this->settings_mock = $this->createMock(DialogProSettings::class);
        
        // Capture error_log calls
        set_error_handler(function($errno, $errstr) {
            $this->error_log_content[] = $errstr;
            return true;
        });

        // Initialize logger
        $this->logger = new DialogProLogger($this->settings_mock);
    }

    public function tearDown(): void {
        // Restore error handler
        restore_error_handler();

        // Clean up test files
        if (is_dir($this->test_log_dir)) {
            $this->recursive_rmdir($this->test_log_dir);
        }

        parent::tearDown();
    }

    /**
     * Test constructor and log level initialization
     */
    public function test_constructor_initialization() {
        $this->settings_mock->method('get_option')
            ->willReturnMap([
                ['logging_level', 'error', 'debug'],
                ['log_to_file', false, false]
            ]);

        $logger = new DialogProLogger($this->settings_mock);
        
        $reflection = new ReflectionClass($logger);
        $log_level_property = $reflection->getProperty('log_level');
        $log_level_property->setAccessible(true);

        $this->assertEquals(4, $log_level_property->getValue($logger));
    }

    /**
     * Test log level filtering
     */
    public function test_should_log() {
        $this->settings_mock->method('get_option')
            ->willReturnMap([
                ['logging_level', 'error', 'warning'],
                ['log_to_file', false, false]
            ]);

        $logger = new DialogProLogger($this->settings_mock);
        
        $reflection = new ReflectionClass($logger);
        $method = $reflection->getMethod('should_log');
        $method->setAccessible(true);

        // Test log levels
        $this->assertTrue($method->invoke($logger, 'error'));
        $this->assertTrue($method->invoke($logger, 'warning'));
        $this->assertFalse($method->invoke($logger, 'info'));
        $this->assertFalse($method->invoke($logger, 'debug'));
    }

    /**
     * Test log entry formatting
     */
    public function test_format_log_entry() {
        $reflection = new ReflectionClass($this->logger);
        $method = $reflection->getMethod('format_log_entry');
        $method->setAccessible(true);

        $context = ['key' => 'value'];
        $entry = $method->invoke($this->logger, 'error', 'Test message', $context);

        // Verify format
        $this->assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] ERROR: Test message {"key":"value"}$/',
            $entry
        );

        // Test without context
        $entry = $method->invoke($this->logger, 'info', 'Simple message', []);
        $this->assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] INFO: Simple message$/',
            $entry
        );
    }

    /**
     * Test file logging
     */
    public function test_write_to_file() {
        $this->settings_mock->method('get_option')
            ->willReturnMap([
                ['logging_level', 'error', 'debug'],
                ['log_to_file', false, true]
            ]);

        $logger = new DialogProLogger($this->settings_mock);
        
        $reflection = new ReflectionClass($logger);
        $method = $reflection->getMethod('write_to_file');
        $method->setAccessible(true);

        $test_entry = "Test log entry";
        $method->invoke($logger, $test_entry);

        $log_file = WP_CONTENT_DIR . '/logs/dialogpro-debug.log';
        $this->assertFileExists($log_file);
        $this->assertStringContainsString($test_entry, file_get_contents($log_file));
    }

    /**
     * Test log file rotation
     */
    public function test_rotate_log_file() {
        // Create oversized log file
        $log_file = $this->test_log_file;
        $large_content = str_repeat('x', 6 * MB_IN_BYTES);
        file_put_contents($log_file, $large_content);

        $reflection = new ReflectionClass($this->logger);
        $method = $reflection->getMethod('rotate_log_file');
        $method->setAccessible(true);

        $method->invoke($this->logger, $log_file);

        // Verify rotation
        $this->assertFileExists($log_file . '.' . date('Y-m-d'));
        $this->assertFileDoesNotExist($log_file);
    }

    /**
     * Test convenience logging methods
     */
    public function test_convenience_methods() {
        $this->settings_mock->method('get_option')
            ->willReturnMap([
                ['logging_level', 'error', 'debug'],
                ['log_to_file', false, false]
            ]);

        $methods = ['error', 'warning', 'info', 'debug'];
        
        foreach ($methods as $method) {
            $this->error_log_content = []; // Reset captured logs
            $this->logger->$method('Test ' . $method);
            
            $this->assertCount(1, $this->error_log_content);
            $this->assertStringContainsString(
                strtoupper($method),
                $this->error_log_content[0]
            );
        }
    }

    /**
     * Test error handling during file operations
     */
    public function test_file_operation_error_handling() {
        $this->settings_mock->method('get_option')
            ->willReturnMap([
                ['logging_level', 'error', 'debug'],
                ['log_to_file', false, true]
            ]);

        // Make logs directory non-writable
        $log_dir = WP_CONTENT_DIR . '/logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir);
        }
        chmod($log_dir, 0444);

        $this->error_log_content = []; // Reset captured logs
        $this->logger->error('Test message');

        // Verify error was logged
        $this->assertNotEmpty($this->error_log_content);
        $this->assertStringContainsString(
            'DialogPro Logger Error:',
            $this->error_log_content[0]
        );

        // Restore permissions
        chmod($log_dir, 0777);
    }

    /**
     * Test log backup file cleanup
     */
    public function test_backup_file_cleanup() {
        $log_file = $this->test_log_file;
        
        // Create test backup files
        for ($i = 1; $i <= 7; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            file_put_contents("$log_file.$date", "backup $i");
        }

        $reflection = new ReflectionClass($this->logger);
        $method = $reflection->getMethod('rotate_log_file');
        $method->setAccessible(true);

        $method->invoke($this->logger, $log_file);

        // Verify only 5 newest backups remain
        $backup_files = glob($log_file . '.*');
        $this->assertCount(5, $backup_files);
    }

    /**
     * Helper method to recursively remove directory
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


// This test suite includes:

// 1. Setup and Teardown:
// - Test directory creation
// - Error handler setup
// - Cleanup procedures

// 2. Core Functionality Tests:
// - Constructor initialization
// - Log level filtering
// - Entry formatting
// - File writing
// - Log rotation

// 3. Convenience Method Tests:
// - Error logging
// - Warning logging
// - Info logging
// - Debug logging

// 4. Error Handling Tests:
// - File operation errors
// - Permission issues
// - Directory creation

// 5. File Management Tests:
// - Log rotation
// - Backup file cleanup
// - Size limits
// - File permissions

// Key features tested:
// 1. Log Level Management
// 2. Message Formatting
// 3. File Operations
// 4. Error Handling
// 5. Backup Management
// 6. Permission Handling
// 7. Convenience Methods