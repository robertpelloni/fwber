# Project Status — fwber v1.0.49 (Local Pulse Scene Signals)

**Date:** 2026-04-02  
**Version:** 1.0.49 "Local Pulse Scene Signals"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Local Pulse Scene Signals
- **Scene-Aware Local Feed Shipped**: Local Pulse artifacts and sponsored promotions now carry `scene_signals` based on followed topics and structured scene terms.
- **Scene Cues on Pulse Cards**: The Local Pulse feed now renders scene-aligned headlines, matched topic chips, and matched tags directly on artifact cards.
- **Shared Graph Reuse Preserved**: Local Pulse enrichment reuses the same topic graph and scene-term logic already powering matches, profiles, and recommendations.
- **Dashboard Shell Overlap Fixed**: The floating global nav now stays out of dashboard/headered pages until header detection completes, eliminating the duplicate-logo overlap.

## ✅ Release Focus
- [x] Added Local Pulse scene enrichment on the backend using followed topics and structured scene terms.
- [x] Added Local Pulse card UI for scene-aligned headlines, topic chips, and scene tags.
- [x] Added focused backend regression coverage and fixed the stale Local Pulse `birthdate` query path.
- [x] Fixed the dashboard floating-logo overlap in the shared shell and validated the affected frontend surfaces.
