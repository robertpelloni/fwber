with open('fwber-backend-ts/.env.example', 'r') as f:
    content = f.read()

email_vars = """
# Email Infrastructure
# If using Resend API:
RESEND_API_KEY="re_..."
# If using SMTP directly (Postfix on Hetzner, Mailgun, SendGrid, etc.):
MAIL_HOST="smtp.example.com"
MAIL_PORT="587"
MAIL_USER="user"
MAIL_PASS="password"
MAIL_FROM="noreply@fwber.me"
"""

if "RESEND_API_KEY" not in content:
    content += email_vars

with open('fwber-backend-ts/.env.example', 'w') as f:
    f.write(content)
