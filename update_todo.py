with open('docs/TODO.md', 'r') as f:
    content = f.read()

content = content.replace("- [ ] Create frontend view to display synced contacts and mutual friends.", "- [x] Create frontend view to display synced contacts and mutual friends.")

with open('docs/TODO.md', 'w') as f:
    f.write(content)
