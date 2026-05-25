# Roadmap

## 🚀 Phase 1: Core Foundation (Released v0.1.0)
- [x] User Authentication (Login, Register, JWT)
- [x] Profile Management
- [x] Geolocation Services (PostGIS)
- [x] Basic Matching Algorithm
- [x] Real-time Messaging (Mercure/Reverb)

## 🎨 Phase 2: Enhanced User Experience (Released v0.2.0)
- [x] Photo Upload & Gallery
- [x] Push Notifications
- [x] Relationship Tiers (Friend, Close Friend, Date)
- [x] Privacy Controls (Ghost Mode, Location Fuzzing)
- [x] Block & Report User

## 🤝 Phase 3: Social Dynamics (Released v0.3.0)
- [x] Groups Creation & Management
- [x] Group Chat
- [x] Video Chat (WebRTC)
- [x] Token Economy (Basic)
- [x] Events & Ticketing

### 5C. Optimization & Compliance (Completed)
- [x] CDN Integration for Images.
- [x] GDPR/CCPA Data Export Tools in `DataExportController`.
- [x] Performance Audit (N+1 Query Elimination).
- [x] Deep Parity Check

### Parity Audit Findings (Phase 5D - Complete)
- [x] **Privacy Controls**: Ghost Mode, Location Fuzzing toggles added to user settings.
- [x] **Moderation Tools**: Driver switching UI (`GoogleVisionDriver`) added to Admin Settings.
- [x] **Merchant Analytics**: Dedicated Merchant Dashboard UI is complete.

## 🔮 Phase 4: Advanced Features (Q1 2026)
- [x] Group Matching (Find other groups)
- [x] AI Wingman (Chat suggestions)
- [x] Augmented Reality (AR) Meetups
- [x] Merchant Integration (Local business promotions)
    - [x] Sponsored promotions UI in Local Pulse
    - [x] Backend API for merchant management
    - [x] Consumer-facing promotion discovery UI (`app/deals/page.tsx`)
    - [x] Merchant Analytics Dashboard (Integrated v0.3.34)

## 🎯 Phase 4B: Feature Completeness (Jan 2026) ✅ COMPLETE
- [x] Achievements System UI (`/achievements`)
- [x] Proximity Chatrooms Discovery (`/proximity-chatrooms`)
- [x] Paid Photo Reveals (`/photos/reveals`)
- [x] Share-to-Unlock (`/share-unlock`)
- [x] Profile Views (`/profile-views`)
- [x] Bulletin Boards (`/bulletin-boards`)
- [x] Extended Viral Content (Fortune, Cosmic, Nemesis, Vibe, Roast)
- [x] Admin System Dashboard (`/admin/system`)
- [x] Physical Profile Settings (`/settings/physical-profile`)
- [x] Admin Logs Viewer (`/admin/logs`)

## 🛠 Infrastructure & DevOps
- [x] CI/CD Pipelines
- [x] Docker Containerization
- [x] Automated Testing (Unit, Feature, E2E)
- [x] Real-time Migration (Mercure → Reverb, Feb 2026)
- [x] Sentry Integration (Frontend/Backend)
- [x] Terms of Service / Privacy Policy final review

## 🔮 Phase 6: Future Horizons (Q3 2026)
- [x] React Native Mobile App (Native wrapper)
- [x] Multi-region deployment (Geo-DNS)
- [x] Voice/Audio Dating Rooms (Clubhouse style)
- [x] Burner Communication Bridge

---

**Last Updated:** 2026-03-06
**Status:** Phase 4 COMPLETE, Phase 6 COMPLETE.

## Phase 9: GraphQL Migration & Data Optimization (Planned)
*   **Target:** Migrate heavily relational REST endpoints to GraphQL to prevent over-fetching on the frontend.
*   **Key Deliverables:**
    *   Implement Apollo Server on `fwber-backend-ts`.
    *   Create GraphQL schemas and resolvers mapped to Prisma.
    *   Migrate `UserProfile`, `Dashboard`, and `Matches` frontend components to use Apollo Client.
*   **Status:** In Design Phase.

## Phase 10: Web3 & Decentralized Identity (Future)
*   **Target:** Integrate verifiable credentials (W3C DIDs) and smart contracts for merchant promotions.
*   **Status:** Ideation.
