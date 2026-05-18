with open('docs/TODO.md', 'r') as f:
    content = f.read()

content = content.replace("- [ ] Implement UI for `burner-links.ts` route (Disposable profile links).", "- [x] Implement UI for `burner-links.ts` route (Disposable profile links).")

with open('docs/TODO.md', 'w') as f:
    f.write(content)

with open('PROJECT_MEMORY.md', 'r') as f:
    content = f.read()

if 'Burner Links:' not in content:
    with open('PROJECT_MEMORY.md', 'a') as f:
        f.write("*   **Burner Links:** Designed and implemented the missing UI for the `burner-links` system (`fwber-frontend/app/burner-links/page.tsx`), providing a dashboard to create, manage, and revoke disposable profile URLs, wired to a Next.js proxy route.\n")
