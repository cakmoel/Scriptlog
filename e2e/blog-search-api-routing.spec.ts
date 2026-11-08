// blog-search-api-routing.spec.ts
// Plan: specs/blog-search-api-routing.md
//
// Locks the routing / transport contract between the public front controller
// (index.php) and the REST API (api/index.php) as seen from a browser on a
// deployment where the web server sends every unknown path to index.php
// (e2e/router.php mirrors nginx's catch-all `try_files ... /index.php`).
//
// Regression focus (docs/dev-docs/TROUBLESHOOTING.md issue 55): /api/* used to
// fall through to the blog theme, so GET /api/v1 rendered the theme 404 HTML and
// GET /api/v1/search?q=... rendered the full HTML search page. jQuery's
// dataType:'json' parse then failed and the sidebar widget always showed
// "An error occurred during search. Please try again later.".
//
// Every transport assertion uses a /api/v1... URL (or the bare /api roots) and
// never /api/index.php, which e2e/router.php serves as a real file and which
// therefore bypasses the branch under test (plan §2.3).
//
// Scope split: widget keyboard/ARIA/clear-button behaviour belongs to
// e2e/blog-search-autocomplete.spec.ts; SQLi probes and header flags belong to
// e2e/security-hardening.spec.ts. Not duplicated here.
import { test, expect } from '@playwright/test';
import type {
  APIRequestContext,
  APIResponse,
  Request,
  Response,
  Route,
} from '@playwright/test';
import { BLOG, BLOG_BASE_URL } from './blog-selectors';
import { clearRateLimiters, runSql } from './gdpr-fixtures';

const BASE_URL: string = BLOG_BASE_URL;

/** Disposable fixture post owned by this spec (never 99960/99961-3 from the autocomplete spec). */
const FIXTURE_POST_ID: number = 99970;
const FIXTURE_POST_TITLE: string = 'API Handoff Beacon';
const FIXTURE_POST_SLUG: string = 'api-handoff-beacon';
/**
 * Whole-word keyword: SearchFinder::buildBooleanQuery() prepends `+` and adds no
 * trailing wildcard, so `han` matches nothing while `handoff` matches the fixture.
 */
const FIXTURE_KEYWORD: string = 'handoff';
/** Single post that renders the sidebar widget (`Test Numeric Post`, publish/public/blog). */
const WIDGET_PAGE_PATH: string = '/?p=11';

/** Marker rendered by the theme 404 template (public/themes/blog/404.php). */
const THEME_404_MARKER: string = 'The page you are looking for was not found.';

/** Substrings that must never appear in an API response body (plan §3.4, §12). */
const HTML_LEAK_MARKERS: string[] = [
  '<!DOCTYPE html',
  '<html',
  '<?php',
  THEME_404_MARKER,
  'id="search-results-page"',
];

/** Shape of the API error envelope. */
type ApiError = {
  code: string;
  message: string;
};

/** Shape of the top-level API envelope shared by every endpoint. */
type ApiEnvelope<TData> = {
  success: boolean;
  status: number;
  message?: string | null;
  data?: TData;
  error?: ApiError;
  _links?: Record<string, { href: string; rel: string; type: string }>;
};

/** Payload of `GET /api/v1`. */
type ApiInfoData = {
  name: string;
  version: string;
  base_url: string;
  endpoints: Record<string, Record<string, string>>;
  authentication: { type: string; header: string; required: boolean };
};

/** Single entry of `data.results` from `GET /api/v1/search`. */
type SearchResult = {
  id: number;
  title: string;
  slug: string;
  type: string;
  date: string;
  url: string;
  excerpt: string;
};

/** `data.pagination` block returned by the search endpoint. */
type SearchPagination = {
  current_page: number;
  per_page: number;
  total_items: number;
  total_pages: number;
};

/** `data` payload of `GET /api/v1/search`. */
type SearchData = {
  keyword: string;
  type: string;
  total: number;
  results: SearchResult[];
  pagination: SearchPagination;
};

/**
 * Seed (or refresh) the routing/search fixture post in the scratch e2e database.
 *
 * Idempotent: a re-run rewrites title/content/status/tags, so `data.total` and
 * the suggestion assertions never depend on leftover state from an earlier run.
 *
 * @returns void
 */
