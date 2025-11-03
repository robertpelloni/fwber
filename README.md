# FWBer.me (aka FWBer.com) – an open-source, privacy-first hookup platform

This is the PHP source code to FWBer, an open-source Adult Match website. Its goal is to replace the defunct Craigslist Casual Encounters and sites like AdultFriendFinder with a completely free and open-source replacement that improves upon the concept.

## Supports all types of lifestyles and preferences

Men, women, couples, groups, transgender, crossdressers, straight, gay, and bisexual lifestyles are supported. More preferences to come in the future.

## Match by dozens of specific sexual interests and fetishes

Find exactly who you are looking for, no matter how kinky. More fetishes and kinks can be easily added by the community.

## Automatic avatar creation based on your attributes

You don't need a public picture. We make one for you. Create your FWBer avatar by filling out your profile. This levels the playing field for everyone, gives an idea of what to expect, and discourages bots with fake pictures.

## No searching. Automatic matches based on sexual interests

No digging through hundreds of profiles. We find you and send you to each other. You are alerted instantly when someone new signs up who matches your interests.

## Privacy comes first. We only show your profile to your matches

Your pictures always remain private until you agree to show them. Private pictures are only shown to matches you authorize. Public pictures are shown to potential matches only. Share as much or as little information as you want.

## Messaging is done off-site using your preferred tools

We don't have a built-in messenger; we only share your contact information with your authorized matches. You communicate through email, text, phone, Skype, Kik—whatever you decide. That keeps your data safe, protects us from liability, and uses higher-quality tools than an in-built messenger. We hook you up; the rest is up to you.

## Open source and fully transparent

Verify your data is stored properly and your secrets are safe. Suggest new features or add them yourself. FWBer is licensed under the AGPL v3.

---

# FWBer.me Modernization Project

This document outlines the current state of the FWBer.me application following a comprehensive security and architectural overhaul. It is intended as a handoff document for future development.

## 1. Project Overview

The original FWBer.me application was a legacy PHP project from 2011. The primary goal of this modernization effort was to address critical security vulnerabilities and refactor the core application to use modern, professional-grade development practices. This initial overhaul is now complete.

The application is now built on a secure, object-oriented foundation, making it stable, maintainable, and ready for future feature development and eventual migration to a full Laravel/Next.js stack.

## 2. Current State of the Application

The application is in a stable, functional, and secure state. The core user-facing features have been completely rewritten.

- **Technology Stack:** PHP 8+, PDO for database access, Composer for dependency management.
- **Key Libraries:** `phpmailer/phpmailer`, `vlucas/phpdotenv`.
- **Modernized Pages:** `index.php`, `signin.php`, `signout.php`, `forgot-password.php`, `settings.php`, `profile.php`, `edit-profile.php`, `matches.php`, `manage-pics.php`, `contact.php`.

## 3. Key Architectural Components

The new architecture is designed to be modular and secure, with a clear separation of concerns.

### `_init.php`
This is the new central bootstrap file for the application. It is responsible for:
1. Loading the Composer autoloader.
2. Loading environment variables (API keys, etc.) from the `.env` file.
3. Establishing the secure PDO database connection (`_db.php`).
4. Initializing the core manager classes (`SecurityManager`, `ProfileManager`, etc.).

### `_db.php`
This file contains the single source of truth for the application's database connection, using PDO for secure, modern database interactions.

### Manager Classes
- **`SecurityManager.php`**: The heart of the application's security. It handles:
  - **Password Hashing:** Using the modern `Argon2ID` algorithm.
  - **Session Management:** Secure, database-driven session validation.
  - **CSRF Protection:** Generation and validation of single-use CSRF tokens for all forms.
  - **Rate Limiting:** Protection against brute-force attacks on authentication endpoints.
- **`ProfileManager.php`**: A robust class for managing all user profile data. It intelligently handles data for both the `users` and `user_preferences` tables, making the profile system easily extensible.
- **`PhotoManager.php`**: A secure class for handling all aspects of photo management, including secure file uploads (with MIME type validation), secure file deletion, and database integration.

