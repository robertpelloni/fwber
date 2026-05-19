with open('docs/TODO.md', 'r') as f:
    content = f.read()

# Make sure we check it off
content = content.replace("- [ ] Implement UI for `leaderboard.ts` route.", "- [x] Implement UI for `leaderboard.ts` route.")

with open('docs/TODO.md', 'w') as f:
    f.write(content)
