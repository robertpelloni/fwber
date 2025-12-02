# FWBer Manual Testing Runbook

**Purpose:** Guide for manual E2E testing when PHP environment is available
**Prerequisites:** PHP 8.x + MySQL 8.x + Web server (Apache/Nginx/PHP built-in)
**Time Required:** 4-5 hours

---

## ⚠️ Important: PHP Environment Required

This project is a **PHP web application** and requires:
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache with mod_rewrite, Nginx, or PHP built-in server)

**If you don't have PHP installed locally, you have these options:**

### Option 1: Install PHP Locally (Recommended for Testing)
**Windows:**
```powershell
# Install PHP via Chocolatey
choco install php

# Or download from php.net
# https://windows.php.net/download/
```

**Verify Installation:**
```bash
php --version
# Should show PHP 8.x
```

### Option 2: Use Docker (Alternative)
```bash
# Run PHP + MySQL in containers
docker-compose up

# Access at http://localhost:8080
```

### Option 3: Deploy to Staging Server
- Upload to VPS with PHP/MySQL
- Test via remote URL
- SSH access for running CLI scripts

### Option 4: Use Local Development Stack
- **XAMPP:** https://www.apachefriends.org/ (Windows/Mac/Linux)
- **MAMP:** https://www.mamp.info/ (Mac/Windows)
- **Laragon:** https://laragon.org/ (Windows)

---

## Quick Start: Using PHP Built-in Server

If you have PHP installed but no Apache/Nginx:

```bash
# 1. Navigate to project
cd C:\Users\hyper\fwber

# 2. Start PHP built-in server
php -S localhost:8000

# 3. Open browser
# http://localhost:8000

# 4. Test the application
```

**Note:** PHP built-in server is fine for testing, not for production.

---

## Environment Setup Checklist

### 1. Verify Prerequisites ✅
```bash
# Check PHP version (need 8.0+)
php --version

# Check MySQL is running
mysql --version

# Check composer (if using Laravel backend)
composer --version
```

### 2. Database Setup ✅
```bash
# Create database
mysql -u root -p
CREATE DATABASE fwber CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Import schema
mysql -u root -p fwber < setup-database.sql

# Import venue tables (if testing venue features)
mysql -u root -p fwber < setup-venue-tables.sql

# Import admin tables (if testing admin features)
mysql -u root -p fwber < setup-admin-tables.sql
```

### 3. Configure Environment ✅
```bash
# Copy environment template
cp env-template.txt .env

# Edit .env with your settings
# Set database credentials
# Set API keys (for avatar generation)
```

**Required Configuration:**
- `DB_HOST` - Database host (usually `localhost`)
- `DB_NAME` - Database name (`fwber`)
- `DB_USER` - Database username
- `DB_PASS` - Database password

### 4. Start Web Server ✅

**Option A: PHP Built-in Server (Easiest)**
```bash
php -S localhost:8000
```

**Option B: Apache/Nginx**
- Configure virtual host pointing to project directory
- Ensure `.htaccess` file is working (mod_rewrite enabled)
- Restart server

**Option C: XAMPP/MAMP**
- Place project in `htdocs/` or `www/` directory
- Access via `http://localhost/fwber/`

### 5. Verify Installation ✅
Open browser to: `http://localhost:8000` (or your configured URL)

**Expected:** Landing page loads without errors

**Check:**
- [ ] Page loads (no 500 errors)
- [ ] CSS loads correctly
- [ ] No PHP errors displayed
- [ ] Database connection working

---

## Manual Testing: Step-by-Step

### Phase 1: Create Test Users (10 minutes)

#### Generate Test Users via CLI:
```bash
php db/generate-test-users.php
```

**Expected Output:**
```
FWBer Test User Generator
=========================
Test Password: TestPass123!
✅ Created user ID: 1 (JohnTest)
✅ Created user ID: 2 (JaneTest)
✅ Created user ID: 3 (AlexTest)
```

**Test Credentials:**
- **John (male):** john.test@example.com / TestPass123!
- **Jane (female):** jane.test@example.com / TestPass123!
- **Alex (non-binary):** alex.test@example.com / TestPass123!

---

### Phase 2: Test John Persona (Male User) - 60-90 min

#### 2.1 Sign In
1. Navigate to: `http://localhost:8000/signin.php`
2. Enter credentials:
   - Email: `john.test@example.com`
   - Password: `TestPass123!`
3. Click "Sign In"

**✅ Expected:** Redirected to profile or matches page

#### 2.2 Navigate to Profile Form
- Go to: `http://localhost:8000/profile-form.php`
  OR `http://localhost:8000/edit-profile.php`

#### 2.3 Verify Male-Specific Fields
**Open test checklist:** `db/test-personas.md` → Persona 1: John Doe

**Check visibility:**
- [ ] ✅ Penis size field VISIBLE
- [ ] ✅ Body hair field VISIBLE
- [ ] ✅ Breast size field NOT VISIBLE
- [ ] ✅ Pubic hair field VISIBLE (gender-neutral)

**Screenshot:** Take screenshot of form showing male-specific fields

