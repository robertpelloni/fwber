import os
import re

contacts_file = 'fwber-backend-ts/src/routes/contacts-integration.ts'
contacts_sync_file = 'fwber-backend-ts/src/routes/contacts-integration-sync.ts'

with open(contacts_file, 'r') as f:
    content = f.read()

# Instead of relying on a mock requireAuth, use the actual authenticateToken middleware
content = content.replace(
    "const requireAuth = (req: Request, res: Response, next: NextFunction) => {\n  req.user = { id: 'mock-user-uuid' } as any;\n  next();\n};",
    "import { authenticateToken } from '../middleware/auth';\n// Replaced mock requireAuth with actual authenticateToken"
)
content = content.replace('requireAuth', 'authenticateToken')

with open(contacts_file, 'w') as f:
    f.write(content)

with open(contacts_sync_file, 'r') as f:
    content = f.read()

content = content.replace(
    "const requireAuth = (req: Request, res: Response, next: NextFunction) => {\n  req.user = { id: 'mock-user-uuid' } as any;\n  next();\n};",
    "import { authenticateToken } from '../middleware/auth';\n// Replaced mock requireAuth with actual authenticateToken"
)
content = content.replace('requireAuth', 'authenticateToken')

with open(contacts_sync_file, 'w') as f:
    f.write(content)
