// security-html-injection.spec.ts
//
// HTML injection / XSS security campaign (specs/security-html-injection.md):
// reflected payloads (search box, admin login), stored comment rendering,
// API comment write-path sanitisation, and the comment form pipeline
// (honeypot, per-IP throttle, routing, CSRF).
//
// KNOWN BUGS - asserted with test.fail() so the suite stays green while the
// bug exists and goes red the moment it is fixed (never a silent pass):
//
//   B1  lib/main.php:35-59 early-404 short-circuit (introduced in commit
//       db2f5b6d) does not know /comments-post.php, so the real comment form
//       POST dies with 404 before any processing (UI symptom: generic
//       #error_message "An error occurred. Please try again later.").
//   B2  checking_form_payload() in lib/utility/comment-submission.php:223
//       validates the whitelist ['post_id','name','email','comment','csrf']
//       against filter_input_array() output, which always contains parent_id
//       -> unknown extra field -> HTTP 413 on EVERY routed submission.
//   B3  lib/utility/comment-submission.php:230 does
//       list($errors) = checking_form_input(...), overwriting the CSRF errors
//       merged at lines 225-228 -> a missing/invalid CSRF token is accepted
//       whenever the remaining fields are valid.
//   B4  The whitelist 413 itself is malformed at transport level:
//       comment-submission.php:63 builds its status header as
//       APP_PROTOCOL . ' 413 ...', but lib/common.php:65 defines APP_PROTOCOL
//       as the URL *scheme* ('http'/'https') - the response therefore
//       contains a colonless header line "http 413 Payload Too Large" which
//       strict HTTP clients (Node/Playwright, HPE_INVALID_HEADER_TOKEN)
//       cannot parse, while lenient clients (curl) accept it.
//
// Pipeline tests bypass B1 with POST /comments-post.php?x=1 (a non-empty query
// string passes the short-circuit - the same trick /fetch-comments.php uses).

import { test, expect } from '@playwright/test';
import type { APIRequestContext } from '@playwright/test';
import { BLOG } from './blog-selectors';
import {
  BASE_URL,
  GDPR_POST_ID,
  clearLoginAttempts,
  clearRateLimiters,
  dbCount,
  dbScalar,
  runSql,
} from './gdpr-fixtures';

/** Author-name markers used to seed and clean up probe comments. */
const XSS_RENDER_PROBE = 'E2E XSS Render Probe';
const API_PROBE = 'E2E API Probe';
const HONEYPOT_PROBE = 'E2E Honeypot';
const THROTTLE_PROBE = 'E2E Throttle Probe';
const PIPELINE_PROBE = 'E2E Pipeline Probe';
const CSRF_PROBE = 'E2E CSRF Probe';
const UI_PROBE = 'E2E Commenter';

/** All marker authors this spec may leave behind (cleaned up in afterAll). */
const MARKER_AUTHORS: string[] = [
  XSS_RENDER_PROBE,
  API_PROBE,
  HONEYPOT_PROBE,
  THROTTLE_PROBE,
  PIPELINE_PROBE,
  CSRF_PROBE,
  UI_PROBE,
];

/** SQL IN-list of quoted marker authors. */
const MARKER_LIST: string = MARKER_AUTHORS.map(
  (name) => `'${name.replace(/'/g, "''")}'`,
).join(',');

/** HTML injection payload used for reflected probes. */
const REFLECTED_PAYLOAD: string = '<img src=x onerror=alert(1)>';

/** Server entry point for comment pipeline probes (query bypass for bug B1). */
const COMMENT_ENDPOINT: string = `${BASE_URL}/comments-post.php?x=1`;

/** Outcome of a comment pipeline POST. */
interface PipelineResponse {
  /** HTTP status, or 413 when the malformed-response defect (B4) aborted the parse. */
  status: number;
  /** Raw response body, or null when the transport could not parse the response. */
  raw: string | null;
}

/**
 * POST the comment pipeline endpoint.
 *
 * Tolerates bug B4: the whitelist 413 response carries a colonless header
 * line ("http 413 Payload Too Large" - APP_PROTOCOL is the URL scheme, see
 * lib/common.php:65), which Playwright's APIRequestContext rejects with
 * HPE_INVALID_HEADER_TOKEN before a response object exists. That transport
 * failure is mapped back to status 413 so assertions stay meaningful;
 * any other transport error is rethrown.
 *
 * @param request Playwright request context (no cookies required).
 * @param form URL-encoded form fields to submit.
 * @returns Parsed status plus raw body when available.
 */
