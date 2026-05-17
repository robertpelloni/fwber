with open('docs/TODO.md', 'r') as f:
    content = f.read()

content = content.replace("- [ ] Implement UI for `referrals.ts` route.", "- [x] Implement UI for `referrals.ts` route.")

with open('docs/TODO.md', 'w') as f:
    f.write(content)

with open('PROJECT_MEMORY.md', 'r') as f:
    content = f.read()

if 'Referrals:' not in content:
    with open('PROJECT_MEMORY.md', 'a') as f:
        f.write("*   **Referrals:** Designed and implemented the missing UI for the `referrals` system (`fwber-frontend/app/referrals/page.tsx`), providing users with their unique invite link and a dashboard to track joined friends and earned token rewards, wired to a Next.js proxy route.\n")
