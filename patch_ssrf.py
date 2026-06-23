import re

with open('fwber-backend-ts/src/services/FederationService.ts', 'r') as f:
    content = f.read()

content = content.replace("import { URL } from 'url';", "import { URL } from 'url';\nimport { isSafeUrl } from '../lib/ssrf.js';")

# Apply isSafeUrl usage
content = content.replace(
    "if (parsed.protocol !== 'https:' && process.env.NODE_ENV !== 'test')",
    "if (!isSafeUrl(actorUri)) return null;\n    if (parsed.protocol !== 'https:' && process.env.NODE_ENV !== 'test')"
)

with open('fwber-backend-ts/src/services/FederationService.ts', 'w') as f:
    f.write(content)
