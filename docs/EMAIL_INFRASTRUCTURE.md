# fwber Email Infrastructure Guide

## Overview
fwber relies on robust email delivery for account verification and password recovery. The backend utilizes a tiered fallback system:
1. **Resend API** (Primary, High Deliverability)
2. **SMTP Transport** (Secondary / Custom Hosted Fallback)
3. **Console Logging** (Development / Failsafe mode)

## 1. Domain Configuration (DNS)
To achieve 100% deliverability and avoid Spam folders, you **must** configure DNS records on your domain registrar (e.g., Cloudflare, Hetzner, GoDaddy) pointing `fwber.me` to your chosen provider (like Resend).

### Required Records for Resend (Example)
Navigate to your DNS provider and add the following records (values will be provided by your Resend dashboard under "Domains"):

| Type  | Name / Host   | Value / Target                                  | Priority |
|-------|---------------|-------------------------------------------------|----------|
| TXT   | `resend._domainkey` | `k=rsa; p=YOUR_PUBLIC_KEY...` (DKIM)            | -        |
| TXT   | `@`           | `v=spf1 include:sendgrid.net ~all` (SPF)        | -        |
| MX    | `feedback`    | `feedback-smtp.us-east-1.amazonses.com`         | 10       |
| TXT   | `_dmarc`      | `v=DMARC1; p=none; rua=mailto:admin@fwber.me;`  | -        |

*Note: Once added, it may take 24-48 hours for DNS to propagate. Verify within the Resend console.*

## 2. Environment Variables
On your production server (Hetzner), update your `.env` file located in `fwber-backend-ts/.env`:

```env
# Primary Email Provider
RESEND_API_KEY="re_YOUR_LIVE_KEY_HERE"
MAIL_FROM="noreply@fwber.me"

# Fallback SMTP Settings (Optional if using Resend)
MAIL_HOST="smtp.example.com"
MAIL_PORT="587"
MAIL_USER="your-smtp-user"
MAIL_PASS="your-smtp-password"
```

## 3. Deployment
Once your `.env` is updated and DNS is verified:
1. Restart the backend process: `pm2 restart fwber-backend`
2. Test delivery by triggering a password reset or registering a new account.
3. Check `pm2 logs fwber-backend` to confirm the log outputs `[Email] Sent via Resend to...`
