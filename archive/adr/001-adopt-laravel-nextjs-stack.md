# ADR 001: Adopt Laravel + Next.js as Official Technology Stack

**AI Model:** Gemini 2.5 Pro - Simulated  
**Date:** 2025-10-18  
**Status:** ✅ **ACCEPTED**

---

## Context

FWBer.me currently maintains three parallel technology implementations:

1. **Legacy PHP (2011-era)**: Original mysqli-based application
   - 481 lines in `_getMatches.php` alone
   - SQL injection vulnerabilities via string concatenation
   - No modern security practices
   - Difficult to maintain and extend

2. **Modernized Secure PHP (2025)**: Hardened version
   - PDO prepared statements
   - Argon2ID password hashing
   - CSRF protection
   - AES-256-CBC encryption
   - Well-designed Manager classes (SecurityManager, ProfileManager, PhotoManager)

3. **Laravel 12 + Next.js 15 (2025)**: Modern full-stack
   - Eloquent ORM with 9 well-designed models
   - Service layer architecture (AIMatchingService, RealTimeCommunicationService, etc.)
   - RESTful API design
   - Next.js frontend with TypeScript, Tailwind CSS v4
   - ML-ready AI matching with behavioral analysis

**The Problem:**
- **1,389 lines of duplicated matching logic** across three implementations
- **3x maintenance burden** - bugs must be fixed in multiple places
- **Inconsistent user experience** - different code paths may produce different results
- **Unclear deployment strategy** - which system is "production"?
- **Growing technical debt** - parallel systems drift further apart over time

**Multi-AI Analysis:**
- Architecture review by Gemini 2.5 Pro identified this as the #1 architectural issue
- Security audit by GPT-5-Codex found security risks in legacy code
- Consensus by GPT-5 and Gemini 2.5 Flash confirmed migration is critical

---

## Decision

**We adopt Laravel 12 (backend) + Next.js 15 (frontend) as the single, official technology stack for FWBer.me.**

### Specific Commitments:

1. **All new features** MUST be implemented in Laravel/Next.js only
2. **Legacy PHP is FROZEN** - bug fixes only, no new features
3. **Modernized PHP serves as bridge** - Security fixes continue, but no expansion
4. **Migration timeline:** 6 months (complete by Q2 2026)
5. **Enforcement:** CI/CD checks prevent new code in legacy directories

### Technical Stack Details:

**Backend:**
- Laravel 12.x
- PHP 8.2+
- MySQL 8.0+ (with planned PostgreSQL migration evaluation)
- Redis for caching
- Laravel Sanctum for API authentication

**Frontend:**
- Next.js 15.x with Turbopack
- React 19
- TypeScript 5.x
- Tailwind CSS v4
- NextAuth.js v5 for session management

**Infrastructure:**
- Docker containers for services
- GitHub Actions for CI/CD
- Environment-based configuration (12-factor app)

---

## Consequences

### Positive

1. **Single Source of Truth**
   - One codebase to maintain
   - Consistent business logic
   - Clear ownership and responsibility

2. **Modern Development Velocity**
   - TypeScript for type safety
   - Hot module replacement (Turbopack)
   - Component-based UI (React)
   - API-first architecture

3. **Security by Default**
   - Laravel's built-in protections (CSRF, XSS, SQL injection)
   - Eloquent ORM prevents raw SQL
   - Modern authentication patterns

4. **Scalability**
   - Horizontal scaling with stateless APIs
   - CDN-ready frontend (Next.js)
   - Caching layers (Redis)
   - Database read replicas ready

5. **AI/ML Integration**
   - AIMatchingService designed for machine learning
   - Behavioral analysis framework in place
   - Easy to add recommendation engines

### Negative

1. **Migration Effort Required**
   - Estimated 160-240 developer hours
   - Must maintain dual systems during transition
   - Risk of bugs during cutover
   - User data migration complexity

2. **Team Learning Curve**
   - Laravel framework knowledge required
   - TypeScript/React skills needed
   - Modern DevOps practices
   - API design patterns

3. **Infrastructure Changes**
   - Need Docker environment
   - CI/CD pipeline setup
   - Environment variable management
   - Database migration tooling

4. **Temporary Complexity**
   - Must maintain bridge code during migration
   - Two authentication systems in parallel
   - Routing complexity (old vs new)
   - Testing both stacks

