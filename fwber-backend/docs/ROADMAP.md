# FWBER Development Roadmap

## Vision Statement
A trustworthy, open-source proximity-based dating platform that combines precise mutual-match discovery with hyperlocal ephemeral communication. Built on principles of consent, fairness, privacy, and transparency.

---

## ✅ Phase 1: MVP Strategic Fusion (COMPLETE - Jan 2025)

### Core Features Delivered
- [x] Avatar-only enforcement system (AVATAR_MODE config)
- [x] Proximity Artifacts unified table (chat/board_post/announce)
- [x] Content sanitization (URL/email/phone blocking)
- [x] TTL-based expiry (45min/48h/2h by type)
- [x] Daily posting caps per user per type
- [x] Location fuzzing for privacy (±0.0008° jitter)
- [x] Flag escalation mechanics (3 flags → flagged)
- [x] Saturation penalty in match scoring
- [x] Local Pulse merged feed endpoint
- [x] Comprehensive test coverage (131 tests, 524 assertions)
- [x] ProximityArtifact factory and test infrastructure

### Technical Foundation
- Laravel 11.x backend
- SQLite for testing
- RESTful API architecture
- Service layer pattern
- Feature flag system
- Telemetry and audit logging
- Content moderation hooks
- Email notification system

---

## 🔄 Phase 2: Hardening & Safety (Q1 2025)

### Priority 1: Enhanced Moderation
- [ ] **Shadow Throttling System**
  - Reduce feed visibility for flagged users
  - Graduated penalties based on flag history
  - Auto-restore after good behavior period
  - Admin dashboard for review

- [ ] **Geo-Spoof Detection**
  - Impossible velocity detection (teleporting users)
  - GPS accuracy score integration
  - Location consistency checks
  - Suspicious pattern flagging

- [ ] **Moderation Dashboard**
  - Review queue for flagged content
  - User history and pattern analysis
  - Batch action capabilities
  - Appeal workflow

### Priority 2: Real-Time Features
- [ ] **Unified Event Stream**
  - WebSocket/SSE infrastructure
  - Live proximity artifact updates
  - Real-time match notifications
  - Presence indicators

- [ ] **Live Location Sharing** (opt-in)
  - Temporary location broadcast
  - Auto-expiry controls
  - Distance-based visibility
  - Safety timeout mechanisms

### Priority 3: Performance
- [ ] **Caching Layer**
  - Redis integration for Local Pulse queries
  - Geospatial index optimization
  - Query result caching (60s TTL)
  - Cache warming strategies

- [ ] **Background Jobs**
  - Scheduled prune command (5min intervals)
  - Async notification processing
  - Batch analytics aggregation
  - Database maintenance tasks

---

## 🎨 Phase 3: User Experience (Q2 2025)

### Frontend Development
- [ ] **Local Pulse UI Component**
  - Merged feed card design
  - Artifact type indicators
  - Candidate preview cards
  - Refresh/pull-to-refresh

- [ ] **Avatar Generation Flow**
  - Style selection interface
  - Preview and regeneration
  - Avatar gallery/history
  - Customization options (future)

- [ ] **Proximity Content Creation**
  - Quick post composer
  - Type selection (chat/board/announce)
  - Character count and validation
  - Location radius picker

- [ ] **Enhanced Match Discovery**
  - Swipe/stack interface
  - Compatibility breakdown
  - Distance visualization
  - Mutual interests highlights

### Mobile Optimization
- [ ] Progressive Web App (PWA)
- [ ] Push notification support
- [ ] Offline-first capabilities
- [ ] Touch gesture optimization

---

## 📚 Phase 4: Documentation & Community (Q2 2025)

### Open Source Preparation
- [ ] **LICENSE** - Select and document license (MIT/Apache 2.0)
- [ ] **CONTRIBUTING.md** - Contribution guidelines and code of conduct
- [ ] **PRIVACY.md** - Privacy policy and data handling practices
- [ ] **TERMS.md** - Terms of service draft
- [ ] **README Enhancement** - Quickstart, features, architecture
- [ ] **API Documentation** - OpenAPI/Swagger specification
- [ ] **Deployment Guide** - Docker, Kubernetes, cloud platforms

