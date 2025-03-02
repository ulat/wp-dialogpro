<?php
/**
 * Class DialogProMessagesTest
 *
 * @package DialogPro
 */

class DialogProMessagesTest extends WP_UnitTestCase {
    private $messages;
    private $container;
    private $api_mock;
    private $session_mock;

    public function setUp(): void {
        parent::setUp();

        // Create API mock
        $this->api_mock = $this->createMock(DialogProAPI::class);
        
        // Create Session mock
        $this->session_mock = $this->createMock(DialogProSession::class);
        $this->session_mock->method('is_valid')->willReturn(true);
        $this->session_mock->method('get_session_id')->willReturn('test_session');
        $this->session_mock->method('get_token_count')->willReturn(100);
        $this->session_mock->method('update_token_count')->willReturn(true);

        // Setup container with mocks
        $this->container = new DialogProContainer();
        $this->container->register('api', function() {
            return $this->api_mock;
        });
        $this->container->register('session', function() {
            return $this->session_mock;
        });

        // Initialize messages handler
        $this->messages = new DialogProMessages($this->container);
    }

    /**
     * Test valid message handling
     */
    public function test_handle_message_valid() {
        $test_message = 'Hello, World!';
        $expected_response = [
            'response' => 'API Response',
            'status' => 'success'
        ];

        // Configure API mock
        $this->api_mock->expects($this->once())
            ->method('send_message')
            ->with($test_message, 'test_session')
            ->willReturn($expected_response);

        $response = $this->messages->handle_message($test_message);

        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertArrayHasKey('token_count', $response);
        $this->assertArrayHasKey('session_valid', $response);
    }

    /**
     * Test empty message validation
     */
    public function test_handle_empty_message() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Message cannot be empty');
        
        $this->messages->handle_message('');
    }

    /**
     * Test message length validation
     */
    public function test_handle_long_message() {
        $long_message = str_repeat('a', 1001);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Message exceeds maximum length');
        
        $this->messages->handle_message($long_message);
    }

    /**
     * Test XSS detection
     */
    public function test_xss_detection() {
        $reflection = new ReflectionClass($this->messages);
        $method = $reflection->getMethod('contains_xss');
        $method->setAccessible(true);

        $test_cases = [
            '<script>alert("xss")</script>' => true,
            'javascript:alert("xss")' => true,
            'onclick=alert("xss")' => true,
            'data:image/jpeg;base64,' => true,
            'Normal text message' => false,
            'Message with :) emoticon' => false
        ];

        foreach ($test_cases as $input => $expected) {
            $this->assertEquals(
                $expected,
                $method->invoke($this->messages, $input),
                "XSS detection failed for: $input"
            );
        }
    }

    /**
     * Test emoticon conversion
     */
    public function test_emoticon_conversion() {
        $reflection = new ReflectionClass($this->messages);
        $method = $reflection->getMethod('convert_emoticons');
        $method->setAccessible(true);

        $test_cases = [
            'Hello :)' => 'Hello smile',
            'Sad :(' => 'Sad sad',
            'Wink ;)' => 'Wink wink',
            'Happy :D' => 'Happy grin',
            'Multiple :) :(' => 'Multiple smile sad',
            'No emoticons' => 'No emoticons'
        ];

        foreach ($test_cases as $input => $expected) {
            $this->assertEquals(
                $expected,
                $method->invoke($this->messages, $input),
                "Emoticon conversion failed for: $input"
            );
        }
    }

    /**
     * Test token calculation
     */
    public function test_calculate_tokens() {
        $reflection = new ReflectionClass($this->messages);
        $method = $reflection->getMethod('calculate_tokens');
        $method->setAccessible(true);

        $test_cases = [
            'Short' => 2,                    // 5 chars = 2 tokens
            'Medium length text' => 4,       // 16 chars = 4 tokens
            'A' => 1,                        // 1 char = 1 token
            str_repeat('a', 100) => 25      // 100 chars = 25 tokens
        ];

        foreach ($test_cases as $input => $expected) {
            $this->assertEquals(
                $expected,
                $method->invoke($this->messages, $input),
                "Token calculation failed for: $input"
            );
        }
    }

    /**
     * Test message history management
     */
    public function test_message_history() {
        // Test empty history
        $this->assertEmpty($this->messages->get_message_history());

        // Add messages to history
        $test_messages = [];
        for ($i = 0; $i < 60; $i++) {
            $test_messages[] = [
                'message' => "Message $i",
                'timestamp' => time()
            ];
            $this->messages->save_to_history($test_messages[$i]);
        }

        // Get history
        $history = $this->messages->get_message_history();

        // Verify history size limit
        $this->assertCount(50, $history);

        // Verify FIFO behavior
        $this->assertEquals(
            "Message 59",
            end($history)['message']
        );
    }

    /**
     * Test invalid session handling
     */
    public function test_invalid_session() {
        // Mock invalid session
        $this->session_mock->method('is_valid')->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid session');
        
        $this->messages->handle_message('Test message');
    }

    /**
     * Test token limit exceeded
     */
    public function test_token_limit_exceeded() {
        // Mock token limit exceeded
        $this->session_mock->method('update_token_count')->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Token limit exceeded');
        
        $this->messages->handle_message('Test message');
    }

    /**
     * Test response formatting
     */
    public function test_format_response() {
        $reflection = new ReflectionClass($this->messages);
        $method = $reflection->getMethod('format_response');
        $method->setAccessible(true);

        $api_response = ['response' => '<p>Test response</p>'];
        $formatted = $method->invoke($this->messages, $api_response);

        $this->assertArrayHasKey('message', $formatted);
        $this->assertArrayHasKey('timestamp', $formatted);
        $this->assertArrayHasKey('token_count', $formatted);
        $this->assertArrayHasKey('session_valid', $formatted);
        $this->assertEquals('<p>Test response</p>', $formatted['message']);
    }

    /**
     * Test message processing filters
     */
    public function test_process_message_filters() {
        $test_message = 'Original message';
        
        add_filter('dialogpro_process_message', function($message) {
            return 'Filtered: ' . $message;
        });

        $reflection = new ReflectionClass($this->messages);
        $method = $reflection->getMethod('process_message');
        $method->setAccessible(true);

        $processed = $method->invoke($this->messages, $test_message);
        
        $this->assertEquals(
            'Filtered: Original message',
            $processed,
            'Message should be processed through filter'
        );

        remove_all_filters('dialogpro_process_message');
    }
}

// This test suite includes:

// 1. Setup and Mocking:
// - Container configuration
// - API mock
// - Session mock
// - Error handling

// 2. Message Handling Tests:
// - Valid message processing
// - Empty message validation
// - Message length validation
// - XSS detection
// - Emoticon conversion

// 3. Token Management Tests:
// - Token calculation
// - Token limit handling
// - Session validation

// 4. History Management Tests:
// - History retrieval
// - History size limits
// - FIFO behavior

// 5. Response Handling Tests:
// - Response formatting
// - Error handling
// - Session validation

// Key features tested:
// 1. Message Validation
// 2. Security Checks
// 3. Token Management
// 4. History Management
// 5. Error Handling
// 6. Filter Integration
// 7. Response Formatting