### API Endpoints (`/api/`)
The application now uses a modern API-driven approach for dynamic frontend features. All API endpoints are secure and use the manager classes to perform their actions.

## 4. Security Hardening Summary

A comprehensive security overhaul was completed, addressing all critical vulnerabilities identified during the code review process.

- **✅ CSRF Protection:** All forms are now protected by single-use CSRF tokens, validated by the `SecurityManager`.
- **✅ Rate Limiting:** The `signin.php` and `forgot-password.php` pages are now protected from brute-force and spam attacks using IP-based rate limiting.
- **✅ Secure HTTPS:** The application now uses a robust `isSecure()` function to reliably detect and enforce secure HTTPS connections.
- **✅ Modern Authentication:** The old MD5-based password system has been completely replaced with `Argon2ID` hashing and secure, database-backed sessions.
- **✅ SQL Injection Prevention:** All database queries have been migrated to PDO with prepared statements.

## 5. Development Environment Setup

To get the application running locally, follow these steps:

1. **Clone the Repository:** Clone the project to your local machine (e.g., into `C:/xampp/htdocs`).
2. **Install Dependencies:** Open a terminal in the project root and run `composer install`.
3. **Create `.env` File:** Create a `.env` file in the project root and add your secret API keys. Use the following template:
   ```
   REPLICATE_API_TOKEN="YOUR_REPLICATE_API_TOKEN_HERE"
   OPENAI_API_KEY="YOUR_OPENAI_API_KEY_HERE"
   ```
4. **Set Up the Database:**
   - In phpMyAdmin, create a new user: `fwber` with password `Temppass0!` (or your preferred password, updated in `_secrets.php`).
   - When creating the user, check the box to **"Create database with same name and grant all privileges."**
   - Navigate to the newly created `fwber` database and use the "Import" tab to run the `setup-database.sql` script.

## 6. The Path to Launch (Next Steps)

The application is now ready for the final pre-launch phase. The following tasks remain:

1. **Complete the Profile Form:** This is the #1 priority. The `profile-form.php` file needs to be updated to include all the detailed preference fields from the original design. The backend `ProfileManager` is already built to handle this data automatically.

2. **Thorough End-to-End Testing:** Once the profile form is complete, the entire application must be tested systematically to find and fix any remaining bugs.

3. **Final Polish:** Modernize any remaining minor pages and implement the placeholder AI/ML logic in the `AIMatchingEngine` when ready.

## 7. A Note on Project History

This project was modernized in a unique collaboration between a human developer and multiple AI assistants (Gemini and Claude). The development process was iterative and conversational, which is reflected in the evolution of the codebase. This `README.md` serves as the canonical summary of the project's current state, superseding any previous conversational logs.

## Migration & Diagnostics (Legacy Matcher Compatibility)

To ensure the legacy matcher works with the new profile form:

1) Enable debug and start the app
- Copy and edit your environment
```cmd
copy .env.example .env
notepad .env
```
- Set `DEBUG_MODE=true`, then start a local server (or use your Apache/XAMPP)
```cmd
php -S 127.0.0.1:8000
```

2) Apply the migration (login required)
- Visit: `http://127.0.0.1:8000/scripts/apply_migration_web.php`
- Expected: `Applied N migration statements successfully.`
- If you see `Forbidden`, ensure you are logged in and `DEBUG_MODE=true`.

3) Verify mirrors and columns
- Visit: `http://127.0.0.1:8000/scripts/profile_diagnostics.php`
- Confirm: no missing columns and b_* mirrors appear in users after saving your profile.

4) Run a quick smoke test
- Create two users, set complementary preferences, and confirm matches appear.

5) Disable debug for safety
```cmd
notepad .env
```
Set `DEBUG_MODE=false`


## CLI Migration (optional)
If PHP and MySQL CLIs are available, you can run:
```cmd
php scripts\apply_migration.php
```
Or:
```cmd
mysql -h localhost -u fwber -p fwber < db\migrations\2025-10-11-legacy-matcher-compat.sql
```
