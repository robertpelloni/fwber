import re

with open('fwber-backend-ts/src/routes/quests.ts', 'r') as f:
    content = f.read()

content = content.replace("import crypto from 'crypto';\n\nimport crypto from 'crypto';", "import crypto from 'crypto';")

with open('fwber-backend-ts/src/routes/quests.ts', 'w') as f:
    f.write(content)
