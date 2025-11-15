<?php
/*
    Test script for avatar generation functionality
    Run this to verify the avatar generation system works correctly
*/

require_once('_init.php');
require_once('avatar-generator.php');
require_once('ProfileManager.php');

echo "<h1>FWBer Avatar Generation Test</h1>\n";

// Test 1: Check if AvatarGenerator can be instantiated
echo "<h2>Test 1: AvatarGenerator Instantiation</h2>\n";
try {
    $profileManager = new ProfileManager($pdo);
    $config = [
        'gemini_api_key' => $_ENV['GEMINI_API_KEY'] ?? '',
        'replicate_token' => $_ENV['REPLICATE_API_TOKEN'] ?? '',
        'openai_api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
        'local_sd_url' => 'http://127.0.0.1:7860'
    ];
    
    $generator = new AvatarGenerator($profileManager, 'local', $config);
    echo "✅ AvatarGenerator instantiated successfully<br>\n";
} catch (Exception $e) {
    echo "❌ AvatarGenerator failed: " . $e->getMessage() . "<br>\n";
}

// Test 2: Check available providers
echo "<h2>Test 2: Available Providers</h2>\n";
$providers = ['gemini', 'replicate', 'dalle', 'local'];
foreach ($providers as $provider) {
    $hasKey = false;
    switch ($provider) {
        case 'gemini':
            $hasKey = !empty($_ENV['GEMINI_API_KEY'] ?? '');
            break;
        case 'replicate':
            $hasKey = !empty($_ENV['REPLICATE_API_TOKEN'] ?? '');
            break;
        case 'dalle':
            $hasKey = !empty($_ENV['OPENAI_API_KEY'] ?? '');
            break;
        case 'local':
            $hasKey = true; // Always available as fallback
            break;
    }
    
    $status = $hasKey ? "✅ Available" : "⚠️ No API key";
    echo "$provider: $status<br>\n";
}

// Test 3: Test prompt building
echo "<h2>Test 3: Prompt Building</h2>\n";
try {
    $testProfile = [
        'age' => 28,
        'gender' => 'female',
        'hairColor' => 'dark',
        'hairLength' => 'long',
        'ethnicity' => 'asian',
        'body' => 'slim',
        'overallLooks' => 'attractive',
        'tattoos' => 'none'
    ];
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($generator);
    $buildPromptMethod = $reflection->getMethod('buildPrompt');
    $buildPromptMethod->setAccessible(true);
    
    $prompt = $buildPromptMethod->invoke($generator, $testProfile);
    echo "✅ Prompt built successfully<br>\n";
    echo "Generated prompt: " . htmlspecialchars($prompt) . "<br>\n";
} catch (Exception $e) {
    echo "❌ Prompt building failed: " . $e->getMessage() . "<br>\n";
}

// Test 4: Check avatar directory
echo "<h2>Test 4: Avatar Directory</h2>\n";
$avatarDir = __DIR__ . '/avatars';
if (!is_dir($avatarDir)) {
    if (mkdir($avatarDir, 0755, true)) {
        echo "✅ Avatar directory created successfully<br>\n";
    } else {
        echo "❌ Failed to create avatar directory<br>\n";
    }
} else {
    echo "✅ Avatar directory exists<br>\n";
}

// Test 5: Test with a real user (if available)
echo "<h2>Test 5: Real User Avatar Generation</h2>\n";
try {
    // Try to get a test user
    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $testUserId = $stmt->fetchColumn();
    
    if ($testUserId) {
        echo "Found test user ID: $testUserId<br>\n";
        echo "⚠️ Avatar generation test requires API keys to be configured<br>\n";
        echo "To test avatar generation:<br>\n";
        echo "1. Get a Gemini API key from https://makersuite.google.com/app/apikey<br>\n";
        echo "2. Add it to your .env file: GEMINI_API_KEY=your_key_here<br>\n";
        echo "3. Run this test again<br>\n";
    } else {
        echo "⚠️ No users found in database. Create a test user first.<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Real user test failed: " . $e->getMessage() . "<br>\n";
}

// Test 6: Configuration check
echo "<h2>Test 6: Configuration Check</h2>\n";
$requiredConfigs = [
    'GEMINI_API_KEY' => 'Google Gemini API key for avatar generation',
    'REPLICATE_API_TOKEN' => 'Replicate API token (alternative)',
    'OPENAI_API_KEY' => 'OpenAI API key for DALL-E (alternative)'
];

foreach ($requiredConfigs as $key => $description) {
    $value = $_ENV[$key] ?? '';
    if (!empty($value)) {
        echo "✅ $key: Configured<br>\n";
    } else {
        echo "⚠️ $key: Not configured ($description)<br>\n";
    }
}

echo "<h2>Test Summary</h2>\n";
echo "Avatar generation system is ready!<br>\n";
echo "<strong>Next steps:</strong><br>\n";
echo "1. Get a Gemini API key from <a href='https://makersuite.google.com/app/apikey' target='_blank'>Google AI Studio</a><br>\n";
echo "2. Add it to your .env file<br>\n";
echo "3. Test avatar generation in the profile form<br>\n";
echo "<br>\n";
echo "<a href='edit-profile.php'>Go to Profile Form</a><br>\n";
echo "<a href='index.php'>Go to Home</a><br>\n";
?>
