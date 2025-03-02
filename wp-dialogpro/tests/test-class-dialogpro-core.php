<?php
/**
 * Class DialogProCoreTest
 *
 * @package DialogPro
 */

class DialogProCoreTest extends WP_UnitTestCase {
    private $container;
    private $core;
    private $settings_mock;
    private $interface_mock;
    private $session_mock;
    private $messages_mock;
    private $styles_mock;

    public function setUp(): void {
        parent::setUp();
        
        // Define plugin constants if not defined
        if (!defined('DIALOGPRO_PATH')) {
            define('DIALOGPRO_PATH', dirname(__DIR__) . '/');
        }

        // Create mock objects
        $this->settings_mock = $this->createMock(DialogProSettings::class);
        $this->interface_mock = $this->createMock(DialogProInterface::class);
        $this->session_mock = $this->createMock(DialogProSession::class);
        $this->messages_mock = $this->createMock(DialogProMessages::class);
        $this->styles_mock = $this->createMock(DialogProStyles::class);

        // Setup container with mocks
        $this->container = new DialogProContainer();
        $this->setup_container_mocks();

        // Initialize core with container
        $this->core = new DialogProCore($this->container);
    }

    /**
     * Setup container with mock services
     */
    private function setup_container_mocks(): void {
        $this->container->register('settings', function() {
            return $this->settings_mock;
        });

        $this->container->register('interface', function() {
            return $this->interface_mock;
        });

        $this->container->register('session', function() {
            return $this->session_mock;
        });

        $this->container->register('messages', function() {
            return $this->messages_mock;
        });

        $this->container->register('styles', function() {
            return $this->styles_mock;
        });
    }

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        // Expect session initialization
        $this->session_mock->expects($this->once())
            ->method('initialize_session');

        // Run plugin initialization
        $this->core->run();

        // Verify hooks are added
        $this->assertEquals(10, has_action('plugins_loaded', [$this->get_i18n_instance(), 'load_plugin_textdomain']));
        $this->assertEquals(10, has_action('wp_enqueue_scripts', [$this->interface_mock, 'enqueue_assets']));
        $this->assertEquals(10, has_action('wp_footer', [$this->interface_mock, 'render_chat_window']));
    }

    /**
     * Test admin hooks registration
     */
    public function test_admin_hooks_registration() {
        // Set WordPress admin context
        set_current_screen('admin.php');

        // Expect settings methods to be called
        $this->settings_mock->expects($this->once())
            ->method('add_settings_page');
        $this->settings_mock->expects($this->once())
            ->method('register_settings');
        $this->settings_mock->expects($this->once())
            ->method('enqueue_admin_assets');

        $this->core->run();

        // Verify admin hooks
        $this->assertEquals(10, has_action('admin_menu', [$this->settings_mock, 'add_settings_page']));
        $this->assertEquals(10, has_action('admin_init', [$this->settings_mock, 'register_settings']));
        $this->assertEquals(10, has_action('admin_enqueue_scripts', [$this->settings_mock, 'enqueue_admin_assets']));
    }

    /**
     * Test AJAX message handling
     */
    public function test_handle_ajax_message() {
        // Setup POST data and nonce
        $_POST['message'] = 'Test message';
        $_POST['nonce'] = wp_create_nonce('dialogpro_message_nonce');

        // Setup expected response
        $expected_response = ['status' => 'success'];
        
        // Configure messages mock
        $this->messages_mock->expects($this->once())
            ->method('handle_message')
            ->with('Test message')
            ->willReturn($expected_response);

        // Capture AJAX response
        try {
            ob_start();
            $this->core->handle_ajax_message();
            $response = json_decode(ob_get_clean(), true);

            $this->assertTrue($response['success']);
            $this->assertEquals($expected_response, $response['data']);
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail('AJAX handler threw an exception: ' . $e->getMessage());
        }
    }

    /**
     * Test AJAX message handling with invalid nonce
     */
    public function test_handle_ajax_message_invalid_nonce() {
        $_POST['message'] = 'Test message';
        $_POST['nonce'] = 'invalid_nonce';

        $this->expectException('WPDieException');
        $this->core->handle_ajax_message();
    }

    /**
     * Test AJAX message handling with empty message
     */
    public function test_handle_ajax_message_empty_message() {
        $_POST['nonce'] = wp_create_nonce('dialogpro_message_nonce');
        $_POST['message'] = '';

        ob_start();
        $this->core->handle_ajax_message();
        $response = json_decode(ob_get_clean(), true);

        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('message', $response['data']);
    }

    /**
     * Test dependency loading
     */
    public function test_load_dependencies() {
        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('load_dependencies');
        $method->setAccessible(true);

        try {
            $method->invoke($this->core);
            $this->assertTrue(true); // If we get here, no exception was thrown
        } catch (Exception $e) {
            $this->fail('Failed to load dependencies: ' . $e->getMessage());
        }
    }

    /**
     * Test component initialization
     */
    public function test_initialize_components() {
        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('initialize_components');
        $method->setAccessible(true);

        // Expect session initialization
        $this->session_mock->expects($this->once())
            ->method('initialize_session');

        // Expect styles hook registration
        $this->styles_mock->expects($this->once())
            ->method('output_custom_css');

        $method->invoke($this->core);

        // Verify styles hook
        $this->assertEquals(10, has_action('wp_head', [$this->styles_mock, 'output_custom_css']));
    }

    /**
     * Test error handling during initialization
     */
    public function test_initialization_error_handling() {
        // Force an error during initialization
        $this->session_mock->method('initialize_session')
            ->willThrowException(new Exception('Test error'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test error');

        $this->core->run();
    }

    /**
     * Helper method to get I18n instance
     */
    private function get_i18n_instance() {
        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('set_locale');
        $method->setAccessible(true);
        $method->invoke($this->core);

        global $wp_filter;
        $callbacks = $wp_filter['plugins_loaded']->callbacks[10];
        $callback = reset($callbacks);
        return $callback['function'][0];
    }

    public function tearDown(): void {
        // Reset POST data
        $_POST = [];
        
        // Reset hooks
        remove_all_actions('plugins_loaded');
        remove_all_actions('wp_enqueue_scripts');
        remove_all_actions('wp_footer');
        remove_all_actions('admin_menu');
        remove_all_actions('admin_init');
        remove_all_actions('admin_enqueue_scripts');
        remove_all_actions('wp_head');

        parent::tearDown();
    }
}