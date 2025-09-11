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
    
    public function hashPassword($password, $salt = null) {
        if (!$salt) {
            $salt = $this->generateSalt();
        }
        
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
    
    public function verifyPassword($password, $hash, $salt) {
        return password_verify($password . $salt, $hash);
    }
    
    public function encryptData($data, $key = null) {
        if (!$key) {
            $key = $this->encryptionKey;
        }
        
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, $this->config['encryption_method'], $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    public function decryptData($encryptedData, $key = null) {
        if (!$key) {
            $key = $this->encryptionKey;
        }
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, $this->config['encryption_method'], $key, 0, $iv);
    }
    
    public function generateSessionToken($userId) {
        $tokenData = [
            'user_id' => $userId,
            'created_at' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'random' => bin2hex(random_bytes(16))
        ];
        
        $token = $this->encryptData(json_encode($tokenData));
        
        $this->storeSession($userId, $token);
        
        return $token;
    }
    
    public function validateSession($token) {
        try {
            $tokenData = json_decode($this->decryptData($token), true);
            
            if (!$tokenData || !isset($tokenData['user_id'])) {
                return false;
            }
            
            if (!$this->isValidSession($tokenData['user_id'], $token)) {
                return false;
            }
            
            if (time() - $tokenData['created_at'] > $this->config['session_lifetime']) {
                $this->invalidateSession($token);
                return false;
            }
            
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

    public function invalidateSession($token) {
        $stmt = $this->db->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_token = ?");
        $stmt->execute([$token]);
    }
    
    public function setup2FA($userId) {
        $secret = $this->generate2FASecret();
        
        $encryptedSecret = $this->encryptData($secret);
        $this->store2FASecret($userId, $encryptedSecret);
        
        return [
            'secret' => $secret,
            'qr_code' => $this->generate2FAQRCode($userId, $secret)
        ];
    }
    
    public function verify2FA($userId, $token) {
        $encryptedSecret = $this->get2FASecret($userId);
        if (!$encryptedSecret) {
            return false;
        }
        
        $secret = $this->decryptData($encryptedSecret);
        return $this->verifyTOTP($secret, $token);
    }
    
    // ... (other methods remain the same)

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

    private function getOrCreateEncryptionKey() {
        $keyPath = __DIR__ . '/.encryption_key';
        
        if (file_exists($keyPath)) {
            return file_get_contents($keyPath);
        }
        
        $key = random_bytes(32);
        file_put_contents($keyPath, $key);
        chmod($keyPath, 0600);
        
        return $key;
    }
    
    private function generateSalt($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // ... (rest of private methods)
}

// ... (base32 functions)
?>
