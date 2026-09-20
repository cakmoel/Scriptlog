# ScriptLog RESTful API Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [Base URL](#base-url)
3. [Authentication](#authentication)
4. [API Endpoints](#api-endpoints)
   - [API Information](#api-information)
   - [Posts](#posts)
   - [Protected Posts](#protected-posts)
   - [Categories](#categories)
   - [Comments](#comments)
   - [Archives](#archives)
   - [Search](#search)
   - [Query (RFC 10008)](#query-rfc-10008)
   - [Languages](#languages)
   - [Translations](#translations)
   - [GDPR](#gdpr)
   - [Media Upload](#media-upload)
   - [Health Check](#health-check)
   - [System](#system)
5. [Response Format](#response-format)
6. [Error Handling](#error-handling)
7. [Filtering and Sorting](#filtering-and-sorting)
8. [Rate Limiting](#rate-limiting)
9. [HATEOAS](#hateoas)
10. [OpenAPI Specification](#openapi-specification)
11. [SDK Examples](#sdk-examples)
---



## Introduction

The ScriptLog RESTful API provides programmatic access to your blog's content, allowing other platforms, operating systems, and devices to interact with your blog data. The API follows REST architectural principles and returns JSON responses.

**API Version:** 1.1.2
**Format:** JSON

> Synchronized with `api/index.php` and `lib/controller/api/`. The machine-readable
> contract is [API_OPENAPI.yaml](./API_OPENAPI.yaml) (source of truth) and its generated
> twin [API_OPENAPI.json](../html/API_OPENAPI.json); the live runtime spec is served at
> `GET /api/v1/openapi.json`. The spec uses a relative server URL (`/api/v1`), so it
> resolves on any host — `http://blogware.site` appears only as the example host in
> human-readable examples.

---

## Base URL

| Environment | URL |
|------------|-----|
| Production | `http://blogware.site/api/v1` |
| Development | `http://localhost/blogware/public_html/api/v1` |

---

## Authentication

The API supports three authentication methods:

### API Key Authentication

Pass your API key in the `X-API-Key` header (header only — query-string keys are not accepted, minimum 32 characters):

```http
GET /api/v1/posts HTTP/1.1
Host: blogware.site
X-API-Key: your-api-key-here
```

### Bearer Token Authentication

Pass a bearer token in the `Authorization` header (minimum 32 characters):

```http
GET /api/v1/posts HTTP/1.1
Host: blogware.site
Authorization: Bearer your-bearer-token
```

### Session/Cookie Authentication

`POST /api/v1/media/upload` authenticates via the admin panel session
(`scriptlog_session_login`) or the `scriptlog_auth` cookie instead of an API
key or Bearer token. The caller level must be one of `administrator`, `manager`,
`editor`, `author` or `contributor`.

### CSRF Protection

Session-authenticated write requests (`POST`/`PUT`/`PATCH`/`DELETE` sent with a
cookie and **without** an `X-API-Key`/`Authorization` header) must include the
token from `GET /api/v1/csrf-token` as the `X-CSRF-Token` header. Missing and
invalid tokens return `403` with codes `CSRF_MISSING` and `CSRF_INVALID`.
Requests authenticated with an API key or Bearer token are exempt.

### Content-Type

Write requests must send `Content-Type: application/json`
(`application/x-www-form-urlencoded` is also accepted). Any other content type
returns `415 Unsupported Media Type`. `POST /media/upload` is the
`multipart/form-data` exception.

### Authentication Requirements

| Endpoint Type | Authentication Required |
|--------------|------------------------|
| Read (GET, QUERY) - Public content | No |
| Create/Update/Delete (POST/PUT/DELETE/PATCH) | Yes |
| `POST /media/upload` | Yes (admin session/cookie + CSRF) |

### Permission Levels

| Level | Can Create Posts | Can Edit Posts | Can Delete Posts | Can Manage Categories | Can Moderate Comments |
|-------|-----------------|----------------|------------------|----------------------|----------------------|
| administrator | Yes | Yes | Yes | Yes | Yes |
| editor | Yes | Yes | No | Yes | Yes |
| author | Yes | Own only | No | No | No |
| subscriber | No | No | No | No | No |

---

## API Endpoints

### API Information

#### Get API Information

```
GET /api/v1/
```

Returns API metadata, available endpoints, and usage information.

**Example Request:**
```bash
curl -X GET http://blogware.site/api/v1/
```

**Example Response:**
```json
{
  "success": true,
  "status": 200,
  "data": {
    "name": "Blogware RESTful API",
    "version": "1.1.2",
    "description": "RESTful API for Blogware content management system",
    "base_url": "/api/v1",
    "authentication": {
      "type": "API Key or Bearer Token",
      "header": "X-API-Key or Authorization: Bearer <token>",
      "required": false
    }
  },
  "_links": {
    "self": { "href": "http://blogware.site/api/v1", "rel": "self", "type": "GET" },
    "posts": { "href": "http://blogware.site/api/v1/posts", "rel": "posts", "type": "GET" },
    "categories": { "href": "http://blogware.site/api/v1/categories", "rel": "categories", "type": "GET" },
    "comments": { "href": "http://blogware.site/api/v1/comments", "rel": "comments", "type": "GET" },
    "archives": { "href": "http://blogware.site/api/v1/archives", "rel": "archives", "type": "GET" },
    "search": { "href": "http://blogware.site/api/v1/search?q={query}", "rel": "search", "type": "GET", "templated": true },
    "gdpr": { "href": "http://blogware.site/api/v1/gdpr/consent", "rel": "gdpr", "type": "GET" },
    "languages": { "href": "http://blogware.site/api/v1/languages", "rel": "languages", "type": "GET" },
    "translations": { "href": "http://blogware.site/api/v1/translations/en", "rel": "translations", "type": "GET" },
    "media": { "href": "http://blogware.site/api/v1/media/upload", "rel": "media", "type": "POST" },
    "openapi": { "href": "http://blogware.site/api/v1/openapi.json", "rel": "service-desc", "type": "application/json" }
  }
}
```

---

### Posts

Endpoints for managing blog posts and pages.

#### List Published Posts

```
GET /api/v1/posts
```

Retrieves a paginated list of published blog posts.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 10 | Items per page (max: 100) |
| sort_by | string | ID | Sort field (ID, post_date, post_modified, post_title) |
| sort_order | string | DESC | Sort direction (ASC, DESC) |

**Example Request:**
```bash
curl -X GET "http://blogware.site/api/v1/posts?page=1&per_page=10"
```

**Example Response:**
```json
{
  "success": true,
  "status": 200,
  "data": [
    {
      "id": 1,
      "title": "My First Blog Post",
      "slug": "my-first-blog-post",
      "content": "Full post content...",
      "summary": "Post summary...",
      "excerpt": "Generated excerpt...",
      "status": "publish",
      "visibility": "public",
      "tags": ["php", "rest-api"],
      "comment_status": "open",
      "type": "blog",
      "locale": "en",
      "author": {
        "id": 1,
        "login": "admin",
        "name": "Administrator"
      },
      "date": "2024-01-15 10:30:00",
      "modified": "2024-01-15 14:20:00",
      "url": "http://blogware.site/post/1/my-first-blog-post",
      "_links": {
        "self": { "href": "http://blogware.site/api/v1/posts/1", "rel": "self", "type": "GET" },
        "comments": { "href": "http://blogware.site/api/v1/posts/1/comments", "rel": "comments", "type": "GET" },
        "canonical": { "href": "http://blogware.site/post/1/my-first-blog-post", "rel": "canonical", "type": "text/html" },
        "collection": { "href": "http://blogware.site/api/v1/posts", "rel": "collection", "type": "GET" }
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total_items": 50,
    "total_pages": 5,
    "has_next_page": true,
    "has_previous_page": false
  },
  "_links": {
    "self": { "href": "http://blogware.site/api/v1/posts?page=1&per_page=10", "rel": "self", "type": "GET" },
    "first": { "href": "http://blogware.site/api/v1/posts?page=1&per_page=10", "rel": "first", "type": "GET" },
    "next": { "href": "http://blogware.site/api/v1/posts?page=2&per_page=10", "rel": "next", "type": "GET" },
    "last": { "href": "http://blogware.site/api/v1/posts?page=5&per_page=10", "rel": "last", "type": "GET" }
  }
}
```

---

#### Get Single Post

```
GET /api/v1/posts/{id}
```

Retrieves a single blog post by its ID.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Post ID |

**Example Request:**
```bash
curl -X GET http://blogware.site/api/v1/posts/1
```

---

#### Get Comments for Post

```
GET /api/v1/posts/{id}/comments
```

Retrieves approved comments for a specific blog post.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Post ID |

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 10 | Items per page |

---

#### Create Post

```
POST /api/v1/posts
```

Creates a new blog post. **Requires authentication.**

**Request Body:**

```json
{
  "post_title": "My New Post",
  "post_content": "Full content of the post",
  "post_summary": "Optional summary",
  "post_status": "draft",
  "post_visibility": "public",
  "post_tags": "php, api",
  "comment_status": "open",
  "topics": [1, 2]
}
```

**Required Fields:**
- `post_title` (string)
- `post_content` (string)

**Optional Fields:**
- `post_summary` (string)
- `post_status` (string: "publish", "draft")
- `post_visibility` (string: "public", "private", "protected")
- `post_tags` (string, comma-separated)
- `comment_status` (string: "open", "closed")
- `topics` (array of integers)

---

#### Update Post

```
PUT /api/v1/posts/{id}
```

Updates an existing blog post. **Requires authentication.**

---

#### Partially Update Post

```
PATCH /api/v1/posts/{id}
```

Partially updates an existing blog post. **Requires authentication.** Only send the fields you want to change.

---

#### Delete Post

```
DELETE /api/v1/posts/{id}
```

Deletes a blog post. **Requires administrator authentication.**

---

### Protected Posts

Password-protected posts are public endpoints gated by the post password
(`application/json` body `{"password": "..."}`). Both endpoints are also
used by the HTMX unlock form, which receives an HTML fragment instead of JSON.
Failed attempts are throttled per post — too many failures return `429`.

#### Unlock Password-Protected Post

```
POST /api/v1/posts/{id}/unlock
```

Verifies the password and returns the sanitized decrypted post content.
Also stores the password in `$_SESSION['unlocked_posts'][id]`.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Post ID |

**Request Body:**
```json
{
  "password": "post-password"
}
```

**Success Response (`200`):** `{ "success": true, "status": 200, "data": { "content": "<p>...</p>" } }`

**Error Responses:** `400` (missing post ID / missing password), `401` (incorrect
password), `429` (too many failed attempts), `500` (decryption unavailable/failed).

---

#### Verify Password-Protected Post

```
POST /api/v1/posts/{id}/verify
```

Lightweight password check that does **not** return content — responds with
`{ "valid": true }` on success.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Post ID |

**Request Body:**
```json
{
  "password": "post-password"
}
```

**Error Responses:** same as unlock (`400` / `401` / `429`).

---

### Categories

#### List Categories

```
GET /api/v1/categories
```

Retrieves a paginated list of all categories/topics.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 10 | Items per page |
| sort_by | string | ID | Sort field |
| sort_order | string | DESC | Sort direction |

**Example Response:**
```json
{
  "success": true,
  "status": 200,
  "data": [
    {
      "id": 1,
      "name": "Technology",
      "slug": "technology",
      "description": "Technology related posts",
      "status": "Y",
      "locale": "en",
      "post_count": 15,
      "url": "http://blogware.site/category/technology",
      "_links": {
        "self": { "href": "http://blogware.site/api/v1/categories/1", "rel": "self", "type": "GET" },
        "posts": { "href": "http://blogware.site/api/v1/categories/1/posts", "rel": "posts", "type": "GET" },
        "canonical": { "href": "http://blogware.site/category/technology", "rel": "canonical", "type": "text/html" },
        "collection": { "href": "http://blogware.site/api/v1/categories", "rel": "collection", "type": "GET" }
      }
    }
  ],
  "pagination": { ... },
  "_links": { ... }
}
```

---

#### Get Single Category

```
GET /api/v1/categories/{id}
```

Retrieves a single category by ID.

---

#### Get Posts in Category

```
GET /api/v1/categories/{id}/posts
```

Retrieves posts belonging to a specific category.

---

#### Create Category

```
POST /api/v1/categories
```

Creates a new category. **Requires authentication.**

**Request Body:**
```json
{
  "topic_title": "Category Name",
  "topic_status": "Y"
}
```

---

#### Update Category

```
PUT /api/v1/categories/{id}
```

Updates a category. **Requires authentication.**

---

#### Partially Update Category

```
PATCH /api/v1/categories/{id}
```

Partially updates a category. **Requires authentication.** Only send the fields you want to change.

---

#### Delete Category

```
DELETE /api/v1/categories/{id}
```

Deletes a category. **Requires administrator authentication.**

---

### Comments

#### List Comments

```
GET /api/v1/comments
```

Retrieves approved comments. Public endpoint.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| post_id | integer | Filter by post ID |
| page | integer | Page number |
| per_page | integer | Items per page |
| sort_by | string | Sort field |
| sort_order | string | Sort direction |

---

#### Get Single Comment

```
GET /api/v1/comments/{id}
```

Retrieves a single comment by ID. Includes nested replies.

---

#### Create Comment

```
POST /api/v1/comments
```

Creates a new comment. Public endpoint - visitors can submit comments.

**Request Body:**
```json
{
  "comment_author_name": "John Doe",
  "comment_author_email": "john@example.com",
  "comment_content": "Great article!",
  "comment_post_id": 1,
  "comment_parent_id": 0
}
```

**Note:** Comments are submitted with 'pending' status for moderation.

---

#### Update Comment

```
PUT /api/v1/comments/{id}
```

Updates a comment. **Requires authentication.**

---

#### Partially Update Comment

```
PATCH /api/v1/comments/{id}
```

Partially updates a comment. **Requires authentication.** Only send the fields you want to change.

---

#### Delete Comment

```
DELETE /api/v1/comments/{id}
```

Deletes a comment. **Requires authentication.**

---

### Archives

#### List Archive Dates

```
GET /api/v1/archives
```

Returns available archive dates (years and months with published posts). Includes HATEOAS links at response root.

**Example Response:**
```json
{
  "success": true,
  "status": 200,
  "data": {
    "archives": [
      {
        "year": 2024,
        "months": [
          {
            "month": 6,
            "month_name": "June",
            "post_count": 5
          }
        ],
        "total_posts": 25
      }
    ],
    "total_years": 3
  },
  "_links": {
    "self": { "href": "http://blogware.site/api/v1/archives", "rel": "self", "type": "GET" },
    "collection": { "href": "http://blogware.site/api/v1/archives", "rel": "collection", "type": "GET" }
  }
}
```

---

#### Get Posts by Year

```
GET /api/v1/archives/{year}
```

Retrieves posts from a specific year.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| year | integer | Year (e.g., 2024) |

---

#### Get Posts by Month

```
GET /api/v1/archives/{year}/{month}
```

Retrieves posts from a specific month and year.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| year | integer | Year (e.g., 2024) |
| month | integer | Month (1-12) |

---

### Search

#### Search Content

```
GET /api/v1/search?q={query}
```

Searches across posts and pages. Returns the result count in both `data.total`
(read by the sidebar widget) and `pagination`. Public endpoint.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| q | string | — | Search query, required (alias: `keyword`, min 2 characters) |
| type | string | all | Search scope: `posts`, `pages`, or `all` |
| page | integer | 1 | Page number |
| per_page | integer | 10 | Items per page |

A non-empty `search_verify` parameter (honeypot) is rejected with `403`. Besides
the global read rate limit, an endpoint-local limit of 30 requests/minute applies.

**Example Request:**
```bash
curl -X GET "http://blogware.site/api/v1/search?q=php&page=1&per_page=10"
```

**Error Response (missing query):**
```json
{
  "success": false,
  "status": 400,
  "error": {
    "code": "BAD_REQUEST",
    "message": "Search keyword is required"
  }
}
```

**Error Responses:** `400` (missing query / shorter than 2 characters),
`403` (honeypot), `429` (rate limit), `500` (`SEARCH_ERROR`).

---

#### Search Posts Only

```
GET /api/v1/search/posts?q={query}
```

Searches only within blog posts (`type` is forced to `posts`).

---

#### Search Pages Only

```
GET /api/v1/search/pages?q={query}
```

Searches only within static pages (`type` is forced to `pages`).

---

### Query (RFC 10008)

The `QUERY` method (safe and idempotent like `GET`) carries the query input as a
JSON request body instead of URI parameters — suitable for complex queries that
are impractical to encode in a URI. `QUERY` requests are counted under the read
rate limit and answered with an `Accept-Query: application/json` header. Public
endpoints. (In `API_OPENAPI.yaml` these operations live under the `x-query`
extension field because OpenAPI 3.0 defines no `query` operation type.)

#### Query All Content

```
QUERY /api/v1/query
```

**Request Body:**
```json
{
  "q": "php",
  "type": "all"
}
```

`q` (alias `keyword`) is required; `type` is `all` (default), `posts` or `pages`.
An empty body, or an empty keyword with `type: all`, returns `400`.

**Example Request:**
```bash
curl -X QUERY http://blogware.site/api/v1/query \
  -H "Content-Type: application/json" \
  -d '{"q": "php", "type": "all"}'
```

**Success Response (`200`):**
```json
{
  "success": true,
  "status": 200,
  "message": "Query executed successfully",
  "data": {
    "query": { "keyword": "php", "type": "all" },
    "total": 3,
    "results": [
      {
        "id": 1,
        "title": "My First Blog Post",
        "slug": "my-first-blog-post",
        "excerpt": "Matching content excerpt...",
        "type": "blog",
        "date": "2024-01-15 10:30:00",
        "status": "publish"
      }
    ]
  }
}
```

---

#### Query Posts Only

```
QUERY /api/v1/query/posts
```

Like `QUERY /query` but restricted to blog posts (`type` forced to `posts`).

---

#### Query Pages Only

```
QUERY /api/v1/query/pages
```

Like `QUERY /query` but restricted to static pages (`type` forced to `pages`).

---

### Languages

#### List Languages

```
GET /api/v1/languages
```

Returns a paginated list of all active languages (in-memory pagination over
`getActiveLanguages()`).

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 10 | Items per page |

---

#### List Active Languages

```
GET /api/v1/languages/active
```

Alias of `GET /api/v1/languages` — returns only active (enabled) languages.

---

#### Get Default Language

```
GET /api/v1/languages/default
```

Returns the current default language. (This static path is registered before
`/languages/{code}` so `default` is never mistaken for a language code.)

---

#### Get Single Language

```
GET /api/v1/languages/{code}
```

Retrieves a language by its 2-letter code (e.g., `en`, `fr`, `zh`).

---

#### Create Language

```
POST /api/v1/languages
```

Creates a new language. **Requires administrator authentication.**

**Request Body:**
```json
{
  "lang_code": "de",
  "lang_name": "German",
  "lang_native": "Deutsch",
  "lang_locale": "de_DE",
  "lang_direction": "ltr",
  "lang_sort": 0,
  "lang_is_default": 0
}
```

**Required Fields:** `lang_code`, `lang_name`, `lang_native`.
**Optional Fields:** `lang_locale` (default `null`), `lang_direction` (`ltr`/`rtl`,
default `ltr`), `lang_sort` (default `0`), `lang_is_default` (truthy → `1`, else `0`).

---

#### Update Language

```
PUT /api/v1/languages/{code}
```

Updates a language. **Requires administrator authentication.** The request body is
passed straight to the service layer (no field whitelist in the controller).

---

#### Partially Update Language

```
PATCH /api/v1/languages/{code}
```

Partially updates a language. **Requires administrator authentication.**

---

#### Set Default Language

```
PUT /api/v1/languages/{code}/default
```

Sets the specified language as the default. **Requires administrator
authentication.** `PATCH` on the same path is accepted as an alias.

---

#### Delete Language

```
DELETE /api/v1/languages/{code}
```

Deletes a language. **Requires administrator authentication.** Returns `200`
with a null data payload (not `204`). Deleting the default language fails with
`500` (`Cannot delete the default language`).

---

### Translations

#### List Translations

```
GET /api/v1/translations/{code}
```

Returns a paginated list of translation strings for a given language
(in-memory pagination; defaults to `en` when the code is absent).

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| code | string | ISO 639-1 language code (e.g., `en`) |

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 50 | Items per page |

---

#### Get Single Translation

```
GET /api/v1/translations/{code}/{key}
```

Retrieves a single translation by language code and key.

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| code | string | Language code (e.g., `en`) |
| key | string | Translation key (e.g., `nav.dashboard`) |

---

#### Create Translation

```
POST /api/v1/translations/{code}
```

Creates a new translation string. **Requires administrator authentication.**
(The `{code}` path segment is accepted but the language comes from the body.)

**Request Body:**
```json
{
  "lang_code": "en",
  "translation_key": "nav.dashboard",
  "translation_value": "Dashboard",
  "translation_context": null,
  "is_html": 0
}
```

**Required Fields:** `lang_code`, `translation_key`, `translation_value`.
**Optional Fields:** `translation_context` (default `null`), `is_html` (truthy → `1`, else `0`).

---

#### Update Translation

```
PUT /api/v1/translations/{id}
```

Updates a translation by ID. **Requires administrator authentication.**
`translation_value` is required; `translation_context` and `is_html` are optional.

---

#### Partially Update Translation

```
PATCH /api/v1/translations/{id}
```

Partially updates a translation by ID (`translation_value` still required by the
controller). **Requires administrator authentication.**

---

#### Delete Translation

```
DELETE /api/v1/translations/{id}
```

Deletes a translation by ID. **Requires administrator authentication.** Returns
`200` with a null data payload (not `204`).

---

#### Export Translations

```
GET /api/v1/translations/{code}/export
```

Exports all translations for a language as a JSON key-value map. Public endpoint.

---

#### Import Translations

```
POST /api/v1/translations/{code}/import
```

Bulk imports translations for a language. **Requires administrator
authentication.** The raw JSON body is the key-value map itself (not wrapped in
a `translations` object). Returns `201` with `{ "count": N }`.

**Request Body:**
```json
{
  "nav.dashboard": "Dashboard",
  "nav.posts": "Posts"
}
```

---

#### Clear Translation Cache

```
POST /api/v1/translations/{code}/cache
```

Regenerates the translation cache for a language. **Requires administrator
authentication.** Returns `200` with a null data payload.

---

### GDPR

#### Submit Consent

```
POST /api/v1/gdpr/consent
```

Records cookie consent for the calling client IP address together with its user
agent. Public endpoint.

**Request Body:**
```json
{
  "status": "accepted"
}
```

`status` must be `accepted` or `rejected`, otherwise `400` (`INVALID_STATUS`).

**Success Response (`200`):**
```json
{
  "success": true,
  "status": 200,
  "message": "Consent recorded",
  "data": {
    "status": "accepted",
    "message": "Consent recorded successfully",
    "timestamp": "2026-07-30 12:00:00"
  }
}
```

---

#### Get Consent Status

```
GET /api/v1/gdpr/consent?type={type}
```

Retrieves the consent status for the calling client IP address. Public endpoint.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| type | string | cookie | Consent type to check |

**Success Response (`200`):**
```json
{
  "success": true,
  "status": 200,
  "data": {
    "consent_given": true,
    "consent_type": "cookie"
  }
}
```

---

### Media Upload

#### Upload Image

```
POST /api/v1/media/upload
```

Uploads an image file (used by the Summernote editor integration).
**Requires an admin session or auth cookie** (API keys/Bearer tokens are not
accepted) with level `administrator`, `manager`, `editor`, `author` or
`contributor`, plus the `X-CSRF-Token` header for session-authenticated callers.

**Request:**
- Content-Type: `multipart/form-data`
- Field: `image` (file, required)
- Optional: `post_id` (integer — links the image to a post via `tbl_mediameta`)

**Validation:** real MIME type via `mime_content_type()` must be JPEG, PNG, GIF,
WebP or BMP (`400` otherwise); size ≤ 5 MB (`400` otherwise). Stored under a
`uniqid()_time.ext` name (resized to 3 sizes + WebP) and recorded in `tbl_media`.

**Success Response (`201`):**
```json
{
  "success": true,
  "status": 201,
  "message": "Image uploaded successfully",
  "data": {
    "url": "http://blogware.site/public/files/pictures/abc123_1720000000.jpg",
    "filename": "abc123_1720000000.jpg",
    "media_id": 42,
    "post_id": 1
  }
}
```

---

### Health Check

#### Health Check

```
GET /api/v1/health
```

Returns the API health status for monitoring and load balancers. Public endpoint.

**Example Response:**
```json
{
  "success": true,
  "status": 200,
  "message": "API is operational",
  "data": {
    "status": "healthy",
    "timestamp": "2026-07-30 12:00:00",
    "php_version": "8.1.2"
  }
}
```

---

### System

#### Get OpenAPI Specification

```
GET /api/v1/openapi.json
```

Returns the dynamic OpenAPI 3.0 specification. The `servers` entry is a relative
URL (`/api/v1`) that resolves on any host without substitution; only the logo URL
is replaced at runtime from the app configuration. Served from the `openapi.json`
file at the project root. Public endpoint.

---

#### Get CSRF Token

```
GET /api/v1/csrf-token
```

Returns a CSRF token for session-authenticated write operations. Public endpoint.
Send the token back as the `X-CSRF-Token` header (see [CSRF Protection](#csrf-protection)).

**Example Response:**
```json
{
  "success": true,
  "status": 200,
  "message": "CSRF token generated. Include as X-CSRF-Token header on session-authenticated write requests.",
  "data": {
    "csrf_token": "a1b2c3d4...",
    "expires_in": 3600
  }
}
```

---

## Response Format

All responses follow a consistent JSON structure:

### Success Response

```json
{
  "success": true,
  "status": 200,
  "message": "Operation description",
  "data": { ... }
}
```

Single resources include `_links` at the response root. `message` is omitted
(`null`) on endpoints that pass none. Language and translation deletes return
`200` with `"data": null` rather than `204`.

### Paginated Response

```json
{
  "success": true,
  "status": 200,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total_items": 50,
    "total_pages": 5,
    "has_next_page": true,
    "has_previous_page": false
  },
  "_links": {
    "self": { "href": "...", "rel": "self", "type": "GET" },
    "first": { "href": "...", "rel": "first", "type": "GET" },
    "prev": { "href": "...", "rel": "prev", "type": "GET" },
    "next": { "href": "...", "rel": "next", "type": "GET" },
    "last": { "href": "...", "rel": "last", "type": "GET" }
  }
}
```

### Created Response

```json
{
  "success": true,
  "status": 201,
  "message": "Resource created",
  "data": { "id": 42 }
}
```

Includes `Location` header with the resource URL.

### No Content Response

```json
{
  "success": true,
  "status": 204
}
```

Used for DELETE operations. No response body.

### Error Response

```json
{
  "success": false,
  "status": 400,
  "error": {
    "code": "BAD_REQUEST",
    "message": "Error description"
  }
}
```

---

## Error Handling

The API uses standard HTTP status codes:

| Status Code | Meaning | Description |
|-------------|---------|-------------|
| 200 | OK | Request succeeded |
| 201 | Created | Resource created successfully |
| 204 | No Content | Request succeeded, no content to return |
| 304 | Not Modified | Resource not modified (conditional GET) |
| 400 | Bad Request | Invalid parameters or missing required fields |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Authenticated but insufficient permissions (also honeypot hits) |
| 404 | Not Found | Resource does not exist |
| 405 | Method Not Allowed | HTTP method not supported (with `Allow` header) |
| 406 | Not Acceptable | Unsupported Accept header |
| 409 | Conflict | Resource already exists or conflict |
| 415 | Unsupported Media Type | Wrong Content-Type header on write requests |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Error Codes

| Code | Description |
|------|-------------|
| BAD_REQUEST | Invalid request parameters |
| UNAUTHORIZED | Authentication required |
| FORBIDDEN | Insufficient permissions |
| NOT_FOUND | Resource not found |
| METHOD_NOT_ALLOWED | HTTP method not supported |
| NOT_ACCEPTABLE | Unsupported Accept header |
| UNSUPPORTED_MEDIA_TYPE | Wrong Content-Type header |
| CONFLICT | Resource already exists |
| VALIDATION_ERROR | Validation failed (`422`, with per-field `details`) |
| CSRF_MISSING / CSRF_INVALID | Missing or invalid `X-CSRF-Token` header (`403`) |
| INVALID_STATUS | Bad GDPR consent status (must be `accepted`/`rejected`) |
| SEARCH_ERROR | Search backend failure (`500`) |
| FETCH_ERROR / CREATE_ERROR / UPDATE_ERROR / DELETE_ERROR | Controller operation failures (`500`) |
| EXPORT_ERROR / IMPORT_ERROR / CACHE_ERROR | Translation export/import/cache failures (`500`) |
| CONSENT_FAILED / CONSENT_STATUS_FAILED | GDPR write/read failures (`500`) |
| RATE_LIMIT_EXCEEDED | Too many requests |
| INTERNAL_SERVER_ERROR | Server error |

---

## Filtering and Sorting

### Query Parameters

| Parameter | Description | Supported Endpoints |
|-----------|-------------|-------------------|
| page | Page number for pagination | Posts, Categories, Comments, Archives, Search, Languages, Translations |
| per_page | Number of items per page (max: 100) | Posts, Categories, Comments, Archives, Search, Languages, Translations |
| sort_by | Field to sort by | Posts, Categories, Comments |
| sort_order | Sort direction (ASC or DESC) | Posts, Categories, Comments |
| type | Search scope (`all`, `posts`, `pages`) | Search |
| q / keyword | Search keyword (min 2 characters) | Search |

> `QUERY /query*` endpoints do not paginate — they return all matches with
> `data.total` and `data.results`.

### Example

```bash
# Get posts sorted by date, descending, page 2, 20 items per page
curl -X GET "http://blogware.site/api/v1/posts?sort_by=post_date&sort_order=DESC&page=2&per_page=20"
```

---

## Rate Limiting

API requests are rate limited to ensure fair usage and prevent abuse. Rate limiting is applied per-client using IP address, API key, or Bearer token as the identifier.

### Rate Limits

| Endpoint Type | Limit | Window |
|--------------|-------|--------|
| **Read (GET, QUERY)** | 60 requests | 60 seconds |
| **Write (POST/PUT/DELETE/PATCH)** | 20 requests | 60 seconds |
| **Search (GET /search\*)** | 30 requests | 60 seconds (endpoint-local, on top of the read limit) |

### Rate Limit Headers

All API responses include rate limit headers:

| Header | Description |
|--------|-------------|
| X-RateLimit-Limit | Maximum requests allowed per window |
| X-RateLimit-Remaining | Remaining requests in current window |
| X-RateLimit-Reset | Unix timestamp when the rate limit resets |
| Retry-After | Seconds to wait before retrying (only on 429 responses) |

### Rate Limit Exceeded

If you exceed the rate limit, you'll receive a `429 Too Many Requests` response:

```json
{
  "success": false,
  "status": 429,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Please slow down."
  }
}
```

### Client Identification

Rate limits are tracked per client using the following priority:
1. **API Key** (`X-API-Key` header) - if provided
2. **Bearer Token** (`Authorization` header) - if provided
3. **IP Address** (`REMOTE_ADDR`) - fallback

### Rate Limiting Configuration

Navigate to **Settings → API** in the admin panel to configure (stored in
`tbl_settings`):

| Setting (key) | Description | Default |
|---------|-------------|---------|
| Enable Rate Limiting (`api_rate_limit_enabled`) | Toggle rate limiting on/off | Enabled |
| Read Rate Limit (`api_rate_limit_read`) | Maximum read requests per minute | 60 |
| Write Rate Limit (`api_rate_limit_write`) | Maximum write requests per minute | 20 |

---

## HATEOAS (Hypermedia as the Engine of Application State)

All API responses include HATEOAS links following [RFC 5988 (Web Linking)](https://tools.ietf.org/html/rfc5988). This allows clients to discover available actions dynamically without hardcoding URLs.

### Response Structure

Every response includes a `_links` object with discoverable navigation:

```json
{
  "success": true,
  "status": 200,
  "data": { ... },
  "_links": {
    "self": {
      "href": "http://blogware.site/api/v1/posts/1",
      "rel": "self",
      "type": "GET"
    },
    "collection": {
      "href": "http://blogware.site/api/v1/posts",
      "rel": "collection",
      "type": "GET"
    }
  }
}
```

### Common Link Relations

| Relation | Description |
|----------|-------------|
| `self` | The current resource URL |
| `collection` | The parent collection URL |
| `first` | First page of paginated results |
| `prev` | Previous page of paginated results |
| `next` | Next page of paginated results |
| `last` | Last page of paginated results |
| `canonical` | The canonical HTML URL for the resource |
| `comments` | Comments for a post |
| `post` | The parent post for a comment |
| `posts` | Posts in a category |
| `year` | Year archive for a month |
| `search` | Search endpoint (templated URL) |
| `service-desc` | OpenAPI specification URL |

### Root API Links

The API root (`GET /api/v1/`) returns links to all available endpoints:

```json
{
  "success": true,
  "status": 200,
  "data": {
    "name": "Blogware RESTful API",
    "version": "1.1.2"
  },
  "_links": {
    "self": { "href": "http://blogware.site/api/v1", "rel": "self", "type": "GET" },
    "posts": { "href": "http://blogware.site/api/v1/posts", "rel": "posts", "type": "GET" },
    "categories": { "href": "http://blogware.site/api/v1/categories", "rel": "categories", "type": "GET" },
    "comments": { "href": "http://blogware.site/api/v1/comments", "rel": "comments", "type": "GET" },
    "archives": { "href": "http://blogware.site/api/v1/archives", "rel": "archives", "type": "GET" },
    "search": { "href": "http://blogware.site/api/v1/search?q={query}", "rel": "search", "type": "GET", "templated": true },
    "gdpr": { "href": "http://blogware.site/api/v1/gdpr/consent", "rel": "gdpr", "type": "GET" },
    "languages": { "href": "http://blogware.site/api/v1/languages", "rel": "languages", "type": "GET" },
    "translations": { "href": "http://blogware.site/api/v1/translations/en", "rel": "translations", "type": "GET" },
    "media": { "href": "http://blogware.site/api/v1/media/upload", "rel": "media", "type": "POST" },
    "openapi": { "href": "http://blogware.site/api/v1/openapi.json", "rel": "service-desc", "type": "application/json" }
  }
}
```

### Paginated Response with HATEOAS

```json
{
  "success": true,
  "status": 200,
  "data": [ ... ],
  "pagination": {
    "current_page": 2,
    "per_page": 10,
    "total_items": 50,
    "total_pages": 5,
    "has_next_page": true,
    "has_previous_page": true
  },
  "_links": {
    "self": { "href": "http://blogware.site/api/v1/posts?page=2&per_page=10", "rel": "self", "type": "GET" },
    "first": { "href": "http://blogware.site/api/v1/posts?page=1&per_page=10", "rel": "first", "type": "GET" },
    "prev": { "href": "http://blogware.site/api/v1/posts?page=1&per_page=10", "rel": "prev", "type": "GET" },
    "next": { "href": "http://blogware.site/api/v1/posts?page=3&per_page=10", "rel": "next", "type": "GET" },
    "last": { "href": "http://blogware.site/api/v1/posts?page=5&per_page=10", "rel": "last", "type": "GET" }
  }
}
```

### Single Resource with HATEOAS

```json
{
  "success": true,
  "status": 200,
  "data": {
    "id": 1,
    "title": "My First Blog Post",
    "slug": "my-first-blog-post"
  },
  "_links": {
    "self": { "href": "http://blogware.site/api/v1/posts/1", "rel": "self", "type": "GET" },
    "comments": { "href": "http://blogware.site/api/v1/posts/1/comments", "rel": "comments", "type": "GET" },
    "canonical": { "href": "http://blogware.site/post/1/my-first-blog-post", "rel": "canonical", "type": "text/html" },
    "collection": { "href": "http://blogware.site/api/v1/posts", "rel": "collection", "type": "GET" }
  }
}
```

---

## OpenAPI Specification

The complete OpenAPI 3.0 specification is available in two formats:

- **YAML** (source of truth): [API_OPENAPI.yaml](./API_OPENAPI.yaml)
- **JSON** (generated from the YAML): [API_OPENAPI.json](../html/API_OPENAPI.json)
- **Live runtime spec**: `GET http://blogware.site/api/v1/openapi.json` (relative server
  URL resolves on any host; logo URL substituted from config; served from `openapi.json`
  at the project root)

You can use these files to:

- Generate client SDKs
- Validate API responses
- Import into API testing tools (Postman, Swagger UI)
- Auto-generate documentation

### Using with Swagger UI

1. Copy the `API_OPENAPI.json` file to a web server
2. Navigate to [Swagger Editor](https://editor.swagger.io/)
3. Paste the JSON content
4. Explore the interactive API documentation

### Using with Postman

1. Open Postman
2. Click Import
3. Select "Import from link"
4. Enter: `http://blogware.site/api/v1/openapi.json`

---

## SDK Examples

### JavaScript/Fetch

```javascript
const baseUrl = 'http://blogware.site/api/v1';

// Get posts
const response = await fetch(`${baseUrl}/posts`);
const data = await response.json();

// Get single post
const post = await fetch(`${baseUrl}/posts/1`);

// Create comment (no auth required)
const comment = await fetch(`${baseUrl}/comments`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    comment_author_name: 'John Doe',
    comment_author_email: 'john@example.com',
    comment_content: 'Great article!',
    comment_post_id: 1
  })
});
```

### PHP

```php
$baseUrl = 'http://blogware.site/api/v1';

// Get posts
$response = file_get_contents($baseUrl . '/posts');
$posts = json_decode($response, true);

// Get posts with authentication
$context = stream_context_create([
  'http' => [
    'header' => "X-API-Key: your-api-key\r\n"
  ]
]);
$response = file_get_contents($baseUrl . '/posts', false, $context);
```

### Python

```python
import requests

base_url = 'http://blogware.site/api/v1'

# Get posts
response = requests.get(f'{base_url}/posts')
posts = response.json()

# Get posts with authentication
headers = {'X-API-Key': 'your-api-key'}
response = requests.get(f'{base_url}/posts', headers=headers)

# Create comment
data = {
    'comment_author_name': 'John Doe',
    'comment_author_email': 'john@example.com',
    'comment_content': 'Great article!',
    'comment_post_id': 1
}
response = requests.post(f'{base_url}/comments', json=data)
```

### cURL

```bash
# Get posts
curl http://blogware.site/api/v1/posts

# Get posts with authentication
curl -H "X-API-Key: your-api-key" http://blogware.site/api/v1/posts

# QUERY request (RFC 10008, JSON body)
curl -X QUERY http://blogware.site/api/v1/query \
  -H "Content-Type: application/json" \
  -d '{"q": "php", "type": "posts"}'

# Create comment
curl -X POST http://blogware.site/api/v1/comments \
  -H "Content-Type: application/json" \
  -d '{
    "comment_author_name": "John Doe",
    "comment_author_email": "john@example.com",
    "comment_content": "Great article!",
    "comment_post_id": 1
  }'
```

---

## Support

For issues and questions:
- Email: alanmoehammad@gmail.com
- Documentation: https://blogware.site/docs/

---


