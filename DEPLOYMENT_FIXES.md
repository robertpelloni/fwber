# Deployment Fixes (2025-12-24)

## Summary
Addressed issues with Mercure connection (401/CORS) and Photo Upload dialog responsiveness on `fwber.vercel.app`.

## Changes

### 1. Mercure Configuration (Backend)
- **File**: `fwber-backend/Caddyfile`
- **Change**: 
    - Hardcoded the working JWT keys (`fwber_mercure_secret_key_2025_secure_and_long`) to bypass potential environment variable injection issues.
    - Added `https://fwber.vercel.app` to the `cors_origins` directive.
- **Reason**: The production Mercure server was rejecting connections from the Vercel app due to missing CORS origin and key mismatch.

### 2. Laravel Configuration (Backend)
- **File**: `fwber-backend/.env.production`
- **Change**: Added `https://fwber.vercel.app` to `CORS_ALLOWED_ORIGINS`.
- **Reason**: Ensures Laravel API accepts requests from the specific Vercel domain.

### 3. Photo Upload UI (Frontend)
- **File**: `fwber-frontend/components/PhotoUpload.tsx`
- **Change**: 
    - Changed file input hiding method from `display: none` to `className="sr-only"`.
    - Added a visible "Select Photos" button.
- **Reason**: `display: none` inputs are sometimes ignored by browsers or screen readers, preventing the file dialog from opening programmatically.

## Required Actions

1. **Redeploy Backend**:
   - This is critical to apply the `Caddyfile` changes for Mercure.
   - Run your backend deployment pipeline.

2. **Redeploy Frontend**:
   - This is required to apply the `PhotoUpload.tsx` UI fixes.
   - Run your frontend deployment pipeline (Vercel).

3. **Verify**:
   - Open `https://fwber.vercel.app`.
   - Try uploading a photo.
   - Check browser console for Mercure connection errors (should be gone).
