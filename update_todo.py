with open('docs/TODO.md', 'r') as f:
    content = f.read()

content = content.replace("- [ ] Implement UI for `reports.ts` route (Moderation reporting UX).", "- [x] Implement UI for `reports.ts` route (Moderation reporting UX).")

with open('docs/TODO.md', 'w') as f:
    f.write(content)

with open('PROJECT_MEMORY.md', 'r') as f:
    content = f.read()

if 'Reports:' not in content:
    with open('PROJECT_MEMORY.md', 'a') as f:
        f.write("*   **Reports:** Designed and implemented the missing UI for the `reports` system (`fwber-frontend/app/reports/page.tsx`), providing a user safety dashboard to track filed moderation requests.\n")
