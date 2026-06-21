import re

with open('fwber-backend-ts/tests/QuestVerification.test.ts', 'r') as f:
    content = f.read()

content = content.replace("import { app } from '../src/app.js';", "const { app } = await import('../src/app.js');")
# Or simpler: we can just mock app too, or import dynamically inside tests.
# Actually Jest + ESM requires the dynamic imports to be inside the test block or mocked properly.
