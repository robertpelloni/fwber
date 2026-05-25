# Code Style and Conventions

## PHP Code Style
- **PSR-12**: Follow PHP-FIG PSR-12 coding standard
- **Naming**: camelCase for variables, PascalCase for classes
- **Security**: Always use PDO prepared statements, never direct SQL
- **Error Handling**: Use try-catch blocks, log errors appropriately

## File Organization
- **Manager Classes**: SecurityManager, ProfileManager, PhotoManager
- **API Endpoints**: Located in `api/` directory
- **Configuration**: `_secrets.php` for sensitive data, `_db.php` for database
- **Bootstrap**: `_init.php` for application initialization

## Database Conventions
- **Tables**: snake_case naming (users, user_preferences, user_photos)
- **Columns**: snake_case naming (user_id, created_at, b_wantGender)
- **Indexes**: Descriptive names (idx_user_email, idx_user_location)

## Security Conventions
- **CSRF Protection**: All forms must include CSRF tokens
- **Input Validation**: Sanitize all user input
- **Password Hashing**: Use Argon2ID exclusively
- **Session Security**: Secure session configuration in _init.php

## API Conventions
- **RESTful**: Use proper HTTP methods (GET, POST, PUT, DELETE)
- **JSON Responses**: Consistent JSON response format
- **Error Handling**: Proper HTTP status codes
- **Authentication**: Session-based authentication

## Documentation
- **README.md**: Main project documentation
- **Markdown Files**: Implementation guides and specifications
- **Comments**: Inline comments for complex logic
- **Docblocks**: For all public methods and classes