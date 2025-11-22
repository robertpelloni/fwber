# fwber Frontend - Project Status Document
**Last Updated**: 2025-11-04  
**Current Phase**: Post-TypeScript-Fixes, Pre-MVP-Core-Flow  
**Developer**: Robert Pelloni (solo developer, working at Dollar Tree)

---

## 🎯 Project Overview

**fwber** is a proximity-based social/dating application designed for alternative relationship styles and sexual health transparency.

### Tech Stack
- **Frontend**: Next.js 14.2.5 + React 18 + TypeScript (strict mode) + Tailwind CSS
- **Backend**: Laravel API (separate codebase, returns camelCase JSON)
- **Auth**: localStorage-based (tokens + user object)
- **Real-time**: WebSocket support (fully typed interfaces)
- **State**: React Query v5 for server state
- **Dev Server**: localhost:3003 (ports 3000-3002 occupied)

### Key Features (Design Intent)
- Proximity-based user discovery via geolocation
- Real-time messaging and presence
- Comprehensive profile fields: STI status, relationship styles, pronouns, sexual orientation
- Photo sharing and matching
- Location-based chatrooms
- Privacy controls for sensitive information

---

## 📋 Recent Session Summary (2025-11-04)

### Major Accomplishments ✅

1. **TypeScript Error Elimination**: Fixed all 116 TypeScript errors → 0 errors
   - Root cause: API returns camelCase, frontend expected snake_case
   - Updated `User` interface in `lib/auth-context.tsx` to match API format:
     - `email_verified: boolean` → `emailVerifiedAt: string | null`
     - `created_at: string` → `createdAt: string`
     - Added `updatedAt: string`
     - Completely redesigned `profile` structure to match API

2. **Auth System Fixed and Tested**
   - Registration flow working: validates password match/length → API call → localStorage save → redirect
   - Login flow working: API call → localStorage save → redirect
   - Session persistence working: refreshes maintain auth state
   - Dashboard displaying user data correctly ("Welcome, Robert Pelloni!")
   - Fixed router.push during render errors (wrapped in useEffect)

3. **Build System Working**
   - Production build succeeds: `npm run build` ✅
   - Fixed 14 linting errors (unescaped apostrophes in JSX)
   - Created `.eslintrc.json` with Next.js config
   - Remaining: 17 useEffect dependency warnings (non-blocking)

4. **Pages Tested Successfully**
   - `/dashboard` - Working, displays user data
   - `/proximity-chatrooms` - Working, no errors
   - `/recommendations` - Working, no errors
   - `/register` - Fully functional with validation
   - `/login` - Fully functional

### Files Modified in Last Session

**Critical Files Changed**:
```
lib/auth-context.tsx - User interface completely redesigned to camelCase
app/register/page.tsx - Added validation, useEffect redirect, cleaned console.logs
app/login/page.tsx - Added useEffect redirect
app/dashboard/page.tsx - Updated field names (createdAt, emailVerifiedAt, updatedAt)
app/error.tsx - Created (Next.js error boundary)
app/global-error.tsx - Created (root error boundary)
.eslintrc.json - Created (Next.js core-web-vitals config)
```

**Apostrophe Fixes** (14 occurrences across 6 files):
```
app/auth/signin/page.tsx - "Don't" → "Don&apos;t"
app/chatrooms/[id]/page.tsx - Multiple apostrophes fixed
app/location-settings/page.tsx - 2 apostrophes
app/matches/page.tsx - 1 apostrophe
app/profile/page.tsx - 4 apostrophes in select options
app/proximity-chatrooms/[id]/page.tsx - 2 apostrophes
```

---

## 🔧 Current Technical State

### Build Status
- ✅ TypeScript compilation: PASSING (0 errors)
- ✅ Production build: PASSING (with expected warnings)
- ⚠️ ESLint: 17 warnings (useEffect dependencies)
- ⚠️ Static generation errors (expected for auth-protected pages)

### Authentication State
```typescript
// lib/auth-context.tsx - Current User Interface
interface User {
  id: number
  email: string
  name: string
  emailVerifiedAt: string | null  // ISO timestamp or null
  createdAt: string               // ISO timestamp
  updatedAt: string               // ISO timestamp
  profile?: {
    displayName: string | null
    dateOfBirth: string | null
    gender: string | null
    pronouns: string | null
    sexualOrientation: string | null
    relationshipStyle: string | null
    bio: string | null
    locationLatitude: number | null
    locationLongitude: number | null
    locationDescription: string | null
    stiStatus: string | null
    preferences: Record<string, any> | null
    avatarUrl: string | null
    createdAt: string
    updatedAt: string
  }
}
```

