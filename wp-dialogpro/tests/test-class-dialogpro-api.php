<?php
/**
 * Class DialogProAPITest
 *
 * @package DialogPro
 */

class DialogProAPITest extends WP_UnitTestCase {
    private $api;
    private $settings_mock;
    private $test_endpoint = 'https://api.example.com/chat';
    private $test_token = 'test_token_123';

    public function setUp(): void {
        parent::setUp();
        
        // Create mock settings
        $this->settings_mock = $this->createMock(DialogProSettings::class);
        $this->settings_mock->method('get_option')
            ->willReturnMap([
                ['api_endpoint', $this->test_endpoint],
                ['api_token', $this->test_token]
            ]);

        // Initialize API with mock settings
        $this->api = new DialogProAPI($this->settings_mock);

        // Clear cache before each test
        wp_cache_flush();
    }

    public function tearDown(): void {
        wp_cache_flush();
        parent::tearDown();
    }

    /**
     * Test send_message with valid input
     */
    public function test_send_message_valid_input() {
        $message = 'Test message';
        $session_id = 'test_session_123';
        
        // Mock successful API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode([
                    'response' => 'API response',
                    'status' => 'success'
                ])
            ];
        }, 10, 3);

        try {
            $response = $this->api->send_message($message, $session_id);
            
            $this->assertIsArray($response);
            $this->assertEquals('API response', $response['response']);
            $this->assertEquals('success', $response['status']);
        } catch (Exception $e) {
            $this->fail('Should not throw exception for valid input: ' . $e->getMessage());
        }
    }

    /**
     * Test send_message with empty message
     */
    public function test_send_message_empty_message() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Message cannot be empty');
        
        $this->api->send_message('', 'test_session');
    }

    /**
     * Test send_message with missing API configuration
     */
    public function test_send_message_missing_config() {
        // Create settings mock that returns empty values
        $settings_mock = $this->createMock(DialogProSettings::class);
        $settings_mock->method('get_option')->willReturn('');
        
        $api = new DialogProAPI($settings_mock);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API configuration is incomplete');
        
        $api->send_message('test message', 'test_session');
    }

    /**
     * Test send_message with API error response
     */
    public function test_send_message_api_error() {
        // Mock error API response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return [
                'response' => ['code' => 500],
                'body' => 'Server error'
            ];
        }, 10, 3);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API returned error code: 500');
        
        $this->api->send_message('test message', 'test_session');
    }

    /**
     * Test send_message with invalid JSON response
     */
    public function test_send_message_invalid_json() {
        // Mock invalid JSON response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return [
                'response' => ['code' => 200],
                'body' => 'Invalid JSON'
            ];
        }, 10, 3);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid JSON response from API');
        
        $this->api->send_message('test message', 'test_session');
    }

    /**
     * Test send_message caching
     */
    public function test_send_message_caching() {
        $message = 'Test message';
        $session_id = 'test_session_123';
        $api_calls = 0;

        // Mock API response and count calls
        add_filter('pre_http_request', function($preempt, $args, $url) use (&$api_calls) {
            $api_calls++;
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['response' => 'Cached response'])
            ];
        }, 10, 3);

        // First call - should hit API
        $response1 = $this->api->send_message($message, $session_id);
        
        // Second call - should use cache
        $response2 = $this->api->send_message($message, $session_id);

        $this->assertEquals(1, $api_calls, 'API should only be called once');
        $this->assertEquals($response1, $response2, 'Cached response should match');
    }

    /**
     * Test check_connection with successful connection
     */
    public function test_check_connection_success() {
        // Mock successful status check
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['status' => 'ok'])
            ];
        }, 10, 3);

        $this->assertTrue($this->api->check_connection());
    }

    /**
     * Test check_connection with failed connection
     */
    public function test_check_connection_failure() {
        // Mock failed status check
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return [
                'response' => ['code' => 500],
                'body' => 'error'
            ];
        }, 10, 3);

        $this->assertFalse($this->api->check_connection());
    }

    /**
     * Test check_connection with network error
     */
    public function test_check_connection_network_error() {
        // Mock network error
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return new WP_Error('http_request_failed', 'Network error');
        }, 10, 3);

        $this->assertFalse($this->api->check_connection());
    }

    /**
     * Test clear_cache functionality
     */
    public function test_clear_cache() {
        // Set some test cache data
        wp_cache_set('test_key', 'test_value', 'dialogpro_api');
        
        // Clear cache
        $this->api->clear_cache();
        
        // Verify cache is cleared
        $this->assertFalse(wp_cache_get('test_key', 'dialogpro_api'));
    }

    /**
     * Test request headers and body format
     */
    public function test_request_format() {
        $captured_args = null;
        
        // Capture request arguments
        add_filter('pre_http_request', function($preempt, $args, $url) use (&$captured_args) {
            $captured_args = $args;
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['status' => 'ok'])
            ];
        }, 10, 3);

        $this->api->send_message('test message', 'test_session');

        // Verify request format
        $this->assertArrayHasKey('headers', $captured_args);
        $this->assertArrayHasKey('Authorization', $captured_args['headers']);
        $this->assertEquals('Bearer ' . $this->test_token, $captured_args['headers']['Authorization']);
        $this->assertEquals('application/json', $captured_args['headers']['Content-Type']);
        
        // Verify body format
        $body = json_decode($captured_args['body'], true);
        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('timestamp', $body);
    }

    /**
     * Test timeout handling
     */
    public function test_timeout_handling() {
        // Mock timeout response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return new WP_Error('http_request_failed', 'Connection timed out');
        }, 10, 3);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error communicating with chat service');
        
        $this->api->send_message('test message', 'test_session');
    }
}