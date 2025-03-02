<?php
/**
 * Class DialogProInterfaceTest
 *
 * @package DialogPro
 */

class DialogProInterfaceTest extends WP_UnitTestCase {
    private $interface;
    private $container;
    private $settings_mock;
    private $test_template_dir;

    public function setUp(): void {
        parent::setUp();

        // Define required constants if not defined
        if (!defined('DIALOGPRO_URL')) {
            define('DIALOGPRO_URL', 'http://example.com/wp-content/plugins/dialogpro/');
        }
        if (!defined('DIALOGPRO_PATH')) {
            define('DIALOGPRO_PATH', dirname(__DIR__) . '/');
        }

        // Create test template directory
        $this->test_template_dir = WP_CONTENT_DIR . '/themes/test-theme/dialogpro';
        if (!is_dir($this->test_template_dir)) {
            mkdir($this->test_template_dir, 0777, true);
        }

        // Create settings mock
        $this->settings_mock = $this->createMock(DialogProSettings::class);
        $this->settings_mock->method('get_option')
            ->willReturnMap([
                ['chat_position', 'bottom-right'],
                ['chat_width', '300'],
                ['welcome_message', 'Welcome!'],
                ['token_limit', '8500'],
                ['use_material_icons', true],
                ['enabled_post_types', ['post', 'page']]
            ]);

        // Setup container with mocks
        $this->container = new DialogProContainer();
        $this->container->register('settings', function() {
            return $this->settings_mock;
        });

        // Initialize interface
        $this->interface = new DialogProInterface($this->container);
    }

    public function tearDown(): void {
        // Clean up test files and directories
        if (is_dir($this->test_template_dir)) {
            $this->recursive_rmdir($this->test_template_dir);
        }

        // Reset WordPress state
        $this->remove_added_uploads();
        parent::tearDown();
    }

    /**
     * Test asset enqueuing
     */
    public function test_enqueue_assets() {
        // Execute enqueue
        $this->interface->enqueue_assets();

        // Verify style registration
        $this->assertTrue(
            wp_style_is('dialogpro-styles', 'registered'),
            'DialogPro styles should be registered'
        );
        $this->assertTrue(
            wp_style_is('dialogpro-styles', 'enqueued'),
            'DialogPro styles should be enqueued'
        );

        // Verify script registration
        $this->assertTrue(
            wp_script_is('dialogpro-scripts', 'registered'),
            'DialogPro scripts should be registered'
        );
        $this->assertTrue(
            wp_script_is('dialogpro-scripts', 'enqueued'),
            'DialogPro scripts should be enqueued'
        );

        // Verify Material Icons
        $this->assertTrue(
            wp_style_is('material-icons', 'enqueued'),
            'Material Icons should be enqueued when enabled'
        );
    }

    /**
     * Test localized data
     */
    public function test_get_localized_data() {
        $reflection = new ReflectionClass(DialogProInterface::class);
        $method = $reflection->getMethod('get_localized_data');
        $method->setAccessible(true);

        $data = $method->invoke($this->interface);

        // Verify data structure
        $this->assertArrayHasKey('ajaxUrl', $data);
        $this->assertArrayHasKey('nonce', $data);
        $this->assertArrayHasKey('settings', $data);
        $this->assertArrayHasKey('i18n', $data);

        // Verify settings
        $this->assertEquals('bottom-right', $data['settings']['position']);
        $this->assertEquals('300', $data['settings']['width']);
        $this->assertEquals('Welcome!', $data['settings']['welcomeMessage']);
        $this->assertEquals(8500, $data['settings']['tokenLimit']);

        // Verify translations
        $this->assertArrayHasKey('sending', $data['i18n']);
        $this->assertArrayHasKey('error', $data['i18n']);
        $this->assertArrayHasKey('tokenLimitReached', $data['i18n']);
    }

