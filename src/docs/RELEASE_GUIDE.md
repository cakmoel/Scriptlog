# How to Create a Stable Release on Packagist

This guide explains how to release a stable version of your scriptlog package on Packagist and how to avoid the **upstream re-tag blocked** error.

> **CRITICAL:** Once a stable version is published on Packagist, its source reference is **permanently locked**. You must never delete, move, or rewrite a tag that has already been pushed to GitHub, even locally. Doing so will trigger a re-tag warning on Packagist, and the version will be frozen to its original snapshot.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Version Immutability](#version-immutability)
3. [Step-by-Step Release Process](#step-by-step-release-process)
4. [What to Do If You Need to Change a Release](#what-to-do-if-you-need-to-change-a-release)
5. [Recovering from a Re-Tag](#recovering-from-a-re-tag)
6. [Quick Reference Commands](#quick-reference-commands)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- Git installed on your computer
- A GitHub account with access to the scriptlog repository
- Terminal/command line access

---

## Version Immutability

### What is Version Immutability?

Once a stable (non-dev) version of a package is published on Packagist, its source and dist reference are **locked**. The Packagist crawler will not update them, even if the upstream tag is moved or rewritten in the git repository.

Dev branches (versions matching `dev-*` or `*-dev`) continue to track their branches as they always have — this rule applies **only to stable releases**.

### Why?

A stable release is a contract: every downstream user who installed `vendor/pkg:1.2.3` expects to receive the **exact same code** as everyone else, today and a year from now. Allowing the source reference of a published version to change opens the door to:

- **Supply-chain attacks** — e.g., taking over a tag after release
- **Maintainer footguns** — e.g., a silent re-tag introducing a regression that nobody can audit

Making stable versions immutable closes both.

### What You Will See If a Re-Tag Is Detected

If Packagist detects that a tag was moved after its initial crawl:

1. A warning is shown in the update log on the package page
2. An email is sent to the package maintainers explaining the previous and attempted reference
3. An audit record is written
4. The version page displays a badge: **"Upstream re-tag blocked"** — Packagist's stored snapshot may no longer match what is currently in git

The audit/email/badge fires **at most once per attempted reference** — subsequent crawls that observe the same diverged reference do not re-send.

---

## Step-by-Step Release Process

### Step 1: Check Your Local Repository

```bash
cd /path/to/your/Scriptlog
git pull origin main
```

Ensure the working tree is clean with no uncommitted changes:

```bash
git status
```

### Step 2: Prepare the Release

1. Update `CHANGELOG.md` with the new version entry
2. Commit all release-related changes to the `develop` branch:
   ```bash
   git add CHANGELOG.md
   git commit -m "docs: update CHANGELOG.md for vX.Y.Z release"
   ```
3. Merge `develop` into `main`:
   ```bash
   git checkout main
   git merge develop
   git push origin main
   ```

### Step 3: Create a Tag — ONCE AND ONLY ONCE

**Rules for tagging:**
- Tag **only after** all commits are finalized
- Tag **only from `main`** (never from `develop`)
- Tag **only once** — never delete and recreate a tag
- Use annotated tags (`-a`), not lightweight tags

```bash
# Ensure you are on main with the latest code
git checkout main
git pull origin main

# Create an annotated tag — DO THIS ONLY ONCE
git tag -a v1.2.3 -m "Brief description of the release"
```

**Tag naming rules:**
- Start with `v` (lowercase)
- Format: `vMAJOR.MINOR.PATCH`
- Examples: `v1.0.0`, `v1.0.1`, `v2.0.0`

| Version | Type | Example | When to Use |
|---------|------|---------|-------------|
| MAJOR | Breaking changes | `v2.0.0` | Incompatible API changes |
| MINOR | New features | `v1.1.0` | Backward compatible features |
| PATCH | Bug fixes | `v1.0.1` | Backward compatible bug fixes |

### Step 4: Push the Tag to GitHub

```bash
git push origin v1.2.3
```

> **WARNING:** Never push `--tags` as a bulk operation. Push tags one at a time so you can verify each one. Bulk-pushing may accidentally push a tag you intended to keep local.

### Step 5: Verify on GitHub

1. Go to: https://github.com/cakmoel/Scriptlog
2. Click the **Releases** tab
3. Confirm the new tag appears

### Step 6: Create a GitHub Release

Create a GitHub Release from the tag with the changelog notes:

```bash
gh release create v1.2.3 --title "v1.2.3" --notes "## [1.2.3] - YYYY-MM-DD

### Added
- Description of new features

### Fixed
- Description of bug fixes"
```

### Step 7: Verify on Packagist

1. Go to: https://packagist.org/packages/cakmoel/scriptlog
2. Wait 2-5 minutes for Packagist to sync with GitHub
3. Check the **Versions** section — confirm the new version appears **without** any "re-tag blocked" badge

If it doesn't appear immediately, click **Trigger** to force a resync.

---

## What to Do If You Need to Change a Release

If you discover a problem after a tag has been pushed:

### Correct approach: Create a new tag

**Never** delete and recreate the same tag. Instead, create a new version:

```bash
# BUG FIX — If 1.2.3 had a regression:
git tag -a v1.2.4 -m "Bug fix release"
git push origin v1.2.4

# Or use a four-part version for minimal-impact fixes:
git tag -a v1.2.3.1 -m "Hotfix for regression in 1.2.3"
git push origin v1.2.3.1
```

This creates a **new** stable version that Packagist will crawl cleanly. The old version remains frozen with its original snapshot — that's by design.

### What NOT to do

```bash
# ❌ NEVER DO THIS:
git tag -d v1.2.3              # Delete local tag
git push origin --delete v1.2.3 # Delete remote tag
git tag -a v1.2.3 -m "..."     # Recreate the same tag
git push origin v1.2.3          # Push the moved tag — RE-TAG BLOCKED
```

This sequence will trigger the "upstream re-tag blocked" warning on Packagist. The version will be permanently frozen to the original reference, and the new commit will never be associated with `v1.2.3`.

---

## Recovering from a Re-Tag

If you have already triggered the "upstream re-tag blocked" warning:

### On Packagist

1. The version with the warning is permanently locked to its original snapshot
2. You can **soft-delete** it from the package page (hidden from Composer metadata, still listed grayed out)
3. Soft-deleted versions can be recovered by the maintainer at any time
4. Administrator takedowns (for malware, abuse, or legal reasons) cannot be recovered

### On GitHub

1. The tag on GitHub can remain — it points to whatever commit you chose
2. **Delete the GitHub Release** associated with the bad tag:
   ```bash
   gh release delete v1.2.3
   ```
3. Create a **new tag** with an incremented version and push it:
   ```bash
   git tag -a v1.2.4 -m "Clean release replacing v1.2.3"
   git push origin v1.2.4
   ```

### Moving Forward

The new tag (e.g., `v1.2.4`) will be crawled cleanly by Packagist. Always use the new version going forward.

---

## Quick Reference Commands

```bash
# List all local tags
git tag -l

# Check what commit a tag points to
git log --oneline v1.2.3 -1

# Push a new tag (correct — do this once per tag)
git push origin v1.2.3

# Create a GitHub Release from a tag
gh release create v1.2.3 --title "v1.2.3" --notes "Release notes here"

# Delete a GitHub Release (safe — does not affect the tag)
gh release delete v1.2.3
```

### Commands to AVOID

```bash
# ❌ Never push all tags at once — may push unintended tags
git push origin --tags

# ❌ Never delete a remote tag after a stable release is published
git push origin --delete v1.2.3

# ❌ Never delete and recreate a tag
git tag -d v1.2.3
git tag -a v1.2.3 -m "..."

# ❌ Never force-push a tag
git push origin v1.2.3 --force
```

---

## Troubleshooting

**Q: Packagist shows "No stable version"**
- A: Make sure you pushed a tag (not just a branch). Tags start with `v`.

**Q: Version not showing on Packagist**
- A: Wait 2-5 minutes. If still not showing, click **Trigger** on Packagist.

**Q: Packagist shows "Upstream re-tag blocked"**
- A: You moved a tag after it was first crawled. The version is permanently locked. Create a **new tag** with an incremented version number. See [Recovering from a Re-Tag](#recovering-from-a-re-tag).

**Q: I need to fix a bug in the latest release**
- A: Create a new patch version (e.g., `v1.2.4`). Never attempt to rewrite the existing tag.

**Q: What if I accidentally pushed the wrong tag?**
- A: If Packagist has not yet crawled it (within ~2 minutes of pushing), immediately delete the remote tag and recreate it at the correct commit. Once Packagist has crawled it, the reference is locked — you must use a new version.

---

## Summary

| Rule | Why |
|------|-----|
| **Tag once, from `main`, after all commits** | Eliminates the need to move a tag |
| **Never delete a remote tag after pushing** | Prevents the re-tag lock on Packagist |
| **Never recreate a tag that was already pushed** | Packagist will reject the new reference |
| **Use a new version for every fix** | Keeps Packagist in sync with your VCS |

Remember: A stable release is a **contract** with your users. Once published, it must never change. Plan your releases carefully, and when something goes wrong, ship a new version — don't rewrite history.