**localStorage Keys**:
- `fwber_token` - JWT auth token
- `fwber_user` - Serialized User object (matches above interface)

### Known Working Components
- Auth context provider (`AuthProvider` wraps entire app in `app/layout.tsx`)
- Protected route wrapper (`ProtectedRoute` component)
- WebSocket hooks (typed, documented with JSDoc)
- Form validation (client-side, pre-API)

---

## ⚠️ Known Issues & Technical Debt

### High Priority (P0)

1. **17 useEffect Dependency Warnings** - Risk of stale closures, memory leaks
   - **Critical files** (must fix):
     - `lib/auth-context.tsx` - Auth session management
     - `lib/hooks/use-websocket.ts` - Socket lifecycle (166 line warning)
     - `lib/hooks/use-location.ts` - Location refetch
     - `lib/hooks/use-mercure-sse.ts` - SSE connections
     - `lib/websocket/client.ts` - React.useEffect in socket client
   
   - **Non-critical files** (can defer):
     - `app/analytics/page.tsx`
     - `app/messages/page.tsx`
     - `app/profile/page.tsx`
     - `components/PhotoUpload.tsx`
     - `components/SwipeableCard.tsx`

2. **WebSocket Reliability** - No hardening implemented
   - Missing: Heartbeat mechanism
   - Missing: Auto-reconnect with exponential backoff
   - Missing: Message acknowledgment system
   - Missing: Offline message queue
   - Missing: Token refresh on reconnect
   - Risk: Socket drops → lost messages → user frustration

3. **No Observability** - Debugging production issues will be difficult
   - No error tracking (Sentry not set up)
   - No performance monitoring
   - No user analytics/metrics
   - Only browser console logs (removed from register page)

### Medium Priority (P1)

4. **Image Optimization Warnings** (5 occurrences)
   - Using `<img>` instead of Next.js `<Image />` component
   - Affects: LCP (Largest Contentful Paint) performance
   - Files: `app/matches/page.tsx`, `app/photos/*`, `components/PhotoUpload.tsx`, `components/SwipeableCard.tsx`

5. **Missing API Type Generation**
   - No OpenAPI spec → TypeScript types workflow
   - Manual type updates required when API changes
   - Risk: Type drift between frontend/backend

6. **No E2E Tests**
   - Manual testing only
   - No Playwright/Cypress setup
   - Core flow regression risk

### Low Priority (P2)

7. **Missing PWA Manifest** - 500 errors in dev console
8. **Missing Favicon** - 404 errors in dev console
9. **Missing 'critters' Module** - CSS optimization warnings

---

## 🧪 Testing Status

### Manually Tested ✅
- User registration (password validation, success flow, error handling)
- User login
- Dashboard page rendering
- Auth persistence across page refreshes
- Proximity chatrooms page
- Recommendations page

### Untested ⚠️
- **Messages/Chat** - Real-time messaging functionality
- **Matches** - Matching algorithm and UI
- **Profile Editing** - Full profile CRUD operations
- **Photos** - Upload, display, moderation
- **Analytics** - User stats and insights
- **Location Settings** - Permission handling, manual location
- **WebSocket Features** - Presence, typing indicators, notifications
- **Error Boundaries** - Actual error scenarios
- **Mobile Responsiveness** - Only tested on desktop
- **Token Expiry** - Auth refresh flow
- **Network Failures** - Offline/online transitions

---

## 🎯 Strategic Direction (Multi-Model Consensus)

Three AI models (Gemini 2.5 Pro, Grok-4, GPT-5 Pro) were consulted on 2025-11-04. Here's the synthesis:

### Agreed Priority: "Hardened MVP Sprint" (1-2 weeks)

**Phase 1: Critical Infrastructure (3-4 days)**
1. Triage & fix critical useEffect warnings (auth, websocket, location hooks) - 1 day
2. Set up basic observability (Sentry free tier) - 1 day
3. WebSocket hardening (heartbeat, reconnect, message acks) - 2 days

**Phase 2: Core Activation Flow (4-6 days)**
Build minimal viable loop:
- Minimal profile setup (name, age, 1 photo - skip advanced fields)
- Location permission request
- Simple proximity feed
- Basic real-time messaging (text only)

**Phase 3: Manual E2E Validation (1 day)**
Test complete user journey in two browser windows

### Deferred (Explicitly NOT doing now)
- Analytics page
- Advanced profile fields (STI status, relationship preferences, etc.)
- Photo albums/moderation
- Sophisticated matching algorithm
- Remaining useEffect warnings in non-critical paths
- Image optimization
- Backend improvements

### Key Insights from Models

**Gemini (9/10 confidence)**: "Existential risk of building wrong thing > technical debt"
**Grok (8/10 confidence)**: "Privacy-sensitive app needs stability foundation"
**GPT-5 (8/10 confidence)**: "Focus on time-to-first-message metric"