#### 2.4 Fill Out Profile Form
Follow the detailed checklist in `db/test-personas.md`

**Key fields to test:**
- Basic info (age, location, height, body type)
- Physical attributes (hair, eyes, ethnicity)
- Male-specific (pubic hair: "Trimmed", penis size: "Average", body hair: "Some")
- Preferences (check 10-15 random checkboxes)

**Time estimate:** 30-40 minutes to fill all fields

#### 2.5 Submit Form
- Click "Save Profile" or "Submit" button
- **Expected:** Success message, redirected to profile page

#### 2.6 Verify Data Persistence
- Reload the profile form page
- **Check:** All data is still there
- **Screenshot:** Take screenshot showing data persisted

#### 2.7 Database Verification
```bash
php db/verify-test-data.php
```

**Expected Output:**
```
Testing: john.test@example.com
✅ User found (ID: 1)
✅ Penis Size: Average
✅ Body Hair: Some
✅ Breast Size: NULL (correct for male)
✅ Preferences saved: 15
```

#### 2.8 Document Results
Create file: `test-results/2025-10-11-john-test.md`

Use template: `test-results/TEMPLATE-test-results.md`

**Include:**
- ✅ All checks that passed
- ❌ Any failures or issues
- 📸 Screenshots
- 📝 Notes about user experience

---

### Phase 3: Test Jane Persona (Female User) - 60-90 min

#### 3.1 Sign Out & Sign In as Jane
1. Sign out John
2. Navigate to: `http://localhost:8000/signin.php`
3. Sign in with:
   - Email: `jane.test@example.com`
   - Password: `TestPass123!`

#### 3.2 Navigate to Profile Form
- Go to: `http://localhost:8000/profile-form.php`

#### 3.3 Verify Female-Specific Fields
**Open:** `db/test-personas.md` → Persona 2: Jane Smith

**Check visibility:**
- [ ] ✅ Breast size field VISIBLE
- [ ] ✅ Penis size field NOT VISIBLE
- [ ] ✅ Body hair field NOT VISIBLE
- [ ] ✅ Pubic hair field VISIBLE

#### 3.4 Fill Out Profile Form
Follow checklist in `db/test-personas.md`

**Key fields:**
- Basic info
- Physical attributes
- Female-specific (pubic hair: "Shaved", breast size: "C-cup")
- Preferences (10-15 checkboxes)

#### 3.5 Submit & Verify
- Submit form
- Reload page to verify persistence
- Run: `php db/verify-test-data.php`
- Document results in `test-results/2025-10-11-jane-test.md`

**Expected:**
```
Testing: jane.test@example.com
✅ Breast Size: C-cup
✅ Penis Size: NULL (correct for female)
✅ Body Hair: NULL (correct for female)
```

---

### Phase 4: Test Alex Persona (Non-Binary User) - 60-90 min

#### 4.1 Sign In as Alex
- Email: `alex.test@example.com`
- Password: `TestPass123!`

#### 4.2 Verify Non-Binary Field Visibility
**Open:** `db/test-personas.md` → Persona 3: Alex Taylor

**Check visibility:**
- [ ] ✅ Penis size field VISIBLE
- [ ] ✅ Body hair field VISIBLE
- [ ] ✅ Breast size field VISIBLE
- [ ] ✅ ALL gender-specific fields visible

**Key Test:** Non-binary users can select ANY combination of attributes

#### 4.3 Fill Out Profile
- Can fill penis size OR breast size OR both OR neither
- Backend should accept any valid combination

#### 4.4 Submit & Verify
- Submit form
- Run: `php db/verify-test-data.php`
- Document results in `test-results/2025-10-11-alex-test.md`

**Expected:**
```
Testing: alex.test@example.com
✅ Non-binary user can have any combination
✅ Penis Size: [value or NOT SET]
✅ Body Hair: Minimal
✅ Breast Size: [value or NOT SET]
```

---

### Phase 5: Cross-Persona Security Tests - 30 min

#### 5.1 Data Leakage Prevention Test

**Test with John (male user signed in):**

1. Open browser developer console (F12)
2. Go to profile form
3. Try to inject female-specific field:
   ```javascript
   // In browser console, try to add hidden breastSize field
   let input = document.createElement('input');
   input.type = 'hidden';
   input.name = 'breastSize';
   input.value = 'D-cup';
   document.querySelector('form').appendChild(input);

   // Then submit form
   document.querySelector('form').submit();
   ```

4. After submission, check database:
   ```bash
   php db/verify-test-data.php
   ```

**Expected:** ✅ breastSize should be NULL (backend rejected it)

**Backend validation location:** `edit-profile.php` lines 48-62

#### 5.2 CSRF Token Test
1. View page source of profile form
2. Find CSRF token input field
3. Copy token value
4. Try to submit form with invalid/old token
5. **Expected:** Form rejected with security error

#### 5.3 Form Validation Tests
Test with any user:

**Age Validation:**
- [ ] Enter age < 18 → Rejected
- [ ] Enter age > 99 → [Check behavior]
- [ ] Enter non-numeric age → Error shown

