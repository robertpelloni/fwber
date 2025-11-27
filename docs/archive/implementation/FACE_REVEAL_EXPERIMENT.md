# Face Reveal Experiment & Auto-Reply Design

## Overview
The "Face Reveal" feature gamifies privacy by allowing users to upload an original photo that is stored in an encrypted state. A blurred version is publicly visible. Users can "reveal" the unblurred version to specific matches after meeting certain criteria (e.g., X messages exchanged or mutual consent).

Additionally, an Auto-Reply system will be introduced to maintain engagement.

## 1. Database Schema

### `photo_reveals` Table
Tracks which users have access to which private photos.

```sql
CREATE TABLE photo_reveals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL, -- The owner of the photo
    match_id BIGINT UNSIGNED NOT NULL, -- The match/user being granted access
    photo_id BIGINT UNSIGNED NOT NULL, -- The photo being revealed
    status ENUM('pending', 'active', 'revoked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE, -- Assuming matches table exists
    FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
);
```

### `photos` Table Updates
We need to store the path to the encrypted original.

```sql
ALTER TABLE photos ADD COLUMN original_path VARCHAR(255) NULL AFTER path;
ALTER TABLE photos ADD COLUMN is_encrypted BOOLEAN DEFAULT FALSE AFTER original_path;
```

## 2. Key Management & Flow

1.  **Upload**:
    *   Client uploads photo.
    *   Server generates a blurred version (public).
    *   Server encrypts the original version using a symmetric key (e.g., AES-256).
    *   Encrypted original is stored in S3/Storage.
    *   Blurred version is stored in S3/Storage (publicly accessible or signed URL).

2.  **Viewing (Public)**:
    *   API returns the URL of the blurred photo.

3.  **Reveal Action**:
    *   User triggers "Reveal" for a match.
    *   Entry created in `photo_reveals`.

4.  **Viewing (Revealed)**:
    *   API checks `photo_reveals` table.
    *   If access exists:
        *   Server decrypts the original photo on the fly (or generates a temporary signed URL to a decrypted copy if using a secure media server).
        *   *Simpler MVP approach*: Server generates a temporary signed URL to an endpoint that streams the decrypted content.

## 3. Auto-Reply Service Interface

Simple rule-based responder.

```php
namespace App\Services;

interface AutoReplyServiceInterface
{
    /**
     * Determine if an auto-reply should be sent.
     *
     * @param int $userId The user receiving the message.
     * @param string $incomingMessage The message content.
     * @return bool
     */
    public function shouldReply(int $userId, string $incomingMessage): bool;

    /**
     * Generate the reply content.
     *
     * @param int $userId
     * @return string
     */
    public function generateReply(int $userId): string;

    /**
     * Send the reply.
     *
     * @param int $senderId The user sending the auto-reply (the offline user).
     * @param int $recipientId The user receiving the reply.
     * @param string $content
     * @return void
     */
    public function sendReply(int $senderId, int $recipientId, string $content): void;
}
```

## 4. API Endpoints (Draft)

*   `POST /api/photos/{photo}/reveal`
    *   Body: `{ "match_id": 123 }`
*   `GET /api/photos/{photo}/original`
    *   Middleware: Check `photo_reveals` permission.
    *   Response: Binary stream of decrypted image.

## 5. Next Steps
1.  Create migration for `photo_reveals` and `photos` update.
2.  Implement `PhotoRevealController`.
3.  Implement `AutoReplyService`.
