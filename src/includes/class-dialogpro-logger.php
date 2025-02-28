<?php
/**
 * Logger Utility Class
 * 
 * Handles logging and debugging
 * @package DialogPro
 */

class DialogProLogger {
    private const LOG_LEVELS = [
        'error' => 1,
        'warning' => 2,
        'info' => 3,
        'debug' => 4
    ];

    private $settings;
    private $log_level;
    private const LOG_FILE = 'dialogpro-debug.log';

    /**
     * Constructor
     */
    public function __construct(DialogProSettings $settings) {
        $this->settings = $settings;
        $this->log_level = self::LOG_LEVELS[
            $settings->get_option('logging_level', 'error')
        ];
    }

    /**
     * Log a message
     */
    public function log(string $level, string $message, array $context = []): void {
        if (!$this->should_log($level)) {
            return;
        }

        $log_entry = $this->format_log_entry($level, $message, $context);
        
        if ($this->settings->get_option('log_to_file', false)) {
            $this->write_to_file($log_entry);
        } else {
            error_log($log_entry);
        }
    }

    /**
     * Check if message should be logged
     */
    private function should_log(string $level): bool {
        return self::LOG_LEVELS[$level] <= $this->log_level;
    }

    /**
     * Format log entry
     */
    private function format_log_entry(
        string $level,
        string $message,
        array $context
    ): string {
        $timestamp = current_time('Y-m-d H:i:s');
        $context_string = !empty($context) ? 
            ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';

        return sprintf(
            '[%s] %s: %s%s',
            $timestamp,
            strtoupper($level),
            $message,
            $context_string
        );
    }

    /**
     * Write to log file
     */
    private function write_to_file(string $entry): void {
        try {
            $log_file = WP_CONTENT_DIR . '/logs/' . self::LOG_FILE;
            
            // Create logs directory if it doesn't exist
            if (!file_exists(WP_CONTENT_DIR . '/logs')) {
                mkdir(WP_CONTENT_DIR . '/logs', 0755, true);
            }

            // Rotate log file if too large
            if (file_exists($log_file) && filesize($log_file) > 5 * MB_IN_BYTES) {
                $this->rotate_log_file($log_file);
            }

            file_put_contents(
                $log_file,
                $entry . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );

        } catch (Exception $e) {
            error_log('DialogPro Logger Error: ' . $e->getMessage());
        }
    }

    /**
     * Rotate log file
     */
    private function rotate_log_file(string $log_file): void {
        $backup_file = $log_file . '.' . date('Y-m-d');
        rename($log_file, $backup_file);

        // Keep only last 5 backup files
        $backup_files = glob($log_file . '.*');
        if (count($backup_files) > 5) {
            array_map('unlink', array_slice($backup_files, 0, -5));
        }
    }

    /**
     * Convenience methods for different log levels
     */
    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log('warning', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }
}