**Required Field Validation:**
- [ ] Leave required fields empty → Error messages shown
- [ ] Fill required fields → Form submits

**Email Validation:**
- [ ] Invalid email format → Error
- [ ] Valid email format → Accepted

---

### Phase 6: Matching Algorithm Integration - 30 min

#### 6.1 View Matches
1. Sign in as John
2. Navigate to: `http://localhost:8000/matches.php`
3. **Check:**
   - [ ] Page loads without errors
   - [ ] Matches are displayed (if any)
   - [ ] User preferences are respected

#### 6.2 Verify Preference Integration
```bash
# Check that matching engine can read new preference fields
php -r "require '_getMatches.php'; echo 'Matching algorithm loaded';"
```

---

### Phase 7: Venue Check-In Smoke Test - 30 min

#### 7.1 Navigate to Venue Features
- URL: `http://localhost:8000/api/venue-checkin.php`
  OR look for "Check In" link in navigation

#### 7.2 Basic Functionality Check
- [ ] Page loads without errors
- [ ] Can select a venue (if any venues exist)
- [ ] Check-in button works
- [ ] Check-in is recorded in database

**Note:** This is a smoke test, not comprehensive venue testing

---

### Phase 8: Mobile Responsiveness - 15 min

#### 8.1 Test on Mobile Device or Responsive Mode
1. Open browser DevTools (F12)
2. Toggle device toolbar (Ctrl+Shift+M)
3. Select mobile device (iPhone 12 Pro, etc.)
4. Navigate through profile form

**Check:**
- [ ] Form is usable on small screen
- [ ] Buttons are tappable
- [ ] Text is readable without zooming
- [ ] Dropdowns work on mobile
- [ ] Checkboxes are large enough

---

## Collecting Results

### Run Final Verification
```bash
php db/verify-test-data.php
```

**Expected:** ✅ ALL TESTS PASSED

### Review Test Result Files
- `test-results/2025-10-11-john-test.md` ✅
- `test-results/2025-10-11-jane-test.md` ✅
- `test-results/2025-10-11-alex-test.md` ✅
- `test-results/errors.log` (if any)
- `test-results/screenshots/` (any issues)

### Create Summary Report
Create: `test-results/2025-10-11-SUMMARY.md`

**Include:**
- Overall status (PASS/FAIL/PARTIAL)
- Critical issues found
- Recommendations
- Next steps

---

## Success Criteria

### All Must Pass:
- ✅ All 3 personas complete profile successfully
- ✅ Data persists correctly in database
- ✅ Gender-specific fields show/hide correctly
- ✅ No data leakage between genders
- ✅ CSRF protection working
- ✅ Form validation catches errors
- ✅ No console errors
- ✅ Basic mobile responsiveness

**If all pass:** 🎉 Profile system is launch-ready!

---

## Troubleshooting

### PHP Not Found
**Solution:** Install PHP 8.x from https://php.net or use XAMPP/MAMP/Laragon

### Database Connection Failed
**Check:** `_db.php` or `.env` file has correct credentials

### Page Shows Blank/White Screen
**Check:** PHP error log or enable error display:
```php
// Add to top of file temporarily
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### CSRF Token Errors
**Check:** `SecurityManager.php` is loaded correctly

### Fields Not Saving
**Check:** `edit-profile.php` and `ProfileManager.php` are working

---

## Next Steps After Testing

### If All Tests Pass ✅
1. Update `PROJECT_STATE.md` → E2E Testing: COMPLETE
2. Proceed to **Security Audit** (see AI_TASKS.md)
3. Begin beta user recruitment
4. Start venue partnership outreach

### If Tests Fail ❌
1. Document all issues in test results
2. Create tickets for critical bugs
3. Fix issues
4. Retest
5. Verify fixes with `php db/verify-test-data.php`

---

## Cleanup (Optional)

### Remove Test Users
```bash
php db/cleanup-test-users.php
# Type 'DELETE' to confirm
```

### Create Database Snapshot
```bash
mysqldump -u root -p fwber > test-results/database-snapshots/after-test.sql
```

---

## Testing Tools Available

### CLI Testing Tools
```bash
# Quick validation
gemini -p "Review this test approach"

# Deep analysis
codex exec "Analyze test results and suggest improvements"

# Multi-model perspective
copilot --model claude-sonnet-4.5 "Review test findings"
```

### MCP Tools (via JetBrains/IDE)
- File operations
- Code inspection
- Symbol search

---

## Time Budget

| Phase | Time |
|-------|------|
| Environment setup | 10-30 min |
| John persona | 60-90 min |
| Jane persona | 60-90 min |
| Alex persona | 60-90 min |
| Security tests | 30 min |
| Matching/venue | 60 min |
| Mobile check | 15 min |
| Results documentation | 30 min |
| **TOTAL** | **4.5-6 hours** |

---

**Last Updated:** 2025-10-11
**Created By:** Claude Code CLI (Sonnet 4.5)
**Based On:** Codex recommendation for manual testing without local PHP
