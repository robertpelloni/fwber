with open('docs/TODO.md', 'r') as f:
    content = f.read()

content = content.replace("- [ ] Implement background worker/cron job to actually fetch and parse contacts using the stored OAuth tokens.", "- [x] Implement background worker/cron job to actually fetch and parse contacts using the stored OAuth tokens.")

with open('docs/TODO.md', 'w') as f:
    f.write(content)