function seedRoutingFixture(): void {
  runSql(`
    INSERT INTO tbl_posts (
      ID, media_id, post_author, post_date, post_title, post_slug,
      post_content, post_summary, post_status, post_visibility, post_tags,
      post_headlines, post_sticky, post_type, post_locale, comment_status
    ) VALUES (
      ${FIXTURE_POST_ID}, 0, 1, NOW(),
      '${FIXTURE_POST_TITLE}', '${FIXTURE_POST_SLUG}',
      '<p>Routing handoff fixture for the front-controller API regression.</p>',
      'Routing handoff fixture summary',
      'publish', 'public', 'handoff,e2e', 0, 0, 'blog', 'en', 'closed'
    )
    ON DUPLICATE KEY UPDATE
      post_title = VALUES(post_title),
      post_slug = VALUES(post_slug),
      post_content = VALUES(post_content),
      post_summary = VALUES(post_summary),
      post_status = 'publish',
      post_visibility = 'public',
      post_tags = VALUES(post_tags),
      post_type = 'blog',
      post_locale = 'en'
  `);
}

/**
 * GET an absolute application path without following redirects.
 *
 * `maxRedirects: 0` makes the issue 51 signature (`301 -> /api/index.php`)
 * fail loudly instead of being swallowed by the HTTP client.
 *
 * @param request Playwright API request context.
 * @param path Path including any query string, e.g. `/api/v1/search?q=handoff`.
 * @returns The raw API response.
 */
async function apiGet(request: APIRequestContext, path: string): Promise<APIResponse> {
  return request.get(`${BASE_URL}${path}`, {
    maxRedirects: 0,
    failOnStatusCode: false,
  });
}

/**
 * Read the response content-type, defaulting to a marker when absent.
 *
 * @param response API response.
 * @returns Lower-cased content-type header value.
 */
function contentTypeOf(response: APIResponse): string {
  return response.headers()['content-type'] ?? '<missing content-type>';
}

/**
 * Build a human-readable probe label used as the assertion message, so a
 * failure always reports the received status and content-type.
 *
 * @param path Requested path.
 * @param response API response.
 * @returns Description such as `GET /api/v1 (status 200, content-type ...)`.
 */
function describeProbe(path: string, response: APIResponse): string {
  return `GET ${path} (status ${response.status()}, content-type ${contentTypeOf(response)})`;
}

/**
 * Parse a JSON body, failing with the received body prefix when it is HTML.
 *
 * @param body Raw response body.
 * @param description Probe description used in the failure message.
 * @returns Parsed payload.
 */
function parseJson<T>(body: string, description: string): T {
  try {
    return JSON.parse(body) as T;
  } catch {
    throw new Error(
      `${description} did not return JSON; body starts with: ${body.slice(0, 200)}`,
    );
  }
}

/**
 * Assert the shared API transport contract: JSON content-type, no redirect and
 * no HTML/source leak in the body.
 *
 * @param response API response.
 * @param body Raw response body.
 * @param description Probe description used in failure messages.
 * @returns void
 */
function expectApiTransport(
  response: APIResponse,
  body: string,
  description: string,
): void {
  expect(contentTypeOf(response), `${description} content-type`).toMatch(/^application\/json/);
  expect(response.headers()['location'], `${description} must not send a Location header`).toBeUndefined();
  const status: number = response.status();
  expect(
    status >= 300 && status < 400,
    `${description} must never answer with a 3xx redirect`,
  ).toBe(false);
  for (const marker of HTML_LEAK_MARKERS) {
    expect(body, `${description} leaked the HTML marker ${JSON.stringify(marker)}`).not.toContain(marker);
  }
}

