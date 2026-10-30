# Posts & Categories (Admin + Frontend) — End-to-End Test Specification

> **Application**: Scriptlog (PHP 7.4+ / MariaDB / Bootstrap / jQuery)
> **Spec Version**: 1.1
> **Feature Owner**: Blogware Team
> **Bootstrap**: `e2e/seed.spec.ts`

---

## Table of Contents

1. [Overview](#1-overview)
2. [Datasets & Fixtures](#2-datasets--fixtures)
3. [Admin — Posts List](#3-admin--posts-list)
4. [Admin — Create New Post (Form & Layout)](#4-admin--create-new-post-form--layout)
5. [Admin — Create Post (Submission & Validation)](#5-admin--create-post-submission--validation)
6. [Admin — Create Post (Image Handling)](#6-admin--create-post-image-handling)
7. [Admin — Create Post (Protected / Password)](#7-admin--create-post-protected--password)
8. [Admin — Edit Post](#8-admin--edit-post)
9. [Admin — Edit Protected Post (Re-Encryption)](#9-admin--edit-protected-post-re-encryption)
10. [Admin — Delete Post](#10-admin--delete-post)
11. [Admin — Categories (Topics) List](#11-admin--categories-topics-list)
12. [Admin — Create Category](#12-admin--create-category)
13. [Admin — Edit Category](#13-admin--edit-category)
14. [Admin — Delete Category](#14-admin--delete-category)
15. [Authorization & Role-Based Access](#15-authorization--role-based-access)
16. [Frontend — Published Post Display](#16-frontend--published-post-display)
17. [Frontend — Draft / Scheduled / Private Posts Hidden](#17-frontend--draft--scheduled--private-posts-hidden)
18. [Frontend — Category Archive](#18-frontend--category-archive)
19. [Frontend — Blog Listing](#19-frontend--blog-listing)
20. [Security Expectations](#20-security-expectations)
21. [Accessibility](#21-accessibility)
22. [Responsive Behaviour](#22-responsive-behaviour)
23. [Browser Compatibility](#23-browser-compatibility)
24. [Failure Scenarios](#24-failure-scenarios)
25. [Recovery Behaviour](#25-recovery-behaviour)
26. [Screenshots to Capture](#26-screenshots-to-capture)
27. [Console & Network Expectations](#27-console--network-expectations)
28. [Test Scenarios](#28-test-scenarios)

---

## 1. Overview

### Purpose

Authors manage blog posts and their categories ("topics") through the admin panel.
Posts are written in a Summernote WYSIWYG editor, assigned to one or more categories,
optionally tagged, given a featured image, and set to `publish`, `draft`, or `scheduled`
status with `public`, `private`, or `protected` visibility. Scheduled posts are published
automatically when their `post_date` arrives (see §1 "How It Works", §4.3, §28). Categories
are simple name/slug/status
records that also act as the tag-autocomplete source in the post form. Published posts
render on the public site via permalink URLs (`/post/{id}/{slug}`) or query strings
(`?p={id}`); category archives render under `/category/{slug}` (or `?cat={id}`).

### Key Files Under Test

| Layer | File | Role |
|-------|------|------|
| Admin entry | `admin/posts.php` | Post action dispatch via `AdminActionRegistry` |
| Admin entry | `admin/topics.php` | Topic action dispatch via `AdminActionRegistry` |
| View | `admin/ui/posts/all-posts.php` | Posts list table + Add New / Edit / Delete |
| View | `admin/ui/posts/edit-post.php` | Create/Edit post form (Summernote, metadata, publishing box) |
| View | `admin/ui/posts/all-topics.php` | Categories list table |
| View | `admin/ui/posts/edit-topic.php` | Create/Edit category form |
| Layout | `admin/admin-layout.php` | Summernote init, tagsInput, datetimepicker, icheck, AJAX upload |
| Controller | `lib/controller/PostController.php` | `listItems`, `insert`, `update`, `remove`, CSRF + payload guards, scheduled-status dropdown wiring |
| Controller | `lib/controller/ConfigurationController.php` | `updateWritingSetting` (Settings → Writing, scheduled-posting toggle) |
| Controller | `lib/controller/TopicController.php` | `listItems`, `insert`, `update`, `remove` |
| Command | `lib/handler/admin/post/*.php` | `ListPostsCmd`, `NewPostCmd`, `EditPostCmd`, `DeletePostCmd` |
| Command | `lib/handler/admin/topic/*.php` | `ListTopicsCmd`, `NewTopicCmd`, `EditTopicCmd`, `DeleteTopicCmd` |
| Registry | `lib/handler/AdminActionRegistry.php` | Action-command mapping |
| Core | `lib/core/ActionConst.php` | Action constants (`newPost`, `editPost`, `deletePost`, `WRITING`, `WRITING_CONFIG`, etc.) |
| Service | `lib/service/PostService.php` | Post CRUD, media processing, author scoping |
| Service | `lib/service/PostApplicationService.php` | Filtering, image orchestration, protected encryption, `normalizePostStatus()` (publish/draft/scheduled) |
| Service | `lib/service/ScheduledPostService.php` | Per-request flip: promotes due `scheduled` posts to `publish` (see §1, §28) |
| Service | `lib/service/TopicService.php` | Topic CRUD + locale dropdown |
| DAO | `lib/dao/PostDao.php` | `tbl_posts` CRUD, post-topic link, `publishDueScheduledPosts()`, `nextScheduledPostDate()` |
| DAO | `lib/dao/ConfigurationDao.php` | `tbl_settings` key-value (scheduled-posting enabled/next-run) |
| DAO | `lib/dao/TopicDao.php` | `tbl_topics` CRUD, `setCheckBoxTopic()`, `checkTopicId()` |
| DAO | `lib/dao/MediaDao.php` | `imageUploadHandler()`, `dropDownMediaSelect()` |
| DTO | `lib/dto/PostRequestDto.php` | Encapsulates `$_POST` + `$_FILES` |
| Validator | `lib/validator/PostValidator.php` | Field limits, required fields, selectbox/date rules (statuses `publish`/`draft`/`scheduled`) |
| Validator | `lib/validator/FileUploadValidator.php` | Uploaded media validation |
| Validator | `lib/validator/ProtectedPostValidator.php` | Password rules for protected posts |
| Utility | `lib/utility/post-dropdown.php` | `post_status/comment_status/visibility/locale` dropdowns (+ `post_status_label()`) |
| Utility | `lib/utility/writing-settings.php` | `scheduled_post_enabled()` gate for the editor's Scheduled option |
| Utility | `lib/utility/csrf-defender.php` | `csrf_check_token` / `csrf_generate_token` |
| Utility | `lib/utility/form-security.php` | `check_form_request()` payload whitelist |
| Utility | `lib/utility/generate-request.php` | Admin action URL builder |
| Utility | `lib/utility/permalinks.php` | Frontend permalink/query-string URL builder |
| Bootstrap | `lib/main.php` | Runs `ScheduledPostService::publishDuePosts()` on every request before page render |
| Admin view | `admin/ui/setting/writing-setting.php` | Settings → Writing page (scheduled-posting toggle + info box) |
| Admin view | `admin/sidebar-nav.php` | "Writing" menu item (`load=option-writing`, gated by `ActionConst::WRITING`) |
| Front handler | `lib/handler/PostHandler.php` | `/post/{id}/{slug}` rendering + 404 |
| Front helper | `lib/core/FrontHelper.php` | Published post/topic/page lookups |
| Front dispatcher | `lib/core/Dispatcher.php` | `validateSinglePost()` slug check → 404 |
| Model | `lib/model/PostModel.php` | Frontend published-post queries (status+visibility filters) |
| Theme | `public/themes/blog/{single.php,home.php,functions.php}` | Post rendering, latest/featured/random lists |
| Schema | `install/include/dbtable.php` | `tbl_posts`, `tbl_topics`, `tbl_post_topic` DDL |

### How It Works (Summary)

1. **List**: `admin/posts.php?load=posts` → `ListPostsCmd` → `PostController::listItems()`.
   The author level decides the query scope: administrators see all posts, other roles only
   their own. Each row shows a **Status** badge (`publish`=green, `draft`=grey, `scheduled`=blue)
   via `post_status_label()`. Session flash `status`/`error` keys render success/error banners.
2. **Create**: `index.php?load=posts&action=newPost&Id=0` → `PostController::insert()`.
   On POST, the controller checks the CSRF token (`csrf_check_token('csrfToken', $_POST, 600)`),
   verifies the payload field whitelist (`check_form_request`, else 413), runs `PostValidator`
   (+ `FileUploadValidator`, + `ProtectedPostValidator` when protected), then delegates to
   `PostApplicationService::createPost()` which filters/distils `$_POST`, processes the image,
   encrypts content for protected posts, normalizes the status, and calls
   `PostService::addPost()`. On success it redirects to the list with `status=postAdded`.
3. **Edit**: `index.php?load=posts&action=editPost&Id={id}` → `EditPostCmd` (validates the ID
   exists via `PostDao::checkPostId()`, else 404) → `PostController::update()`. Protected posts
   are decrypted for the editor. On POST the same CSRF + payload + validation guards run, then
   `PostApplicationService::updatePost()` re-encrypts protected content in three branches.
4. **Delete**: `index.php?load=posts&action=deletePost&Id={id}` (navigated to from a JS
   `confirm()` dialog) → `DeletePostCmd` (400 for `Id<=0`) → `PostController::remove()` →
   `PostService::removePost()` deletes the post and its media files + record.
5. **Categories**: symmetric CRUD under `index.php?load=topics&action={newTopic|editTopic|deleteTopic}`.
   Title is slugified (`make_slug`); status is `Y`/`N` validated with `sanitize_selection_box`.
6. **Scheduling**: On create/update, `PostApplicationService::normalizePostStatus()` flips
   `publish` + future `post_date` → `scheduled` (auto-detect), and `scheduled` + past date →
   `publish` (due immediately). The editor's `Scheduled` status option is only rendered for
   `administrator` when `scheduled_post_enabled()` is true. On **every** request, `lib/main.php`
   runs `ScheduledPostService::publishDuePosts()`, which promotes due `scheduled` posts to
   `publish` (respecting the enabled setting + next-run shortcut). The feature can be toggled
   at `admin/index.php?load=option-writing` (Settings → Writing, `ActionConst::WRITING`).
7. **Frontend**: `single.php` renders a post only when `post_status='publish'` and
   `post_visibility IN ('public','protected')` (enforced in `FrontHelper`/`PostModel`).
   The Dispatcher 404s when the URL slug does not match the DB slug. Draft, scheduled, and
   private posts never appear on the frontend.

### URL Formats

| Resource | URL |
|----------|-----|
| Post list (admin) | `admin/index.php?load=posts` |
| New post (admin) | `admin/index.php?load=posts&action=newPost&Id=0` |
| Edit post (admin) | `admin/index.php?load=posts&action=editPost&Id={id}` |
| Delete post (admin) | `admin/index.php?load=posts&action=deletePost&Id={id}` |
| Category list (admin) | `admin/index.php?load=topics` |
| New category (admin) | `admin/index.php?load=topics&action=newTopic&Id=0` |
| Edit category (admin) | `admin/index.php?load=topics&action=editTopic&Id={id}` |
| Delete category (admin) | `admin/index.php?load=topics&action=deleteTopic&Id={id}` |
| Writing settings (admin) | `admin/index.php?load=option-writing` |
| Single post (SEO) | `/post/{id}/{slug}` |
| Single post (query) | `/?p={id}` |
| Category archive (SEO) | `/category/{slug}` |
| Category archive (query) | `/?cat={id}` |
| Blog listing | `/blog` |
| Tag archive | `/tag/{tag}` |
| Summernote image AJAX | `admin/media-upload.php` (POST, `image` + `csrfToken`) |
| Tag autocomplete | `admin/fetch-tags.php?term={q}` (GET) |

---

## 2. Datasets & Fixtures

### 2.1 Pre-seeded Admin Account

| Field | Value |
|-------|-------|
| Username | `administrator` |
| Password | `4dMin(*)^` |
| Role | `administrator` |

For role-based tests an additional lower-privilege account (e.g. `editor` / `contributor`)
must exist or be seeded in the bootstrap. Without one, the RBAC scenarios in §15 are skipped.

### 2.2 Fixture — New Post (canonical values)

| Field | Value |
|-------|-------|
| Title | `E2E Post Alpha` |
| Content | `<p>Hello from the automated test.</p>` |
| Meta description | `A short meta description for E2E.` |
| Status | `publish` |
| Visibility | `public` |
| Category | `E2E Category` |
| Tags | `alpha, beta` |
| Comment status | `open` |
| Featured | No (standard) |
| Locale | `en` |

### 2.3 Fixture — New Category

| Field | Value |
|-------|-------|
| Title | `E2E Category` |
| Status | `Y` (active) |
| Locale | `en` |
| Slug (auto) | `e2e-category` |

### 2.4 Expected Session Flash Keys (admin)

| Scenario | `$_SESSION` key | Redirect |
|----------|----------------|----------|
| Post created | `status=postAdded` → "New post added" | `index.php?load=posts&status=postAdded` |
| Post updated | `status=postUpdated` → "Post updated" | `index.php?load=posts&status=postUpdated` |
| Post deleted | `status=postDeleted` → "Post deleted" | `index.php?load=posts&status=postDeleted` |
| Post not found | `error=postNotFound` → "Error: Post Not Found!" | `index.php?load=posts&error=postNotFound` |
| Category added | `status=topicAdded` → "New cateogory added" | `index.php?load=topics&status=topicAdded` |
| Category updated | `status=topicUpdated` → "Category has been updated" | `index.php?load=topics&status=topicUpdated` |
| Category deleted | `status=topicDeleted` → "Category deleted" | `index.php?load=topics&status=topicDeleted` |
| Category not found | `error=topicNotFound` → "Error: Topic Not Found!" | `index.php?load=topics&error=topicNotFound` |
| Writing settings saved | `status=writingConfigUpdated` → "Writing settings have been updated successfully." | `index.php?load=option-writing&status=writingConfigUpdated` |

---

## 3. Admin — Posts List

### 3.1 Page Structure

- **URL**: `admin/index.php?load=posts`
- **Page title**: `Posts`
- **Header**: `<h1>Posts</h1>` with a small `Add New` button (`.btn.btn-primary`, `fa-plus-circle`)
  whose `href` resolves to `index.php?load=posts&action=newPost&Id=0`.
- **Breadcrumb**: `Home` (dashboard) → `Posts` → `Data Posts`.
- **Box header**: `<h2 class="box-title">` renders `{postsTotal} Post(s) in Total`
  (singular "Post" when exactly 1).
- **Table** `#scriptlog-table` (`table-bordered table-striped`), columns:
  `#`, `Title`, `Author`, `Date`, `Status`, `Edit`, `Delete`. The header row is repeated in `<tfoot>`.
- **Rows**:
  - `#`: 1-based sequence number.
  - Title: `safe_html($post['post_title'])`.
  - Author: `safe_html($post['user_login'])`.
  - Date: `make_date()` of `post_modified` when present, else `post_date`.
  - Status: `post_status_label($post['post_status'])` inside a `<span class="label">`;
    `label-success` for `publish`, `label-default` for `draft`, `label-primary` for `scheduled`.
  - Edit: `.btn.btn-warning` with `fa-pencil`, `title="Edit post"`, href =
    `index.php?load=posts&action=editPost&Id={id}`.
  - Delete: `.btn.btn-danger` with `fa-trash-o`, `title="Delete post"`, invoking
    `javascript:deletePost({id}, '{title}')`.
- **JS**: `deletePost(id, title)` calls `confirm("Are you sure want to delete Post '...'")`
  then navigates to `index.php?load=posts&action=deletePost&Id={id}`.
- **Flash banners**: `.alert-danger.alert-dismissible` (heading `Error!`, `fa-ban`) for errors,
  `.alert-success.alert-dismissible` (heading `Success!`, `fa-check`) for status. Both are
  dismissible via the `×` close button and rendered with `safe_html()`.

### 3.2 Expected Behaviours

| # | Action | Expected |
|---|--------|----------|
| 3.2.1 | Load the list unauthenticated | Redirected to `admin/login.php` |
| 3.2.2 | Load as a user without `posts` capability | 403 page (`index.php?load=403&forbidden=...`) |
| 3.2.3 | Load with zero posts | Box shows `0 Posts in Total`; empty tbody; no JS errors |
| 3.2.4 | Load with N posts | Row count equals N; count text matches N and pluralization |
| 3.2.5 | Click `Add New` | Navigates to `index.php?load=posts&action=newPost&Id=0` |
| 3.2.6 | Click an Edit (pencil) button | Navigates to `index.php?load=posts&action=editPost&Id={id}` |
| 3.2.7 | Click a Delete (trash) button | Native `confirm()` dialog appears with the post title |
| 3.2.8 | Cancel the confirm dialog | Stay on list; no navigation, no request |
| 3.2.9 | Accept the confirm dialog | Redirects to `deletePost` action (see §10) |
| 3.2.10 | Arrive with `?status=postAdded` | Green `Success!` banner "New post added" visible |
| 3.2.11 | Arrive with `?error=postNotFound` | Red `Error!` banner "Error: Post Not Found!" visible |
| 3.2.12 | Banner dismiss | Clicking `×` removes the banner from the DOM |
| 3.2.13 | Banner persistence | Banner disappears on next navigation (flash is unset) |
| 3.2.14 | Date column | Displays formatted date; uses `post_modified` over `post_date` when set |
| 3.2.15 | Title XSS attempt | Title stored as `<script>` is rendered inert via `safe_html` (see §20) |
| 3.2.16 | Status column | Badge matches each post's `post_status`: `Publish` (green), `Draft` (grey), `Scheduled` (blue) |
| 3.2.17 | Status of a scheduled post | A post saved with `scheduled` status shows the blue `Scheduled` badge in the list |

### 3.3 Notes / Known Quirks to Assert

- The `#` column is a row index, **not** the post ID.
- `deletePost()` embeds the title into a JS string; a title containing a `'` must not break
  the confirm dialog or cause a JS error (verify escaping behaviour).
- The list table has no pagination; all posts are rendered in one page.

---

## 4. Admin — Create New Post (Form & Layout)

### 4.1 Page Structure

- **URL**: `admin/index.php?load=posts&action=newPost&Id=0`
- **Page title**: `Add New Post`
- **Breadcrumb**: `Home` → `Posts` → `Add New Post`.
- **Layout**: Bootstrap grid — left `col-md-8` (Post Content box) and right `col-md-4`
  (Publishing Options box).
- **Form**: `method="post"`, `enctype="multipart/form-data"`, action =
  `index.php?load=posts&action=newPost&Id=0`. Hidden fields: `post_id=0`,
  `MAX_FILE_SIZE={APP_FILE_SIZE}`, `csrfToken`.
- **Submit button** (`.btn.btn-primary.pull-right`, `name="postFormSubmit"`):
  label `Publish`, icon `fa-paper-plane`, `aria-label="Publish Post"`.
- **Cancel button** (`.btn.btn-default`, `role="button"`): label `Cancel`, icon `fa-times`,
  href `index.php?load=posts`.

### 4.2 Left Column — Post Content

| Field | Element | Name | Attributes / Notes |
|-------|---------|------|--------------------|
| Title | `input#title` | `post_title` | `maxlength=200`, `required`, `aria-required=true`, placeholder `Enter title here`, label `Title *` |
| Content | `textarea#summernote` | `post_content` | `rows=10 cols=80`, `maxlength=500000`, `required`; upgraded to Summernote WYSIWYG |
| Meta Description | `textarea#meta_desc` | `post_summary` | `rows=3`, `maxlength=320`, help "Maximum 320 characters...", placeholder "Brief summary for search engines..." |
| Featured post | radio `post_headlines` | value `1` (`id=headlines`) | label "Yes, make this post featured" |
| Featured post | radio `post_headlines` | value `0` (`id=sticky`) | label "No, standard post"; default for a new post |
| Publication Date | `input#datetimepicker` | `post_date` | datetimepicker plugin (`data-time="1"` → `format: 'Y-m-d H:i:s'`; without it `'Y-m-d'`), placeholder `YYYY-MM-DD HH:MM:SS`, help "Pick a future date and time with status "Publish" to schedule this post, or a past date to backdate it." |
| Visibility | `select#visibility.system` | `visibility` | `onchange="checkVisibilitySelection()"`; options `Public`/`Private`/`Protected`; label "Post visibility" |
| Password (conditional) | `div#protected` (hidden) | `post_password` | `type=password`; shown only when visibility = Protected; help "Protected with a password you choose..." |
| Language | `select` | `post_locale` | options per `post_locale_dropdown()`; label "Language"; help "Select the language for this post." |

### 4.3 Right Column — Publishing Options

| Field | Element | Name | Notes |
|-------|---------|------|-------|
| Category | checkboxes `catID[]` | `catID` | from `TopicDao::setCheckBoxTopic()`; when no categories exist a single checkbox `name="catID" value="0"` labelled `Uncategorized` is rendered checked |
| Tags | `textarea#tags` | `post_tags` | `maxlength=200`; upgraded to tagsInput with `autocomplete_url: 'fetch-tags.php'`; `#suggesstion-box` present |
| Featured image | file input `#image-upload` | `media` | `accept="image/*"`, `maxlength=512`; preview `#image-preview`; help "Maximum file size: {APP_FILE_SIZE}" — rendered for non-contributor roles |
| Post status | `select` | `post_status` | options `Publish`/`Draft`; plus `Scheduled` only for `administrator` when `scheduled_post_enabled()` is true (see §4.4.13) |
| Comment status | `select` | `comment_status` | options `Open`/`Closed` |

> **Role difference**: A `contributor` role sees `dropDownMediaSelect()` (a select of
> previously-uploaded images) instead of the file-upload widget. Assert this only when a
> contributor account is available.

### 4.4 Expected Behaviours

| # | Action | Expected |
|---|--------|----------|
| 4.4.1 | Open the new-post form | Page title "Add New Post"; form action targets `newPost&Id=0` |
| 4.4.2 | Title field | Required; `maxlength=200` enforced on input |
| 4.4.3 | Content field | Rendered as Summernote editor; toolbar visible (style/font/para/insert/view) |
| 4.4.4 | Meta description | `maxlength=320` enforced; help text visible |
| 4.4.5 | Featured radio | "No, standard post" selected by default for a new post |
| 4.4.6 | Date picker | Opens calendar on focus; default empty for new post |
| 4.4.7 | Visibility select | Default `Public`; `#protected` password field hidden |
| 4.4.8 | Switch visibility to `Protected` | `#protected` div becomes visible (inline) |
| 4.4.9 | Switch visibility back to `Public`/`Private` | `#protected` div hides again |
| 4.4.10 | Category checkboxes | Rendered for each existing category; `Uncategorized` checked when no categories exist |
| 4.4.11 | Tags field | tagsInput active; typing triggers `fetch-tags.php` suggestions |
| 4.4.12 | Image widget | File input `accept="image/*"` present (non-contributor) |
| 4.4.13 | Post status select | Options `Publish` and `Draft`, default none selected; a third `Scheduled` option is present only for `administrator` accounts while `scheduled_post_enabled()` is true |
| 4.4.14 | Comment status select | Options `Open` and `Closed`, default none selected |
| 4.4.15 | CSRF token | Hidden `csrfToken` input present and non-empty |
| 4.4.16 | Cancel | Returns to `index.php?load=posts` |
| 4.4.17 | Submit label | `Publish` with paper-plane icon on new-post form |
| 4.4.18 | HTML5 required validation | Submitting with empty Title/Content triggers browser validation and no network request |
| 4.4.19 | Tab order | Logical order Title → Content → Meta → Featured → Date → Visibility → Language → right column → footer |

### 4.5 Notes / Known Quirks to Assert

- The `#datetimepicker` input is named `post_date` on the new form. On the edit form its name is
  `post_modified` **unless** the post is `scheduled` (or `post_modified` is unset), in which case
  it is `post_date` — assert the correct name per mode/status (see §8.1.8).
- The `data-time="1"` attribute makes the datetimepicker use `format: 'Y-m-d H:i:s'` (time
  picker); without it the plugin falls back to `'Y-m-d'`. The scheduled-posting feature depends
  on the time component.
- `checkVisibilitySelection()` reads `document.getElementById("visibility.system")` and sets
  `#protected` `display:inline`/`display:none`. The `#protected` div also contains a `<br/>`
  before the label.
- Summernote `onImageUpload` posts to `admin/media-upload.php` with `image` + `csrfToken`
  (`#csrf-token` hidden input) and, when `#post_id` has a value, a `post_id` field. On success
  it calls `summernote('insertImage', response.data.url)`. There is **no CSRF token input inside
  the summernote dialog** — the token comes from the page-level hidden input `#csrf-token`.
- The tagsInput plugin's autocomplete source is `fetch-tags.php`, which returns **category
  titles** (not tags) — a known data-source mismatch worth asserting (see §27).

---

## 5. Admin — Create Post (Submission & Validation)

### 5.1 Happy Path

| Step | Action | Expected |
|------|--------|----------|
| 5.1.1 | Fill Title `E2E Post Alpha` | Title input value matches |
| 5.1.2 | Fill Content `<p>Hello from the automated test.</p>` | Summernote content matches (value in hidden textarea) |
| 5.1.3 | Set Meta Description | Field value matches |
| 5.1.4 | Set Status = `Publish` | Selected |
| 5.1.5 | Set Visibility = `Public` | Selected |
| 5.1.6 | Check category `E2E Category` | Checkbox checked |
| 5.1.7 | Enter tags `alpha, beta` | tagsInput contains both tags |
| 5.1.8 | Set Comment status = `Open` | Selected |
| 5.1.9 | Click `Publish` | POST to `index.php?load=posts&action=newPost&Id=0` with `postFormSubmit` |
| 5.1.10 | Redirect | `index.php?load=posts&status=postAdded` (HTTP 200) |
| 5.1.11 | Success banner | Green "Success!" banner with "New post added" |
| 5.1.12 | List contains post | New row for `E2E Post Alpha` with correct author/date |
| 5.1.13 | DB assertions (via API/direct query) | `tbl_posts` row with `post_status='publish'`, `post_visibility='public'`, correct slug `e2e-post-alpha`, `post_author` = admin ID, `comment_status='open'`, `post_locale='en'` |
| 5.1.14 | Post-topic link | Row in `tbl_post_topic` linking post → `E2E Category` |
| 5.1.15 | Frontend | `/post/{id}/e2e-post-alpha` renders title + content (see §16) |

### 5.2 Server-Side Validation (bypassing HTML5 required)

Submit with `novalidate` or remove the `required` attributes (e.g. via `page.evaluate`) to
force server-side validation:

| # | Scenario | Expected server behaviour |
|---|----------|---------------------------|
| 5.2.1 | Missing title + content | `PostValidator` adds "Please enter a required field"; form re-renders with red "Invalid Form Data!" alert; no redirect |
| 5.2.2 | Title > 200 chars | `form_size_validation` triggers "Form data is longer than allowed" |
| 5.2.3 | Summary > 320 chars | "Form data is longer than allowed" |
| 5.2.4 | Content > 500000 chars | "Form data is longer than allowed" |
| 5.2.5 | `post_status` = invalid value (e.g. `hacked`) | `MESSAGE_INVALID_SELECTBOX` error; form re-rendered |
| 5.2.6 | `comment_status` = invalid | `MESSAGE_INVALID_SELECTBOX` error |
| 5.2.7 | `visibility` = invalid | `MESSAGE_INVALID_SELECTBOX` error |
| 5.2.8 | `post_date` = malformed (e.g. `not-a-date`) | "Please fix your date format" |
| 5.2.9 | `post_modified` = malformed | "Please fix your date format" |
| 5.2.10 | Validation failure — error display | `.alert-danger` box with heading "Invalid Form Data!" and each error as a `<p>` |
| 5.2.11 | Validation failure — values preserved | Re-rendered form keeps submitted Title/Content/Meta values (via `$formData`) |

### 5.3 CSRF & Payload Guards

| # | Scenario | Expected |
|---|----------|----------|
| 5.3.1 | Submit without `csrfToken` | HTTP 400; "Sorry, unpleasant attempt detected!" (AppException) |
| 5.3.2 | Submit with stale/expired token (>10 min) | HTTP 400 |
| 5.3.3 | Submit with a valid but single-use consumed token | HTTP 400 (token is invalidated after use) |
| 5.3.4 | Submit with unknown extra POST field (e.g. `evil=1`) | `check_form_request` fails → HTTP 413, `Retry-After: 3600` |
| 5.3.5 | Submit missing expected field (e.g. `post_status`) | `check_form_request` — missing keys pass the whitelist; validation errors render instead |
| 5.3.6 | Double-click submit | Second request reuses consumed token → 400; only one post row created |
| 5.3.7 | Token replay via copy-paste of full POST | 400; no duplicate row |

### 5.4 Notes / Known Quirks to Assert

- `check_form_request()` allows `csrfToken`, `postFormSubmit`, `MAX_FILE_SIZE` unconditionally
  and rejects any other unlisted key (413). The allowed set for create is: `post_id`,
  `post_title`, `post_content`, `post_date`, `image_id`, `catID`, `post_summary`, `post_tags`,
  `post_status`, `post_headlines`, `visibility`, `comment_status`, `post_password`, `post_locale`.
- The `catID` field passes through `FILTER_VALIDATE_INT` with `FILTER_REQUIRE_ARRAY` — a non-array
  `catID` may fail distillation; assert graceful handling (post still created, uncategorized fallback).
- `PostService::addPost()` auto-creates an `Uncategorized` topic when `catID == 0`.
- Status normalization is applied by `PostApplicationService::normalizePostStatus()` **after**
  validation: `publish` + future `post_date` → stored as `scheduled`; `scheduled` + past
  `post_date` → stored as `publish` (see §28 C-15/M-18).
- Success redirect uses HTTP 200 (`direct_page(..., 200)`).

---

## 6. Admin — Create Post (Image Handling)

### 6.1 No Image Selected (Default)

| # | Action | Expected |
|---|--------|----------|
| 6.1.1 | Create a post without selecting an image | Post created; `media_id` points to a media row whose `media_filename` is `nophoto.jpg` (via `processDefaultImage`) |
| 6.1.2 | `media_access` on created media | `public` when status = publish, `private` when draft/scheduled |
| 6.1.3 | Media metadata | `tbl_mediameta` row keyed by `nophoto.jpg` with Origin/File type/File size/Uploaded at/Dimension |

### 6.2 Valid Image Upload (non-contributor widget)

| # | Action | Expected |
|---|--------|----------|
| 6.2.1 | Choose a valid `.jpg`/`.png`/`.webp` under `APP_FILE_SIZE` | File accepted; `media` param present in multipart POST |
| 6.2.2 | Post created | `media_id` set to the new media record; `media_filename` = generated filename |
| 6.2.3 | Media meta | Dimension/File type/File size populated |
| 6.2.4 | List view | Post row shows; image preview available on edit |
| 6.2.5 | Invalid type (e.g. `.php` renamed `.jpg`) | `FileUploadValidator` rejects; form re-renders with error |
| 6.2.6 | Oversized file > `APP_FILE_SIZE` | Rejected (client `MAX_FILE_SIZE` + server validator); error shown |
| 6.2.7 | Empty/invalid file error code | Rejected; error rendered; no media row created |

### 6.3 Summernote Inline Image Upload (AJAX)

| # | Action | Expected |
|---|--------|----------|
| 6.3.1 | Click the image icon in the Summernote toolbar | File picker opens |
| 6.3.2 | Select an image | AJAX POST to `admin/media-upload.php` with `image` + `csrfToken` |
| 6.3.3 | Upload succeeds | `response.success` true with `data.url`; image inserted into editor |
| 6.3.4 | Upload fails (invalid/oversized) | `alert('Failed to upload image: ...')` fires; no image inserted |
| 6.3.5 | Upload without CSRF token | Server rejects (400); alert shown; editor unchanged |
| 6.3.6 | Upload while editing an existing post | `post_id` included in the AJAX payload (hidden `#post_id` populated) |

### 6.4 Notes / Known Quirks to Assert

- `upload_media()` moves the temp file and generates resized variants (large/medium/small);
  assert the variant files exist on disk for image posts.
- `processUploadedImage()` deletes the **previous** media record when replacing an image on edit.
- The `image-upload` input has `maxlength="512"` (attribute present on file inputs is unusual).

---

## 7. Admin — Create Post (Protected / Password)

### 7.1 Happy Path

| # | Action | Expected |
|---|--------|----------|
| 7.1.1 | Set visibility = `Protected` | `#protected` field becomes visible |
| 7.1.2 | Enter password `Secret123` | Field value set |
| 7.1.3 | Fill title + content + publish | Post created with `post_visibility='protected'` |
| 7.1.4 | DB assertions | `post_password` = bcrypt hash (not plaintext); `passphrase` = SHA-256(`app_key().password`) |
| 7.1.5 | Content stored | Encrypted (AES-256-CBC) in `post_content` — raw content not present in DB |
| 7.1.6 | Frontend | `/post/{id}/slug` shows lock form instead of content (see `protected-posts.md` for the unlock flow) |
| 7.1.7 | Admin re-edit | `decrypt_post_admin()` reveals plaintext content in Summernote (see §9) |

### 7.2 Validation

| # | Scenario | Expected |
|---|----------|----------|
| 7.2.1 | Visibility = Protected, empty password | `ProtectedPostValidator` rejects (if it enforces a password) or falls back; assert actual behaviour |
| 7.2.2 | Visibility = Protected, weak/short password | Validator rules applied per `ProtectedPostValidator`; assert messages |
| 7.2.3 | Visibility ≠ Protected but password filled | Password ignored; content stored in plain |
| 7.2.4 | Protected with valid password but empty content | Required-field error (content still required) |

### 7.3 Notes

- On create, `setPassPhrase()` stores `hash('sha256', app_key() . $passphrase)` in
  `passphrase`; `setProtected()` stores the bcrypt hash in `post_password`.
- `$_SESSION['post_protected']` is set to the raw password after a protected create.
- Full unlock/rate-limit/API behaviour is covered by `specs/protected-posts.md`; this spec
  only asserts the admin create path and stored fields.

---

## 8. Admin — Edit Post

### 8.1 Entry & Data Prefill

| # | Action | Expected |
|---|--------|----------|
| 8.1.1 | Click Edit on an existing post | `index.php?load=posts&action=editPost&Id={id}`; page title "Edit Post" |
| 8.1.2 | Edit a non-existent ID | 404 (`index.php?load=404&notfound=...`) via `EditPostCmd::checkPostId()` |
| 8.1.3 | Edit ID that is not an integer / `Id=-1` | `abs((int))` normalises; 404 if the post does not exist |
| 8.1.4 | Form prefill — Title | Existing title shown in `input#title` |
| 8.1.5 | Form prefill — Content | Existing content shown in Summernote (decrypted for protected posts) |
| 8.1.6 | Form prefill — Meta | Existing `post_summary` in `textarea#meta_desc` |
| 8.1.7 | Form prefill — Featured | Correct radio (`1`/`0`) checked per `post_headlines` |
| 8.1.8 | Form prefill — Date | `#datetimepicker` shows `post_modified` (falling back to `post_date` when null); for a `scheduled` post the field is named `post_date` and shows `post_date` |
| 8.1.9 | Form prefill — Visibility | Correct option selected; `#protected` shown iff `post_visibility='protected'` |
| 8.1.10 | Form prefill — Language | `post_locale` selected |
| 8.1.11 | Form prefill — Categories | Existing post's categories checked via `setCheckBoxTopic($getPost['ID'])` |
| 8.1.12 | Form prefill — Tags | Existing `post_tags` shown in tagsInput |
| 8.1.13 | Form prefill — Status/Comment | Existing `post_status`, `comment_status` selected |
| 8.1.14 | Form prefill — Image | Existing media preview + `Replace image` label (imageUploadHandler with mediaId) |
| 8.1.15 | Submit label | `Update` with `fa-save` icon, `aria-label="Update Post"` |
| 8.1.16 | Hidden fields | `post_id={id}`; `csrfToken` present |
| 8.1.17 | Cancel | Returns to `index.php?load=posts` |

### 8.2 Update Happy Path

| # | Action | Expected |
|---|--------|----------|
| 8.2.1 | Change title to `E2E Post Alpha v2` | Submitted |
| 8.2.2 | Change status to `draft` | Submitted |
| 8.2.3 | Click `Update` | POST to `index.php?load=posts&action=editPost&Id={id}` |
| 8.2.4 | Redirect | `index.php?load=posts&status=postUpdated` |
| 8.2.5 | Success banner | Green "Success!" banner "Post updated" |
| 8.2.6 | DB assertions | `post_title`, `post_status` updated; `post_slug` regenerated from the new title; `post_modified` updated to now |
| 8.2.7 | List reflects change | New title + date shown |
| 8.2.8 | Frontend | Draft post no longer visible on the frontend (see §17) |

### 8.3 Update Validation & Guards

| # | Scenario | Expected |
|---|----------|----------|
| 8.3.1 | Clear title (bypass required) | "Please enter a required field"; form re-rendered, values preserved |
| 8.3.2 | Malformed `post_modified` | "Please fix your date format" |
| 8.3.3 | Invalid status/comment/visibility values | `MESSAGE_INVALID_SELECTBOX` |
| 8.3.4 | No CSRF token | HTTP 400 |
| 8.3.5 | Extra unknown POST field | HTTP 413 |
| 8.3.6 | Concurrent edit by another admin | Last-write-wins; no optimistic locking — assert current behaviour |
| 8.3.7 | Update with a new featured image | Old media record deleted; new media row created; `media_id` updated |

### 8.4 Notes / Known Quirks to Assert

- `PostController::update()` calls `date_default_timezone_set()` using `timezone_identifier()`
  before rendering, affecting displayed dates.
- The hidden `post_id` and `MAX_FILE_SIZE` fields are present; `post_id` is used both by the
  server whitelist and by Summernote's AJAX upload.
- Category checkbox persistence relies on `findPostTopic($item['ID'], $postId)`.
- The edit-form payload whitelist (`PostController::update()`) adds `post_modified` to the
  create set: `post_id`, `post_title`, `post_content`, `post_modified`, `post_date`, `image_id`,
  `catID`, `post_summary`, `post_status`, `post_headlines`, `visibility`, `comment_status`,
  `post_password`, `post_tags`, `post_locale`.
- Editing a `scheduled` post keeps the `Scheduled` option selected only when the current user is
  an `administrator` and `scheduled_post_enabled()` is true; `post_status_dropdown()` renders it
  only under those conditions. Changing the date to the past normalizes status to `publish`.

---

## 9. Admin — Edit Protected Post (Re-Encryption)

The re-encryption logic lives in `PostApplicationService::setProtectedPostContent()` with
three branches.

| # | Scenario | Expected |
|---|----------|----------|
| 9.1 | Visibility stays `protected`, **new** password entered | Content re-encrypted with the new passphrase; `post_password` = bcrypt of new password; `passphrase` = SHA-256 of new password |
| 9.2 | Visibility stays `protected`, password left **empty** | Content re-encrypted with the **existing** `passphrase` (`encrypt($content, $existing['passphrase'])`) |
| 9.3 | Visibility changed to `public`/`private` | Content stored **plain**; `post_password`/`passphrase` no longer relevant |
| 9.4 | Edit form prefill | Content decrypted via `decrypt_post_admin($id)` — plaintext visible in Summernote |
| 9.5 | Save without touching content | No data loss; decrypted+re-encrypted content round-trips correctly |
| 9.6 | Frontend after password change | Old password no longer unlocks; new password unlocks (per `protected-posts.md`) |

---

## 10. Admin — Delete Post

### 10.1 Flow

| # | Action | Expected |
|---|--------|----------|
| 10.1.1 | Click Delete (trash) | `confirm()` dialog with the post title |
| 10.1.2 | Confirm | Navigates to `index.php?load=posts&action=deletePost&Id={id}` |
| 10.1.3 | Post exists | `PostController::remove()` deletes post + post-topic links; session `status=postDeleted`; redirect to list |
| 10.1.4 | Success banner | Green "Success!" banner "Post deleted" |
| 10.1.5 | Row removed | Post no longer in the list; `tbl_posts` row gone |
| 10.1.6 | Frontend | `/post/{id}/slug` returns 404 after deletion |
| 10.1.7 | Associated media | Media files + media record deleted by `PostService::removePost()` when the post owns a non-nophoto image |
| 10.1.8 | Associated comments | `deletePost` (DAO) removes related comments and post-topic rows |

### 10.2 Failure & Edge Cases

| # | Scenario | Expected |
|---|----------|----------|
| 10.2.1 | `Id=0` or negative | `DeletePostCmd` throws AppException → HTTP 400 "Invalid ID data type!" |
| 10.2.2 | Non-existent ID | `checkPostId()` false → 404 redirect (`load=404&notfound=...`) |
| 10.2.3 | ID not numeric (e.g. `Id=abc`) | `abs((int)` coerces to 0 → 400 |
| 10.2.4 | Cancel dialog | No request sent; page unchanged |
| 10.2.5 | Deletion of a post while unauthenticated | Redirect to login |
| 10.2.6 | Direct GET to delete URL | Executes the delete (no CSRF protection on this GET endpoint) — see §20.7 |

---

## 11. Admin — Categories (Topics) List

### 11.1 Page Structure

- **URL**: `admin/index.php?load=topics`
- **Page title**: `Categories` (from `TopicController::listItems()`).
- **Header**: `<h1>Categories</h1>` with `Add New` button (`fa-plus-circle`) linking directly to
  `index.php?load=topics&action=newTopic&Id=0` (hardcoded, not `generate_request`).
- **Breadcrumb**: `Home` → `Topics` → `Data Topics` — **label mismatch**: page title says
  "Categories", breadcrumb says "Topics". Assert and note.
- **Box header**: `{topicsTotal} Categories in Total` (singular `Category` when 1).
- **Table** `#scriptlog-table`: columns `#`, `Name`, `Link`, `Status`, `Edit`, `Delete`
  (header + tfoot).
- **Rows**:
  - Name: `safe_html($topic['topic_title'])`.
  - Link: `safe_html($topic['topic_slug'])`.
  - Status: `safe_html($topic['topic_status'])` (`Y`/`N`).
  - Edit: `.btn-warning` pencil → `generate_request('index.php','get',['topics','editTopic',{id}])`.
  - Delete: `.btn-danger` trash → `javascript:deleteTopic({id}, '{title}')`.
- **JS**: `deleteTopic(id, title)` confirms "Are you sure want to delete Topic '...'" then
  navigates to `index.php?load=topics&action=deleteTopic&Id={id}`.
- **Flash banners**: `.alert-danger` heading `Alert!` (errors) / `.alert-success` heading
  `Success!` (status). Rendered with `safe_html()`.

### 11.2 Expected Behaviours

| # | Action | Expected |
|---|--------|----------|
| 11.2.1 | Load unauthenticated | Redirect to login |
| 11.2.2 | Load without `topics` capability | 403 |
| 11.2.3 | Load with 0 categories | "0 Categories in Total"; empty tbody |
| 11.2.4 | Load with N categories | N rows; count matches |
| 11.2.5 | Click `Add New` | `index.php?load=topics&action=newTopic&Id=0` |
| 11.2.6 | Click Edit | `editTopic&Id={id}` |
| 11.2.7 | Click Delete | confirm dialog with category title |
| 11.2.8 | Cancel dialog | No navigation |
| 11.2.9 | Accept dialog | Delete flow (see §14) |
| 11.2.10 | Arrive with `status=topicAdded` | Green banner "New cateogory added" (sic — typo present in code) |
| 11.2.11 | Arrive with `error=topicNotFound` | Red banner "Error: Topic Not Found!" |
| 11.2.12 | Status column | Shows `Y` or `N` |

### 11.3 Notes / Known Quirks to Assert

- The success message "New **cateogory** added" is misspelled in the source — assert it as-is
  (documenting the bug rather than fixing).
- Breadcrumb says "Topics" while page title/box say "Categories" — inconsistent taxonomy
  labelling to record in the spec.
- Like posts, the `#` column is a row index, not the topic ID.

---

## 12. Admin — Create Category

### 12.1 Page Structure

- **URL**: `admin/index.php?load=topics&action=newTopic&Id=0`
- **Page title**: `Add New Category`.
- **Breadcrumb**: `Home` → `Categories` → `Add New Category`.
- **Box title**: `Add New Category` (since `$topic_id` is falsy).
- **Form**: `method="post"`, action `index.php?load=topics&action=newTopic&Id=0`, hidden
  `topic_id=0`, hidden `csrfToken`.
- **Fields**:
  - Title: `input#title` `name="topic_title"`, `required`, label `Title *`, placeholder
    `e.g. Technology, Lifestyle, News`, help "Enter a unique and descriptive name for this category."
  - Language: `select` `name="topic_locale"` (from `localeDropDown()`), label "Language", help
    "Select the language for this category."
  - Status radios are **not rendered on create** (only when `$topicData['topic_status']` exists).
- **Footer**: Cancel → `index.php?load=topics`; submit `name="topicFormSubmit"` label
  `Add Category` (icon `fa-plus`), `aria-label="Add New Category"`.

### 12.2 Expected Behaviours

| # | Action | Expected |
|---|--------|----------|
| 12.2.1 | Open form | Title input empty; no status radios; Language select present |
| 12.2.2 | Submit empty title (bypass required) | "Please enter title" error; re-renders; HTTP stays on form |
| 12.2.3 | Enter `E2E Category`, submit | POST to `newTopic&Id=0`; CSRF validated |
| 12.2.4 | Redirect | `index.php?load=topics&status=topicAdded` (HTTP 302) |
| 12.2.5 | Banner | Green "Success!" + "New cateogory added" |
| 12.2.6 | List contains category | Row with title `E2E Category`, slug `e2e-category`, status `Y` |
| 12.2.7 | DB assertion | `tbl_topics` row; `topic_status='Y'` default; `topic_locale='en'`; slug auto-generated |
| 12.2.8 | Duplicate title | Slug duplicates are NOT unique-checked on create — assert current behaviour (both rows created with same slug) |
| 12.2.9 | Title with special characters / spaces | Slugified (lowercase, hyphens) via `make_slug` |
| 12.2.10 | Non-ASCII title | Slug behaviour per `make_slug`; assert no 500 |
| 12.2.11 | CSRF missing | HTTP 400 "Sorry, unpleasant attempt detected!" |
| 12.2.12 | No `topicFormSubmit` | Form re-rendered, no DB write |
| 12.2.13 | Cancel | Returns to category list |

### 12.3 Notes

- `TopicController::insert()` sanitizes title with `Sanitize::severeSanitizer`, applies
  `prevent_injection` + `trim` + `distill_post_request`, then slugifies.
- Category title is the autocomplete source for the post-form tag input (`fetch-tags.php`
  queries `tbl_topics.topic_title`).

---

## 13. Admin — Edit Category

### 13.1 Page Structure

- **URL**: `admin/index.php?load=topics&action=editTopic&Id={id}`
- **Page title**: `Edit Topic` (from `TopicController::update()`).
- **Box title**: `Edit Category` (from template: `($topic_id) ? 'Edit Category' : 'Add New Category'`).
- **Breadcrumb**: `Home` → `Categories` → `Edit Topic`.
- **Fields**: Title (prefilled), **Active Status** radios `topic_status` (`Y` = "Yes, keep this
  category active" / `N` = "No, deactivate this category") — rendered because the topic has
  `topic_status`, Language (prefilled), hidden `topic_id`, hidden `csrfToken`.
- **Submit**: `name="topicFormSubmit"`, label `Update` (icon `fa-save`), `aria-label="Update Category"`.

### 13.2 Expected Behaviours

| # | Action | Expected |
|---|--------|----------|
| 13.2.1 | Open edit for existing topic | Title prefilled; status radio reflects DB value; language prefilled |
| 13.2.2 | Edit non-existent topic | `EditTopicCmd::checkTopicId()` false → 404 |
| 13.2.3 | Non-integer ID | 400 "Invalid ID data type" |
| 13.2.4 | Change title → `E2E Category Renamed`, submit | POST `editTopic&Id={id}`; redirect `status=topicUpdated` (302) |
| 13.2.5 | Banner | Green "Success!" + "Category has been updated" |
| 13.2.6 | Slug regenerated | `topic_slug` = slug of new title |
| 13.2.7 | Change status to `N` | Saved; list shows `N`; frontend category archive hidden (see §18) |
| 13.2.8 | Change status back to `Y` | Archive visible again |
| 13.2.9 | Empty title (bypass required) | "Please enter title" error; form re-rendered with values preserved |
| 13.2.10 | Invalid status value (e.g. `X`) | `sanitize_selection_box(['Y','N'])` fails → "Please choose the available value provided!" |
| 13.2.11 | CSRF missing/invalid | HTTP 400 |
| 13.2.12 | Cancel | Returns to category list |

### 13.3 Notes / Known Quirks to Assert

- Page title ("Edit Topic") and box title ("Edit Category") differ — assert both as-is.
- Status radios are only rendered for existing topics, so a create form has no status field.

---

## 14. Admin — Delete Category

### 14.1 Flow

| # | Action | Expected |
|---|--------|----------|
| 14.1.1 | Click Delete (trash) | `confirm()` with category title |
| 14.1.2 | Confirm | `index.php?load=topics&action=deleteTopic&Id={id}` |
| 14.1.3 | Topic exists | `TopicController::remove()` → `TopicService::removeTopic()` deletes; `status=topicDeleted`; redirect (302) |
| 14.1.4 | Banner | Green "Success!" + "Category deleted" |
| 14.1.5 | Row removed | Topic gone from list and `tbl_topics` |
| 14.1.6 | Post links | `tbl_post_topic` rows for the topic removed (DAO cascade) |

### 14.2 Failure & Edge Cases

| # | Scenario | Expected |
|---|----------|----------|
| 14.2.1 | Non-existent ID | `findTopicById()` false → error "Error: Topic not found" rendered on list view (no redirect in this path) |
| 14.2.2 | Non-integer / filtered `Id` | `filter_input(FILTER_SANITIZE_NUMBER_INT)` / `filter_var(FILTER_VALIDATE_INT)` fail → HTTP 400 |
| 14.2.3 | `Id` param absent | Controller body guarded by `isset($_GET['Id'])` — no action |
| 14.2.4 | Cancel dialog | No request |
| 14.2.5 | Direct GET to delete URL | Executes the delete (no CSRF protection on this GET endpoint) — see §20.7 |

---

## 15. Authorization & Role-Based Access

All admin commands guard with `$app->authenticator->userAccessControl(ActionConst::POSTS|TOPICS)`
and redirect to `403` when denied.

| # | Scenario | Expected |
|---|----------|----------|
| 15.1 | Anonymous access to `load=posts` | Redirect to login |
| 15.2 | Anonymous access to `load=posts&action=newPost` | Redirect to login |
| 15.3 | Anonymous access to `load=topics` | Redirect to login |
| 15.4 | Role without `posts` capability | 403 (`load=403&forbidden=...`) |
| 15.5 | Role without `topics` capability | 403 |
| 15.6 | Administrator | Sees **all** posts (`PostController::listItems` admin branch) |
| 15.7 | Author/editor role | Sees **only own** posts (`grabPosts('ID', authorId)`, `totalPosts([authorId])`) |
| 15.8 | Editor editing another author's post | No explicit ownership guard — assert current behaviour |
| 15.9 | Contributor image widget | `dropDownMediaSelect()` instead of upload handler |
| 15.10 | Unknown action (e.g. `action=hack`) | `AdminActionRegistry::has()` false → 404 (`load=404&notfound=...`) |
| 15.11 | Empty action | `default_post` / `default_topic` command used |

> RBAC scenarios requiring non-admin accounts are skipped when no such fixture exists in the
> bootstrap seed.

---

## 16. Frontend — Published Post Display

### 16.1 Single Post (SEO permalinks enabled)

| # | Action | Expected |
|---|--------|----------|
| 16.1.1 | Visit `/post/{id}/{slug}` for a published public post | HTTP 200; `single.php` renders |
| 16.1.2 | Title | Rendered in `<h1>`, HTML-escaped |
| 16.1.3 | Content | Rendered post-body (htmLawed-sanitized; inline styles/event handlers removed) |
| 16.1.4 | Author | `user_login`/`user_fullname` shown |
| 16.1.5 | Date | `make_date(post_modified)` shown |
| 16.1.6 | Category links | `link_topic(post_id)` renders category link(s) |
| 16.1.7 | Tags | `link_tag(post_id)` renders tag links |
| 16.1.8 | Comment count | `total_comment()` count displayed |
| 16.1.9 | Comments section | Rendered only when `comment_status='open'`; form posts to `comments-post.php` with CSRF |
| 16.1.10 | Prev/Next nav | `previous_post`/`next_post` links present |
| 16.1.11 | Featured image | Thumbnail rendered (or placeholder) |
| 16.1.12 | Slug mismatch (e.g. `/post/{id}/wrong-slug`) | Dispatcher `validateSinglePost()` → 404 |
| 16.1.13 | Non-numeric id (`/post/abc/foo`) | 404 |
| 16.1.14 | Missing id/slug | 404 |
| 16.1.15 | Deleted post | 404 |

### 16.2 Single Post (query-string mode, permalinks disabled)

| # | Action | Expected |
|---|--------|----------|
| 16.2.1 | Visit `/?p={id}` for a published post | Renders same single view |
| 16.2.2 | Visit `/?p={id}` for a non-existent post | 404 |

---

## 17. Frontend — Draft / Scheduled / Private Posts Hidden

| # | Scenario | Expected |
|---|----------|----------|
| 17.1 | Post set to `draft` | `grabPreparedFrontPostById` filters `post_status='publish'` → `/post/{id}/slug` is 404 |
| 17.2 | Post set to `private` | Filter `post_visibility IN ('public','protected')` → 404 |
| 17.3 | Post set to `scheduled` (future `post_date`) | `post_status='scheduled'` fails the `post_status='publish'` filter → `/post/{id}/slug` is 404 |
| 17.4 | Draft post in `/blog` listing | Not shown (`latest_posts`/`PostModel` filter `publish`) |
| 17.5 | Draft post in home "Latest Posts" | Not shown |
| 17.6 | Draft post in category archive | Not shown |
| 17.7 | Draft post in tag archive | Not shown |
| 17.8 | Draft post in RSS/feeds | Excluded (`getPostFeeds` requires `publish` + `public`) |
| 17.9 | Draft post in sitemap | Excluded (if sitemap uses published queries) |
| 17.10 | Scheduled post in `/blog`, home, archives, feeds, sitemap | Excluded everywhere until promoted to `publish` |

> **Note on promotion**: A scheduled post becomes visible on the frontend only after
> `ScheduledPostService::publishDuePosts()` promotes it (runs on the next request once
> `post_date` has passed). Asserting §17.10 requires either waiting past `post_date` or
> seeding a past-due scheduled post in the fixture and issuing one frontend request.

---

## 18. Frontend — Category Archive

| # | Action | Expected |
|---|--------|----------|
| 18.1 | Visit `/category/e2e-category` (SEO) | HTTP 200; lists published posts in that category |
| 18.2 | Visit `/?cat={id}` (query) | Same archive |
| 18.3 | Category with 0 posts | Empty-state handled (`nothing_found()` or empty list) without error |
| 18.4 | Category status `N` (inactive) | `grabPreparedFrontTopicBySlug` filters `topic_status='Y'` → 404 |
| 18.5 | Unknown slug | 404 via `validateCategory()` |
| 18.6 | Category links on post page | Navigate to correct archive URL |
| 18.7 | Pagination | Paginated posts per `post_per_page` reading setting |
| 18.8 | Canonical URL | Archive renders canonical tag for `/category/{slug}` |

---

## 19. Frontend — Blog Listing

| # | Action | Expected |
|---|--------|----------|
| 19.1 | Visit `/blog` | HTTP 200; blog listing rendered |
| 19.2 | Post cards | Show thumbnail, title, excerpt, date, category, comment count |
| 19.3 | Only published public posts | Draft/private/scheduled posts absent |
| 19.4 | Post count | Respects `post_per_page` reading setting |
| 19.5 | Card title link | Points to `/post/{id}/{slug}` (SEO) or `/?p={id}` |
| 19.6 | Excerpt | `paragraph_trim`/`safe_html` applied; no raw HTML injection |
| 19.7 | Home page "Latest Posts" | Mirrors the reading setting count; excludes drafts |

---

## 20. Security Expectations

| # | Check | Expected |
|---|-------|----------|
| 20.1 | SQL injection | All DAO queries use PDO prepared statements / bound parameters; no string-concatenated user input. Category `fetch-tags.php` uses a prepared LIKE query |
| 20.2 | XSS — title | `safe_html($post['post_title'])` in list; `htmlspecialchars(..., ENT_QUOTES)` in frontend — a `<script>` title must render inert |
| 20.3 | XSS — content | Stored content passed through `htmLawed` on the frontend (styles/event handlers denied); admin editor renders via Summernote |
| 20.4 | XSS — category title | `safe_html($topic['topic_title'])` in list |
| 20.5 | XSS — tag/comment fields | Output-escaped in templates |
| 20.6 | CSRF — create/edit post | `csrf_check_token('csrfToken', $_POST, 600)` — missing/stale/consumed token → 400 |
| 20.7 | CSRF — **delete post/category** | Delete runs on a plain GET link with no CSRF token — document as a known gap; test that a cross-site image/link could trigger it (this is a finding, not an expected-pass) |
| 20.8 | CSRF — Summernote image upload | `media-upload.php` requires `csrfToken` (assert a 4xx without it) |
| 20.9 | Payload integrity | `check_form_request()` whitelist — unknown fields → 413 |
| 20.10 | Authz | Every admin command calls `userAccessControl`; anonymous → login redirect; no-capability role → 403 |
| 20.11 | Path traversal | Media filename handling uses `basename()` + randomized generated filenames (`generate_filename`) |
| 20.12 | Upload validation | `FileUploadValidator` runs MIME/size checks; file inputs restricted to images |
| 20.13 | Protected content at rest | Encrypted (AES-256-CBC); password bcrypt-hashed; never stored plaintext |
| 20.14 | Sensitive output | No stack traces / raw DB errors in responses (handled via `LogError` + generic pages) |
| 20.15 | Unauthorized category deletion effects | Deleting a category does not delete its posts, only the link rows — assert posts survive |
| 20.16 | Numeric abuse | `Id` coerced via `abs((int))` and validated (`check_integer`, `FILTER_VALIDATE_INT`) → 400/404 |
| 20.17 | Scheduled-posting authorization | The `Scheduled` status option and Writing settings page are admin-gated (`user_level === 'administrator'`, `ActionConst::WRITING`); scheduled posts stay `post_status='scheduled'` in DB and are never exposed on the frontend until promoted |
| 20.18 | Scheduled-posting enable gate | `scheduled_post_enabled()` reads `tbl_settings.writing_scheduled_post_enabled`; when disabled, the `Scheduled` option is hidden and `publishDuePosts()` short-circuits (no promotion) |

---

## 21. Accessibility

| # | Check | Expected |
|---|-------|----------|
| 21.1 | Form labels | All inputs (`#title`, `#summernote`, `#meta_desc`, tags, selects) have `<label>`; required marked with `*` and `aria-required="true"` |
| 21.2 | Select labels | Visibility/status/comment/language selects each wrapped in a labelled `.form-group` with `<label>` |
| 21.3 | Icon-only buttons | Edit/Delete icons carry `title` attributes; submit buttons carry `aria-label` |
| 21.4 | Cancel link | `role="button"` + `aria-label="Cancel and return to posts list"` |
| 21.5 | Alerts | Error/success banners use semantic headings (`<h2>`/`<h4>`) and are readable by screen readers; edit-post alert has `role="alert"` |
| 21.6 | Tables | `#scriptlog-table` has `aria-describedby`; header + tfoot provided |
| 21.7 | Focus visibility | Keyboard focus visible on all interactive elements |
| 21.8 | Dialog (delete) | Uses native `confirm()` — focus returns to trigger after dismissal |
| 21.9 | Keyboard operation | Form fully operable via keyboard (Tab order, Enter to submit) |
| 21.10 | Colour contrast | Status/banner text meets AA contrast |
| 21.11 | Summernote editor | Editor is a `<textarea>` fallback (screen readers see the textarea); toolbar buttons have aria semantics per plugin defaults |
| 21.12 | Multiple `id` collisions | `#image_id`, `#title`, `#summernote`, `#post_id`, `#csrf-token` must each be unique on the page (assert no duplicate ids) |
| 21.13 | iCheck radios | `post_headlines` radios hidden by iCheck — assert keyboard reachability still works |

---

## 22. Responsive Behaviour

| # | Viewport | Check | Expected |
|---|----------|-------|----------|
| 22.1 | ≥992px | List tables | Full 7 columns visible |
| 22.2 | <992px | Create/Edit form | Left/right columns stack (col-md-8 + col-md-4 collapse to single column) |
| 22.3 | <768px | Tables | `table-responsive` wrapper scrolls horizontally without breaking layout |
| 22.4 | <768px | Buttons | Add New/Cancel/Submit remain tappable (≥44px hit target recommended) |
| 22.5 | <576px | Summernote | Toolbar wraps; editor usable |
| 22.6 | Mobile | Delete confirm | Native dialog works on touch |
| 22.7 | Mobile | Image preview | Responsive image (`.img-responsive.pad`) scales |

---

## 23. Browser Compatibility

Test matrix (desktop + mobile):

| Browser | Min version |
|---------|-------------|
| Chrome / Chromium (Playwright default) | latest |
| Firefox | latest |
| Safari | 14+ |
| Edge (Chromium) | latest |

| # | Area | Notes |
|---|------|-------|
| 23.1 | Summernote | Full toolbar + image upload must work cross-browser |
| 23.2 | tagsInput | Autocomplete + Enter-key tag creation |
| 23.3 | datetimepicker | Opens/closes; date input accepted |
| 23.4 | iCheck | Radio toggling reflects in `post_headlines` |
| 23.5 | Native `confirm()` | Playwright `page.on('dialog')` handler required |
| 23.6 | CSP nonce | Inline scripts carry `nonce`; admin scripts must not be blocked by CSP |
| 23.7 | SameSite cookies | Session persists through post submit (SameSite=Strict cookies sent on same-origin POST) |

---

## 24. Failure Scenarios

| # | Scenario | Expected |
|---|----------|----------|
| 24.1 | DB down | `LogError::exceptionHandler` catches; generic error page; no stack trace |
| 24.2 | CSRF token expired while typing (10-min window) | Submit → 400 "Sorry, unpleasant attempt detected!"; user loses input (document behaviour) |
| 24.3 | Upload of a corrupt/empty file | Validator rejects; no media row; form re-renders |
| 24.4 | Title exactly 200 chars / content 500000 chars | Accepted at boundary (assert no off-by-one rejection) |
| 24.5 | Title 201 chars | Rejected "Form data is longer than allowed" |
| 24.6 | Double submit of the delete GET | Second call → 404 (post already gone) |
| 24.7 | Editing a post deleted by another admin | `EditPostCmd::checkPostId()` false → 404 |
| 24.8 | Category rename while posts are linked | Links preserved via `topic_id`; slug changes; frontend archive URL changes |
| 24.9 | Deleting a category with linked posts | Posts kept; link rows removed; posts become uncategorized-capable (assert list state) |
| 24.10 | Special-char title (quotes/apostrophes) | Escaped in list, edit prefill, and delete-confirm JS |
| 24.11 | Very long post content | Summernote handles; DB `longtext` accepts |
| 24.12 | Uploading a non-image via drag into Summernote | `media-upload.php` rejects; alert shown |
| 24.13 | Concurrent sessions editing the same post | Last write wins; no lock error (assert current behaviour) |

---

## 25. Recovery Behaviour

| # | Scenario | Expected |
|---|----------|----------|
| 25.1 | Validation failure on create | Form re-rendered with entered values + error banner; user can correct and resubmit with a fresh token |
| 25.2 | Validation failure on edit | Same; `postData` + `errors` re-rendered; values preserved |
| 25.3 | 400 on CSRF failure | Browser back → form re-renders with a **new** token; resubmit succeeds |
| 25.4 | 404 on post-not-found | User can navigate back to the list; banner not shown (error path) |
| 25.5 | 413 payload | Retry-After header; user must return to the form and resubmit |
| 25.6 | Post deleted accidentally | No undo; must recreate (document; assert a new post can be created with same title) |
| 25.7 | Category deleted with posts | Posts remain; re-create the category to relink |
| 25.8 | Image upload partial failure | No orphan media row left behind (assert media table state after a rejected upload) |

---

## 26. Screenshots to Capture

| # | View | Notes |
|---|------|-------|
| 26.1 | Posts list (populated) | Success banner visible (`?status=postAdded`) |
| 26.2 | Posts list (error banner) | `?error=postNotFound` |
| 26.3 | Posts list (empty) | 0 Posts in Total |
| 26.4 | New Post form — top half | Title + Summernote + meta |
| 26.5 | New Post form — bottom half | Publishing options, categories, tags, image, status |
| 26.6 | Visibility = Protected | Password field visible |
| 26.7 | Edit Post (prefilled) | All fields populated |
| 26.8 | Edit Post — validation error | "Invalid Form Data!" alert |
| 26.9 | Categories list | Populated with status column |
| 26.10 | New Category form | Title + language |
| 26.11 | Edit Category | Title + status radios |
| 26.12 | Delete confirm dialog | Native browser dialog |
| 26.13 | Frontend single post | Published post rendering |
| 26.14 | Frontend category archive | Published posts under category |
| 26.15 | Blog listing | Latest posts cards |
| 26.16 | Mobile: New Post form | Stacked layout |
| 26.17 | Posts list with a scheduled post | Blue `Scheduled` badge visible |
| 26.18 | Writing settings (`load=option-writing`) | Scheduled-posting toggle + info box |

---

## 27. Console & Network Expectations

### 27.1 Console

| # | Check | Expected |
|---|-------|----------|
| 27.1.1 | Posts list | No JS errors |
| 27.1.2 | Create form | No Summernote/tagsInput init errors |
| 27.1.3 | Visibility toggle | `checkVisibilitySelection` no errors; `#protected` toggles |
| 27.1.4 | Delete confirm | No errors after confirm/cancel |
| 27.1.5 | Summernote image upload | Success path logs no errors; failure path only the alert |
| 27.1.6 | Frontend pages | No console errors; htmLawed sanitization silent |

### 27.2 Network

| # | Request | Expected |
|---|---------|----------|
| 27.2.1 | GET `load=posts` | 200 HTML |
| 27.2.2 | GET `load=posts&action=newPost&Id=0` | 200 HTML (form) |
| 27.2.3 | POST create post (valid) | 302/200 to `status=postAdded`; `Content-Type: text/html` |
| 27.2.4 | POST with missing CSRF | 400 |
| 27.2.5 | POST with unknown field | 413 + `Retry-After: 3600` |
| 27.2.6 | GET `action=deletePost&Id={valid}` | 302/200 to list with `status=postDeleted` |
| 27.2.7 | GET `action=deletePost&Id=0` | 400 |
| 27.2.8 | GET `action=deletePost&Id={missing}` | 404 |
| 27.2.9 | POST category create (valid) | 302 to `status=topicAdded` |
| 27.2.10 | GET `fetch-tags.php?term={q}` (authed) | 200 JSON array of category titles |
| 27.2.11 | GET `fetch-tags.php?term={q}` (unauthed) | 405 "Sorry, Method Not Allowed" |
| 27.2.12 | POST `media-upload.php` (valid) | 200 JSON `{success:true,data:{url}}` |
| 27.2.13 | POST `media-upload.php` (no CSRF) | 4xx |
| 27.2.14 | GET `/post/{id}/{slug}` (published) | 200 |
| 27.2.15 | GET `/post/{id}/{wrong-slug}` | 404 |
| 27.2.16 | GET `/category/{slug}` | 200 |
| 27.2.17 | GET `/category/{inactive-slug}` | 404 |
| 27.2.18 | POST `load=option-writing&action=writingConfig&Id=0` (valid) | 302 to `?load=option-writing&status=writingConfigUpdated` |
| 27.2.19 | GET `/post/{id}/{slug}` (scheduled post, not yet due) | 404 |

---

## 28. Test Scenarios

Prioritized list for the Playwright suite. All admin steps assume the `administrator` account
is logged in (or perform login first).

### CRITICAL

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| C-01 | Create a published public post | Navigate to new-post; fill Title/Content; status Publish; visibility Public; category; tags; submit | Redirect to `status=postAdded`; green banner; row in list |
| C-02 | Create a draft post | As C-01 with status Draft | Redirect `postAdded`; row shows; **frontend 404** for its permalink |
| C-03 | Edit an existing post | Open edit; change title+status to publish; update | Redirect `postUpdated`; banner; list updated; frontend renders new slug |
| C-04 | Delete a post (confirm) | Click trash; accept dialog | Redirect `postDeleted`; banner; row gone; frontend 404 |
| C-05 | Delete a post (cancel) | Click trash; dismiss dialog | No navigation; no request |
| C-06 | Create a category | New category form; submit title | `topicAdded` banner; row with auto slug |
| C-07 | Edit a category | Change title + status to N | `topicUpdated` banner; list shows N |
| C-08 | Delete a category | Confirm dialog | `topicDeleted` banner; row gone; linked posts survive |
| C-09 | CSRF on create post | Remove `csrfToken` via JS; submit | HTTP 400; no row created |
| C-10 | Payload whitelist on create | Add `evil=1` hidden field; submit | HTTP 413 + Retry-After |
| C-11 | Anonymous access to posts admin | Not logged in; visit `load=posts` | Redirect to login |
| C-12 | Unauthorized role → 403 | Login as non-privileged role (fixture permitting); visit `load=posts` | 403 page |
| C-13 | Unknown action → 404 | Visit `load=posts&action=nope` | 404 page |
| C-14 | Validate required fields (server side) | `novalidate` submit with empty title+content | "Please enter a required field"; form re-rendered |
| C-15 | Create a scheduled post | As C-01 but set a **future** `post_date`; keep status `Publish` | Redirect `postAdded`; DB row `post_status='scheduled'`; list shows blue `Scheduled` badge; **frontend 404** for its permalink (§17.3) |
| C-16 | Due scheduled post publishes on next request | Seed a post with `post_status='scheduled'` and `post_date` in the past; issue one frontend request | `ScheduledPostService` promotes it → `/post/{id}/{slug}` returns 200 with the post |

### HIGH

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| H-01 | Protected post create | Visibility Protected + password | `post_visibility='protected'`; content encrypted; frontend lock form |
| H-02 | Protected post edit — new password | Edit; change password; save | Re-encrypted with new passphrase; old password fails on frontend |
| H-03 | Protected post edit — no password | Edit; save without touching password | Re-encrypted with existing passphrase; content intact |
| H-04 | Protected post unprotect | Change visibility to Public; save | Content stored plain; frontend renders content |
| H-05 | Slug mismatch 404 | Visit `/post/{id}/wrong-slug` | 404 |
| H-06 | Non-existent post 404 | Visit `/post/999999/x` | 404 |
| H-07 | Post with image upload | Attach valid JPG; publish | Media row + variant files created; image shown on edit/frontend |
| H-08 | Post image invalid type | Attach renamed `.php` | Rejected; error alert; no media row |
| H-09 | Summernote AJAX image upload | Insert image in editor | POST `media-upload.php`; image inserted into content |
| H-10 | Summernote AJAX without CSRF | Stub token empty; upload | 4xx; alert shown |
| H-11 | Invalid selectbox values | Set `post_status=evil` via JS; submit | `MESSAGE_INVALID_SELECTBOX` error |
| H-12 | Field length overflow | Title 201 chars | "Form data is longer than allowed" |
| H-13 | Malformed date | Set `post_date=bad` | "Please fix your date format" |
| H-14 | XSS in title | Title = `<script>alert(1)</script>`; publish | Renders inert in list + frontend; no alert fires |
| H-15 | Draft excluded from blog listing | Create draft; visit `/blog` and home | Post absent |
| H-16 | Private post 404 | Create private; visit permalink | 404 |
| H-17 | Category inactive → archive 404 | Set category status N; visit `/category/{slug}` | 404 |
| H-18 | Category archive lists posts | Publish post in category; visit archive | Post listed |
| H-19 | Delete post deletes media | Post with image; delete post | Media files + record removed |
| H-20 | Delete category keeps posts | Delete category of a published post | Post still published; archive of that category 404 |
| H-21 | Scheduled option gated by feature | Disable scheduled posting (`load=option-writing`, uncheck, save) | New/edit post status select shows only `Publish`/`Draft`; `Scheduled` option absent |
| H-22 | Scheduled post edit — past date flips to publish | Edit a scheduled post; set `post_date` to the past; save with status `scheduled` | `normalizePostStatus` → stored as `publish`; badge becomes `Publish`; frontend 200 |
| H-23 | Editing a scheduled post keeps `Scheduled` selected | Admin opens a scheduled post's edit form | `post_status` select shows `Scheduled` selected; date field named `post_date`; frontend stays 404 |

### MEDIUM

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| M-01 | Posts list count + pluralization | 0 posts → "0 Posts in Total"; 1 → "1 Post in Total" | Text matches |
| M-02 | List order | Posts ordered by ID DESC | Newest first |
| M-03 | Author scoping | Non-admin sees only own posts (fixture permitting) | Row count reflects ownership |
| M-04 | Tag autocomplete | Type in tags field | `fetch-tags.php` JSON of category titles; suggestions appear |
| M-05 | `fetch-tags.php` unauthenticated | Visit directly without session | 405 |
| M-06 | Visibility toggle UI | Switch Protected↔Public | Password field shows/hides |
| M-07 | Cancel buttons | Create/edit forms | Return to respective lists |
| M-08 | Update without image change | Edit post; leave image untouched | `media_id` preserved; no new media row |
| M-09 | Replace image on edit | Attach new image | Old media deleted; new row; `media_id` updated |
| M-10 | Duplicate category titles | Create two categories with same title | Both created (duplicate slug not rejected) |
| M-11 | Special-character title round trip | Title with `' " &` | Escaped correctly in list, edit, delete-confirm |
| M-12 | Very long content | Content near 500000 chars | Saves; renders on frontend |
| M-13 | Query-string post view | `/?p={id}` (permalinks off fixture) | Same single post renders |
| M-14 | Comments section condition | Post with comment_status open vs closed | Form present only when open |
| M-15 | Featured post selection | Set featured=1; publish | `post_headlines=1` in DB; appears in featured section |
| M-16 | Post locale | Set locale `fr`; save | `post_locale='fr'` persisted; select reflects on edit |
| M-17 | Writing settings toggle persists | `load=option-writing`; toggle checkbox; save | `status=writingConfigUpdated` banner; `tbl_settings.writing_scheduled_post_enabled` reflects the choice; option re-renders checked/unchecked |
| M-18 | `Publish` + future date auto-schedules | Create with status `Publish` + a future `post_date` | Stored as `post_status='scheduled'` (no manual status change needed); list badge `Scheduled`; frontend 404 |
| M-19 | Scheduled option hidden for non-administrator | Log in as editor/author (fixture permitting); open new-post | Status select shows only `Publish`/`Draft` (no `Scheduled`) |

### LOW / EDGE

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| L-01 | Delete with Id=0 | Navigate `action=deletePost&Id=0` | HTTP 400 |
| L-02 | Edit with Id=0 | Navigate `action=editPost&Id=0` | 404 (checkPostId fails) |
| L-03 | Delete non-integer Id | `action=deletePost&Id=abc` | 400 (coerced to 0) |
| L-04 | Empty action | `load=posts` with no action | default list renders |
| L-05 | Token replay | Capture POST; resubmit twice | First succeeds; second 400 |
| L-06 | Double-click publish | Rapid double click | Single row created; second request 400 |
| L-07 | Uncategorized fallback | Create post with no categories selected | `Uncategorized` topic auto-created + linked |
| L-08 | Category edit — invalid status | Set `topic_status=X` via JS | "Please choose the available value provided!" |
| L-09 | Category create — empty title (server) | `novalidate` submit | "Please enter title" |
| L-10 | Unknown category slug | `/category/does-not-exist` | 404 |
| L-11 | Blog listing pagination | Publish > `post_per_page` posts | Pagination controls appear |
| L-12 | Deleting post with comments | Post with a comment; delete | Comments cascade-deleted (assert no orphans) |
| L-13 | Editing post deleted concurrently | Open edit; delete via second tab; save | 404 on update (checkPostId re-run not performed on POST — document) |
| L-14 | Id with leading/trailing spaces | `Id= 5 ` | Coerced; behaves as 5 |
| L-15 | Apostrophe in post title delete-confirm | Title `Bob's Post`; open confirm | Dialog renders; confirm navigates correctly |
| L-16 | Direct GET to Writing settings without `WRITING` capability | Visit `load=option-writing` as non-privileged role | 403 (or redirect), per `userAccessControl(ActionConst::WRITING)` |

### Cross-Cutting (apply to all scenarios above)

- Log `console` errors and `failed`/`4xx`/`5xx` requests per scenario.
- Capture screenshots on failure.
- After each CRUD scenario, assert the DB state via direct query (or API) for the row created,
  updated, or deleted.
- Assert the redirect URL, HTTP status code, and flash banner text for every state change.
- For frontend scenarios, assert the HTTP status (200 vs 404) and absence of draft/scheduled/private posts.
- For scheduled-post scenarios (C-15, C-16, M-18, H-22), the fixture should seed/expect
  `tbl_settings.writing_scheduled_post_enabled = '1'` and `writing_scheduled_next_run`; the
  per-request flip runs on any request (frontend or admin) once `post_date` has passed.
