import re

with open('fwber-backend-ts/src/routes/quests.ts', 'r') as f:
    content = f.read()

# I see my regex left a duplicate block at the end. Let's fix that.
content = re.sub(r'      if \(\!uq \|\| uq\.status \!\=\= \'active\'\).*?\}\n  \}\);\n\nexport default router;', 'export default router;\n', content, flags=re.DOTALL)

with open('fwber-backend-ts/src/routes/quests.ts', 'w') as f:
    f.write(content)
