with open('docs/TODO.md', 'r') as f:
    content = f.read()

content = content.replace("- [ ] Implement UI for `bulletin-boards.ts` route.", "- [x] Implement UI for `bulletin-boards.ts` route.")

with open('docs/TODO.md', 'w') as f:
    f.write(content)