---

## Alternatives Considered

### Alternative 1: Continue with Modernized PHP Only
**Rejected because:**
- Still server-side rendered (slower UX)
- No clear path to modern frontend
- Limited scaling compared to Laravel
- Misses benefits of API-first architecture

### Alternative 2: Gradual TypeScript Migration of Existing PHP
**Rejected because:**
- PHP and TypeScript don't integrate well
- Would create fourth parallel implementation
- Doesn't solve architectural fragmentation
- Higher complexity, lower benefit

### Alternative 3: Microservices with Multiple Stacks
**Rejected because:**
- Too complex for current team size
- Operational overhead too high
- Doesn't solve code duplication
- Over-engineering for current scale

---

## Migration Strategy

### Phase 1: Foundation (Weeks 1-2) ✅
- [x] Fix encryption key storage (security-manager.php)
- [x] Eliminate SQL injection (secure wrapper for _getMatches.php)
- [ ] Create this ADR
- [ ] Create legacy deprecation timeline

### Phase 2: API Integration (Weeks 3-4)
- [ ] Laravel API: User profile endpoint
- [ ] Next.js: Profile display page
- [ ] Authentication flow (NextAuth → Laravel Sanctum)
- [ ] Set up CORS and API middleware

### Phase 3: Feature Parity (Weeks 5-12)
- [ ] Matches display (Next.js + AIMatchingService)
- [ ] Profile editing
- [ ] Photo management
- [ ] Messaging system

### Phase 4: New Features on Modern Stack (Weeks 13-20)
- [ ] Block/report functionality
- [ ] Real-time notifications
- [ ] Enhanced AI matching
- [ ] PWA features

### Phase 5: Legacy Sunset (Weeks 21-24)
- [ ] Migrate remaining users
- [ ] Archive legacy code
- [ ] Remove old database tables
- [ ] Full cutover to new stack

---

## Enforcement Mechanisms

### 1. Directory Structure
```
/                          # Legacy (FROZEN - bug fixes only)
/fwber-backend/            # Official backend (ALL new features)
/fwber-frontend/           # Official frontend (ALL new features)
```

### 2. CI/CD Checks
```yaml
# .github/workflows/enforce-stack.yml
- name: Block legacy feature development
  run: |
    # Fail if new .php files added to root (outside fwber-backend)
    # Fail if new features added to _*.php files
    # Allow only security/bug fixes in legacy
```

### 3. Code Ownership (CODEOWNERS)
```
# Legacy - Requires security team approval
/*.php @security-team
/_*.php @security-team

# Official Stack - Normal review process
/fwber-backend/ @backend-team
/fwber-frontend/ @frontend-team
```

### 4. Documentation
- Update README.md with "Official Stack" badge
- Add deprecation warnings to legacy files
- Create migration guide for developers

---

## Success Criteria

### Technical
- [ ] 100% feature parity in Laravel/Next.js
- [ ] Zero security regressions
- [ ] API response time < 200ms (p95)
- [ ] Frontend load time < 2s
- [ ] Test coverage > 80%

### Business
- [ ] No user-facing disruption during migration
- [ ] Zero data loss
- [ ] Improved development velocity (features ship faster)
- [ ] Reduced bug rate (single codebase)

### Timeline
- [ ] Complete migration by Q2 2026 (6 months)
- [ ] Legacy code archived by Q3 2026
- [ ] Full production deployment by Q3 2026

---

## References

- **Security Audit:** Multi-AI analysis (GPT-5-Codex) - 8.5/10 rating
- **Architecture Review:** Gemini 2.5 Pro analysis - Identified fragmentation
- **Priority Consensus:** GPT-5 + Gemini 2.5 Flash - 9/10 confidence
- **Development Plan:** FWBER_MULTI_AI_DEVELOPMENT_PLAN.md
- **Implementation Guide:** PHASE_1_IMPLEMENTATION_GUIDE.md

---

## Decision Makers

- **AI Models Consulted:** GPT-5-Codex, Gemini 2.5 Pro, GPT-5, Gemini 2.5 Flash
- **Consensus Level:** 9/10 (High)
- **Approved By:** Multi-AI Orchestration Session 2025-10-18

---

**Last Updated:** 2025-10-18  
**Review Date:** 2026-01-18 (3 months - assess progress)
