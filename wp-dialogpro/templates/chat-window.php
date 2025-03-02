<?php
/**
 * Chat Window Template
 *
 * @package DialogPro
 * @version 1.0.0
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

$settings = DialogProSettings::get_instance();
$position_class = 'dialogpro-position-' . $settings->get_option('chat_position', 'bottom-right');
$welcome_message = $settings->get_option('welcome_message', __('Hello! How can I help you today?', 'wp-dialogpro'));
?>

<div id="dialogpro-chat-container" 
     class="dialogpro-chat-container <?php echo esc_attr($position_class); ?>" 
     style="display: none;">
    
    <!-- Chat Button -->
    <button id="dialogpro-chat-button"
            class="dialogpro-button"
            aria-label="<?php esc_attr_e('Open chat', 'wp-dialogpro'); ?>"
            aria-expanded="false">
        <span class="material-icons">chat</span>
    </button>

    <!-- Chat Window -->
    <div id="dialogpro-chat-window" 
         class="dialogpro-chat-window"
         role="dialog"
         aria-label="<?php esc_attr_e('Chat Window', 'wp-dialogpro'); ?>"
         aria-hidden="true">
        
        <!-- Header -->
        <div class="dialogpro-chat-header">
            <h3 class="dialogpro-chat-title">
                <?php echo esc_html($settings->get_option('chat_title', __('Chat', 'wp-dialogpro'))); ?>
            </h3>
            <div class="dialogpro-chat-controls">
                <span id="dialogpro-connection-status" 
                      class="dialogpro-status"
                      aria-live="polite">
                    <?php esc_html_e('Connecting...', 'wp-dialogpro'); ?>
                </span>
                <button id="dialogpro-minimize-button"
                        class="dialogpro-control-button"
                        aria-label="<?php esc_attr_e('Minimize chat', 'wp-dialogpro'); ?>">
                    <span class="material-icons">remove</span>
                </button>
                <button id="dialogpro-close-button"
                        class="dialogpro-control-button"
                        aria-label="<?php esc_attr_e('Close chat', 'wp-dialogpro'); ?>">
                    <span class="material-icons">close</span>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div id="dialogpro-messages-container" 
             class="dialogpro-messages-container"
             role="log"
             aria-label="<?php esc_attr_e('Chat messages', 'wp-dialogpro'); ?>"
             aria-live="polite">
            
            <!-- Welcome Message -->
            <div class="dialogpro-message dialogpro-message-bot">
                <div class="dialogpro-message-content">
                    <?php echo wp_kses_post($welcome_message); ?>
                </div>
                <span class="dialogpro-message-time" aria-hidden="true">
                    <?php echo esc_html(current_time('H:i')); ?>
                </span>
            </div>

            <!-- Messages will be dynamically inserted here -->
        </div>

        <!-- Token Counter -->
        <div id="dialogpro-token-counter" 
             class="dialogpro-token-counter"
             aria-live="polite">
            <span class="material-icons">data_usage</span>
            <span id="dialogpro-token-count">0</span>/<?php echo esc_html($settings->get_option('token_limit', '8500')); ?>
        </div>

        <!-- Input Area -->
        <div class="dialogpro-input-container">
            <form id="dialogpro-message-form" class="dialogpro-message-form">
                <div class="dialogpro-input-wrapper">
                    <textarea id="dialogpro-message-input"
                            class="dialogpro-message-input"
                            placeholder="<?php esc_attr_e('Type your message...', 'wp-dialogpro'); ?>"
                            aria-label="<?php esc_attr_e('Message input', 'wp-dialogpro'); ?>"
                            rows="1"
                            maxlength="1000"
                            required></textarea>
                    
                    <div class="dialogpro-input-controls">
                        <span id="dialogpro-char-counter" 
                              class="dialogpro-char-counter"
                              aria-live="polite">1000</span>
                        
                        <button type="submit"
                                id="dialogpro-send-button"
                                class="dialogpro-send-button"
                                aria-label="<?php esc_attr_e('Send message', 'wp-dialogpro'); ?>"
                                disabled>
                            <span class="material-icons">send</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Loading Indicator -->
        <div id="dialogpro-loading" 
             class="dialogpro-loading"
             role="status"
             aria-hidden="true">
            <div class="dialogpro-loading-spinner"></div>
            <span class="dialogpro-loading-text">
                <?php esc_html_e('Processing...', 'wp-dialogpro'); ?>
            </span>
        </div>

        <!-- Error Messages -->
        <div id="dialogpro-error-container"
             class="dialogpro-error-container"
             role="alert"
             style="display: none;">
        </div>
    </div>
</div>

<!-- Message Template (for JavaScript use) -->
<template id="dialogpro-message-template">
    <div class="dialogpro-message" data-message-id="">
        <div class="dialogpro-message-content"></div>
        <span class="dialogpro-message-time" aria-hidden="true"></span>
    </div>
</template>

<!-- Loading Message Template -->
<template id="dialogpro-loading-message-template">
    <div class="dialogpro-message dialogpro-message-loading">
        <div class="dialogpro-typing-indicator">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</template>

<?php
// Add data for JavaScript
$dialog_data = [
    'nonce' => wp_create_nonce('dialogpro_message_nonce'),
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'settings' => [
        'tokenLimit' => (int)$settings->get_option('token_limit', 8500),
        'maxLength' => 1000,
        'position' => $settings->get_option('chat_position', 'bottom-right'),
        'autoOpen' => (bool)$settings->get_option('auto_open', false),
        'soundEnabled' => (bool)$settings->get_option('sound_enabled', true),
    ],
    'i18n' => [
        'sending' => __('Sending...', 'wp-dialogpro'),
        'error' => __('Error sending message', 'wp-dialogpro'),
        'reconnecting' => __('Reconnecting...', 'wp-dialogpro'),
        'connected' => __('Connected', 'wp-dialogpro'),
        'disconnected' => __('Disconnected', 'wp-dialogpro'),
        'tokenLimitReached' => __('Token limit reached', 'wp-dialogpro'),
    ]
];
?>

<script type="text/javascript">
    // Pass data to JavaScript
    window.dialogProData = <?php echo wp_json_encode($dialog_data); ?>;
</script>