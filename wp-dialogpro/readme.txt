= Is it compatible with page builders? =

Yes, the plugin works with popular page builders including Elementor, Divi, and WPBakery Page Builder.

= Can I extend the functionality? =

Yes, DialogPro provides various filters and actions for developers to extend its functionality.

= How does token management work? =

The plugin tracks token usage per session with configurable limits. This helps manage API costs and prevents abuse.

= Is multi-language support included? =

Yes, DialogPro comes with English and German translations. Additional languages can be added using translation files.

== Screenshots ==

1. Chat interface in action
2. Admin settings panel
3. Customization options
4. Mobile view
5. Token management interface
6. Language settings
7. API configuration
8. Style customization

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of DialogPro. Requires WordPress 6.4+ and PHP 8.3+.

== Usage ==

= Basic Configuration =

1. Navigate to Settings > DialogPro
2. Enter your API endpoint and authentication token
3. Configure basic appearance settings
4. Save changes

= Advanced Customization =

The plugin provides several filters and actions for advanced customization:

```php
// Modify API request parameters
add_filter('dialogpro_api_params', function($params) {
    // Modify parameters
    return $params;
});

// Custom message processing
add_filter('dialogpro_process_message', function($message) {
    // Process message
    return $message;
});
```

= Template Customization =

Override the default templates by copying them to your theme:

1. Create a `dialogpro` directory in your theme
2. Copy templates from `/wp-content/plugins/wp-dialogpro/templates/`
3. Modify the templates as needed

= CSS Customization =

Add custom CSS using the WordPress customizer or your theme's stylesheet:

```css
.dialogpro-chat-window {
    /* Custom styles */
}
```

== Development ==

= GitHub Repository =

Development happens on GitHub. Feel free to contribute:

* Submit bug reports
* Propose new features
* Create pull requests

= Local Development =

1. Clone the repository
2. Install dependencies: `composer install`
3. Build assets: `npm install && npm run build`

= Testing =

Run tests using PHPUnit:

```bash
composer test
```

= Coding Standards =

The project follows WordPress coding standards. Check your code:

```bash
composer lint
```

== Documentation ==

= API Integration =

DialogPro expects the following API endpoint structure:

```json
{
    "message": "User message",
    "session_id": "unique-session-id",
    "timestamp": 1234567890
}
```

Expected response:

```json
{
    "response": "Bot response",
    "token_count": 123
}
```

= Hooks Reference =

Actions:
* `dialogpro_before_send_message`
* `dialogpro_after_send_message`
* `dialogpro_message_error`
* `dialogpro_session_start`
* `dialogpro_session_end`

Filters:
* `dialogpro_api_url`
* `dialogpro_api_headers`
* `dialogpro_message_content`
* `dialogpro_response_content`
* `dialogpro_token_limit`

= Session Management =

Sessions are handled using secure cookies with the following properties:
* HTTP-only
* Secure flag (requires HTTPS)
* SameSite attribute
* Configurable expiration

= Token Management =

Token usage is tracked per session:
* Configurable limits
* Reset options
* Usage analytics
* Overflow protection

== Support ==

= Official Support =

* WordPress.org plugin forums
* Documentation website
* Email support for premium users

= Contributing =

We welcome contributions:
1. Fork the repository
2. Create a feature branch
3. Submit a pull request

= Reporting Issues =

Please report bugs through:
* GitHub Issues
* WordPress.org plugin support forum
* Support email for premium users

== Credits ==

DialogPro uses the following open-source components:

* Material Icons by Google (Apache License 2.0)
* Various WordPress core components (GPL)

== License ==

DialogPro is licensed under the GPL v2 or later:

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

== Privacy Policy ==

DialogPro collects:
* Chat messages
* Session data
* Token usage statistics

Data is processed according to GDPR guidelines:
* Data minimization
* Purpose limitation
* Storage limitation
* User consent handling

For detailed information, visit our privacy policy page.

The readme.txt includes:
1. Basic plugin information
2. Feature descriptions
3. Installation instructions
4. FAQs
5. Usage documentation
6. Development guidelines
7. API documentation
8. Hook references
9. Privacy information
10. License details
