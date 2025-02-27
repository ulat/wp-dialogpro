<?php
/**
 * Core plugin class
 * 
 * @package DialogPro
 */

class DialogProCore {
    /**
     * @var DialogProContainer Service container instance
     */
    private $container;

    /**
     * @var array Loaded components
     */
    private $components = [];

    /**
     * Constructor
     * 
     * @param DialogProContainer $container Service container
     */
    public function __construct(DialogProContainer $container) {
        $this->container = $container;
    }

    /**
     * Initialize the plugin
     */
    public function run(): void {
        try {
            $this->load_dependencies();
            $this->set_locale();
            $this->define_admin_hooks();
            $this->define_public_hooks();
            $this->initialize_components();
        } catch (Exception $e) {
            error_log('DialogPro Core Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies(): void {
        // Load required files if not using autoloader
        $required_files = [
            'interface',
            'messages',
            'styles',
            'i18n'
        ];

        foreach ($required_files as $component) {
            $file = DIALOGPRO_PATH . "includes/class-dialogpro-{$component}.php";
            if (file_exists($file)) {
                require_once $file;
            } else {
                throw new Exception("Required component file missing: {$component}");
            }
        }
    }

    /**
     * Set plugin locale
     */
    private function set_locale(): void {
        $i18n = new DialogProI18n();
        add_action('plugins_loaded', [$i18n, 'load_plugin_textdomain']);
    }

    /**
     * Register admin hooks
     */
    private function define_admin_hooks(): void {
        if (is_admin()) {
            // Get settings instance from container
            $settings = $this->container->get('settings');
            
            add_action('admin_menu', [$settings, 'add_settings_page']);
            add_action('admin_init', [$settings, 'register_settings']);
            add_action('admin_enqueue_scripts', [$settings, 'enqueue_admin_assets']);
        }
    }

    /**
     * Register public hooks
     */
    private function define_public_hooks(): void {
        // Initialize interface component
        $interface = new DialogProInterface($this->container);
        
        add_action('wp_enqueue_scripts', [$interface, 'enqueue_assets']);
        add_action('wp_footer', [$interface, 'render_chat_window']);

        // Initialize AJAX handlers
        add_action('wp_ajax_dialogpro_send_message', [$this, 'handle_ajax_message']);
        add_action('wp_ajax_nopriv_dialogpro_send_message', [$this, 'handle_ajax_message']);
    }

    /**
     * Initialize all required components
     */
    private function initialize_components(): void {
        // Initialize session
        $session = $this->container->get('session');
        $session->initialize_session();

        // Initialize styles
        $styles = new DialogProStyles($this->container->get('settings'));
        add_action('wp_head', [$styles, 'output_custom_css']);
    }

    /**
     * Handle AJAX message requests
     */
    public function handle_ajax_message(): void {
        try {
            // Verify nonce
            check_ajax_referer('dialogpro_message_nonce', 'nonce');

            // Get message from request
            $message = sanitize_text_field($_POST['message'] ?? '');
            if (empty($message)) {
                throw new Exception(__('Message cannot be empty', 'wp-dialogpro'));
            }

            // Get message handler from container
            $messages = new DialogProMessages($this->container);
            $response = $messages->handle_message($message);

            wp_send_json_success($response);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}