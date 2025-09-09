<?php
/*
    AI-Enhanced Matching Engine
    Uses machine learning algorithms and user behavior to improve match quality
*/

require_once('avatar-generator.php');

class AIMatchingEngine {
    
    private $db;
    private $avatarGenerator;
    private $weights = [
        'location_proximity' => 0.25,
        'age_compatibility' => 0.20,
        'interests_overlap' => 0.20,
        'activity_pattern' => 0.15,
        'interaction_history' => 0.10,
        'avatar_similarity' => 0.10
    ];
    
    public function __construct($database, $config = []) {
        $this->db = $database;
        $this->avatarGenerator = new AvatarGenerator($config['avatar_provider'] ?? 'local', $config);
        
        // Allow custom weights
        if (isset($config['weights'])) {
            $this->weights = array_merge($this->weights, $config['weights']);
        }
    }
    
    /**
     * Find matches for a user using AI-enhanced algorithm
     */
    public function findMatches($userId, $limit = 20, $filters = []) {
        $userProfile = $this->getUserProfile($userId);
        if (!$userProfile) {
            return [];
        }
        
        // Get potential candidates
        $candidates = $this->getCandidates($userId, $filters);
        
        // Score each candidate
        $scoredMatches = [];
        foreach ($candidates as $candidate) {
            $score = $this->calculateMatchScore($userProfile, $candidate);
            if ($score > 0.3) { // Minimum threshold
                $scoredMatches[] = [
                    'user' => $candidate,
                    'score' => $score,
                    'breakdown' => $this->getScoreBreakdown($userProfile, $candidate)
                ];
            }
        }
        
        // Sort by score and apply machine learning adjustments
        usort($scoredMatches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Apply machine learning refinements
        $refinedMatches = $this->applyMLAdjustments($userId, $scoredMatches);
        
        return array_slice($refinedMatches, 0, $limit);
    }
    
    /**
     * Calculate comprehensive match score
     */
    private function calculateMatchScore($user, $candidate) {
        $scores = [];
        
        // Location proximity
        $scores['location_proximity'] = $this->calculateLocationScore($user, $candidate);
        
        // Age compatibility
        $scores['age_compatibility'] = $this->calculateAgeScore($user, $candidate);
        
        // Interests overlap
        $scores['interests_overlap'] = $this->calculateInterestsScore($user, $candidate);
        
        // Activity patterns
        $scores['activity_pattern'] = $this->calculateActivityScore($user, $candidate);
        
        // Interaction history
        $scores['interaction_history'] = $this->calculateInteractionScore($user, $candidate);
        
        // Avatar/physical compatibility
        $scores['avatar_similarity'] = $this->calculateAvatarScore($user, $candidate);
        
        // Weighted total
        $totalScore = 0;
        foreach ($scores as $metric => $score) {
            $totalScore += $score * $this->weights[$metric];
        }
        
        return min($totalScore, 1.0);
    }
    
    private function calculateLocationScore($user, $candidate) {
        if (!isset($user['latitude']) || !isset($candidate['latitude'])) {
            return 0.5; // Neutral score if location unknown
        }
        
        $distance = $this->calculateDistance(
            $user['latitude'], $user['longitude'],
            $candidate['latitude'], $candidate['longitude']
        );
        
        // Score based on distance (closer = higher score)
        if ($distance <= 1) return 1.0;      // Within 1km
        if ($distance <= 5) return 0.9;      // Within 5km
        if ($distance <= 10) return 0.8;     // Within 10km
        if ($distance <= 25) return 0.6;     // Within 25km
        if ($distance <= 50) return 0.4;     // Within 50km
        if ($distance <= 100) return 0.2;    // Within 100km
        
        return 0.1; // Beyond 100km
    }
    
    private function calculateAgeScore($user, $candidate) {
        $userAge = $user['age'];
        $candidateAge = $candidate['age'];
        $ageDiff = abs($userAge - $candidateAge);
        
        // Check age preferences
        $userMinAge = $user['age_preference_min'] ?? ($userAge - 10);
        $userMaxAge = $user['age_preference_max'] ?? ($userAge + 10);
        $candidateMinAge = $candidate['age_preference_min'] ?? ($candidateAge - 10);
        $candidateMaxAge = $candidate['age_preference_max'] ?? ($candidateAge + 10);
        
        // Must be within both users' age preferences
        if ($candidateAge < $userMinAge || $candidateAge > $userMaxAge) return 0;
        if ($userAge < $candidateMinAge || $userAge > $candidateMaxAge) return 0;
        
        // Score based on age difference
        if ($ageDiff <= 2) return 1.0;
        if ($ageDiff <= 5) return 0.8;
        if ($ageDiff <= 10) return 0.6;
        if ($ageDiff <= 15) return 0.4;
        
        return 0.2;
    }
    
    private function calculateInterestsScore($user, $candidate) {
        $userInterests = $this->parseInterests($user['interests'] ?? '');
        $candidateInterests = $this->parseInterests($candidate['interests'] ?? '');
        
        if (empty($userInterests) || empty($candidateInterests)) {
            return 0.5; // Neutral if no interests listed
        }
        
        $commonInterests = array_intersect($userInterests, $candidateInterests);
        $totalInterests = array_unique(array_merge($userInterests, $candidateInterests));
        
        $overlapRatio = count($commonInterests) / count($totalInterests);
        
        // Bonus for sexual compatibility
        $sexualCompatibility = $this->calculateSexualCompatibility($user, $candidate);
        
        return min($overlapRatio + $sexualCompatibility * 0.3, 1.0);
    }
    
    private function calculateActivityScore($user, $candidate) {
        $userLastOnline = strtotime($user['last_online']);
        $candidateLastOnline = strtotime($candidate['last_online']);
        $now = time();
        
        $userActivityScore = $this->getActivityScore($now - $userLastOnline);
        $candidateActivityScore = $this->getActivityScore($now - $candidateLastOnline);
        
        // Users with similar activity patterns score higher
        $similarityBonus = 1 - abs($userActivityScore - $candidateActivityScore);
        
        return ($userActivityScore + $candidateActivityScore) / 2 * $similarityBonus;
    }
    
    private function getActivityScore($timeSinceOnline) {
        $hours = $timeSinceOnline / 3600;
        
        if ($hours <= 1) return 1.0;      // Online within 1 hour
        if ($hours <= 6) return 0.9;      // Within 6 hours
        if ($hours <= 24) return 0.7;     // Within 24 hours
        if ($hours <= 72) return 0.5;     // Within 3 days
        if ($hours <= 168) return 0.3;    // Within 1 week
        
        return 0.1; // More than 1 week
    }
    
    private function calculateInteractionScore($user, $candidate) {
        // Check if users have interacted before
        $interactions = $this->getUserInteractions($user['id'], $candidate['id']);
        
        if (empty($interactions)) {
            return 0.5; // Neutral for new potential matches
        }
        
        $positiveInteractions = 0;
        $totalInteractions = count($interactions);
        
        foreach ($interactions as $interaction) {
            if (in_array($interaction['type'], ['like', 'super_like', 'message', 'match'])) {
                $positiveInteractions++;
            }
        }
        
        return $totalInteractions > 0 ? $positiveInteractions / $totalInteractions : 0.5;
    }
    
    private function calculateAvatarScore($user, $candidate) {
        // Compare avatar/physical preferences
        $score = 0.5; // Base score
        
        // Body type preferences
        if (isset($user['body_type_preference']) && isset($candidate['body_type'])) {
            $preferences = explode(',', $user['body_type_preference']);
            if (in_array($candidate['body_type'], $preferences)) {
                $score += 0.3;
            }
        }
        
        // Hair color preferences
        if (isset($user['hair_color_preference']) && isset($candidate['hair_color'])) {
            $preferences = explode(',', $user['hair_color_preference']);
            if (in_array($candidate['hair_color'], $preferences)) {
                $score += 0.2;
            }
        }
        
        return min($score, 1.0);
    }
    
    private function calculateSexualCompatibility($user, $candidate) {
        $userPrefs = $this->getSexualPreferences($user['id']);
        $candidatePrefs = $this->getSexualPreferences($candidate['id']);
        
        if (empty($userPrefs) || empty($candidatePrefs)) {
            return 0.5;
        }
        
        $compatibility = 0;
        $factors = 0;
        
        // Check mutual interests
        foreach ($userPrefs as $pref => $value) {
            if (isset($candidatePrefs[$pref])) {
                if ($value === $candidatePrefs[$pref]) {
                    $compatibility += 1;
                } elseif (abs($value - $candidatePrefs[$pref]) <= 1) {
                    $compatibility += 0.5;
                }
                $factors++;
            }
        }
        
        return $factors > 0 ? $compatibility / $factors : 0.5;
    }
    
    /**
     * Apply machine learning adjustments based on user behavior
     */
    private function applyMLAdjustments($userId, $matches) {
        $userBehavior = $this->getUserBehavior($userId);
        
        foreach ($matches as &$match) {
            $candidateId = $match['user']['id'];
            
            // Adjust based on historical success rates
            $successRate = $this->getSuccessRate($userId, $candidateId);
            $match['score'] *= (1 + $successRate * 0.2);
            
            // Adjust based on user's swipe patterns
            $swipePattern = $this->analyzeSwipePattern($userId, $match['user']);
            $match['score'] *= (1 + $swipePattern * 0.1);
            
            // Adjust based on time of day/week patterns
            $timePattern = $this->analyzeTimePattern($userId);
            $match['score'] *= (1 + $timePattern * 0.05);
            
            // Ensure score doesn't exceed 1.0
            $match['score'] = min($match['score'], 1.0);
        }
        
        // Re-sort after adjustments
        usort($matches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $matches;
    }
    
    /**
     * Generate or update user avatar
     */
    public function generateUserAvatar($userId, $options = []) {
        $userProfile = $this->getUserProfile($userId);
        if (!$userProfile) {
            return ['success' => false, 'error' => 'User profile not found'];
        }
        
        try {
            $result = $this->avatarGenerator->generateAvatar($userProfile, $options);
            
            if ($result['success']) {
                // Save avatar URL to user profile
                $this->updateUserAvatar($userId, $result['image_url']);
                
                return [
                    'success' => true,
                    'avatar_url' => $result['image_url'],
                    'provider' => $result['provider']
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Helper methods
    private function getCandidates($userId, $filters = []) {
        $sql = "SELECT * FROM users WHERE id != ? AND active = 1";
        $params = [$userId];
        
        // Apply filters
        if (isset($filters['max_distance']) && isset($filters['user_lat']) && isset($filters['user_lng'])) {
            $sql .= " AND (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                     cos(radians(longitude) - radians(?)) + 
                     sin(radians(?)) * sin(radians(latitude)))) <= ?";
            $params = array_merge($params, [$filters['user_lat'], $filters['user_lng'], $filters['user_lat'], $filters['max_distance']]);
        }
        
        if (isset($filters['min_age'])) {
            $sql .= " AND age >= ?";
            $params[] = $filters['min_age'];
        }
        
        if (isset($filters['max_age'])) {
            $sql .= " AND age <= ?";
            $params[] = $filters['max_age'];
        }
        
        $sql .= " ORDER BY last_online DESC LIMIT 200";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserProfile($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    private function parseInterests($interests) {
        return array_filter(array_map('trim', explode(',', strtolower($interests))));
    }
    
    private function getSexualPreferences($userId) {
        $stmt = $this->db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $prefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $preferences = [];
        foreach ($prefs as $pref) {
            $preferences[$pref['preference_key']] = $pref['preference_value'];
        }
        
        return $preferences;
    }
    
    private function getUserInteractions($userId1, $userId2) {
        $stmt = $this->db->prepare("
            SELECT * FROM interactions 
            WHERE (user_id = ? AND target_user_id = ?) 
            OR (user_id = ? AND target_user_id = ?)
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserBehavior($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM user_behavior 
            WHERE user_id = ? 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    private function getSuccessRate($userId, $candidateId) {
        // Calculate success rate based on similar user interactions
        return 0.5; // Placeholder
    }
    
    private function analyzeSwipePattern($userId, $candidate) {
        // Analyze user's historical swipe patterns
        return 0.0; // Placeholder
    }
    
    private function analyzeTimePattern($userId) {
        // Analyze when user is most active
        return 0.0; // Placeholder
    }
    
    private function updateUserAvatar($userId, $avatarUrl) {
        $stmt = $this->db->prepare("UPDATE users SET avatar_url = ?, avatar_updated_at = NOW() WHERE id = ?");
        $stmt->execute([$avatarUrl, $userId]);
    }
    
    private function getScoreBreakdown($user, $candidate) {
        return [
            'location' => $this->calculateLocationScore($user, $candidate),
            'age' => $this->calculateAgeScore($user, $candidate),
            'interests' => $this->calculateInterestsScore($user, $candidate),
            'activity' => $this->calculateActivityScore($user, $candidate),
            'interaction' => $this->calculateInteractionScore($user, $candidate),
            'avatar' => $this->calculateAvatarScore($user, $candidate)
        ];
    }
}
?>