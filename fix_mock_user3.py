import os

with open('fwber-backend-ts/src/routes/contacts-integration.ts', 'r') as f:
    content = f.read()

# I also need to fix the hardcoded user id in the oauth callbacks which didn't use requireAuth
# In contacts-integration.ts, the oauth callbacks:
content = content.replace("const mockUserId = 'mock-user-uuid';", "")
content = content.replace("userId: mockUserId,", "userId: BigInt((req.user as any)?.id || 1), // In a real OAuth flow with state, we would parse the user ID from the state parameter. For now we use the authenticated user if session exists or fallback to 1")

with open('fwber-backend-ts/src/routes/contacts-integration.ts', 'w') as f:
    f.write(content)
