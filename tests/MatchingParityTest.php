<?php
/**
 * Matching Algorithm Parity Test Suite
 * 
 * AI Model: GPT-5-Codex (HIGH reasoning) - Simulated
 * Purpose: Verify secure MatchingEngine.php produces identical results to legacy _getMatches.php
 * Created: 2025-10-18
 * Phase: 1B - SQL Injection Fix Validation
 */

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../_db.php');
require_once(__DIR__ . '/../MatchingEngine.php');
require_once(__DIR__ . '/../_getMatches.php');
require_once(__DIR__ . '/../_globals.php');

class MatchingParityTest extends TestCase
{
    private $pdo;
    private $testUsers = [];
    
    protected function setUp(): void
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->createTestUsers();
    }
    
    protected function tearDown(): void
    {
        $this->cleanupTestUsers();
    }
    
    /**
     * Create test user fixtures with various preferences
     */
    private function createTestUsers()
    {
        // Test User 1: Male, 25, looking for females
        $this->testUsers[] = [
            'email' => 'test_male_1@test.com',
            'gender' => 'male',
            'age' => 25,
            'latitude' => 40.7128,
            'longitude' => -74.0060, // NYC
            'distance' => 'dist25m',
            'b_wantGenderWoman' => 1,
            'overallLooks' => 'attractive',
            'intelligence' => 'average'
        ];
        
        // Test User 2: Female, 23, looking for males
        $this->testUsers[] = [
            'email' => 'test_female_1@test.com',
            'gender' => 'female',
            'age' => 23,
            'latitude' => 40.7589,
            'longitude' => -73.9851, // NYC (close to user 1)
            'distance' => 'dist25m',
            'b_wantGenderMan' => 1,
            'overallLooks' => 'attractive',
            'intelligence' => 'average'
        ];
        
        // Test User 3: Female, 30, far away
        $this->testUsers[] = [
            'email' => 'test_female_2@test.com',
            'gender' => 'female',
            'age' => 30,
            'latitude' => 34.0522,
            'longitude' => -118.2437, // LA (far from user 1)
            'distance' => 'dist25m',
            'b_wantGenderMan' => 1,
            'overallLooks' => 'hottie',
            'intelligence' => 'genius'
        ];
        
        // Insert test users into database
        foreach ($this->testUsers as $user) {
            $this->insertTestUser($user);
        }
    }
    
    private function insertTestUser($userData)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, gender, age, latitude, longitude, distance, 
                              b_wantGenderMan, b_wantGenderWoman, overallLooks, intelligence,
                              active, email_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        
        $stmt->execute([
            $userData['email'],
            $userData['gender'],
            $userData['age'],
            $userData['latitude'],
            $userData['longitude'],
            $userData['distance'],
            $userData['b_wantGenderMan'] ?? 0,
            $userData['b_wantGenderWoman'] ?? 0,
            $userData['overallLooks'],
            $userData['intelligence']
        ]);
    }
    
    private function cleanupTestUsers()
    {
        foreach ($this->testUsers as $user) {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE email = ?");
            $stmt->execute([$user['email']]);
        }
    }
    
    /**
     * TEST 1: Basic parity - same results from both implementations
     */
    public function testBasicMatchingParity()
    {
        $email = 'test_male_1@test.com';
        
        // Get user ID for new implementation
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        
        // Get matches from legacy implementation
        $legacyMatches = getMatches($email);
        
        // Get matches from new secure implementation
        $engine = new MatchingEngine($this->pdo, $userId);
        $secureMatches = $engine->getMatches(50);
        
        // Both should return arrays
        $this->assertIsArray($legacyMatches, 'Legacy matcher should return array');
        $this->assertIsArray($secureMatches, 'Secure matcher should return array');
        
        // Extract user IDs for comparison (ignore scoring details)
        $legacyIds = array_column($legacyMatches, 'id');
        $secureIds = array_map(function($match) {
            return $match['id'] ?? $match->id ?? null;
        }, $secureMatches);
        
        // Sort for comparison (order might differ due to scoring)
        sort($legacyIds);
        sort($secureIds);
        
        // Should contain the same users
        $this->assertEquals(
            $legacyIds,
            $secureIds,
            'Legacy and secure matchers should return same users'
        );
    }
    
    /**
     * TEST 2: Gender filtering works correctly
     */
    public function testGenderFiltering()
    {
        $email = 'test_male_1@test.com';
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        
        $engine = new MatchingEngine($this->pdo, $userId);
        $matches = $engine->getMatches(50);
        
        // Male looking for females - should only get females
        foreach ($matches as $match) {
            $matchData = is_array($match) ? $match : (array)$match;
            $this->assertEquals(
                'female',
                $matchData['gender'],
                'Male user should only match with females'
            );
        }
    }
    
    /**
     * TEST 3: Distance filtering works
     */
    public function testDistanceFiltering()
    {
        $email = 'test_male_1@test.com';
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        
        $engine = new MatchingEngine($this->pdo, $userId);
        $matches = $engine->getMatches(50, ['max_distance' => 25]);
        
        // Should NOT include LA user (3000+ km away)
        foreach ($matches as $match) {
            $matchData = is_array($match) ? $match : (array)$match;
            $this->assertNotEquals(
                'test_female_2@test.com',
                $matchData['email'],
                'Should not match with users beyond max distance'
            );
        }
    }
    
    /**
     * TEST 4: Performance benchmark
     */
    public function testPerformanceAcceptable()
    {
        $email = 'test_male_1@test.com';
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        
        $engine = new MatchingEngine($this->pdo, $userId);
        
        $startTime = microtime(true);
        $matches = $engine->getMatches(50);
        $endTime = microtime(true);
        
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Should complete in under 500ms for reasonable dataset
        $this->assertLessThan(
            500,
            $duration,
            'Matching should complete in under 500ms'
        );
    }
    
    /**
     * TEST 5: No matches scenario
     */
    public function testNoMatches()
    {
        // Create incompatible user (male looking for males, but no gay males in test data)
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, gender, age, latitude, longitude, distance, 
                              b_wantGenderMan, active, email_verified, created_at)
            VALUES (?, 'male', 25, 40.7128, -74.0060, 'dist25m', 1, 1, 1, NOW())
        ");
        $stmt->execute(['test_no_matches@test.com']);
        
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(['test_no_matches@test.com']);
        $userId = $stmt->fetchColumn();
        
        $engine = new MatchingEngine($this->pdo, $userId);
        $matches = $engine->getMatches(50);
        
        // Should return empty array, not null or error
        $this->assertIsArray($matches, 'Should return array even with no matches');
        $this->assertEmpty($matches, 'Should return empty array when no compatible users');
        
        // Cleanup
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE email = ?");
        $stmt->execute(['test_no_matches@test.com']);
    }
    
    /**
     * TEST 6: Edge case - self-matching prevention
     */
    public function testNoSelfMatching()
    {
        $email = 'test_male_1@test.com';
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        
        $engine = new MatchingEngine($this->pdo, $userId);
        $matches = $engine->getMatches(50);
        
        // Should never match with self
        foreach ($matches as $match) {
            $matchData = is_array($match) ? $match : (array)$match;
            $this->assertNotEquals(
                $userId,
                $matchData['id'],
                'User should never match with themselves'
            );
        }
    }
}

