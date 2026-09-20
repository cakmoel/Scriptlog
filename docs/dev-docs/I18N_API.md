# i18n API Documentation

**Project:** Blogware/Scriptlog CMS  
**API Version:** 1.0  
**Base URL:** `/api/v1`  
**Last Updated:** March 2026  

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Languages API](#languages-api)
4. [Translations API](#translations-api)
5. [Error Handling](#error-handling)
6. [Rate Limiting](#rate-limiting)
7. [Examples](#examples)

---

## Overview

The i18n API provides endpoints for managing languages and translations in the Blogware CMS. All responses are in JSON format.

### Base URL

```
/api/v1
```

### Content Type

All requests and responses use `application/json` content type.

---

## Authentication

The i18n API requires authentication for write operations (POST, PUT, DELETE). Authentication is handled via:

1. **Session Cookie** - Admin users with valid session
2. **API Key** - For programmatic access

### Required Role

All i18n API endpoints require `administrator` role.

---

## Languages API

Base path: `/api/v1/languages`

### List All Languages

```
GET /api/v1/languages
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `active` | boolean | Filter to active languages only (default: false) |

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "lang_code": "en",
            "lang_name": "English",
            "lang_native": "English",
            "lang_locale": "en_US",
            "lang_direction": "ltr",
            "lang_sort": 1,
            "lang_is_default": true,
            "lang_is_active": true,
            "lang_created_at": "2026-03-21T10:00:00+00:00"
        }
    ],
    "meta": {
        "total": 1,
        "page": 1,
        "per_page": 20
    }
}
```

### List Active Languages

```
GET /api/v1/languages/active
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "lang_code": "en",
            "lang_name": "English",
            "lang_native": "English",
            "lang_locale": "en_US",
            "lang_direction": "ltr",
            "lang_sort": 1,
            "lang_is_default": true,
            "lang_is_active": true
        }
    ]
}
```

### Get Default Language

```
GET /api/v1/languages/default
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "lang_code": "en",
        "lang_name": "English",
        "lang_native": "English",
        "lang_locale": "en_US",
        "lang_direction": "ltr",
        "lang_is_default": true,
        "lang_is_active": true
    }
}
```

### Get Language by Code

```
GET /api/v1/languages/{code}
```

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `code` | string | Language code (ISO 639-1, e.g., 'en', 'es') |

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "lang_code": "en",
        "lang_name": "English",
        "lang_native": "English",
        "lang_locale": "en_US",
        "lang_direction": "ltr",
        "lang_sort": 1,
        "lang_is_default": true,
        "lang_is_active": true,
        "lang_created_at": "2026-03-21T10:00:00+00:00"
    }
}
```

**Response (404 Not Found):**
```json
{
    "success": false,
    "error": {
        "code": "LANGUAGE_NOT_FOUND",
        "message": "Language with code 'xx' not found"
    }
}
```

### Create Language

```
POST /api/v1/languages
```

**Request Body:**
```json
{
    "lang_code": "es",
    "lang_name": "Spanish",
    "lang_native": "Español",
    "lang_locale": "es_ES",
    "lang_direction": "ltr",
    "lang_sort": 2,
    "lang_is_default": false,
    "lang_is_active": true
}
```

**Required Fields:**
- `lang_code` - ISO 639-1 code (e.g., 'en', 'es', 'fr')
- `lang_name` - English name (e.g., 'Spanish')
- `lang_native` - Native name (e.g., 'Español')

**Optional Fields:**
- `lang_locale` - Full locale (e.g., 'es_ES')
- `lang_direction` - Text direction ('ltr' or 'rtl'), default: 'ltr'
- `lang_sort` - Display order, default: 0
- `lang_is_default` - Set as default language, default: false
- `lang_is_active` - Active status, default: true

**Response (201 Created):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "lang_code": "es",
        "lang_name": "Spanish",
        "lang_native": "Español",
        "lang_locale": "es_ES",
        "lang_direction": "ltr",
        "lang_sort": 2,
        "lang_is_default": false,
        "lang_is_active": true
    },
    "message": "Language created successfully"
}
```

### Update Language

```
PUT /api/v1/languages/{code}
```

**Request Body:**
```json
{
    "lang_name": "Spanish (Castilian)",
    "lang_native": "Español",
    "lang_direction": "ltr",
    "lang_sort": 3,
    "lang_is_active": true
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "lang_code": "es",
        "lang_name": "Spanish (Castilian)",
        "lang_native": "Español",
        "lang_locale": "es_ES",
        "lang_direction": "ltr",
        "lang_sort": 3,
        "lang_is_default": false,
        "lang_is_active": true
    },
    "message": "Language updated successfully"
}
```

### Delete Language

```
DELETE /api/v1/languages/{code}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Language deleted successfully"
}
```

**Response (400 Bad Request - Cannot delete default):**
```json
{
    "success": false,
    "error": {
        "code": "CANNOT_DELETE_DEFAULT",
        "message": "Cannot delete the default language"
    }
}
```

### Set Default Language

```
POST /api/v1/languages/{code}/default
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "lang_code": "es",
        "lang_is_default": true
    },
    "message": "Default language updated successfully"
}
```

---

## Translations API

Base path: `/api/v1/translations`

### List Translations for Language

```
GET /api/v1/translations/{lang}
```

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `lang` | string | Language code (e.g., 'en', 'es') |

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `context` | string | Filter by context (e.g., 'menu', 'form') |
| `search` | string | Search in key or value |
| `page` | integer | Page number (default: 1) |
| `per_page` | integer | Items per page (default: 50) |

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "lang_id": 1,
            "translation_key": "header.nav.home",
            "translation_value": "Home",
            "translation_context": "menu",
            "translation_plurals": null,
            "is_html": false,
            "created_at": "2026-03-21T10:00:00+00:00",
            "updated_at": null
        },
        {
            "id": 2,
            "lang_id": 1,
            "translation_key": "header.nav.blog",
            "translation_value": "Blog",
            "translation_context": "menu",
            "translation_plurals": null,
            "is_html": false,
            "created_at": "2026-03-21T10:00:00+00:00",
            "updated_at": null
        }
    ],
    "meta": {
        "total": 32,
        "page": 1,
        "per_page": 50,
        "total_pages": 1
    }
}
```

### Get Single Translation

```
GET /api/v1/translations/{lang}/{key}
```

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `lang` | string | Language code |
| `key` | string | Translation key (URL encoded) |

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "lang_id": 1,
        "translation_key": "header.nav.home",
        "translation_value": "Home",
        "translation_context": "menu",
        "translation_plurals": null,
        "is_html": false,
        "created_at": "2026-03-21T10:00:00+00:00",
        "updated_at": null
    }
}
```

