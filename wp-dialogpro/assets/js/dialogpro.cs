/**
 * DialogPro Main JavaScript
 * @package DialogPro
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Main DialogPro class
    class DialogPro {
        constructor() {
            // Cache DOM elements
            this.container = $('#dialogpro-chat-container');
            this.button = $('#dialogpro-chat-button');
            this.window = $('#dialogpro-chat-window');
            this.messages = $('#dialogpro-messages-container');
            this.input = $('#dialogpro-message-input');
            this.form = $('#dialogpro-message-form');
            this.loading = $('#dialogpro-loading');
            this.errorContainer = $('#dialogpro-error-container');
            this.tokenCounter = $('#dialogpro-token-count');
            this.connectionStatus = $('#dialogpro-connection-status');

            // Initialize state
            this.isOpen = false;
            this.isProcessing = false;
            this.tokenCount = 0;
            this.messageQueue = [];

            // Initialize
            this.init();
        }

        /**
         * Initialize the chat interface
         */
        init() {
            // Show container
            this.container.show();

            // Bind events
            this.bindEvents();

            // Auto-open if configured
            if (window.dialogProData.settings.autoOpen) {
                setTimeout(() => this.openChat(), 1000);
            }

            // Initialize connection status
            this.checkConnection();
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Toggle chat window
            this.button.on('click', () => this.toggleChat());

            // Close button
            $('#dialogpro-close-button').on('click', (e) => {
                e.preventDefault();
                this.closeChat();
            });

            // Minimize button
            $('#dialogpro-minimize-button').on('click', (e) => {
                e.preventDefault();
                this.minimizeChat();
            });

            // Form submission
            this.form.on('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });

            // Input handling
            this.input.on('input', () => this.handleInput());
            this.input.on('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Window resize handling
            $(window).on('resize', () => this.handleResize());
        }

        /**
         * Toggle chat window
         */
        toggleChat() {
            this.isOpen ? this.closeChat() : this.openChat();
        }

        /**
         * Open chat window
         */
        openChat() {
            this.isOpen = true;
            this.window.addClass('active');
            this.button.attr('aria-expanded', 'true');
            this.window.attr('aria-hidden', 'false');
            this.input.focus();
            this.scrollToBottom();
        }

        /**
         * Close chat window
         */
        closeChat() {
            this.isOpen = false;
            this.window.removeClass('active');
            this.button.attr('aria-expanded', 'false');
            this.window.attr('aria-hidden', 'true');
        }

        /**
         * Minimize chat window
         */
        minimizeChat() {
            this.closeChat();
        }

        /**
         * Handle input changes
         */
        handleInput() {
            const message = this.input.val().trim();
            const sendButton = this.form.find('.dialogpro-send-button');
            
            // Enable/disable send button
            sendButton.prop('disabled', !message);

            // Auto-resize textarea
            this.input.css('height', 'auto');
            this.input.css('height', this.input.prop('scrollHeight') + 'px');
        }

        /**
         * Send message
         */
        async sendMessage() {
            if (this.isProcessing || !this.input.val().trim()) return;

            try {
                this.isProcessing = true;
                this.showLoading();

                const message = this.input.val().trim();
                this.addMessage(message, 'user');
                this.input.val('').trigger('input');

                const response = await this.sendToAPI(message);
                this.handleResponse(response);

            } catch (error) {
                this.showError(error.message);
            } finally {
                this.hideLoading();
                this.isProcessing = false;
            }
        }

        /**
         * Send message to API
         */
        async sendToAPI(message) {
            const response = await $.ajax({
                url: window.dialogProData.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'dialogpro_send_message',
                    message: message,
                    nonce: window.dialogProData.nonce
                }
            });

            if (!response.success) {
                throw new Error(response.data.message || window.dialogProData.i18n.error);
            }

            return response.data;
        }

        /**
         * Handle API response
         */
        handleResponse(response) {
            if (response.message) {
                this.addMessage(response.message, 'bot');
            }

            if (response.token_count) {
                this.updateTokenCount(response.token_count);
            }
        }

        /**
         * Add message to chat
         */
        addMessage(content, type) {
            const template = $('#dialogpro-message-template').html();
            const message = $(template);
            
            message.addClass(`dialogpro-message-${type}`);
            message.find('.dialogpro-message-content').html(content);
            message.find('.dialogpro-message-time').text(this.getCurrentTime());

            this.messages.append(message);
            this.scrollToBottom();
        }

        /**
         * Update token count
         */
        updateTokenCount(count) {
            this.tokenCount = count;
            this.tokenCounter.text(count);

            if (count >= window.dialogProData.settings.tokenLimit) {
                this.showError(window.dialogProData.i18n.tokenLimitReached);
                this.input.prop('disabled', true);
            }
        }

        /**
         * Show loading indicator
         */
        showLoading() {
            this.loading.addClass('active');
        }

        /**
         * Hide loading indicator
         */
        hideLoading() {
            this.loading.removeClass('active');
        }

        /**
         * Show error message
         */
        showError(message) {
            this.errorContainer.html(message).slideDown();
            setTimeout(() => this.errorContainer.slideUp(), 5000);
        }

        /**
         * Check connection status
         */
        checkConnection() {
            const updateStatus = (status, text) => {
                this.connectionStatus
                    .removeClass('connected disconnected')
                    .addClass(status)
                    .text(text);
            };

            // Initial status
            updateStatus('connecting', window.dialogProData.i18n.connecting);

            // Periodic connection check
            setInterval(async () => {
                try {
                    const response = await $.ajax({
                        url: window.dialogProData.ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'dialogpro_check_connection',
                            nonce: window.dialogProData.nonce
                        }
                    });

                    updateStatus(
                        response.success ? 'connected' : 'disconnected',
                        response.success ? window.dialogProData.i18n.connected : window.dialogProData.i18n.disconnected
                    );

                } catch (error) {
                    updateStatus('disconnected', window.dialogProData.i18n.disconnected);
                }
            }, 30000); // Check every 30 seconds
        }

        /**
         * Scroll messages to bottom
         */
        scrollToBottom() {
            this.messages.animate({ scrollTop: this.messages.prop('scrollHeight') }, 300);
        }

        /**
         * Get current time
         */
        getCurrentTime() {
            return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        /**
         * Handle window resize
         */
        handleResize() {
            if (window.innerWidth <= 480) {
                this.container.addClass('mobile');
            } else {
                this.container.removeClass('mobile');
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        window.dialogPro = new DialogPro();
    });

})(jQuery);