test.describe('Blog search API routing', () => {
  test.beforeAll((): void => {
    // The 60 GET/min bucket is shared by every request from 127.0.0.1, so it is
    // shared with the other suites; start from an empty counter.
    clearRateLimiters();
    seedRoutingFixture();
  });

  test('serves the API info root at /api/v1 as JSON, not the theme 404', async ({ request }) => {
    const response = await apiGet(request, '/api/v1');
    const body: string = await response.text();
    const description: string = describeProbe('/api/v1', response);

    expect(response.status(), description).toBe(200);
    expectApiTransport(response, body, description);

    const payload = parseJson<ApiEnvelope<ApiInfoData>>(body, description);
    expect(payload.success, `${description} success flag`).toBe(true);
    expect(payload.status, `${description} status field`).toBe(200);
    expect(payload.message, `${description} message`).toBe('Welcome to Blogware RESTful API');

    const data = payload.data;
    if (data === undefined) {
      throw new Error(`${description} returned no data object; body starts with: ${body.slice(0, 200)}`);
    }
    expect(data.name, `${description} data.name`).toBe('Blogware RESTful API');
    // Never pin the exact release: a version bump must not fail this test.
    expect(data.version, `${description} data.version`).toMatch(/^\d+\.\d+\.\d+$/);
    expect(data.base_url, `${description} data.base_url`).toBe('/api/v1');
    expect(Object.keys(data.endpoints), `${description} data.endpoints keys`).toEqual(
      expect.arrayContaining(['posts', 'categories', 'comments', 'archives']),
    );
    expect(data.authentication.required, `${description} data.authentication.required`).toBe(false);
    expect(data.authentication.type.length, `${description} data.authentication.type`).toBeGreaterThan(0);
    expect(data.authentication.header.length, `${description} data.authentication.header`).toBeGreaterThan(0);

    const links = payload._links;
    if (links === undefined) {
      throw new Error(`${description} returned no _links object; body starts with: ${body.slice(0, 200)}`);
    }
    expect(Object.keys(links).length, `${description} _links must be non-empty`).toBeGreaterThan(0);
    expect(links.self?.href, `${description} _links.self.href`).toContain('/api/v1');

    // API middleware signatures: cache header + rate-limit headers.
    expect(response.headers()['cache-control'], `${description} cache-control`).toContain('max-age');
    expect(response.headers()['x-ratelimit-limit'], `${description} x-ratelimit-limit`).toBe('60');
    expect(response.headers()['x-ratelimit-remaining'], `${description} x-ratelimit-remaining`).toBeDefined();
  });

  test('serves the trailing-slash API root at /api/v1/ with the same contract', async ({ request }) => {
    const response = await apiGet(request, '/api/v1/');
    const body: string = await response.text();
    const description: string = describeProbe('/api/v1/', response);

    expect(response.status(), description).toBe(200);
    expectApiTransport(response, body, description);

    const payload = parseJson<ApiEnvelope<ApiInfoData>>(body, description);
    expect(payload.success, `${description} success flag`).toBe(true);
    expect(payload.status, `${description} status field`).toBe(200);
    expect(payload.data?.base_url, `${description} data.base_url`).toBe('/api/v1');
    expect(response.headers()['x-ratelimit-limit'], `${description} x-ratelimit-limit`).toBe('60');
  });

  test('serves /api/v1/search as JSON results, not the HTML search page', async ({ request }) => {
    const path: string = `/api/v1/search?q=${FIXTURE_KEYWORD}&type=all`;
    const response = await apiGet(request, path);
    const body: string = await response.text();
    const description: string = describeProbe(path, response);

    expect(response.status(), description).toBe(200);
    expectApiTransport(response, body, description);
    // The regression signature: the HTML search page answered this URL.
    expect(body, `${description} served the HTML search page`).not.toContain('#search-results-page');
    expect(body, `${description} served the HTML search page`).not.toContain('id="search-heading"');

    const payload = parseJson<ApiEnvelope<SearchData>>(body, description);
    expect(payload.success, `${description} success flag`).toBe(true);
    expect(payload.status, `${description} status field`).toBe(200);

    const data = payload.data;
    if (data === undefined) {
      throw new Error(`${description} returned no data object; body starts with: ${body.slice(0, 200)}`);
    }
    expect(data.keyword, `${description} data.keyword`).toBe(FIXTURE_KEYWORD);
    expect(data.type, `${description} data.type`).toBe('all');
    expect(data.total, `${description} data.total`).toBeGreaterThanOrEqual(1);
    expect(Array.isArray(data.results), `${description} data.results must be an array`).toBe(true);
    expect(data.results.length, `${description} data.results length`).toBeGreaterThanOrEqual(1);
    expect(data.results.length, `${description} data.results must respect per_page=10`).toBeLessThanOrEqual(10);

    const fixture: SearchResult | undefined = data.results.find(
      (result: SearchResult): boolean => result.id === FIXTURE_POST_ID,
    );
    if (fixture === undefined) {
      throw new Error(
        `${description} did not include fixture post ${FIXTURE_POST_ID}; returned ids: ${data.results
          .map((result: SearchResult): number => result.id)
          .join(', ')}`,
      );
    }
    expect(fixture.title, `${description} result title`).toBe(FIXTURE_POST_TITLE);
    expect(fixture.slug, `${description} result slug`).toBe(FIXTURE_POST_SLUG);
    expect(fixture.type, `${description} result type`).toBe('blog');
    expect(typeof fixture.date, `${description} result date must be a string`).toBe('string');
    expect(fixture.url, `${description} result url must be absolute`).toMatch(/^https?:\/\//);
    expect(fixture.url, `${description} result url`).toContain(`p=${FIXTURE_POST_ID}`);
    expect(typeof fixture.excerpt, `${description} result excerpt must be a string`).toBe('string');

    expect(data.pagination.current_page, `${description} pagination.current_page`).toBe(1);
    expect(data.pagination.per_page, `${description} pagination.per_page`).toBe(10);
    expect(typeof data.pagination.total_items, `${description} pagination.total_items`).toBe('number');
    expect(data.pagination.total_items, `${description} pagination.total_items`).toBeGreaterThanOrEqual(1);
    expect(typeof data.pagination.total_pages, `${description} pagination.total_pages`).toBe('number');
  });

  test('accepts a multi-word keyword and the two character minimum', async ({ request }) => {
    // Encoding boundary: the space is sent as `+` and echoed decoded.
    const multiWordPath: string = '/api/v1/search?q=handoff+beacon';
    const multiWord = await apiGet(request, multiWordPath);
    const multiWordBody: string = await multiWord.text();
    const multiWordDescription: string = describeProbe(multiWordPath, multiWord);

    expect(multiWord.status(), multiWordDescription).toBe(200);
    expectApiTransport(multiWord, multiWordBody, multiWordDescription);
    const multiWordPayload = parseJson<ApiEnvelope<SearchData>>(multiWordBody, multiWordDescription);
    expect(multiWordPayload.data?.keyword, `${multiWordDescription} data.keyword`).toBe('handoff beacon');
    // AND semantics: the fixture contains both words.
    expect(multiWordPayload.data?.total, `${multiWordDescription} data.total`).toBeGreaterThanOrEqual(1);

    // Minimum-length boundary: the gate is "at least 2", so `ab` is accepted.
    const minimumPath: string = '/api/v1/search?q=ab';
    const minimum = await apiGet(request, minimumPath);
    const minimumBody: string = await minimum.text();
    const minimumDescription: string = describeProbe(minimumPath, minimum);

    expect(minimum.status(), minimumDescription).toBe(200);
    expectApiTransport(minimum, minimumBody, minimumDescription);
    const minimumPayload = parseJson<ApiEnvelope<SearchData>>(minimumBody, minimumDescription);
    expect(minimumPayload.success, `${minimumDescription} success flag`).toBe(true);
    expect(Array.isArray(minimumPayload.data?.results), `${minimumDescription} data.results must be an array`).toBe(true);
  });

  test('rejects too-short and missing keywords as JSON 400 errors', async ({ request }) => {
    const shortPath: string = '/api/v1/search?q=a';
    const short = await apiGet(request, shortPath);
    const shortBody: string = await short.text();
    const shortDescription: string = describeProbe(shortPath, short);

    expect(short.status(), shortDescription).toBe(400);
    expectApiTransport(short, shortBody, shortDescription);
    const shortPayload = parseJson<ApiEnvelope<SearchData>>(shortBody, shortDescription);
    expect(shortPayload.success, `${shortDescription} success flag`).toBe(false);
    expect(shortPayload.status, `${shortDescription} status field`).toBe(400);
    expect(shortPayload.error?.code, `${shortDescription} error.code`).toBe('BAD_REQUEST');
    expect(shortPayload.error?.message ?? '', `${shortDescription} error.message`).toContain(
      'at least 2 characters',
    );

    const missingPath: string = '/api/v1/search';
    const missing = await apiGet(request, missingPath);
    const missingBody: string = await missing.text();
    const missingDescription: string = describeProbe(missingPath, missing);

    expect(missing.status(), missingDescription).toBe(400);
    expectApiTransport(missing, missingBody, missingDescription);
    const missingPayload = parseJson<ApiEnvelope<SearchData>>(missingBody, missingDescription);
    expect(missingPayload.success, `${missingDescription} success flag`).toBe(false);
    expect(missingPayload.error?.code, `${missingDescription} error.code`).toBe('BAD_REQUEST');
    expect(missingPayload.error?.message ?? '', `${missingDescription} error.message`).toContain(
      'Search keyword is required',
    );
  });

  test('answers the bare /api roots with the API JSON envelope, not the theme', async ({ request }) => {
    // "Consistent with /api/v1" means routed to the API and speaking the JSON
    // envelope, not "same status code": the API has no route at its bare root,
    // so 404 + JSON is the correct outcome (plan §7, §17.1).
    const paths: string[] = ['/api', '/api/', '/api/?q=handoff', '/api?q=handoff'];
    for (const path of paths) {
      const response = await apiGet(request, path);
      const body: string = await response.text();
      const description: string = describeProbe(path, response);

      expect(response.status(), `${description} must be the API's JSON 404, not the theme 404`).toBe(404);
      expectApiTransport(response, body, description);

      const payload = parseJson<ApiEnvelope<unknown>>(body, description);
      expect(payload.success, `${description} success flag`).toBe(false);
      expect(payload.status, `${description} status field`).toBe(404);
      expect(payload.error?.code, `${description} error.code`).toBe('NOT_FOUND');
      expect(payload.error?.message ?? '', `${description} error.message`).toContain('API endpoint not found');
      // X-RateLimit-* is emitted by the API middleware only - proof it ran.
      expect(response.headers()['x-ratelimit-limit'], `${description} x-ratelimit-limit`).toBe('60');
    }

    // Double slashes are passed through: still the API, never the theme.
    const doubleSlashPath: string = '/api//v1';
    const doubleSlash = await apiGet(request, doubleSlashPath);
    const doubleSlashBody: string = await doubleSlash.text();
    const doubleSlashDescription: string = describeProbe(doubleSlashPath, doubleSlash);

    expect(doubleSlash.status(), doubleSlashDescription).toBe(404);
    expectApiTransport(doubleSlash, doubleSlashBody, doubleSlashDescription);
    const doubleSlashPayload = parseJson<ApiEnvelope<unknown>>(doubleSlashBody, doubleSlashDescription);
    expect(doubleSlashPayload.error?.message ?? '', `${doubleSlashDescription} error.message`).toContain(
      'API endpoint not found: /api//v1',
    );
  });

  test('keeps look-alike prefixes on the theme 404 branch', async ({ request }) => {
    // Regression guard for a "fix" that degrades to str_starts_with($path, '/api').
    const paths: string[] = ['/apiary', '/apis', '/api-x'];
    for (const path of paths) {
      const response = await apiGet(request, path);
      const body: string = await response.text();
      const description: string = describeProbe(path, response);

      expect(response.status(), description).toBe(404);
      expect(contentTypeOf(response), `${description} must be the theme HTML page`).toMatch(/^text\/html/);
      expect(response.headers()['location'], `${description} must not send a Location header`).toBeUndefined();
      expect(body, `${description} theme 404 marker`).toContain(THEME_404_MARKER);
      expect(body, `${description} must not be a JSON error envelope`).not.toContain('"success":false');
      // Negative control: the JSON discriminator actually discriminates.
      expect(() => JSON.parse(body), `${description} must not parse as JSON`).toThrow();
      // Proves api/index.php (the rate-limit middleware) never ran.
      expect(response.headers()['x-ratelimit-limit'], `${description} must not hit the API middleware`).toBeUndefined();
    }
  });

  test('renders the theme 404 page in the browser for /apiary', async ({ page }) => {
    const response = await page.goto(`${BASE_URL}/apiary`);
    expect(response?.status(), '/apiary document status').toBe(404);
    expect(response?.headers()['content-type'] ?? '', '/apiary document content-type').toMatch(/^text\/html/);

    await expect(page.locator(BLOG.notFoundHeading)).toBeVisible();
    await expect(page.locator(BLOG.notFoundHeading)).toHaveText('404');

    const backHome = page.locator(BLOG.backHomeButton);
    await expect(backHome).toBeVisible();
    await expect(backHome).toContainText('Back to Home');
    const href: string | null = await backHome.getAttribute('href');
    if (href === null) {
      throw new Error('/apiary 404 page rendered a "Back to Home" link without an href');
    }
    expect(new URL(href).pathname, `Back to Home href ${href} must point at the site root`).toBe('/');

    // Document integrity: exactly one theme shell element each.
    await expect(page.locator(BLOG.html)).toHaveCount(1);
    await expect(page.locator(BLOG.banner)).toHaveCount(1);
    await expect(page.locator(BLOG.main)).toHaveCount(1);
    await expect(page.locator(BLOG.footer)).toHaveCount(1);
  });

  test('leaves the frontend single post route on the theme', async ({ page }) => {
    // Counterweight to the API scenarios: if scriptlog_is_api_request()
    // over-matched, this page would start serving JSON or the API 404.
    const response = await page.goto(`${BASE_URL}${WIDGET_PAGE_PATH}`);
    const description: string = `GET ${WIDGET_PAGE_PATH}`;

    expect(response?.status(), description).toBe(200);
    expect(response?.headers()['content-type'] ?? '', `${description} content-type`).toMatch(/^text\/html/);
    expect(response?.headers()['location'], `${description} must not send a Location header`).toBeUndefined();

    await expect(page.locator(BLOG.postSingle)).toBeVisible();
    await expect(page.locator(BLOG.searchInput)).toBeVisible();
    await expect(page.locator(BLOG.banner)).toHaveCount(1);
    await expect(page.locator(BLOG.main)).toHaveCount(1);
    await expect(page.locator(BLOG.footer)).toHaveCount(1);

    const html: string = await page.content();
    // The widget still targets the API, not a workaround path.
    expect(html, `${description} must expose the API base URL to the widget`).toMatch(
      /api_url\s*:\s*'[^']*\/api\/v1'/,
    );
    expect(() => JSON.parse(html), `${description} must not be JSON`).toThrow();
  });

  test('leaves the frontend search route on the theme', async ({ page }) => {
    const path: string = `/search?q=${FIXTURE_KEYWORD}`;
    const response = await page.goto(`${BASE_URL}${path}`);
    const description: string = `GET ${path}`;

    expect(response?.status(), description).toBe(200);
    expect(response?.headers()['content-type'] ?? '', `${description} content-type`).toMatch(/^text\/html/);
    expect(response?.headers()['location'], `${description} must not send a Location header`).toBeUndefined();

    await expect(page.locator(BLOG.searchResultsPage)).toBeVisible();
    await expect(page.locator(BLOG.searchHeading)).toBeVisible();

    const html: string = await page.content();
    expect(() => JSON.parse(html), `${description} must not be JSON`).toThrow();
  });

  test('sidebar widget receives 200 JSON from /api/v1/search and renders suggestions', async ({ page }) => {
    // Plan 6.1: attach the pageerror/console collectors BEFORE navigating, so
    // no early error escapes. CSP Report-Only noise is filtered below.
    const pageErrors: string[] = [];
    const consoleErrors: string[] = [];
    const apiRequests: string[] = [];
    const apiResponses: Array<{ url: string; status: number }> = [];

    page.on('pageerror', (error: Error): void => {
      pageErrors.push(error.message);
    });
    page.on('console', (message): void => {
      // CSP `Report-Only` violations come from the theme shell on every blog
      // page, not from the widget (same filter as blog-search-autocomplete).
      const text: string = message.text();
      const isCspNoise: boolean = /content.security.policy|\[report only\]/i.test(text);
      if (message.type() === 'error' && !isCspNoise) {
        consoleErrors.push(text);
      }
    });
    page.on('request', (request: Request): void => {
      if (request.url().includes('/api/')) {
        apiRequests.push(request.url());
      }
    });
    page.on('response', (response: Response): void => {
      if (response.url().includes('/api/v1')) {
        apiResponses.push({ url: response.url(), status: response.status() });
      }
    });

    // Plan 6.2: open the sidebar-bearing post page and assert it is a normal
    // 200 HTML document (the widget page itself must never become JSON).
    const documentResponse = await page.goto(`${BASE_URL}${WIDGET_PAGE_PATH}`);
    expect(documentResponse?.status(), `GET ${WIDGET_PAGE_PATH} document status`).toBe(200);

    // Plan 6.3: pre-typing baseline.
    const input = page.locator(BLOG.searchInput);
    await expect(input).toBeVisible();
    await expect(input).toHaveAttribute('aria-expanded', 'false');

    // Plan 6.4: the predicate must match the FINAL keyword: intermediate
    // responses for shorter input render the empty state (plan §10.1).
    const apiCall: Promise<Response> = page.waitForResponse(
      (response: Response): boolean =>
        response.url().includes('/api/v1/search') &&
        response.request().method() === 'GET' &&
        new URL(response.url()).searchParams.get('q') === FIXTURE_KEYWORD,
    );
    // Plan 6.5: type the complete keyword - FULLTEXT matching is whole-word.
    await input.fill(FIXTURE_KEYWORD);
    // Plan 6.6-6.7: await the XHR and assert the transport contract.
    const response: Response = await apiCall;
    const description: string = `widget XHR ${response.url()}`;

    expect(response.status(), description).toBe(200);
    expect(response.headers()['content-type'] ?? '', `${description} content-type`).toMatch(/^application\/json/);
    expect(response.headers()['location'], `${description} must not send a Location header`).toBeUndefined();
    expect(response.request().redirectedFrom(), `${description} must not be redirected`).toBeNull();

    const requestUrl = new URL(response.url());
    expect(requestUrl.pathname, `${description} pathname`).toBe('/api/v1/search');
    expect(requestUrl.searchParams.get('type'), `${description} type parameter`).toBe('all');
    expect(response.url(), `${description} must never point at /api/index.php`).not.toContain('/api/index.php');

    // Plan 6.8-6.9: the rendered suggestions and the (empty) error region.
    await expect(page.locator(BLOG.searchResults)).toHaveClass(/show/);
    await expect(page.locator(BLOG.searchResultItem).first()).toBeVisible();
    await expect(page.locator(BLOG.searchResultTitle).first()).toHaveText(FIXTURE_POST_TITLE);
    // The regression's exact failure point: this region used to show
    // "An error occurred during search. Please try again later.".
    await expect(page.locator(BLOG.searchError)).toHaveText('');
    await expect(input).toHaveAttribute('aria-expanded', 'true');

    // Plan 6.10: the widget must not navigate away from the page while typing.
    expect(page.url(), 'widget must not navigate').toContain('p=11');

    // Plan 6.11 / 7.5-7.6: no uncaught errors, no 3xx or HTML on /api/v1, and
    // no request ever hits /api/index.php.
    expect(pageErrors, 'uncaught page errors').toEqual([]);
    expect(consoleErrors, 'console errors').toEqual([]);
    expect(consoleErrors.join('\n'), 'jQuery parsererror signature (dataType json got HTML)').not.toMatch(
      /parsererror/i,
    );
    expect(
      apiRequests.filter((requestUrl: string): boolean => requestUrl.includes('/api/index.php')),
      'no direct /api/index.php requests',
    ).toEqual([]);
    expect(
      apiResponses.filter((entry: { url: string; status: number }): boolean => entry.status !== 200),
      'every widget API response must be 200',
    ).toEqual([]);
  });

  test('surfaces a visible error when the search API fails', async ({ page }) => {
    // Plan 7.9 (M-06): negative control for #search-error - proves the
    // empty-state assertion in the happy path is meaningful instead of
    // permanently empty by accident.
    await page.route('**/api/v1/search*', (route: Route) =>
      route.fulfill({
        status: 500,
        contentType: 'text/html',
        body: '<html><body>boom</body></html>',
      }),
    );

    const documentResponse = await page.goto(`${BASE_URL}${WIDGET_PAGE_PATH}`);
    expect(documentResponse?.status(), `GET ${WIDGET_PAGE_PATH} document status`).toBe(200);

    const input = page.locator(BLOG.searchInput);
    const failedCall: Promise<Response> = page.waitForResponse(
      (response: Response): boolean =>
        response.url().includes('/api/v1/search') &&
        new URL(response.url()).searchParams.get('q') === FIXTURE_KEYWORD,
    );
    await input.fill(FIXTURE_KEYWORD);
    const response: Response = await failedCall;

    expect(response.status(), 'simulated failure status').toBe(500);

    // A 500 + text/html must surface the widget's i18n error, not be swallowed.
    const error = page.locator(BLOG.searchError);
    await expect(error).toHaveClass(/show/);
    await expect(error).toBeVisible();
    await expect(error).not.toHaveText('');
    await expect(error).toContainText(/error/i);
  });
});
