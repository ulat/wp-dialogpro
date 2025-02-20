# Project Overview
WordPress Plugin for Chatbot Frontend Integration
Connects to existing backend service
Primary focus on user interface and WordPress integration
Target WordPress versions: 6.4+
PHP versions: 8.3 and 8.4

# Key Technical Requirements:
## Frontend Components:
 - Chat button (configurable position)
 - Responsive chat window (default 20% width)
 - User/Bot message interface
 - Session-based chat history
 - Token limit management (default 8500)
 - Error handling and connection status indicators

## Configuration Features:
 - Font customization (family, size)
 - Color schemes (primary, secondary, highlight colors)
 - Chat window styling
 - Avatar icons (Material Icons integration)
 - Position settings
 - Welcome message
 - Token limits
 - Logging levels

## Security & Compliance:
 - WCAG compliance required
 - Cross-site scripting protection
 - Least privilege principles
 - Data protection standards
 - Backend authentication via bearer token

## Technical Integration:
 - Cookie-based session management
 - Responsive design
 - Multi-language support (minimum: German, English)
 - Backend API integration
 - Error logging system

### Development Process:
 - GitHub repository
 - YouTrack for issue tracking
 - Weekly code reviews
 - CI/CD via GitHub Actions
 - Unit and integration testing
 - Documentation in German/English (Markdown)

## Suggested Additional Details for Knowledge Base:
### Development Environment Setup:
 - Recommended local WordPress development environment
 - Required PHP extensions
 - Node.js version for build tools (if needed)
 - Testing environment configuration
### Code Organization:
 - Plugin directory structure
 - Naming conventions
 - Documentation standards
 - Asset management approach
### Testing Strategy:
 - Unit testing framework choice (PHPUnit?)
 - Integration testing approach
 - API mocking strategy
 - Browser testing requirements
### Security Checklist:
 - WordPress security best practices
 - Data sanitization requirements
 - CSRF protection
 - XSS prevention measures
 - Performance Considerations:
 - Asset optimization
 - Cache strategy
 - Database interactions
 - API call optimization