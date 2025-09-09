<?php
/*
    Enhanced Security and Privacy Manager
    Handles encryption, profile verification, and privacy controls
*/

class SecurityManager {
    
    private $encryptionKey;
    private $db;
    private $config;
    
    public function __construct($database, $config = []) {
        $this->db = $database;
        $this->config = array_merge([
            'encryption_method' => 'AES-256-CBC',
            'hash_algorithm' => 'sha256',
            'password_min_length' => 8,
            'session_lifetime' => 3600, // 1 hour
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutes
            'require_2fa' => false,
            'photo_encryption' => true,
            'message_encryption' => true
        ], $config);
        
        $this->encryptionKey = $this->getOrCreateEncryptionKey();
    }
    
    /**
     * Enhanced password hashing with salt
     */
    public function hashPassword($password, $salt = null) {
        if (!$salt) {
            $salt = $this->generateSalt();
        }
        
        // Use password_hash with custom salt integration
        $hashedPassword = password_hash($password . $salt, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
        
        return [
            'hash' => $hashedPassword,
            'salt' => $salt
        ];
    }
    
    /**
     * Verify password with enhanced security
     */
    public function verifyPassword($password, $hash, $salt) {
        return password_verify($password . $salt, $hash);
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encryptData($data, $key = null) {
        if (!$key) {
            $key = $this->encryptionKey;
        }
        
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, $this->config['encryption_method'], $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decryptData($encryptedData, $key = null) {
        if (!$key) {
            $key = $this->encryptionKey;
        }
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, $this->config['encryption_method'], $key, 0, $iv);
    }
    
    /**
     * Generate secure session token
     */
    public function generateSessionToken($userId) {
        $tokenData = [
            'user_id' => $userId,
            'created_at' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'random' => bin2hex(random_bytes(16))
        ];
        
        $token = $this->encryptData(json_encode($tokenData));
        
        // Store session in database
        $this->storeSession($userId, $token);
        
        return $token;
    }
    
    /**
     * Validate session token
     */
    public function validateSession($token) {
        try {
            $tokenData = json_decode($this->decryptData($token), true);
            
            if (!$tokenData || !isset($tokenData['user_id'])) {
                return false;
            }
            
            // Check if session exists in database
            if (!$this->isValidSession($tokenData['user_id'], $token)) {
                return false;
            }
            
            // Check expiration
            if (time() - $tokenData['created_at'] > $this->config['session_lifetime']) {
                $this->invalidateSession($token);
                return false;
            }
            
            // Security checks
            if ($this->config['check_ip'] ?? true) {
                if ($tokenData['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
                    $this->invalidateSession($token);
                    return false;
                }
            }
            
            return $tokenData['user_id'];
            
        } catch (Exception $e) {
            error_log('Session validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Two-Factor Authentication setup
     */
    public function setup2FA($userId) {
        $secret = $this->generate2FASecret();
        
        // Store encrypted secret
        $encryptedSecret = $this->encryptData($secret);
        $this->store2FASecret($userId, $encryptedSecret);
        
        return [
            'secret' => $secret,
            'qr_code' => $this->generate2FAQRCode($userId, $secret)
        ];
    }
    
    /**
     * Verify 2FA token
     */
    public function verify2FA($userId, $token) {
        $encryptedSecret = $this->get2FASecret($userId);
        if (!$encryptedSecret) {
            return false;
        }
        
        $secret = $this->decryptData($encryptedSecret);
        return $this->verifyTOTP($secret, $token);
    }
    
    /**
     * Photo encryption for privacy
     */
    public function encryptPhoto($photoPath, $userId) {
        $photoData = file_get_contents($photoPath);
        if (!$photoData) {
            throw new Exception('Unable to read photo file');
        }
        
        // Generate user-specific photo key
        $photoKey = $this->generatePhotoKey($userId);
        $encryptedPhoto = $this->encryptData($photoData, $photoKey);
        
        // Store encrypted photo
        $encryptedPath = $this->getEncryptedPhotoPath($userId, basename($photoPath));
        file_put_contents($encryptedPath, $encryptedPhoto);
        
        // Store photo metadata
        $this->storePhotoMetadata($userId, $encryptedPath, $photoPath);
        
        return $encryptedPath;
    }
    
    /**
     * Decrypt photo for authorized viewing
     */
    public function decryptPhoto($encryptedPath, $userId, $viewerId) {
        // Check if viewer is authorized to see this photo
        if (!$this->isAuthorizedToViewPhoto($userId, $viewerId, $encryptedPath)) {
            throw new Exception('Unauthorized to view photo');
        }
        
        $encryptedData = file_get_contents($encryptedPath);
        if (!$encryptedData) {
            throw new Exception('Photo not found');
        }
        
        $photoKey = $this->generatePhotoKey($userId);
        $photoData = $this->decryptData($encryptedData, $photoKey);
        
        // Log photo access
        $this->logPhotoAccess($userId, $viewerId, $encryptedPath);
        
        return $photoData;
    }
    
    /**
     * Message encryption
     */
    public function encryptMessage($message, $senderId, $recipientId) {
        // Generate conversation key
        $conversationKey = $this->getOrCreateConversationKey($senderId, $recipientId);
        
        $encryptedMessage = $this->encryptData($message, $conversationKey);
        
        return [
            'encrypted_message' => $encryptedMessage,
            'message_hash' => hash($this->config['hash_algorithm'], $message)
        ];
    }
    
    /**
     * Message decryption
     */
    public function decryptMessage($encryptedMessage, $senderId, $recipientId) {
        $conversationKey = $this->getConversationKey($senderId, $recipientId);
        if (!$conversationKey) {
            throw new Exception('Conversation key not found');
        }
        
        return $this->decryptData($encryptedMessage, $conversationKey);
    }
    
    /**
     * Profile verification system
     */
    public function initiatePhotoVerification($userId, $photoPath) {
        // Generate verification challenge
        $challenge = $this->generateVerificationChallenge();
        
        // Store verification request
        $this->storeVerificationRequest($userId, $challenge, $photoPath);
        
        return [
            'challenge' => $challenge,
            'instructions' => 'Please take a photo holding a piece of paper with this code: ' . $challenge
        ];
    }
    
    /**
     * Verify submitted verification photo
     */
    public function verifyPhoto($userId, $verificationPhotoPath) {
        $verificationRequest = $this->getVerificationRequest($userId);
        if (!$verificationRequest) {
            return ['success' => false, 'error' => 'No verification request found'];
        }
        
        // Use AI/ML to verify the photo contains the challenge code
        $verificationResult = $this->analyzeVerificationPhoto($verificationPhotoPath, $verificationRequest['challenge']);
        
        if ($verificationResult['success']) {
            $this->markUserAsVerified($userId);
            $this->completeVerificationRequest($userId);
            
            return [
                'success' => true,
                'message' => 'Photo verification successful',
                'verification_badge' => true
            ];
        }
        
        return [
            'success' => false,
            'error' => $verificationResult['error'] ?? 'Verification failed'
        ];
    }
    
    /**
     * Privacy controls
     */
    public function setPrivacySetting($userId, $setting, $value) {
        $allowedSettings = [
            'show_online_status',
            'show_last_seen',
            'show_distance',
            'allow_photo_requests',
            'auto_share_photos',
            'show_in_discovery',
            'allow_message_requests'
        ];
        
        if (!in_array($setting, $allowedSettings)) {
            throw new Exception('Invalid privacy setting');
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO user_privacy_settings (user_id, setting_key, setting_value, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        
        $stmt->execute([$userId, $setting, $value, $value]);
        
        // Log privacy change
        $this->logPrivacyChange($userId, $setting, $value);
        
        return true;
    }
    
    /**
     * Get user privacy settings
     */
    public function getPrivacySettings($userId) {
        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value 
            FROM user_privacy_settings 
            WHERE user_id = ?
        ");
        
        $stmt->execute([$userId]);
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Apply defaults for missing settings
        $defaults = [
            'show_online_status' => 1,
            'show_last_seen' => 1,
            'show_distance' => 1,
            'allow_photo_requests' => 1,
            'auto_share_photos' => 0,
            'show_in_discovery' => 1,
            'allow_message_requests' => 1
        ];
        
        return array_merge($defaults, $settings);
    }
    
    /**
     * Rate limiting and abuse prevention
     */
    public function checkRateLimit($userId, $action, $limit = 10, $window = 3600) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM user_actions 
            WHERE user_id = ? 
            AND action_type = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $stmt->execute([$userId, $action, $window]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] < $limit;
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($userId, $event, $details = []) {
        $stmt = $this->db->prepare("
            INSERT INTO security_log (user_id, event_type, event_details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $event,
            json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    // Private helper methods
    private function getOrCreateEncryptionKey() {
        $keyPath = __DIR__ . '/.encryption_key';
        
        if (file_exists($keyPath)) {
            return file_get_contents($keyPath);
        }
        
        $key = random_bytes(32); // 256-bit key
        file_put_contents($keyPath, $key);
        chmod($keyPath, 0600); // Restrict access
        
        return $key;
    }
    
    private function generateSalt($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    private function generate2FASecret() {
        return base32_encode(random_bytes(20));
    }
    
    private function generate2FAQRCode($userId, $secret) {
        $appName = 'FWBer';
        $userEmail = $this->getUserEmail($userId);
        
        $qrUrl = "otpauth://totp/{$appName}:{$userEmail}?secret={$secret}&issuer={$appName}";
        
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrUrl);
    }
    
    private function verifyTOTP($secret, $token, $window = 1) {
        $timeStep = 30;
        $currentTime = floor(time() / $timeStep);
        
        for ($i = -$window; $i <= $window; $i++) {
            $testTime = $currentTime + $i;
            $expectedToken = $this->generateTOTP($secret, $testTime);
            
            if (hash_equals($expectedToken, $token)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function generateTOTP($secret, $time) {
        $binaryTime = pack('N*', 0) . pack('N*', $time);
        $hash = hash_hmac('sha1', $binaryTime, base32_decode($secret), true);
        
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    private function generatePhotoKey($userId) {
        return hash($this->config['hash_algorithm'], $this->encryptionKey . $userId . 'photos');
    }
    
    private function getOrCreateConversationKey($user1Id, $user2Id) {
        // Create deterministic key for conversation between two users
        $userIds = [$user1Id, $user2Id];
        sort($userIds);
        $conversationId = implode('_', $userIds);
        
        return hash($this->config['hash_algorithm'], $this->encryptionKey . $conversationId . 'messages');
    }

    private function storeSession($userId, $token) {
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['session_lifetime']);
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $token,
            $expiresAt,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    private function isValidSession($userId, $token) {
        $stmt = $this->db->prepare("
            SELECT 1 FROM user_sessions 
            WHERE user_id = ? AND session_token = ? AND expires_at > NOW() AND is_active = 1
        ");
        $stmt->execute([$userId, $token]);
        return $stmt->fetchColumn() !== false;
    }

    private function invalidateSession($token) {
        $stmt = $this->db->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_token = ?");
        $stmt->execute([$token]);
    }

    private function getUserEmail($userId) {
        $stmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    // Additional helper methods would be implemented here...
    // (Database operations, verification analysis, etc.)
}

// Base32 encoding/decoding functions for 2FA
function base32_encode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $v = 0;
    $vbits = 0;
    
    for ($i = 0; $i < strlen($data); $i++) {
        $v <<= 8;
        $v |= ord($data[$i]);
        $vbits += 8;
        
        while ($vbits >= 5) {
            $output .= $alphabet[($v >> ($vbits - 5)) & 31];
            $v <<= (32 - $vbits + 5);
            $v >>= (32 - $vbits + 5);
            $vbits -= 5;
        }
    }
    
    if ($vbits > 0) {
        $output .= $alphabet[($v << (5 - $vbits)) & 31];
    }
    
    return $output;
}

function base32_decode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $v = 0;
    $vbits = 0;
    
    for ($i = 0; $i < strlen($data); $i++) {
        $c = $data[$i];
        $val = strpos($alphabet, $c);
        if ($val === false) continue;
        
        $v <<= 5;
        $v |= $val;
        $vbits += 5;
        
        if ($vbits >= 8) {
            $output .= chr(($v >> ($vbits - 8)) & 255);
            $v <<= (32 - $vbits + 8);
            $v >>= (32 - $vbits + 8);
            $vbits -= 8;
        }
    }
    
    return $output;
}
?>