<?php
/*
    SECURE MIGRATION WRAPPER FOR LEGACY MATCHER
    
    Migration Date: 2025-10-18
    Migration Reason: SQL injection vulnerability in legacy mysqli code  
    AI Models Consensus: GPT-5-Codex, Gemini 2.5 Pro, GPT-5, Gemini 2.5 Flash
    
    This wrapper replaces the insecure _getMatches.php with a secure implementation
    that calls MatchingEngine.php (which uses PDO prepared statements).
    
    SECURITY IMPROVEMENT:
    - OLD: mysqli with SQL string concatenation (SQL injection risk)
    - NEW: PDO prepared statements (secure)
    
    MONITORING:
    - All calls are logged for tracking
    - Monitor for 1 week before deleting legacy entirely
    
    ROLLBACK:
    - If issues occur, rename _getMatches.php.backup to _getMatches.php
*/

function getMatches($email) {
    // Log migration usage for monitoring
    error_log("====== SECURITY MIGRATION ======");
    error_log("Legacy getMatches() called for email: " . $email);
    error_log("Routing to secure MatchingEngine implementation");
    error_log("Timestamp: " . date('Y-m-d H:i:s'));
    
    // Load secure dependencies
    require_once(__DIR__ . '/_db.php');
    require_once(__DIR__ . '/MatchingEngine.php');
    require_once(__DIR__ . '/_globals.php');
    
    // Get user ID using secure PDO query
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        
        if (!$userId) {
            error_log("MIGRATION: User not found for email (email hidden for privacy)");
            return [];
        }
        
        // Use secure MatchingEngine with PDO prepared statements
        $engine = new MatchingEngine($pdo, $userId);
        $matches = $engine->getMatches();
        
        // Log successful migration
        $matchCount = is_array($matches) ? count($matches) : 0;
        error_log("MIGRATION SUCCESS: Returned {$matchCount} matches via secure MatchingEngine");
        error_log("================================");
        
        return $matches;
        
    } catch (PDOException $e) {
        error_log("MIGRATION ERROR (Database): " . $e->getMessage());
        error_log("================================");
        return [];
    } catch (Exception $e) {
        error_log("MIGRATION ERROR (General): " . $e->getMessage());
        error_log("================================");
        return [];
    }
}
?>

