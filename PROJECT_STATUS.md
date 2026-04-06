# PROJECT_STATUS.md - fwber v1.7.4 (Rewind Avatar Prompt Relation Refresh Fix)

**Date:** 2026-04-06
**Version:** 1.7.4 "Rewind Avatar Prompt Relation Refresh Fix"
**Status:** ✅ **THE NEXT EXPLICIT REWIND BACKEND CI SEAM HAS A SOURCE-LEVEL FIX; FRONTEND RESTORATION SURFACES REMAIN INTACT**

---

## 🎯 What This Release Delivered
This release stayed focused on the restore branch’s Hetzner-safe promotion path by patching the next concrete backend CI seam after the recent surface recoveries.

Delivered:
- avatar prompt generation now resolves the latest stored profile row directly through the relation query instead of trusting a possibly stale cached relation instance
- this targets the still-failing rewind avatar-generation test that expected rich persisted profile attributes to appear in the outbound prompt
- the recently restored frontend surfaces for boosts, gifts, referrals, video, and unlocks remain untouched and build-safe

## ✅ Why This Matters
To restore everything removed while keeping the branch deployable to Hetzner, the rewind line has to keep converting explicit backend CI failures into direct fixes. This patch continues that pattern instead of letting known red seams linger.
