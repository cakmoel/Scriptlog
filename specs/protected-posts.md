# Password-Protected Posts — End-to-End Test Specification

> **Application**: Scriptlog (PHP 7.4+ / MariaDB / Bootstrap / jQuery)
> **Spec Version**: 1.0
> **Feature Owner**: Blogware Team
> **Bootstrap**: `e2e/seed.spec.ts`

---

## Table of Contents

1. [Overview](#1-overview)
2. [Datasets & Fixtures](#9-datasets--fixtures)
3. [Frontend — Protected Post Display](#2-frontend--protected-post-display)
4. [Frontend — AJAX Unlock (Correct Password)](#3-frontend--ajax-unlock-correct-password)
5. [Frontend — AJAX Unlock (Incorrect Password)](#4-frontend--ajax-unlock-incorrect-password)
6. [Frontend — Rate Limiting](#5-frontend--rate-limiting)
7. [Frontend — Already Unlocked (Session)](#6-frontend--already-unlocked-session)
8. [Frontend — Concurrent Tabs & Session Persistence](#13-frontend--concurrent-tabs--session-persistence)
9. [Frontend — CSRF & Direct API Access](#7-frontend--csrf--direct-api-access)
10. [Admin — Editing a Protected Post](#8-admin--editing-a-protected-post)
11. [Admin — Creating a New Protected Post](#10-admin--creating-a-new-protected-post)
12. [Admin — Changing Password on Existing Protected Post](#11-admin--changing-password-on-existing-protected-post)
13. [Admin — Removing Protection from a Post](#12-admin--removing-protection-from-a-post)
14. [API — Verify Endpoint](#14-api--verify-endpoint)
15. [API — Security & Error Handling](#15-api--security--error-handling)
16. [Security Expectations](#16-security-expectations)
17. [Accessibility](#17-accessibility)
18. [Responsive Behaviour](#18-responsive-behaviour)
19. [Browser Compatibility](#19-browser-compatibility)
20. [Failure Scenarios](#20-failure-scenarios)
21. [Recovery Behaviour](#21-recovery-behaviour)
22. [Screenshots to Capture](#22-screenshots-to-capture)
23. [Console & Network Expectations](#23-console--network-expectations)
24. [Test Scenarios](#24-test-scenarios)

---

## 1. Overview

### Purpose

Allow authors to protect individual blog posts with a password. Visitors must enter the correct password (verified server-side via bcrypt) to view the post content. Content is encrypted at rest using AES-256-CBC. The unlock happens via AJAX without page reload.

### Key Files Under Test

| Layer | File | Role |
|-------|------|------|
| Theme Template | `public/themes/blog/single.php` | Renders password form or decrypted content |
| Theme JS | `public/themes/blog/assets/js/unlock-post.js` | AJAX form handler |
| Theme Footer | `public/themes/blog/footer.php` | Loads `unlock-post.min.js` |
| Theme Header | `public/themes/blog/header.php` | Defines `scriptlog_vars.api_url` |
| API Controller | `lib/controller/api/ProtectedPostApiController.php` | `unlock()` and `verify()` endpoints |
| API Router | `api/index.php:184-185` | Routes for `/unlock` and `/verify` |
| Utility | `lib/utility/protected-post.php` | Encryption, rate limiting, password checking |
| Utility | `lib/utility/encrypt-decrypt.php` | AES-256-CBC encrypt/decrypt |
| Post Controller | `lib/controller/PostController.php` | Admin edit: decrypts for Summernote |
| App Service | `lib/service/PostApplicationService.php` | `setProtectedPostContent()` — 3-branch re-encryption |
| Post Service | `lib/service/PostService.php` | `setProtected()`, `setPassPhrase()` |
| Post DAO | `lib/dao/PostDao.php` | `updatePost()` — conditional password fields |
| FrontHelper | `lib/core/FrontHelper.php` | `grabPreparedFrontPostById()` includes protected posts |
| Dispatcher | `lib/core/Dispatcher.php` | `validateSinglePost()` — allows protected posts through |

### How It Works (Summary)

1. **Create/Edit**: Admin sets visibility to `protected` and supplies a password. `protect_post()` encrypts content with AES-256-CBC using a SHA-256 passphrase derived from `app_key() + password`. The bcrypt hash of the password is stored in `post_password`; the SHA-256 passphrase in `passphrase`.
2. **Display**: `single.php` checks `post_visibility === 'protected'`. If the post is not already unlocked in `$_SESSION['unlocked_posts']`, a password form is shown instead of content.
3. **Unlock**: AJAX POST to `/api/v1/posts/{id}/unlock` with `{password}`. Server verifies bcrypt, decrypts content, strips inline styles/event handlers, returns sanitized HTML. JS fades out the form and fades in the content.
4. **Rate Limit**: Max 5 failed attempts per 15 minutes per IP per post (file-based tracking).
5. **Admin Edit**: `decrypt_post_admin()` decrypts without password verification.

### URL Formats

| Format | Example |
|--------|---------|
| SEO-friendly (permalinks enabled) | `/post/3/cicero` |
| Query string (permalinks disabled) | `?p=3` |

The API endpoint is always `/api/v1/posts/{id}/unlock` (independent of permalink setting).

---

## 9. Datasets & Fixtures

### 9.1 Pre-seeded Protected Post

| Field | Value |
|-------|-------|
| ID | 3 |
| Title | "Cicero" |
| Slug | "cicero" |
| Visibility | `protected` |
| Password (plaintext) | `Bac4D0nG(*)#` |
| Password (bcrypt) | `$2y$10$...` (stored in `post_password`) |
| Passphrase | SHA-256 hash of `app_key() + password` |
| Content encrypted | Yes (AES-256-CBC) |

### 9.2 Pre-seeded Public Post

| Field | Value |
|-------|-------|
| ID | 1 |
| Title | (any public post) |
| Visibility | `public` |
| Password | NULL |

### 9.3 Pre-seeded Admin Account

| Field | Value |
|-------|-------|
| Username | `administrator` |
| Password | `4dMin(*)^` |
| Role | `administrator` |

### 9.4 Weak Password for Validation Tests

| Password | Strength |
|----------|----------|
| `Ab1!` | Fails (< 8 chars) |
| `abcdefgh` | Fails (no uppercase, no digit, no special) |
| `ABCDEFGH1!` | Fails (no lowercase) |
| `Abcdefgh!` | Fails (no digit) |
| `Abcdefgh1` | Fails (no special char) |
| `SecurePass123!` | Passes (all criteria met) |

---

## 2. Frontend — Protected Post Display

### URL

| Permalinks Enabled | Permalinks Disabled |
|--------------------|---------------------|
| `/post/3/cicero` | `/?p=3` |

### Preconditions

- The application is installed and configured
- A protected post exists (ID=3, password=`Bac4D0nG(*)#`)
- The user is **not logged in** to the admin panel
- The user has **not previously unlocked** this post in their session

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to protected post URL | Direct URL entry, click link from homepage/category, browser refresh | Page loads with HTTP 200; password form is displayed instead of post content |
| View page title | — | Page `<title>` shows the post title |
| Inspect DOM for content | — | `post-body` contains `password-protected-post` div; no raw encrypted content visible |
| View network tab | — | No decrypted content in HTML source |
| Right-click → View Page Source | — | Only the password form markup; encrypted content is NOT in the source |
| Navigate back/forward | Browser back/forward buttons | Protected post still shows password form (session not yet set) |
| Open in incognito/private window | — | Same behaviour — password form shown |

### Expected Page Structure

```html
<div class="post-body">
  <div class="password-protected-post text-center py-5" id="password-protected-3">
    <div class="lock-icon mb-3">
      <i class="fa fa-lock fa-3x text-muted"></i>
    </div>
    <h3 class="h4 mb-3"><!-- translated: "Password Protected" --></h3>
    <p class="text-muted mb-4"><!-- translated: "This post is password protected..." --></p>
    <form method="post" class="password-form-inline d-inline-flex align-items-start gap-2 unlock-post-form" data-post-id="3">
      <div class="form-group">
        <input type="password" class="form-control post-password-input" name="post_password"
               placeholder="Password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary unlock-post-btn">Unlock</button>
    </form>
    <div class="unlock-post-error text-danger mt-2" style="display: none;"></div>
    <div class="unlock-post-loading" style="display: none;">
      <i class="fa fa-spinner fa-spin"></i> Loading...
    </div>
  </div>
  <div class="password-protected-content" id="unlocked-content-3" style="display: none;"></div>
</div>
```

### UI Components

| Component | Selector | Description |
|-----------|----------|-------------|
| Lock icon | `.fa-lock` | FontAwesome lock icon |
| Heading | `h3.h4.mb-3` | "Password Protected" (translated via `t('visibility.password')`) |
| Description | `p.text-muted` | "This post is password protected..." (translated via `t('protected.post.description')`) |
| Password input | `input.post-password-input[type="password"]` | Password field with `autocomplete="current-password"` and `required` |
| Unlock button | `button.unlock-post-btn` | Submit button with translated label |
| Error container | `div.unlock-post-error` | Hidden by default; shown on error |
| Loading indicator | `div.unlock-post-loading` | Hidden by default; shown during AJAX |
| Content container | `div.password-protected-content#unlocked-content-3` | Hidden by default; populated on success |
| Featured image | `.post-thumbnal` | Featured image is still shown even for protected posts |
| Post title | `h1` | Post title is visible even for protected posts |
| Post meta | `.post-meta` | Category, author, date, comment count are visible |
| Post tags | `.post-tags` | Tags are visible even for protected posts |
| Previous/Next nav | `.posts-nav` | Navigation links are visible |
| Comment form | `.comment-form-wrap` | Only shown if `comment_status === 'open'` |

### Validation Rules

| Rule | Detail |
|------|--------|
| Encrypted content must NOT be in HTML source | The base64-encoded ciphertext must never be rendered in the DOM |
| Password form must be visible for non-unlocked visitors | CSS `display` must not hide it |
| Content container must be hidden | CSS `display: none` on `#unlocked-content-{id}` |
| Error container must be hidden | CSS `display: none` on `.unlock-post-error` |
| Loading container must be hidden | CSS `display: none` on `.unlock-post-loading` |
| Post title, featured image, meta, tags, navigation must be visible | These are not encrypted |

### Network Behaviour

| Request | Method | Status | Notes |
|---------|--------|--------|-------|
| Page load | GET | 200 | Full HTML page |
| No API call on page load | — | — | Unlock JS is loaded but idle |

### Accessibility

| Check | Expectation |
|-------|-------------|
| Password field has `autocomplete="current-password"` | Browser can suggest saved passwords |
| Password field has `required` attribute | Browser validation |
| Password field has `placeholder` | Visual hint |
| Unlock button is a `<button type="submit">` | Keyboard submittable |
| Error container is announced | Use `aria-live="polite"` or `role="alert"` (should be verified/added) |
| Loading indicator uses `fa-spinner fa-spin` | Visual feedback |
| Focus management after unlock | Focus should move to content area |

### Responsive Behaviour

| Breakpoint | Behaviour |
|------------|-----------|
| Desktop (≥992px) | Form inline with `d-inline-flex align-items-start gap-2` |
| Tablet (768-991px) | Form remains inline |
| Mobile (<768px) | Form should stack vertically; input full width |

---

## 3. Frontend — AJAX Unlock (Correct Password)

### URL

`/post/3/cicero` (or `/?p=3`)

### Preconditions

- Protected post exists (ID=3, password=`Bac4D0nG(*)#`)
- User is on the protected post page seeing the password form
- User has NOT exceeded rate limit

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Click password field | — | Field receives focus; placeholder text visible |
| Type password | `Bac4D0nG(*)#` | Characters masked |
| Click "Unlock" button | — | Form submits via AJAX (no page reload) |
| Observe loading state | — | Button disabled; loading spinner shown; error hidden |
| Wait for response | — | Password form fades out; content fades in |
| Read decrypted content | — | Post content visible with proper formatting |

### Detailed Flow

1. User types `Bac4D0nG(*)#` into password input
2. User clicks "Unlock" button (or presses Enter)
3. JavaScript prevents default form submission
4. Client-side validation: if password empty, show "Please enter a password" error immediately (no AJAX)
5. Button is disabled (`prop('disabled', true)`)
6. Loading spinner shown (`$loading.show()`)
7. Error message hidden (`$error.hide()`)
8. AJAX POST to `/api/v1/posts/3/unlock`
   - `Content-Type: application/json`
   - Body: `{"password": "Bac4D0nG(*)#"}`
9. Server response (200):
   ```json
   {
     "success": true,
     "status": 200,
     "data": {
       "content": "<p>Decrypted HTML content...</p>"
     }
   }
   ```
10. Password form fades out (300ms)
11. Content injected into `#unlocked-content-3` and fades in (300ms)
12. Session: `$_SESSION['unlocked_posts'][3]` set to the password

### UI Components After Unlock

| Component | State |
|-----------|-------|
| Password form | Hidden (faded out) |
| Content container | Visible with decrypted HTML |
| Error container | Hidden |
| Loading indicator | Hidden |
| All other elements | Same as before (title, meta, tags, nav) |

### Validation Rules

| Rule | Detail |
|------|--------|
| Content must be sanitized | No `<style>`, `onclick`, `onerror`, `onload`, `onmouseover`, `onfocus`, `onblur`, `onchange`, `onsubmit`, `onkeydown`, `onkeyup`, `onkeypress` attributes |
| Inline styles stripped | `style="..."` attributes removed from all HTML tags |
| HTML entities double-decoded | Both `&amp;lt;` and `&lt;` must be decoded to `<` |
| Safe HTML preserved | `<p>`, `<strong>`, `<em>`, `<a>`, `<img>`, `<ul>`, `<ol>`, `<li>`, `<h1>-<h6>`, `<blockquote>`, `<code>`, `<pre>` allowed |
| No raw encrypted content in DOM | Only decrypted plaintext |

### Network Behaviour

| Request | Method | URL | Status | Response |
|---------|--------|-----|--------|----------|
| Unlock AJAX | POST | `/api/v1/posts/3/unlock` | 200 | `{"success":true,"status":200,"data":{"content":"..."}}` |

Headers:
- `Content-Type: application/json`
- `Accept: application/json`

### Accessibility

| Check | Expectation |
|-------|-------------|
| Focus after unlock | Focus should move to `#unlocked-content-3` or first heading inside it |
| ARIA live region | Content container should have `aria-live="polite"` |
| Loading state announced | Screen reader should know content is loading |

---

## 4. Frontend — AJAX Unlock (Incorrect Password)

### Preconditions

- Protected post exists (ID=3, password=`Bac4D0nG(*)#`)
- User is on the protected post page seeing the password form
- User has NOT exceeded rate limit

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Type wrong password | `wrongpassword` | Characters masked |
| Click "Unlock" button | — | AJAX request sent |
| Loading state | — | Button disabled, spinner shown |
| Response received | — | Error message shown in red; form remains visible |

### Expected Behaviour

1. AJAX POST to `/api/v1/posts/3/unlock` with `{"password": "wrongpassword"}`
2. Server returns 401:
   ```json
   {
     "success": false,
     "status": 401,
     "message": "Incorrect password"
   }
   ```
3. JavaScript shows error: `.unlock-post-error` displays "Incorrect password. Please try again."
4. Button re-enabled
5. Loading spinner hidden
6. Password form remains visible
7. Failed attempt logged for rate limiting

### Validation Rules

| Rule | Detail |
|------|--------|
| Error shown after wrong password | Red text below form |
| Form remains for retry | Input not cleared (or should it be cleared?) |
| Rate limit counter incremented | File-based tracking updated |

### Network Behaviour

| Request | Method | URL | Status | Response |
|---------|--------|-----|--------|----------|
| Unlock AJAX | POST | `/api/v1/posts/3/unlock` | 401 | `{"success":false,"status":401,"message":"Incorrect password"}` |

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 401 | Incorrect password |
| 400 | Missing password or post ID |
| 429 | Rate limited |
| 500 | Decryption failure, missing functions |
| 200 | Success |

---

## 5. Frontend — Rate Limiting

### Preconditions

- Protected post exists (ID=3)
- User is not logged in
- Rate limit files directory is writable (`public/log/unlock_attempts/`)

### User Actions

| # | Action | Input | Expected Behaviour |
|---|--------|-------|-------------------|
| 1 | Submit wrong password | `wrong1` | 401 — "Incorrect password" |
| 2 | Submit wrong password | `wrong2` | 401 |
| 3 | Submit wrong password | `wrong3` | 401 |
| 4 | Submit wrong password | `wrong4` | 401 |
| 5 | Submit wrong password | `wrong5` | 401 |
| 6 | Submit wrong password | `wrong6` | 429 — "Too many failed attempts. Please try again later." |
| 7 | Submit correct password | `Bac4D0nG(*)#` | 429 — still rate limited (attempts not cleared) |

### Expected Behaviour After Rate Limit

1. 6th failed attempt returns 429
2. Error message displayed: "Too many failed attempts. Please try again later."
3. Button re-enabled, spinner hidden
4. Even correct password is rejected while rate limited
5. Rate limit auto-resets after 15 minutes (900 seconds)
6. Rate limit is per-post per-IP: different post or different IP unaffected

### Validation Rules

| Rule | Detail |
|------|--------|
| Max 5 failed attempts per 15 min | 6th attempt returns 429 |
| Rate limit is per post | Different post ID uses different rate counter |
| Rate limit is per IP | Different IP addresses have separate counters |
| Rate limit resets after 900 seconds | Old attempts (>900s) are pruned |
| Successful unlock clears attempts | `clear_failed_unlock_attempts()` called on success |

### Rate Limit File Format

```
/public/log/unlock_attempts/{md5(ip + '_' + postId)}.json
→ [timestamp1, timestamp2, ...]
```

### Network Behaviour (6th failed attempt)

| Request | Method | URL | Status | Response |
|---------|--------|-----|--------|----------|
| Unlock AJAX | POST | `/api/v1/posts/3/unlock` | 429 | `{"success":false,"status":429,"message":"Too many failed attempts. Please try again later."}` |

---

## 6. Frontend — Already Unlocked (Session)

### Preconditions

- Protected post exists (ID=3)
- User successfully unlocked the post in the current session (or has `$_SESSION['unlocked_posts'][3]` set)

### User Actions

| Action | Expected Behaviour |
|--------|-------------------|
| Navigate to `/post/3/cicero` | Content is shown immediately; no password form |
| Refresh the page | Content still shown (session persists) |
| Navigate away and come back | Content shown (same session) |
| Open in new tab | Content shown (same session cookie) |
| Open in incognito window | Password form shown (no session) |
| Close all tabs and reopen | Depends on session lifetime; if session expired, password form shown |

### Expected Behaviour

- `single.php` checks `isset($_SESSION['unlocked_posts'][$postId])`
- If true, content is decrypted server-side and rendered
- No AJAX call needed
- Content is sanitized (same as AJAX path)

---

## 13. Frontend — Concurrent Tabs & Session Persistence

### Preconditions

- Protected post exists (ID=3)
- User is on the protected post page (not yet unlocked)

### User Actions

| # | Action | Tab | Expected Behaviour |
|---|--------|-----|-------------------|
| 1 | Open same post in Tab 2 | 2 | Password form shown in both tabs |
| 2 | Unlock in Tab 1 with correct password | 1 | Content shown in Tab 1 |
| 3 | Refresh Tab 2 | 2 | Content shown (session-based, same cookie) |
| 4 | Open different protected post (ID=4) in Tab 1 | 1 | Password form shown (different post) |
| 5 | Unlock Tab 1 with correct password for ID=4 | 1 | Content shown for ID=4 |

### Expected Behaviour

- Session unlock state is shared across tabs (same PHP session cookie)
- Unlock is per-post: unlocking one post does NOT unlock all protected posts
- Refreshing any tab after unlock in another tab shows content

---

## 7. Frontend — CSRF & Direct API Access

### Preconditions

- Protected post exists (ID=3)

### User Actions

| Action | Detail | Expected Behaviour |
|--------|--------|-------------------|
| POST to `/api/v1/posts/3/unlock` without body | `curl -X POST ...` | 400 — "Password is required" |
| POST to `/api/v1/posts/3/unlock` with empty password | `{"password": ""}` | 400 — "Password is required" |
| POST to `/api/v1/posts/9999/unlock` | Non-existent post ID | 401 — "Incorrect password" (no post, no hash → fails verification) |
| POST to `/api/v1/posts/abc/unlock` | Non-numeric ID | 400 — "Post ID is required" (cast to 0) |
| POST to `/api/v1/posts/3/unlock` with malformed JSON | `not json` | 400 — request body parsing fails |
| Direct GET to `/api/v1/posts/3/unlock` | Wrong method | 405 Method Not Allowed |
| Direct GET to `/api/v1/posts/3/verify` | Wrong method | 405 Method Not Allowed |

### Network Behaviour

| Test | Method | URL | Status | Response |
|------|--------|-----|--------|----------|
| No body | POST | `/api/v1/posts/3/unlock` | 400 | `{"success":false,"status":400,"message":"Password is required"}` |
| Empty password | POST | `/api/v1/posts/3/unlock` | 400 | `{"success":false,"status":400,"message":"Password is required"}` |
| Non-existent ID | POST | `/api/v1/posts/9999/unlock` | 401 | `{"success":false,"status":401,"message":"Incorrect password"}` |
| Wrong method | GET | `/api/v1/posts/3/unlock` | 405 | Method not allowed |

---

## 8. Admin — Editing a Protected Post

### URL

`admin/index.php?load=posts&action=editPost&Id=3`

### Preconditions

- User is logged in as **administrator** (`administrator` / `4dMin(*)^`)
- Protected post exists (ID=3)

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to Edit Post | — | Post loads with decrypted content in Summernote editor |
| Inspect visibility dropdown | — | "Protected" is selected |
| Inspect password field | — | Password field shows bcrypt hash value (encoded) |
| View content in Summernote | — | Content is decrypted and editable |
| Modify content | Add/change text | Summernote editor allows editing |
| Change password | New password | Password field is editable |
| Click "Update" button | — | Post saved; content re-encrypted with new or existing password |
| Change visibility to "Public" | Select "Public" | Post saved as public; content stored in plaintext |
| Change visibility to "Private" | Select "Private" | Post saved as private; content stored in plaintext |

### Detailed Admin Decryption Flow

1. `PostController::update()` renders edit form
2. `renderEditPostForm()` checks `$data_post['post_visibility'] == 'protected'`
3. Calls `decrypt_post_admin($getPost['ID'])` — NO password required
4. `decrypt_post_admin()` calls `grab_post_protected()` → fetches post from DB
5. If `visibility === 'protected'` and `passphrase` is set → decrypts content
6. Decrypted content set as `$postContent` in view
7. Summernote editor shows decrypted content for editing

### UI Components

| Component | Location | Note |
|-----------|----------|------|
| Visibility dropdown | Right sidebar | Shows "Protected" selected |
| Password input | Below visibility dropdown | Shows bcrypt hash; editable |
| Content editor | Main area | Summernote WYSIWYG with decrypted content |
| Update button | Bottom right | Submits form |

### Update Branches (PostApplicationService::setProtectedPostContent)

| Branch | Condition | Behaviour |
|--------|-----------|-----------|
| 1 | `visibility=protected` + new password | `protect_post()` encrypts with new passphrase |
| 2 | `visibility=protected` + no password | `encrypt()` re-encrypts with existing `passphrase` from DB |
| 3 | `visibility=public/private` | Store plain content (no encryption) |

### Security

| Check | Expectation |
|-------|-------------|
| CSRF token | Form has hidden `csrfToken` input; validated server-side |
| Auth check | `userAccessControl(ActionConst::POSTS)` before edit |
| Post existence | `checkPostId()` before proceeding |

---

## 10. Admin — Creating a New Protected Post

### URL

`admin/index.php?load=posts&action=newPost`

### Preconditions

- User is logged in as **administrator**
- No existing post data in session

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to Add New Post | — | Blank form loaded |
| Enter title | "My Protected Post" | Title field populated |
| Enter content | Some HTML content | Content in Summernote |
| Select "Protected" in visibility dropdown | Protected | Password field appears (JS toggle) |
| Enter password | `SecurePass123!` | Password field populated |
| Click "Publish" | — | Post created with encrypted content |
| View frontend | `/post/{id}/my-protected-post` | Password form shown |
| Unlock with correct password | `SecurePass123!` | Content decrypted and displayed |

### UI Components

| Component | Detail |
|-----------|--------|
| Title input | Required, max 200 chars |
| Content textarea (Summernote) | Required, max 500000 chars |
| Visibility select | Options: Public, Private, Protected |
| Password input | Hidden until "Protected" selected; shown via `checkVisibilitySelection()` |
| Publish button | Submits form |

### JavaScript Behaviour (checkVisibilitySelection)

When visibility dropdown changes:
- If value === `"protected"`: password input div shown (`display: inline`)
- Otherwise: password input div hidden (`display: none`)

### Validation

| Rule | Detail |
|------|--------|
| Password required when visibility=protected | `$appService->createPost()` checks `$_POST['visibility']` and `$_POST['post_password']` |
| Password min 8 chars | `check_post_password_strength()` — enforced server-side |
| Password must have uppercase, lowercase, digit, special char | `check_post_password_strength()` regex checks |

### Network Behaviour

| Request | Method | URL | Status |
|---------|--------|-----|--------|
| Form submit | POST | `admin/index.php?load=posts&action=newPost&Id=0` | 302 → redirect to posts list |
| (AJAX from Summernote image upload) | POST | `/admin/media-upload.php` | 201 (if image uploaded) |

---

## 11. Admin — Changing Password on Existing Protected Post

### Preconditions

- User is logged in as **administrator**
- Protected post exists (ID=3) with current password `Bac4D0nG(*)#`

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to Edit Post | ID=3 | Decrypted content shown in Summernote |
| Change password field | `NewSecurePass456!` | Password input updated |
| Click "Update" | — | Content re-encrypted with new passphrase |
| Visit frontend | `/post/3/cicero` | Password form shown |
| Unlock with OLD password | `Bac4D0nG(*)#` | 401 — Incorrect password |
| Unlock with NEW password | `NewSecurePass456!` | 200 — Content decrypted |

### Branch 1 (New Password) Flow

1. `setProtectedPostContent()` detects `visibility=protected` + `!empty($_POST['post_password'])`
2. Calls `protect_post($content, 'protected', $newPassword)`
3. `protect_post()` generates new SHA-256 passphrase from `app_key() + newPassword`
4. Content encrypted with new passphrase
5. New bcrypt hash stored in `post_password`
6. New SHA-256 passphrase stored in `passphrase`
7. `post_password` and `passphrase` are included in UPDATE query via conditional check in `PostDao::updatePost()`

---

## 12. Admin — Removing Protection from a Post

### Preconditions

- User is logged in as **administrator**
- Protected post exists (ID=3)

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to Edit Post | ID=3 | Decrypted content in Summernote |
| Change visibility to "Public" | Public | Password field hidden |
| Click "Update" | — | Post saved; content stored as plaintext |
| Visit frontend | `/post/3/cicero` | Content displayed without password form |

### Branch 3 Flow

1. `setProtectedPostContent()` detects `visibility !== 'protected'`
2. Content stored as plaintext in `post_content`
3. `post_password` and `passphrase` NOT included in UPDATE (conditional check keeps existing values in DB or clears them? Verify)

---

## 14. API — Verify Endpoint

### URL

`POST /api/v1/posts/{id}/verify`

### Preconditions

- Protected post exists (ID=3)

### User Actions

| Test | Body | Expected Status | Expected Response |
|------|------|-----------------|-------------------|
| Correct password | `{"password":"Bac4D0nG(*)#"}` | 200 | `{"success":true,"status":200,"data":{"valid":true}}` |
| Incorrect password | `{"password":"wrong"}` | 401 | `{"success":false,"status":401,"message":"Incorrect password"}` |
| Empty password | `{"password":""}` | 400 | `{"success":false,"status":400,"message":"Password is required"}` |
| No body | — | 400 | `{"success":false,"status":400,"message":"Password is required"}` |
| No ID | `/api/v1/posts/0/verify` | 400 | `{"success":false,"status":400,"message":"Post ID is required"}` |
| Rate limited (6th attempt) | `{"password":"wrong"}` | 429 | `{"success":false,"status":429,"message":"Too many failed attempts. Please try again later."}` |

### Key Difference from Unlock

- The `verify` endpoint only returns `{"valid": true/false}` — it does NOT return decrypted content
- It is a lightweight check for pre-validation before full unlock
- Rate limiting applies the same as unlock

---

## 15. API — Security & Error Handling

### Input Validation

| Input | Validation | Expected Behaviour |
|-------|------------|-------------------|
| Post ID | `(int)$params['id']` | Non-numeric → 0 → 400 "Post ID is required" |
| Password | `trim($input['password'])` | Empty after trim → 400 "Password is required" |
| Request body JSON | `$this->getJsonBody()` | Malformed JSON → 400 |
| Rate limit check | `is_unlock_rate_limited($postId)` | ≥5 attempts → 429 |
| Function existence | `function_exists('checking_post_password')` | Missing → 500 |
| Function existence | `function_exists('decrypt_post')` | Missing → 500 |

### Error Response Format

```json
{
  "success": false,
  "status": 4XX,
  "message": "Human-readable error message"
}
```

### Success Response Format (unlock)

```json
{
  "success": true,
  "status": 200,
  "data": {
    "content": "<p>Decrypted HTML...</p>"
  }
}
```

### Success Response Format (verify)

```json
{
  "success": true,
  "status": 200,
  "data": {
    "valid": true
  }
}
```

---

## 16. Security Expectations

### CSRF

| Area | Status | Notes |
|------|--------|-------|
| Admin create/edit post | ✅ CSRF token in form (`csrfToken`) | Validated server-side |
| AJAX unlock API | ❌ No CSRF token required | POST endpoint is publicly accessible; password itself is the authentication |
| Direct API access | ✅ Password required | Without valid password, no content returned |

### XSS

| Vector | Protection | Detail |
|--------|------------|--------|
| Post content (public) | `htmLawed` + `htmlout()` | Inline styles and event handlers stripped |
| Post content (protected/decrypted) | Double `html_entity_decode` + `htmLawed` + regex strip | Double decoding handles double-encoded entities |
| Password input | Server-side `trim()` | Displayed in error messages (should be escaped) |
| API response | JSON encoding | Content is returned as JSON string value |

### SQL Injection

| Area | Protection | Detail |
|------|------------|--------|
| `checking_post_password()` | `sanitizer($id, 'sql')` + Medoo | Parameterized via Medoo |
| `grab_post_protected()` | `sanitizer($id, 'sql')` + Medoo | Parameterized via Medoo |
| `grabPreparedFrontPostById()` | Prepared statement (`?` placeholder) | PDO prepared statement |
| `PostDao::updatePost()` | Prepared statements via `modify()` | DAO uses prepared statements |

### Authentication & Authorization

| Area | Check | Detail |
|------|-------|--------|
| Admin edit post | `userAccessControl(ActionConst::POSTS)` | Contributors and above can access |
| Admin create post | `userAccessControl(ActionConst::POSTS)` | Same check |
| API unlock | No auth required | Public endpoint (password is the auth) |
| Rate limit | File-based, IP-identified | No session/cookie required |

### Sensitive Information Exposure

| Secret | Exposed? | Detail |
|--------|----------|--------|
| bcrypt password hash | In admin form value | Shown in password input when editing |
| passphrase (SHA-256) | In HTML source? | **Should not be** — verify it's not in any hidden field |
| Encrypted content | In HTML source? | **Should not be** — only decrypted content is rendered |
| Decrypted content | Only to authorized users | Via successful unlock or admin session |
| Rate limit file contents | No | File is JSON with timestamps only |

### Session Security

| Aspect | Detail |
|--------|--------|
| Unlock state stored in `$_SESSION['unlocked_posts'][postId]` | Persists for session lifetime |
| Session cookie `scriptlog_auth` | Encrypted with Defuse key |
| Session fingerprinting | IP + User Agent hash |
| No unlock state in URL | Only in server-side session |

---

## 17. Accessibility

### Keyboard Navigation

| Element | Expected Behaviour |
|---------|-------------------|
| Password input | Tab to focus; type password |
| Unlock button | Tab to focus; Enter/Space to submit |
| Error message | Should be announced by screen reader |
| Decrypted content | Focus should move to content area after unlock |

### ARIA

| Element | Expected ARIA |
|---------|---------------|
| Password form | `role="form"`, `aria-label` for the form |
| Error container | `role="alert"` or `aria-live="assertive"` |
| Loading indicator | `aria-hidden="true"` (decorative spinner) |
| Unlock button | `aria-label` describing action |
| Content container after unlock | `aria-live="polite"` for dynamic content |
| Password input | `aria-required="true"` |

### Focus Management

| Action | Focus Should Move To |
|--------|---------------------|
| Page load | Body or skip-link |
| Unlock success | First heading or paragraph in decrypted content |
| Unlock error | Error message container (if `role="alert"`) |
| Rate limit | Error message container |

### Color Contrast

| Element | Foreground | Background | Ratio |
|---------|-----------|------------|-------|
| Error text | `#dc3545` (Bootstrap danger) | `#fff` | ~4.5:1 |
| Button text | `#fff` | `#007bff` (Bootstrap primary) | ~4.5:1 |
| Input text | `#495057` | `#fff` | ~4.5:1 |

### WCAG 2.2 AA Compliance

| Criterion | Applicable | Check |
|-----------|-----------|-------|
| 1.1.1 Non-text Content | Lock icon | Has `aria-hidden="true"` |
| 1.3.1 Info and Relationships | Form structure | Label, input association |
| 1.4.3 Contrast (Minimum) | All text | ≥4.5:1 |
| 2.1.1 Keyboard | Unlock button | Keyboard accessible |
| 2.4.3 Focus Order | Form → button | Logical tab order |
| 2.4.7 Focus Visible | All interactive | Visible focus indicator |
| 3.3.1 Error Identification | Wrong password | Error message displayed |
| 3.3.2 Labels or Instructions | Password field | Placeholder + context |
| 4.1.2 Name, Role, Value | Form elements | Properly labeled |

---

## 18. Responsive Behaviour

### Breakpoints

| Breakpoint | Width | Behaviour |
|------------|-------|-----------|
| Desktop | ≥992px | Form inline (`d-inline-flex`); sidebar visible |
| Tablet | 768-991px | Form inline; sidebar below content |
| Mobile | <768px | Form stacks vertically; full-width inputs |

### Mobile-Specific Checks

| Element | Expected Behaviour |
|---------|-------------------|
| Password input | Full width, touch-friendly height (≥44px) |
| Unlock button | Full width below input, tap target ≥44x44px |
| Error message | Full width below button |
| Loading spinner | Centered |
| Content container | Full width, readable font size (≥16px to prevent iOS zoom) |

---

## 19. Browser Compatibility

| Browser | Minimum Version | Notes |
|---------|-----------------|-------|
| Chrome | 90+ | Full support |
| Firefox | 90+ | Full support |
| Safari | 14+ | Test `autocomplete`, `fetch` compatibility |
| Edge | 90+ | Full support (Chromium-based) |

### Cross-Browser Checks

| Feature | Check |
|---------|-------|
| jQuery AJAX | All browsers |
| JSON.parse | All modern browsers |
| CSS fadeIn/fadeOut | jQuery animations work cross-browser |
| `autocomplete="current-password"` | Works in all browsers (may suggest saved passwords) |
| CSP nonce | Injected via PHP; all browsers support CSP |

---

## 20. Failure Scenarios

### 20.1 Empty Password Submission (Client-Side)

| Action | Expected Behaviour |
|--------|-------------------|
| Leave password field empty | Client-side `required` attribute triggers browser validation |
| Click "Unlock" with empty field | HTML5 validation stops submission; "Please fill out this field" bubble shown |
| JavaScript validation | `if (!password) { $error.text('Please enter a password').show(); return; }` |

### 20.2 Empty Password Submission (API Direct)

| Action | Expected Behaviour |
|--------|-------------------|
| POST to `/api/v1/posts/3/unlock` with `{"password":""}` | 400 — "Password is required" |

### 20.3 Non-Existent Post ID

| Action | Expected Behaviour |
|--------|-------------------|
| POST to `/api/v1/posts/9999/unlock` | 401 — "Incorrect password" (bcrypt verify fails on empty hash) |
| Navigate to `/post/9999/non-existent` | Dispatcher validates: post not found → 404 |

### 20.4 Non-Numeric Post ID

| Action | Expected Behaviour |
|--------|-------------------|
| POST to `/api/v1/posts/abc/unlock` | Router does not match: should return 404 or 405 |
| Navigate to `/post/abc/slug` | Dispatcher: `$postId = 0` → `validateSinglePost()` returns false → 404 |

### 20.5 Malformed JSON in Request Body

| Action | Expected Behaviour |
|--------|-------------------|
| POST to `/api/v1/posts/3/unlock` with `not-json` | `json_decode()` fails → 400 error |
| POST with `Content-Type: text/plain` | Depends on server parsing; likely 400 |

### 20.6 Slow Network

| Action | Expected Behaviour |
|--------|-------------------|
| Submit unlock on slow connection | Loading spinner shown; button disabled |
| Response eventually arrives | Normal success/error handling |
| User navigates away during request | Request cancelled by browser; no side effects |

### 20.7 Offline

| Action | Expected Behaviour |
|--------|-------------------|
| Submit unlock while offline | AJAX error handler triggered |
| Error message: "An error occurred. Please try again." | Shown in error container |
| Button re-enabled | User can retry when back online |

### 20.8 Database Unavailable

| Action | Expected Behaviour |
|--------|-------------------|
| Submit unlock | Server returns 500 error or connection error |
| API returns error JSON or fails to respond | JS error handler shows generic message |

### 20.9 Expired Session

| Action | Expected Behaviour |
|--------|-------------------|
| Unlock post, close browser, reopen hours later | Session may have expired |
| Navigate to protected post | Password form shown again (no session data) |

### 20.10 Double Submit

| Action | Expected Behaviour |
|--------|-------------------|
| Click "Unlock" twice rapidly | Button disabled on first click; second click does nothing |
| First request succeeds | Content shown; second response (if any) silently handled |
| First request fails | Error shown once; button re-enabled |

### 20.11 Refresh During Submission

| Action | Expected Behaviour |
|--------|-------------------|
| Click "Unlock", then refresh page before response | Page reloads; password form shown again |
| No side effects (unlock not written to session) | Safe because password wasn't verified yet |

### 20.12 Multiple Browser Tabs

| Action | Expected Behaviour |
|--------|-------------------|
| Open same protected post in 2 tabs | Both show password form |
| Unlock in Tab 1 | Content in Tab 1 |
| Refresh Tab 2 | Content in Tab 2 (shared session) |

---

## 21. Recovery Behaviour

### After Failed Unlock

1. Error message displayed below form
2. Button re-enabled
3. Password field: **verify if cleared or preserved** (document expected behaviour)
4. User can retry with different password
5. After rate limit, user must wait 15 minutes (or switch IP/network)

### After Rate Limit Expiry

1. Wait 15 minutes from first failed attempt
2. Old attempt timestamps are pruned (only timestamps < 900s old count)
3. Rate limit check returns false (attempts < 5)
4. User can retry (successful unlock clears remaining attempts)

### After Successful Unlock

1. Content displayed
2. Failed attempt counter cleared (`clear_failed_unlock_attempts()`)
3. Password stored in session for subsequent same-session page loads
4. No further password prompts for this post in this session

### After Admin Password Change

1. Old password stops working immediately (new bcrypt hash stored)
2. Content re-encrypted with new passphrase
3. Users with session unlocked with old password will see content from session (not re-verified)
4. After session expires, old password no longer works

---

## 22. Screenshots to Capture

| # | Screen | Viewport | Description |
|---|--------|----------|-------------|
| 1 | Protected post page — password form | Desktop (1280×720) | Lock icon, heading, description, password input, unlock button |
| 2 | Protected post page — password form | Tablet (768×1024) | Responsive layout |
| 3 | Protected post page — password form | Mobile (375×667) | Stacked layout |
| 4 | Unlock success — content displayed | Desktop | Decrypted content visible, form hidden |
| 5 | Unlock error — wrong password | Desktop | Red error text below form |
| 6 | Rate limited — 429 error | Desktop | "Too many failed attempts" error |
| 7 | Loading state | Desktop | Spinner visible, button disabled |
| 8 | Admin edit page — protected post | Desktop (1280×720) | Decrypted content in Summernote, visibility=Protected, password field shown |
| 9 | Admin edit page — visibility dropdown | Desktop | Dropdown open showing Public/Private/Protected options |
| 10 | Verify endpoint — cURL success | Terminal | Response with `{"valid": true}` |
| 11 | Verify endpoint — cURL failure | Terminal | Response with `{"valid": false}` |
| 12 | Rate limit file — file system | Terminal | Contents of `public/log/unlock_attempts/*.json` |

---

## 23. Console & Network Expectations

### Console

| Scenario | Expected Console Output |
|----------|------------------------|
| Normal page load | No errors; `unlock-post.js` loaded |
| Successful unlock | No errors; jQuery animations run |
| Failed unlock (wrong password) | No errors; AJAX error handler runs |
| Rate limited | No errors; success handler with message |
| Network error | AJAX error handler runs |
| Missing scriptlog_vars | **Error**: `ReferenceError: scriptlog_vars is not defined` |
| Multiple forms on page | Each form independently handled |

### Network

| Request | Expected | Unacceptable |
|---------|----------|--------------|
| Page load (protected post) | 200 HTML | 404, 500 |
| AJAX unlock (correct password) | 200 JSON | 4xx, 5xx, non-JSON response |
| AJAX unlock (wrong password) | 401 JSON | 200, 500, non-JSON |
| AJAX unlock (rate limited) | 429 JSON | 200, 500 |
| AJAX unlock (invalid ID) | 4xx JSON | 200, HTML response |
| AJAX unlock (no body) | 400 JSON | 200, 500 |
| Admin edit page | 200 HTML | 403 (if unauthorized), 404 |
| Admin update post | 302 Redirect | 500 |

---

## 24. Test Scenarios

### 24.1 Critical Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| C01 | Frontend: Protected post shows password form | Positive | Navigate to `/post/3/cicero` as logged-out user | Lock icon, password input, unlock button visible; no content |
| C02 | Frontend: Correct password unlocks post | Positive | Enter `Bac4D0nG(*)#`, click Unlock | Password form fades out; content fades in |
| C03 | Frontend: Wrong password shows error | Negative | Enter `wrongpassword`, click Unlock | Red error message "Incorrect password" |
| C04 | Frontend: Already-unlocked post shows content | Positive | Unlock in session → refresh page | Content shown without password form |
| C05 | Admin: Edit protected post shows decrypted content | Positive | Login as admin, edit post ID=3 | Decrypted content in Summernote |
| C06 | Admin: Create protected post | Positive | New post, visibility=protected, set password | Post created with encrypted content |
| C07 | API: Verify correct password | Positive | POST `/api/v1/posts/3/verify` with correct password | `{"valid": true}` |
| C08 | API: Verify wrong password | Negative | POST `/api/v1/posts/3/verify` with wrong password | `{"valid": false}` + 401 |
| C09 | API: Unlock non-existent post | Negative | POST `/api/v1/posts/9999/unlock` | 401 "Incorrect password" |
| C10 | API: Unlock with missing password | Negative | POST `/api/v1/posts/3/unlock` with empty body | 400 "Password is required" |

### 24.2 High Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| H01 | Frontend: Rate limiting after 5 failed attempts | Negative | Submit 5 wrong passwords → 6th attempt | 429 "Too many failed attempts" |
| H02 | Frontend: Encrypted content not in HTML source | Security | View page source of `/post/3/cicero` | No base64 ciphertext visible |
| H03 | Frontend: Password field attributes | Accessibility | Inspect password input | `autocomplete="current-password"`, `required` |
| H04 | Frontend: Unlock via Enter key | Keyboard | Focus input, type password, press Enter | Same as clicking Unlock |
| H05 | Frontend: Double-click Unlock button | Negative | Rapid double-click on Unlock | Only one AJAX request sent |
| H06 | Admin: Change password on protected post | Positive | Edit post ID=3, change password, update | Frontend: new password works, old doesn't |
| H07 | Admin: Remove protection (set to Public) | Positive | Edit post ID=3, visibility=Public, update | Frontend: content shown without password |
| H08 | Admin: Update protected post without changing password | Positive | Edit post ID=3, modify content, no password change | Content re-encrypted with existing passphrase |
| H09 | Admin: CSRF token present | Security | Inspect edit post form | Hidden `csrfToken` input with value |
| H10 | API: Unlock with malformed JSON | Negative | POST with `not-json` body | 400 error |

### 24.3 Medium Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| M01 | Frontend: Loading indicator during unlock | Visual | Click Unlock on slow connection (throttle) | Spinner visible, button disabled |
| M02 | Frontend: Error recovery after wrong password | Positive | Enter wrong password → error → enter correct password | Success |
| M03 | Frontend: Multiple tabs — unlock in one, refresh other | Concurrent | Unlock in Tab 1, refresh Tab 2 | Content shown in Tab 2 |
| M04 | Frontend: Mobile layout | Responsive | View at 375×667 | Form stacks vertically |
| M05 | Frontend: Session expires → password form returns | Session | Unlock, clear cookies, refresh | Password form shown again |
| M06 | Frontend: Inline styles stripped from content | Security | Create post with `<p style="color:red">` text, protect | Decrypted content has no `style` attribute |
| M07 | Frontend: XSS event handlers stripped | Security | Create post with `<img onerror="alert(1)">` | `onerror` removed |
| M08 | Admin: Visibility dropdown shows password field | UI | Select "Protected" in dropdown | Password input appears |
| M09 | Admin: Visibility dropdown hides password field | UI | Switch from "Protected" to "Public" | Password input hidden |
| M10 | API: Verify endpoint respects rate limit | Security | 5 failed verify attempts → 6th attempt | 429 response |
| M11 | API: Different endpoints share rate limit | Integration | 3 failed unlock + 2 failed verify → 6th of either | 429 response |
| M12 | API: Wrong HTTP method returns 405 | Negative | GET `/api/v1/posts/3/unlock` | 405 Method Not Allowed |
| M13 | Frontend: Rate limit per post (different post unaffected) | Isolation | 5 fails on post 3 → unlock post 4 (different post) | Post 4 unlock works |

### 24.4 Low Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| L01 | Frontend: Post featured image visible on protected post | Visual | View `/post/3/cicero` | Featured image shown above password form |
| L02 | Frontend: Post meta visible (author, date, comments) | Visual | View `/post/3/cicero` | Author, date, comment count visible |
| L03 | Frontend: Category and tags visible | Visual | View `/post/3/cicero` | Category links and tags visible |
| L04 | Frontend: Previous/Next navigation visible | Visual | View `/post/3/cicero` | Previous/next post links visible |
| L05 | Frontend: Comment form visibility with protected post | Edge | View protected post with `comment_status=open` | Comment form visible below password form (or hidden?) |
| L06 | Frontend: Verify button exists in JS but not UI | Code | Search for verify endpoint usage | Verify may not have UI; test directly via API only |
| L07 | Frontend: Concurrent rate limit files | Race | Send 10 simultaneous unlock requests | Rate limit file should handle concurrent access (LOCK_EX) |
| L08 | Admin: Contributor role cannot create protected posts? | Auth | Log in as contributor, try to create protected post | Authorization check prevents |
| L09 | Admin: Password strength validation | Validation | Set weak password like `abc` for protected post | Server-side validation rejects weak password |
| L10 | Frontend: HTML entities in decrypted content | Encoding | Create post with `&amp;lt;script&amp;gt;` in content | Displayed as `<script>` (decoded, not executed) |

### 24.5 Edge Cases

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| E01 | Post with `visibility=protected` but empty `post_password` | Error | Manually set in DB | `checking_post_password()` returns false; all attempts fail with 401 |
| E02 | Post with `visibility=protected` but empty `passphrase` | Error | Manually set in DB | `decrypt()` fails; returns empty content |
| E03 | Very long password (>72 chars, bcrypt limit) | Boundary | Create with 100-char password, unlock with exact same | Should work (bcrypt truncates at 72 chars) |
| E04 | Unicode password | Boundary | Create with `Pässwörd123!` | Encrypt and decrypt correctly |
| E05 | Very long post content (>1MB) | Boundary | Create protected post with 2MB of text | Encryption/decryption handles large content |
| E06 | Empty post content | Boundary | Create protected post with no body | Decrypt returns empty string; displayed as empty |
| E07 | Multiple protected posts — unlock all | Flow | Navigate to 3 different protected posts, unlock each | All three show content; each password independently verified |
| E08 | Rate limit after exactly 5 attempts | Boundary | Submit exactly 5 wrong passwords | 6th attempt is limited (≥5 returns true) |
| E09 | Rate limit at exactly 900 seconds | Time | Submit 1 failed attempt, wait 900 seconds | Attempt count resets to 0 |
| E10 | Admin: Update protected post — only change categories | Flow | Edit protected post, change categories, keep password | Content re-encrypted; categories updated |

### 24.6 Regression Scenarios

| ID | Scenario | Reason |
|----|----------|--------|
| R01 | Public post display unaffected by protection changes | Ensure non-protected posts still display normally |
| R02 | Admin post list shows all posts (including protected) | Post list should include protected posts |
| R03 | Search results include protected posts | Search should NOT exclude protected posts (but content not shown until unlocked) |
| R04 | RSS feeds exclude protected post content | Protected post content should not appear in RSS |
| R05 | Sitemap includes protected post URLs | Protected posts should still appear in sitemap (URL is visible, content is not) |
| R06 | API posts endpoint lists protected posts | GET `/api/v1/posts` should include protected posts (without content) |
| R07 | Page cache invalidation on protected post update | Updating protected post should clear page cache |

### 24.7 Security-Focused Scenarios

| ID | Scenario | Type | Expected |
|----|----------|------|----------|
| S01 | Direct access to `/api/v1/posts/3/unlock` with brute force | Security | Rate limiting prevents >5 attempts/15min |
| S02 | Session replay attack | Security | Old session cookies replay work but only for that session duration |
| S03 | Passphrase never in HTML/API response | Security | `passphrase` column not exposed in any API response |
| S04 | Encrypted content not in HTML source | Security | Base64 ciphertext not rendered in page source |
| S05 | Timing attack on password verification | Security | `password_verify()` is constant-time |
| S06 | XSS in password field via error message | Security | Error message displayed via `.text()` not `.html()` |
| S07 | SQL injection in post ID | Security | `(int)$params['id']` ensures integer |
| S08 | Admin: protected post content not cached in public caches | Security | Response headers like `Cache-Control: no-store` for protected content? |

### 24.8 Accessibility Scenarios

| ID | Scenario | Type | Expected |
|----|----------|------|----------|
| A01 | Tab through password form | Keyboard | Input → Unlock button → (other page links) |
| A02 | Submit form with Enter key | Keyboard | Same as clicking Unlock |
| A03 | Screen reader announces error message | A11y | Error container uses `role="alert"` |
| A04 | Focus moves to content after unlock | A11y | Focus is programmatically moved |
| A05 | Password field has associated label | A11y | `<label>` or `aria-label` present |

### Testing Priority Matrix

```
                    Critical  High  Medium  Low
Functional            C01-C04  H01    M01-M04 L01-L04
Security              C07-C10  H02    M06-M12 S01-S08
Accessibility         —        H03    —       A01-A05
Responsive            —        —      M04     —
Regression            —        —      —       R01-R07
Edge Cases            —        —      —       E01-E10
```

### Recommended Execution Order

1. **Critical** (C01–C10) — Core functionality must work first
2. **High** (H01–H10) — Important features and security
3. **Medium** (M01–M13) — Secondary features and edge cases
4. **Security** (S01–S08) — In-depth security verification
5. **Low** (L01–L10) — Nice-to-have verification
6. **Edge Cases** (E01–E10) — Boundary and error conditions
7. **Regression** (R01–R07) — Verify no collateral damage
8. **Accessibility** (A01–A05) — Accessibility verification

---

## Database Schema Reference

```sql
-- tbl_posts columns relevant to protected posts
post_visibility VARCHAR(20) NOT NULL DEFAULT 'public',  -- 'public', 'private', 'protected'
post_password VARCHAR(255) DEFAULT NULL,                 -- bcrypt hash
passphrase VARCHAR(255) DEFAULT NULL,                    -- SHA-256(app_key + password)
post_content longtext NOT NULL,                          -- AES-256-CBC encrypted (when protected)
```

---

## Environment Variables & Configuration

| Variable | Used In | Purpose |
|----------|---------|---------|
| `APP_KEY` | `create_encoded_key()` fallback, `setPassPhrase()` | Key derivation material |
| `DEFUSE_KEY_PATH` | `lib/core/ScriptlogCryptonize.php` | Primary encryption key path |
| `APP_URL` | `scriptlog_vars.api_url` | API base URL |

---

## End of Specification
