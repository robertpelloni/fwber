# Backend Design Document

## 1. Technology Stack
The fwber backend leverages a modern, high-performance stack built for concurrency and maintainability.

*   **Runtime:** Node.js using ECMAScript Modules (ESM)
*   **Language:** TypeScript
*   **Framework:** Express.js for routing and HTTP handling
*   **ORM:** Prisma ORM for database modeling and migrations
*   **Database:** Compatible with PostgreSQL or MySQL
*   **Microservices:** Rust (`fwber-geo`) for spatial indexing (H3-js) and caching (Redis Bloom Filters)

---

## 2. Core API Endpoints

### Zero-Knowledge Identity Management
Handles anonymous verification without exposing raw PII.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/identity/verify/init` | Initializes a ZK-proof generation request. Returns a challenge payload. |
| `POST` | `/api/identity/verify/submit` | Submits the generated zero-knowledge proof for on-chain or off-chain validation. |
| `GET` | `/api/identity/status` | Returns the current verification status and level of the authenticated user. |
| `POST` | `/api/identity/revoke` | Revokes the current identity verification state. |

### Local Pulse Feed
Powers the hyper-local, proximity-based discovery feed.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/pulse/feed` | Retrieves artifacts based on the user's location (`lat`, `lng`, `radius_m`, `limit`). |
| `POST` | `/api/pulse/post` | Creates a new artifact in the Local Pulse (`content`, `type`, `media_url`). |
| `POST` | `/api/pulse/:id/react` | Adds a reaction/upvote to a specific pulse artifact. |
| `POST` | `/api/pulse/:id/reply` | Adds a threaded reply to a specific pulse artifact. |
| `DELETE`| `/api/pulse/:id` | Deletes a user's own pulse post. |

---

## 3. Prisma Data Models

The following schemas define the necessary data structures to support the features above. Note that `fwber` relies heavily on `BigInt` for primary keys.

### Identity Management

```prisma
model user_profiles {
  id                     BigInt    @id @default(autoincrement()) @db.UnsignedBigInt
  user_id                BigInt    @unique @db.UnsignedBigInt
  display_name           String?   @db.VarChar(100)

  // ZK Verification Fields
  verification_status    String?   @default("unverified") @db.VarChar(50) // 'unverified', 'zk_pending', 'verified'
  zk_proof_hash          String?   @db.VarChar(255)
  zk_verification_level  Int?      @default(0) // 0 = none, 1 = basic, 2 = biometric

  users                  users     @relation(fields: [user_id], references: [id], onDelete: Cascade)
}
```

### Local Pulse Artifacts

```prisma
model proximity_artifacts {
  id                       BigInt                     @id @default(autoincrement()) @db.UnsignedBigInt
  user_id                  BigInt?                    @db.UnsignedBigInt
  type                     String                     @db.VarChar(255) // 'standard', 'deal', 'event'
  content                  String                     @db.Text
  media_url                String?                    @db.VarChar(500)
  location_lat             Decimal                    @db.Decimal(10, 7)
  location_lng             Decimal                    @db.Decimal(10, 7)
  visibility_radius_m      Int                        @default(1000) @db.UnsignedInt
  moderation_status        String                     @default("clean") @db.VarChar(255)
  meta                     Json?
  expires_at               DateTime                   @db.Timestamp(0)
  created_at               DateTime?                  @default(now()) @db.Timestamp(0)
  updated_at               DateTime?                  @updatedAt @db.Timestamp(0)

  users                    users?                     @relation(fields: [user_id], references: [id], onDelete: SetNull)
  artifact_reactions       proximity_artifact_reactions[]
  artifact_replies         proximity_artifact_replies[]

  @@index([location_lat, location_lng])
  @@index([expires_at])
}

model proximity_artifact_reactions {
  id            BigInt              @id @default(autoincrement()) @db.UnsignedBigInt
  artifact_id   BigInt              @db.UnsignedBigInt
  user_id       BigInt              @db.UnsignedBigInt
  reaction_type String              @db.VarChar(50) // 'like', 'fire'
  created_at    DateTime            @default(now()) @db.Timestamp(0)

  artifact      proximity_artifacts @relation(fields: [artifact_id], references: [id], onDelete: Cascade)
  user          users               @relation(fields: [user_id], references: [id], onDelete: Cascade)

  @@unique([artifact_id, user_id]) // One reaction per user per artifact
}

model proximity_artifact_replies {
  id            BigInt              @id @default(autoincrement()) @db.UnsignedBigInt
  artifact_id   BigInt              @db.UnsignedBigInt
  user_id       BigInt              @db.UnsignedBigInt
  content       String              @db.Text
  created_at    DateTime            @default(now()) @db.Timestamp(0)
  updated_at    DateTime            @updatedAt @db.Timestamp(0)

  artifact      proximity_artifacts @relation(fields: [artifact_id], references: [id], onDelete: Cascade)
  user          users               @relation(fields: [user_id], references: [id], onDelete: Cascade)
}
```
