import re

with open('fwber-backend-ts/src/lib/email.ts', 'r') as f:
    content = f.read()

# Replace static import of Resend with dynamic import to avoid ESM crashes if resend is not installed but SMTP is used.
# Since it's typescript, we'll type it dynamically or use any.

new_content = """import nodemailer from 'nodemailer';
import * as dotenv from 'dotenv';
dotenv.config();

// Resend support (modern API-based email)
let resend: any = null;
if (process.env.RESEND_API_KEY) {
  (async () => {
    try {
      const { Resend } = await import('resend');
      resend = new Resend(process.env.RESEND_API_KEY);
      console.log('[Email] Resend API initialized');
    } catch (err: any) {
      console.error('[Email] Failed to load or initialize Resend module:', err.message);
    }
  })();
}
"""

content = re.sub(r"import nodemailer from 'nodemailer';\nimport \{ Resend \} from 'resend';\nimport \* as dotenv from 'dotenv';\ndotenv\.config\(\);\n\n// Resend support \(modern API-based email\)\nlet resend: Resend \| null = null;\nif \(process\.env\.RESEND_API_KEY\) \{\n  try \{\n    resend = new Resend\(process\.env\.RESEND_API_KEY\);\n    console\.log\('\[Email\] Resend API initialized'\);\n  \} catch \(err: any\) \{\n    console\.error\('\[Email\] Failed to initialize Resend:', err\.message\);\n  \}\n\}", new_content.strip(), content)

with open('fwber-backend-ts/src/lib/email.ts', 'w') as f:
    f.write(content)
