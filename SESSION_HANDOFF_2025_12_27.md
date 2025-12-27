# Session Handoff - 2025-12-27

## 📝 Summary
Successfully migrated the real-time infrastructure from Mercure to Pusher (Laravel Echo). This involved updating the frontend hooks, backend event broadcasting, and configuration files. Also refactored the LLM instruction set to a centralized `docs/LLM_INSTRUCTIONS.md` protocol.

## 🛠️ Technical Changes

### 1. Real-time Migration (Mercure -> Pusher)
*   **Frontend:**
    *   Refactored `use-bulletin-boards.ts` to use `usePusherLogic`.
    *   Shimmed `use-mercure-sse.ts` to redirect to Pusher logic.
    *   Removed Mercure rewrite from `next.config.js`.
    *   Fixed TypeScript errors in `use-pusher-logic.ts` (added `echo` export).
*   **Backend:**
    *   Created `BulletinMessageCreated` and `BulletinBoardActivity` events implementing `ShouldBroadcast`.
    *   Updated `BulletinBoardController` to dispatch these events instead of using `MercurePublisher`.
    *   Updated `config/broadcasting.php` to respect `BROADCAST_DRIVER` env var.

### 2. Documentation & Protocol
*   **Centralized Instructions:** Created `docs/LLM_INSTRUCTIONS.md` as the master protocol.
*   **Agent Files:** Updated `CLAUDE.md`, `AGENTS.md`, `GEMINI.md`, `GPT.md` to reference the master protocol.
*   **Structure:** Updated `docs/PROJECT_STRUCTURE.md` with current layout and versioning strategy.

### 3. Fixes
*   **Frontend Build:** Resolved React 19 peer dependency conflicts via `package.json` overrides.
*   **Backend Tests:** Fixed `sign_message.cjs` script for Solana wallet tests (bs58 import issue).

## ⏭️ Next Steps
1.  **Environment Variables:**
    *   **Backend:** Set `BROADCAST_DRIVER=pusher` and add Pusher credentials.
    *   **Frontend:** Add `NEXT_PUBLIC_PUSHER_APP_KEY` and `NEXT_PUBLIC_PUSHER_APP_CLUSTER`.
2.  **Verification:**
    *   Test Bulletin Board real-time updates manually.
    *   Verify no console errors related to `EventSource` or `demo.mercure.rocks`.
3.  **Feature Development:**
    *   Proceed with "Video Chat" or "Face Blur" features as per Roadmap.

## 📦 Versioning
*   **Current Version:** 0.3.12 (Bumped from 0.3.11)
*   **Changelog:** Updated with migration details.