All agreed: Solo developer context means pragmatism over perfection.

---

## 📁 Project Structure

```
fwber-frontend/
├── app/                      # Next.js 14 app directory
│   ├── layout.tsx           # Root layout (AuthProvider wrapper)
│   ├── page.tsx             # Homepage
│   ├── dashboard/           # ✅ Tested, working
│   ├── register/            # ✅ Fixed, validated
│   ├── login/               # ✅ Fixed, working
│   ├── proximity-chatrooms/ # ✅ Tested, working
│   ├── recommendations/     # ✅ Tested, working
│   ├── messages/            # ⚠️ Untested
│   ├── matches/             # ⚠️ Untested
│   ├── profile/             # ⚠️ Untested (extensive)
│   ├── photos/              # ⚠️ Untested
│   ├── analytics/           # ⚠️ Untested, deferred
│   ├── location-settings/   # ⚠️ Untested
│   ├── chatrooms/           # ⚠️ Untested
│   ├── websocket/           # ⚠️ Untested
│   ├── bulletin-boards/     # ⚠️ Untested
│   ├── error.tsx            # ✅ Created, untested
│   └── global-error.tsx     # ✅ Created, untested
├── components/              # Shared React components
│   ├── PhotoUpload.tsx      # Has useEffect warning
│   ├── SwipeableCard.tsx    # Has useEffect warning
│   └── ProtectedRoute.tsx   # ✅ Working
├── lib/
│   ├── auth-context.tsx     # ✅ CRITICAL - Recently fixed, has warning
│   ├── hooks/
│   │   ├── use-websocket.ts # ⚠️ CRITICAL - Major warning (166 line)
│   │   ├── use-location.ts  # ⚠️ CRITICAL - Has warning
│   │   └── use-mercure-sse.ts # Has warning
│   └── websocket/
│       └── client.ts        # ⚠️ Has React.useEffect warning
├── public/                  # Static assets
├── types/                   # TypeScript type definitions
├── .eslintrc.json          # ✅ Created this session
├── next.config.js
├── tailwind.config.ts
├── tsconfig.json           # Strict mode enabled
└── package.json
```

---

## 🔑 Key Environment Details

### API Configuration
```bash
# .env.local (not in version control)
NEXT_PUBLIC_API_URL=https://api.fwber.me  # Laravel backend
NEXT_PUBLIC_WS_URL=wss://ws.fwber.me      # WebSocket server
```

### Developer Notes
- **Current dev**: Robert Pelloni (solo, working at Dollar Tree)
- **Time constraints**: Part-time availability expected
- **Known personality**: Indie game developer, creator of "bob's game", Christian, ADHD/Bipolar
- **Context**: This is a passion project, not funded startup
- **Risk tolerance**: Can afford to ship MVP fast, iterate based on usage
- **Privacy concerns**: Handles sensitive data (STI status, location, sexual preferences)

---

## 🚀 Immediate Next Steps (Actionable)

### Step 1: Fix Critical useEffect Warnings (Day 1)

**File: `lib/hooks/use-websocket.ts` (Line 166)**
```typescript
// Current: Missing 13+ dependencies
useEffect(() => {
  // Socket setup logic
}, []) // ⚠️ Missing deps

// Need to: Add refs, useCallback, proper dependency array
```

**File: `lib/auth-context.tsx`**
```typescript
// Fix missing dependencies in auth session effect
// Add token to dependency array or use ref pattern
```

**File: `lib/hooks/use-location.ts` (Line 106)**
```typescript
// Missing 'refetch' dependency
// Add to array or wrap in useCallback
```

**Action Items**:
1. Read each file's useEffect implementation
2. Identify stale closure risks (callbacks, state references)
3. Apply fixes: useCallback for functions, refs for non-reactive values
4. Document suppressions with rationale if deliberately ignoring

### Step 2: Set Up Sentry (Day 1)

1. Create free Sentry account (sentry.io)
2. Get DSN key
3. Install: `npm install @sentry/nextjs`
4. Run: `npx @sentry/wizard@latest -i nextjs`
5. Add to `.env.local`:
   ```bash
   NEXT_PUBLIC_SENTRY_DSN=https://...@sentry.io/...
   ```
6. Test error tracking in dashboard

### Step 3: WebSocket Hardening (Days 2-3)

**File: `lib/hooks/use-websocket.ts`**

