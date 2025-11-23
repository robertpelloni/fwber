# E2E Test Personas for Profile Form Testing

## Overview
Three test personas to validate gender-specific field visibility, data persistence, and matching algorithm integration.

**Test Password (all users):** `TestPass123!`

---

## Persona 1: John Doe (Male User)

### Credentials
- **Email:** john.test@example.com
- **Username:** JohnTest
- **Password:** TestPass123!
- **Age:** 28
- **Gender:** Male

### Profile Form Test Checklist

#### Visibility Tests
- [ ] Male-specific fields visible:
  - [ ] Penis size field visible
  - [ ] Body hair field visible
- [ ] Female-specific fields hidden:
  - [ ] Breast size field NOT visible
- [ ] Gender-neutral fields visible:
  - [ ] Pubic hair field visible
  - [ ] All preference checkboxes visible

#### Data Entry Tests
Fill in ALL fields:
- [ ] **Basic Info:** Age, location, height, body type, ethnicity
- [ ] **Hair/Eyes:** Hair color, hair style, eye color
- [ ] **Physical Attributes:**
  - [ ] Pubic hair: "Trimmed"
  - [ ] Penis size: "Average"
  - [ ] Body hair: "Some"
- [ ] **Preferences:** Check 10-15 random preference boxes
  - [ ] Tattoo preferences (b_wantTattoos*)
  - [ ] Looks preferences (b_wantLooks*)
  - [ ] Intelligence preferences (b_wantIntelligence*)
  - [ ] Bedroom personality (b_wantBedroom*)
  - [ ] Penis size preferences (b_wantPenisSize*)
  - [ ] Body hair preferences (b_wantBodyHair*)

#### Validation Tests
- [ ] Submit with missing required fields → See error messages
- [ ] Submit with age < 18 → Rejected
- [ ] Submit with invalid email format → Rejected
- [ ] CSRF token validation works

#### Persistence Tests
- [ ] Save profile successfully
- [ ] Reload profile page → All data displays correctly
- [ ] Check database:
  ```sql
  SELECT * FROM users WHERE email = 'john.test@example.com';
  SELECT * FROM user_preferences WHERE user_id = [john's ID];
  ```
- [ ] Verify penisSize, bodyHair saved correctly
- [ ] Verify breastSize is NULL (not saved for male users)

---

## Persona 2: Jane Smith (Female User)

### Credentials
- **Email:** jane.test@example.com
- **Username:** JaneTest
- **Password:** TestPass123!
- **Age:** 25
- **Gender:** Female

### Profile Form Test Checklist

#### Visibility Tests
- [ ] Female-specific fields visible:
  - [ ] Breast size field visible
- [ ] Male-specific fields hidden:
  - [ ] Penis size field NOT visible
  - [ ] Body hair field NOT visible
- [ ] Gender-neutral fields visible:
  - [ ] Pubic hair field visible
  - [ ] All preference checkboxes visible

#### Data Entry Tests
Fill in ALL fields:
- [ ] **Basic Info:** Age, location, height, body type, ethnicity
- [ ] **Hair/Eyes:** Hair color, hair style, eye color
- [ ] **Physical Attributes:**
  - [ ] Pubic hair: "Shaved"
  - [ ] Breast size: "C-cup"
- [ ] **Preferences:** Check 10-15 random preference boxes
  - [ ] Tattoo preferences
  - [ ] Looks preferences
  - [ ] Intelligence preferences
  - [ ] Bedroom personality
  - [ ] Breast size preferences (b_wantBreastSize*)

#### Validation Tests
- [ ] Submit with missing required fields → See error messages
- [ ] Submit with age < 18 → Rejected
- [ ] CSRF token validation works

#### Persistence Tests
- [ ] Save profile successfully
- [ ] Reload profile page → All data displays correctly
- [ ] Check database:
  ```sql
  SELECT * FROM users WHERE email = 'jane.test@example.com';
  SELECT * FROM user_preferences WHERE user_id = [jane's ID];
  ```
- [ ] Verify breastSize saved correctly
- [ ] Verify penisSize and bodyHair are NULL (not saved for female users)

---

## Persona 3: Alex Taylor (Non-Binary User)

