<?php
/*
    AI-Enhanced Matching Engine
    Uses machine learning algorithms and user behavior to improve match quality
*/

require_once('avatar-generator.php');

class AIMatchingEngine {
    
    private $db;
    private $avatarGenerator;
    private $profileManager;
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
        $this->profileManager = new ProfileManager($database);
        $this->avatarGenerator = new AvatarGenerator($config['avatar_provider'] ?? 'local', $config);
        
        if (isset($config['weights'])) {
            $this->weights = array_merge($this->weights, $config['weights']);
        }
    }
    
    public function findMatches($userId, $limit = 20, $filters = []) {
        $userProfile = $this->profileManager->getProfile($userId);
        if (!$userProfile) {
            return [];
        }
        
        $candidates = $this->getCandidates($userId, $userProfile, $filters);
        
        $scoredMatches = [];
        foreach ($candidates as $candidate) {
            $candidateProfile = $this->profileManager->getProfile($candidate['id']);
            $score = $this->calculateMatchScore($userProfile, $candidateProfile);
            if ($score > 0.3) { // Minimum threshold
                $scoredMatches[] = [
                    'user' => $candidateProfile,
                    'score' => $score,
                    'breakdown' => $this->getScoreBreakdown($userProfile, $candidateProfile)
                ];
            }
        }
        
        usort($scoredMatches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        $refinedMatches = $this->applyMLAdjustments($userId, $scoredMatches);
        
        return array_slice($refinedMatches, 0, $limit);
    }
    
    private function getCandidates($userId, $userProfile, $filters = []) {
        // Define the columns we need for the initial filtering
        $columns = "id, username, age, gender, latitude, longitude, last_online, interests, body_type, hair_color";
        $sql = "SELECT $columns FROM users WHERE id != ? AND active = 1";
        $params = [$userId];

        // Bounding Box Calculation for efficient location filtering
        if (isset($filters['max_distance']) && isset($userProfile['latitude']) && isset($userProfile['longitude'])) {
            $lat = $userProfile['latitude'];
            $lon = $userProfile['longitude'];
            $distance = $filters['max_distance'];

            // Earth radius in kilometers
            $earthRadius = 6371;

            $lat_rad = deg2rad($lat);
            $lon_rad = deg2rad($lon);

            $delta_lat = $distance / $earthRadius;
            $delta_lon = $distance / ($earthRadius * cos($lat_rad));

            $min_lat = rad2deg($lat_rad - $delta_lat);
            $max_lat = rad2deg($lat_rad + $delta_lat);
            $min_lon = rad2deg($lon_rad - $delta_lon);
            $max_lon = rad2deg($lon_rad + $delta_lon);

            $sql .= " AND (latitude BETWEEN ? AND ?) AND (longitude BETWEEN ? AND ?)";
            array_push($params, $min_lat, $max_lat, $min_lon, $max_lon);
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
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Second pass: precise distance filtering for the candidates within the bounding box
        if (isset($filters['max_distance']) && isset($userProfile['latitude'])) {
            $filteredCandidates = [];
            foreach ($candidates as $candidate) {
                $distance = $this->calculateDistance($userProfile['latitude'], $userProfile['longitude'], $candidate['latitude'], $candidate['longitude']);
                if ($distance <= $filters['max_distance']) {
                    $filteredCandidates[] = $candidate;
                }
            }
            return $filteredCandidates;
        }

        return $candidates;
    }

    // ... (rest of the class methods remain the same) ...

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
            $totalScore += $score * ($this->weights[$metric] ?? 0);
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
        
        $overlapRatio = count($totalInterests) > 0 ? count($commonInterests) / count($totalInterests) : 0;
        
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
        if (isset($user['b_wantBodyType']) && isset($candidate['body_type'])) {
            $preferences = explode(',', $user['b_wantBodyType']);
            if (in_array($candidate['body_type'], $preferences)) {
                $score += 0.3;
            }
        }
        
        // Hair color preferences
        if (isset($user['b_wantHairColor']) && isset($candidate['hair_color'])) {
            $preferences = explode(',', $user['b_wantHairColor']);
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
    
    private function applyMLAdjustments($userId, $matches) {
        // Placeholder for ML adjustments
        return $matches;
    }
    
    public function generateUserAvatar($userId, $options = []) {
        $userProfile = $this->profileManager->getProfile($userId);
        if (!$userProfile) {
            return ['success' => false, 'error' => 'User profile not found'];
        }
        
        try {
            $result = $this->avatarGenerator->generateAvatar($userProfile, $options);
            
            if ($result['success']) {
                $this->updateUserAvatar($userId, $result['image_url']);
                return $result;
            }
            
            return $result;
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
    
    private function parseInterests($interests) {
        return array_filter(array_map('trim', explode(',', strtolower($interests))));
    }
    
    private function getSexualPreferences($userId) {
        $stmt = $this->db->prepare("SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    private function getUserInteractions($userId1, $userId2) {
        $stmt = $this->db->prepare("SELECT type FROM interactions WHERE (user_id = ? AND target_user_id = ?) OR (user_id = ? AND target_user_id = ?)");
        $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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