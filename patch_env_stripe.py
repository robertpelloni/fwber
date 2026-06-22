with open('fwber-backend-ts/.env.example', 'r') as f:
    content = f.read()

stripe_vars = """
# Stripe Payments Infrastructure
# Test Mode Keys: (pk_test_... / sk_test_...)
# Live Mode Keys: (pk_live_... / sk_live_...)
STRIPE_SECRET_KEY="sk_test_..."
STRIPE_WEBHOOK_SECRET="whsec_..."
"""

if "STRIPE_SECRET_KEY" not in content:
    content += stripe_vars

with open('fwber-backend-ts/.env.example', 'w') as f:
    f.write(content)
