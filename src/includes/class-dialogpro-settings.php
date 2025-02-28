<?php
/**
 * Settings Manager Class
 * 
 * Handles all plugin settings and admin interface
 * @package DialogPro
 */

class DialogProSettings {
    private static $instance = null;
    private $options;
    private const OPTION_NAME = 'dialogpro_settings';

    /**
     * Default settings
     */
    private const DEFAULTS = [
        'api_endpoint' => '',
        'api_token' => '',
        'chat_position' => 'bottom-right',
        'chat_width' => '20',
        'primary_color' => '#007bff',
        'font_family' => 'Arial, sans-serif',
        'font_size' => '14',
        'token_limit' => '8500',
        'welcome_message' => 'Hello! How can I help you today?',
        'logging_level' => 'error'
    ];

    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->options = get_option(self::OPTION_NAME, self::DEFAULTS);
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page(): void {
        add_options_page(
            __('DialogPro Settings', 'wp-dialogpro'),
            __('DialogPro', 'wp-dialogpro'),
            'manage_options',
            'dialogpro-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings and fields
     */
    public function register_settings(): void {
        register_setting(
            'dialogpro_options',
            self::OPTION_NAME,
            [$this, 'validate_settings']
        );

        // API Settings Section
        add_settings_section(
            'dialogpro_api_settings',
            __('API Configuration', 'wp-dialogpro'),
            [$this, 'render_api_section'],
            'dialogpro-settings'
        );

        // Add API fields
        $this->add_field('api_endpoint', __('API Endpoint', 'wp-dialogpro'), 'url');
        $this->add_field('api_token', __('API Token', 'wp-dialogpro'), 'password');

        // Appearance Settings Section
        add_settings_section(
            'dialogpro_appearance',
            __('Appearance Settings', 'wp-dialogpro'),
            [$this, 'render_appearance_section'],
            'dialogpro-settings'
        );

        // Add appearance fields
        $this->add_field('chat_position', __('Chat Position', 'wp-dialogpro'), 'select', [
            'bottom-right' => __('Bottom Right', 'wp-dialogpro'),
            'bottom-left' => __('Bottom Left', 'wp-dialogpro'),
            'top-right' => __('Top Right', 'wp-dialogpro'),
            'top-left' => __('Top Left', 'wp-dialogpro')
        ]);
        $this->add_field('chat_width', __('Chat Width (%)', 'wp-dialogpro'), 'number');
        $this->add_field('primary_color', __('Primary Color', 'wp-dialogpro'), 'color');
    }

    /**
     * Add a settings field
     */
    private function add_field(string $id, string $title, string $type, array $options = []): void {
        add_settings_field(
            "dialogpro_{$id}",
            $title,
            [$this, 'render_field'],
            'dialogpro-settings',
            'dialogpro_api_settings',
            [
                'id' => $id,
                'type' => $type,
                'options' => $options
            ]
        );
    }

    /**
     * Render a settings field
     */
    public function render_field(array $args): void {
        $id = $args['id'];
        $type = $args['type'];
        $value = $this->get_option($id);
        $name = self::OPTION_NAME . "[$id]";

        switch ($type) {
            case 'text':
            case 'url':
            case 'password':
            case 'number':
            case 'color':
                printf(
                    '<input type="%s" id="%s" name="%s" value="%s" class="regular-text">',
                    esc_attr($type),
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value)
                );
                break;

            case 'select':
                echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
                foreach ($args['options'] as $key => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($key),
                        selected($value, $key, false),
                        esc_html($label)
                    );
                }
                echo '</select>';
                break;
        }
    }

    /**
     * Validate settings before save
     */
    public function validate_settings($input): array {
        $validated = [];

        foreach (self::DEFAULTS as $key => $default) {
            switch ($key) {
                case 'api_endpoint':
                    $validated[$key] = esc_url_raw($input[$key] ?? $default);
                    break;

                case 'api_token':
                    $validated[$key] = sanitize_text_field($input[$key] ?? $default);
                    break;

                case 'chat_width':
                    $validated[$key] = min(100, max(10, intval($input[$key] ?? $default)));
                    break;

                case 'primary_color':
                    $validated[$key] = sanitize_hex_color($input[$key] ?? $default);
                    break;

                default:
                    $validated[$key] = sanitize_text_field($input[$key] ?? $default);
            }
        }

        return $validated;
    }

    /**
     * Get a specific option
     */
    public function get_option(string $key) {
        return $this->options[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    /**
     * Render the settings page
     */
    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('dialogpro_options');
                do_settings_sections('dialogpro-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}