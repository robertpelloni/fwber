# FWBer Environment Assessment & Testing Strategy

**Date:** 2025-10-11
**PHP Version:** 8.3.26 ✅
**Database Drivers:** ❌ None available (PDO issue)

---

## Current Environment Status

### ✅ What's Working
- PHP 8.3.26 installed and functional
- File system access working
- All project files accessible

### ❌ What's Missing
- **PDO MySQL Driver:** Not available (`PDO::getAvailableDrivers()` returns empty array)
- **SQLite3 Extension:** Not available (`class_exists('SQLite3')` returns false)
- **MySQL Client:** Not in PATH (`mysql --version` fails)

### Root Cause Analysis
**Issue:** PHP installation compiled without database driver support
```bash
# Test results:
php -r "var_dump(PDO::getAvailableDrivers());"  # array(0) {}
php -r "require '_db.php';"  # "could not find driver" error
```

---

## Testing Strategy Options

### Option A: Full MySQL Setup (Recommended for Production Parity)
**Pros:** Complete feature testing, production environment match
**Cons:** 2-4 hours setup time

**Setup Steps:**
1. Install MySQL 8.0+ Server
2. Enable PDO MySQL extension in php.ini
3. Add MySQL to system PATH
4. Create fwber database and user
5. Import schema: `mysql -u fwber -p fwber < setup-database.sql`
6. Configure `_secrets.php` with real credentials

**Estimated Time:** 2-4 hours + testing

---

### Option B: SQLite Testing Environment (Alternative)
**Pros:** Faster setup (1-2 hours), tests core functionality
**Cons:** Some features may not work (venue check-ins, complex queries)

**Implementation Plan:**
1. Enable SQLite3 extension in PHP
2. Create SQLite-compatible database adapter
3. Convert MySQL schema to SQLite
4. Modify `_db.php` to use SQLite for testing
5. Run core profile form tests

**SQLite Schema Conversion:**
- Convert MySQL ENUMs to SQLite CHECK constraints
- Modify AUTO_INCREMENT to INTEGER PRIMARY KEY
- Adjust timestamp defaults for SQLite compatibility

---

### Option C: Code Review Only (Current State)
**Pros:** No setup required, comprehensive static analysis complete
**Cons:** Cannot test runtime behavior, database interactions, user flows

**Current Status:**
- ✅ Profile form implementation verified (static analysis)
- ✅ Security implementation verified
- ✅ Gender-specific logic verified
- ✅ Database schema reviewed
- ❌ Runtime testing impossible without database

---

## Recommended Approach: Option B (SQLite) for Quick Testing

### Why SQLite?
1. **Faster Setup:** Can be implemented in 1-2 hours
2. **Core Testing:** Can test profile form, user registration, basic matching
3. **Risk Mitigation:** Identifies major runtime issues before MySQL setup
4. **Incremental Progress:** Gets us closer to launch faster

### SQLite Implementation Plan

#### Step 1: Enable SQLite3 Extension
**Check php.ini for SQLite support:**
```bash
php -i | grep -i sqlite
# Should show: sqlite3 => enabled
```

**If not enabled, modify php.ini:**
```ini
extension=sqlite3
extension=pdo_sqlite
```

#### Step 2: Create SQLite Database Adapter
**Create:** `test-sqlite.php`
```php
<?php
// SQLite test database adapter
class TestDatabase {
    private $pdo;

    public function __construct() {
        $this->pdo = new PDO('sqlite:fwber-test.db');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createTables();
    }

    private function createTables() {
        // Convert MySQL schema to SQLite
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                username TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                password_salt TEXT NOT NULL,
                email_verified INTEGER DEFAULT 0,
                email_verification_token TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                last_online TEXT DEFAULT CURRENT_TIMESTAMP,
                active INTEGER DEFAULT 1,
                age INTEGER,
                gender TEXT CHECK(gender IN ('male','female','non-binary','trans-male','trans-female','other')),
                seeking_gender TEXT CHECK(seeking_gender IN ('male','female','non-binary','trans-male','trans-female','any')),
                relationship_type TEXT CHECK(relationship_type IN ('casual','relationship','friends','hookup','any')),
                hair_color TEXT,
                hair_style TEXT,
                eye_color TEXT,
                ethnicity TEXT,
                body_type TEXT,
                height INTEGER,
                interests TEXT,
                latitude REAL,
                longitude REAL,
                location_updated_at TEXT,
                city TEXT,
                state TEXT,
                country TEXT,
                zip_code TEXT,
                age_preference_min INTEGER DEFAULT 18,
                age_preference_max INTEGER DEFAULT 99,
                max_distance INTEGER DEFAULT 50,
                pubicHair TEXT,
                penisSize TEXT,
                bodyHair TEXT,
                breastSize TEXT,
                wantAgeFrom INTEGER DEFAULT 18,
                wantAgeTo INTEGER DEFAULT 99
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS user_preferences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                preference_name TEXT NOT NULL,
                preference_value INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
    }

    public function getPdo() {
        return $this->pdo;
    }
}

// Global test database instance
$testDb = new TestDatabase();
$pdo = $testDb->getPdo();
?>
```

