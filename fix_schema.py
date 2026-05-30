import re

with open('fwber-backend-ts/prisma/schema.prisma', 'r') as f:
    content = f.read()

# Replace String with BigInt for userId in UserIntegration and SyncedContact
content = re.sub(r'model UserIntegration \{\n\s+id\s+String\s+@id @default\(uuid\(\)\)\n\s+userId\s+String',
                 r'model UserIntegration {\n  id        String   @id @default(uuid())\n  userId    BigInt', content)

content = re.sub(r'model SyncedContact \{\n\s+id\s+String\s+@id @default\(uuid\(\)\)\n\s+userId\s+String',
                 r'model SyncedContact {\n  id        String   @id @default(uuid())\n  userId    BigInt', content)

with open('fwber-backend-ts/prisma/schema.prisma', 'w') as f:
    f.write(content)
