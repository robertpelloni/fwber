# VISION.md — The Ultimate fwber Architecture & Philosophy

> **Last Updated:** 2026-02-27 by Claude (Antigravity)
> **Goal:** To serve as the definitive North Star for all human and AI agents working on the fwber project.

---

## 🚀 1. The Core Philosophy

**fwber** is not just another dating app. It is a **privacy-first, proximity-based social operating system** designed for the physical world. It actively rejects the gamified, swipe-based, infinite-scroll, superficial dopamine loops of traditional dating apps (Tinder, Bumble).

Instead, fwber facilitates **intentional, real-world connections** facilitated by technology that gets out of the way. We believe that technology should orchestrate real-life serendipity, not keep users glued to screens.

### 🌟 The Four Pillars
1. **Privacy is a Human Right**: In an age of surveillance capitalism, fwber is a sanctuary. We do not sell user data. We do not track exact GPS coordinates (fuzzy location only). We allow "Ghost Mode" and incognito browsing. Ultimately, we aim for mathematically proven privacy via **Zero-Knowledge Proximity Proofs**.
2. **The "Anti-Catfish" Guarantee**: By defaulting to **AI-generated Avatars** until a mutual match occurs, we eliminate superficial judgment and profile picture fraud. Physical attraction is revealed *after* a connection is established, or via secure, consensual "Face Reveals."
3. **Proximity is Power**: The most meaningful connections happen with the people around you right now. The **Local Pulse** is a real-time, ephemeral feed of the vibe of your immediate vicinity—not a global feed of influencers. It is the digital aura of a neighborhood.
4. **Safety by Design**: From **Geo-Spoof Detection** to **End-to-End Encrypted Messaging**, safety is baked into the architecture at the deepest levels, not added as a feature toggle.

---

## 🏗️ 2. Architectural Vision & Technology Stack

fwber aims to be a bleeding-edge, high-performance platform capable of handling extreme concurrency in dense urban environments (festivals, conferences, downtowns).

### The Current Stack (The Monolith Phase)
*   **Backend**: Laravel 12.x (PHP 8.3+) focusing on elegant REST APIs, deep FormRequest validation, and queued jobs.
*   **Frontend**: Next.js 16 (React 19) utilizing Server Components where applicable, PWA capabilities, and TailwindCSS for the "Cyber-Noir" aesthetic.
*   **Real-time**: Pusher/Reverb WebSockets for instant chat and typing indicators.
*   **Database**: PostgreSQL with PostGIS for spatial queries.

### The Future Stack (The Distributed Next-Gen Phase)
To achieve our ultimate vision, the architecture must evolve to support extreme scale and privacy:
*   **Rust Geo-Screener Microservices**: For high-density areas, we will implement Rust microservices utilizing H3 or S2 spatial indexing. This will calculate the Local Pulse in sub-millisecond time.
*   **Zero-Knowledge (ZK) Proximity**: Users will prove they are within a certain radius of an event or another user without ever revealing their exact coordinates to the server.
*   **CRDT Offline Sync**: Conflict-free Replicated Data Types to allow seamless offline chatting that resolves conflicts effortlessly when devices regain connection.
*   **Edge Compute**: Cloudflare Workers / Vercel Edge functions for initial request routing, location fuzzing, and static content delivery.

---

## 🔮 3. The Feature Horizon

### Currently Realized (Phase 1-4)
- **Identity & Discovery**: AI Avatars, Bio analysis, Local Pulse feed.
- **Engagement**: Relationship Tiers (Discovery -> Verified), Photo Reveals, Token Economy (FWB).
- **Communication**: E2E Encrypted Chat, Voice Memos, Proximity Chatrooms, Event Discussion Boards.
- **Viral Mechanics**: "Roast My Date", Wingman AI (Cosmic, Vibe, Nemesis).
- **Merchant Integration**: Sponsored local deals seamlessly integrated into the Pulse.

### The Next Frontier (Phase 5+)
- **Value-Aligned Matching**: Moving beyond physical compatibility to an AI-powered "Compatibility Audit" assessing shared life goals and ethical frameworks.
- **The AI Wingman Evolution**: A proactive agent that watches conversation flow and suggests real-time nudges ("You've been chatting for 3 days — suggest this nearby jazz event!").
- **Dynamic Emotional Avatars**: Avatars that shift their visual "aura" based on the user's recent sentiment and activity.
- **Neighborhood Social Layer**: Expanding the Local Pulse to serve as a hyper-local neighborhood forum ("Nextdoor meets modern dating").
- **Burner Communication Bridges**: Native ephemeral relay QR codes for anonymous out-of-band communication.

---

## 🎨 4. Design Language & UX

*   **Aesthetic**: "Neon-Professional" / "Cyber-Noir". Deep dark modes, subtle glowing accents, glassmorphism for overlays, but maintaining high-contrast typography and absolute usability.
*   **Experience**: Fluid, app-like interactions. Zero page reloads for core flows. Instant optimistic UI updates backed by robust error recovery. The app should feel *alive* but never overwhelming.
*   **Tone**: Confident, slightly edgy, yet universally respectful and safe.

---

## 🛡️ 5. AI Governance & Protocol

All AI agents (Claude, Gemini, GPT) working on this repository must adhere to the following universal directives:
1.  **Read the Documentation First**: Always sync with `AGENTS.md`, `TODO.md`, and `CHANGELOG.md` before writing code.
2.  **Avoid Destructive Laziness**: Do not leave `// TODO: Implement this` when asked to write a feature. Implement it fully with proper error handling and UI polishing.
3.  **No `console.log` in Production**: Use structured logging (`Log::info` in Laravel, `apiErrorHandling.ts` or custom hooks in Next.js).
4.  **UX Anti-Patterns**: Never use native `alert()` or `confirm()`. Use the established Toast system or custom Modals.
5.  **Autonomous Progression**: When executing a protocol, push through to completion. Anticipate edge cases. Update the `VERSION` and `CHANGELOG.md` autonomously.

---

*fwber is more than software. It is a movement to reclaim real-world connection from the algorithms that divide us.*
