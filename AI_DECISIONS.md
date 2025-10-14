# Architecture Decision Record (ADR)
**Project:** FWBer.me
**Purpose:** Document key technical and strategic decisions for multi-AI continuity

---

## ADR-001: Hybrid Approach - Legacy PHP + Modern Stack in Parallel

**Date:** 2025-10-10
**Status:** ✅ ACCEPTED
**Deciders:** Robert (Human), Claude Code CLI, Gemini, JetBrains AI

### Context
Project has 3 concurrent implementations:
- Legacy PHP (root directory) - 90% complete, production-ready
- Laravel backend (fwber-backend/) - 60% complete
- Next.js frontend (fwber-frontend/) - 60% complete

### Decision
**Option C: Hybrid Approach**
- Ship legacy PHP MVP immediately (4-week target)
- Continue building Laravel + Next.js in parallel (no deadline pressure)
- Migrate users to modern stack once mature (6-12 months)

### Rationale
- **Time to market:** Legacy PHP can launch in 4 weeks vs 3-6 months for modern stack
- **Risk mitigation:** MVP validates business model before investing in full rewrite
- **Revenue-funded:** User revenue can fund modern stack development
- **No technical debt:** Legacy PHP was security-hardened in 2024 (Argon2ID, CSRF, PDO)

### Alternatives Considered
- **Option A:** Complete modern stack first (rejected - too slow)
- **Option B:** Ship only legacy PHP, abandon modern stack (rejected - bad long-term)

### Consequences
- ✅ Faster market validation
- ✅ Less financial risk
- ⚠️ Maintain two codebases temporarily
- ⚠️ Eventual migration complexity

---

## ADR-002: Profile Form - 150+ Field Implementation

**Date:** 2025-10-11
**Status:** ✅ IMPLEMENTED
**Deciders:** Claude Code CLI (Sonnet 4.5)

### Context
Profile form was 85% complete (~80 fields). PROFILE_FIELD_MAPPING.md spec requires 150+ fields for sophisticated matching algorithm.

### Decision
Complete profile form to 100% specification with:
- Gender-specific fields (penisSize, bodyHair, breastSize)
- All preference checkboxes (tattoos, looks, intelligence, bedroom personality, grooming)
- Dynamic JavaScript for gender-aware field visibility
- Backend validation in edit-profile.php

### Rationale
- **Matching quality:** 150+ fields enable accurate compatibility scoring
- **User experience:** Gender-specific fields reduce form clutter
- **Security:** Backend validation prevents data leakage
- **Spec compliance:** Matching algorithm expects these fields

### Alternatives Considered
- **Simplified form (50 fields):** Rejected - would break matching algorithm
- **Progressive disclosure:** Considered but deferred to post-launch

### Consequences
- ✅ Full matching algorithm capability
- ✅ Better UX with dynamic sections
- ⚠️ Long form (may intimidate some users)
- ⚠️ Requires thorough testing

### Implementation Notes
- See `MULTI_AI_SESSION_REPORT.md` for bug fixes
- QA AI consultation caught 3 critical bugs
- Backend validation added for security

---

## ADR-003: Multi-AI Collaboration via Log Files

**Date:** 2025-10-10
**Status:** ✅ ACTIVE
**Deciders:** Robert, All AI contributors

### Context
Robert uses 6+ AI tools (Claude, Gemini, Cursor, Cline, JetBrains AI, Copilot). Need coordination mechanism.

### Decision
Use structured markdown files for AI coordination:
- `INITIAL_AI_INTERCOMMUNICATION_LOG.md` - Conversation history
- `AI_TASKS.md` - Kanban board
- `AI_DECISIONS.md` - This file (ADR format)
- `PROJECT_STATE.md` - Single source of truth

### Rationale
- **Tool-agnostic:** Works across CLI, IDE plugins, web interfaces
- **Version controlled:** Git tracks all AI decisions
- **Auditable:** Human can review AI collaboration
- **Asynchronous:** AIs can "talk" without being online simultaneously

### Alternatives Considered
- **MCP servers:** Requires custom development (deferred)
- **Shared database:** Too complex for MVP
- **Slack/Discord:** Not integrated with AI tools

### Consequences
- ✅ Successful multi-AI coordination (proven in session 2025-10-11)
- ✅ Clear audit trail
- ⚠️ Requires AI discipline (update logs consistently)
- ⚠️ Long log files (1850+ lines) - mitigated by summary files

---

## ADR-004: Backend Validation for Gender-Specific Fields

**Date:** 2025-10-11
**Status:** ✅ IMPLEMENTED
**Deciders:** Claude Code CLI (after QA AI consultation)

### Context
Frontend hides gender-specific fields via JavaScript, but backend didn't validate. Security vulnerability: user could inject inappropriate data.

### Decision
Add server-side validation in edit-profile.php:
- Male/non-binary users: allow penisSize, bodyHair
- Female users: allow breastSize only
- Explicitly unset() inappropriate fields

### Rationale
- **Security:** Never trust client-side validation
- **Data integrity:** Prevent garbage data in database
- **Matching accuracy:** Gender-specific fields must be accurate for algorithm

### Alternatives Considered
- **Client-side only:** Rejected - security vulnerability
- **Database constraints:** Rejected - key/value table structure doesn't support it