### Credentials
- **Email:** alex.test@example.com
- **Username:** AlexTest
- **Password:** TestPass123!
- **Age:** 30
- **Gender:** Non-binary

### Profile Form Test Checklist

#### Visibility Tests
- [ ] ALL gender-specific fields visible (non-binary sees everything):
  - [ ] Penis size field visible
  - [ ] Body hair field visible
  - [ ] Breast size field visible
- [ ] Gender-neutral fields visible:
  - [ ] Pubic hair field visible
  - [ ] All preference checkboxes visible

#### Data Entry Tests
Fill in ALL fields INCLUDING both male and female attributes:
- [ ] **Basic Info:** Age, location, height, body type, ethnicity
- [ ] **Hair/Eyes:** Hair color, hair style, eye color
- [ ] **Physical Attributes:**
  - [ ] Pubic hair: "Natural"
  - [ ] Penis size: "N/A" or leave blank
  - [ ] Body hair: "Minimal"
  - [ ] Breast size: "A-cup" or leave blank
- [ ] **Preferences:** Check 10-15 random preference boxes across ALL categories

#### Validation Tests
- [ ] Submit with missing required fields → See error messages
- [ ] CSRF token validation works
- [ ] Can save profile with BOTH or NEITHER gender-specific attributes

#### Persistence Tests
- [ ] Save profile successfully
- [ ] Reload profile page → All data displays correctly
- [ ] Check database:
  ```sql
  SELECT * FROM users WHERE email = 'alex.test@example.com';
  SELECT * FROM user_preferences WHERE user_id = [alex's ID];
  ```
- [ ] Verify ALL selected attributes saved correctly (penisSize, bodyHair, breastSize)
- [ ] Verify non-binary users can have flexible attribute combinations

---

## Cross-Persona Tests

### Gender Preference Visibility
Test dynamic field showing/hiding based on "who you want to meet":

#### Test with John (Male)
- [ ] Select "Want to meet: Men only" → Male preference fields appear
- [ ] Select "Want to meet: Women only" → Female preference fields appear
- [ ] Select "Want to meet: Non-binary" → All preference fields appear
- [ ] Select "Want to meet: Everyone" → All preference fields appear

### Data Leakage Prevention
- [ ] John (male) cannot inject breastSize via browser console → Rejected by backend
- [ ] Jane (female) cannot inject penisSize via browser console → Rejected by backend
- [ ] Verify edit-profile.php unsets inappropriate fields based on gender (lines 48-62)

### Matching Algorithm Integration
After all three profiles are complete:
- [ ] John's matches should respect his preference checkboxes
- [ ] Jane's matches should respect her preference checkboxes
- [ ] Alex's matches should work with flexible attributes
- [ ] Verify `_getMatches.php` reads preference data correctly

---

## Test Execution Flow

1. **Setup:**
   ```bash
   php db/generate-test-users.php
   ```

2. **Test Each Persona:**
   - Sign in as user
   - Navigate to profile form (edit-profile.php or profile-form.php)
   - Follow checklist above
   - Take screenshots of any errors
   - Log results

3. **Database Verification:**
   ```bash
   php db/verify-test-data.php
   ```

4. **Cleanup:**
   ```bash
   php db/cleanup-test-users.php
   ```

---

## Expected Results

### Success Criteria
- ✅ All 150+ fields render correctly
- ✅ Gender-specific visibility works dynamically
- ✅ All data persists to database correctly
- ✅ No data leakage between genders
- ✅ CSRF protection works
- ✅ Form validation catches errors
- ✅ Mobile responsive (fields usable on phone)

### Known Issues to Watch For
- ⚠️ JavaScript errors in console
- ⚠️ Fields not saving to database
- ⚠️ Gender-specific fields showing when they shouldn't
- ⚠️ CSRF token failures on submission
- ⚠️ SQL errors in PHP error log

---

## Regression Logging

Create `test-results/` directory with:
- `YYYY-MM-DD-persona-name.md` - Test results for each persona
- `screenshots/` - Screenshots of any issues
- `errors.log` - Copy of PHP error log during testing
- `database-snapshots/` - SQL dumps before/after each test

**Next Step:** After E2E tests pass, proceed to venue check-in flow verification.
