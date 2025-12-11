# Recent Updates - December 11, 2025

## Summary
Implemented visual polish for the Logo, added typing indicators for chatrooms, integrated Photo Reveal into the discovery flow, and added Dark Mode support to the Matches page.

## ✅ Completed Tasks

### 1. Visual Polish
- **Logo**: Updated `Logo.tsx` with a unified gradient cycle and outer glow effect for better visual appeal.

### 2. Real-time Features
- **Chatroom Typing**: Updated `useMercureLogic` and `PresenceComponents` to support typing indicators in chatrooms (filtering by `chatroom_id`).
- **Context**: Updated `MercureContext` to expose the new typing indicator signature.

### 3. User Experience Refinement
- **Photo Reveal**: Integrated `PhotoRevealGate` into `MatchesPage` to preview the photo unlock progression during discovery.
- **Dark Mode**: Added comprehensive dark mode support to `MatchesPage` (backgrounds, text colors, borders).

## 🔄 Git Commits
- `feat(ui): polish logo with outer glow and unified gradient cycle` (implied)
- `feat(realtime): implement typing indicators for chatrooms`
- `feat(ui): integrate PhotoRevealGate into matches discovery view`
- `feat(ui): add dark mode support to matches page`
