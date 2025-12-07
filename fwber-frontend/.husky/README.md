# Git Hooks with Husky

This directory contains Git hooks that automatically run checks before commits and pushes.

## Setup

To enable Git hooks in your local repository, run:

```bash
# Install dependencies (includes husky and lint-staged)
npm install

# Install husky hooks
npm install --save-dev husky lint-staged
npx husky install

# Make hook scripts executable (if needed)
chmod +x .husky/pre-commit
chmod +x .husky/pre-push
chmod +x .husky/commit-msg
```

Add this to package.json scripts if not already present:

```json
{
  "scripts": {
    "prepare": "husky install"
  }
}
```

## Available Hooks

### pre-commit

Runs before `git commit`:
- ✨ Lints and formats staged files (ESLint + Prettier)
- 🔍 Checks for large files (>500KB)
- 🔐 Scans for potential secrets
- 📝 Runs TypeScript type checking

**What it checks:**
- `.ts`, `.tsx`, `.js`, `.jsx` files: ESLint + Prettier + Type checking
- `.json`, `.md`, `.css` files: Prettier formatting
- All files: Size and secrets validation

**To skip** (use sparingly):
```bash
git commit --no-verify -m "your message"
```

### pre-push

Runs before `git push`:
- 📝 Full TypeScript type checking
- 🧪 Runs test suite (if tests exist)
- 🏗️ Verifies production build succeeds

**To skip** (use sparingly):
```bash
git push --no-verify
```

### commit-msg

Runs after entering commit message:
- 📋 Validates commit message format
- ✅ Enforces Conventional Commits specification

**Required format:**
```
type(scope?): subject

Examples:
  feat: add user profile page
  fix(auth): resolve token expiration bug
  docs: update API documentation
  refactor(components): simplify button logic
```

**Valid types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style/formatting
- `refactor`: Code refactoring
- `perf`: Performance improvement
- `test`: Test changes
- `chore`: Maintenance tasks
- `build`: Build system changes
- `ci`: CI/CD changes
- `revert`: Revert previous commit

## Configuration Files

### `.lintstagedrc.js`

Configures what lint-staged runs on staged files. You can customize:

```javascript
module.exports = {
  '**/*.{ts,tsx}': ['eslint --fix', 'prettier --write'],
  // Add more file patterns and commands
};
```

### `.prettierrc.js`

Configures Prettier formatting rules:

```javascript
module.exports = {
  printWidth: 100,
  singleQuote: true,
  // Customize formatting rules
};
```

## Troubleshooting

### Hooks not running

1. Ensure husky is installed: `npx husky install`
2. Check hook permissions: `chmod +x .husky/*`
3. Verify Git hooks are enabled: `git config core.hooksPath`

### Hooks failing

1. **Linting errors**: Fix the errors or use `--no-verify` (not recommended)
2. **Type errors**: Run `npm run type-check` to see all errors
3. **Build failing**: Run `npm run build` to debug

### Bypass hooks (emergency only)

```bash
# Skip pre-commit and commit-msg
git commit --no-verify -m "message"

# Skip pre-push
git push --no-verify
```

**Warning**: Only use `--no-verify` when absolutely necessary. Bypassing hooks can introduce bugs and inconsistencies.

## Disable hooks temporarily

```bash
# Disable all hooks
export HUSKY=0

# Your git commands here
git commit -m "message"

# Re-enable
unset HUSKY
```

## Custom hooks

To add new hooks:

1. Create a new file in `.husky/`
2. Make it executable: `chmod +x .husky/your-hook`
3. Add your checks
4. Document it here

## Benefits

✅ Consistent code style across team
✅ Catch errors before they reach CI/CD
✅ Prevent committing secrets or large files
✅ Enforce commit message conventions
✅ Improve code quality automatically
✅ Faster feedback loop (catch issues locally)

## Related Documentation

- [Husky Documentation](https://typicode.github.io/husky/)
- [Lint-staged Documentation](https://github.com/okonet/lint-staged)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Prettier Documentation](https://prettier.io/)
