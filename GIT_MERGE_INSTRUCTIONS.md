# Git Monorepo Migration Guide

This guide explains how to merge your `fwber-frontend` and `fwber-backend` repositories into the main `fwber` repository while preserving their commit history.

## Prerequisites
- Ensure you have a clean working directory (commit or stash changes).
- Backup your repositories before proceeding.

## Instructions

### 1. Remove Existing Submodules
If you currently have them added as submodules, remove them first:

```bash
# Remove submodule entries
git rm fwber-frontend
git rm fwber-backend

# Remove .gitmodules if empty
rm .gitmodules

# Commit the removal
git commit -m "chore: Remove submodules in preparation for monorepo merge"
```

### 2. Merge Backend Repository

```bash
# Add backend remote
git remote add -f backend <path-to-backend-repo-or-url>
# Example: git remote add -f backend ../fwber-backend

# Merge backend master/main branch
git merge backend/main --allow-unrelated-histories -m "merge: Merge backend repository"

# Move backend files to a subdirectory
mkdir fwber-backend
# Move all files except .git and the new directory itself
# (On Windows PowerShell, this might be tricky, use Git Bash or move manually then git add)
# Standard bash command:
git mv !(fwber-backend|.git) fwber-backend/

# Commit the move
git commit -m "chore: Move backend files to fwber-backend directory"
```

### 3. Merge Frontend Repository

```bash
# Add frontend remote
git remote add -f frontend <path-to-frontend-repo-or-url>

# Merge frontend master/main branch
git merge frontend/main --allow-unrelated-histories -m "merge: Merge frontend repository"

# Move frontend files to a subdirectory
mkdir fwber-frontend
git mv !(fwber-frontend|fwber-backend|.git) fwber-frontend/

# Commit the move
git commit -m "chore: Move frontend files to fwber-frontend directory"
```

### 4. Cleanup

```bash
# Remove remotes
git remote remove backend
git remote remove frontend

# Push changes
git push origin main
```

## Alternative: Using `git-subtree` (Simpler)

If you don't need to preserve the exact commit hashes (just the file history), `git subtree` is often easier:

```bash
git subtree add --prefix=fwber-backend <backend-repo-url> main
git subtree add --prefix=fwber-frontend <frontend-repo-url> main
```

This command fetches the history and puts it directly into the specified prefix folder in one step.