### Community Building
- [ ] Public repository launch
- [ ] Issue templates and labels
- [ ] Discussion forum setup
- [ ] Roadmap feedback mechanism
- [ ] Contributor recognition system

---

## 🚀 Phase 5: Growth & Scaling (Q3 2025)

### Advanced Features
- [ ] **Reactions & Threads**
  - Emoji reactions on artifacts
  - Threaded conversations
  - Reaction analytics

- [ ] **Seeded System Tips**
  - AI-generated helpful hints
  - Location-specific suggestions
  - Community guidelines reminders

- [ ] **Smart Recommendations**
  - ML-based compatibility scoring
  - Interest clustering
  - Behavioral pattern analysis

### Platform Scaling
- [ ] **Multi-Region Support**
  - Geographic sharding
  - CDN integration
  - Latency optimization

- [ ] **Adaptive Radius**
  - Dynamic visibility based on density
  - Rural vs urban adjustments
  - Time-based radius expansion

- [ ] **Semantic Clustering**
  - Interest-based grouping
  - Tag recommendations
  - Community discovery

---

## 💎 Phase 6: Monetization & Sustainability (Q4 2025)

### Ethical Revenue Streams
- [ ] **Optional Highlighting** (non-intrusive)
  - Temporary boost in Local Pulse
  - Clearly labeled as promoted
  - Fair rotation algorithm

- [ ] **Premium Features** (non-essential)
  - Extended artifact expiry
  - Advanced filtering options
  - Analytics and insights
  - All core features remain free

### Platform Health
- [ ] Advanced analytics dashboard
- [ ] A/B testing framework
- [ ] User feedback integration
- [ ] Success metrics tracking

---

## 🔧 Technical Debt & Maintenance (Ongoing)

### Code Quality
- [ ] Replace PHPUnit docblocks with attributes
- [ ] Increase unit test coverage (target: 90%)
- [ ] Static analysis integration (PHPStan level 8)
- [ ] Automated code formatting

### Infrastructure
- [ ] CI/CD pipeline optimization
- [ ] Automated security scanning
- [ ] Dependency updates automation
- [ ] Performance monitoring integration

### Documentation
- [ ] Inline code documentation improvement
- [ ] Architecture decision records (ADRs)
- [ ] Database schema documentation
- [ ] API changelog maintenance

---

## 📊 Success Metrics

### Phase 1 (MVP)
- ✅ Zero critical bugs at launch
- ✅ 100% test coverage for new features
- ✅ Sub-500ms API response times
- ✅ Content sanitizer blocking 100% of test patterns

### Phase 2 (Hardening)
- 99.9% uptime
- <100ms WebSocket latency
- 95% reduction in spam/abuse reports
- 80% cache hit rate

### Phase 3 (UX)
- 4.5+ app store rating
- <5s time to first artifact display
- 70% user retention after 7 days
- 50% weekly active user engagement

### Phase 4 (Community)
- 10+ external contributors
- 50+ GitHub stars
- Active community discussions
- Clear documentation feedback

### Phase 5 (Growth)
- Multi-region deployment
- 10k+ daily active users
- <200ms p95 global latency
- Positive unit economics

---

## 🤝 Contributing

This roadmap is a living document. Community input is valued and encouraged. To suggest changes:
1. Open a GitHub Discussion for new feature ideas
2. Submit PRs for roadmap updates
3. Vote on prioritization in quarterly planning

---

## 📅 Review Cadence

- **Weekly**: Sprint planning and tactical adjustments
- **Monthly**: Phase progress review and dependency unblocking
- **Quarterly**: Strategic direction validation and community feedback integration
- **Annually**: Vision alignment and major version planning

---

**Last Updated**: January 12, 2025  
**Current Phase**: Phase 1 Complete → Phase 2 Planning  
**Next Milestone**: Shadow Throttling System (Feb 2025)
