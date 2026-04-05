# PROJECT_STATUS.md - fwber v1.7.1 (Rewind CI Repair for Avatar Requests + Recommendation Caching)

**Date:** 2026-04-05
**Version:** 1.7.1 "Rewind CI Repair for Avatar Requests + Recommendation Caching"
**Status:** ✅ **RESTORE BRANCH FRONTEND IS GREEN AND THE NEXT BACKEND CI CONTRACT FAILURES ARE NOW PATCHED AT THE SOURCE**

---

## 🎯 What This Release Delivered
This release followed the rewind navigation recovery with another compatibility tranche aimed directly at the two concrete backend CI failures still blocking the richer branch from looking like a viable Hetzner-ready promotion candidate.

Delivered:
- avatar generation in testing now uses deterministic placeholder provider credentials so the rewind suite can actually observe outbound image-generation HTTP fakes instead of silently short-circuiting before requests are recorded
- `RecommendationController` now uses tagged caching for personalized recommendation responses, matching the restore-branch caching contract expected by the controller-caching suite
- these repairs stay branch-local and deployment-safe, so the rewind branch keeps moving toward a full restored feature surface without regressing modern Hetzner/runtime assumptions

## ✅ Why This Matters
To restore everything that was removed while still deploying successfully to Hetzner, the rewind branch has to keep converging on green CI. This release patches the next two explicit backend contract mismatches instead of waiting for another opaque red run to say “something failed somewhere.”