    /**
     * Test chat window rendering
     */
    public function test_render_chat_window() {
        // Create test template
        $template_content = '<div class="dialogpro-chat-window">Test Content</div>';
        file_put_contents(
            DIALOGPRO_PATH . 'templates/chat-window.php',
            $template_content
        );

        // Capture output
        ob_start();
        $this->interface->render_chat_window();
        $output = ob_get_clean();

        // Verify output
        $this->assertStringContainsString('dialogpro-chat-window', $output);
        $this->assertStringContainsString('role="complementary"', $output);
        $this->assertStringContainsString('aria-live="polite"', $output);
    }

    /**
     * Test chat display conditions
     */
    public function test_should_display_chat() {
        $reflection = new ReflectionClass(DialogProInterface::class);
        $method = $reflection->getMethod('should_display_chat');
        $method->setAccessible(true);

        // Test admin page
        set_current_screen('admin.php');
        $this->assertFalse(
            $method->invoke($this->interface),
            'Chat should not display in admin'
        );

        // Test allowed post type
        set_current_screen('front');
        $GLOBALS['post'] = $this->factory->post->create_and_get();
        $this->assertTrue(
            $method->invoke($this->interface),
            'Chat should display on allowed post type'
        );
    }

    /**
     * Test template path resolution
     */
    public function test_get_template_path() {
        $reflection = new ReflectionClass(DialogProInterface::class);
        $method = $reflection->getMethod('get_template_path');
        $method->setAccessible(true);

        // Test theme override
        $theme_template = $this->test_template_dir . '/chat-window.php';
        file_put_contents($theme_template, 'Theme Template');
        
        $path = $method->invoke($this->interface, 'chat-window.php');
        $this->assertEquals(
            $theme_template,
            $path,
            'Should use theme template when available'
        );

        // Test fallback to plugin template
        unlink($theme_template);
        $path = $method->invoke($this->interface, 'chat-window.php');
        $this->assertEquals(
            DIALOGPRO_PATH . 'templates/chat-window.php',
            $path,
            'Should fallback to plugin template'
        );
    }

    /**
     * Test accessibility attributes
     */
    public function test_add_accessibility_attributes() {
        $reflection = new ReflectionClass(DialogProInterface::class);
        $method = $reflection->getMethod('add_accessibility_attributes');
        $method->setAccessible(true);

        $html = '<div class="dialogpro-chat-window">Test</div>';
        $output = $method->invoke($this->interface, $html);

        $this->assertStringContainsString('role="complementary"', $output);
        $this->assertStringContainsString('aria-live="polite"', $output);
        $this->assertStringContainsString('aria-label=', $output);
    }

    /**
     * Test error handling during rendering
     */
    public function test_render_error_handling() {
        // Force template not found error
        $this->settings_mock->method('get_option')
            ->willReturn(['post']);

        ob_start();
        $this->interface->render_chat_window();
        $output = ob_get_clean();

        // Verify error handling for admin
        wp_set_current_user($this->factory->user->create(['role' => 'administrator']));
        ob_start();
        $this->interface->render_chat_window();
        $admin_output = ob_get_clean();
        
        $this->assertStringContainsString('DialogPro Error:', $admin_output);
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
// - Mock settings and container
// - Test template directory management
// - WordPress environment setup
// - Cleanup procedures

// 2. Asset Management Tests:
// - Style registration
// - Script registration
// - Material Icons integration
// - Localized data verification

// 3. Rendering Tests:
// - Template loading
// - Output buffering
// - Accessibility attributes
// - Error handling

// 4. Display Logic Tests:
// - Admin page detection
// - Post type verification
// - Template path resolution
// - Theme override support

// 5. Helper Methods:
// - Directory cleanup
// - File management
// - Screen simulation

// Key features tested:
// 1. Asset Loading
// 2. Template Handling
// 3. Accessibility
// 4. Error Management
// 5. Display Logic
// 6. Theme Integration
// 7. Admin Interface