### Create Translation

```
POST /api/v1/translations/{lang}
```

**Request Body:**
```json
{
    "translation_key": "header.nav.about",
    "translation_value": "About",
    "translation_context": "menu",
    "translation_plurals": null,
    "is_html": false
}
```

**Required Fields:**
- `translation_key` - Dot-notation key (e.g., 'header.nav.about')
- `translation_value` - Translated string

**Optional Fields:**
- `translation_context` - Category (e.g., 'menu', 'form', 'button')
- `translation_plurals` - JSON for plural forms
- `is_html` - Contains HTML (default: false)

**Response (201 Created):**
```json
{
    "success": true,
    "data": {
        "id": 33,
        "lang_id": 1,
        "translation_key": "header.nav.about",
        "translation_value": "About",
        "translation_context": "menu",
        "translation_plurals": null,
        "is_html": false
    },
    "message": "Translation created successfully"
}
```

### Update Translation

```
PUT /api/v1/translations/{id}
```

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Translation ID |

**Request Body:**
```json
{
    "translation_value": "Updated value",
    "translation_context": "menu"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 33,
        "lang_id": 1,
        "translation_key": "header.nav.about",
        "translation_value": "Updated value",
        "translation_context": "menu"
    },
    "message": "Translation updated successfully"
}
```

### Delete Translation

```
DELETE /api/v1/translations/{id}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Translation deleted successfully"
}
```

### Export Translations

