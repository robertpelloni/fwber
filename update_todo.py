with open('docs/TODO.md', 'r') as f:
    content = f.read()

content = content.replace("- [ ] Implement UI for `topics.ts` route.", "- [x] Implement UI for `topics.ts` route.")

with open('docs/TODO.md', 'w') as f:
    f.write(content)

with open('PROJECT_MEMORY.md', 'r') as f:
    content = f.read()

if 'Topics:' not in content:
    with open('PROJECT_MEMORY.md', 'a') as f:
        f.write("*   **Topics:** Designed and implemented the missing UI for conversational topics (`fwber-frontend/app/topics/page.tsx`), giving users a way to explore trending local discussions, and wired it up via a Next.js proxy route (`fwber-frontend/app/api/topics/route.ts`).\n")
