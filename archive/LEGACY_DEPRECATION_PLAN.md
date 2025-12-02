# Legacy PHP Deprecation Plan

**AI Model:** GPT-5 - Simulated  
**Date:** 2025-10-18  
**Status:** 📋 **ACTIVE PLAN**  
**Target Completion:** Q2 2026 (6 months)

---

## Executive Summary

This document outlines the systematic deprecation and replacement of the legacy PHP codebase (2011-era) with the official Laravel + Next.js stack (per ADR 001).

**Scope:** 100+ legacy PHP files  
**Timeline:** 24 weeks (6 months)  
**Risk Level:** MEDIUM (manageable with proper planning)  
**Dependencies:** ADR 001, Next.js integration complete

---

## Phase 1: Inventory & Classification (Week 1) ✅

### Legacy Endpoints Identified

#### Critical Path (User-Facing)
| File | Function | Users/Day | Priority | Replacement |
|------|----------|-----------|----------|-------------|
| `index.php` | Landing page | HIGH | P1 | Next.js landing page |
| `signin.php` | Authentication | HIGH | P1 | NextAuth.js + Laravel API |
| `signout.php` | Logout | HIGH | P1 | NextAuth.js signout |
| `matches.php` | View matches | HIGH | P1 | Next.js matches page + Laravel API |
| `edit-profile.php` | Edit profile | HIGH | P2 | Next.js profile editor + ProfileController |
| `manage-pics.php` | Photo management | MEDIUM | P2 | Next.js photo manager + Laravel API |
| `settings.php` | User settings | MEDIUM | P3 | Next.js settings page |

#### Backend Logic (Include Files)
| File | Function | Priority | Replacement |
|------|----------|----------|-------------|
| `_getMatches.php` | Matching algorithm | P1 | ✅ COMPLETED - Secure wrapper deployed |
| `_init.php` | Bootstrap | P1 | Laravel bootstrap |
| `_db.php` | Database connection | P1 | Laravel database config |
| `security-manager.php` | Security | P1 | ✅ UPDATED - Now uses env vars |
| `ProfileManager.php` | Profile CRUD | P2 | Laravel ProfileController |
| `PhotoManager.php` | Photo CRUD | P2 | Laravel PhotoController |
| `_matchAction.php` | Match actions | P2 | Laravel MatchController |
| `_emailFunctions.php` | Email sending | P3 | Laravel Mail |

#### Administrative
| File | Function | Priority | Replacement |
|------|----------|----------|-------------|
| `admin-dashboard.php` | Admin panel | P3 | Laravel Nova or custom admin |
| `admin-login.php` | Admin auth | P3 | Laravel admin middleware |

#### Utility Pages
| File | Function | Priority | Replacement |
|------|----------|----------|-------------|
| `contact.php` | Contact form | P4 | Next.js contact page |
| `privacy.php` | Privacy policy | P4 | Next.js static page |
| `tos.php` | Terms of service | P4 | Next.js static page |
| `forgot-password.php` | Password reset | P2 | NextAuth.js + Laravel |
| `verify-email.php` | Email verification | P2 | Laravel email verification |

**Total Legacy Files:** 50+ PHP files  
**High Priority:** 15 files (user-facing critical path)  
**Medium Priority:** 20 files (features)  
**Low Priority:** 15 files (admin, utility)

---

## Phase 2: Freeze Legacy Development (Week 2) ⏳

### Enforcement Rules

**ALLOWED in Legacy Code:**
- ✅ Security fixes (e.g., encryption key migration)
- ✅ Critical bug fixes (data corruption, crashes)
- ✅ Security patches (SQL injection, XSS, CSRF)

**FORBIDDEN in Legacy Code:**
- ❌ New features
- ❌ UI improvements
- ❌ Code refactoring (except security-related)
- ❌ Database schema changes

### Code Freeze Implementation

```php
// Add to top of every legacy file:
<?php
/**
 * ⚠️ DEPRECATED - Legacy PHP Code
 * 
 * Status: FROZEN (bug fixes only)
 * Replacement: [Link to new implementation]
 * Deprecation Date: 2025-10-18
 * Sunset Date: 2026-04-18
 * 
 * DO NOT ADD NEW FEATURES HERE
 * See ADR 001 for migration plan
 */
```

### CI/CD Enforcement
```yaml
# .github/workflows/legacy-freeze-check.yml
name: Enforce Legacy Freeze

on: [pull_request]

jobs:
  check-legacy:
    runs-on: ubuntu-latest
    steps:
      - name: Check for legacy modifications
        run: |
          # Get changed files in root directory
          CHANGED=$(git diff --name-only origin/main... | grep -E '^[^/]+\.php$' || true)
          
          if [ -n "$CHANGED" ]; then
            echo "::error::Legacy PHP files modified. New features must go in fwber-backend/"
            echo "Changed files: $CHANGED"
            echo "If this is a security fix, add label: security-fix"
            exit 1
          fi
```

---

## Phase 3: Feature Migration Timeline (Weeks 3-20)

### Week 3-4: Authentication & User Flow
- [ ] NextAuth.js configuration
- [ ] Laravel Sanctum API tokens
- [ ] Login/logout flow
- [ ] Session management
- **Cutover:** Redirect /signin.php → /auth/signin

### Week 5-6: Profile Management
- [ ] Profile display (Next.js)
- [ ] Profile editing (Next.js + ProfileController)
- [ ] Photo upload (Next.js + PhotoController)
- [ ] Profile completion wizard
- **Cutover:** Redirect /edit-profile.php → /profile/edit

### Week 7-10: Matching System
- [ ] Matches display (Next.js)
- [ ] Match actions (like, pass, super_like)
- [ ] Match algorithm integration (AIMatchingService)
- [ ] Real-time match notifications
- **Cutover:** Redirect /matches.php → /matches

