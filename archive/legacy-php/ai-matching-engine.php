<?php
/*
    AI-Enhanced Matching Engine
    Uses a weighted algorithm to score and find compatible matches.
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
        $this->avatarGenerator = new AvatarGenerator($this->profileManager, $config['avatar_provider'] ?? 'replicate', $config);
        
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
        
        return array_slice($scoredMatches, 0, $limit);
    }
    
    private function getCandidates($userId, $userProfile, $filters = []) {
        $columns = "id, username, age, gender, latitude, longitude, last_online, interests, body_type, hair_color";
        $sql = "SELECT $columns FROM users WHERE id != ? AND active = 1";
        $params = [$userId];

        if (isset($filters['max_distance']) && isset($userProfile['latitude']) && isset($userProfile['longitude'])) {
            $lat = $userProfile['latitude'];
            $lon = $userProfile['longitude'];
            $distance = $filters['max_distance'];
            $earthRadius = 6371;
            $lat_rad = deg2rad($lat);
            $delta_lat = $distance / $earthRadius;
            $delta_lon = $distance / ($earthRadius * cos($lat_rad));
            $min_lat = $lat - rad2deg($delta_lat);
            $max_lat = $lat + rad2deg($delta_lat);
            $min_lon = $lon - rad2deg($delta_lon);
            $max_lon = $lon + rad2deg($delta_lon);

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

        if (isset($filters['max_distance']) && isset($userProfile['latitude'])) {
            return array_filter($candidates, function($candidate) use ($userProfile, $filters) {
                $distance = $this->calculateDistance($userProfile['latitude'], $userProfile['longitude'], $candidate['latitude'], $candidate['longitude']);
                return $distance <= $filters['max_distance'];
            });
        }

        return $candidates;
    }

    private function calculateMatchScore($user, $candidate) {
        $scores = [];
        $scores['location_proximity'] = $this->calculateLocationScore($user, $candidate);
        $scores['age_compatibility'] = $this->calculateAgeScore($user, $candidate);
        $scores['interests_overlap'] = $this->calculateInterestsScore($user, $candidate);
        $scores['activity_pattern'] = $this->calculateActivityScore($user, $candidate);
        $scores['interaction_history'] = $this->calculateInteractionScore($user, $candidate);
        $scores['avatar_similarity'] = $this->calculateAvatarScore($user, $candidate);
        
        $totalScore = 0;
        foreach ($scores as $metric => $score) {
            $totalScore += $score * ($this->weights[$metric] ?? 0);
        }
        
        return min($totalScore, 1.0);
    }
    
    private function calculateLocationScore($user, $candidate) {
        if (!isset($user['latitude']) || !isset($candidate['latitude'])) return 0.5;
        $distance = $this->calculateDistance($user['latitude'], $user['longitude'], $candidate['latitude'], $candidate['longitude']);
        if ($distance <= 1) return 1.0;
        if ($distance <= 5) return 0.9;
        if ($distance <= 10) return 0.8;
        if ($distance <= 25) return 0.6;
        if ($distance <= 50) return 0.4;
        if ($distance <= 100) return 0.2;
        return 0.1;
    }
    
    private function calculateAgeScore($user, $candidate) {
        $ageDiff = abs($user['age'] - $candidate['age']);
        if ($candidate['age'] < ($user['age_preference_min'] ?? 18) || $candidate['age'] > ($user['age_preference_max'] ?? 99)) return 0;
        if ($user['age'] < ($candidate['age_preference_min'] ?? 18) || $user['age'] > ($candidate['age_preference_max'] ?? 99)) return 0;
        if ($ageDiff <= 2) return 1.0;
        if ($ageDiff <= 5) return 0.8;
        if ($ageDiff <= 10) return 0.6;
        if ($ageDiff <= 15) return 0.4;
        return 0.2;
    }
    
    private function calculateInterestsScore($user, $candidate) {
        $userInterests = $this->parseInterests($user['interests'] ?? '');
        $candidateInterests = $this->parseInterests($candidate['interests'] ?? '');
        if (empty($userInterests) || empty($candidateInterests)) return 0.5;
        $commonInterests = array_intersect($userInterests, $candidateInterests);
        $totalInterests = count(array_unique(array_merge($userInterests, $candidateInterests)));
        $overlapRatio = $totalInterests > 0 ? count($commonInterests) / $totalInterests : 0;
        $sexualCompatibility = $this->calculateSexualCompatibility($user, $candidate);
        return min($overlapRatio + $sexualCompatibility * 0.3, 1.0);
    }
    
    private function calculateActivityScore($user, $candidate) {
        $userLastOnline = strtotime($user['last_online']);
        $candidateLastOnline = strtotime($candidate['last_online']);
        $now = time();
        $userActivityScore = $this->getActivityScore($now - $userLastOnline);
        $candidateActivityScore = $this->getActivityScore($now - $candidateLastOnline);
        $similarityBonus = 1 - abs($userActivityScore - $candidateActivityScore);
        return ($userActivityScore + $candidateActivityScore) / 2 * $similarityBonus;
    }
    
    private function getActivityScore($timeSinceOnline) {
        $hours = $timeSinceOnline / 3600;
        if ($hours <= 1) return 1.0;
        if ($hours <= 6) return 0.9;
        if ($hours <= 24) return 0.7;
        if ($hours <= 72) return 0.5;
        if ($hours <= 168) return 0.3;
        return 0.1;
    }
    
    private function calculateInteractionScore($user, $candidate) {
        $interactions = $this->getUserInteractions($user['id'], $candidate['id']);
        if (empty($interactions)) return 0.5;
        $positiveInteractions = count(array_filter($interactions, fn($i) => in_array($i['type'], ['like', 'super_like', 'message', 'match'])));
        return count($interactions) > 0 ? $positiveInteractions / count($interactions) : 0.5;
    }
    
    private function calculateAvatarScore($user, $candidate) {
        $score = 0.5;
        if (isset($user['b_wantBodyType']) && isset($candidate['body_type'])) {
            if (in_array($candidate['body_type'], explode(',', $user['b_wantBodyType']))) $score += 0.3;
        }
        if (isset($user['b_wantHairColor']) && isset($candidate['hair_color'])) {
            if (in_array($candidate['hair_color'], explode(',', $user['b_wantHairColor']))) $score += 0.2;
        }
        return min($score, 1.0);
    }
    
    private function calculateSexualCompatibility($user, $candidate) {
        $userPrefs = $this->getSexualPreferences($user['id']);
        $candidatePrefs = $this->getSexualPreferences($candidate['id']);
        if (empty($userPrefs) || empty($candidatePrefs)) return 0.5;
        $compatibility = 0;
        $factors = 0;
        foreach ($userPrefs as $pref => $value) {
            if (isset($candidatePrefs[$pref])) {
                if ($value == $candidatePrefs[$pref]) $compatibility++;
                $factors++;
            }
        }
        return $factors > 0 ? $compatibility / $factors : 0.5;
    }
    
    public function generateUserAvatar($userId, $options = []) {
        $userProfile = $this->profileManager->getProfile($userId);
        if (!$userProfile) return ['success' => false, 'error' => 'User profile not found'];
        try {
            $result = $this->avatarGenerator->generateAvatar($userId, $options);
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
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        return $r * (2 * atan2(sqrt($a), sqrt(1-$a)));
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
        $stmt = $this->db->prepare("SELECT type FROM user_interactions WHERE (user_id = ? AND target_user_id = ?) OR (user_id = ? AND target_user_id = ?)");
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