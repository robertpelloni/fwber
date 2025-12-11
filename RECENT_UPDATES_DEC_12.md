# Recent Updates - December 12, 2025

## Summary
Implemented advanced match filtering capabilities, including backend support for new filter criteria and a persistent frontend UI. Also finalized the chatroom typing indicator implementation.

## ✅ Completed Tasks

### 1. Advanced Match Filters
- **Backend**: Updated `MatchController` and `AIMatchingService` to support filtering by:
    -   Smoking Status
    -   Drinking Status
    -   Body Type
    -   Minimum Height
    -   Bio Existence
    -   Verification Status
- **Frontend**: Enhanced `MatchFilter` component with:
    -   Collapsible UI
    -   New filter fields (Lifestyle, Appearance, Demographics, Preferences)
    -   **Persistence**: Filters are now saved to `localStorage` and restored on page load.

### 2. Real-time Features
- **Backend**: Finalized `WebSocketController` and `WebSocketService` to support `chatroom_id` in typing indicators.

## 🔄 Git Commits
- `feat(backend): support chatroom typing indicators`
- `feat(match): implement advanced match filters`
- `feat(ui): persist match filters to local storage`
- `fix(backend): correct column mapping for match filters`
