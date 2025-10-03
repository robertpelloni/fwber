<?php
/**
 * Advanced Matching Engine
 * AI-powered weighted scoring system for optimal match recommendations
 * 
 * This replaces the basic getMatches() function with a sophisticated
 * compatibility scoring algorithm that considers multiple factors:
 * - Physical attraction compatibility
 * - Lifestyle and personality alignment
 * - Sexual compatibility and preferences
 * - Location proximity and availability
 * - Mutual interest indicators
 * - Activity patterns and engagement
 */

class MatchingEngine {
    private $pdo;
    private $userId;
    private $userProfile;
    
    // Scoring weights (adjustable based on A/B testing)
    private $weights = [
        'physical' => 0.25,        // Physical attraction factors
        'personality' => 0.20,     // Intelligence, looks, personality
        'sexual' => 0.20,          // Sexual compatibility
        'lifestyle' => 0.15,       // STI status, drugs, relationship style
        'location' => 0.10,        // Proximity and availability
        'activity' => 0.10         // Recent activity and engagement
    ];
    
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        
        // Load user profile
        $stmt = $pdo->prepare("SELECT u.*, GROUP_CONCAT(CONCAT(up.preference_key, '=', up.preference_value)) as preferences 
                               FROM users u 
                               LEFT JOIN user_preferences up ON u.id = up.user_id 
                               WHERE u.id = ? 
                               GROUP BY u.id");
        $stmt->execute([$userId]);
        $this->userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Parse preferences into array
        if ($this->userProfile['preferences']) {
            $prefs = [];
            foreach (explode(',', $this->userProfile['preferences']) as $pref) {
                list($key, $value) = explode('=', $pref);
                $prefs[$key] = $value;
            }
            $this->userProfile = array_merge($this->userProfile, $prefs);
        }
    }
    
