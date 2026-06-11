# MEMORY.md — fwber Internal Architectural Observations

## Current Version: 2.1.9 "Intelligent Match Refinement"
**Last Updated:** 2026-06-08

### Architectural Shift: Narrative Compatibility (v2.1.9)
The matching engine now produces "Narrative Reports" via `NarrativeService.ts`. This moves the platform away from raw percentages toward a more immersive "Cyber-Noir" discovery experience.
- **Signal Unification**: Compatibility scores are no longer just about question answers. They now incorporate real-time proximity (80/20 split) via `MatchingHeuristicService.ts`.

### Project Memory: Matching Engine (v2.1.5 - v2.1.8)
- The project implements an OkCupid-style matching engine using a geometric-mean compatibility heuristic: `Score = (Satisfaction A * Satisfaction B) ^ (1/2)`.
- It includes importance weighting: Irrelevant (0), A Little Important (1), Somewhat Important (10), Very Important (50), and Mandatory (250).
- The matching question frontend interface is at `/settings/matching`.
- As of v2.1.8, the dataset contains 95 broad personality questions. (v2.1.9: 108 questions including Cyber-Noir ethics).

### Federation Protocol (v2.0.11+)
- ActivityPub implementation with WebFinger support.
- Routes: `/api/federation/actors/:id`, `/api/federation/users/:id/inbox`, `/api/federation/users/:userId/outbox`.
- (Fixed v2.1.9): The actor detail route now handles non-numeric IDs gracefully.

### UI Standards
- High-fidelity "Cyber-Noir" aesthetic.
- Every backend feature must be comprehensively wired to the frontend with detailed tooltips and descriptions.

[... Historical observations ...]
