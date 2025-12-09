# FWBer Frontend Testing Guide

This guide provides comprehensive testing procedures for the FWBer frontend application. Use this checklist to ensure all features work correctly before deploying to production.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Testing Recent Improvements](#testing-recent-improvements)
- [Feature Testing Checklist](#feature-testing-checklist)
- [Common Testing Scenarios](#common-testing-scenarios)
- [Browser Compatibility Testing](#browser-compatibility-testing)
- [Performance Testing](#performance-testing)
- [Security Testing](#security-testing)
- [Accessibility Testing](#accessibility-testing)

## Prerequisites

### Development Environment
- Node.js 18+ installed
- Backend API running on `http://localhost:8000` (or configured API endpoint)
- Port `3005` available locally (Next.js dev server + Cypress e2e rely on it)
- Test user accounts with different roles/permissions
- Browser DevTools familiarity (Console, Network, Application tabs)

### Test Data Requirements
- Multiple user accounts for testing matches/messages
- Test images for profile photos (various sizes: small, large, invalid formats)
- Local placeholder asset: `/images/test-avatar.svg` (used by Cypress mocks to avoid external fetches)
- Location services enabled for geolocation features
- WebSocket connection capabilities

## Testing Recent Improvements

### useEffect Dependency Fixes

**Components to Test:**
1. **WebSocket Hook** (`lib/hooks/use-websocket.ts`)
   - Open browser DevTools Console
   - Navigate to any page that uses WebSocket (messages, matches)
   - Verify no "missing dependency" warnings in console
   - Test connection/disconnection scenarios
   - Verify no memory leaks when navigating away and back

2. **Location Hook** (`lib/hooks/use-location.ts`)
   - Allow location permissions when prompted
   - Navigate to discovery page
   - Check console for no warnings
   - Test "Refetch Location" button functionality
   - Verify location updates correctly

3. **Photo Upload** (`components/PhotoUpload.tsx`)
   - Upload multiple photos
   - Delete photos while gallery is open
   - Verify gallery closes when last photo is deleted
   - Check console for no useEffect warnings

4. **Swipeable Card** (`components/SwipeableCard.tsx`)
   - Test swipe gestures on mobile (or DevTools mobile emulation)
   - Test mouse drag on desktop
   - Verify smooth animations
   - Check console for no warnings during drag operations

5. **Analytics Page** (`app/analytics/page.tsx`)
   - Navigate to `/analytics`
   - Change time range filter
   - Verify data refreshes correctly
   - Check console for no useEffect warnings

### API Error Handling & Retry Logic

**Test Scenarios:**

1. **Network Failure Simulation:**
   ```javascript
   // In DevTools Console, enable offline mode:
   // DevTools → Network tab → Throttling dropdown → Offline

   // Test GET requests (should retry 2 times):
   // - Navigate to discovery page
   // - Observe Network tab for 3 total attempts (initial + 2 retries)
   // - Should see exponential backoff: ~1s, ~2s delays

   // Test POST requests (should retry 1 time):
   // - Try to send a message
   // - Observe Network tab for 2 total attempts

   // Test DELETE requests (should NOT retry):
   // - Try to delete a photo
   // - Observe Network tab for 1 attempt only
   ```

2. **API Error Response Testing:**
   ```javascript
   // Test 401 Unauthorized (should not retry):
   // - Manually expire auth token or use invalid token
   // - Make any API request
   // - Should fail immediately without retries
   // - Should redirect to login page

   // Test 404 Not Found (should not retry):
   // - Navigate to non-existent profile
   // - Should show error immediately

   // Test 500 Server Error (should retry):
   // - Backend devs: temporarily make endpoint return 500
   // - Should retry with exponential backoff
   // - Should show error after all retries exhausted
   ```

3. **Error Type Checking:**
   ```javascript
   // In console, verify error types:
   try {
     await apiClient.get('/api/nonexistent')
   } catch (error) {
     console.log('Is ApiError?', isApiError(error))
     console.log('Is Network Error?', isNetworkError(error))
     console.log('Error message:', getErrorMessage(error))
   }
   ```

### Security Headers

**Test Procedure:**
1. Open DevTools Network tab
2. Load any page
3. Click on the HTML document request
4. Check Response Headers for:
   - `X-Content-Type-Options: nosniff`
   - `X-XSS-Protection: 1; mode=block`
   - `X-Frame-Options: SAMEORIGIN`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Content-Security-Policy: ...` (should be present)
   - `Permissions-Policy: ...` (should be present)
   - `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` (HTTPS only)
   - Verify `X-Powered-By` and `Server` headers are NOT present

### PWA Icons

**Test Procedure:**
1. Check `/favicon.svg` loads correctly in browser tab
2. Install PWA on mobile device:
   - iOS Safari: Share → Add to Home Screen
   - Android Chrome: Menu → Add to Home Screen
3. Verify icon appears correctly on home screen
4. Open app from home screen, verify proper branding
5. Check `manifest.json` loads correctly in DevTools Application tab

## Feature Testing Checklist

### Authentication Flow

- [ ] **Registration**
  - [ ] Register with valid email/password
  - [ ] Verify email validation (invalid formats rejected)
  - [ ] Verify password requirements enforced
  - [ ] Check error messages are clear
  - [ ] Verify successful registration redirects to profile setup

- [ ] **Login**
  - [ ] Login with valid credentials
  - [ ] Login with invalid credentials (should show error)
  - [ ] "Remember me" functionality
  - [ ] Redirect to protected pages after login
  - [ ] Token stored correctly in localStorage

- [ ] **Logout**
  - [ ] Logout clears auth token
  - [ ] Redirects to login page
  - [ ] Protected routes inaccessible after logout

- [ ] **Password Reset**
  - [ ] Request password reset with valid email
  - [ ] Receive reset email (check email client)
  - [ ] Reset password with valid token
  - [ ] Expired token shows appropriate error

### Profile Management

- [ ] **Profile Setup**
  - [ ] Upload profile photo
  - [ ] Set display name, age, bio
  - [ ] Set gender and preferences
  - [ ] Enable/disable location sharing
  - [ ] Save profile successfully

- [ ] **Photo Upload**
  - [ ] Upload multiple photos (test limit)
  - [ ] Set primary photo
  - [ ] Reorder photos with drag-and-drop
  - [ ] Delete photos
  - [ ] Test various image formats (JPEG, PNG, HEIC)
  - [ ] Test large image files (>10MB)
  - [ ] Test invalid file types (should reject)
  - [ ] Verify photo compression/resizing

- [ ] **Privacy Settings**
  - [ ] Toggle photo privacy (public/matched-only/tier-based)
  - [ ] Toggle location visibility
  - [ ] Test blocking users
  - [ ] Verify blocked users can't see profile

### Discovery & Matching

- [ ] **Discovery Feed**
  - [ ] Load discovery feed with potential matches
  - [ ] Verify distance calculations are accurate
  - [ ] Check compatibility scores display correctly
  - [ ] Verify pagination/infinite scroll

- [ ] **Swipe Functionality**
  - [ ] Swipe right (like) works
  - [ ] Swipe left (pass) works
  - [ ] Swipe up (super like) works
  - [ ] Button clicks work same as swipes
  - [ ] Test gesture animations smooth
  - [ ] Test on touch devices and desktop

- [ ] **Matching**
  - [ ] Mutual like creates match
  - [ ] Match notification appears
  - [ ] Match appears in matches list
  - [ ] Super like creates special notification

### Messaging

- [ ] **Conversation List**
  - [ ] All matches appear in list
  - [ ] Last message preview shows correctly
  - [ ] Unread count badge displays
  - [ ] Sorting by most recent message works
  - [ ] Empty state shows when no matches

- [ ] **Chat Interface**
  - [ ] Send text message
  - [ ] Receive messages in real-time (WebSocket)
  - [ ] Message timestamps display correctly
  - [ ] Scroll to bottom when new message arrives
  - [ ] Load message history (pagination)
  - [ ] "Typing..." indicator works
  - [ ] Read receipts show correctly

- [ ] **Media Messages**
  - [ ] Send image message
  - [ ] Image preview/lightbox works
  - [ ] Image loading states display
  - [ ] Failed uploads show error and retry option

### Relationship Tiers

- [ ] **Tier Progression**
  - [ ] View current tier with match
  - [ ] Upgrade tier (if criteria met)
  - [ ] Downgrade tier (if applicable)
  - [ ] Tier-specific photo access works
  - [ ] Tier history displays correctly

- [ ] **Photo Access by Tier**
  - [ ] Public photos visible to everyone
  - [ ] Matched photos visible after match
  - [ ] Tier 1-5 photos visible at correct tier
  - [ ] Blurred/locked indicators show for inaccessible photos

### Bulletin Boards

- [ ] **Community Feed**
  - [ ] Load bulletin board feed
  - [ ] Post new message to board
  - [ ] React to messages (like, etc.)
  - [ ] Reply to messages
  - [ ] Delete own messages
  - [ ] Report inappropriate content

- [ ] **Moderation**
  - [ ] Moderator role can see flagged content
  - [ ] Moderator can remove messages
  - [ ] Moderation actions log correctly

### Location Features

- [ ] **Geolocation**
  - [ ] Grant location permission
  - [ ] Current location detected accurately
  - [ ] Distance to other users calculated correctly
  - [ ] Location updates when user moves (if watching)
  - [ ] Handle location permission denied gracefully

- [ ] **Location-based Discovery**
  - [ ] Filter matches by distance radius
  - [ ] Verify distance sorting works
  - [ ] Test edge cases (international, very far distances)

### Analytics & Insights

- [ ] **User Analytics**
  - [ ] View profile views count
  - [ ] View like/match statistics
  - [ ] View message statistics
  - [ ] Time range filters work (7d, 30d, 90d)
  - [ ] Charts render correctly
  - [ ] Data auto-refreshes periodically

## Common Testing Scenarios

### Network Failure Recovery

1. **Offline → Online Transition:**
   - Enable offline mode in DevTools
   - Try to perform actions (should queue or show error)
   - Re-enable network
   - Verify actions retry and complete successfully

2. **Slow Network:**
   - Set Network throttling to "Slow 3G"
   - Navigate through app
   - Verify loading states show correctly
   - Verify no broken states or crashes

3. **API Endpoint Failures:**
   - Backend temporarily unavailable
   - Verify retry logic works
   - Verify error messages are user-friendly
   - Verify app doesn't crash

### Auth Edge Cases

1. **Token Expiration:**
   - Let auth token expire (or manually delete)
   - Try to access protected route
   - Should redirect to login
   - After re-login, should return to intended page

2. **Concurrent Sessions:**
   - Login on Device A
   - Login on Device B (same account)
   - Verify both sessions work OR
   - Verify single-session enforcement if implemented

3. **Logout During Operation:**
   - Start uploading photo or sending message
   - Logout in another tab
   - Verify operation handles auth loss gracefully

### File Upload Edge Cases

1. **Large Files:**
   - Try uploading 50MB+ image
   - Should show size limit error OR compress automatically

2. **Invalid File Types:**
   - Try uploading .exe, .txt, .pdf
   - Should reject with clear error message

3. **Network Interruption During Upload:**
   - Start photo upload
   - Disable network mid-upload
   - Should show error and provide retry option

4. **Simultaneous Uploads:**
   - Upload multiple photos at once
   - Verify all complete successfully
   - Verify UI shows progress for each

### WebSocket Connection

1. **Connection Loss:**
   - Disable network while in chat
   - Should detect disconnection
   - Re-enable network
   - Should auto-reconnect and sync messages

2. **Background Tab:**
   - Open chat in tab
   - Switch to another tab for 5+ minutes
   - Return to chat tab
   - Should reconnect and show new messages

3. **Server Restart:**
   - Backend devs: restart WebSocket server
   - Frontend should detect and reconnect
   - Should not lose message draft or state

### Location Permission States

1. **Permission Granted:**
   - Standard flow, everything works

2. **Permission Denied:**
   - App should show helpful message
   - Should offer manual location entry or disable location features

3. **Permission Prompt Dismissed:**
   - User closes prompt without deciding
   - Should show how to re-enable in browser settings

4. **Location Services Disabled:**
   - Disable location services at OS level
   - App should detect and show appropriate message

## Browser Compatibility Testing

Test on the following browsers and versions:

### Desktop
- [ ] **Chrome** (latest 2 versions)
  - [ ] Windows 10/11
  - [ ] macOS
  - [ ] Linux
- [ ] **Firefox** (latest 2 versions)
  - [ ] Windows 10/11
  - [ ] macOS
- [ ] **Safari** (latest 2 versions)
  - [ ] macOS
- [ ] **Edge** (latest version)
  - [ ] Windows 10/11

### Mobile
- [ ] **iOS Safari**
  - [ ] iOS 15+
  - [ ] iPhone SE, iPhone 12/13/14/15
  - [ ] iPad
- [ ] **Android Chrome**
  - [ ] Android 11+
  - [ ] Various screen sizes (small, medium, large)
- [ ] **Samsung Internet**
  - [ ] Latest version

### Key Features to Test Cross-Browser
- [ ] Photo upload and display
- [ ] WebSocket connections
- [ ] Geolocation
- [ ] Touch gestures (swipe)
- [ ] CSS Grid and Flexbox layouts
- [ ] Form validations
- [ ] PWA installation

## Performance Testing

### Page Load Performance

1. **First Contentful Paint (FCP):**
   - Should be < 1.8s on 4G
   - DevTools → Lighthouse → Performance audit

2. **Time to Interactive (TTI):**
   - Should be < 3.5s on 4G
   - Test on throttled network

3. **Largest Contentful Paint (LCP):**
   - Should be < 2.5s
   - Profile images should load quickly

4. **Cumulative Layout Shift (CLS):**
   - Should be < 0.1
   - Watch for image loading causing layout shifts

### Memory Leaks

1. **Component Mount/Unmount:**
   - Open DevTools → Memory tab
   - Take heap snapshot
   - Navigate through 10+ pages
   - Return to starting page
   - Take another heap snapshot
   - Compare - memory should not grow significantly

2. **WebSocket Connections:**
   - Check Network tab → WS
   - Navigate to chat, leave, return multiple times
   - Verify old connections are closed
   - Should only have 1 active WS connection

3. **Event Listeners:**
   - Install "Event Listeners" Chrome extension
   - Navigate through app
   - Verify listeners are cleaned up when leaving pages
   - Should not have hundreds of orphaned listeners

### Bundle Size

```bash
# Run production build
npm run build

# Check bundle sizes
# Main bundle should be < 300KB gzipped
# Each route chunk should be < 100KB gzipped

# Use webpack-bundle-analyzer if available
npm run analyze
```

### Image Optimization

- [ ] Verify images are compressed/optimized
- [ ] Check for next-gen formats (WebP with fallback)
- [ ] Verify lazy loading works for off-screen images
- [ ] Test responsive image sizes (srcset)

## Security Testing

### XSS Prevention

1. **Input Sanitization:**
   - Try entering `<script>alert('XSS')</script>` in:
     - [ ] Bio field
     - [ ] Message input
     - [ ] Bulletin board post
   - Should be escaped/sanitized, not executed

2. **HTML Injection:**
   - Try entering `<img src=x onerror=alert('XSS')>` in inputs
   - Should not execute

### CSRF Prevention

- [ ] Verify API requests include proper CSRF tokens
- [ ] Test state-changing operations require valid token
- [ ] Cross-origin requests should be blocked

### Content Security Policy

- [ ] Open DevTools Console
- [ ] Look for CSP violations
- [ ] Should have no violations in normal operation
- [ ] Try to load external script (should be blocked)

### Authentication Security

- [ ] Tokens stored securely (not in cookies if XSS risk)
- [ ] Tokens expire after appropriate time
- [ ] Refresh token flow works securely
- [ ] Sensitive operations require re-authentication

### Data Privacy

- [ ] Private photos not accessible via direct URL
- [ ] User location not exposed beyond intended precision
- [ ] Blocked users can't access your data
- [ ] Deleted data actually removed from UI and API

## Accessibility Testing

### Keyboard Navigation

- [ ] Tab through all interactive elements in order
- [ ] Enter/Space activates buttons
- [ ] Escape closes modals/dialogs
- [ ] Arrow keys navigate through lists
- [ ] Focus indicators visible on all elements

### Screen Reader Testing

**Test with:**
- [ ] **NVDA** (Windows) - Free
- [ ] **JAWS** (Windows) - Paid
- [ ] **VoiceOver** (macOS/iOS) - Built-in

**Check:**
- [ ] All images have alt text
- [ ] Form inputs have labels
- [ ] Buttons have descriptive text
- [ ] Links indicate purpose
- [ ] Dynamic content announces changes
- [ ] Modal focus management works

### ARIA Attributes

- [ ] Roles defined correctly (`role="button"`, etc.)
- [ ] States communicated (`aria-expanded`, `aria-selected`)
- [ ] Labels and descriptions present (`aria-label`, `aria-describedby`)
- [ ] Live regions for dynamic updates (`aria-live`)

### Color Contrast

- [ ] Run Lighthouse accessibility audit
- [ ] Text contrast ratio ≥ 4.5:1 for normal text
- [ ] Text contrast ratio ≥ 3:1 for large text
- [ ] UI components contrast ratio ≥ 3:1

### Responsive Design

- [ ] Test at 320px width (iPhone SE)
- [ ] Test at 375px width (iPhone 12/13)
- [ ] Test at 768px width (iPad)
- [ ] Test at 1024px width (iPad Pro)
- [ ] Test at 1920px width (Desktop)
- [ ] No horizontal scrolling on any screen size
- [ ] Touch targets ≥ 44×44px on mobile

### Visual Impairment Simulation

- [ ] Test with browser zoom at 200%
- [ ] Test with high contrast mode
- [ ] Test with dark mode enabled
- [ ] Test with color blindness simulator

## Automated Testing

### Unit Tests

```bash
# Run all unit tests
npm run test

# Run with coverage
npm run test:coverage

# Should have > 80% coverage for:
# - Utility functions (lib/utils/)
# - API client (lib/api/client.ts)
# - Custom hooks (lib/hooks/)
```

### Integration Tests

```bash
# Cypress E2E suite (auto-starts Next.js dev server on http://localhost:3005)
npm run test:e2e

# Targeted specs
npm run test:e2e:pulse
npm run test:e2e:matching

# Key flows to observe in any run:
# - Complete registration → profile setup → discovery
# - Match creation → messaging
# - Photo upload → tier progression → photo access
```

> **Note:** The scripts above use `start-server-and-test` to launch `npm run dev:3005` and wait for `http://localhost:3005` before executing Cypress. Make sure the Laravel API is reachable at `http://localhost:8000` (or adjust `NEXT_PUBLIC_API_URL`).

### Visual Regression Tests

```bash
# If using Percy, Chromatic, or similar
npm run test:visual

# Capture screenshots of:
# - All major pages
# - Component variants
# - Responsive breakpoints
```

## Pre-Deployment Checklist

Before deploying to production, verify:

- [ ] All tests pass (unit, integration, e2e)
- [ ] No console errors or warnings in production build
- [ ] Lighthouse scores: Performance > 80, Accessibility > 90, Best Practices > 90, SEO > 90
- [ ] All environment variables configured correctly
- [ ] API endpoints point to production backend
- [ ] Error tracking configured (Sentry, etc.)
- [ ] Analytics configured (if applicable)
- [ ] PWA manifest and service worker tested
- [ ] HTTPS certificate valid
- [ ] Security headers verified in production
- [ ] Database migrations run successfully
- [ ] Backup/rollback plan prepared
- [ ] Monitoring alerts configured
- [ ] Load testing completed (if high traffic expected)

## Reporting Issues

When you find a bug, report it with:

1. **Environment:**
   - Browser and version
   - OS and version
   - Device type
   - Screen size

2. **Steps to Reproduce:**
   - Numbered list of exact steps
   - Starting state/preconditions

3. **Expected vs Actual:**
   - What should happen
   - What actually happens

4. **Evidence:**
   - Screenshots or screen recording
   - Console errors (full stack trace)
   - Network requests (HAR file if needed)

5. **Severity:**
   - Critical: App unusable, data loss
   - High: Major feature broken
   - Medium: Feature partially broken
   - Low: Cosmetic issue, minor UX problem

---

**Last Updated:** November 20, 2025

For questions or suggestions about this testing guide, please contact the development team.