async function postCommentPipeline(
  request: APIRequestContext,
  form: Record<string, string>,
): Promise<PipelineResponse> {
  try {
    const res = await request.post(COMMENT_ENDPOINT, { form });
    return { status: res.status(), raw: await res.text() };
  } catch (err) {
    const message = err instanceof Error ? err.message : String(err);
    if (message.indexOf('Invalid header token') !== -1) {
      return { status: 413, raw: null };
    }
    throw err;
  }
}

/**
 * Delete every marker comment this spec may have created.
 */
function cleanupMarkerComments(): void {
  runSql(`DELETE FROM tbl_comments WHERE comment_author_name IN (${MARKER_LIST})`);
}

/**
 * Count approved+pending comments currently attached to the target post.
 *
 * @returns Row count for post 11.
 */
function commentCountOnPost(): number {
  return dbCount(
    `SELECT COUNT(*) FROM tbl_comments WHERE comment_post_id = ${GDPR_POST_ID}`,
  );
}

test.describe.serial('security: HTML injection (reflected, stored, API)', () => {
  test.beforeEach(() => {
    // The comment throttle is keyed by IP in public/cache/rate_limit/ -
    // reset it so pipeline tests never inherit a previous test's counter.
    clearRateLimiters();
  });

  test.beforeAll(() => {
    clearRateLimiters();
    clearLoginAttempts();
    // Remove leftovers from earlier aborted runs so marker counts start at 0.
    cleanupMarkerComments();
  });

  test.afterAll(() => {
    cleanupMarkerComments();
    clearRateLimiters();
    clearLoginAttempts();
  });

  test('reflected XSS: search results neutralise an HTML payload in the keyword field', async ({
    page,
  }) => {
    const dialogs: string[] = [];
    page.on('dialog', (dialog) => {
      dialogs.push(dialog.message());
      void dialog.dismiss().catch(() => undefined);
    });

    const res = await page.goto(
      `${BASE_URL}/search?q=${encodeURIComponent(REFLECTED_PAYLOAD)}`,
    );
    expect(res?.status()).toBe(200);

    // The results page renders the query back into the search field...
    await expect(page.locator(BLOG.searchHeading)).toBeVisible();
    const keyword = page.locator('#page-search-keyword');
    await expect(keyword).toBeVisible();

    // ...but angle brackets are stripped server-side (verified:
    // value="img src=x onerror=alert 1").
    const value = await keyword.inputValue();
    expect(value).not.toContain('<');
    expect(value).not.toContain('>');

    // No raw payload in the source, no executable element, no alert().
    const html = await page.content();
    expect(html).not.toContain('<img src=x onerror');
    expect(await page.locator('img[src="x"]').count()).toBe(0);
    expect(dialogs).toEqual([]);
  });

  test('reflected XSS: failed login does not render an unescaped username payload', async ({
    page,
  }) => {
    const dialogs: string[] = [];
    page.on('dialog', (dialog) => {
      dialogs.push(dialog.message());
      void dialog.dismiss().catch(() => undefined);
    });

    await page.goto(`${BASE_URL}/admin/login.php`);
    await page.locator('#inputLogin').fill(REFLECTED_PAYLOAD);
    await page.locator('#inputPassword').fill('WrongPassword1!');
    await page.locator('input[name="LogIn"]').click();
    await page.waitForLoadState('domcontentloaded');

    // The form re-renders with the payload reflected into the value attribute
    // (escaped as &lt;img ...&gt; - inputValue() decodes that back, which is
    // correct). Assert against the raw source instead.
    const html = await page.content();
    expect(html).not.toContain('<img src=x onerror');
    expect(await page.locator('img[src="x"]').count()).toBe(0);
    expect(dialogs).toEqual([]);

    // Still on the login page (wrong password - nothing executed).
    await expect(page.locator('#inputLogin')).toBeVisible();
    await expect(page.locator('#inputPassword')).toBeVisible();
    clearLoginAttempts();
  });

  test('stored XSS: raw markup in an approved comment renders inert on the post page', async ({
    page,
  }) => {
    // Worst case: seed the payload directly into the DB, bypassing every
    // write-path sanitiser, to test the render layer alone
    // (load-comment.min.js escapeHtml()).
    runSql(
      `INSERT INTO tbl_comments
         (comment_post_id, comment_parent_id, comment_author_name,
          comment_author_ip, comment_author_email, comment_content,
          comment_status, comment_date)
       VALUES
         (${GDPR_POST_ID}, 0, '${XSS_RENDER_PROBE}', '127.0.0.1',
          'e2e.render@example.com',
          '<img src=x onerror=alert(1)><script>alert(2)</script>',
          'approved', NOW())`,
    );

    const dialogs: string[] = [];
    page.on('dialog', (dialog) => {
      dialogs.push(dialog.message());
      void dialog.dismiss().catch(() => undefined);
    });

    await page.goto(`${BASE_URL}/?p=${GDPR_POST_ID}`);

    // Comments load asynchronously into #comments (data-post-id attribute).
    const comments = page.locator('#comments');
    await expect(comments).toContainText(XSS_RENDER_PROBE, { timeout: 15_000 });

    const seeded = comments.locator('.comment').filter({
      hasText: XSS_RENDER_PROBE,
    });
    await expect(seeded.first()).toBeVisible();

    // Payload shows as literal text (entities decoded by innerText)...
    await expect(seeded.first()).toContainText(REFLECTED_PAYLOAD);
    await expect(seeded.first()).toContainText('<script>alert(2)</script>');

    // ...but never as live elements, and no dialog fired.
    expect(await seeded.locator('img').count()).toBe(0);
    expect(await seeded.locator('script').count()).toBe(0);
    expect(await page.locator('#comments script').count()).toBe(0);
    expect(dialogs).toEqual([]);
  });

  test('API write path: POST /api/v1/comments sanitises HTML and holds the comment pending', async ({
    request,
  }) => {
    const res = await request.post(`${BASE_URL}/api/v1/comments`, {
      data: {
        comment_post_id: GDPR_POST_ID,
        comment_author_name: API_PROBE,
        comment_author_email: 'e2e.api@example.com',
        comment_content:
          '<script>alert(1)</script><img src=x onerror=alert(2)>bold <b>text</b>',
      },
    });
    expect(res.status()).toBe(201);

    const body = await res.json();
    expect(body.success).toBe(true);
    // New API comments are never public before moderation.
    expect(body.data.status).toBe('pending');

    const stored: string = String(body.data.content ?? '');
    expect(stored).not.toContain('<script');
    expect(stored).not.toContain('<img');
    expect(stored).not.toContain('<b');
    expect(stored).not.toContain('onerror=');

    // Server-side truth: the row in tbl_comments is sanitised too.
    const id = Number(body.data.id);
    const dbContent = dbScalar(
      `SELECT comment_content FROM tbl_comments WHERE ID = ${id}`,
    );
    expect(dbContent).not.toContain('<script');
    expect(dbContent).not.toContain('<img');
    expect(dbContent).not.toContain('onerror=');
  });

  test('comment pipeline: honeypot submissions return fake success and store nothing', async ({
    request,
  }) => {
    const before = commentCountOnPost();

    // ?x=1 bypasses bug B1 (lib/main.php early-404 short-circuit).
    // The honeypot field short-circuits BEFORE CSRF/payload checks, so a
    // placeholder token is fine and the 200 must be fake.
    const res = await request.post(COMMENT_ENDPOINT, {
      form: {
        post_id: String(GDPR_POST_ID),
        parent_id: '0',
        name: HONEYPOT_PROBE,
        email: 'e2e.honeypot@example.com',
        comment: 'honeypot probe comment',
        csrf: 'bogus-token',
        website: 'http://spam.example',
      },
    });
    expect(res.status()).toBe(200);

    const body = await res.json();
    expect(body.success).toBe(true);
    expect(body.success_message).toContain('submitted successfully');

    // Nothing was persisted.
    expect(commentCountOnPost()).toBe(before);
    expect(
      dbCount(
        `SELECT COUNT(*) FROM tbl_comments WHERE comment_author_name = '${HONEYPOT_PROBE}'`,
      ),
    ).toBe(0);
  });

  test('comment pipeline: per-IP throttle engages after 10 submissions (429 on the 11th)', async ({
    request,
  }) => {
    clearRateLimiters();

    const submit = (): Promise<PipelineResponse> =>
      postCommentPipeline(request, {
        post_id: String(GDPR_POST_ID),
        parent_id: '0',
        name: THROTTLE_PROBE,
        email: 'e2e.throttle@example.com',
        comment: 'throttle counter probe comment',
        csrf: 'bogus-token',
      });

    // Fill the 10/600s quota. Responses 1-10 are bug-dependent (413 today,
    // 200 once B2 is fixed) and deliberately not asserted - only the limiter
    // outcome matters here.
    for (let i = 0; i < 10; i++) {
      await submit();
    }

    const eleventh = await submit();
    expect(eleventh.status).toBe(429);

    const body = JSON.parse(eleventh.raw ?? '{}');
    expect(body.success).toBe(false);
    expect(String(body.error_message)).toContain('Too many comments');
  });

  test('BUG B1/B2: comment form submission succeeds end-to-end (known failure)', async ({
    page,
  }) => {
    // KNOWN BUG REPORT: lib/main.php:35-59 early-404 short-circuit (commit
    // db2f5b6d) 404s POST /comments-post.php (B1), and checking_form_payload()
    // rejects parent_id with 413 even when routed (B2). The test asserts the
    // WORKING outcome; remove test.fail() only when both are fixed.
    test.fail(
      true,
      'B1+B2: comment form POST is 404ed by the lib/main.php short-circuit; whitelist 413s every routed submission (see specs/security-html-injection.md)',
    );

    await page.goto(`${BASE_URL}/?p=${GDPR_POST_ID}`);
    await page.locator('#comment').fill('E2E pipeline probe comment');
    await page.locator('#name').fill(UI_PROBE);
    await page.locator('#email').fill('e2e.commenter@example.com');
    await page.locator('#commentForm button[type="submit"]').click();

    // Success path: the AJAX response renders #success_message (fadeIn)...
    await expect(page.locator('#success_message')).toBeVisible({
      timeout: 10_000,
    });
    // ...and the comment landed in moderation.
    expect(
      dbCount(
        `SELECT COUNT(*) FROM tbl_comments WHERE comment_author_name = '${UI_PROBE}'`,
      ),
    ).toBe(1);
  });

  test('BUG B2: routed comment pipeline accepts a valid submission (known failure)', async ({
    request,
  }) => {
    // KNOWN BUG REPORT: lib/utility/comment-submission.php:223 - the payload
    // whitelist omits parent_id, which filter_input_array() always returns
    // -> HTTP 413 for every submission that reaches the pipeline (probed with
    // valid fields and a placeholder token; CSRF handling itself is covered
    // by the BUG B3 test below). Asserts "not 413" so it keeps passing after
    // the whitelist is fixed regardless of how CSRF evolves.
    test.fail(
      true,
      'B2: checking_form_payload() whitelist rejects parent_id with 413 on every routed comment submission (specs/security-html-injection.md)',
    );

    const res = await postCommentPipeline(request, {
      post_id: String(GDPR_POST_ID),
      parent_id: '0',
      name: PIPELINE_PROBE,
      email: 'e2e.pipeline@example.com',
      comment: 'valid pipeline probe comment',
      csrf: 'bogus-token',
    });
    expect(res.status).not.toBe(413);
    expect(res.status).toBeLessThan(500);
  });

  test('BUG B2/B3: comment endpoint rejects a missing CSRF token (known failure)', async ({
    request,
  }) => {
    // KNOWN BUG REPORT: today this request never reaches the CSRF check -
    // the parent_id whitelist 413s first (B2). Once B2 is fixed, B3
    // (comment-submission.php:230 overwrites the CSRF errors merged at
    // lines 225-228) lets the insert through with 200. The test passes only
    // when the endpoint returns 400 with "Invalid CSRF token" AND stores
    // nothing - i.e. when both bugs are fixed.
    test.fail(
      true,
      'B2 (413 before CSRF) + B3 (CSRF errors overwritten at comment-submission.php:230) - missing token is never rejected (specs/security-html-injection.md)',
    );

    const before = dbCount(
      `SELECT COUNT(*) FROM tbl_comments WHERE comment_author_name = '${CSRF_PROBE}'`,
    );

    const res = await postCommentPipeline(request, {
      post_id: String(GDPR_POST_ID),
      parent_id: '0',
      name: CSRF_PROBE,
      email: 'e2e.csrf@example.com',
      comment: 'comment submitted without a CSRF token',
      // no csrf field on purpose
    });

    expect(res.status).toBe(400);
    expect(res.raw ?? '').toContain('Invalid CSRF token');

    // A rejected token must never store the comment.
    expect(
      dbCount(
        `SELECT COUNT(*) FROM tbl_comments WHERE comment_author_name = '${CSRF_PROBE}'`,
      ),
    ).toBe(before);
  });

  test('BUG B4: pipeline 413 response is parseable by strict HTTP clients (known failure)', async ({
    request,
  }) => {
    // KNOWN BUG REPORT: lib/utility/comment-submission.php:63-64 emits its
    // status via header(APP_PROTOCOL . ' 413 ...') + header('Status: ...'),
    // but APP_PROTOCOL (lib/common.php:65) is the URL scheme 'http'/'https' -
    // the response ends up with a colonless header line
    // "http 413 Payload Too Large" that Node rejects with
    // HPE_INVALID_HEADER_TOKEN (curl/browsers may tolerate it). The test
    // passes once the raw response parses and still reports 413.
    test.fail(
      true,
      'B4: malformed 413 header line (APP_PROTOCOL scheme used as HTTP protocol) breaks strict HTTP clients (specs/security-html-injection.md)',
    );

    const res = await request.post(COMMENT_ENDPOINT, {
      form: {
        post_id: String(GDPR_POST_ID),
        parent_id: '0',
        name: THROTTLE_PROBE,
        email: 'e2e.transport@example.com',
        comment: 'transport parse probe comment',
        csrf: 'bogus-token',
      },
    });
    expect(res.status()).toBe(413);
  });
});
