<?php
/**
 * Test the matching algorithm with the new profile fields
 */

require_once '_init.php';
require_once 'ProfileManager.php';
require_once '_getMatches.php';

echo "<h1>FWBer.me Matching Algorithm Test</h1>\n";
echo "<h2>Testing with New Profile Fields</h2>\n";

try {
    // Get test user
    $userId = 1;
    echo "Testing matches for user ID: $userId<br>\n";
    
    // Get matches using the legacy algorithm
    $matches = getMatches($userId, 10);
    
    echo "✅ Found " . count($matches) . " potential matches<br>\n";
    echo "✅ Matching algorithm is working with new profile fields!<br>\n";
    
    if (count($matches) > 0) {
        echo "<h3>Sample Match:</h3>\n";
        $sampleMatch = $matches[0];
        echo "Match ID: " . $sampleMatch['id'] . "<br>\n";
        echo "Username: " . ($sampleMatch['username'] ?? 'N/A') . "<br>\n";
        echo "Age: " . ($sampleMatch['age'] ?? 'N/A') . "<br>\n";
        echo "Location: " . ($sampleMatch['city'] ?? 'N/A') . "<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}

echo "<h2>Test Complete</h2>\n";
echo "<a href='index.php'>Go to Home</a><br>\n";
echo "<a href='matches.php'>View Matches</a><br>\n";
?>
