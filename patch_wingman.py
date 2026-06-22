import re

with open('fwber-backend-ts/src/routes/wingman.ts', 'r') as f:
    content = f.read()

# Make OpenAI instantiation safe
safe_openai = """const apiKey = process.env.OPENROUTER_API_KEY || process.env.OPENAI_API_KEY;
const openai = apiKey ? new OpenAI({
  apiKey,
  baseURL: process.env.OPENROUTER_API_KEY ? 'https://openrouter.ai/api/v1' : undefined,
  defaultHeaders: process.env.OPENROUTER_API_KEY ? {
    "HTTP-Referer": "https://fwber.me",
    "X-Title": "fwber",
  } : undefined
}) : null;"""

content = re.sub(r'const openai = new OpenAI\(\{[\s\S]*?\}\);', safe_openai, content)

# Protect route handlers that use 'openai'
def add_openai_guard(match):
    return match.group(0) + "\n  if (!openai) return res.status(503).json({ error: 'AI provider not configured' });"

content = re.sub(r'(router\.post\(\'/.*?\', authenticate, async \(req.*? res\) => \{\n  try \{)', add_openai_guard, content)

with open('fwber-backend-ts/src/routes/wingman.ts', 'w') as f:
    f.write(content)
