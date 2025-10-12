<?php
/**
 * SQLite Database Adapter for FWBer Testing
 * Provides SQLite-compatible database schema and connection
 */

class TestDatabase {
    private $pdo;
    private $dbFile = 'fwber-test.db';

    public function __construct() {
        try {
            $this->pdo = new PDO('sqlite:' . $this->dbFile);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->createTables();
        } catch (PDOException $e) {
            throw new Exception("SQLite connection failed: " . $e->getMessage());
        }
    }

    private function createTables() {
        // Users table (SQLite compatible version of MySQL schema)
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

                -- Profile information
                age INTEGER,
                gender TEXT CHECK(gender IN ('male','female','non-binary','trans-male','trans-female','other')),
                seeking_gender TEXT CHECK(seeking_gender IN ('male','female','non-binary','trans-male','trans-female','any')),
                relationship_type TEXT CHECK(relationship_type IN ('casual','relationship','friends','hookup','any')),

                -- Physical attributes for avatar generation
                hair_color TEXT,
                hair_style TEXT,
                eye_color TEXT,
                ethnicity TEXT,
                body_type TEXT,
                height INTEGER,
                interests TEXT,

                -- Location
                latitude REAL,
                longitude REAL,
                location_updated_at TEXT,
                city TEXT,
                state TEXT,
                country TEXT,
                zip_code TEXT,

                -- Preferences
                age_preference_min INTEGER DEFAULT 18,
                age_preference_max INTEGER DEFAULT 99,
                max_distance INTEGER DEFAULT 50,

                -- Gender-specific physical attributes
                pubicHair TEXT,
                penisSize TEXT,
                bodyHair TEXT,
                breastSize TEXT,
                wantAgeFrom INTEGER DEFAULT 18,
                wantAgeTo INTEGER DEFAULT 99
            )
        ");

        // User preferences table (key-value store for 100+ preference flags)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS user_preferences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                preference_name TEXT NOT NULL,
                preference_value INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                UNIQUE(user_id, preference_name)
            )
        ");

        // Venues table (simplified for testing)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS venues (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                latitude REAL,
                longitude REAL,
                city TEXT,
                state TEXT,
                country TEXT,
                active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Check-ins table (simplified for testing)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS checkins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                venue_id INTEGER,
                latitude REAL,
                longitude REAL,
                announcement TEXT,
                expires_at TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // Create indexes for better performance
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_gender ON users(gender)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_location ON users(latitude, longitude)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_preferences_user ON user_preferences(user_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_checkins_user ON checkins(user_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_checkins_expires ON checkins(expires_at)");
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function resetDatabase() {
        // Drop all tables (for clean test runs)
        $tables = ['checkins', 'user_preferences', 'venues', 'users'];
        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS $table");
        }
        $this->createTables();
    }

    public function getStats() {
        $stats = [];
        $tables = ['users', 'user_preferences', 'venues', 'checkins'];

        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM $table");
            $stats[$table] = $stmt->fetch()['count'];
        }

        return $stats;
    }
}

// Global test database instance
$testDb = new TestDatabase();
$pdo = $testDb->getPdo();

// Make PDO globally available for existing code
$GLOBALS['pdo'] = $pdo;
?>