### Consequences
- ✅ Security vulnerability closed
- ✅ Data integrity improved
- ⚠️ Added 20 lines of validation code

### Bug Report
- Found by: QA/Testing AI (via Task agent)
- Fixed by: Claude Code CLI
- See: `MULTI_AI_SESSION_REPORT.md` for details

---

## ADR-005: Venue Pilot Strategy - Hyper-Local Approach

**Date:** 2025-10-10 (documented 2025-10-11)
**Status:** 📋 PLANNED
**Deciders:** Gemini (Business AI), Robert

### Context
B2B/B2C marketplace has chicken-and-egg problem: venues need users, users need venues.

### Decision
Hyper-Local Pilot Strategy:
1. Partner with 1 venue initially (gay bar, sex club, or kink event)
2. Recruit 10-20 beta users from that venue's existing community
3. Provide free venue subscription during pilot
4. Expand to adjacent venues only after pilot success

### Rationale
- **Controlled test:** Small user base = easier bug management
- **Built-in community:** Venue's patrons already know each other
- **Marketing channel:** Venue promotes app to customers
- **Revenue validation:** Prove venue subscription model works

### Alternatives Considered
- **Pure B2C launch:** Rejected - no venue partnerships
- **Multiple venues at once:** Rejected - too complex for beta

### Consequences
- ✅ Solves chicken-and-egg problem
- ✅ Built-in user acquisition channel
- ⚠️ Geographic limitation (single city initially)
- ⚠️ Dependent on venue partner quality

### Next Steps
- Create venue pitch materials (see AI_TASKS.md)
- Identify 5-10 target venues
- Schedule informational calls

---

## ADR-006: Launch Deadline - 4 Weeks from 2025-10-11

**Date:** 2025-10-11
**Status:** 🎯 ACTIVE GOAL
**Deciders:** Gemini (Business AI), Claude Code CLI

### Context
Project risk: "One more feature" syndrome leading to perpetual delays. Legacy PHP app is 90%+ complete.

### Decision
Hard launch deadline: **November 8, 2025** (4 weeks from today)
- Beta launch with 1 venue + 10-20 users
- Accept "good enough" quality bar (not perfection)
- Rapid iteration post-launch (weekly bug fixes)

### Rationale
- **Market timing:** Post-COVID hookup culture momentum
- **Business validation:** Need revenue to justify continued development
- **Developer morale:** Shipping motivates better than endless coding
- **Competitive pressure:** Grindr, Feeld, #Open are iterating fast

### Alternatives Considered
- **6-month timeline:** Rejected - too slow, risk of burnout
- **2-week timeline:** Rejected - not enough testing time
- **No deadline:** Rejected - enables scope creep

### Consequences
- ✅ Forces prioritization (critical bugs only)
- ✅ Prevents over-engineering
- ⚠️ May ship with minor bugs (acceptable if labeled BETA)
- ⚠️ Requires discipline to avoid scope creep

### Success Criteria
See `AI_TASKS.md` for launch readiness checklist (to be created)

---

## ADR-007: Testing Strategy - Manual First, Automate Later

**Date:** 2025-10-11
**Status:** ✅ ACCEPTED
**Deciders:** QA/Testing AI, Claude Code CLI

### Context
Profile form has 150+ fields with complex JavaScript interactions. No automated test coverage exists.

### Decision
Phase 1 (This Week): Manual testing
- E2E user flow tests
- Cross-browser testing
- Mobile responsiveness

Phase 2 (Next Sprint): Automated testing
- PHPUnit tests for ProfileManager
- Integration tests for matching algorithm
- Playwright/Selenium for UI tests

### Rationale
- **Speed:** Manual testing reveals obvious bugs faster
- **Complexity:** 150+ fields = hard to mock/fixture
- **ROI:** Automate after stabilization, not during discovery

### Alternatives Considered
- **TDD approach:** Rejected - would slow down by weeks
- **Skip testing:** Rejected - too risky for production

### Consequences
- ✅ Faster bug discovery
- ✅ Lower upfront time investment
- ⚠️ Manual test repetition (mitigated by checklist)
- ⚠️ Deferred automation (acceptable trade-off)

---

## ADR Template (For Future Decisions)

```markdown
## ADR-XXX: [Decision Title]

**Date:** YYYY-MM-DD
**Status:** 📋 PROPOSED | ✅ ACCEPTED | ❌ REJECTED | ⚠️ SUPERSEDED
**Deciders:** [AI Name(s), Human Name]

### Context
[Describe the problem or situation requiring a decision]

### Decision
[Describe the decision made]

### Rationale
[Explain why this decision was made]

### Alternatives Considered
[List other options and why they were rejected]

### Consequences
[Positive and negative outcomes]

### Implementation Notes (optional)
[Technical details, file changes, etc.]
```

---

## Decision Status Legend

- 📋 **PROPOSED:** Under discussion
- ✅ **ACCEPTED:** Decision made and implemented
- ⚠️ **SUPERSEDED:** Replaced by newer decision (see ADR-XXX)
- ❌ **REJECTED:** Considered but not chosen

---

**Last Updated:** 2025-10-11
**Next Review:** After major milestone (beta launch, migration to modern stack, etc.)