Add features:
```typescript
// 1. Heartbeat
const heartbeatInterval = useRef<NodeJS.Timeout>()
useEffect(() => {
  if (connected) {
    heartbeatInterval.current = setInterval(() => {
      send({ type: 'ping' })
    }, 30000) // 30 seconds
  }
  return () => clearInterval(heartbeatInterval.current)
}, [connected])

// 2. Auto-reconnect with backoff
const reconnectAttempts = useRef(0)
const reconnect = useCallback(() => {
  const delay = Math.min(1000 * 2 ** reconnectAttempts.current, 30000)
  setTimeout(() => {
    reconnectAttempts.current++
    connect()
  }, delay)
}, [connect])

// 3. Message acknowledgment
const pendingMessages = useRef<Map<string, Message>>(new Map())
const sendWithAck = useCallback((message: Message) => {
  const id = crypto.randomUUID()
  pendingMessages.current.set(id, message)
  send({ ...message, id })
}, [send])
```

### Step 4: Map Core Flow Files (Day 4)

Identify exact files needed for:
- Minimal profile creation
- Location permission request
- Proximity feed display
- Basic messaging

Document:
- Which API endpoints each page calls
- Which components are reusable vs need creation
- Which WebSocket events are required

---

## 📊 Success Metrics (How to Know We're Done)

### Phase 1 Success Criteria
- [ ] Zero useEffect warnings in `lib/auth-context.tsx`
- [ ] Zero useEffect warnings in `lib/hooks/use-websocket.ts`
- [ ] Zero useEffect warnings in `lib/hooks/use-location.ts`
- [ ] Sentry receiving test errors
- [ ] WebSocket reconnects after manual disconnect
- [ ] No console errors on happy path

### Phase 2 Success Criteria (MVP)
- [ ] User can register → set location → see ≥1 nearby user → send message → receive reply
- [ ] Messages persist across page refresh
- [ ] Socket reconnects automatically on network interruption
- [ ] No data loss during reconnection
- [ ] Two-browser-window test passes end-to-end

### Phase 3 Success Criteria (Launch Ready)
- [ ] 95%+ manual test success rate
- [ ] <2% message delivery failures (measured via console logs)
- [ ] Token expiry handled gracefully
- [ ] Location permission denial has fallback
- [ ] Basic privacy controls visible (who sees profile)

---

## 🆘 Common Issues & Solutions

### "Cannot find module 'critters'"
**Status**: Known, non-blocking  
**Impact**: CSS optimization in production builds  
**Solution**: Ignore for now, doesn't affect functionality

### "Authentication required" prerender error
**Status**: Expected  
**Impact**: Protected pages can't be statically generated  
**Solution**: This is correct behavior, pages render server-side

### Dev server won't start (ports in use)
**Solution**: 
```bash
# Find and kill process on port 3003
Get-Process -Name node | Stop-Process -Force
npm run dev
```

### localStorage not persisting
**Check**: Browser privacy/incognito mode  
**Check**: `fwber_token` and `fwber_user` keys exist  
**Fix**: Clear localStorage and re-login

### TypeScript errors after API changes
**Solution**: Update User interface in `lib/auth-context.tsx` to match API  
**Prevention**: Set up OpenAPI → TS codegen (P1 priority)

---

## 📚 Additional Context

### Design Decisions

**Why localStorage over cookies?**
- Simpler for MVP (no CSRF complexity)
- Works with separate API domain
- Easy to debug (inspect in DevTools)
- Trade-off: Not httpOnly secure (acceptable for MVP)

**Why not NextAuth?**
- Laravel backend provides auth
- Avoided dependency on third-party auth flow
- More control over token handling
- Removed NextAuth in recent session

**Why WebSocket over Server-Sent Events?**
- Bidirectional needed for typing indicators
- Better for real-time chat UX
- SSE hooks exist but unused

### Related Documentation

- Next.js 14 App Router: https://nextjs.org/docs/app
- React Query v5: https://tanstack.com/query/latest
- Tailwind CSS: https://tailwindcss.com/docs
- TypeScript Strict Mode: https://www.typescriptlang.org/tsconfig#strict

---

## 🤝 Handoff Checklist

For next session/developer:

- [ ] Read this document completely
- [ ] Verify dev environment: `npm run dev` starts on :3003
- [ ] Check auth works: Register new user, see dashboard
- [ ] Review git status: `git status` (uncommitted changes?)
- [ ] Check eslint warnings: `npm run lint`
- [ ] Run build to verify: `npm run build`
- [ ] Read multi-model consensus section for context
- [ ] Understand solo developer constraint
- [ ] Review immediate next steps (useEffect fixes first)

---

**Last Session End State**: 2025-11-04 23:45 UTC  
**Next Session Should Start With**: "Fix critical useEffect warnings in lib/hooks/use-websocket.ts (line 166)"

**Human's Last Words**: "Let's continue to proceed as you recommend. Please first document your findings..."

---

*This document is a living record. Update after each significant session.*