### Week 11-14: Messaging & Communication
- [ ] Message list (Next.js)
- [ ] Chat interface (Next.js)
- [ ] Real-time messaging (WebSockets)
- [ ] Message encryption
- **Cutover:** Enable messaging in new stack

### Week 15-16: User Safety
- [ ] Block user functionality
- [ ] Report user functionality
- [ ] Admin moderation panel
- [ ] Automated abuse detection
- **Cutover:** Enable safety features

### Week 17-20: Remaining Features
- [ ] Settings page
- [ ] Contact form
- [ ] Static pages (privacy, TOS)
- [ ] Admin dashboard
- **Cutover:** Complete migration

---

## Phase 4: Dual-Stack Operation (Weeks 3-20)

### Routing Strategy

**During Migration Period:**
```nginx
# nginx.conf or .htaccess
# Route new features to Next.js, legacy to PHP

# New stack (served by Next.js)
location /auth { proxy_pass http://localhost:3000; }
location /profile { proxy_pass http://localhost:3000; }
location /matches { proxy_pass http://localhost:3000; }
location /api/v1 { proxy_pass http://localhost:8000; } # Laravel

# Legacy stack (PHP)
location ~ \.php$ { 
    # Serve PHP files
    # Log deprecation warnings
}
```

### Session Sharing
```php
// Bridge authentication between stacks
// Laravel creates NextAuth-compatible sessions
// Legacy PHP reads from same session store
```

### Data Consistency
- Single MySQL database (shared)
- Laravel migrations manage schema
- Legacy PHP reads from same tables
- No dual writes (Laravel is source of truth)

---

## Phase 5: Monitoring & Validation (Ongoing)

### Metrics to Track

**Traffic Patterns:**
- Legacy PHP requests/day
- New stack requests/day
- Error rates (legacy vs new)
- Response times (legacy vs new)

**User Experience:**
- User complaints about features
- Bug reports (legacy vs new)
- Feature usage analytics
- A/B test results

**Technical Health:**
- Legacy code coverage (should decrease)
- New stack code coverage (should increase to 80%+)
- Security scan results
- Performance benchmarks

### Migration Milestones

| Week | Milestone | Success Criteria |
|------|-----------|------------------|
| 4 | Auth migrated | 50% of logins via NextAuth |
| 8 | Profile migrated | 50% of profile edits via new stack |
| 12 | Matches migrated | 75% of match views via new stack |
| 16 | Messaging live | 90% of messages via new stack |
| 20 | Full migration | 95% of traffic on new stack |
| 24 | Legacy sunset | 100% on new stack, legacy archived |

---

## Phase 6: Legacy Sunset (Weeks 21-24)

### Week 21: Final Migration Push
- [ ] Force all remaining users to new stack
- [ ] Update all external links
- [ ] Redirect all legacy URLs
- [ ] Monitor for errors

### Week 22: Decommission Legacy
- [ ] Archive legacy code to `/archive/legacy-php/`
- [ ] Remove from production deployment
- [ ] Update documentation
- [ ] Celebrate! 🎉

### Week 23: Cleanup
- [ ] Remove legacy database tables/columns
- [ ] Clean up legacy dependencies (mysqli, old libraries)
- [ ] Remove legacy configuration files
- [ ] Update README and docs

### Week 24: Post-Migration Review
- [ ] Performance analysis (before vs after)
- [ ] Security posture review
- [ ] Developer velocity metrics
- [ ] User satisfaction survey

---

## Risk Mitigation

### Risk 1: Data Loss During Migration
**Mitigation:**
- Database backups before each phase
- Dual-write temporarily if needed
- Rollback procedures documented
- Gradual cutover (not big bang)

### Risk 2: User Disruption
**Mitigation:**
- Feature flags for gradual rollout
- A/B testing for new features
- Maintain legacy as fallback
- Clear communication to users

### Risk 3: Performance Regression
**Mitigation:**
- Performance benchmarks before/after
- Load testing on new stack
- Caching strategy (Redis)
- Database query optimization

### Risk 4: Team Capacity
**Mitigation:**
- Realistic timeline (6 months)
- Focus on critical path first
- Parallel work where possible
- External help if needed

---

## Success Criteria

### Technical Success
- [ ] 100% feature parity
- [ ] Zero security regressions
- [ ] Performance equal or better
- [ ] 80%+ test coverage
- [ ] Zero data loss

### Business Success
- [ ] No user complaints about migration
- [ ] Development velocity increases
- [ ] Bug rate decreases
- [ ] New features ship faster
- [ ] Technical debt reduced by 70%

### Team Success
- [ ] Developer satisfaction improves
- [ ] Onboarding time decreases
- [ ] Code review time decreases
- [ ] Clearer ownership and responsibility

---

## Rollback Plan

If critical issues arise during migration:

**Emergency Rollback (< 5 minutes):**
```bash
# Revert routing to legacy
# Disable new stack
# Monitor error logs
# Investigate issue
```

**Planned Rollback (if phase fails):**
- Maintain legacy code operational for 12 months after "completion"
- Archive but don't delete legacy code
- Keep database schema compatible
- Document lessons learned

---

## Communication Plan

### Weekly Updates
- Migration progress report
- Blocker identification
- Success metrics
- Next week priorities

### Stakeholder Communication
- Executive summary monthly
- User communication 2 weeks before each cutover
- Team standup daily during active migration

---

**Plan Owner:** Development Team  
**Approved By:** Multi-AI Consensus (GPT-5, Gemini 2.5 Pro, Gemini 2.5 Flash, GPT-5-Codex)  
**Next Review:** Weekly during migration, final review at Week 24
