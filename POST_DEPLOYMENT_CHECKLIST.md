# Post-Deployment Verification Checklist

Use this checklist to verify that your deployment on DreamHost is fully functional, especially the real-time features.

## 1. SSL Verification (Mercure)
- [ ] **Check Certificate**: Visit `https://mercure.fwber.me/.well-known/mercure` in your browser.
    -   **Success**: You see a "Method Not Allowed" or 404 page, but the **lock icon** in the address bar is secure (no warnings).
    -   **Failure**: You see a "Privacy Error" or "Connection Not Secure".
    -   **Fix**: Ensure Let's Encrypt is enabled in DreamHost Panel and the `.htaccess` allows `.well-known` requests.

## 2. Environment Configuration
- [ ] **Frontend Env**: Verify `.env.production` on the server.
    ```bash
    cat ~/fwber/fwber-frontend/.env.production
    ```
    -   Ensure `NEXT_PUBLIC_MERCURE_URL` starts with `https://`.
    -   Example: `NEXT_PUBLIC_MERCURE_URL=https://mercure.fwber.me/.well-known/mercure`

## 3. Real-Time Connection
- [ ] **Open Console**: Open your browser's Developer Tools (F12) -> Console.
- [ ] **Login**: Log in to the application.
- [ ] **Check Logs**: Look for "Mercure" or "EventSource" logs.
    -   **Success**: No red errors related to connection. You might see "Mercure connected".
    -   **Failure**: `EventSource failed loading: GET "https://mercure.fwber.me..."`.
        -   **401 Unauthorized**: Cookie issue (CORS).
        -   **net::ERR_CERT_AUTHORITY_INVALID**: SSL issue (see step 1).
        -   **404 Not Found**: Proxy issue (check `.htaccess`).

## 4. Functional Tests
- [ ] **Typing Indicator**: Open a chat with a friend. Type in the box. Ask the friend (or check in a second browser window) if they see "Typing...".
- [ ] **Instant Message**: Send a message. It should appear instantly in the other window without refreshing.
- [ ] **Online Status**: The green dot on your avatar should be visible to others.

## 5. Troubleshooting Common Errors

### "Service Unavailable" (503)
-   **Cause**: Deployment script failed or maintenance mode got stuck.
-   **Fix**: Run `php artisan up` in `fwber-backend`.

### "CORS Missing Allow Origin"
-   **Cause**: Double headers or misconfiguration.
-   **Fix**: We fixed this in v0.2.3. Ensure you have pulled the latest code and `public/.htaccess` does NOT have `Header set Access-Control-Allow-Origin`.

### "404 Not Found" on `/_next/static/...`
-   **Cause**: Old build files cached or Vercel monitoring enabled.
-   **Fix**: We disabled Vercel monitoring in v0.2.3. Clear browser cache and rebuild frontend (`npm run build`).
