# Comments & Reply System — End-to-End Test Specification

> **Application**: Scriptlog (PHP 7.4+ / MariaDB / Bootstrap 4 / jQuery)
> **Spec Version**: 1.0
> **Feature Owner**: Blogware Team
> **Bootstrap**: `e2e/seed.spec.ts`
> **Companion Specs**: `specs/admin-login.md` (auth preconditions), `specs/posts.md` (post creation, `comment_status` setting)

---

## Table of Contents

1. [Overview / Purpose](#1-overview--purpose)
2. [URLs Under Test](#2-urls-under-test)
3. [Preconditions](#3-preconditions)
4. [Datasets & Fixtures](#4-datasets--fixtures)
5. [User Actions](#5-user-actions)
6. [Expected Behaviour](#6-expected-behaviour)
7. [Validation Rules](#7-validation-rules)
8. [Network Behaviour](#8-network-behaviour)
9. [UI Components](#9-ui-components)
10. [Accessibility](#10-accessibility)
11. [Responsive Behaviour](#11-responsive-behaviour)
12. [Browser Compatibility](#12-browser-compatibility)
13. [Security Expectations](#13-security-expectations)
14. [Failure Scenarios](#14-failure-scenarios)
15. [Recovery Behaviour](#15-recovery-behaviour)
16. [Screenshots to Capture](#16-screenshots-to-capture)
17. [Console Expectations](#17-console-expectations)
18. [Network Expectations](#18-network-expectations)
19. [Test Scenarios](#19-test-scenarios)

---

## 1. Overview / Purpose

The Comments & Reply system lets site visitors post comments on blog posts and lets
admins (and managers/editors/authors) moderate them.

- **Public side** (`/post/{id}/{slug}` + `comments-post.php` + `fetch-comments.php`):
  visitors submit a comment/reply through an AJAX form; approved comments are loaded
  lazily (paged "Load More") on the single-post page. Threaded replies are supported by
  a self-referential `comment_parent_id` on `tbl_comments`, but the default `blog` theme
  renders comments as a flat list (parent + replies are not visually nested).
- **Admin side** (`?load=comments` and `?load=reply`): list/edit/delete comments,
  list/create/edit/delete replies, approve/pend/mark-spam statuses.

### Key architectural facts a test must know

1. **New visitor comments are stored with status `pending`** (schema default
   `comment_status VARCHAR(20) NOT NULL DEFAULT 'pending'` in `tbl_comments`). They do
   **not** appear in the public `fetch-comments.php` feed (which filters
   `comment_status = 'approved'`) until an admin approves them. The public comment-count
   badge also counts **approved only**.
2. **Comment form is shown only when the post's `comment_status = 'open'`** (rendered as
   `$comment_permit` in `single.php`). Closed posts show neither the form nor the comments
   section.
3. **Submission is AJAX + JSON** to `comments-post.php` (`POST` only). There is **no
   server-side rate limiting and no honeypot field** on the public comment form today
   (the `scriptpot_validate()` honeypot helper exists but is not wired to this form).
4. **CSRF is a single-use session token** stored per form name (`comment_form`). A
   page refresh mints a new token; the old one is invalid.
5. **Deleting a parent comment does NOT cascade-delete its replies** in the admin panel
   (documented behaviour; orphaned replies may remain pointing at a deleted parent).

### Key files under test

| Layer | File | Role |
|-------|------|------|
| Front entry | `comments-post.php` | Public comment POST endpoint (JSON) |
| Front entry | `fetch-comments.php` | Public approved-comment JSON feed (offset paging) |
| Front view | `public/themes/blog/single.php` | Post page: comment form + comments section render |
| Front view | `public/themes/blog/functions.php` | `render_comments_section()`, `total_comment()`, `block_csrf()` |
| Front view | `public/themes/blog/render-comments.php` | Comments section HTML helper |
| Front JS | `public/themes/blog/assets/js/comment-submission.js` | AJAX submit + client validation |
| Front JS | `public/themes/blog/assets/js/load-comment.js` | Lazy comment loading + "Load More" |
| Utility | `lib/utility/comment-submission.php` | `processing_comment()`, `checking_form_input()`, `fetch_comments()` |
| Utility | `lib/utility/form-security.php` | `generate_form_token`, `verify_form_token`, `check_form_request` |
| Utility | `lib/utility/form-size-validation.php` | Field length guard |
| Model | `lib/model/CommentModel.php` | `addComment()` insert (no status set → default pending) |
| Admin entry | `admin/comments.php` | Comment action dispatch via `AdminActionRegistry` |
| Admin entry | `admin/reply.php` | Reply action dispatch (direct switch) |
| Admin view | `admin/ui/comments/all-comments.php` | Comments list + reply counts + edit/reply/delete actions |
| Admin view | `admin/ui/comments/edit-comment.php` | Edit comment form |
| Admin view | `admin/ui/comments/reply.php` | Create/edit reply form |
| Admin view | `admin/ui/comments/reply-list.php` | Reply list for a comment |
| Controller | `lib/controller/CommentController.php` | `listItems`, `update`, `remove` |
| Controller | `lib/controller/ReplyController.php` | `insert`, `update`, `remove` |
| Command | `lib/handler/admin/comment/*.php` | `ListCommentsCmd`, `EditCommentCmd`, `DeleteCommentCmd` |
| Service | `lib/service/CommentService.php` | Comment CRUD, `countReplies()`, `modifyComment()`, `removeComment()` |
| Service | `lib/service/ReplyService.php` | Reply CRUD, `addReply()` (default status `pending`) |
| DAO | `lib/dao/CommentDao.php` | `tbl_comments` queries, `countReplies()`, `deleteComment()` |
| DAO | `lib/dao/ReplyDao.php` | Reply queries, `getParentComment()` |
| Schema | `install/include/dbtable.php` | `tbl_comments` DDL (status default `pending`) |

---

## 2. URLs Under Test

| Page / Endpoint | URL | Auth |
|-----------------|-----|------|
| Single post (permalink) | `<base_url>/post/{id}/{slug}` | Public |
| Single post (query string) | `<base_url>/?p={id}` | Public |
| Comment submit | `<base_url>/comments-post.php` | Public (POST) |
| Comment feed | `<base_url>/fetch-comments.php?post_id={id}&offset={n}` | Public (GET) |
| Admin login | `<base_url>/admin/login.php` | — |
| Admin comments list | `<base_url>/admin/index.php?load=comments` | ADMIN/COMMENTS |
| Edit comment | `<base_url>/admin/index.php?load=comments&action=editComment&Id={id}` | COMMENTS |
| Delete comment | `<base_url>/admin/index.php?load=comments&action=deleteComment&Id={id}` | COMMENTS |
| Reply form / submit | `<base_url>/admin/index.php?load=reply&action=reply&Id={parent_id}` | REPLY |
| Edit reply | `<base_url>/admin/index.php?load=reply&action=editReply&Id={reply_id}` | REPLY |
| Delete reply | `<base_url>/admin/index.php?load=reply&action=deleteReply&Id={reply_id}` | REPLY |
| Reading settings | `<base_url>/admin/index.php?load=option-reading` | CONFIGURATION |

---

## 3. Preconditions

1. **Application installed** — `config.php` with valid DB credentials, all 22 tables
   created (`blogware_test` for tests), admin user seeded. See `specs/installer.md`.
2. **Admin account exists** — `administrator` / `$E2E_ADMIN_PASS` (see `e2e/.env.example`).
3. **At least one published post** with `comment_status = 'open'` exists on the frontend.
   For negative tests, also have a post with `comment_status = 'closed'`.
4. **Reading setting** `comment_per_post` ("Comments to display in post") is known; set a
   small value (e.g. 3) to exercise paging deterministically.
5. **Comments exist in known states**: at least one `approved`, one `pending`, one `spam`
   comment, and one top-level comment with a child reply, seeded directly in the DB (see
   §4). The exact comment IDs must be recorded per test run because IDs are
   auto-increment.
6. **Browser state**: fresh, no leftover session (public comment CSRF is session-based;
   tests that reuse a page must reload the page to mint a fresh token).
7. **Theme**: default `blog` theme active (asset URLs/integrity hashes are theme-specific).

---

## 4. Datasets & Fixtures

Seed rows via SQL in `blogware_test` (unprefixed tables) before the suite. Record the
returned IDs (comments table is auto-increment):

| Fixture | Fields | Purpose |
|---------|--------|---------|
| Post A (`comment_status='open'`) | slug `open-comments-post` | Positive path |
| Post B (`comment_status='closed'`) | slug `closed-comments-post` | Form hidden case |
| Comment C1 (approved, `comment_parent_id=0`) | on Post A | Appears publicly |
| Comment C2 (pending, `comment_parent_id=0`) | on Post A | Hidden publicly until approved |
| Comment C3 (spam, `comment_parent_id=0`) | on Post A | Hidden publicly |
| Comment C4 (approved) | on Post A, `comment_parent_id = C1.ID` | Reply case; verifies parent delete ≠ cascade |
| Comment C5, C6, C7 (approved) | on Post A | Paging (`comment_per_post=3`) |

Test users for RBAC (see `specs/posts.md` §15 for exact role setup): `administrator`,
`manager`, `editor`, `author`, `contributor`, `subscriber`.

---

## 5. User Actions

### 5.1 Public — single post page

| Action | Input |
|--------|-------|
| Navigate to `/post/{id}/{slug}` (permalink) or `?p={id}` (query string) | — |
| Scroll to comments section | — |
| Read comment list (first `comment_per_post` approved comments) | — |
| Click **Load More Comments** | — |
| Click **Load More Comments** again after list exhausted | — |
| Fill comment textarea `#comment` | keyboard |
| Fill name `#name` | keyboard |
| Fill email `#email` | keyboard |
| Submit the form (`Enter` in a field or click **Submit Comment**) | mouse / keyboard |
| Submit with empty fields | — |
| Submit with invalid email / invalid name format / over-length fields | — |
| Submit, then double-click submit / press Enter repeatedly (double submit) | — |
| Refresh the page after loading | browser |
| Press Back / Forward after submitting | browser |
| Open the post in a second tab and submit from both | multiple tabs |
| Disable JavaScript and submit (progressive enhancement) | — |

### 5.2 Public — comment feed (XHR)

The page fires an XHR to `fetch-comments.php?post_id={id}&offset=0` automatically.
User-driven actions that trigger further feed requests: clicking **Load More Comments**;
submitting a comment (success path reloads the feed from offset 0).

### 5.3 Admin — comments list (`?load=comments`)

| Action |
|--------|
| Log in as administrator and open the list |
| Read table rows, reply-count badges, status/date columns |
| Click Edit (pencil) icon on a comment |
| Click Reply icon on a comment |
| Click Delete (trash) icon → confirm dialog → confirm |
| Click Delete → confirm dialog → cancel |
| Dismiss the success/error alert banners |

### 5.4 Admin — edit comment (`?load=comments&action=editComment&Id={id}`)

| Action |
|--------|
| Modify author name |
| Modify comment content |
| Change status dropdown (Approved / Pending / Spam) |
| Submit **Update** |
| Submit with empty author name |
| Submit with empty content |
| Submit with stale/absent CSRF token |
| Click **Cancel** / breadcrumb back to list |
| Open edit page for a non-existent comment ID |

### 5.5 Admin — reply to comment (`?load=reply&action=reply&Id={parent_id}`)

| Action |
|--------|
| Open reply form from list (Reply icon) or from edit-comment page |
| Read parent-comment info panel |
| Fill author name / reply content, pick status, submit **Submit Reply** |
| Submit with empty author name / empty content |
| Submit with stale CSRF |
| Cancel back to edit-comment |
| Open reply for a non-existent parent comment ID / `Id=0` |

### 5.6 Admin — edit / delete reply (`action=editReply|deleteReply`)

| Action |
|--------|
| Edit reply author/content/status, submit **Update Reply** |
| Delete reply via confirm dialog (confirm and cancel) |
| Delete a reply that does not exist |

---

## 6. Expected Behaviour

### 6.1 Public — comments section display

- Post with `comment_status='open'`: section header "Post Comments" + badge showing
  **approved** comment count; `#comments` container renders up to `comment_per_post`
  approved comments, ordered `comment_date DESC`.
- Post with `comment_status='closed'`: **no** comment section and **no** form.
- Comment cards show escaped author name, escaped content, and a date string; scripts in
  content are inert (`escapeHtml` via jQuery `.text()`).
- When approved count ≤ `comment_per_post`: "Load More Comments" button becomes **No More
  Comments** and is disabled after the first batch.
- When approved count = 0: feed returns `[]`; button immediately shows **No More
  Comments** (disabled).
- `?p={id}` query-string form renders the same sections.

### 6.2 Public — comment submission (AJAX)

- Valid submit → form resets, `#success_message` shows **"Comment was submitted
  successfully"**, fades out after ~2 s, and the comments feed reloads from offset 0.
- Because the new comment is stored **pending**, it does **not** appear in the refreshed
  feed and the count badge is unchanged. (This is expected; flag as documented behaviour.)
- Invalid input blocked client-side → no network request; `#error_message` shows the
  client message and invalid fields get `.is-invalid`.
- Server-side rejection → HTTP 400, JSON `{success:false, error_message:[...]}`, error
  surfaced via `#error_message`.
- Empty `email` or invalid `email` fails `FILTER_VALIDATE_EMAIL` in `comments-post.php`,
  which calls `scriptlog_error("Invalid comment data received.")` (no JSON response —
  test asserts no success message and DB unchanged).
- Non-POST request to `comments-post.php` → HTTP **405** with `Allow: POST`.

### 6.3 Admin — comments list

- Table headers: `#`, Comment, In Response To, Replies, Submitted On, Actions.
- Reply column: badge `{n} reply/replies` when `n>0`, plain "0 replies" otherwise.
- Edit/Reply/Delete icon buttons per row; JS `confirm()` gates delete.
- Flash banners: success ("Comment has been updated" / "Comment deleted" / "New comment
  added"), error ("Error: Comment Not Found!").

### 6.4 Admin — edit comment

- GET loads existing values into the form (author, content via `safe_html`, status
  dropdown, post title in side panel, submitted-on timestamp).
- Valid POST → DB updated, `page_cache_clear()`, redirect to
  `index.php?load=comments&status=commentUpdated` with success banner.
- Empty author/content → re-render form with error list ("Please enter author name",
  "Please enter comment content"); no DB change.
- Invalid/stale CSRF → HTTP 400 + "Sorry, unpleasant attempt detected!" (logged).

### 6.5 Admin — reply lifecycle

- Reply form shows parent-comment info (author, content, date, post title).
- Valid new-reply POST → insert with chosen status (default `pending`), redirect to
  `index.php?load=comments&action=editComment&Id={parent}&status=replyAdded` with banner
  "Reply added successfully"; reply count badge increments on the list.
- Valid edit POST → update, redirect to `...&action=editReply&Id={id}&status=replyUpdated`.
- Delete reply → redirect `index.php?load=comments` with "Reply deleted".
- `Id=0` on reply action → redirect `index.php?load=comments` (302).
- Non-existent parent / reply → 404 page (`load=404`).

### 6.6 Access control

- `COMMENTS` permission required for the comments screens; `REPLY` for reply screens.
  Denied roles → HTTP 403 (`?load=403`). Available to administrator/manager/editor/author;
  denied to contributor/subscriber.
- Unauthenticated admin access → redirect to `admin/login.php`.

---

## 7. Validation Rules

### 7.1 Public form (server: `lib/utility/comment-submission.php`)

| Field | Required | Max length (server) | Rules | Error message |
|-------|----------|---------------------|-------|---------------|
| `post_id` | yes | — | integer; must equal re-sanitized POST value | — (silent → 0) |
| `parent_id` | no | — | integer; 0 for comment, >0 for reply | — |
| `name` | yes | 90 | `/^[A-Z \'.-]{2,90}$/i` | empty → "All column required must be filled"; format → "Please enter a valid name" |
| `email` | yes | 120 (server) / 180 (HTML) | RFC 5321 via `Egulias RFCValidation` or multiple-email | "Please enter a valid email address" |
| `comment` | yes | 320 (server + HTML `maxlength`) | non-empty | "All column required must be filled" |
| `csrf` | yes | — | session token `comment_form`, single-use | "Invalid CSRF token." |
| payload | — | — | whitelist `post_id,name,email,comment,csrf` only | HTTP 413 "413 Payload Too Large" + `Retry-After: 3600` |

Field-length gate (`form_size_validation`) → "Form data is longer than allowed" (HTTP 400).

Client-side (`comment-submission.js`) mirrors these: comment non-empty; name regex
`/^[A-Z \'.-]{2,90}$/i`; email regex
`/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/`. Client blocks the request entirely
when invalid.

### 7.2 Admin — edit comment

| Field | Required | Max | Rules |
|-------|----------|-----|-------|
| `author_name` | yes | — | non-empty (server); "Please enter author name" |
| `comment_content` | yes | 500 (HTML `maxlength`) | non-empty (server); "Please enter comment content" |
| `comment_status` | no | — | dropdown: Approved / Pending / Spam |
| `csrfToken` | yes | — | `csrf_check_token('csrfToken', $_POST, 600)` → else 400 |

### 7.3 Admin — reply

| Field | Required | Max | Rules |
|-------|----------|-----|-------|
| `author_name` | yes | 60 (HTML) | non-empty (server) |
| `reply_content` | yes | 1000 (HTML) | non-empty (server) |
| `reply_status` | no | — | dropdown: Draft / Pending / Approved / Spam (default `pending`) |
| `csrfToken` | yes | — | as above |

### 7.4 Success messages (verbatim)

- Public: `Comment was submitted successfully`
- Admin: `New comment added` · `Comment has been updated` · `Comment deleted`
- Reply: `Reply added successfully` · `Reply has been updated` · `Reply deleted`

---

## 8. Network Behaviour

### 8.1 Public page load (`/post/{id}/{slug}`)

- `200 OK` (HTML). Dispatcher validates post + slug; wrong slug → `404`.
- After render, XHR `GET /fetch-comments.php?post_id={id}&offset=0` → `200`, JSON array
  (possibly `[]`).
- Assets: `comment-submission.min.js`, `load-comment.min.js?v=1.2` load with `defer`
  (both under `/assets/js/`).

### 8.2 Comment submit (`POST /comments-post.php`)

| Case | Status | Body |
|------|--------|------|
| Valid | `200` | `{"success": true, "success_message": "Comment was submitted successfully"}` |
| Server validation failure | `400` | `{"success": false, "error_message": [{"error_message": "..."}]}` |
| Non-POST | `405` | `Allow: POST` header, empty body |
| Extra/forged fields | `413` | plain `413 Payload Too Large` + `Retry-After: 3600` |
| Failed filter (invalid email, etc.) | depends on error level (see §6.2) | no JSON |

### 8.3 Feed (`GET /fetch-comments.php`)

- `post_id <= 0` or missing → `200` with `[]`.
- Valid → `200` JSON array; item fields `ID, comment_post_id, comment_parent_id,
  comment_author_name, comment_content, comment_status, comment_date`; **approved only**;
  `ORDER BY comment_date DESC LIMIT {offset},{limit}`.
- Server error → PHP exception logged to error log; empty array returned.

### 8.4 Admin

- List `GET ?load=comments` → `200`.
- Edit GET → `200`; comment not found → `404` (`?load=404&notfound=...`).
- Delete/edit POST success → `302` redirect to list/edit URL; failure → `400`.
- Reply create POST → `302` to `?load=comments&action=editComment&Id={parent}&status=replyAdded`.
- Reply `Id=0` → `302` to `?load=comments`.
- Forbidden role → `403` (`?load=403&forbidden=...`).

---

## 9. UI Components

### 9.1 Public

- **Comments section** `#comments-section`: header `h3` "Post Comments" + `<span
  class="badge badge-secondary">{count}`; container `#comments[data-post-id]`; button
  `#load-more` "Load More Comments".
- **Comment card**: `<div class="comment mb-3 p-3 border rounded shadow-sm">` with
  `card-title` (author), `card-text` (content), `text-muted` (date). No `role`/ARIA.
- **Form** `#commentForm` (`method=post`, action `<site>/comments-post.php`, class
  `p-5 bg-light`):
  - label + textarea `#comment` (`maxlength=320`, `required`, placeholder i18n)
  - label + input `#name` (`maxlength=90`, `autocomplete=name`, `required`)
  - label + input `#email` (`type=email`, `maxlength=180`, `autocomplete=email`,
    `required`)
  - hidden `#csrf`, `#post_id`, `#parent_id`
  - button `.btn-secondary` "Submit Comment"
  - feedback divs `#error_message.ajax_response`, `#success_message.ajax_response`
- **Language switcher** and site nav (header) — unchanged by this feature.

### 9.2 Admin — comments list

- Breadcrumb: Home › All Comments › Data Comments.
- Alerts: `.alert-danger` (errors) / `.alert-success` (status), dismissible.
- Table `#scriptlog-table.table.table-bordered.table-striped` with header/tfoot; columns
  `#` (row number), Comment (60-char excerpt + `by {author}`), In Response To (post
  title), Replies (badge), Submitted On (`time_elapsed_string`), Actions.
- Action buttons: `.btn-warning` Edit (pencil), `.btn-primary` Reply (reply icon),
  `.btn-danger` Delete (trash) — each with `aria-label`.
- Inline JS `deleteComment(id, name)` / `deleteReply(id, name)` using `confirm()`.

### 9.3 Admin — edit comment

- Form fields `author_name` (required, star marker), `comment_content` (textarea, 500),
  `comment_status` dropdown, hidden `comment_id`, `post_id`, `csrfToken`.
- Side panel: "Response To" (post title), "Submited On" (human-readable datetime),
  Reply button.
- Footer: **Update** (submit), **Cancel** link to list.

### 9.4 Admin — reply

- Form: `author_name` (60), `reply_content` (textarea, 1000, helper "Maximum 1000
  characters"), `reply_status` dropdown, hidden `parent_comment_id`, `csrfToken`.
- Side panels: "Parent Comment Information" (author/content/date/post title) and, when
  editing, "Reply Info" (submitted-on).
- Footer: Cancel (to edit-comment of parent) + **Submit Reply** / **Update Reply**.

### 9.5 Admin — reply list

- Header "Replies for Comment #{id}", Back to Comment, Add Reply buttons.
- Table: `#`, Author, Reply Content, Status (label colors: approved→success,
  pending→warning, spam→danger, else default), Submitted On, Actions (Edit / Delete).
- Empty state: info alert "No replies found for this comment." + "Add First Reply".

---

## 10. Accessibility (WCAG 2.2 AA)

| Check | Current state (from code) | Test assertion |
|-------|---------------------------|----------------|
| Labels | `label for` present for comment/name/email (public) and admin fields | `for` matches `id` |
| Required indication | `required` attr + `*` in labels (public), `*` + `required` (admin) | — |
| Keyboard | All controls are native `input/textarea/button/a` → tabbable; Delete uses JS `confirm()` (blocking, reachable by keyboard Enter on link) | Tab order covers form → submit → load-more |
| Focus visibility | Global `*:focus-visible{outline:3px solid #7fff00}` in theme critical CSS | visible focus ring |
| Skip link | `.skip-link` to `#main-content` (header) | present, focusable, jumps to main |
| Live feedback | `#error_message`/`#success_message` are plain divs — **no `role="alert"`/`aria-live`**; client errors set `.is-invalid` but **no `aria-invalid`** | Note as gaps; assert messages still appear in DOM |
| Name/role/value | Comment cards are unlabelled `div`s — no semantic list or `aria-label`; load-more is a real `<button>` | button role ok; card content announced as generic text |
| Table headers | `th scope="col"` and `aria-describedby` present (admin) | — |
| Colour contrast | Badges/labels rely on colour (`label-success/warning/danger`) without text alternatives | status text also present |
| `lang`/`dir` | `<html lang dir>` set from locale | present |
| Multiple-language forms | i18n keys `single.comment.*`, `form.name.label`, `form.email.label` (en/ar/zh/fr/ru/es/id) | RTL layout still operable (see §11) |

Recommended a11y tests: keyboard-only submission, focus order, `role="alert"` presence
(gap), `aria-invalid` on invalid fields (gap), load-more accessible name, screen-reader
announcement of the comment count badge.

---

## 11. Responsive Behaviour

Breakpoints (Bootstrap 4): <576 (mobile), ≥576 (sm), ≥768 (md/tablet), ≥992 (lg/desktop).

| Element | Desktop | Tablet | Mobile |
|---------|---------|--------|--------|
| Comments form (`.p-5`) | full width inside `.col-lg-8` post column | stacks naturally | padding scales |
| Comment cards | full-width | full-width | full-width |
| Admin comments table | all columns | "In Response To" (`hidden-xs hidden-sm`) and date/replies columns (`hidden-xs`) hidden | only `#`, Comment, Actions visible |
| Reply list table | all columns | Author/Status/Date hidden | Comment + Actions |
| Admin action buttons | inline `.btn-group` | inline | may wrap |
| Nav | desktop bar | collapse toggle | hamburger (`sina-nav`) |

Assert no horizontal scroll at 375px width, form controls remain ≥44px tap targets where
possible, and the Load More button remains reachable.

---

## 12. Browser Compatibility

Must pass on current stable Chrome, Firefox, Safari, Edge (Playwright browsers).

| Area | Risk |
|------|------|
| `$.ajax` JSON handling | covered by jQuery (no native fetch) — low risk |
| `window.CommentSettings?.limit ?? 3` (optional chaining + nullish coalescing) in `load-comment.js` | requires modern browser; **not** transpiled — fails on IE11/legacy; verify in the supported browsers, note as limitation |
| `String.prototype.trim`, template literals in `load-comment.js` | modern browsers only |
| `defer` script ordering | feed XHR must fire after `load-comment.js` executes |

---

## 13. Security Expectations

| Concern | Implementation | Test |
|---------|----------------|------|
| CSRF (public) | single-use session token `comment_form` via `verify_form_token()` | submit without token → 400 + "Invalid CSRF token."; replay the same token → 400 |
| CSRF (admin) | `csrf_check_token('csrfToken', $_POST, 600)` | stale/absent token → 400 + "Sorry, unpleasant attempt detected!" |
| XSS | content purified on write (`purify_dirty_html`), escaped on read (admin `safe_html`/`htmlspecialchars`; public JS `escapeHtml` via jQuery `.text()`) | post `<script>`/`onerror`/`<img src=x onerror=alert(1)>` in name+content → inert in admin list, edit form, and public feed |
| SQL injection | PDO prepared statements; IDs sanitized (`filteringId`) | `Id=1 OR 1=1`, negative/string IDs → 400/404, no data leak |
| Payload whitelist | `check_form_request` (public) | extra field (`is_admin=1`, `comment_status=approved`) → 413 |
| Status forging | public insert never sets status → DB default `pending` | forged `comment_status=approved` param cannot escalate (whitelist rejects it anyway) |
| Rate limiting | **none** on public comment submit (documented gap) | test rapid-fire submissions succeed; flag as improvement |
| Honeypot | `scriptpot_validate()` exists but is **not wired** to this form | no honeypot fields in DOM; flag as gap |
| Auth cookies / sessions | admin uses `Authentication`/`SessionMaker` (see `admin-login.md`) | session expiry mid-edit → login redirect |
| Authorization | server-side per-role (`userAccessControl`) | contributor/subscriber get 403 on comments & reply; anonymous admin URLs redirect to login |
| Sensitive data | commenter IP stored (`comment_author_ip`), email stored; not exposed in public feed or admin excerpt | feed JSON must not include `comment_author_ip`/`comment_author_email` |
| Info leakage | no stack traces to client; `LogError` logs server-side | error responses are generic |

---

## 14. Failure Scenarios

| Scenario | Expected |
|----------|----------|
| Empty name | client blocks; if bypassed, server 400 "All column required must be filled" |
| Empty email / malformed email | client blocks; bypassed → `scriptlog_error("Invalid comment data received.")` (no success), no DB row |
| Name format invalid (`1name`, `a`) | client + server 400 "Please enter a valid name" |
| Comment > 320 chars | server 400 "Form data is longer than allowed" (client `maxlength` normally prevents) |
| Email > 120 chars (server) | 400 "Form data is longer than allowed" |
| Stale/absent/missing CSRF | 400 "Invalid CSRF token." (public) / 400 + logged exception (admin) |
| Double submit / double-click | token is single-use → second submit 400; assert only one DB row for first request |
| Refresh during submit | request aborted; no partial write; on reload new token minted |
| Page reload before submit | old token invalidated → resubmit with stale token fails (user must re-enter) |
| Multiple tabs | each tab has its own token? Session token store is shared (`$_SESSION[form.'_csrf']`) but token values are per-render; submitting from two tabs with two different tokens works once each; submitting with the **same** token twice fails on the second |
| Submit on closed-comment post | form not rendered; direct POST to `comments-post.php` with that `post_id` is **not blocked server-side** (no status check in `processing_comment`) → comment is inserted as pending. **Flag:** verify whether this should be blocked |
| Direct POST without visiting page | CSRF token absent → 400 |
| Slow network on `fetch-comments.php` | spinner absent; empty container until response; on failure error text + button hidden |
| `fetch-comments.php` returns 500 / invalid JSON | `error` callback → "Failed to load comments. Please try again later."; button hidden |
| DB unavailable | `fetch_comments()` returns `[]` and logs; submit path may surface generic error; no crash on comments section (section renders, feed empty) |
| Expired admin session mid-edit | next POST redirects to login |
| Delete parent comment with replies | replies are **not** cascade-deleted (documented) → orphaned replies remain in DB and list; verify count and admin list behaviour |
| Edit comment with ID that is a reply | `findComment` returns it (no parent filter) → admin can edit a reply via editComment; verify expected UX |
| Reply to a comment that is itself a reply | `getParentComment` requires `comment_parent_id=0` → parent lookup fails → 404 "parentCommentNotFound" |
| Duplicate `csrf` parameter (hidden input + JS append) | both values identical so submission succeeds; assert it does not break valid submits |
| `post_id` tampered (string, negative) | sanitized to 0 → comment written against post 0 (orphan). **Flag** for verification |

---

## 15. Recovery Behaviour

- **Comment submit failure (400)**: form data preserved client-side (no reset on error);
  user can fix and resubmit; success path resets the form and reloads comments.
- **Network error / timeout**: `formError()` shows "An error occurred. Please try again
  later." (or "The request timed out…"); form data retained; no DB write assumed; retry
  works (new token needed if the failed request already consumed it — verify).
- **Feed failure**: message "Failed to load comments…"; Load More hidden; a page reload
  retries the feed.
- **Admin failed edit**: form re-rendered with the submitted values + error banner; user
  can correct and resubmit (a fresh CSRF token is re-rendered).
- **Post-refresh CSRF loss**: user must reload the public page to mint a new token before
  resubmitting.
- **Session timeout in admin**: redirect to login; after re-login the redirect target
  (`?load=comments…`) should be reachable.

---

## 16. Screenshots to Capture

1. **Desktop** — post page with comments section + form (open post, ≥1 approved comment).
2. **Desktop** — closed post (no form/section).
3. **Desktop** — success state (`#success_message` visible after valid submit).
4. **Desktop** — client validation error state (`.is-invalid` fields + `#error_message`).
5. **Desktop** — server 400 JSON error surfaced in `#error_message`.
6. **Desktop** — "No More Comments" disabled state.
7. **Admin** — comments list with reply-count badges and action buttons.
8. **Admin** — success banner after update ("Comment has been updated").
9. **Admin** — edit-comment form (with side panel).
10. **Admin** — reply form (with parent-comment panel).
11. **Admin** — reply list with status labels.
12. **Admin** — 403 page for unauthorized role.
13. **Tablet** (768px) — post page comments; admin list (hidden columns).
14. **Mobile** (375px) — post page comments + form; admin list.
15. **RTL locale** (e.g. `ar`) — post page comments + form (layout/direction).

---

## 17. Console Expectations

- **No** uncaught `TypeError`/`ReferenceError` on the post page.
- `load-comment.js` logs `console.error("Post ID missing or invalid…")` **only** when the
  `#comments[data-post-id]` attribute is missing/invalid — must not appear on normal pages.
- `formError()` is silent on abort (expected).
- No CSP violations (scripts carry `nonce`; third-party `integrity` hashes must match).
- jQuery `.parseJSON` failure would throw in `formSuccess` — assert no JSON parse errors on
  valid submit.

## 18. Network Expectations

- `fetch-comments.php` requests: `GET`, one on load + one per Load More click + one after
  each successful submit; no duplicates beyond these.
- `comments-post.php`: exactly one `POST` per successful submit.
- No request to a backend/`coments-post.php`-like typo'd URL.
- All static assets resolve `200`; any `404` for `.js`/`.css` (missing minified copy,
  theme mismatch) must be flagged — see `plan/TASTYBITES_FIX_PLAN.md` precedent where
  themes were missing `comment-submission.min.js`/`load-comment.min.js`.
- Page-cache invalidation: after admin edit/delete of a comment, `page_cache_clear()`
  runs — assert the public page reflects the change (fresh feed).

---

## 19. Test Scenarios

Priority: **Critical / High / Medium / Low**.

### 19.1 Positive (happy path)

| # | Priority | Scenario |
|---|----------|----------|
| P1 | Critical | Visitor submits a valid comment on an open post → 200 JSON success, form resets, success message visible, feed reloads, no crash |
| P2 | Critical | Approved comments render on the post page (correct count badge, DESC order, escaped content) |
| P3 | Critical | "Load More" fetches next page; shows "No More Comments" (disabled) when exhausted |
| P4 | High | Admin edits a comment (author/content/status) → success banner + DB updated + public feed reflects approved status |
| P5 | High | Admin creates a reply to a comment → redirect + "Reply added successfully" + reply badge count increments |
| P6 | High | Admin edits a reply → "Reply has been updated" |
| P7 | High | Admin deletes a reply (confirmed) → "Reply deleted", row gone |
| P8 | High | Admin deletes a comment (confirmed) → "Comment deleted", row gone |
| P9 | Medium | Query-string URL `?p={id}` shows identical comment UI |
| P10 | Medium | Pending comment hidden publicly, appears after admin approval |
| P11 | Medium | `comment_per_post` setting change (e.g. 3→5) alters page size of feed |

### 19.2 Negative

| # | Priority | Scenario |
|---|----------|----------|
| N1 | Critical | Submit with all fields empty → client error, no request |
| N2 | Critical | Submit with invalid email → client error, no request |
| N3 | High | Bypass client → invalid email → `scriptlog_error`, no success, no DB row |
| N4 | High | Bypass client → bad name format → 400 "Please enter a valid name" |
| N5 | High | Submit without CSRF → 400 "Invalid CSRF token." |
| N6 | High | Replay same CSRF token twice → second fails |
| N7 | High | Admin edit with empty author → re-render with error, no change |
| N8 | High | Admin edit/reply with stale CSRF → 400 + logged exception |
| N9 | Medium | GET request to `comments-post.php` → 405 `Allow: POST` |
| N10 | Medium | Edit/delete non-existent comment → 404 |
| N11 | Medium | Reply action with `Id=0` → 302 to comments list |
| N12 | Medium | Reply to non-existent parent → 404 "parentCommentNotFound" |
| N13 | Medium | Delete non-existent reply → 404 |

### 19.3 Boundary

| # | Priority | Scenario |
|---|----------|----------|
| B1 | High | Name exactly 2 chars (valid) vs 1 char (invalid) |
| B2 | High | Comment exactly 320 chars (valid) vs 321 (client blocks; bypass → 400) |
| B3 | High | Email exactly 120 chars (server boundary) vs 121 |
| B4 | Medium | Name with `'`, `.`, `-`, space (allowed) vs `_` or digit (invalid) |
| B5 | Medium | Feed offset exactly at page boundary (3, 6 comments with limit 3) |
| B6 | Low | Comment count badge: 0, 1, 9, 10, 11 |

### 19.4 Edge cases

| # | Priority | Scenario |
|---|----------|----------|
| E1 | High | Post with 0 approved comments → empty container + "No More Comments" |
| E2 | High | Post `comment_status='closed'` → no form/section; also verify direct POST behaviour (flagged §14) |
| E3 | High | Submit with content containing HTML/script → stored purified, rendered inert |
| E4 | Medium | Double-click submit → single DB row, second response 400 |
| E5 | Medium | Two tabs submit with distinct tokens → both succeed; same token → second fails |
| E6 | Medium | Reply to a reply → parent lookup fails → 404 |
| E7 | Medium | Editing a comment that is actually a reply via editComment route → verify behaviour |
| E8 | Medium | Deleting a parent comment with replies → replies orphaned (no cascade) — verify list/count |
| E9 | Low | Duplicate `csrf` param (hidden + JS) → valid submit still works |
| E10 | Low | `post_id` tampered to non-numeric/negative → sanitized; no crash |

### 19.5 Regression

| # | Priority | Scenario |
|---|----------|----------|
| R1 | Critical | Existing approved comments + pagination still render after a comment edit/delete (cache clear) |
| R2 | High | Post page with comments still renders after CSRF refactor (token value present in hidden input) |
| R3 | Medium | Admin comments list, edit, reply pages load for all 6 roles (or 403 as documented) |
| R4 | Medium | RTL locale page loads comment UI without layout breakage |
| R5 | Low | Theme assets (`comment-submission.min.js`, `load-comment.min.js?v=1.2`) load with matching integrity hashes |

### 19.6 Security

| # | Priority | Scenario |
|---|----------|----------|
| S1 | Critical | Stored XSS via comment name/content → inert in public feed, admin list, edit form |
| S2 | Critical | SQL injection probes in `Id`, `post_id`, `offset` → 400/404/`[]`, no exception, no data leak |
| S3 | High | Payload whitelist: extra `comment_status=approved` / `is_admin` field → 413 |
| S4 | High | Public feed excludes `comment_author_ip` / `comment_author_email` |
| S5 | High | CSRF single-use on public form (replay) |
| S6 | High | Unauthorized roles (contributor/subscriber) → 403 on comments & reply screens |
| S7 | High | Anonymous direct access to `admin/index.php?load=comments` → redirect to login |
| S8 | Medium | Rapid-fire submits (rate limit gap) — document that none applies |
| S9 | Medium | Response bodies contain no stack traces / SQL errors |
| S10 | Low | IP header spoofing via `X-Forwarded-For` does not bypass (uses `RemoteAddress`); assert stored IP is the client IP |

### 19.7 Accessibility

| # | Priority | Scenario |
|---|----------|----------|
| A1 | High | Keyboard-only: tab to comment form, submit via Enter, tab to Load More |
| A2 | High | All form labels `for`/`id` matched; required marked |
| A3 | Medium | Focus ring visible on all interactive elements |
| A4 | Medium | Skip link jumps to main content |
| A5 | Medium | Invalid fields announce errors (flag `aria-invalid`/`role=alert` gaps) |
| A6 | Low | Table headers `scope=col`; admin action buttons have `aria-label` |

### 19.8 Performance

| # | Priority | Scenario |
|---|----------|----------|
| F1 | Medium | Post page with many approved comments: initial feed request returns within threshold; page loads under 2 s (development baseline) |
| F2 | Medium | Load More does not duplicate previously rendered comments |
| F3 | Low | No N+1: feed is a single query; admin list reply counts per row use `countReplies()` per comment (note potential N+1 — flag) |
| F4 | Low | Cache cleared only on comment mutations, not on reads |

---

## Appendix — Implementation Notes for Test Authors

- **Bootstrap**: start from `e2e/seed.spec.ts`; the seed is an empty Playwright scaffold.
  Test data must be seeded via the `blogware_test` DB (unprefixed tables) and/or the admin
  UI; record auto-increment IDs at runtime.
- **Deterministic paging**: set `comment_per_post` to 3 in
  `?load=option-reading` before paging tests, then restore.
- **CSRF freshness**: reload the public page immediately before each submit assertion to
  guarantee a live token; capture the token from the hidden `#csrf` input.
- **Bypassing client validation**: use `page.route`/`request.post` (or Playwright
  `request` fixture) to send raw POST bodies to `comments-post.php`.
- **JSON responses**: assert exact response codes and the presence of
  `success`/`success_message`/`error_message` keys.
- **Pending default**: after any public submit, confirm via DB (or admin list) the new row
  has `comment_status='pending'`.
- **Cleanup**: delete seeded comments/replies after the suite; never leave rows in
  `blogware_test` that break subsequent runs.
- **Open defects / gaps to raise**: no public rate limit, no honeypot on the comment form,
  no server-side `comment_status='open'` guard on `comments-post.php`, delete-parent does
  not cascade to replies, optional-chaining JS in `load-comment.js` (not transpiled),
  `role="alert"`/`aria-invalid` missing, possible N+1 in admin reply-count badges.
