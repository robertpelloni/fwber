# Feedback System

## Overview
The Feedback System allows users to submit feedback directly from the frontend (via `FeedbackModal`), which is stored in the database and optionally analyzed by AI for categorization and sentiment analysis.

## API Specification

### POST /api/feedback
Submit new feedback.

**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Body:**
```json
{
  "category": "bug" | "feature" | "general" | "safety",
  "message": "string (max 2000 chars)",
  "page_url": "string (optional)",
  "metadata": {
    "userAgent": "string",
    "screenSize": "string",
    ...
  }
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Thank you for your feedback!",
  "data": {
    "id": 123,
    "category": "bug",
    "message": "...",
    "created_at": "..."
  }
}
```

### GET /api/feedback
List all feedback (Admin/Moderator only).

**Headers:**
- `Authorization: Bearer <token>`

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "category": "bug",
      "message": "...",
      "status": "new",
      "sentiment": "negative",
      "ai_analysis": "User reporting login crash.",
      "created_at": "..."
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

### PUT /api/feedback/{id}
Update feedback status (Admin/Moderator only).

**Body:**
```json
{
  "status": "new" | "reviewed" | "resolved" | "dismissed"
}
```

## Backend Implementation
- **Controller**: `App\Http\Controllers\FeedbackController`
- **Model**: `App\Models\Feedback`
- **Job**: `App\Jobs\AnalyzeFeedback` (handles AI analysis)
- **Migrations**:
  - `2025_12_09_094138_create_feedback_table.php`
  - `2025_12_15_112151_add_analysis_to_feedback_table.php`

## Frontend Implementation
- **Component**: `components/FeedbackModal.tsx`
- **API Client**: `lib/api/feedback.ts`

## AI Analysis
When feedback is submitted, the `AnalyzeFeedback` job is dispatched. It uses the configured LLM driver to:
1.  Determine the true category (e.g., reclassify "general" to "bug" if appropriate).
2.  Analyze sentiment.
3.  Generate a short summary.
