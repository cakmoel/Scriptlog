# Blog Search API Routing — End-to-End Test Specification

> **Application**: Scriptlog (PHP 7.4+ / MariaDB / Bootstrap / jQuery)
> **Spec Version**: 1.0
> **Feature Owner**: Blogware Team
> **Bootstrap**: `e2e/seed.spec.ts`
> **Regression**: `docs/dev-docs/TROUBLESHOOTING.md` issue 55
> **Harness**: `playwright.config.js` (blog theme, `workers: 1`, `http://127.0.0.1:8099` + `e2e/router.php`)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Environment & Harness](#2-environment--harness)
3. [Datasets, Fixtures & Helpers](#3-datasets-fixtures--helpers)
4. [The Transport Contract (Verified Baseline)](#4-the-transport-contract-verified-baseline)
5. [Scenario 1 — API Info Root `/api/v1`](#5-scenario-1--api-info-root-apiv1)
6. [Scenario 2 — API Search `/api/v1/search`](#6-scenario-2--api-search-apiv1search)
7. [Scenario 3 — Bare `/api` and `/api/`](#7-scenario-3--bare-api-and-api)
8. [Scenario 4 — Look-Alike Prefix `/apiary`](#8-scenario-4--look-alike-prefix-apiary)
9. [Scenario 5 — Frontend Routes Unaffected](#9-scenario-5--frontend-routes-unaffected)
10. [Scenario 6 — End-to-End Widget Proof](#10-scenario-6--end-to-end-widget-proof)
11. [Scenario 7 — Failure-Mode / No-Redirect Assertions](#11-scenario-7--failure-mode--no-redirect-assertions)
12. [Security Expectations](#12-security-expectations)
13. [Accessibility](#13-accessibility)
14. [Responsive Behaviour](#14-responsive-behaviour)
15. [Console & Network Expectations](#15-console--network-expectations)
16. [Failure Signatures & Recovery](#16-failure-signatures--recovery)
17. [Documented Boundaries (Observed, Not Bugs)](#17-documented-boundaries-observed-not-bugs)
18. [Out of Scope](#18-out-of-scope)
19. [Screenshots](#19-screenshots)
20. [Test Scenarios (Prioritised Summary)](#20-test-scenarios-prioritised-summary)
21. [Requirement Traceability](#21-requirement-traceability)

---

## 1. Overview

### 1.1 Purpose

Lock the **routing / transport contract** between the public front controller (`index.php`) and the
REST API (`api/index.php`) as seen from a browser, on a deployment where the web server sends every
unknown path to `index.php` (nginx / DDEV / PHP built-in server / default LiteSpeed).

The blog theme sidebar autocomplete is the user-facing canary: it calls `GET /api/v1/search` with
`dataType: 'json'` (`public/themes/blog/assets/js/search.js`). If `/api/*` falls through to the
theme, the widget shows *"An error occurred during search. Please try again later."* even though the
API itself is healthy. This spec proves the fix at three levels:

| Level | Question answered |
|-------|-------------------|
| HTTP (no browser) | Does `/api/v1…` answer JSON instead of the theme 404 / HTML search page? |
| Boundary | Do `/apiary` and normal frontend routes still take the **non**-API branch? |
| Browser widget | Does the real XHR get 200 JSON, stay on `/api/v1/search`, and render suggestions? |

### 1.2 Regression Summary

* **Symptom**: on nginx/DDEV, `/api/v1` rendered the blog theme 404 (HTML, `content-type: text/html`);
  `/api/v1/search?q=…` rendered the full HTML search page with status 200. jQuery's `dataType: 'json'`
  parse failed, so the widget's `error` callback always rendered the generic error string.
* **Root cause**: the web server never routed `/api/*` to `api/index.php`. nginx's catch-all
  `try_files … /index.php?$query_string` handed `/api/v1` to the main front controller.
* **Fix under test**: `index.php` (lines ~14–31) requires `lib/utility/api-request.php` and, when
  `scriptlog_is_api_request(null, null, __DIR__)` is true, requires `api/index.php` and exits **before**
  `lib/main.php` bootstraps. Apache still short-circuits `/api/*` via `.htaccess`; fresh nginx installs
  get a `location ~ ^/api/` block from `install/include/setup.php`.
* **Harness change already made**: `e2e/router.php` no longer special-cases `/api`. Every path that is
  not a real file goes to `index.php`, exactly like nginx. A direct hit on `/api/index.php` is still
  executed as a real file (see §2.3 — this matters for test design).

### 1.3 Key Files Under Test

| Layer | File | Role |
|-------|------|------|
| Front controller | `index.php` | API hand-off before `lib/main.php` bootstrap |
| Helper | `lib/utility/api-request.php` | `scriptlog_is_api_request()`, path normalisation, install-base stripping |
| API entry | `api/index.php` | API bootstrap, CORS/security headers, JSON router, rate-limit headers |
| API controller | `lib/controller/api/ApiController.php` | `GET /api/v1` info payload (version, endpoints, HATEOAS) |
| API controller | `lib/controller/api/SearchApiController.php` | `GET /api/v1/search` (2-char minimum → 400 JSON) |
| Search engine | `lib/core/SearchFinder.php` | FULLTEXT `MATCH … AGAINST (… IN BOOLEAN MODE)` on `tbl_posts` |
| Theme widget | `public/themes/blog/sidebar.php` | `#ajax-search-form`, `#search-keyword`, `#search-results`, `#search-error` |
| Theme widget | `public/themes/blog/assets/js/search.js` | 300 ms debounce, `$.ajax({url: api_url + '/search', dataType: 'json'})` |
| Theme shell | `public/themes/blog/header.php` | `scriptlog_vars.api_url = '{app_url}/api/v1'` |
| Harness | `e2e/router.php` | Static passthrough, everything else → `index.php` |
| Harness | `e2e/blog-selectors.ts` | `BLOG`, `BLOG_BASE_URL` |
| Harness | `e2e/gdpr-fixtures.ts` | `runSql`, `clearRateLimiters` |
| Unit | `tests/unit/ApiFrontControllerHandoffTest.php` | Path matching + ordering (not replaced by this spec) |

### 1.4 Why This Plan Is Not a Duplicate

| Existing coverage | What it already proves | What this plan adds |
|-------------------|------------------------|---------------------|
| `e2e/blog-search-autocomplete.spec.ts` | Widget behaviour: suggestions, keyboard nav, empty state, submit, clear, XHR success contract | Routing matrix for `/api/v1`, `/api`, `/api/`, `/apiary`, frontend controls; header/body discriminators; prefix boundary |
| `e2e/security-hardening.spec.ts` | `/api/v1/search?q=%27` → 400 `BAD_REQUEST`; SQLi probes; headers | No HTML error pages on `/api/*`; no `Location` leak; JSON envelope for unrouted API paths |
| `e2e/theme-baseline.spec.ts` | `/search?q=Test` → 200 HTML | Frontend 200 used as the **negative control** for the hand-off (must not become JSON) |
| `tests/unit/ApiFrontControllerHandoffTest.php` | `scriptlog_is_api_request()` matching in isolation | The same contract through a real HTTP server + a real browser XHR |

---

## 2. Environment & Harness

### 2.1 Runtime (already running — do not start or restart)

| Item | Value |
|------|-------|
| Base URL | `http://127.0.0.1:8099` (`BLOG_BASE_URL` in `e2e/blog-selectors.ts`) |
| Server | PHP built-in server with `e2e/router.php`, DB `blogware_e2e` |
| Config | `playwright.config.js`, blog theme, `workers: 1`, `reuseExistingServer: true` |
| Projects | `chromium`, `firefox`, `webkit` (all three run every scenario) |
| Never | Run two Playwright configs concurrently; start/restart the server |

### 2.2 Router Behaviour That This Spec Depends On

`e2e/router.php`:

1. Serves a request statically **iff** `realpath(docroot + path)` is an existing file.
2. Otherwise `require __DIR__ . '/../index.php'` — **including `/api/*`**.

Therefore, in this harness:

* `GET /api/v1` and `GET /api/v1/search` reach `index.php` and exercise the new hand-off branch.
* `GET /api/index.php` is served **as a real file** and never reaches the hand-off.
* `GET /apiary` reaches `index.php` and must fall through to the normal dispatcher (theme 404).

### 2.3 Test-Design Rule (important)

> **Never use `/api/index.php` as evidence that the hand-off works.** That path bypasses the code under
> test and returns `404 {"success":false,…,"message":"API endpoint not found: /api/index.php"}` even
> with the regression present. Every transport assertion must use a `/api/v1…` URL (or the bare
> `/api` / `/api/` roots).

### 2.4 Environment Quirks Worth Knowing

* `e2e/router.php` spoofs `HTTPS=on` only for **document** GETs (`Accept: text/html`). API XHRs carry
  `Accept: application/json, text/javascript, */*; q=0.01`, so they are treated as plain HTTP. Do not
  assert `Secure` cookie behaviour here.
* The API issues `Set-Cookie: _scriptlog=…; HttpOnly; SameSite=lax` — a normal side effect, not a
  redirect.
* Rate limit is **60 GET/min per client IP**, and every e2e request comes from `127.0.0.1`. Call
  `clearRateLimiters()` in `beforeAll` and keep the probe list small.

---

## 3. Datasets, Fixtures & Helpers

### 3.1 Fixture — Routing/Search Post

Seeded idempotently with `runSql()` (same pattern as `e2e/blog-search-autocomplete.spec.ts`) so this
spec does not depend on fixtures owned by another spec file.

| Field | Value |
|-------|-------|
| `ID` | `99970` |
| `post_title` | `API Handoff Beacon` |
| `post_slug` | `api-handoff-beacon` |
| `post_content` | `<p>Routing handoff fixture for the front-controller API regression.</p>` |
| `post_summary` | `Routing handoff fixture summary` |
| `post_tags` | `handoff,e2e` |
| `post_status` | `publish` |
| `post_visibility` | `public` |
| `post_type` | `blog` |
| `post_locale` | `en` |
| `comment_status` | `closed` |
| `post_author` | `1` |

Use `INSERT … ON DUPLICATE KEY UPDATE` on the same columns as the existing autocomplete spec so a
re-run rewrites title/content/status and never depends on leftover state.

**Search keyword**: `handoff` (7 characters).

* It satisfies the widget's `minLength: 2` and the API's `mb_strlen($keyword) >= 2` gate.
* `innodb_ft_min_token_size = 3`, so 3+ character tokens are indexed.
* `SearchFinder::buildBooleanQuery()` prepends `+` and does **not** append `*`, so matching is
  **whole-word**: `han` returns 0 hits, `handoff` returns the fixture. Scenario 6 therefore types the
  complete keyword (see §10.1).
* Keyword must not be a stopword and must not appear in unrelated seeded posts (verify with
  `GET /api/v1/search?q=handoff` → `data.total` equals the number of fixture rows only).

### 3.2 Page Under Test for the Widget

`/?p=11` — `Test Numeric Post`, `publish` / `public` / `blog` in `blogware_e2e`. Verified to render the
sidebar form: `<form id="ajax-search-form">`, `<input id="search-keyword">`, and
`scriptlog_vars.api_url = 'http://127.0.0.1:8099/api/v1'`.

> Do **not** assume post ID `1` exists. IDs `11` and `12` are the known-good seeded posts; `12` is
> protected and must not be used here.

### 3.3 Helpers & Fixtures to Reuse

| Helper | Source | Use in this plan |
|--------|--------|------------------|
| `BLOG_BASE_URL` | `e2e/blog-selectors.ts` | Absolute URLs for `request`/`page.goto` |
| `BLOG` | `e2e/blog-selectors.ts` | All DOM selectors (never hard-code selectors inline) |
| `runSql` | `e2e/gdpr-fixtures.ts` | Seed/refresh fixture post 99970 |
| `clearRateLimiters` | `e2e/gdpr-fixtures.ts` | Reset the shared 60 GET/min bucket in `beforeAll` |
| Playwright `request` fixture | `@playwright/test` | Header-level probes (no browser) — same style as `security-hardening.spec.ts` |
| Playwright `page` + `page.waitForResponse` | `@playwright/test` | Widget/XHR assertions |

### 3.4 Discriminator Markers (use these, not status codes alone)

| Response kind | Positive markers | Must **not** contain |
|---------------|------------------|----------------------|
| API JSON | `content-type: application/json; charset=utf-8`, `X-RateLimit-Limit`, `X-RateLimit-Remaining`, body parses as JSON, top-level `success`/`status` keys | `<!DOCTYPE html`, `<html`, `<?php`, `Stack trace`, `Fatal error` |
| Theme 404 (HTML) | `content-type: text/html; charset=UTF-8`, `h1.h4.sr-only` = `404` (`BLOG.notFoundHeading`), `The page you are looking for was not found.`, `Back to Home` link (`BLOG.backHomeButton`) | `X-RateLimit-*` headers, JSON `success` envelope |
| HTML search page | `content-type: text/html; charset=UTF-8`, `#search-results-page` (`BLOG.searchResultsPage`), `#search-heading` (`BLOG.searchHeading`) | `X-RateLimit-*` headers |

`X-RateLimit-*` presence is the cheapest proof that `api/index.php` ran (it is emitted by the API
middleware only) — use it as a secondary assertion, never as the only one.

---

## 4. The Transport Contract (Verified Baseline)

Captured against `http://127.0.0.1:8099` on 2026-11-07 with the fix in place. Re-verify with `curl -i`
if a scenario fails unexpectedly, and treat the table as the expected contract.

| # | Request | Status | `content-type` | Body shape | `Location` |
|---|---------|--------|----------------|------------|------------|
| R1 | `GET /api/v1` | `200` | `application/json; charset=utf-8` | `{"success":true,"status":200,"message":"Welcome to Blogware RESTful API","data":{…},"_links":{…}}` | none |
| R2 | `GET /api/v1/` | `200` | `application/json; charset=utf-8` | same as R1 | none |
| R3 | `GET /api/v1/search?q=handoff` | `200` | `application/json; charset=utf-8` | `{"success":true,"status":200,"message":null,"data":{"keyword":"handoff","type":"all","total":N,"results":[…],"pagination":{…}}}` | none |
| R4 | `GET /api` | `404` | `application/json; charset=utf-8` | `{"success":false,"status":404,"error":{"code":"NOT_FOUND","message":"API endpoint not found: /api"}}` | none |
| R5 | `GET /api/` | `404` | `application/json; charset=utf-8` | same as R4 (message normalises to `/api`) | none |
| R6 | `GET /api/?q=handoff` | `404` | `application/json; charset=utf-8` | same as R4 (query string ignored — **not** the HTML search page) | none |
| R7 | `GET /apiary` | `404` | `text/html; charset=UTF-8` | theme 404 template | none |
| R8 | `GET /?p=11` | `200` | `text/html; charset=UTF-8` | blog single + sidebar widget | none |
| R9 | `GET /search?q=handoff` | `200` | `text/html; charset=UTF-8` | HTML search results page | none |

Contract summary: **anything matching `^/api(?:/|$)` is answered by the API and never by the theme;
anything else keeps the theme.** `/api` and `/api/` are *consistent with* `/api/v1` in transport
(JSON, no redirect, API middleware) but carry `404`, because the API exposes no route at its bare root.
Do not assert `200` on `/api` — see §17.

---

## 5. Scenario 1 — API Info Root `/api/v1`

### Purpose

Prove `/api/v1` is answered by `api/index.php` through the front-controller hand-off — i.e. it is
**not** the theme 404 HTML that broke the widget.

### Preconditions

* `clearRateLimiters()` executed in `beforeAll`.
* No authentication required (the info endpoint is public).

### Steps

| # | Step | Detail |
|---|------|--------|
| 1.1 | `GET {BLOG_BASE_URL}/api/v1` using the Playwright `request` fixture | Follow no redirects: pass `maxRedirects: 0` so a `301` fails loudly instead of being swallowed |
| 1.2 | Record status, `headers()`, and `text()` | Keep the raw body for marker assertions |
| 1.3 | Parse the body with `JSON.parse` (or `response.json()`) | Must not throw |
| 1.4 | Inspect the `data` object | Version/name/base URL/endpoints |
| 1.5 | Inspect `_links` | HATEOAS root present |
| 1.6 | Repeat 1.1–1.3 for `/api/v1/` (trailing slash) | Variant R2 |

### Expected Outcomes

| # | Assertion | Expected |
|---|-----------|----------|
| 1.1 | HTTP status | `200` |
| 1.2 | `content-type` | matches `/^application\/json/` (exact: `application/json; charset=utf-8`) |
| 1.3 | `location` header | `undefined` |
| 1.4 | Body parses as JSON | `success === true`, `status === 200` |
| 1.5 | `message` | `'Welcome to Blogware RESTful API'` |
| 1.6 | `data.name` | `'Blogware RESTful API'` |
| 1.7 | `data.version` | string matching `/^\d+\.\d+\.\d+$/` (do **not** pin `1.1.1`; a release bump must not fail this test) |
| 1.8 | `data.base_url` | `'/api/v1'` |
| 1.9 | `data.endpoints` | object containing keys `posts`, `categories`, `comments`, `archives` |
| 1.10 | `data.authentication` | object with `type`, `header`, `required === false` |
| 1.11 | `_links` | object, non-empty |
| 1.12 | `cache-control` | contains `max-age` (API cache header present) |
| 1.13 | Rate-limit headers | `x-ratelimit-limit` = `'60'`, `x-ratelimit-remaining` present |
| 1.14 | HTML leak guard | body does **not** contain `<!DOCTYPE html`, `<html`, `<?php`, `The page you are looking for was not found.`, `id="search-results-page"` |
| 1.15 | Trailing-slash variant `/api/v1/` | identical contract to 1.1–1.14 |

### Selectors & URLs

* `/api/v1`, `/api/v1/`
* No DOM selectors (transport-only). Body markers per §3.4.

### Helpers Reused

`BLOG_BASE_URL`, Playwright `request` fixture, `clearRateLimiters()`.

---

## 6. Scenario 2 — API Search `/api/v1/search`

### Purpose

Prove the exact endpoint the sidebar widget calls returns **200 + parseable JSON with a results
payload** — not the HTML search page (which is what the bug produced whenever `?q=` was present).

### Steps

| # | Step | Detail |
|---|------|--------|
| 2.1 | Ensure fixture post `99970` exists (`runSql`, §3.1) | Re-run after any suite that might have changed it |
| 2.2 | `GET /api/v1/search?q=handoff&type=all` | Same parameters the widget sends |
| 2.3 | Record status/headers/body | |
| 2.4 | Parse the body as JSON | |
| 2.5 | Assert the payload shape | keyword/type/total/results/pagination |
| 2.6 | Assert result-item shape on `data.results[0]` | |
| 2.7 | Variant: `GET /api/v1/search?q=handoff+beacon` (URL-encoded space) | Encoding boundary |
| 2.8 | Variant: `GET /api/v1/search?q=ab` (exactly 2 characters) | Minimum-length boundary accepted |
| 2.9 | Variant: `GET /api/v1/search?q=a` (1 character) | Rejected as JSON, not HTML |
| 2.10 | Variant: `GET /api/v1/search` (no `q`) | Rejected as JSON |

### Expected Outcomes

| # | Assertion | Expected |
|---|-----------|----------|
| 2.1 | Status | `200` |
| 2.2 | `content-type` | matches `/^application\/json/` |
| 2.3 | `location` header | `undefined` |
| 2.4 | `success` / `status` | `true` / `200` |
| 2.5 | `data.keyword` | `'handoff'` |
| 2.6 | `data.type` | `'all'` |
| 2.7 | `data.total` | `>= 1` (exactly the fixture count) |
| 2.8 | `data.results` | `Array.isArray === true`, length `>= 1`, length `<= 10` |
| 2.9 | `data.results[0]` | has `id` (number), `title` (`'API Handoff Beacon'`), `slug` (`'api-handoff-beacon'`), `type` (`'blog'`), `date` (string), `url` (absolute, contains `p=99970`), `excerpt` (string) |
| 2.10 | `data.pagination` | `current_page === 1`, `per_page === 10`, numeric `total`/`total_pages` |
| 2.11 | HTML-page leak guard | body does **not** contain `id="search-results-page"`, `id="search-heading"`, `<!DOCTYPE html` |
| 2.12 | Space-separated keyword | `200` JSON; `data.keyword === 'handoff beacon'`; `data.total >= 1` (AND semantics, fixture matches both words) |
| 2.13 | 2-character keyword `ab` | `200` JSON (`data.total` may be `0`; `data.results` is an empty array) — the gate is *at least* 2 |
| 2.14 | 1-character keyword `a` | `400` JSON, `error.code === 'BAD_REQUEST'`, `error.message` contains `at least 2 characters` |
| 2.15 | Missing `q` | `400` JSON, `error.code === 'BAD_REQUEST'`, `error.message` contains `Search keyword is required` |

### Selectors & URLs

* `/api/v1/search?q=handoff&type=all`
* `/api/v1/search?q=handoff+beacon`
* `/api/v1/search?q=ab`
* `/api/v1/search?q=a`
* `/api/v1/search`

### Helpers Reused

`runSql` (fixture), `BLOG_BASE_URL`, `request` fixture, `clearRateLimiters()`.

### Notes

* `SearchApiController` uses `mb_strlen($keyword, 'UTF-8') < 2` → 400. The widget's `minLength: 2`
  mirrors it; keep both in sync.
* Keyword echo is sanitised but **not** lower-cased in the payload (`data.keyword` mirrors the query).
  Assert the value you sent.
* Do **not** re-test `q=%27` (SQLi → 400) — owned by `e2e/security-hardening.spec.ts`.

---

## 7. Scenario 3 — Bare `/api` and `/api/`

### Purpose

Prove the helper's alternation `^/api(?:/|$)` covers the **bare root and the trailing slash** as well
as deep paths, and that both are still answered by the API rather than the theme.

### Steps

| # | Step | Detail |
|---|------|--------|
| 3.1 | `GET /api` (`maxRedirects: 0`) | |
| 3.2 | `GET /api/` | |
| 3.3 | `GET /api/?q=handoff` | Query string must not resurrect the HTML search page |
| 3.4 | `GET /api?q=handoff` | Same, without the slash |
| 3.5 | For each: parse the body as JSON | |

### Expected Outcomes

| # | Assertion | Expected |
|---|-----------|----------|
| 3.1 | Status (all four probes) | `404` |
| 3.2 | `content-type` (all four) | matches `/^application\/json/` |
| 3.3 | `location` header (all four) | `undefined` |
| 3.4 | Body parses as JSON | `success === false`, `status === 404` |
| 3.5 | `error.code` | `'NOT_FOUND'` |
| 3.6 | `error.message` | contains `API endpoint not found` |
| 3.7 | Rate-limit headers | `x-ratelimit-limit` present → API middleware ran |
| 3.8 | HTML leak guard | body does **not** contain `<!DOCTYPE html`, `id="search-results-page"`, `The page you are looking for was not found.` |
| 3.9 | Redirect guard | no `301`/`302`; body never contains `api/index.php` as a `Location` target |

### Interpretation (must be documented in the test title/comment)

"Consistent with `/api/v1`" here means **routed to the API and speaking the JSON envelope**, not
"same status code". The API has no route at its bare root, so `404` + JSON is the correct, expected
outcome. The regression signature is `404` + **`text/html`** (theme 404) or `200` + HTML search page.

### Selectors & URLs

* `/api`, `/api/`, `/api/?q=handoff`, `/api?q=handoff`

### Helpers Reused

`BLOG_BASE_URL`, `request` fixture.

---

## 8. Scenario 4 — Look-Alike Prefix `/apiary`

### Purpose

Regression guard for the prefix match. `preg_match('#^/api(?:/|$)#')` must **not** match `/apiary`;
the path has to keep flowing through the normal dispatcher and render the theme 404. This is the
scenario that catches a "fix" that degrades to `str_starts_with($path, '/api')`.

### Steps

| # | Step | Detail |
|---|------|--------|
| 4.1 | `GET /apiary` | |
| 4.2 | Assert status and `content-type` | |
| 4.3 | Assert the theme 404 markers in the HTML | |
| 4.4 | Assert JSON parse fails (body is HTML) | Use a try/catch, assert the throw |
| 4.5 | Assert no `X-RateLimit-*` headers | Proves the API middleware did not run |
| 4.6 | Browser variant: `page.goto('/apiary')` and assert the rendered 404 | `BLOG.notFoundHeading`, `BLOG.backHomeButton` |
| 4.7 | Variant: `GET /apis` and `GET /api-x` (optional, LOW) | Same theme-404 expectation |

### Expected Outcomes

| # | Assertion | Expected |
|---|-----------|----------|
| 4.1 | Status | `404` |
| 4.2 | `content-type` | matches `/^text\/html/` |
| 4.3 | `BLOG.notFoundHeading` (`h1.sr-only`) | visible, text `404` |
| 4.4 | `Back to Home` link (`BLOG.backHomeButton`) | visible, `href` = site root |
| 4.5 | Body markers | contains `The page you are looking for was not found.` |
| 4.6 | JSON envelope | body does **not** contain `"success":false`; `JSON.parse` throws |
| 4.7 | Rate-limit headers | absent (`x-ratelimit-limit` is `undefined`) |
| 4.8 | `location` header | `undefined` (no redirect to `/api/index.php`) |
| 4.9 | Document integrity (browser variant) | exactly one `BLOG.html`, one `BLOG.banner`, one `BLOG.main`, one `BLOG.footer` |

### Selectors & URLs

* `/apiary` (primary), `/apis`, `/api-x` (optional)
* `BLOG.notFoundHeading`, `BLOG.backHomeButton`, `BLOG.html`, `BLOG.banner`, `BLOG.main`, `BLOG.footer`

### Helpers Reused

`BLOG_BASE_URL`, `BLOG`, `request` + `page` fixtures.

---

## 9. Scenario 5 — Frontend Routes Unaffected

### Purpose

Prove the new early `require`/`exit` branch does **not** swallow normal traffic. If
`scriptlog_is_api_request()` over-matched (e.g. returned `true` for `/` or for every request), the
blog would start serving JSON or the API 404 — this is the counterweight to Scenarios 1–3.

### Steps

| # | Step | Detail |
|---|------|--------|
| 5.1 | `GET /?p=11` | |
| 5.2 | Assert 200 + HTML content type | |
| 5.3 | Assert `BLOG.postSingle` is visible and the sidebar widget is present | |
| 5.4 | Assert the theme shell was rendered (`BLOG.banner`, `BLOG.footer` count 1) | |
| 5.5 | `GET /search?q=handoff` | |
| 5.6 | Assert 200 + HTML + `BLOG.searchResultsPage` | |
| 5.7 | Assert `#search-heading` (`BLOG.searchHeading`) is visible | |
| 5.8 | Assert neither response contains a JSON `success` envelope | |

### Expected Outcomes

| # | Assertion | Expected |
|---|-----------|----------|
| 5.1 | `/?p=11` status | `200` |
| 5.2 | `/?p=11` `content-type` | matches `/^text\/html/` |
| 5.3 | `BLOG.postSingle` / `BLOG.searchInput` | both visible |
| 5.4 | `BLOG.banner`, `BLOG.main`, `BLOG.footer` | one each |
| 5.5 | `scriptlog_vars.api_url` (optional string check on the HTML) | `'{base}/api/v1'` — confirms the widget still targets the API, not a workaround path |
| 5.6 | `/search?q=handoff` status | `200` |
| 5.7 | `/search?q=handoff` `content-type` | matches `/^text\/html/` |
| 5.8 | `BLOG.searchResultsPage` / `BLOG.searchHeading` | visible |
| 5.9 | `location` headers | `undefined` on both |
| 5.10 | JSON guard | bodies do not contain `"success":true` as a top-level envelope; `JSON.parse` of the full body throws |

### Selectors & URLs

* `/?p=11` → `BLOG.postSingle`, `BLOG.searchInput`, `BLOG.banner`, `BLOG.main`, `BLOG.footer`
* `/search?q=handoff` → `BLOG.searchResultsPage`, `BLOG.searchHeading`

### Helpers Reused

`BLOG_BASE_URL`, `BLOG`, `page` fixture. (Note: `/search` 200 is also covered by
`theme-baseline.spec.ts`; here it is a **control** for the hand-off, asserted together with the
content-type — not a duplicate of the baseline smoke.)

---

## 10. Scenario 6 — End-to-End Widget Proof

### Purpose

The user-facing acceptance test: on a real blog post page, typing into the sidebar search box must
produce a real `GET /api/v1/search`, receive `200` JSON, render suggestions, keep the error element
empty, and raise no uncaught page/console errors. This is the exact journey that used to fail with
*"An error occurred during search. Please try again later."*

### Preconditions

* Fixture post `99970` seeded (§3.1); `clearRateLimiters()` called in `beforeAll`.
* `/?p=11` reachable (200) — asserted in step 6.1, not assumed.

### Steps

| # | Step | Detail |
|---|------|--------|
| 6.1 | Attach `page.on('pageerror')` and `page.on('console')` collectors **before** navigation | Filter CSP `Report-Only` noise (§15.1) |
| 6.2 | `page.goto('/?p=11')` | Assert `200` |
| 6.3 | Assert `BLOG.searchInput` visible and `aria-expanded="false"` | Pre-typing baseline |
| 6.4 | Register `page.waitForResponse(r => r.url().includes('/api/v1/search') && r.request().method() === 'GET' && new URL(r.url()).searchParams.get('q') === KEYWORD)` | Predicate must match the **final** keyword — intermediate 1–6 character requests return the empty state (§10.1) |
| 6.5 | `BLOG.searchInput.fill(KEYWORD)` (`handoff`) | Fires the widget's `input` handler; 300 ms debounce |
| 6.6 | Await the captured response | |
| 6.7 | Assert transport: status, URL, content-type, no `Location`, `redirectedFrom()` is `null` | |
| 6.8 | Assert the rendered result: `BLOG.searchResults` has class `show`; `BLOG.searchResultItem` visible; `BLOG.searchResultTitle` text is `API Handoff Beacon` | |
| 6.9 | Assert `BLOG.searchError` is empty (`toHaveText('')`) and `aria-expanded="true"` | |
| 6.10 | Assert `page.url()` is unchanged (still `/?p=11`) | The widget must not navigate |
| 6.11 | Assert `pageErrors` and `consoleErrors` arrays are empty | |

### Expected Outcomes

| # | Assertion | Expected |
|---|-----------|----------|
| 6.1 | Page status | `200` |
| 6.2 | XHR method / URL | `GET`, pathname exactly `/api/v1/search`, query `q=handoff&type=all` |
| 6.3 | XHR status | `200` |
| 6.4 | XHR `content-type` | matches `/^application\/json/` |
| 6.5 | `response.headers()['location']` | `undefined` |
| 6.6 | `response.request().redirectedFrom()` | `null` |
| 6.7 | `response.url()` | still ends with `/api/v1/search?…`; never `/api/index.php` |
| 6.8 | `BLOG.searchResults` | has class `show` |
| 6.9 | `BLOG.searchResultItem` | count `>= 1`, first visible |
| 6.10 | `BLOG.searchResultTitle` (first) | `API Handoff Beacon` |
| 6.11 | `BLOG.searchResultCount` (optional) | `Found 1 result(s)` |
| 6.12 | `BLOG.searchError` | empty string — **the regression's exact failure point** |
| 6.13 | `BLOG.searchInput` | `aria-expanded="true"` |
| 6.14 | `page.url()` | unchanged, `?p=11` |
| 6.15 | `pageerror` / console-error arrays | `[]` / `[]` |

### Selectors

`BLOG.searchForm`, `BLOG.searchInput`, `BLOG.searchResults`, `BLOG.searchError`,
`BLOG.searchResultItem`, `BLOG.searchResultTitle`, `BLOG.searchResultCount`.

### Helpers Reused

`BLOG`, `BLOG_BASE_URL`, `runSql`, `clearRateLimiters`.

### 10.1 Typing Strategy (why the full keyword)

`SearchFinder` uses `AGAINST ('+handoff' IN BOOLEAN MODE)` with **no trailing wildcard**, so partial
words do not match. Filling the complete keyword keeps the assertion deterministic while still
satisfying "at least 2 characters". If the plan owner prefers a character-by-character `pressSequentially`
run, keep the response predicate filtered on the final `q` value — otherwise the first captured
response is the 0-result intermediate one and the suggestion assertions flake.

DOM coverage here is deliberately minimal (suggestions + empty error). Keyboard navigation, empty
state, submit, and clear belong to `e2e/blog-search-autocomplete.spec.ts`.

---

## 11. Scenario 7 — Failure-Mode / No-Redirect Assertions

### Purpose

Pin the two failure modes that produced the bug and its predecessor, independent of any single happy
path:

1. **HTML where JSON is expected** — the nginx/theme-404 regression (issue 55).
2. **Redirect away from the API URL** — the issue 51 regression (`301 → /api/index.php`).

Plus a "test the test" control proving the discriminators actually discriminate.

### Steps

| # | Step | Detail |
|---|------|--------|
| 7.1 | For every API probe from Scenarios 1–3, assert `headers()['location'] === undefined` | No redirect |
| 7.2 | For every API probe, assert `status` is **not** in `300..399` | `maxRedirects: 0` makes this explicit |
| 7.3 | For every API probe, assert `content-type` does **not** start with `text/html` | |
| 7.4 | For every API probe, assert the body does not start with `<!DOCTYPE html` (trimmed) | |
| 7.5 | Widget-level: collect every response whose URL includes `/api/v1` during Scenario 6 and assert all statuses are `200` and none is `3xx` | |
| 7.6 | Widget-level: assert no request URL contains `/api/index.php` | |
| 7.7 | Negative control: assert the discriminators flag the theme 404 — i.e. `GET /apiary` fails the "content-type is JSON" and "no `X-RateLimit-*`" checks | Proves 7.1–7.4 are meaningful |
| 7.8 | Negative control: assert `JSON.parse` of the `/apiary` body throws, while `JSON.parse` of the `/api/v1` body succeeds | |
| 7.9 | Assert the widget's error text on a *simulated* failure is not silently swallowed | Optional: `page.route('**/api/v1/search*', route => route.fulfill({status: 500, contentType: 'text/html', body: '<html>boom</html>'}))`, type the keyword, and assert `BLOG.searchError` becomes non-empty. Confirms `#search-error` is a live assertion target, not permanently empty by accident |

### Expected Outcomes

| # | Assertion | Expected |
|---|-----------|----------|
| 7.1 | `Location` on all `/api*` probes | absent |
| 7.2 | Statuses on `/api*` probes | `200` or `404` only (never `3xx`) |
| 7.3 | `content-type` on `/api*` probes | `application/json…` |
| 7.4 | Bodies on `/api*` probes | never HTML documents |
| 7.5 | Widget API responses | all `200`, none redirected |
| 7.6 | Requests to `/api/index.php` | zero |
| 7.7 | `/apiary` control | `text/html` **and** no rate-limit headers → discriminator works |
| 7.8 | JSON parse control | `/apiary` throws; `/api/v1` succeeds |
| 7.9 | Simulated 500 → `BLOG.searchError` | non-empty (message text contains `error occurred during search` or the widget's `i18n.error`) |

### Selectors & URLs

All `/api*` URLs from §4, plus `BLOG.searchError`, `BLOG.searchInput`.

### Helpers Reused

`request` + `page` fixtures, `BLOG`, `BLOG_BASE_URL`, `clearRateLimiters`.

---

## 12. Security Expectations

| # | Check | Expected |
|---|-------|----------|
| 12.1 | No HTML error page for any `/api*` path | every probe returns `content-type: application/json…`; body never contains `<!DOCTYPE html` or `<html` |
| 12.2 | No stack traces / DB errors | API bodies contain none of: `Stack trace`, `Fatal error`, `PDOException`, `SQL syntax`, `You have an error in your SQL`, `SELECT * FROM`, `tbl_posts`, `mysqli_` (same pattern list as `e2e/security-hardening.spec.ts`) |
| 12.3 | No source disclosure | bodies never contain `<?php`, `__DIR__`, `require_once`, or the filesystem path `/var/www/` |
| 12.4 | No internal path disclosure via redirect | no `Location` header at all on `/api*`; specifically never `Location: /api/index.php` (issue 51 signature) |
| 12.5 | No config leakage | bodies never contain the fallback table prefix `tpglkl` or DB credentials |
| 12.6 | API middleware actually ran | `X-RateLimit-Limit` / `X-RateLimit-Remaining` present on API responses, absent on theme responses |
| 12.7 | Errors keep the structured envelope | `{"success":false,"status":<code>,"error":{"code":…,"message":…}}` — no HTML, no bare strings |
| 12.8 | Honeypot/parameter handling unchanged | the widget sends only `q` and `type` (already asserted in the autocomplete spec); this plan adds no new parameters |
| 12.9 | Rate limiting still enforced on the hand-off path | a 429 (if reached) must still be JSON; **do not** deliberately exhaust the bucket in this suite (see §15.3) |
| 12.10 | No auth bypass introduced | `/api/v1` and `/api/v1/search` are public by design (`data.authentication.required === false`); no admin/session-only endpoint is exercised here |

> Not duplicated here: `q=%27` → 400 `BAD_REQUEST`, SQLi probes, traversal probes, header/cookie flags
> — all owned by `e2e/security-hardening.spec.ts`.

---

## 13. Accessibility

**Boundary note**: the combobox ARIA contract is already covered by `e2e/blog-search-autocomplete.spec.ts`
(`role="combobox"`, `aria-autocomplete="list"`, `aria-controls="search-results"`, `aria-expanded`,
`aria-activedescendant`, `role="listbox"` / `role="option"`, focus retention, Escape). **Do not
duplicate it here.**

This plan asserts only what the routing fix can affect:

| # | Check | Expected |
|---|-------|----------|
| 13.1 | `BLOG.searchError` has `aria-live="assertive"` | attribute present (it is the element the bug populated) |
| 13.2 | Happy path leaves `#search-error` empty | a false assertive announcement is the accessibility symptom of the routing regression |
| 13.3 | `BLOG.searchInput` `aria-expanded` | `"false"` before typing, `"true"` after suggestions render |
| 13.4 | Accessible name unchanged | `label[for="search-keyword"]` still present (sr-only); the fix must not remove or rename it |
| 13.5 | No duplicated live region | `#search-results` remains `aria-live="polite"` + `aria-atomic="true"`, exactly one instance |

---

## 14. Responsive Behaviour

**Not required.** The contract under test is transport-level and viewport-independent. The API response
is byte-identical at every breakpoint and the widget's markup is unchanged by this fix. Responsive
layout of the sidebar widget is owned by the theme UX/responsive suites.

> Do not add viewport matrices to this spec; they only multiply runtime against the shared 60 GET/min
> rate limit.

---

## 15. Console & Network Expectations

### 15.1 Console

| # | Check | Expected |
|---|-------|----------|
| 15.1.1 | `pageerror` during Scenario 6 | `[]` |
| 15.1.2 | `console` messages of type `error` during Scenario 6 | `[]` after filtering CSP noise |
| 15.1.3 | CSP noise filter (copy the regex from `blog-search-autocomplete.spec.ts`) | ignore messages matching `/content.security.policy|\[report only\]/i` — these come from the theme shell on every blog page, not from the widget |
| 15.1.4 | No jQuery `parsererror` | the classic signature of the bug (`dataType: 'json'` receiving HTML); assert no console message contains `parsererror` |
| 15.1.5 | No mixed-content warnings | API URL is same-origin `http://127.0.0.1:8099/api/v1` |

### 15.2 Network (Scenario 6, widget)

| # | Request | Expected |
|---|---------|----------|
| 15.2.1 | `GET /?p=11` (document) | `200`, `text/html` |
| 15.2.2 | `GET /api/v1/search?q=handoff&type=all` (XHR) | `200`, `application/json`, no `Location` |
| 15.2.3 | Any `3xx` on `/api/v1/*` | none |
| 15.2.4 | Request to `/api/index.php` | none |
| 15.2.5 | Request to a theme 404 document while typing | none |
| 15.2.6 | Requests below the 2-character minimum | none (debounce + `minLength`; already asserted in the autocomplete spec) |

### 15.3 Rate-Limit Hygiene

Every API probe consumes the shared `127.0.0.1` GET bucket (60/min). The full matrix in this plan is
~15 GETs, so one `clearRateLimiters()` in `beforeAll` is sufficient. If a scenario ever sees `429`:

* treat it as **test infrastructure failure**, not an application bug;
* call `clearRateLimiters()`, re-run;
* never "fix" it by asserting `429` acceptance.

---

## 16. Failure Signatures & Recovery

Use these to triage a red run — each maps to a distinct root cause.

| # | Symptom | Likely root cause | Failing scenarios |
|---|---------|-------------------|-------------------|
| 16.1 | `/api/v1` → `404` + `text/html` + `h1.sr-only` `404` | Hand-off branch removed/reordered after `lib/main.php`; helper file not required | 1, 2, 3, 6, 7 |
| 16.2 | `/api/v1/search?q=…` → `200` + `text/html` + `#search-results-page` | Request reached `HandleRequest::deliverQuerySearch()` instead of the API | 2, 6, 7 |
| 16.3 | `/api/v1` → `301`/`302` + `Location: /api/index.php` | Issue 51 regression (session/error-handler promotion) | 1, 3, 7 |
| 16.4 | `#search-error` shows `An error occurred during search. Please try again later.` | JSON parse failure or non-2xx on the XHR — the canonical bug signature | 6 |
| 16.5 | `/apiary` returns JSON / has `X-RateLimit-*` | Prefix match degraded to a plain `startsWith('/api')` | 4, 7.7 |
| 16.6 | `/` or `/search` returns JSON | Helper over-matches (missing `(?:/|$)` or wrong install-base strip) | 5 |
| 16.7 | `/api` returns `200` | API gained a root route — update §4/§17 and the Scenario 3 status assertion; **do not** treat as a regression by itself | 3 |
| 16.8 | `data.results` empty for `handoff` | Fixture missing / FULLTEXT index not updated / keyword changed | 2, 6 |
| 16.9 | Intermittent suggestion failures | Response predicate captured an intermediate 0-result response (§10.1) | 6 |

**Recovery**: this plan is read-only. No cleanup is required beyond the disposable fixture row. Do not
delete `public/cache/rate_limit/` wholesale (its `.htaccess` is tracked) — `clearRateLimiters()` only
removes counter files.

---

## 17. Documented Boundaries (Observed, Not Bugs)

Verified against the running server; record them so a future reader does not "fix" them:

| # | Behaviour | Why it is correct |
|---|-----------|-------------------|
| 17.1 | `GET /api` and `GET /api/` → `404` JSON | The API has no route at its bare root. The fix guarantees *routing*, not a route. |
| 17.2 | `GET /API/v1` (uppercase) → theme `404` HTML | Matching is case-sensitive (`#^/api(?:/|$)#`), mirroring Apache's `RewriteRule ^api/(.*)$` (no `[NC]`). Assert as an observation only if a scenario needs it; never as a requirement. |
| 17.3 | `GET /api/v1/search/` (trailing slash) → `400` JSON `Search keyword is required` | Still the API; the router normalises the trailing slash before matching the action. |
| 17.4 | `GET /api//v1` → `404` JSON `API endpoint not found: /api//v1` | Double slashes are passed through; still the API, never the theme. |
| 17.5 | `GET /api/index.php` → `404` JSON `API endpoint not found: /api/index.php` | Real-file passthrough in `e2e/router.php`; bypasses the branch under test. Never cite as evidence (§2.3). |
| 17.6 | FULLTEXT search is whole-word (no wildcard) | `buildBooleanQuery()` adds `+` only. `han` → 0 hits, `handoff` → hits. |
| 17.7 | API responses set `_scriptlog` cookie | Normal session initialisation, not a redirect or a leak. |

---

## 18. Out of Scope

| Area | Reason / owner |
|------|----------------|
| Apache `.htaccess` `RewriteRule ^api/(.*)$` path | Not exercised by the PHP built-in server + `e2e/router.php`. Deployment contract is a file-content assertion in `security-hardening.spec.ts` style; routing logic is unit-tested. |
| nginx installer template (`install/include/setup.php::generate_server_config()`) | Covered by `specs/installer.md` and unit tests; no nginx in this harness. |
| Sub-directory installs (`/scriptlog/api/v1`) | `scriptlog_install_base_path()` strips the install base; the e2e server is a root install, so this cannot be exercised in the browser. Covered by `tests/unit/ApiFrontControllerHandoffTest.php`. |
| API auth (API keys, bearer tokens), CORS preflight/`OPTIONS` | Separate API security coverage. |
| HATEOAS link correctness, pagination/sorting semantics, search relevance/ranking | `docs/dev-docs/API_DOCUMENTATION.md` + API test suites. |
| `429` rate-limit flow | Deliberately not triggered (§15.3). |
| Widget UX (keyboard nav, empty state, submit, clear, caching) | `e2e/blog-search-autocomplete.spec.ts`. |
| `q=%27` → 400, SQLi/traversal probes, security headers/cookie flags | `e2e/security-hardening.spec.ts`. |
| Theme 404 template markup beyond the markers in §3.4 | `e2e/theme-404.spec.ts`. |
| Admin panel, GDPR, media, comments | Other specs. |
| Mobile viewports | §14. |

---

## 19. Screenshots

No dedicated screenshots are required (the plan is assertion-driven). Capture on failure only:

| # | View | When |
|---|------|------|
| 19.1 | `/?p=11` with the suggestion popup open | Scenario 6 failure (shows `#search-error` text if present) |
| 19.2 | Rendered `/apiary` theme 404 | Scenario 4 failure |
| 19.3 | Raw response body of the failing `/api*` probe (attach as text, not an image) | Scenarios 1–3, 7 |

---

## 20. Test Scenarios (Prioritised Summary)

### CRITICAL

| ID | Scenario | Steps (short) | Expected |
|----|----------|---------------|----------|
| C-01 | `/api/v1` is JSON, not the theme 404 | `GET /api/v1` with `maxRedirects: 0`; parse body | `200`, `application/json`, no `Location`, `success:true`, `data.base_url === '/api/v1'`, no `<!DOCTYPE html` |
| C-02 | `/api/v1/search` is JSON with results | Seed fixture; `GET /api/v1/search?q=handoff&type=all` | `200`, `data.keyword === 'handoff'`, `data.results[0].title === 'API Handoff Beacon'`, no `#search-results-page` |
| C-03 | Sidebar widget end-to-end | `/?p=11`; type `handoff`; await XHR | XHR `200` JSON on `/api/v1/search`, suggestions visible, `#search-error` empty, no page/console errors |
| C-04 | XHR is never redirected and never HTML | Assert `Location`/`redirectedFrom()`/content-type on the widget XHR + all probes | no `3xx`, no `Location`, `application/json` only |
| C-05 | `/api` and `/api/` are handled by the API | `GET /api`, `GET /api/`, `GET /api/?q=handoff` | `404` **JSON** `NOT_FOUND`, no `Location`, not the theme 404 |

### HIGH

| ID | Scenario | Steps (short) | Expected |
|----|----------|---------------|----------|
| H-01 | Look-alike prefix stays on the theme | `GET /apiary` (+ browser visit) | `404` `text/html`, theme 404 markers, no `X-RateLimit-*`, JSON parse throws |
| H-02 | Frontend routes unaffected — single post | `GET /?p=11` | `200` `text/html`, `BLOG.postSingle` + `BLOG.searchInput` visible, one header/main/footer |
| H-03 | Frontend routes unaffected — search page | `GET /search?q=handoff` | `200` `text/html`, `BLOG.searchResultsPage` + `BLOG.searchHeading` |
| H-04 | Trailing slash on the API version root | `GET /api/v1/` | identical to C-01 |
| H-05 | API errors stay JSON | `GET /api/v1/search?q=a`, `GET /api/v1/search` | `400` JSON `BAD_REQUEST` with the expected message, no HTML |
| H-06 | Discriminators actually discriminate | `/apiary` must fail the JSON/rate-limit checks | negative control passes (§11.7–11.8) |

### MEDIUM

| ID | Scenario | Steps (short) | Expected |
|----|----------|---------------|----------|
| M-01 | 2-character minimum accepted at the API | `GET /api/v1/search?q=ab` | `200` JSON, `data.results` is an array (possibly empty) |
| M-02 | Space-separated keyword | `GET /api/v1/search?q=handoff+beacon` | `200` JSON, `data.keyword === 'handoff beacon'` |
| M-03 | Query string on the bare root | `GET /api/?q=handoff` and `/api?q=handoff` | `404` JSON — **not** the HTML search page |
| M-04 | No `parsererror` in the console | Scenario 6 console capture | no message contains `parsererror` |
| M-05 | Widget does not navigate while typing | `page.url()` after typing | still `?p=11` |
| M-06 | `#search-error` is a live target | `page.route` → fulfil `500 text/html`; type keyword | `BLOG.searchError` becomes non-empty |
| M-07 | API payload metadata | `GET /api/v1` | `data.endpoints` has `posts`/`categories`/`comments`/`archives`; `data.version` matches `/^\d+\.\d+\.\d+$/` |

### LOW / EDGE

| ID | Scenario | Steps (short) | Expected |
|----|----------|---------------|----------|
| L-01 | Double slash | `GET /api//v1` | `404` JSON (`API endpoint not found`) |
| L-02 | Uppercase prefix (documented boundary) | `GET /API/v1` | theme `404` HTML — matches Apache; assert as observation, not requirement (§17.2) |
| L-03 | Sibling prefixes | `GET /apis`, `GET /api-x` | theme `404` HTML, no rate-limit headers |
| L-04 | Direct API file hit is not evidence | `GET /api/index.php` | `404` JSON `API endpoint not found: /api/index.php` — documents §2.3 |
| L-05 | Fixture idempotency | Run the suite twice | `runSql` upsert keeps `data.total` stable |

### Cross-Cutting (all scenarios)

* Collect `pageerror` / console errors / non-2xx responses per scenario.
* Capture a screenshot + raw body on failure.
* Assert `content-type` **and** body markers, never the status code alone.
* Keep the API probe count small (shared 60 GET/min bucket).
* Every scenario must be independently runnable, in any order, and must seed its own fixture.

---

## 21. Requirement Traceability

| Requirement | Scenarios |
|-------------|-----------|
| 1. `/api/v1` → 200 JSON, no `Location`, not the theme 404 | C-01, H-04, M-07, L-01 |
| 2. `/api/v1/search?q=…` → 200 JSON results, parseable, not the HTML search page | C-02, H-05, M-01, M-02 |
| 3. `/api/` and `/api` consistent with `/api/v1` | C-05, M-03, L-01 |
| 4. `/apiary` keeps the theme 404 (prefix guard) | H-01, L-03 |
| 5. Frontend routes unaffected (`/?p=11`, `/search?q=…`) | H-02, H-03 |
| 6. Widget end-to-end: ≥2 chars → `GET /api/v1/search` → 200 JSON → suggestions → empty error → no console/page errors | C-03, M-04, M-05 |
| 7. No redirect away from the API URL, no `text/html` | C-04, H-06, M-06 |
