<?php

class ProfileManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getProfile($userId) {
        // Fetch user data from the 'users' table
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userProfile) {
            return null; // User not found
        }

        // Fetch user preferences from the 'user_preferences' table
        $stmt = $this->pdo->prepare("SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $preferences = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Merge preferences into the main user profile array
        return array_merge($userProfile, $preferences);
    }

    public function saveProfile($userId, $data) {
        // Define which keys belong to the users table (core profile data)
        $userTableColumns = [
            'username', 'age', 'gender', 'seeking_gender', 'relationship_type',
            'hair_color', 'hair_style', 'eye_color', 'ethnicity', 'body_type', 'height',
            'interests', 'latitude', 'longitude', 'location_updated_at', 'city', 'state',
            'country', 'zip_code', 'age_preference_min', 'age_preference_max', 'max_distance',
            'avatar_url', 'avatar_updated_at', 'publicText', 'privateText', 'firstName',
            // Legacy fields that should stay in users table
            'dateJoined', 'dateLastSignedIn', 'dateLastSeen', 'postalCode', 
            'isoCountryCode', 'lat', 'lon', 'distance', 'birthdayMonth', 'birthdayDay', 
            'birthdayYear', 'body', 'hairLength', 'tattoos', 'overallLooks', 
            'intelligence', 'bedroomPersonality', 'pubicHair', 'penisSize', 'bodyHair', 
            'breastSize', 'wantAgeFrom', 'wantAgeTo'
        ];

        $userData = [];
        $preferencesData = [];

        // Separate data for users table vs user_preferences table
        foreach ($data as $key => $value) {
            if (in_array($key, $userTableColumns)) {
                $userData[$key] = $value;
            } else {
                // Assume everything else is a preference
                $preferencesData[$key] = $value;
            }
        }

        try {
            $this->pdo->beginTransaction();

            // Update the users table
            if (!empty($userData)) {
                $sql = "UPDATE users SET ";
                $updates = [];
                foreach ($userData as $key => $value) {
                    $updates[] = "`$key` = ?";
                }
                $sql .= implode(', ', $updates);
                $sql .= " WHERE id = ?";

                $stmt = $this->pdo->prepare($sql);
                $values = array_values($userData);
                $values[] = $userId;
                $stmt->execute($values);
            }

            // Update the user_preferences table
            $stmt = $this->pdo->prepare("
                INSERT INTO user_preferences (user_id, preference_key, preference_value)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)
            ");

            foreach ($preferencesData as $key => $value) {
                $stmt->execute([$userId, $key, $value]);
            }

            $this->pdo->commit();
            return true;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Profile save failed: " . $e->getMessage());
            return false;
        }
    }
}
