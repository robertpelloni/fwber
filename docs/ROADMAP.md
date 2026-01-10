# Roadmap

## 🚀 Phase 1: Core Foundation (Released v0.1.0)
- [x] User Authentication (Login, Register, JWT)
- [x] Profile Management
- [x] Geolocation Services (PostGIS)
- [x] Basic Matching Algorithm
- [x] Real-time Messaging (Mercure)

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

## 🔮 Phase 4: Advanced Features (Q1 2026)
- [x] Group Matching (Find other groups)
    - [x] Database Schema Updates
    - [x] Matching Algorithm Implementation
    - [x] Frontend UI for Group Discovery
- [x] AI Wingman (Chat suggestions)
- [x] Augmented Reality (AR) Meetups
    - [x] AR View Component (Camera + Overlay)
    - [x] Geolocation & Bearing Calculation
    - [x] Integration with Local Pulse
- [ ] Merchant Integration (Local business promotions)
    - [x] Sponsored promotions UI in Local Pulse (Jan 6)
    - [x] Backend API for merchant management (Complete)
    - [ ] Consumer-facing promotion discovery UI
    - [ ] Nearby deals map view
    - [ ] Analytics for promotion performance
- [ ] Advanced Analytics Dashboard
    - [ ] K-Factor (viral coefficient) tracking
    - [ ] User retention metrics
    - [ ] Engagement funnels
    - [ ] Revenue analytics (token + fiat)

## 🎯 Phase 4B: Missing Frontend UIs (Jan 2026 - CRITICAL)

**Backend exists, Frontend missing:**

- [ ] Achievements System UI
    - [ ] Achievements page in profile/dashboard
    - [ ] Progress tracking display
    - [ ] Badge display on profiles
    - [ ] Unlock notifications
- [ ] Proximity Chatrooms Discovery
    - [ ] Chatroom browsing/search interface
    - [ ] Create chatroom flow
    - [ ] Join nearby chatrooms map
    - [ ] Message reactions UI
    - [ ] Member management UI
- [ ] Paid Photo Reveals
    - [ ] Blurred photo preview component
    - [ ] Token payment unlock flow
    - [ ] Unlock history page
- [ ] Share-to-Unlock (Viral Growth)
    - [ ] Share buttons with incentive display
    - [ ] Progress tracking ("3 more shares to unlock")
    - [ ] Social platform integrations
- [ ] Profile Views ("Who Viewed You")
    - [ ] Viewer list page
    - [ ] View count display on profile
- [ ] Bulletin Boards
    - [ ] Board discovery page
    - [ ] Post/reply interface
    - [ ] Subscription management
- [ ] Extended Viral Content
    - [ ] Fortune generation UI
    - [ ] Cosmic match UI
    - [ ] Nemesis finder UI
- [ ] Group-to-Group Matching
    - [ ] Group match discovery page
    - [ ] Match request/accept flow
- [ ] Message Reactions
    - [ ] Reaction picker component
    - [ ] Reaction display on messages

## 🛠 Infrastructure & DevOps
- [x] CI/CD Pipelines
- [x] Docker Containerization
- [x] Automated Testing (Unit, Feature, E2E)
- [x] Real-time Migration (Mercure → Pusher → Reverb, Dec 2025)
- [ ] Kubernetes Deployment
- [ ] Horizontal Scaling Strategy
- [ ] Redis Caching Layer

## 🔧 Phase 4C: Technical Debt (Ongoing)
- [ ] Enable feature flags for production (chatrooms, recommendations, ai_wingman, video_chat)
- [ ] Replace mock content safety APIs with real moderation (AWS Rekognition/Google Vision)
- [ ] Re-enable Sentry error tracking in frontend
- [ ] Re-enable commented WebSocket and Bulletin Board routes
- [ ] Clean up legacy Mercure references
- [ ] Add missing tests for new UI features

## 📊 Phase 5: Growth & Optimization (Q2 2026)
- [ ] Premium Features (Fiat/Stripe) - LOW priority
- [ ] React Native Mobile App - LOW priority
- [ ] Multi-region deployment
- [ ] Advanced caching strategy
- [ ] Advanced Analytics Dashboard (K-Factor, retention, viral metrics)
