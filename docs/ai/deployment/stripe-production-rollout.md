# Stripe Production Rollout Checklist

> **Version:** 1.0.76  
> **Status:** Backend prepared, DevOps configuration required.

The `fwber` platform integrates Stripe for the 200 FWB "Gold" subscription upgrade, including full webhook validation and an internal MLM-style referral token payout on renewal. The code is ready; this document serves as the operations-ready payout/reconciliation checklist and environment variable guide for production deployment.

## 1. Environment Variable Configuration
To flip the switch from mock to live, the following variables must be configured in Vercel (Frontend) and DreamHost (Backend).

**Backend (`fwber-backend/.env`):**
```env
STRIPE_KEY=sk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
# Note: The webhook secret MUST match the live endpoint registration in the Stripe Dashboard.
```

**Frontend (`fwber-frontend/.env.production`):**
```env
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_live_...
```

## 2. Stripe Webhook Registration
1. Log into the Stripe Dashboard.
2. Navigate to **Developers > Webhooks**.
3. Add a new endpoint: `https://api.fwber.me/api/stripe/webhook`
4. Select the following events to listen to:
   - `payment_intent.succeeded`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
5. Copy the generated Webhook Secret (`whsec_...`) and update the backend `.env`.

## 3. Product & Price Provisioning
The backend `PremiumPlanCatalog` expects specific price IDs. You must create these in Stripe:
1. Create a Product: "fwber Gold"
2. Create a Recurring Price for $15.00/month.
3. Obtain the `price_...` ID.
4. Ensure the backend config `config/premium.php` or `PremiumPlanCatalog.php` matches this live price ID.

## 4. Operations-Ready Payout / Reconciliation Checklist
When running a real-money system with virtual token rewards (FWB coins), reconciliation is critical to prevent token inflation and chargeback abuse.

- [ ] **Daily Reconciliation:** Match the sum of successful `payment_intent.succeeded` logs in Laravel to the Daily Gross Volume in Stripe.
- [ ] **Chargeback / Dispute Handling:** Currently, if a user charges back, the webhook controller does not automatically deduct FWB coins or revoke Gold status.
  - *Action:* Configure Stripe to send `charge.dispute.created` webhooks, and map it to a new backend method to suspend the account.
- [ ] **Referral Payout Audits:**
  - `invoice.payment_succeeded` awards 10% / 5% token bounties up the referral tree. 
  - Ensure the `ReferralCommissionService` logs its transactions clearly. Check the `referral_commissions` table weekly to ensure no cyclical referral loops are draining the reserve.
- [ ] **Zero-Auth Fallback:** Verify that if Stripe goes down, the client-side `PremiumUpgradeModal` gracefully falls back to explaining the outage, rather than hanging indefinitely.

## 5. Live Testing (Pre-Launch)
Before announcing to the public, do a $1 live transaction with a real card.
1. Sign up as a new user.
2. Purchase the $15 Gold plan.
3. Wait 1 minute.
4. Verify `token_balance` increased by 200 FWB.
5. Verify `is_premium` is true.
6. Cancel the subscription in Stripe.
7. Verify `customer.subscription.deleted` webhook fires and sets `is_premium` back to false upon expiration.