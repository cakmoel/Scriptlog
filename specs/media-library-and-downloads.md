# Media Library & Downloads — End-to-End Test Specification

> **Application**: Scriptlog (PHP 7.4+ / MariaDB / Bootstrap / jQuery)
> **Spec Version**: 1.0
> **Feature Owner**: Blogware Team
> **Bootstrap**: `e2e/seed.spec.ts`

---

## Table of Contents

1. [Overview](#1-overview)
2. [Datasets & Fixtures](#2-datasets--fixtures)
3. [Admin — Media Library List](#3-admin--media-library-list)
4. [Admin — Upload New Media](#4-admin--upload-new-media)
5. [Admin — Edit Media](#5-admin--edit-media)
6. [Admin — Delete Media](#6-admin--delete-media)
7. [Admin — Downloads Management](#7-admin--downloads-management)
8. [Admin — Download History](#8-admin--download-history)
9. [Admin — Download Settings](#9-admin--download-settings)
10. [Admin — Summernote AJAX Upload Endpoint](#10-admin--summernote-ajax-upload-endpoint)
11. [Frontend — Download Page (Success State)](#11-frontend--download-page-success-state)
12. [Frontend — Download Page (Error States)](#12-frontend--download-page-error-states)
13. [Frontend — Direct File Download](#13-frontend--direct-file-download)
14. [Frontend — Copy Share Link](#14-frontend--copy-share-link)
15. [Security Expectations](#15-security-expectations)
16. [Accessibility](#16-accessibility)
17. [Responsive Behaviour](#17-responsive-behaviour)
18. [Browser Compatibility](#18-browser-compatibility)
19. [Failure Scenarios](#19-failure-scenarios)
20. [Recovery Behaviour](#20-recovery-behaviour)
21. [Screenshots to Capture](#21-screenshots-to-capture)
22. [Console & Network Expectations](#22-console--network-expectations)
23. [Test Scenarios](#23-test-scenarios)

---

## 1. Overview

### Purpose

The Media Library allows administrators to upload, organise, edit, and delete media files
(images, audio, video, and documents). Files marked **"Display on: Download"** get a
time-limited, tokenised download link (UUID identifier) that can be shared publicly. The
Downloads admin page lets admins manage those links — expire, regenerate, or delete them
individually or in bulk, and view per-file download history. A Download Settings page
controls allowed MIME types, link expiration hours, hotlink protection, allowed referrer
domains, and an optional support/donation button.

### Key Files Under Test

| Layer | File | Role |
|-------|------|------|
| Admin Page | `admin/medialib.php` | Media library entry point (list/upload/edit/delete dispatch) |
| Admin Page | `admin/downloads.php` | Downloads management entry (expire/regenerate/delete/history/bulk/createLink) |
| Admin Page | `admin/option-downloads.php` | Download settings entry (CONFIGURATION gate) |
| Admin Upload | `admin/media-upload.php` | Summernote AJAX image upload endpoint (JSON) |
| Controller | `lib/controller/MediaController.php` | Media CRUD; `TIME_BEFORE_EXPIRED = 8` hours |
| Controller | `lib/controller/DownloadAdminController.php` | Admin download management |
| Controller | `lib/controller/DownloadController.php` | Frontend download page + file streaming |
| Controller | `lib/controller/ConfigurationController.php` | `updateDownloadSetting()` |
| Service | `lib/service/MediaService.php` | Media business logic + dropdowns |
| Service | `lib/service/DownloadService.php` | Download records, expiry, logging, statistics |
| Model | `lib/model/DownloadModel.php` | `tbl_media_download` / `tbl_download_log` queries |
| Utility | `lib/utility/download-handler.php` | `DownloadUtility` — streaming, validation, headers |
| Utility | `lib/utility/download-settings.php` | `DownloadSettings` — config keys, defaults |
| Utility | `lib/utility/get-download-link.php` | `get_download_link()` URL generation |
| Handler | `lib/handler/DownloadHandler.php` | Frontend route → page render or file stream |
| Frontend | `lib/core/HandleRequest.php`, `lib/core/Dispatcher.php` | Query-string + SEO download routing |
| Theme | `public/themes/blog/download.php` | Frontend download info page |
| Theme | `public/themes/blog/download_file.php` | File download bootstrap (streams via controller) |
| Admin UI | `admin/ui/medialib/all-media.php`, `admin/ui/medialib/edit-media.php` | Media list + upload/edit form |
| Admin UI | `admin/ui/downloads/all-downloads.php`, `admin/ui/downloads/download-history-page.php` | Downloads list + history |
| Admin UI | `admin/ui/setting/download-setting.php` | Download settings form |

### How It Works (Summary)

1. **Upload**: Admin submits a file via `MediaController::insert()`. The file is validated
   (CSRF, MIME, extension, size, filename) and stored under `public/files/{pictures|audio|video|docs}/`.
   Metadata (Origin, File type, File size, Uploaded at, Dimension) is stored in `tbl_mediameta`.
2. **Download target**: When `media_target === 'download'`, `MediaController` creates a row in
   `tbl_media_download` with a freshly generated UUID identifier and `before_expired =
   time() + TIME_BEFORE_EXPIRED * 3600` (8 hours by default).
3. **Link creation/regeneration**: `DownloadService::createDownloadRecord()` / `regenerateDownloadRecord()`
   generate a new UUID and set expiry from `DownloadSettings::getDownloadExpiry()` (default 8h).
   Expiring sets `before_expired = time() - 1`.
4. **Frontend page**: `/download/{identifier}` (or `/?download={identifier}`) renders
   `download.php` with file details, download button, share/copy control, expiry pill, and
   optional support link.
5. **File streaming**: `/download/{identifier}/file` (or `/?download={identifier}/file`) streams
   the file via `DownloadUtility::deliverFile()` in 8 KB chunks with forced-attachment headers,
   after validating identifier, expiry, MIME allow-list, and hotlink referrer.
6. **Download log**: Every successful stream is recorded in `tbl_download_log`
   (media_id, identifier, IP, user-agent, status) for the history/stats views.

### URL Formats

| Resource | Permalinks Enabled (SEO) | Permalinks Disabled (Query String) |
|----------|--------------------------|------------------------------------|
| Download info page | `/download/{identifier}` | `/?download={identifier}` |
| Direct file download | `/download/{identifier}/file` | `/?download={identifier}/file` |
| Media library (admin) | `admin/index.php?load=medialib` | same |
| Upload new media | `admin/index.php?load=medialib&action=newMedia&Id=0` | same |
| Edit media | `admin/index.php?load=medialib&action=editMedia&Id={id}` | same |
| Downloads mgmt | `admin/index.php?load=downloads` | same |
| Download history | `admin/index.php?load=downloads&action=history&mediaId={id}` | same |
| Download settings | `admin/index.php?load=option-downloads` | same |

**Route regex** (`lib/core/Bootstrap.php`):
- `download` → `/download/(?'identifier'[a-f0-9\-]+)`
- `download_file` → `/download/(?'identifier'[a-f0-9\-]+)/file`

The Dispatcher handles `?download=` even when SEO URLs are enabled
(`Dispatcher::handleSeoFriendlyUrl()` line 104).

---

## 2. Datasets & Fixtures

### 2.1 Pre-seeded Admin Account

| Field | Value |
|-------|-------|
| Username | `administrator` |
| Password | `$E2E_ADMIN_PASS` (see `e2e/.env.example`) |
| Role | `administrator` |

A second lower-privilege account (e.g. `author`) is recommended for access-control tests.

### 2.2 Media Files

| Media | Type | `media_target` | `media_access` | `media_status` |
|-------|------|----------------|----------------|----------------|
| `cicero.jpg` | `image/jpeg` | `blog` | `public` | 1 |
| `report.pdf` | `application/pdf` | `download` | `public` | 1 |
| `guide.zip` | `application/zip` | `download` | `public` | 1 |
| `podcast.mp3` | `audio/mpeg` | `download` | `public` | 1 |
| `private-note.pdf` | `application/pdf` | `download` | `private` | 1 |
| `draft.pdf` | `application/pdf` | `download` | `public` | 0 (inactive) |

Seed data for test files should live in the test fixture directory and be uploaded through the
admin UI (preferred, exercises real upload path) or inserted directly into
`tbl_media` + `tbl_mediameta` + `tbl_media_download`.

### 2.3 Download Records

| Identifier | Media | `before_expired` | State |
|------------|-------|------------------|-------|
| `123e4567-e89b-12d3-a456-426614174000` | `report.pdf` | `time() + 8h` | Active |
| `9f8e7d6c-5b4a-3210-fedc-ba9876543210` | `guide.zip` | `time() - 1` | Expired |
| `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee` | (no media) | `time() + 8h` | Orphan |

### 2.4 Download Log Rows

| media_id | media_identifier | ip_address | status |
|----------|------------------|------------|--------|
| (report.pdf id) | `123e4567-...` | `127.0.0.1` | `success` |
| (report.pdf id) | `123e4567-...` | `203.0.113.9` | `success` |

### 2.5 Default Settings Snapshot

| Setting Key | Default |
|-------------|---------|
| `download_allowed_mime_types` | 25 default MIME types (see `DownloadSettings::DEFAULT_MIME_TYPES`) |
| `download_expiry_hours` | `8` |
| `download_hotlink_protection` | `no` |
| `download_allowed_domains` | `[]` |
| `download_support_url` | `''` |
| `download_support_label` | `'Support'` |

---

## 3. Admin — Media Library List

### URL

`admin/index.php?load=medialib`

### Preconditions

- User is logged in as **administrator**
- At least one media file exists

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to Media Library | — | Page loads with HTTP 200; table renders |
| View page title | — | "Media Library" |
| View header count | — | "N items in Total" (pluralised) |
| Click "Add New" | — | Navigates to `index.php?load=medialib&action=newMedia&Id=0` |
| Inspect table columns | — | `#`, File, Type, Display on, Download Link, Edit, Delete |
| Inspect File cell | — | `invoke_fileicon()` icon link to the media file |
| Inspect Display on | — | One of `blog`, `download`, `gallery`, `page` |
| Inspect Download Link column | — | If `media_target === 'download'`: "Create Link" button → `index.php?load=downloads&action=createLink&mediaId={id}`; else `N/A` |
| Click Edit | — | Navigates to `index.php?load=medialib&action=editMedia&Id={id}` |
| Click Delete | — | JS confirm "Are you sure want to delete media belongs to '<user>'" → deletes |
| Non-admin login | — | Only media owned by that user shown (`grabAllMedia('ID', user)`) |

### UI Components

| Component | Selector | Description |
|-----------|----------|-------------|
| Table | `table#scriptlog-table` | Bootstrap striped table |
| Add New button | `a[href*="action=newMedia"]` | Primary button, `fa-cloud-upload` icon |
| Create Link button | `a[href*="action=createLink"]` | `btn-success btn-xs` "Create Link" |
| Edit button | `a[href*="editMedia"]` | `btn-warning`, `fa-pencil` |
| Delete button | `a[href*="action=deleteMedia"]` | `btn-danger`, `fa-trash-o`, calls `deleteMedia(id, user)` |
| Success alert | `.alert-success` | Green banner with dismiss button |
| Error alert | `.alert-danger` | Red banner with dismiss button |

### Status Messages

| Session value | Rendered Text |
|---------------|---------------|
| `mediaAdded` | "New media added" |
| `mediaUpdated` | "Media has been updated" |
| `mediaDeleted` | "Media deleted" |
| `mediaNotFound` (error) | "Error: Media Not Found" |

### Network Behaviour

| Request | Method | Status |
|---------|--------|--------|
| `admin/index.php?load=medialib` | GET | 200 |
| `admin/index.php?load=medialib&status=mediaAdded` | GET | 200 (banner shown) |
| Unauthorized access | GET | 403 → redirect `index.php?load=403&forbidden=...` |

---

## 4. Admin — Upload New Media

### URL

`admin/index.php?load=medialib&action=newMedia&Id=0`

### Preconditions

- User is logged in as **administrator**
- Valid test files available

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to Upload form | — | "Upload New Media" page renders |
| Select file | `report.pdf` | File name shown in input |
| Enter caption | "Monthly Report" | Caption field populated |
| Select "Display on" | Download | Dropdown value set |
| Select "Access" | Public | Dropdown value set |
| Click "Upload" | — | Form POSTs; success → redirect to medialib with success banner |

### Form Fields

| Field | Name | Attributes |
|-------|------|------------|
| File | `media` | `id="mediaUploaded"`, `required`, `maxlength="512"`, `accept` for images only on image paths |
| MAX_FILE_SIZE | `MAX_FILE_SIZE` | hidden, `value=APP_FILE_SIZE` |
| Caption | `media_caption` | `maxlength="200"`, placeholder "Describe this media..." |
| Display on | `media_target` | select: Blog / Download / Gallery / Page |
| Access | `media_access` | select: Public / Private |
| CSRF | `csrfToken` | hidden, generated per render |
| Submit | `mediaFormSubmit` | `btn-primary`, "Upload" |

### Validation Rules

| Rule | Error Message |
|------|---------------|
| CSRF token valid (10 min) | 400 — "Sorry, unpleasant attempt detected!" |
| Caption length ≤ 200 | "Form data is longer than allowed" |
| `media_target` ∈ {blog, download, gallery, page} | "Please choose the available value provided!" |
| `media_access` ∈ {public, private} | "Please choose the available value provided!" |
| File uploaded (`UPLOAD_ERR_OK`) | "No file uploaded" |
| Size ≤ `APP_FILE_SIZE` | "Exceeded filesize limit" / "Exceeded file size limit. Maximum file size is. X" |
| `check_file_name()` | "file name is not valid" |
| `check_file_length()` | "file name is too long" |
| Extension + MIME allowed | "Invalid file format" |

### Download-Specific Behaviour

When `media_target === 'download'` and the media is saved:
1. `generate_media_identifier()` produces a UUID v4
2. `before_expired = time() + TIME_BEFORE_EXPIRED * 3600` (8 hours — `MediaController::TIME_BEFORE_EXPIRED = 8`)
3. Row inserted into `tbl_media_download` (media_id, media_identifier, before_expired, ip_address, created_at)

### Network Behaviour

| Request | Method | Status |
|---------|--------|--------|
| Form submit (success) | POST `index.php?load=medialib&action=newMedia&Id=0` | 302 → `index.php?load=medialib&status=mediaAdded` |
| CSRF failure | POST | 400 "Bad Request" |

---

## 5. Admin — Edit Media

### URL

`admin/index.php?load=medialib&action=editMedia&Id={id}`

### Preconditions

- User is logged in as **administrator**
- Media exists (id known)

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to Edit Media | — | "Edit Media" page with preview + properties box |
| View preview | — | Image (with `<picture>`/webp), video player, audio player, or file icon |
| Update caption | New caption | Saved |
| Change "Display on" to Download | Download | `modifyMediaDownload()` regenerates identifier + expiry |
| Toggle Active Status | 0 | Media deactivated |
| Click "Update" | — | 302 → `index.php?load=medialib&status=mediaUpdated` |
| Edit non-existent id | `Id=9999` | 404 → `index.php?load=medialib&error=mediaNotFound` |

### Media Properties Box (right column)

| Property | Source |
|----------|--------|
| File name | `meta_value['Origin']` |
| MIME type | `meta_value['File type']` |
| File size | `meta_value['File size']` |
| Uploaded by | `media_user` |
| Uploaded on | `meta_value['Uploaded at']` |
| Dimension | `meta_value['Dimension']` (only for image MIME types, else "Not specified") |

### Update Branches

| Branch | Condition | Behaviour |
|--------|-----------|-----------|
| No new file | `$_FILES['media']['tmp_name']` empty | `updateMediaMetaOnly()` — metadata fields only |
| New file | Valid upload | `updateMediaWithFile()` — new file stored + metadata rebuilt |
| New file invalid | MIME/extension/size fail | Re-render form with errors |

### Download-Specific Behaviour

If `media_target === 'download'` on update:
- New identifier generated
- `before_expired = time() + TIME_BEFORE_EXPIRED * 3600`
- `tbl_media_download` row updated via `modifyMediaDownload()`

---

## 6. Admin — Delete Media

### URL

`admin/index.php?load=medialib&action=deleteMedia&Id={id}`

### Preconditions

- User is logged in as **administrator**
- Media exists

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Click Delete in list | — | JS `confirm("Are you sure want to delete media belongs to '<user>'")` |
| Confirm | — | Navigates to `index.php?load=medialib&action=deleteMedia&Id={id}` |
| Media deleted | — | 302 → `index.php?load=medialib&status=mediaDeleted`, success banner |
| Delete non-existent id | `Id=9999` | "Media not found" page rendered with error banner |
| Invalid Id (non-numeric) | `Id=abc` | 400 "Bad Request" |

### Security

| Check | Expectation |
|-------|-------------|
| Id validated | `filter_var($id, FILTER_VALIDATE_INT)` |
| Media existence | `grabMedia()` before removal |

---

## 7. Admin — Downloads Management

### URL

`admin/index.php?load=downloads`

### Preconditions

- User is logged in as **administrator**
- At least one media file with `media_target='download'` exists

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to Downloads | — | Page renders table of download records |
| View row | — | File (caption + filename), Type, both links, Expires, Created, Actions |
| Copy Download Page link | — | Copies `get_download_link(identifier,'page')`; notification "Download page link copied!" |
| Copy Direct File link | — | Copies `get_download_link(identifier,'file')`; notification "Direct file link copied!" |
| Click History action | — | Navigates to `action=history&mediaId={media_id}` |
| Click Expire action | — | Sets `before_expired=time()-1`; 302 → banner "Download has been expired" |
| Click Delete action | — | JS confirm `"Are you sure you want to delete download '<filename>'?"` → deletes; banner "Download has been deleted" |
| Bulk select + Apply | expire/regenerate/delete | JS confirm `"Are you sure you want to <action> N download(s)?"` → bulk operation + banner |
| Click Apply with no action | — | `alert('Please select an action.')`, submit blocked |
| Click Apply with no selection | — | `alert('Please select at least one download.')`, submit blocked |

### Table Columns

| Column | Content |
|--------|---------|
| Checkbox | `input[name="downloads[]"]` value = media_identifier; header checkbox `#select-all` |
| ID | Row number |
| File | `<strong>media_caption</strong>` + `<small>media_filename</small>` |
| Type | `media_type` |
| Download Links | Page link `<code id="page-link-{identifier}">` + copy btn; Direct `<code id="file-link-{identifier}">` + copy btn |
| Expires | `label-danger` "Expired" or `label-success` "Active" + date |
| Created | `date('M j, Y H:i')` of `created_at` |
| Actions | History (info), Expire (warning), Delete (danger) buttons |

### Individual Actions

| Action | URL | Behaviour |
|--------|-----|-----------|
| History | `action=history&mediaId={media_id}` | Renders history page |
| Expire | `action=expire&identifier={id}` | `expireDownloadRecord()` — sets expired, generates new identifier |
| Delete | `action=deleteDownload&identifier={id}` | Deletes record |
| Regenerate | (via bulk only) | New identifier + fresh expiry |

### Bulk Operations (via POST)

| bulk-action value | Service call | Banner |
|-------------------|--------------|--------|
| `expire` | `bulkExpireDownloads()` | "Selected downloads have been expired" |
| `regenerate` | `bulkRegenerateDownloads()` | "Selected downloads have been regenerated" |
| `delete` | `bulkDeleteDownloads()` | "Selected downloads have been deleted" |

### Status Messages

| Session value | Rendered Text |
|---------------|---------------|
| `downloadsExpired` | "Selected downloads have been expired" |
| `downloadsRegenerated` | "Selected downloads have been regenerated" |
| `downloadsDeleted` | "Selected downloads have been deleted" |
| `downloadExpired` | "Download has been expired" |
| `downloadRegenerated` | "Download has been regenerated" |
| `downloadDeleted` | "Download has been deleted" |
| `downloadLinkCreated` | "Download link has been created" |
| `downloadLinkCreateFailed` (error) | (error banner; message from session) |
| `invalidMediaId` (error) | (error banner; "Invalid media id") |

### Create Link Action

- Trigger: `index.php?load=downloads&action=createLink&mediaId={id}` (also reachable from
  Media Library "Create Link" button)
- Calls `DownloadService::createDownloadRecord($mediaId, $ip)` → new UUID + 8h expiry
- Success: `$_SESSION['status']='downloadLinkCreated'`; failure: `$_SESSION['error']='downloadLinkCreateFailed'`
- Invalid mediaId (0): `$_SESSION['error']='invalidMediaId'`

### Access Control

| Role | Access |
|------|--------|
| Administrator/Manager (MEDIALIB granted) | Allowed |
| Unauthorized | 403 → `index.php?load=403&forbidden=...` |

| Invalid input | Response |
|---------------|----------|
| Missing `identifier` on expire/delete/regenerate | 400 → `index.php?load=downloads&error=invalidId` |

---

## 8. Admin — Download History

### URL

`admin/index.php?load=downloads&action=history&mediaId={media_id}`

### Preconditions

- User is logged in as **administrator**
- Media with download log entries exists

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to History | — | "Download History" page renders |
| View header | — | Title + "for media: {media_filename}" |
| View table | — | ID, Download ID, IP Address, Downloaded At, Status |
| Empty history | — | Info alert "No download history found for this media file." |
| Click "Back to All Downloads" | — | Navigates to `index.php?load=downloads` |

### Status Rendering

| Condition | Label |
|-----------|-------|
| `strtotime(before_expired) < time()` | `label-danger` "Expired" |
| else | `label-success` "Active" |

### Access Control

- Gated by `userAccessControl(ActionConst::MEDIALIB)`; unauthorized → 403 redirect
- Invalid/missing `mediaId` (≤ 0) → 400 → `index.php?load=downloads&error=invalidId`

---

## 9. Admin — Download Settings

### URL

`admin/index.php?load=option-downloads` (POST to `action=downloadConfig`)

### Preconditions

- User is logged in as **administrator** (CONFIGURATION access)

### Form Fields

| Field | Name | Attributes |
|-------|------|------------|
| Allowed MIME types | `allowed_mime_types[]` | checkboxes, one per default MIME; chunked into 4 columns |
| Expiration hours | `expiry_hours` | number, `id="expiry-hours"`, `min="1"`, `max="720"`, default 8 |
| Hotlink protection | `hotlink_protection` | checkbox `value="1"` |
| Allowed domains | `allowed_domains` | textarea `id="allowed-domains"`, one domain per line |
| Support URL | `support_url` | `type="url"`, `id="support-url"`, placeholder `https://opencollective.com/...` |
| Button label | `support_label` | text, `id="support-label"`, default "Support" |
| CSRF | `csrfToken` | hidden |
| Submit | `downloadSettingSubmit` | `btn-primary` "Save Changes" |

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to settings | — | Form renders with current values checked/prefilled |
| Toggle MIME checkboxes | Uncheck `application/pdf` | Saved |
| Set expiry | `24` | Saved |
| Enable hotlink protection + domains | `example.com` | Saved |
| Set support URL + label | URL + "Donate" | Saved |
| Click Save Changes | — | 302 → `?load=option-downloads&action=downloadConfig&status=downloadConfigUpdated`; banner "Download setting has been updated" |
| Submit with bad CSRF | — | 400 "Bad Request" — "Sorry, unpleasant attempt detected!" |

### Processing Details (`ConfigurationController::updateDownloadSetting`)

```php
'allowed_mime_types' => $_POST['allowed_mime_types'] ?? [],
'expiry_hours'       => (int)($_POST['expiry_hours'] ?? 8),
'hotlink_protection' => isset($_POST['hotlink_protection']),
'allowed_domains'    => array_filter(array_map('trim', explode("\n", $_POST['allowed_domains'] ?? ''))),
'support_url'        => $_POST['support_url'] ?? '',
'support_label'      => $_POST['support_label'] ?? 'Support'
```

Stored via `DownloadSettings::saveSettings()` (each key upserted in `tbl_settings`).

### Access Control

- Gated by `userAccessControl(ActionConst::CONFIGURATION)`; unauthorized → 403 redirect

---

## 10. Admin — Summernote AJAX Upload Endpoint

### URL

`admin/media-upload.php` — POST, `multipart/form-data` with file field `image`

### Purpose

Enables the WYSIWYG editor to inline-upload images during post editing. Returns JSON only.

### Auth

| Scenario | Response |
|----------|----------|
| Not logged in | 401 `{"success":false,"error":{"code":"UNAUTHORIZED","message":"Admin authentication required"}}` |
| Level not in {administrator, manager, editor, author, contributor} | 403 `{"success":false,"error":{"code":"FORBIDDEN","message":"Insufficient permissions"}}` |

### Validation

| Scenario | Response |
|----------|----------|
| `UPLOAD_ERR_INI_SIZE` | 400 `UPLOAD_ERROR` "File exceeds upload_max_filesize" |
| `UPLOAD_ERR_FORM_SIZE` | 400 `UPLOAD_ERROR` "File exceeds MAX_FILE_SIZE" |
| `UPLOAD_ERR_PARTIAL` | 400 `UPLOAD_ERROR` "File was only partially uploaded" |
| `UPLOAD_ERR_NO_FILE` | 400 `UPLOAD_ERROR` "No file was uploaded" |
| MIME not in {jpeg, png, gif, webp, bmp} | 400 `INVALID_FILE_TYPE` "Invalid file type. Only JPEG, PNG, GIF, WebP, and BMP are allowed." |
| Size > 5 MB | 400 `FILE_TOO_LARGE` "File size exceeds maximum allowed (5MB)" |

### Success Response

```
201 {"success":true,"data":{"url":".../public/files/pictures/{name}.{ext}","filename":"...","media_id":N,"post_id":N}}
```

- Filename: `uniqid() . '_' . time() . '.' . ext`
- DB: `MediaDao::createMedia()` with `media_type='image'`, `media_target='blog'`,
  `media_access='public'`, `media_status=1`, `media_user` = session login
- If `post_id` provided: `tbl_mediameta` row `post_id` → value links image to post
- Response headers: `Cache-Control: no-store, no-cache, must-revalidate`

---

## 11. Frontend — Download Page (Success State)

### URL

| Permalinks Enabled | Permalinks Disabled |
|--------------------|---------------------|
| `/download/123e4567-e89b-12d3-a456-426614174000` | `/?download=123e4567-e89b-12d3-a456-426614174000` |

### Preconditions

- Active download record exists (identifier `123e4567-e89b-12d3-a456-426614174000`, `report.pdf`)
- Media is public + active
- User is **not** logged in

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Navigate to download page | — | HTTP 200; success card renders |
| View title | — | `h1#download-heading` = media caption |
| View file icon | — | `fa-file-pdf-o` for PDF, `fa-file-image-o`/`-video-o`/`-audio-o`/`-archive-o`/`-text-o`/`-o` |
| View badges | — | Extension (uppercase), MIME type, size |
| View details rows | — | Filename, Type, Size |
| View "Download Now" button | — | `aria-label="Download {caption}"`, href = `/download/{id}/file` (or `/?download={id}/file`) |
| View share input | — | Readonly input with full URL, Copy button |
| View expiry pill | — | "Expires {Mmm j, YYYY} at {h:mm AM/PM}" |
| Support link (if configured) | — | `target="_blank" rel="noopener noreferrer"` |
| Download page (no support URL) | — | Support section absent |

### Expected Page Structure

```html
<section class="download-page-section" aria-labelledby="download-heading">
  <div class="download-container">
    <div class="download-card" role="region" aria-label="File download">
      <div class="download-header">
        <div class="download-file-icon" aria-hidden="true">
          <i class="fa fa-file-pdf-o"></i>
        </div>
        <div class="download-file-meta">
          <h1 class="download-file-title" id="download-heading">Monthly Report</h1>
          <div class="download-file-badges">
            <span class="download-badge badge-ext">PDF</span>
            <span class="download-badge badge-type">application/pdf</span>
            <span class="download-badge badge-size">120.00 KB</span>
          </div>
        </div>
      </div>
      <!-- details rows, download button, share input, support, expiry pill -->
    </div>
  </div>
</section>
```

### UI Components

| Component | Selector | Notes |
|-----------|----------|-------|
| Region | `.download-card[role="region"][aria-label="File download"]` | |
| Heading | `h1#download-heading` | `safe_html()` of caption |
| Icon | `.download-file-icon i.fa` | Derived from MIME type |
| Ext badge | `.badge-ext` | `strtoupper()` extension |
| Type badge | `.badge-type` | MIME type |
| Size badge | `.badge-size` | `file_size` or "Unknown" |
| Detail rows | `.download-detail-row` | Filename / Type / Size |
| Download button | `.download-btn-primary` | `role="button"`, `aria-label` |
| Share input | `#download-share-url` | readonly |
| Copy button | `#copy-link-btn` | `aria-label="Copy download link to clipboard"` |
| Copy status | `#copy-status` | `aria-live="polite" role="status"` |
| Support button | `.download-btn-support` | `target="_blank" rel="noopener noreferrer"` |
| Expiry pill | `.download-expiry-pill` | "Expires … at …" |

### URL Generation (get_download_link)

| Setting | type=page | type=file |
|---------|-----------|-----------|
| Permalinks enabled | `/download/{id}` | `/download/{id}/file` |
| Permalinks disabled | `/?download={id}` | `/?download={id}/file` |

### Network Behaviour

| Request | Method | Status |
|---------|--------|--------|
| Download page | GET `/download/{id}` | 200 HTML |
| Download page (query) | GET `/?download={id}` | 200 HTML |

---

## 12. Frontend — Download Page (Error States)

### 12.1 Invalid / Non-Existent Identifier

| Test | URL | Page Behaviour | Raw `/file` Behaviour |
|------|-----|----------------|----------------------|
| Unknown identifier | `/download/00000000-0000-4000-8000-000000000000` | 404 page | 404 "Download link not found" |
| Malformed identifier | `/download/not-a-uuid` | Route regex fails → 404 (Dispatcher) | — |
| Orphan record (no media) | `/download/aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee` | `error: "File not found"` | 404 "File not found" |

### 12.2 Expired Link

| Field | Value |
|-------|-------|
| Identifier | `9f8e7d6c-5b4a-3210-fedc-ba9876543210` (expired) |
| Page behaviour | `role="alert"` block; title "Download Unavailable"; message "Download link has expired"; hint "This download link has expired. Please contact the site administrator for a new link." |
| `/file` behaviour | **410** "Download link has expired" |

### 12.3 Private / Inactive Media

| Scenario | Page Behaviour |
|----------|----------------|
| `media_access='private'` | `getMediaDownload()` requires `media_access='public' AND media_status='1'` → error |
| `media_status='0'` (inactive) | Same query restriction → error |

### Error State Markup

```html
<div class="download-error" role="alert">
  <div class="download-error-icon"><i class="fa fa-exclamation-circle" aria-hidden="true"></i></div>
  <h2 class="download-error-title" id="download-heading">Download Unavailable</h2>
  <p class="download-error-message">…</p>
  <p class="download-error-hint">This download link has expired. …</p>
</div>
```

---

## 13. Frontend — Direct File Download

### URL

| Permalinks Enabled | Permalinks Disabled |
|--------------------|---------------------|
| `/download/{identifier}/file` | `/?download={identifier}/file` |

### Preconditions

- Active download record for a public + active media file
- Actual file exists on disk under `public/files/{pictures|audio|video|docs}/`

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Click "Download Now" | — | Browser downloads file (attachment) |
| Direct navigation | — | File streamed with attachment headers |
| Re-download | — | Works; new `tbl_download_log` row added |

### Response Headers (DownloadUtility::setDownloadHeaders)

| Header | Value |
|--------|-------|
| `Content-Type` | media MIME type |
| `Content-Length` | filesize |
| `Content-Disposition` | `attachment; filename="{filename}"` |
| `Cache-Control` | `no-cache, must-revalidate` |
| `Pragma` | `public` |
| `Expires` | `0` |

Streaming: `fread($handle, CHUNK_SIZE=8192)` loop with `flush()`.

### Controller Guard Sequence (`DownloadController::download`)

| # | Check | Failure Response |
|---|-------|------------------|
| 1 | Identifier valid (UUID regex) | 404 "Download link not found" |
| 2 | Not expired | 410 "Download link has expired" |
| 3 | Media exists (public + active) | 404 "File not found" |
| 4 | MIME allowed (`isMimeTypeAllowed`) | 403 "File type not allowed" |
| 5 | Hotlink protection OK | 403 "Hotlinking not allowed" |
| 6 | File exists on server | 404 "File not found on server" |

### Hotlink Protection

| Setting | Referrer | Result |
|---------|----------|--------|
| Off (default) | any | Allowed |
| On, allowed domains `['example.com']` | `http://example.com/` | Allowed |
| On, allowed domains `['example.com']` | `http://evil.com/` | 403 "Hotlinking not allowed" |
| On, allowed domains `['example.com']` | empty referer | Allowed (`empty($referer) → true`) |

### Download Logging

Each successful download logs via `recordDownloadAttempt()`:
`tbl_download_log(media_id, media_identifier, ip_address, user_agent, status='success')`

### download_file.php Bootstrap

| Scenario | Response |
|----------|----------|
| Empty identifier | 400 "Invalid download request" |
| Valid identifier | Delegates to `DownloadController::download()` |

---

## 14. Frontend — Copy Share Link

### Preconditions

- Download page rendered in success state
- Browser supports Clipboard API

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Click "Copy" | — | `navigator.clipboard.writeText()` called with URL |
| Clipboard API rejects | — | `fallbackCopy()` uses hidden textarea + `document.execCommand('copy')` |
| Success feedback | — | Button text "Copied!", class `copied`, aria-label "Link copied to clipboard"; status text "Link copied to clipboard!" |
| After 2s | — | Button and status reset to original |
| Keyboard | Enter/Space on button | Same copy behaviour |

### Validation Rules

| Rule | Detail |
|------|--------|
| Input selected on click | `input.select(); setSelectionRange(0, 99999)` |
| ARIA live status updated | `#copy-status` text set then cleared |
| Reset after 2 seconds | `setTimeout(2000)` |
| Failure feedback | Button "Failed", status "Failed to copy. Please copy manually." |

---

## 15. Security Expectations

### CSRF

| Area | Status | Notes |
|------|--------|-------|
| Upload media form | ✅ `csrfToken` hidden field | `csrf_check_token('csrfToken', $_POST, 60*10)` |
| Edit media form | ✅ | Same check |
| Downloads bulk form | ✅ | `csrf_check_token` before bulk ops |
| Download settings form | ✅ | `csrf_check_token` before save |
| Download page (public) | N/A | Read-only; no state change |
| File download | N/A | Authenticated by unguessable UUID |

### XSS

| Vector | Protection |
|--------|------------|
| Caption/filename/type on frontend | `safe_html()` on output |
| Download error messages | `safe_html()` on output |
| Media captions in admin | `safe_html()` / `htmlout()` |
| Download page identifier | `preg_replace('/[^a-zA-Z0-9\-]/', '', ...)` sanitized |
| Admin download links | `safe_html()` on URLs/identifiers |

### SQL Injection

| Query | Protection |
|-------|------------|
| All model queries | PDO prepared statements / parameterised `findRow`/`findAll` |
| `getMediaDownloadURL` | Identifier sanitized via `preg_replace('/[^a-f0-9\-]/i','')` + prepared statement (NOT sql sanitizer — preserves hyphens) |
| Media CRUD | Medoo/DAO parameterised |

### Authorization

| Area | Check |
|------|-------|
| Media library | `userAccessControl(ActionConst::MEDIALIB)` |
| Downloads management | `userAccessControl(ActionConst::MEDIALIB)` |
| Download settings | `userAccessControl(ActionConst::CONFIGURATION)` |
| Frontend download | Public by design (UUID as capability token) |
| Summernote upload | Session login + level whitelist |

### Path Traversal

- `sanitizeFilePath()` — `basename()`, rejects `\0` and `..`
- `DownloadController::getFilePath()` uses `basename(media_filename)` within fixed subdirectories

### Sensitive Information

| Item | Exposed? |
|------|----------|
| Download UUID identifiers | Shown to admins in Downloads table; intended shareable |
| `before_expired` timestamps | Shown as human dates only |
| Download log IP addresses | Admin history page only (role-gated) |
| File paths | Not exposed; only filename basename |

---

## 16. Accessibility

### Download Page

| Element | Expected |
|---------|----------|
| Section landmark | `aria-labelledby="download-heading"` |
| Card region | `role="region" aria-label="File download"` |
| Decorative icons | `aria-hidden="true"` |
| Download button | `role="button"`, `aria-label="Download {caption}"` |
| Share input | `aria-label="Download link URL"`, `aria-describedby="copy-status"`, readonly |
| Copy button | `aria-label="Copy download link to clipboard"`; label updates after copy |
| Copy status | `aria-live="polite" role="status"` |
| Error block | `role="alert"` |
| Support link | `aria-label="Support this project (opens in new tab)"` |

### Admin Pages

| Element | Expected |
|---------|----------|
| Media table | `aria-describedby="all media"` |
| Form inputs | `<label for="...">` associations (caption, file, target, access) |
| Required file | `required aria-required="true"` |
| Cancel button | `aria-label="Cancel and return to media library"` |
| Submit buttons | `aria-label="Upload Media"/"Update Media"` |
| Dismiss buttons | `aria-label="Close"` on alerts |

### Keyboard Navigation

| Element | Behaviour |
|---------|-----------|
| Copy button | Enter/Space triggers copy (explicit `keydown` handler) |
| All buttons/links | Native tab focus + focus visible |
| File inputs | Native file picker on Enter/Space |

### Issues to Verify

| Issue | WCAG Criterion | Severity |
|-------|----------------|----------|
| Copy feedback color/text contrast | 1.4.3 | Verify |
| Share input contrast (readonly grey) | 1.4.3 | Verify |
| `aria-live` only on success/error | 4.1.3 | Verify |

---

## 17. Responsive Behaviour

### Download Page

| Breakpoint | Behaviour |
|------------|-----------|
| Desktop ≥992px | Card centered, badges inline, details in 2 columns, share row inline |
| Tablet 768-991px | Card ~full width, share input + button still inline |
| Mobile <768px | Card full width; share input stacks above Copy button; badges wrap; download button full width |

### Admin Tables

| Breakpoint | Behaviour |
|------------|-----------|
| Desktop | Full table with all columns |
| Tablet | Horizontal scroll via `.table-responsive` |
| Mobile | Horizontal scroll; bulk actions dropdown full-width-ish (`style="display:inline-block; width:auto;"`) |

---

## 18. Browser Compatibility

| Browser | Minimum Version | Notes |
|---------|-----------------|-------|
| Chrome | 90+ | Clipboard API, CSP nonce |
| Firefox | 90+ | Clipboard API fallback path |
| Safari | 14+ | `navigator.clipboard` availability varies → fallback |
| Edge | 90+ | Chromium-based |

### Cross-Browser Checks

| Feature | Check |
|---------|-------|
| Clipboard API | Fallback to `execCommand('copy')` verified |
| `fetch`/form submit | Standard |
| CSS icons (FontAwesome) | All modern browsers |
| CSP nonce | Injected via PHP `CSP_NONCE` |
| `confirm()` / `alert()` dialogs | All browsers (Playwright must handle dialogs) |

---

## 19. Failure Scenarios

### 19.1 Upload Failures

| Scenario | Expected |
|----------|----------|
| No file selected | "No file uploaded" error |
| Oversized file | "Exceeded filesize limit" |
| Invalid extension/MIME | "Invalid file format" |
| Caption > 200 chars | "Form data is longer than allowed" |
| Tampered CSRF | 400 "Sorry, unpleasant attempt detected!" |

### 19.2 Download Page Failures

| Scenario | Expected |
|----------|----------|
| Unknown identifier | 404 / "Download link not found" |
| Expired identifier | Error state with expired hint |
| Private media | Error state "File not found" |
| Inactive media | Error state |
| Malformed URL | 404 via Dispatcher |

### 19.3 File Stream Failures

| Scenario | Expected |
|----------|----------|
| File deleted from disk | 404 "File not found on server" |
| MIME type not in allow-list | 403 "File type not allowed" |
| Hotlink from disallowed domain | 403 "Hotlinking not allowed" |
| Download page for orphan record | Error state |
| Empty identifier (`download_file.php`) | 400 "Invalid download request" |

### 19.4 Admin Failures

| Scenario | Expected |
|----------|----------|
| Expire/delete without identifier | 400 → `?load=downloads&error=invalidId` |
| Bulk apply with no selection | `alert("Please select at least one download.")` |
| Bulk apply with no action | `alert("Please select an action.")` |
| CSRF failure on bulk/settings | 400 Bad Request |
| Non-existent media edit/delete | 404 / "Media not found" |

### 19.5 Offline / Slow Network

| Scenario | Expected |
|----------|----------|
| Page loaded, then offline | Download button fails; browser error |
| Slow stream | Chunked delivery; no timeout under normal limits |
| Clipboard API unavailable | Fallback copy path runs |

---

## 20. Recovery Behaviour

### After Failed Upload

1. Errors listed in `alert-danger` banner on re-rendered form
2. Previously entered caption/selection preserved via `formData`
3. User can retry upload

### After Expiring a Link

1. Identifier regenerated (old UUID invalidated)
2. Download page for old identifier shows expired error
3. New identifier must be shared via Downloads table copy buttons

### After Regenerating a Link

1. New UUID + fresh 8h expiry
2. Old UUID no longer resolves (verify)
3. Log history retained (keyed by media_id, not identifier)

### After Deleting a Download Record

1. Identifier removed from `tbl_media_download`
2. Frontend `/download/{old-id}` returns 404 "Download link not found"
3. Media file itself remains in library

### After a Hotlink Block

1. Response 403 "Hotlinking not allowed"
2. Direct navigation (no referrer) still works
3. Referrer from allowed domain works

---

## 21. Screenshots to Capture

| # | Screen | Viewport | Description |
|---|--------|----------|-------------|
| 1 | Frontend download page — success | Desktop 1280×720 | Card, icon, badges, details, download button, share, expiry |
| 2 | Frontend download page — success | Tablet 768×1024 | Responsive layout |
| 3 | Frontend download page — success | Mobile 375×667 | Stacked layout |
| 4 | Frontend download page — expired | Desktop | Error state with hint |
| 5 | Frontend download page — invalid | Desktop | "Download Unavailable" |
| 6 | Frontend download page — with support | Desktop | Support button visible |
| 7 | Admin media library | Desktop | Table with status banner |
| 8 | Admin upload form | Desktop | All fields + preview |
| 9 | Admin edit media | Desktop | Preview + properties box |
| 10 | Admin downloads page | Desktop | Table, links, expiry labels, bulk controls |
| 11 | Admin download history | Desktop | History table with status labels |
| 12 | Admin download settings | Desktop | MIME checkboxes, expiry, hotlink, support |
| 13 | Copy feedback | Desktop | "Copied!" state on button |

---

## 22. Console & Network Expectations

### Console

| Scenario | Expected |
|----------|----------|
| Download page load | No errors |
| Copy success | No errors; status text updated |
| Copy fallback | No errors; `execCommand` used |
| Admin pages | No errors |
| Download stream | No errors (download handled as response) |

### Network

| Request | Expected | Unacceptable |
|---------|----------|--------------|
| Download page | 200 HTML | 404, 500 |
| Direct file stream (active) | 200 + attachment headers | 4xx, 5xx |
| Expired direct file | 410 | 200, 500 |
| Unknown identifier direct file | 404 | 200 |
| MIME not allowed | 403 | 200 |
| Hotlink blocked | 403 | 200 |
| Download settings POST | 302 redirect | 500 |
| Downloads bulk POST | 302 redirect | 500 |
| Summernote upload success | 201 JSON | non-JSON, 5xx |
| Summernote upload unauth | 401 JSON | 200 |
| Media delete | 302 redirect | 500 |

---

## 23. Test Scenarios

### 23.1 Critical Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| C01 | Frontend: active download page renders | Positive | GET `/download/123e4567-...` | 200; heading, badges, button, expiry present |
| C02 | Frontend: direct file download streams | Positive | Click Download Now / GET `/download/123e4567-.../file` | 200, attachment headers, `Content-Disposition` |
| C03 | Frontend: expired link shows error + 410 | Negative | GET `/download/9f8e7d6c-...` and `/file` | Page error state; `/file` → 410 |
| C04 | Admin: upload media with download target | Positive | Login; upload file; Display on=Download | `tbl_media_download` row created with UUID + 8h expiry; success banner |
| C05 | Admin: downloads list shows records | Positive | Navigate to `load=downloads` | Table rows with links, expiry labels, actions |
| C06 | Admin: create download link | Positive | Media Library → Create Link | New identifier; banner "Download link has been created" |
| C07 | Admin: expire single download | Positive | Downloads → Expire | Banner "Download has been expired"; row shows Expired |
| C08 | Admin: regenerate single download | Positive | Bulk → regenerate (or direct) | New identifier; banner "Download has been regenerated" |
| C09 | Admin: delete download record | Positive | Downloads → Delete (confirm) | Banner "Download has been deleted"; row gone; frontend 404 |
| C10 | Admin: download settings save | Positive | Change MIME/expiry/support → Save | Banner "Download setting has been updated" |

### 23.2 High Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| H01 | Frontend: unknown identifier → 404 | Negative | GET `/download/00000000-...` | 404 page |
| H02 | Frontend: hotlink protection | Security | Enable protection + `evil.com`; request with `Referer: http://evil.com/` | 403 "Hotlinking not allowed" |
| H03 | Frontend: MIME allow-list enforced | Security | Uncheck `application/pdf`; download PDF | 403 "File type not allowed" |
| H04 | Frontend: download log recorded | Data | Download file; check history | New `tbl_download_log` row |
| H05 | Admin: bulk expire | Positive | Select 2 → expire → Apply | Banner "Selected downloads have been expired" |
| H06 | Admin: bulk delete | Positive | Select 2 → delete → Apply | Banner "Selected downloads have been deleted" |
| H07 | Admin: bulk regenerate | Positive | Select 2 → regenerate → Apply | Banner "Selected downloads have been regenerated" |
| H08 | Admin: download history page | Positive | Open history for media with logs | Rows with IP, date, Active/Expired |
| H09 | Admin: unauthorised user blocked | Security | Login as author (no MEDIALIB) → `load=downloads` | 403 redirect |
| H10 | Admin: settings CSRF | Security | POST settings without token | 400 Bad Request |

### 23.3 Medium Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| M01 | Frontend: copy link works | Positive | Click Copy; read clipboard | Clipboard contains download URL; "Copied!" shown |
| M02 | Frontend: copy fallback | Compatibility | Mock clipboard rejection | Fallback copy succeeds |
| M03 | Frontend: private media blocked | Security | Download private media link | Error state "File not found" |
| M04 | Frontend: inactive media blocked | Security | Download media with status=0 | Error state |
| M05 | Admin: upload validation errors | Negative | Oversized/invalid file | Listed error messages; form re-rendered |
| M06 | Admin: edit media (metadata only) | Positive | Edit caption, no file | Banner "Media has been updated"; caption updated |
| M07 | Admin: switch blog media to download | Positive | Edit media, set Display on=Download | Download record created |
| M08 | Admin: media delete | Positive | Delete media (confirm) | Banner "Media deleted" |
| M09 | Admin: empty download history | Edge | History for media with no logs | Info alert "No download history found..." |
| M10 | Admin: bulk actions without selection | Negative | Click Apply, nothing selected | `alert("Please select at least one download.")` |

### 23.4 Low Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| L01 | Frontend: support section hidden when unset | UI | Default settings | No `.download-support` block |
| L02 | Frontend: support section shown when set | UI | Configure support URL + label | Support button with label |
| L03 | Frontend: file icon mapping | UI | Check image/video/audio/pdf/zip/text/downloads | Correct `fa-file-*` icon each |
| L04 | Admin: non-admin sees own media only | Auth | Login as author → medialib | Only author-owned rows |
| L05 | Admin: expiry hours range validation | Edge | Set expiry 0 or 1000 | Browser `min`/`max` blocks |
| L06 | Frontend: query-string mode | Positive | Permalinks disabled → `/?download={id}` | Same page as SEO URL |
| L07 | Frontend: `?download=` with SEO enabled | Compatibility | Permalinks enabled → `/?download={id}/file` | Still streams file |
| L08 | Admin: support settings persist | Data | Save URL/label; reload | Values prefilled |
| L09 | Frontend: orphan download record | Edge | Link with no media | Error state |
| L10 | Admin: createLink for non-download media | Edge | Media with target=blog | No Create Link button (N/A) |

### 23.5 Edge Cases

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| E01 | Identifier case-insensitivity | Boundary | UUID in uppercase | Regex is `/i`; resolves (verify DB/route) |
| E02 | Download of file missing on disk | Error | Delete `report.pdf` from disk | 404 "File not found on server" |
| E03 | Very large file stream | Boundary | 100 MB file | Streams in 8 KB chunks without memory blowup |
| E04 | Concurrent downloads | Race | 2 simultaneous `/file` requests | Both succeed; 2 log rows |
| E05 | Expiry boundary (exact 8h) | Boundary | `before_expired == time()` | Expired (strict `>` comparison) |
| E06 | Regenerate preserves history | Data | Regenerate; view history | Old log rows retained |
| E07 | Delete media with active link | Integration | Delete media that has download record | Download row cascades or orphans (verify) |
| E08 | Duplicate createLink rapid clicks | Race | Double-click Create Link | Two records or single (verify idempotency) |
| E09 | MIME with parameters | Boundary | `application/pdf; charset=utf-8` | Not in allow-list → 403 (verify) |
| E10 | Caption with HTML/entities | Security | Caption `<script>` | `safe_html()` escapes on output |
