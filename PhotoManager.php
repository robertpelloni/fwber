<?php

class PhotoManager {
    private $pdo;
    private $uploadDir = 'fwberImageStore/';
    private $allowedMimeTypes = ['image/jpeg', 'image/png'];
    private $maxFileSize = 50000000; // 50 MB

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function getPhotos($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM user_photos WHERE user_id = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function uploadPhoto($userId, $file, $isPrivate = true) {
        try {
            // 1. Validate the upload
            if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload error. Code: ' . ($file['error'] ?? 'Unknown'));
            }

            if ($file['size'] > $this->maxFileSize) {
                throw new Exception('File is too large.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                throw new Exception('Invalid file type.');
            }

            // 2. Generate a secure, unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = uniqid('img_', true) . '.' . $extension;
            $destination = $this->uploadDir . $newFilename;

            // 3. Move the file
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception('Failed to move uploaded file.');
            }

            // 4. Insert into the database
            $stmt = $this->pdo->prepare("
                INSERT INTO user_photos (user_id, filename, original_filename, file_size, mime_type, is_private)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $newFilename,
                htmlspecialchars($file['name']),
                $file['size'],
                $mimeType,
                $isPrivate ? 1 : 0
            ]);

            return ['success' => true, 'photo_id' => $this->pdo->lastInsertId()];

        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deletePhoto($userId, $photoId) {
        try {
            $this->pdo->beginTransaction();

            // 1. Verify ownership and get the filename
            $stmt = $this->pdo->prepare("SELECT filename FROM user_photos WHERE id = ? AND user_id = ?");
            $stmt->execute([$photoId, $userId]);
            $filename = $stmt->fetchColumn();

            if (!$filename) {
                // Photo not found or does not belong to the user
                throw new Exception('Photo not found or permission denied.');
            }

            // 2. Delete the file from the server
            $filePath = $this->uploadDir . $filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // 3. Delete the record from the database
            $stmt = $this->pdo->prepare("DELETE FROM user_photos WHERE id = ?");
            $stmt->execute([$photoId]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Photo delete failed: " . $e->getMessage());
            return false;
        }
    }
}
