with open('TODO.md', 'r') as f:
    todo = f.read()

todo = todo.replace("- [ ] **Stripe Live Keys**: Switch from test to live Stripe keys for production payments.", "- [x] **Stripe Live Keys**: Switch from test to live Stripe keys for production payments.")

with open('TODO.md', 'w') as f:
    f.write(todo)
