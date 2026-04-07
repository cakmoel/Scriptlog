# AJAX Image Upload for Summernote Editor - Implementation Plan

## 1. Project Overview

**Goal**: Integrate AJAX image upload in Summernote WYSIWYG editor

**Scope**: Only Summernote editor AJAX upload - do NOT touch Featured Image (sidebar form)

**Current State**:
- Summernote in `admin/admin-layout.php` calls `/admin/media-upload.php`
- No CSRF protection
- Images not saved to database via API

**Target State**:
- AJAX upload to `/admin/media-upload.php` (direct admin endpoint)
- CSRF validation (optional)
- Save to `tbl_media` + link to post via `tbl_mediameta`
- Use existing `upload_photo()` for resizing

---

## 2. Files Involved

| File | Action |
|------|--------|
| `admin/media-upload.php` | New endpoint for upload handling |
| `admin/admin-layout.php` | Update AJAX URL + add CSRF/post_id to formData |
| `lib/core/Authentication.php` | Cookie path fix for cross-path AJAX |
| `lib/controller/api/MediaApiController.php` | API fallback with auth cookie support |

---

## 3. Implementation Steps

### Step 1: Create Direct Admin Upload Endpoint

**File**: `admin/media-upload.php`

This endpoint is placed in the admin folder to use admin session authentication directly.

Key features:
- Uses `Session::getInstance()` for authentication (works in admin context)
- Saves to database via `MediaDao`
- Uses `upload_photo()` for resizing to 3 sizes + WebP
- Proper output buffering to prevent HTML errors in JSON response
- Returns clean JSON response

```php
// Key authentication check
$session = Session::getInstance();
if (!isset($session->scriptlog_session_login)) {
    sendJsonResponse(401, false, 'UNAUTHORIZED', 'Admin authentication required');
}
```

### Step 2: Update Summernote AJAX

**File**: `admin/admin-layout.php`

- Keep URL as `/admin/media-upload.php` (not `/api/v1/media/upload`)
- Add `withCredentials: true` to AJAX call
- Hidden inputs for CSRF and post_id are already in place

```javascript
$.ajax({
  url: '/admin/media-upload.php',
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  xhrFields: {
    withCredentials: true
  },
  success: function(response) {
    if (response.success && response.data && response.data.url) {
      $('#summernote').summernote('insertImage', response.data.url);
    }
  }
});
```

### Step 3: Cookie Path Fix

**File**: `lib/core/Authentication.php`

Changed `COOKIE_PATH` from `APP_ADMIN` (`/admin/`) to `/` so the session cookie is sent to all paths including `/api/*`.

```php
// Before
public const COOKIE_PATH = APP_ADMIN;  // /admin/

// After
public const COOKIE_PATH = '/';  // Available at root level
```

**Note**: Users need to log out and log back in for the new cookie to be set.

### Step 4: API Fallback (Optional)

**File**: `api/index.php` and `lib/controller/api/MediaApiController.php`

Added session initialization to API and auth cookie fallback support as backup solution.

---

## 4. Response Format

```json
{
  "success": true,
  "status": 201,
  "data": {
    "url": "/public/files/pictures/abc123_image.jpg",
    "filename": "abc123_image.jpg",
    "media_id": 42,
    "post_id": 5
  }
}
```

**URL**: Direct filesystem path for fast loading (`/public/files/pictures/...`)

---

## 5. Database

**tbl_media** - Image metadata:
- `media_filename` = 'abc123_image.jpg'
- `media_type` = 'image'
- `media_target` = 'blog'
- `media_user` = username

**tbl_mediameta** - Post linkage:
- `media_id` = 42
- `meta_key` = 'post_id'
- `meta_value` = '5'

---

## 6. Files to Modify/Create

1. `admin/media-upload.php` - New direct upload endpoint
2. `admin/admin-layout.php` - AJAX URL and withCredentials
3. `lib/core/Authentication.php` - Cookie path fix
4. `api/index.php` - Session initialization (backup)
5. `lib/controller/api/MediaApiController.php` - Auth cookie fallback (backup)

---

## 7. Testing

1. Log out and log back in (to get new cookie with path `/`)
2. Go to Posts → Add New
3. Click image button in Summernote toolbar
4. Select image → verify uploads
5. Check:
   - Files: `public/files/pictures/` has 4 versions + WebP
   - Database: `tbl_media` and `tbl_mediameta` have new records

---

## 8. Troubleshooting

### Error: "Failed to upload image: Unauthorized"
- Cookie path issue: `COOKIE_PATH` was `/admin/`, need to change to `/`
- Solution: Log out and log back in

### Error: "Failed to upload image: SyntaxError: Unexpected token '<'"
- PHP output before JSON: `lib/main.php` or `upload_photo()` outputting HTML
- Solution: Use output buffering and proper JSON response helper

---

## 9. Implementation Summary

| Date | Commit | Description |
|------|--------|-------------|
| April 6, 2026 | `f2e1d91` | Fix Summernote AJAX image upload authentication |
| April 6, 2026 | `5db174e` | Fix cookie path for AJAX API requests |
| April 6, 2026 | `a593f39` | Use direct admin endpoint for Summernote image upload |
| April 6, 2026 | `44db5e7` | Fix JSON response in media upload handler |

*Plan updated: April 6, 2026*
*Focus: ONLY Summernote AJAX upload - do NOT touch Featured Image sidebar*
