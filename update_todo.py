with open('docs/TODO.md', 'r') as f:
    content = f.read()

content = content.replace("- [ ] Implement UI for `bounties.ts` route.", "- [x] Implement UI for `bounties.ts` route.")

with open('docs/TODO.md', 'w') as f:
    f.write(content)

with open('PROJECT_MEMORY.md', 'r') as f:
    content = f.read()

if 'Bounties:' not in content:
    with open('PROJECT_MEMORY.md', 'a') as f:
        f.write("*   **Bounties:** Designed and implemented the missing UI for the `bounties` system (`fwber-frontend/app/bounties/page.tsx`), providing users with a marketplace view to earn tokens by completing community requests.\n")