    /**
     * Get top matches for the current user
     * 
     * @param int $limit Maximum number of matches to return
     * @param array $filters Additional filters (e.g., online_only, new_users)
     * @return array Ranked list of matches with compatibility scores
     */
    public function getMatches($limit = 50, $filters = []) {
        // Build base query for potential matches
        $sql = "SELECT DISTINCT u.*, 
                (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude)))) AS distance_km
                FROM users u
                WHERE u.id != ?
                AND u.active = 1";
        
        $params = [
            $this->userProfile['latitude'] ?? 0,
            $this->userProfile['longitude'] ?? 0,
            $this->userProfile['latitude'] ?? 0,
            $this->userId
        ];
        
        // Apply filters
        if (isset($filters['online_only']) && $filters['online_only']) {
            $sql .= " AND u.last_seen > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
        }
        
        if (isset($filters['new_users']) && $filters['new_users']) {
            $sql .= " AND u.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
        }
        
        if (isset($filters['max_distance']) && $filters['max_distance'] > 0) {
            $sql .= " HAVING distance_km <= ?";
            $params[] = $filters['max_distance'];
        } else {
            // Use user's max distance preference
            $maxDistance = $this->userProfile['max_distance'] ?? 50;
            $sql .= " HAVING distance_km <= ?";
            $params[] = $maxDistance;
        }
        
        $sql .= " ORDER BY distance_km ASC LIMIT 500"; // Get larger pool for scoring
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $potentialMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Score each potential match
        $scoredMatches = [];
        foreach ($potentialMatches as $match) {
            // Load match preferences
            $matchProfile = $this->loadFullProfile($match['id']);
            
            // Calculate compatibility score
            $score = $this->calculateCompatibilityScore($matchProfile);
            
            if ($score > 0) {
                $scoredMatches[] = [
                    'user' => $matchProfile,
                    'score' => $score,
                    'distance_km' => $match['distance_km'],
                    'breakdown' => $this->getScoreBreakdown($matchProfile)
                ];
            }
        }
        
        // Sort by score (highest first)
        usort($scoredMatches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Return top matches
        return array_slice($scoredMatches, 0, $limit);
    }
    
    /**
     * Calculate overall compatibility score (0-100)
     */
    private function calculateCompatibilityScore($matchProfile) {
        // Check basic compatibility first (gender, age, dealbreakers)
        if (!$this->checkBasicCompatibility($matchProfile)) {
            return 0;
        }
        
        // Calculate component scores
        $physicalScore = $this->calculatePhysicalScore($matchProfile);
        $personalityScore = $this->calculatePersonalityScore($matchProfile);
        $sexualScore = $this->calculateSexualScore($matchProfile);
        $lifestyleScore = $this->calculateLifestyleScore($matchProfile);
        $locationScore = $this->calculateLocationScore($matchProfile);
        $activityScore = $this->calculateActivityScore($matchProfile);
        
        // Weighted total
        $totalScore = 
            ($physicalScore * $this->weights['physical']) +
            ($personalityScore * $this->weights['personality']) +
            ($sexualScore * $this->weights['sexual']) +
            ($lifestyleScore * $this->weights['lifestyle']) +
            ($locationScore * $this->weights['location']) +
            ($activityScore * $this->weights['activity']);
        
        // Apply boost factors
        $totalScore = $this->applyBoostFactors($totalScore, $matchProfile);
        
        return round($totalScore, 2);
    }
    
    /**
     * Check basic compatibility (dealbreakers)
     */
    private function checkBasicCompatibility($matchProfile) {
        // Check gender compatibility
        if (!$this->isGenderCompatible($matchProfile)) {
            return false;
        }
        
        // Check age range
        $wantAgeFrom = $this->userProfile['wantAgeFrom'] ?? 18;
        $wantAgeTo = $this->userProfile['wantAgeTo'] ?? 99;
        if ($matchProfile['age'] < $wantAgeFrom || $matchProfile['age'] > $wantAgeTo) {
            return false;
        }
        
        // Check if match wants my age
        $matchWantAgeFrom = $matchProfile['wantAgeFrom'] ?? 18;
        $matchWantAgeTo = $matchProfile['wantAgeTo'] ?? 99;
        if ($this->userProfile['age'] < $matchWantAgeFrom || $this->userProfile['age'] > $matchWantAgeTo) {
            return false;
        }
        
        // Check STI dealbreakers
        if (!$this->checkSTICompatibility($matchProfile)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check gender compatibility
     */
    private function isGenderCompatible($matchProfile) {
        $myGender = $this->userProfile['gender'] ?? '';
        $matchGender = $matchProfile['gender'] ?? '';
        
        // Check if I want their gender
        $wantKey = 'b_wantGender' . ucfirst($matchGender);
        if (!($this->userProfile[$wantKey] ?? 0)) {
            return false;
        }
        
        // Check if they want my gender
        $matchWantKey = 'b_wantGender' . ucfirst($myGender);
        if (!($matchProfile[$matchWantKey] ?? 0)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check STI compatibility
     */
    private function checkSTICompatibility($matchProfile) {
        $stiConditions = ['HIV', 'Herpes', 'Warts', 'Hepatitis', 'OtherSTI'];
        
        foreach ($stiConditions as $condition) {
            $iHave = $this->userProfile['b_have' . $condition] ?? 0;
            $theyReject = $matchProfile['b_no' . $condition] ?? 0;
            
            if ($iHave && $theyReject) {
                return false; // They reject my condition
            }
            
            $theyHave = $matchProfile['b_have' . $condition] ?? 0;
            $iReject = $this->userProfile['b_no' . $condition] ?? 0;
            
            if ($theyHave && $iReject) {
                return false; // I reject their condition
            }
        }
        
        return true;
    }
    
    /**
     * Calculate physical attraction score (0-100)
     */
    private function calculatePhysicalScore($matchProfile) {
        $score = 0;
        $maxScore = 0;
        
        // Body type preference
        $bodyTypes = ['Tiny', 'Slim', 'Average', 'Muscular', 'Curvy', 'Thick', 'BBW'];
        foreach ($bodyTypes as $type) {
            $maxScore += 20;
            if ($this->userProfile['b_wantBody' . $type] ?? 0) {
                if ($matchProfile['body'] === strtolower($type)) {
                    $score += 20;
                }
            }
        }
        
        // Ethnicity preference
        $ethnicities = ['White', 'Asian', 'Latino', 'Indian', 'Black', 'Other'];
        foreach ($ethnicities as $ethnicity) {
            $maxScore += 15;
            if ($this->userProfile['b_wantEthnicity' . $ethnicity] ?? 0) {
                if ($matchProfile['ethnicity'] === strtolower($ethnicity)) {
                    $score += 15;
                }
            }
        }
        
        // Hair color preference
        $hairColors = ['Light', 'Medium', 'Dark', 'Red', 'Gray', 'Other'];
        foreach ($hairColors as $color) {
            $maxScore += 10;
            if ($this->userProfile['b_wantHairColor' . $color] ?? 0) {
                if ($matchProfile['hairColor'] === strtolower($color)) {
                    $score += 10;
                }
            }
        }
        
        // Hair length preference
        $hairLengths = ['Bald', 'Short', 'Medium', 'Long'];
        foreach ($hairLengths as $length) {
            $maxScore += 10;
            if ($this->userProfile['b_wantHairLength' . $length] ?? 0) {
                if ($matchProfile['hairLength'] === strtolower($length)) {
                    $score += 10;
                }
            }
        }
        
        return $maxScore > 0 ? ($score / $maxScore) * 100 : 50;
    }
    
    /**
     * Calculate personality compatibility score (0-100)
     */
    private function calculatePersonalityScore($matchProfile) {
        $score = 50; // Base score
        
        // Overall looks alignment
        $looksMap = ['ugly' => 1, 'plain' => 2, 'quirky' => 3, 'average' => 4, 'attractive' => 5, 'hottie' => 6, 'superModel' => 7];
        $myLooks = $looksMap[$this->userProfile['overallLooks'] ?? 'average'] ?? 4;
        $theirLooks = $looksMap[$matchProfile['overallLooks'] ?? 'average'] ?? 4;
        $looksDiff = abs($myLooks - $theirLooks);
        $score += (7 - $looksDiff) * 5; // Closer looks = higher score
        
        // Intelligence alignment
        $intMap = ['goodHands' => 1, 'bitSlow' => 2, 'average' => 3, 'faster' => 4, 'genius' => 5];
        $myInt = $intMap[$this->userProfile['intelligence'] ?? 'average'] ?? 3;
        $theirInt = $intMap[$matchProfile['intelligence'] ?? 'average'] ?? 3;
        $intDiff = abs($myInt - $theirInt);
        $score += (5 - $intDiff) * 5; // Similar intelligence = higher score
        
        // Bedroom personality compatibility
        $bedroomMap = ['passive' => 1, 'shy' => 2, 'confident' => 3, 'aggressive' => 4];
        $myBedroom = $bedroomMap[$this->userProfile['bedroomPersonality'] ?? 'confident'] ?? 3;
        $theirBedroom = $bedroomMap[$matchProfile['bedroomPersonality'] ?? 'confident'] ?? 3;
        
        // Complementary bedroom personalities get bonus
        if (($myBedroom <= 2 && $theirBedroom >= 3) || ($myBedroom >= 3 && $theirBedroom <= 2)) {
            $score += 15; // Opposite personalities work well
        } else {
            $score += 5; // Similar personalities okay
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate sexual compatibility score (0-100)
     */
    private function calculateSexualScore($matchProfile) {
        $score = 0;
        $matches = 0;
        $total = 0;
        
        // Sexual activity preferences
        $sexualPrefs = [
            'SafeSex', 'BarebackSex', 'OralGive', 'OralReceive',
            'AnalTop', 'AnalBottom', 'Filming', 'Voyeur', 'Exhibitionist',
            'Roleplay', 'Spanking', 'Dom', 'Sub', 'Strapon', 'Cuckold', 'Furry'
        ];
        
        foreach ($sexualPrefs as $pref) {
            $iWant = $this->userProfile['b_want' . $pref] ?? 0;
            $theyWant = $matchProfile['b_want' . $pref] ?? 0;
            
            if ($iWant || $theyWant) {
                $total++;
                if ($iWant && $theyWant) {
                    $matches++;
                    $score += 10;
                } elseif ($iWant || $theyWant) {
                    $score += 3; // Partial match
                }
            }
        }
        
        // Gender-specific attributes
        if ($matchProfile['gender'] === 'man' || $matchProfile['gender'] === 'TSMan') {
            // Penis size preference (simplified)
            $score += 10;
        }
        
        if ($matchProfile['gender'] === 'woman' || $matchProfile['gender'] === 'TSWoman') {
            // Breast size preference (simplified)
            $score += 10;
        }
        
        return min(100, $total > 0 ? ($matches / $total) * 100 : 50);
    }
    
    /**
     * Calculate lifestyle compatibility score (0-100)
     */
    private function calculateLifestyleScore($matchProfile) {
        $score = 50; // Base score
        $penalties = 0;
        
        // Smoking compatibility
        $iSmoke = $this->userProfile['b_smokeCigarettes'] ?? 0;
        $theyRejectSmoking = $matchProfile['b_noCigs'] ?? 0;
        if ($iSmoke && $theyRejectSmoking) $penalties += 15;
        
        // Drinking compatibility
        $iDrinkHeavy = $this->userProfile['b_heavyDrinker'] ?? 0;
        $theyRejectHeavyDrink = $matchProfile['b_noHeavyDrink'] ?? 0;
        if ($iDrinkHeavy && $theyRejectHeavyDrink) $penalties += 10;
        
        // Marijuana compatibility
        $iSmokeMJ = $this->userProfile['b_smokeMarijuana'] ?? 0;
        $theyRejectMJ = $matchProfile['b_noMarijuana'] ?? 0;
        if ($iSmokeMJ && $theyRejectMJ) $penalties += 10;
        
        // Drug compatibility
        $iUseDrugs = $this->userProfile['b_otherDrugs'] ?? 0;
        $theyRejectDrugs = $matchProfile['b_noDrugs'] ?? 0;
        if ($iUseDrugs && $theyRejectDrugs) $penalties += 15;
        
        // Relationship style compatibility
        $iPoly = $this->userProfile['b_poly'] ?? 0;
        $theyRejectPoly = $matchProfile['b_noPoly'] ?? 0;
        if ($iPoly && $theyRejectPoly) $penalties += 20;
        
        $iMarriedSecret = $this->userProfile['b_marriedSecret'] ?? 0;
        $theyRejectMarriedSecret = $matchProfile['b_noMarriedSecret'] ?? 0;
        if ($iMarriedSecret && $theyRejectMarriedSecret) $penalties += 20;
        
        return max(0, $score - $penalties);
    }
    
    /**
     * Calculate location/availability score (0-100)
     */
    private function calculateLocationScore($matchProfile) {
        $distance = $matchProfile['distance_km'] ?? 999;
        $maxDistance = $this->userProfile['max_distance'] ?? 50;
        
        // Score based on proximity
        $proximityScore = max(0, 100 - ($distance / $maxDistance * 100));
        
        // Boost for same venue
        if (isset($this->userProfile['current_venue_id']) && 
            isset($matchProfile['current_venue_id']) &&
            $this->userProfile['current_venue_id'] == $matchProfile['current_venue_id']) {
            $proximityScore = 100; // Maximum score for same venue
        }
        
        // Boost for recent activity
        $lastSeen = $matchProfile['last_seen'] ?? null;
        if ($lastSeen) {
            $hoursSinceActive = (time() - strtotime($lastSeen)) / 3600;
            if ($hoursSinceActive < 1) {
                $proximityScore += 20; // Online now
            } elseif ($hoursSinceActive < 24) {
                $proximityScore += 10; // Active today
            }
        }
        
        return min(100, $proximityScore);
    }
    
    /**
     * Calculate activity/engagement score (0-100)
     */
    private function calculateActivityScore($matchProfile) {
        $score = 50; // Base score
        
        // Profile completeness
        $completenessFields = ['publicText', 'privateText', 'avatar_url', 'body', 'ethnicity', 'hairColor'];
        $completed = 0;
        foreach ($completenessFields as $field) {
            if (!empty($matchProfile[$field])) $completed++;
        }
        $score += ($completed / count($completenessFields)) * 30;
        
        // Recent activity
        $lastSeen = $matchProfile['last_seen'] ?? null;
        if ($lastSeen) {
            $daysSinceActive = (time() - strtotime($lastSeen)) / 86400;
            if ($daysSinceActive < 1) {
                $score += 20;
            } elseif ($daysSinceActive < 7) {
                $score += 10;
            } elseif ($daysSinceActive < 30) {
                $score += 5;
            }
        }
        
        return min(100, $score);
    }
    
    /**
     * Apply boost factors for special situations
     */
    private function applyBoostFactors($score, $matchProfile) {
        // Same venue boost
        if (isset($this->userProfile['current_venue_id']) && 
            isset($matchProfile['current_venue_id']) &&
            $this->userProfile['current_venue_id'] == $matchProfile['current_venue_id']) {
            $score *= 1.2; // 20% boost
        }
        
        // Mutual interest boost (if we implement favorite/like system)
        // Would require additional tables
        
        // New user boost (help new users get matches)
        $accountAge = (time() - strtotime($matchProfile['created_at'])) / 86400;
        if ($accountAge < 7) {
            $score *= 1.1; // 10% boost for new users
        }
        
        return min(100, $score);
    }
    
    /**
     * Get detailed score breakdown for debugging/display
     */
    private function getScoreBreakdown($matchProfile) {
        return [
            'physical' => round($this->calculatePhysicalScore($matchProfile), 2),
            'personality' => round($this->calculatePersonalityScore($matchProfile), 2),
            'sexual' => round($this->calculateSexualScore($matchProfile), 2),
            'lifestyle' => round($this->calculateLifestyleScore($matchProfile), 2),
            'location' => round($this->calculateLocationScore($matchProfile), 2),
            'activity' => round($this->calculateActivityScore($matchProfile), 2)
        ];
    }
    
    /**
     * Load full profile with preferences
     */
    private function loadFullProfile($userId) {
        $stmt = $this->pdo->prepare("SELECT u.*, GROUP_CONCAT(CONCAT(up.preference_key, '=', up.preference_value)) as preferences 
                                     FROM users u 
                                     LEFT JOIN user_preferences up ON u.id = up.user_id 
                                     WHERE u.id = ? 
                                     GROUP BY u.id");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Parse preferences
        if ($profile['preferences']) {
            $prefs = [];
            foreach (explode(',', $profile['preferences']) as $pref) {
                if (strpos($pref, '=') !== false) {
                    list($key, $value) = explode('=', $pref);
                    $prefs[$key] = $value;
                }
            }
            $profile = array_merge($profile, $prefs);
        }
        
        return $profile;
    }
    
    /**
     * Get mutual matches (both users like each other)
     */
    public function getMutualMatches($limit = 20) {
        // This would require a likes/favorites table
        // For now, return top scored matches
        return $this->getMatches($limit, ['mutual_only' => true]);
    }
    
    /**
     * Save match for future reference
     */
    public function saveMatch($matchUserId, $score, $matchType = 'algorithm') {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_matches (user1_id, user2_id, match_type, compatibility_score, matched_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE compatibility_score = ?, matched_at = NOW()
        ");
        
        return $stmt->execute([
            min($this->userId, $matchUserId),
            max($this->userId, $matchUserId),
            $matchType,
            $score,
            $score
        ]);
    }
}
?>
