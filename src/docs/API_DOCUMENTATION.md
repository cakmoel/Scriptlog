# ScriptLog RESTful API Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [Base URL](#base-url)
3. [Authentication](#authentication)
4. [API Endpoints](#api-endpoints)
   - [API Information](#api-information)
   - [Posts](#posts)
   - [Categories](#categories)
   - [Comments](#comments)
   - [Archives](#archives)
5. [Response Format](#response-format)
6. [Error Handling](#error-handling)
7. [Filtering and Sorting](#filtering-and-sorting)
8. [Rate Limiting](#rate-limiting)
9. [OpenAPI Specification](#openapi-specification)

---

## Introduction

The ScriptLog RESTful API provides programmatic access to your blog's content, allowing other platforms, operating systems, and devices to interact with your blog data. The API follows REST architectural principles and returns JSON responses.

**API Version:** 1.1.1  
**Format:** JSON

---

## Base URL

| Environment | URL |
|------------|-----|
| Production | `http://blogware.site/api/v1` |
| Development | `http://localhost/blogware/public_html/api/v1` |

---

## Authentication

The API supports two authentication methods:

### API Key Authentication

Pass your API key in the `X-API-Key` header:

```http
GET /api/v1/posts HTTP/1.1
Host: blogware.site
X-API-Key: your-api-key-here
```

### Bearer Token Authentication

Pass a bearer token in the `Authorization` header:

```http
GET /api/v1/posts HTTP/1.1
Host: blogware.site
Authorization: Bearer your-bearer-token
```

### Authentication Requirements

| Endpoint Type | Authentication Required |
|--------------|------------------------|
| Read (GET) - Public content | No |
| Create/Update/Delete (POST/PUT/DELETE) | Yes |

### Permission Levels

| Level | Can Create Posts | Can Edit Posts | Can Delete Posts | Can Manage Categories | Can Moderate Comments |
|-------|-----------------|----------------|------------------|----------------------|----------------------|
| administrator | Yes | Yes | Yes | Yes | Yes |
| editor | Yes | Yes | No | Yes | Yes |
| author | Yes | Own only | No | No | No |
| subscriber | No | No | No | No | No |

---

## API Endpoints

### API Settings (Admin Panel)

The API includes configurable rate limiting settings that can be managed through the admin panel.

#### Access API Settings

Navigate to **Settings → API** in the admin panel:

```
https://blogware.site/admin/index.php?load=option-api&action=apiConfig&Id=0
```

#### Rate Limiting Configuration

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Rate Limiting | Toggle rate limiting on/off | Enabled |
| Read Rate Limit | Maximum GET requests per minute per client | 60 |
| Write Rate Limit | Maximum POST/PUT/DELETE/PATCH requests per minute per client | 20 |

**Note:** Rate limiting settings are stored in the `tbl_settings` table with keys:
- `api_rate_limit_enabled` (0 or 1)
- `api_rate_limit_read` (1-1000)
- `api_rate_limit_write` (1-500)

---

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
  "message": "Welcome to Blogware RESTful API",
  "data": {
    "name": "Blogware RESTful API",
    "version": "1.1.1",
    "description": "RESTful API for Blogware content management system",
    "base_url": "/api/v1",
    "authentication": {
      "type": "API Key or Bearer Token",
      "header": "X-API-Key or Authorization: Bearer <token>",
      "required": true
    }
  }
}
```

---

### Posts

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
      "author": {
        "id": 1,
        "login": "admin",
        "name": "Administrator"
      },
      "date": "2024-01-15 10:30:00",
      "modified": "2024-01-15 14:20:00",
      "url": "http://blogware.site/post/1/my-first-blog-post"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total_items": 50,
    "total_pages": 5,
    "has_next_page": true,
    "has_previous_page": false
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
      "title": "Technology",
      "slug": "technology",
      "status": "Y",
      "post_count": 15,
      "url": "http://blogware.site/category/technology"
    }
  ],
  "pagination": {...}
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

Retrieves a single comment by ID.

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

Returns available archive dates (years and months with published posts).

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
  }
}
```

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
| 400 | Bad Request | Invalid parameters or missing required fields |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Authenticated but insufficient permissions |
| 404 | Not Found | Resource does not exist |
| 405 | Method Not Allowed | HTTP method not supported |
| 409 | Conflict | Resource already exists |
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
| CONFLICT | Resource already exists |
| VALIDATION_ERROR | Validation failed |
| RATE_LIMIT_EXCEEDED | Too many requests |
| INTERNAL_SERVER_ERROR | Server error |

---

## Filtering and Sorting

### Query Parameters

| Parameter | Description |
|-----------|-------------|
| page | Page number for pagination |
| per_page | Number of items per page (max: 100) |
| sort_by | Field to sort by |
| sort_order | Sort direction (ASC or DESC) |

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
| **Read (GET)** | 60 requests | 60 seconds |
| **Write (POST/PUT/DELETE/PATCH)** | 20 requests | 60 seconds |

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
    "version": "1.0.0"
  },
  "_links": {
    "self": {
      "href": "http://blogware.site/api/v1",
      "rel": "self",
      "type": "GET"
    },
    "posts": {
      "href": "http://blogware.site/api/v1/posts",
      "rel": "posts",
      "type": "GET"
    },
    "categories": {
      "href": "http://blogware.site/api/v1/categories",
      "rel": "categories",
      "type": "GET"
    },
    "comments": {
      "href": "http://blogware.site/api/v1/comments",
      "rel": "comments",
      "type": "GET"
    },
    "archives": {
      "href": "http://blogware.site/api/v1/archives",
      "rel": "archives",
      "type": "GET"
    },
    "search": {
      "href": "http://blogware.site/api/v1/search?q={query}",
      "rel": "search",
      "type": "GET",
      "templated": true
    },
    "openapi": {
      "href": "http://blogware.site/api/v1/openapi.json",
      "rel": "service-desc",
      "type": "application/json"
    }
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
    "self": {
      "href": "http://blogware.site/api/v1/posts?page=2&per_page=10",
      "rel": "self",
      "type": "GET"
    },
    "first": {
      "href": "http://blogware.site/api/v1/posts?page=1&per_page=10",
      "rel": "first",
      "type": "GET"
    },
    "prev": {
      "href": "http://blogware.site/api/v1/posts?page=1&per_page=10",
      "rel": "prev",
      "type": "GET"
    },
    "next": {
      "href": "http://blogware.site/api/v1/posts?page=3&per_page=10",
      "rel": "next",
      "type": "GET"
    },
    "last": {
      "href": "http://blogware.site/api/v1/posts?page=5&per_page=10",
      "rel": "last",
      "type": "GET"
    }
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
    "self": {
      "href": "http://blogware.site/api/v1/posts/1",
      "rel": "self",
      "type": "GET"
    },
    "comments": {
      "href": "http://blogware.site/api/v1/posts/1/comments",
      "rel": "comments",
      "type": "GET"
    },
    "canonical": {
      "href": "http://blogware.site/post/1/my-first-blog-post",
      "rel": "canonical",
      "type": "text/html"
    },
    "collection": {
      "href": "http://blogware.site/api/v1/posts",
      "rel": "collection",
      "type": "GET"
    }
  }
}
```

---

## OpenAPI Specification

The complete OpenAPI 3.0 specification is available in two formats:

- **YAML**: [API_OPENAPI.yaml](./API_OPENAPI.yaml)
- **JSON**: [API_OPENAPI.json](./API_OPENAPI.json)

You can use these files to:

- Generate client SDKs
- Validate API responses
- Import into API testing tools (Postman, Swagger UI)
- Auto-generate documentation

### Using with Swagger UI

To view the API documentation in Swagger UI:

1. Copy the `API_OPENAPI.json` file to a web server
2. Navigate to [Swagger Editor](https://editor.swagger.io/)
3. Paste the JSON content
4. Explore the interactive API documentation

### Using with Postman

To import into Postman:

1. Open Postman
2. Click Import
3. Select "Import from link"
4. Enter: `http://blogware.site/docs/API_OPENAPI.json`

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
  headers: {
    'Content-Type': 'application/json'
  },
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

## Changelog

### Version 1.1.1 (2026-04-04)
- **Caching**: GET responses are now cacheable with `Cache-Control: public, max-age=300`
  - `ETag` header for entity tag cache validation
  - `Last-Modified` header for timestamp-based validation
  - `304 Not Modified` response for conditional requests (`If-None-Match`, `If-Modified-Since`)
  - `Vary: Accept, Accept-Encoding, X-API-Key` header for proper cache keying
- **HTTP Compliance**:
  - `Location` header on all `201 Created` responses
  - `204 No Content` for `DELETE` operations (was `200 OK`)
  - `406 Not Acceptable` for unsupported `Accept` header values
  - `PATCH` method support for partial updates (Posts, Categories, Comments)
  - `Allow` header on `405 Method Not Allowed` responses
- **REST Semantic Fixes**:
  - Changed `POST /languages/{code}/default` to `PUT` (proper state change semantics)

### Version 1.1.0 (2026-04-04)
- **Rate Limiting**: Implemented file-based rate limiting with sliding window
  - 60 requests/minute for read operations (GET)
  - 20 requests/minute for write operations (POST/PUT/DELETE/PATCH)
  - Per-client tracking by API key, Bearer token, or IP address
  - Standard rate limit headers (X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset)
- **HATEOAS**: Added Hypermedia as the Engine of Application State to all responses
  - `_links` object in every response following RFC 5988 (Web Linking)
  - Pagination links (self, first, prev, next, last)
  - Resource links (self, collection, canonical, comments, post)
  - Root API links for endpoint discovery
  - Templated search URL support

### Version 1.0.0 (2024-01-15)
- Initial release
- Posts CRUD operations
- Categories CRUD operations
- Comments CRUD operations
- Archives by date
- API Key and Bearer Token authentication
- OpenAPI 3.0 specification
