# Webhook Handling Guide

## Overview
FWBer relies on webhooks primarily for Stripe integration to handle asynchronous payment events and subscription lifecycle updates.

## Stripe Webhooks

### Endpoint
`POST /api/webhooks/stripe`

### Security
- **Signature Verification**: All incoming requests are verified using the `STRIPE_WEBHOOK_SECRET`.
- **Middleware**: Custom middleware ensures the signature is valid before processing.

### Handled Events

| Event Type | Action | Side Effects |
|------------|--------|--------------|
| `customer.subscription.created` | Creates a new `Subscription` record. | Invalidates `user:{id}` cache. |
| `customer.subscription.updated` | Updates status, period end, and plan details. | Invalidates `user:{id}` cache. |
| `customer.subscription.deleted` | Marks subscription as `canceled`. | Invalidates `user:{id}` cache. |
| `invoice.payment_succeeded` | Logs a `Payment` record. | Invalidates `user:{id}` cache. |
| `invoice.payment_failed` | Logs a failed `Payment` record. | Triggers user notification (planned). |

### Idempotency
Stripe may send the same webhook event multiple times. Our handlers are designed to be idempotent:
- **Subscriptions**: We use `updateOrCreate` based on the `stripe_id`.
- **Payments**: We check if a payment with the `stripe_id` already exists before creating a new one.

### Testing Webhooks Locally
1. Install the Stripe CLI.
2. Forward events to your local server:
   ```bash
   stripe listen --forward-to localhost:8000/api/webhooks/stripe
   ```
3. Trigger test events:
   ```bash
   stripe trigger customer.subscription.created
   ```

### Troubleshooting
- **400 Bad Request**: Usually indicates a signature verification failure. Check `STRIPE_WEBHOOK_SECRET`.
- **500 Internal Server Error**: Check `laravel.log`. If the error is transient, Stripe will retry automatically.
