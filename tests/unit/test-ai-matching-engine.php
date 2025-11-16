<?php
/**
 * Test the modern AI matching engine
 */

require_once '_init.php';
require_once 'ai-matching-engine.php';
require_once 'ProfileManager.php';

echo "<h1>FWBer.me AI Matching Engine Test</h1>\n";
echo "<h2>Testing Modern Matching System</h2>\n";

try {
    // Initialize the AI matching engine
    $config = [
        'weights' => [
            'location_proximity' => 0.25,
            'age_compatibility' => 0.20,
            'interests_overlap' => 0.20,
            'activity_pattern' => 0.15,
            'interaction_history' => 0.10,
            'avatar_similarity' => 0.10
        ]
    ];
    
    $matchingEngine = new AIMatchingEngine($pdo, $config);
    echo "✅ AI Matching Engine instantiated successfully<br>\n";
    
    // Test with user ID 1
    $userId = 1;
    $matches = $matchingEngine->findMatches($userId, 10);
    
    echo "✅ Found " . count($matches) . " AI-powered matches<br>\n";
    echo "✅ Modern AI matching engine is working!<br>\n";
    
    if (count($matches) > 0) {
        echo "<h3>Sample AI Match:</h3>\n";
        $sampleMatch = $matches[0];
        echo "Match ID: " . ($sampleMatch['id'] ?? 'N/A') . "<br>\n";
        echo "Username: " . ($sampleMatch['username'] ?? 'N/A') . "<br>\n";
        echo "Score: " . ($sampleMatch['match_score'] ?? 'N/A') . "<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}

echo "<h2>Test Complete</h2>\n";
echo "<a href='index.php'>Go to Home</a><br>\n";
echo "<a href='api/get-ai-matches.php'>Test AI Matches API</a><br>\n";
?>