#### Step 3: Modify _db.php for Testing
**Backup original _db.php:**
```bash
cp _db.php _db.php.backup
```

**Create test version:**
```php
<?php
// Test database connection using SQLite
require_once('_secrets.php');

// Use SQLite for testing
$dsn = "sqlite:fwber-test.db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);

    // Create tables if they don't exist (from setup-database.sql)
    require_once('test-sqlite.php');

} catch (\PDOException $e) {
    error_log($e->getMessage());
    throw new Exception("Database connection failed");
}
?>
```

#### Step 4: Run Tests with SQLite
1. Enable SQLite3 in PHP
2. Run test scripts: `php db/generate-test-users.php`
3. Execute manual E2E tests using TESTING_QUICKSTART.md
4. Document any SQLite-specific issues

---

## Setup Instructions for Each Option

### Option A: MySQL Setup (Complete Testing)

```bash
# 1. Download MySQL 8.0+ from mysql.com
# 2. Install with default settings
# 3. Add mysql/bin to system PATH
# 4. Start MySQL service

# 5. Create database and user
mysql -u root -p
CREATE DATABASE fwber;
CREATE USER 'fwber'@'localhost' IDENTIFIED BY 'Temppass0!';
GRANT ALL PRIVILEGES ON fwber.* TO 'fwber'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 6. Import schema
mysql -u fwber -p fwber < setup-database.sql

# 7. Configure _secrets.php
# Edit _secrets.php with real MySQL credentials

# 8. Test connection
php -r "require '_db.php'; echo 'MySQL connection successful!';"

# 9. Run tests
php db/generate-test-users.php
```

### Option B: SQLite Setup (Quick Testing)

```bash
# 1. Enable SQLite3 in php.ini (if not already enabled)
# Add these lines to php.ini:
extension=sqlite3
extension=pdo_sqlite

# 2. Restart PHP/web server

# 3. Test SQLite availability
php -r "echo class_exists('SQLite3') ? 'SQLite3 available' : 'SQLite3 not available';"

# 4. Create test database adapter (see above)

# 5. Run tests
php db/generate-test-users.php
```

### Option C: Code Review Only (No Setup)

```bash
# No setup required - continue with static analysis
# Review: test-results/2025-10-11-STATIC-CODE-ANALYSIS.md
```

---

## Risk Assessment

### Option A (MySQL) - LOW RISK
- **Pros:** Complete testing, production parity
- **Cons:** Setup time (2-4 hours)
- **Risk:** Environment configuration issues

### Option B (SQLite) - MEDIUM RISK
- **Pros:** Quick setup, core functionality testing
- **Cons:** Some features may not work, schema conversion needed
- **Risk:** Missing critical bugs in MySQL-specific features

### Option C (Code Review) - HIGH RISK
- **Pros:** No setup required
- **Cons:** No runtime testing
- **Risk:** Runtime bugs not caught, false confidence

---

## Recommendation: Start with Option B (SQLite)

**Rationale:**
1. **Quickest Path to Testing:** 1-2 hours vs 2-4 hours for MySQL
2. **Core Functionality Coverage:** Profile forms, user registration, basic matching
3. **Incremental Progress:** Find major issues early
4. **Fallback Option:** Can still set up MySQL if SQLite testing reveals issues

**Next Steps:**
1. Implement SQLite testing environment
2. Run core E2E tests (profile forms, user registration)
3. Assess if SQLite provides sufficient test coverage
4. Set up MySQL for production if needed

---

## Timeline Estimate

### Option B Timeline (Recommended)
- **Hour 1:** Enable SQLite3, create test adapter
- **Hour 2:** Run test user generation and basic tests
- **Hours 3-4:** Execute E2E profile form tests
- **Hour 5:** Document results and plan next steps

### Option A Timeline (If SQLite insufficient)
- **Hours 1-2:** MySQL installation and configuration
- **Hour 3:** Schema import and testing
- **Hours 4-6:** Full E2E test suite

---

## Success Criteria

**For SQLite Testing:**
- ✅ Test users created successfully
- ✅ Profile form save/reload works
- ✅ Gender-specific fields show/hide correctly
- ✅ Basic matching algorithm functions
- ✅ No critical runtime errors

**For MySQL Testing (if needed):**
- ✅ All SQLite criteria met
- ✅ Venue check-in functionality works
- ✅ Complex queries (location matching) work
- ✅ Production environment parity

---

**Ready to implement your chosen testing strategy!** 🚀

Which option would you like to proceed with? I'm ready to implement Option B (SQLite) immediately if you'd like to get testing started quickly.
