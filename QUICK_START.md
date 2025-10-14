# 🚀 FWBer Testing - QUICK START

**Status:** ✅ PHP 8.3 & MySQL 8.4 installed!
**Next:** 3 quick steps to start testing

---

## ⚡ 3 Steps to Start Testing

### Step 1: Restart Your Terminal (IMPORTANT!)
```powershell
# Close this terminal window and open a new PowerShell window
# This allows PHP to be recognized in your PATH
```

### Step 2: Run the Quick Start Script
```powershell
cd C:\Users\mrgen\fwber
.\START_TESTING.ps1
```

**Or manually:**
```powershell
# Check PHP works
php --version
# Should show: PHP 8.3.26

# Start server
php -S localhost:8000

# Open browser to: http://localhost:8000
```

### Step 3: Follow the Testing Guide
```
📄 Open: MANUAL_TESTING_RUNBOOK.md
✅ Follow steps 1-10
⏱️ Time: 4-5 hours
```

---

## 🗄️ Database Setup (Required)

### Option A: Quick Setup (If you have MySQL command)
```bash
# Create database
mysql -u root -p
CREATE DATABASE fwber CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fwber;
SOURCE setup-database.sql;
exit;
```

### Option B: MySQL Workbench (GUI)
1. Open MySQL Workbench
2. Connect to local instance
3. Create new schema: `fwber`
4. Run SQL file: `setup-database.sql`

### Option C: Use Existing Database
Edit `_db.php` with your database credentials

---

## 🧪 Generate Test Users

Once database is set up:
```bash
php db/generate-test-users.php
```

**Test Credentials:**
- john.test@example.com / TestPass123!
- jane.test@example.com / TestPass123!
- alex.test@example.com / TestPass123!

---

## 🌐 Access the Application

**URL:** http://localhost:8000

**Test Flow:**
1. Sign in with test credentials
2. Navigate to profile form
3. Fill out all fields (follow MANUAL_TESTING_RUNBOOK.md)
4. Submit and verify data persists
5. Check database: `php db/verify-test-data.php`

---

## 📋 Testing Checklist

### Phase 1: John (Male) - 60-90 min
- [ ] Sign in as john.test@example.com
- [ ] Verify male-specific fields visible (penis size, body hair)
- [ ] Verify female-specific fields hidden (breast size)
- [ ] Fill all 150+ fields
- [ ] Submit form
- [ ] Reload and verify data persists
- [ ] Run: `php db/verify-test-data.php`
- [ ] Document results in test-results/2025-10-11-john-test.md

### Phase 2: Jane (Female) - 60-90 min
- [ ] Sign in as jane.test@example.com
- [ ] Verify female-specific fields visible (breast size)
- [ ] Verify male-specific fields hidden (penis size, body hair)
- [ ] Fill all fields
- [ ] Submit and verify
- [ ] Document results

### Phase 3: Alex (Non-Binary) - 60-90 min
- [ ] Sign in as alex.test@example.com
- [ ] Verify ALL gender fields visible
- [ ] Test flexible attribute combinations
- [ ] Submit and verify
- [ ] Document results

### Phase 4: Security Tests - 30 min
- [ ] Data leakage prevention test
- [ ] CSRF token validation
- [ ] Form validation tests

### Phase 5: Final Verification - 30 min
- [ ] Run: `php db/verify-test-data.php`
- [ ] Check all tests passed
- [ ] Create summary report

---

## ✅ Success Criteria

**All must pass:**
- ✅ All 3 personas complete profile
- ✅ Data persists correctly
- ✅ Gender fields show/hide correctly
- ✅ No data leakage
- ✅ CSRF working
- ✅ No console errors

**If all pass:** 🎉 Launch ready!

---

## 🆘 Troubleshooting

### PHP not recognized
```powershell
# Restart PowerShell terminal
# Or add to PATH manually
```

### MySQL not working
```powershell
# Check if MySQL service is running
Get-Service -Name MySQL* | Start-Service
```

### Database connection failed
```
# Edit _db.php with correct credentials
# Default: user=root, password=(set during MySQL install)
```

### Port 8000 already in use
```bash
# Use different port
php -S localhost:8080

# Access at: http://localhost:8080
```

---

## 📚 Full Documentation

| File | Purpose |
|------|---------|
| **MANUAL_TESTING_RUNBOOK.md** | Complete testing guide (START HERE!) |
| **HANDOFF_README.md** | Project overview |
| **db/test-personas.md** | Detailed test checklists |
| **test-results/TEMPLATE-test-results.md** | Result template |

---

## 🎯 After Testing

### If All Tests Pass ✅
1. Update PROJECT_STATE.md
2. Proceed to security audit
3. Beta user recruitment
4. Launch! 🚀

### If Issues Found ❌
1. Document in test-results/
2. Fix critical bugs
3. Retest
4. Then proceed to audit

---

## 🤖 Need Help?

### Quick Question:
```bash
gemini -p "your question about testing"
```

### Deep Analysis:
```bash
codex exec "help me with this testing issue: [describe issue]"
```

---

## 🎉 You're Ready!

**Installed:**
- ✅ PHP 8.3.26
- ✅ MySQL 8.4.6

**Next:**
1. ↻ Restart terminal
2. ▶️ Run START_TESTING.ps1
3. 📖 Follow MANUAL_TESTING_RUNBOOK.md

**Time to completion:** 4-5 hours of testing
**Then:** Security audit → Launch! 🚀

---

**Last Updated:** 2025-10-11
**Status:** Ready to test!
**Let's GOOOOO!** 🚀
