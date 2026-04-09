# How to Create a Stable Release on Packagist

This guide explains how to release a stable version of your scriptlog package on Packagist so developers can install it without using dev branches.

## Prerequisites

- Git installed on your computer
- A GitHub account with access to the scriptlog repository
- Terminal/command line access

---

## Step 1: Check Your Local Repository

Open your terminal and navigate to your local scriptlog folder:

```bash
cd /path/to/your/Scriptlog
```

Make sure you have the latest code:

```bash
git pull origin main
```

---

## Step 2: Create a Tag

Tags mark specific points in your repository's history as releases. Use semantic versioning (e.g., v1.0.0, v1.0.1, v1.1.0).

### For first release (v1.0.0):

```bash
git tag -a v1.0.0 -m "First stable release"
```

### For subsequent releases:

```bash
# Bug fixes (e.g., fixed bugs from v1.0.0)
git tag -a v1.0.1 -m "Bug fix release"

# New features (backward compatible)
git tag -a v1.1.0 -m "Added new features"

# Major changes (breaking changes)
git tag -a v2.0.0 -m "Major update with breaking changes"
```

**Tag naming rules:**
- Start with `v` (lowercase)
- Follow format: `vMAJOR.MINOR.PATCH`
- Examples: v1.0.0, v1.0.1, v2.0.0

---

## Step 3: Push Tag to GitHub

Push your tag to the remote repository:

```bash
git push origin v1.0.0
```

If you have multiple tags to push:

```bash
git push origin --tags
```

---

## Step 4: Verify on GitHub

1. Go to your GitHub repository: https://github.com/cakmoel/Scriptlog
2. Click on the **Releases** tab (near the top, next to Tags)
3. You should see your release listed there

---

## Step 5: Verify on Packagist

1. Go to: https://packagist.org/packages/cakmoel/scriptlog
2. Wait 2-5 minutes for Packagist to sync with GitHub
3. Check the **Versions** section - you should see v1.0.0 listed

If it doesn't appear immediately, you can manually trigger a sync:
1. On Packagist, go to your package page
2. Click **Trigger** button to force a resync

---

## Step 6: Test the Stable Release

Now developers can install your package without dev branches:

```bash
# Install specific version
composer require cakmoel/scriptlog:1.0.0

# Or use semver range
composer require cakmoel/scriptlog:^1.0
```

---

## Summary: Version Numbering

| Version | Type | Example | When to Use |
|---------|------|---------|-------------|
| MAJOR | Breaking changes | v2.0.0 | Incompatible API changes |
| MINOR | New features | v1.1.0 | Backward compatible features |
| PATCH | Bug fixes | v1.0.1 | Backward compatible bug fixes |

---

## Quick Reference Commands

```bash
# List all local tags
git tag -l

# Delete a local tag
git tag -d v1.0.0

# Delete a remote tag
git push origin --delete v1.0.0

# Push a new tag
git push origin v1.0.0

# Push all tags
git push origin --tags
```

---

## Troubleshooting

**Q: Packagist shows "No stable version"**
- A: Make sure you pushed a tag (not just a branch). Tags start with `v`.

**Q: Version not showing on Packagist**
- A: Wait 2-5 minutes. If still not showing, click "Trigger" on Packagist.

**Q: Want to change the release?**
- A: Tags are immutable. Create a new tag with an incremented version number.

---

That's it! Once you push a tag, Packagist will automatically recognize it as a stable version.