# Core Features Roadmap (MVP Focus) – 2025-11-09

This document captures the minimal, high-impact next steps for the dating/social MVP. It intentionally avoids premature complexity while leaving room for future expansion.

## P0 – Immediate Implementation Targets
| Feature | Purpose | Implementation Location | Acceptance Criteria | Flag |
|---------|---------|-------------------------|---------------------|------|
| Mutual Match → Auto Chat | Convert reciprocal interest into conversation instantly | `MatchController::action` | On second reciprocal like: chatroom created once, system message inserted | `auto_chat_on_match` |
| Safe Message Send (baseline) | Enable 1:1 messaging with block + light moderation | `ChatroomMessageController::store` | Valid send returns 200 + id; blocked/flagged returns 422 | (existing moderation flag) |
| Minimal Notifications (email only) | Re-engage users when match/message arrives & offline | Match + message hooks | Single email per event type within debounce window | `email_notifications` (optional) |
| Onboarding Completion Gate | Ensure profile completeness for better match quality | Middleware or profile check in feed | Incomplete profile hits 400 with helpful message | (reuse existing feed flag) |
| Feed Quality Polish | Improve relevance with simple filters + freshness | `MatchController::findMatches` | p95 < 400ms; filters applied; empty gracefully | `matching_feed_v1` |

## P1 – After Core Loop Validates
| Feature | Purpose | Notes |
|---------|---------|-------|
| Read Receipts | Light engagement signal | Add `read_at` column and update on fetch |
| Last Seen Presence | Basic activity indicator | Update `last_seen` on auth + message send |
| Simple Filters UI | User control (age/distance) | Extend request params safely |

## Telemetry Hooks (Lean)
| Event | Trigger |
|-------|--------|
| user.signup | After successful registration |
| feed.viewed | After feed constructed (already added) |
| message.sent | After successful message persist |
| message.received | On recipient fetch (optional later) |
| moderation.flagged/actioned | During message moderation flow |

## Data Model Touch Points
- Chatrooms: ensure uniqueness for pair; use consistent ordering rule (lower user_id first) for lookup key.
- Chatroom Messages: add system message type (if not present, use `from_user_id = null`).
- Match Actions: record like/pass; on reciprocal like invoke auto chat logic under flag.

## Risk Guardrails
| Risk | Mitigation |
|------|------------|
| Duplicate chatrooms on race | Transaction + deterministic pair key |
| Email spam | Debounce per user/event (cache 15–30 min) |
| Moderation latency | Short timeout, safe approve fallback for MVP |
| Feed perf issues | Lightweight caching (60s) per user |

## Rollout Strategy
1. Implement mutual match auto-chat (flag off by default → enable after test).
2. Add message send moderation check + telemetry.
3. Introduce optional email notifications (deferred if complexity high).
4. Tighten feed & onboarding gate.

## Test Coverage Plan
| Test Name | Scenario |
|-----------|----------|
| MutualMatchChatroomTest | Two users like each other → chatroom created, system message present |
| MessageSendModerationTest | Message blocked by user or moderated content rejected |
| FeedProfileGateTest | Incomplete profile blocked from feed |
| FeedFiltersTest | Returned matches respect basic filters |

## Flags to Add
- `FLAG_AUTO_CHAT_ON_MATCH` (default false)
- `FLAG_EMAIL_NOTIFICATIONS` (default false)

## Next Action
Proceed with Feature 1: Mutual Match → Auto Chat.

---
Generated: 2025-11-09
