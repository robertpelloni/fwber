<?php

namespace App\Services;

use App\Models\UserProfile;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class VectorService
{
    const INDEX_NAME = 'user:profile:idx';
    const PREFIX = 'user:profile:';

    /**
     * Initialize the RediSearch index if it doesn't exist.
     */
    public function initializeIndex()
    {
        try {
            Redis::command('FT.INFO', [self::INDEX_NAME]);
        } catch (\Exception $e) {
            // Index likely doesn't exist, create it
            Log::info("Creating RediSearch index: " . self::INDEX_NAME);
            
            // Create index on Hash with prefix 'user:profile:'
            // Fields: 
            // - embedding: VECTOR (FLAT, 1536 dims, FLOAT32)
            // - gender: TAG
            // - age: NUMERIC
            Redis::command('FT.CREATE', [
                self::INDEX_NAME,
                'ON', 'HASH',
                'PREFIX', '1', self::PREFIX,
                'SCHEMA',
                'embedding', 'VECTOR', 'FLAT', '6', 'TYPE', 'FLOAT32', 'DIM', '1536', 'DISTANCE_METRIC', 'COSINE',
                'gender', 'TAG',
                'age', 'NUMERIC'
            ]);
        }
    }

    /**
     * Store a user profile's embedding in Redis.
     */
    public function storeProfile(UserProfile $profile)
    {
        $text = $this->formatProfileForEmbedding($profile);
        $embedding = $this->generateEmbedding($text);

        if (empty($embedding)) {
            Log::error("Failed to generate embedding for profile {$profile->id}");
            return;
        }

        $key = self::PREFIX . $profile->user_id;

        // Pack the float array into a binary string for Redis
        $packed = pack('f*', ...$embedding);

        // Store in Redis Hash
        Redis::hmset($key, [
            'embedding' => $packed,
            'gender' => $profile->gender ?? 'unknown',
            'age' => $profile->age ?? 0,
            'user_id' => $profile->user_id
        ]);
        
        Log::info("Stored embedding for user {$profile->user_id}");
    }

    /**
     * Search for similar profiles.
     */
    public function search(array $vector, int $limit = 10, array $filters = []): array
    {
        $packed = pack('f*', ...$vector);

        $query = "*=>[KNN $limit @embedding \$blob AS score]";
        $params = ['blob' => $packed];

        // Add filters to query if needed (e.g., @gender:{male} @age:[18 30])
        // For now, simple KNN
        
        try {
            $result = Redis::command('FT.SEARCH', [
                self::INDEX_NAME,
                $query,
                'PARAMS', '2', 'blob', $packed,
                'SORTBY', 'score',
                'DIALECT', '2'
            ]);

            // Parse result
            // Result format: [count, key1, [field1, val1, ...], key2, ...]
            // Or with DIALECT 2? Need to check exact format return by phpredis
            
            return $this->parseSearchResults($result);

        } catch (\Exception $e) {
            Log::error("Vector search failed: " . $e->getMessage());
            return [];
        }
    }

    private function generateEmbedding(string $text): array
    {
        try {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            return $response->embeddings[0]->embedding;
        } catch (\Exception $e) {
            Log::error("OpenAI Embedding failed: " . $e->getMessage());
            // In dev/mock mode, return a random vector?
            if (config('app.env') === 'local' && !config('openai.api_key')) {
                return array_fill(0, 1536, 0.1);
            }
            return [];
        }
    }

    private function formatProfileForEmbedding(UserProfile $profile): string
    {
        // Combine bio, preferences, and key traits into a text blob
        $parts = [];
        if ($profile->bio) $parts[] = "Bio: {$profile->bio}";
        if ($profile->preferences) {
            $prefs = is_array($profile->preferences) ? json_encode($profile->preferences) : $profile->preferences;
            $parts[] = "Preferences: {$prefs}";
        }
        if ($profile->political_views) $parts[] = "Politics: {$profile->political_views}";
        if ($profile->religion) $parts[] = "Religion: {$profile->religion}";
        
        return implode(". ", $parts);
    }

    private function parseSearchResults(array $result): array
    {
        // First element is total count
        $count = array_shift($result);
        $hits = [];

        // Loop through pairs (key, fields) or (key, score, fields) depending on flags
        // With standard FT.SEARCH and no WITHSCORES, it might be key, fields...
        // But we asked for score in query "AS score"
        
        // Let's assume standard behavior for now and refine via debugging if needed
        for ($i = 0; $i < count($result); $i += 2) {
            $key = $result[$i];
            $fields = $result[$i+1]; // Array of field/values
            
            // Extract user_id from key or fields
            $userId = str_replace(self::PREFIX, '', $key);
            
            // Extract score if present in fields
            // ...
            
            $hits[] = [
                'user_id' => $userId,
                // 'score' => ...
            ];
        }

        return $hits;
    }
}
