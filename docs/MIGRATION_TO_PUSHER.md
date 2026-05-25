# Migration from Mercure to Pusher

**Date:** December 26, 2025
**Status:** Complete

## Overview
The real-time infrastructure has been migrated from Mercure to Pusher (using Laravel Broadcasting and Laravel Echo). This change simplifies the stack and leverages standard Laravel features.

## Changes

### Backend (`fwber-backend`)
1.  **Configuration**:
    -   Updated `routes/api.php` to include `Broadcast::routes()`.
    -   Created `routes/channels.php` to define channel authorization rules.
    -   Updated `bootstrap/app.php` to load the broadcasting routes.
2.  **Services**:
    -   Rewrote `App\Services\WebSocketService` to use Laravel Events and the `broadcast()` helper.
    -   The service now dispatches events instead of making HTTP requests to a Mercure hub.
3.  **Events**:
    -   Created the following Event classes in `App\Events`:
        -   `ChatMessageSent`
        -   `NotificationSent`
        -   `TypingIndicator`
        -   `VideoSignal`
        -   `PresenceUpdate`
    -   All events implement `ShouldBroadcast` and use the `private` channel type (except potentially presence channels in the future).

### Frontend (`fwber-frontend`)
1.  **Hooks**:
    -   Created `lib/hooks/use-pusher-logic.ts`: Contains the core logic for connecting to Pusher/Echo, listening for events, and managing state.
    -   Updated `lib/hooks/use-mercure-logic.ts`: Now acts as a compatibility shim. It internally uses `usePusherLogic` but exports the same interface as before. This ensures existing components (`RealTimeChat`, `MercureContext`, etc.) continue to work without modification.
2.  **Configuration**:
    -   Created `lib/echo.ts`: Utility to initialize the Laravel Echo instance with Pusher.

## Required Actions

### 1. Environment Variables
You must update your `.env` files in both backend and frontend.

**Backend (`fwber-backend/.env`):**
```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1
```

**Frontend (`fwber-frontend/.env.local`):**
```env
NEXT_PUBLIC_PUSHER_APP_KEY=your_app_key
NEXT_PUBLIC_PUSHER_APP_CLUSTER=mt1
```

### 2. Verification
1.  Restart your backend server (`php artisan serve`).
2.  Restart your frontend server (`npm run dev`).
3.  Open the application and check the browser console. You should see Pusher connection logs (if enabled).
4.  Test chat functionality. Messages should be delivered via Pusher.

## Notes
-   The `MercureContext` and `useMercure` hook names have been preserved to minimize code churn, but they now power the Pusher implementation.
-   Future refactoring can rename these to `WebSocketContext` and `useWebSocket` for clarity.