```
GET /api/v1/translations/{lang}/export
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "lang_code": "en",
        "translations": {
            "header.nav.home": "Home",
            "header.nav.blog": "Blog",
            "form.submit": "Submit",
            "form.cancel": "Cancel"
        },
        "meta": {
            "total": 4,
            "exported_at": "2026-03-21T10:00:00+00:00"
        }
    }
}
```

### Import Translations

```
POST /api/v1/translations/{lang}/import
```

**Request Body:**
```json
{
    "translations": {
        "header.nav.home": "Home",
        "header.nav.blog": "Blog",
        "form.submit": "Submit"
    },
    "mode": "merge"
}
```

**Import Modes:**
- `merge` - Add new translations, update existing (default)
- `replace` - Delete all existing and import fresh
- `update_only` - Only update existing, don't add new

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "imported": 3,
        "updated": 0,
        "skipped": 0
    },
    "message": "Import completed successfully"
}
```

### Regenerate Cache

```
POST /api/v1/translations/{lang}/cache
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Cache regenerated for language 'en'"
}
```

---

## Error Handling

All API errors follow a consistent format:

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human-readable error message",
        "details": {}
    }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource doesn't exist |
| 409 | Conflict - Resource already exists |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error |

### Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Input validation failed |
| `AUTHENTICATION_REQUIRED` | User not authenticated |
| `INSUFFICIENT_PERMISSIONS` | User lacks required role |
| `LANGUAGE_NOT_FOUND` | Language doesn't exist |
| `LANGUAGE_EXISTS` | Language code already in use |
| `CANNOT_DELETE_DEFAULT` | Cannot delete default language |
| `TRANSLATION_NOT_FOUND` | Translation doesn't exist |
| `TRANSLATION_KEY_EXISTS` | Translation key already exists |
| `INVALID_LOCALE` | Invalid language code format |
| `INVALID_KEY_FORMAT` | Invalid translation key format |
| `CACHE_ERROR` | Failed to regenerate cache |
| `IMPORT_ERROR` | Failed to import translations |
| `RATE_LIMIT_EXCEEDED` | Too many requests |

---

## Rate Limiting

API requests are rate-limited to prevent abuse.

**Limits:**
- 100 requests per minute for read operations (GET)
- 20 requests per minute for write operations (POST, PUT, DELETE)

**Rate Limit Headers:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1647868860
```

---

## Examples

### cURL Examples

**List all languages:**
```bash
curl -X GET "http://localhost/api/v1/languages" \
  -H "Content-Type: application/json"
```

**Create a new language:**
```bash
curl -X POST "http://localhost/api/v1/languages" \
  -H "Content-Type: application/json" \
  -H "Cookie: scriptlog_auth=your_session_cookie" \
  -d '{
    "lang_code": "fr",
    "lang_name": "French",
    "lang_native": "Français",
    "lang_locale": "fr_FR",
    "lang_direction": "ltr"
  }'
```

**Update a translation:**
```bash
curl -X PUT "http://localhost/api/v1/translations/1" \
  -H "Content-Type: application/json" \
  -H "Cookie: scriptlog_auth=your_session_cookie" \
  -d '{
    "translation_value": "Mise à jour"
  }'
```

**Export translations:**
```bash
curl -X GET "http://localhost/api/v1/translations/en/export" \
  -H "Content-Type: application/json"
```

### JavaScript Fetch Examples

**Get languages:**
```javascript
fetch('/api/v1/languages')
  .then(response => response.json())
  .then(data => console.log(data));
```

**Create translation with async/await:**
```javascript
async function createTranslation() {
  const response = await fetch('/api/v1/translations/en', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      translation_key: 'greeting.hello',
      translation_value: 'Hello, World!',
      translation_context: 'message'
    })
  });
  
  const data = await response.json();
  console.log(data);
}
```

### PHP cURL Examples

```php
<?php

$baseUrl = 'http://localhost/api/v1';

// Get languages
$ch = curl_init("$baseUrl/languages");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);

// Create language
$ch = curl_init("$baseUrl/languages");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'lang_code' => 'de',
    'lang_name' => 'German',
    'lang_native' => 'Deutsch'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
```

---

**Document Version:** 1.0  
**Last Updated:** March 2026
