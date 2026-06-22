import re

with open('fwber-backend-ts/src/routes/wingman.ts', 'r') as f:
    content = f.read()

# I see it complains because `openai` can be null. We need to cast it or use ?.
# Actually we already added a guard: if (!openai) return res.status(503).json(...);
# But TypeScript might not carry that refinement down depending on how it's structured.
# Let's just use `openai!` or `openai?.chat.completions.create`

content = content.replace("openai.chat.completions.create", "openai!.chat.completions.create")

with open('fwber-backend-ts/src/routes/wingman.ts', 'w') as f:
    f.write(content)
