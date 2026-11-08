// security-hardening.spec.ts
//
// Disclosure / traversal / hardening campaign (specs/security-hardening.md):
// source-disclosure probes, production .htaccess deny rules, percent-encoded
// path traversal, SQL & error-disclosure probes, security headers and
// session cookie flags.
//
// NOTE on /.env: the Playwright harness runs e2e/router.php (PHP built-in
// server), which serves existing static files and does NOT process .htaccess
// - an HTTP probe for /.env would document a harness artifact. The production
// contract is asserted via the committed deny rules (file content, below).

import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { BASE_URL } from './gdpr-fixtures';

/** Response-body markers that indicate SQL/DB/stack-trace disclosure. */
const ERROR_LEAK_PATTERNS: string[] = [
  'SQL syntax',
  'PDOException',
  'Stack trace',
  'Fatal error',
  'mysqli_',
  'You have an error in your SQL',
  'tbl_users',
  'SELECT * FROM',
];

/**
 * Assert that a response body carries no SQL/DB/stack-trace markers.
 *
 * @param body Raw response body.
 * @param label Context label for the assertion message.
 */
function expectNoErrorLeaks(body: string, label: string): void {
  for (const pattern of ERROR_LEAK_PATTERNS) {
    expect(body, `${label} must not contain "${pattern}"`).not.toContain(pattern);
  }
}

/** Library classes protected by the defined('SCRIPTLOG') guard. */
const GUARDED_LIB_FILES: string[] = [
  '/lib/service/NotificationService.php',
  '/lib/utility/comment-submission.php',
  '/lib/core/Bootstrap.php',
  '/lib/dao/PostDao.php',
];

/** Percent-encoded traversal probes with verified expected statuses. */
const TRAVERSAL_PROBES: Array<{ url: string; expectStatus: number }> = [
  { url: '/themes/..%2fconfig.php', expectStatus: 404 },
  { url: '/files/..%2fconfig.php', expectStatus: 404 },
  { url: '/download/..%2f..%2fconfig.php', expectStatus: 404 },
  { url: '/download/%2e%2e%2f%2e%2e%2fconfig.php', expectStatus: 404 },
  { url: '/download/..%2f..%2fetc%2fpasswd', expectStatus: 404 },
];

/**
 * SQL-injection probes: parameter values carrying classic metacharacter
 * payloads with the verified ordinary responses they must keep producing.
 */
const SQLI_PROBES: Array<{ url: string; expectStatus: number }> = [
  { url: "/?p=%27%20OR%20%271%27%3D%271", expectStatus: 404 },
  { url: '/search?q=%27%20OR%20%271%27%3D%271--', expectStatus: 200 },
  { url: "/category/x%27%20OR%201%3D1--", expectStatus: 404 },
  { url: "/tag/x%27%20OR%201%3D1--", expectStatus: 404 },
  { url: "/?pg=%27%20OR%201%3D1--", expectStatus: 404 },
  { url: '/api/v1/search?q=%27', expectStatus: 400 },
  { url: '/api/v1/posts/1%27', expectStatus: 404 },
  { url: '/api/v1/posts/99999999999999999999', expectStatus: 404 },
  { url: '/api/v1/categories?q=%27', expectStatus: 200 },
  { url: '/api/v1/doesnotexist', expectStatus: 404 },
];

test.describe('security: disclosure, traversal & hardening', () => {
  test('config files execute without disclosing PHP source', async ({
    request,
  }) => {
    for (const url of ['/config.php', '/config.sample.php']) {
      const res = await request.get(BASE_URL + url);
      expect(res.status(), url).toBe(200);
      const body = await res.text();
      // Array-returning config: no output, no credentials, no source.
      expect(body.trim(), url).toBe('');
      expect(body, url).not.toContain('<?php');
    }
  });

  test('library classes refuse direct execution with a fixed guard message', async ({
    request,
  }) => {
    for (const file of GUARDED_LIB_FILES) {
      const res = await request.get(BASE_URL + file);
      expect(res.status(), file).toBe(200);
      const body = await res.text();
      // 27 bytes, no source/namespace/function bodies leak past the guard.
      expect(body, file).toBe('Direct access not permitted');
      expect(body, file).not.toContain('<?php');
    }

    // lib/main.php hits the early-404 short-circuit instead of the guard.
    const main = await request.get(`${BASE_URL}/lib/main.php`);
    expect(main.status()).toBe(404);
    const mainBody = await main.text();
    expect(mainBody).not.toContain('<?php');
  });

  test('production .htaccess denies dotfiles, .env, config.php and /lib/', () => {
    // File-content assertion: the e2e router does not process .htaccess, so
    // the deployment contract is verified on disk instead of over HTTP.
    const htaccessPath = path.resolve(__dirname, '..', '.htaccess');
    const htaccess = fs.readFileSync(htaccessPath, 'utf8');

    expect(htaccess).toContain('<FilesMatch "^\\.">'); // covers .env, .htaccess, .git
    expect(htaccess).toContain('\\.(env|log|sql|sh|bak|old|'); // sensitive extensions
    expect(htaccess).toContain('<Files "config.php">');
    expect(htaccess).toContain('RewriteRule ^(lib)/ - [F,L]');
  });

  test('percent-encoded traversal paths never escape the document root', async ({
    request,
  }) => {
    // Raw-dot variants (/themes/../config.php) are normalized away by every
    // conforming WHATWG URL client before hitting the wire, so only encoded
    // forms actually reach the server - those must 404 without content.
    for (const probe of TRAVERSAL_PROBES) {
      const res = await request.get(BASE_URL + probe.url);
      expect(res.status(), probe.url).toBe(probe.expectStatus);
      const body = await res.text();
      expect(body, probe.url).not.toContain('<?php');
      expect(body, probe.url).not.toContain('root:x');
      expect(body, probe.url).not.toContain('tpglkl'); // config table-prefix fallback
    }
  });

  test('SQL injection probes surface ordinary responses and no DB errors', async ({
    request,
  }) => {
    for (const probe of SQLI_PROBES) {
      const res = await request.get(BASE_URL + probe.url);
      expect(res.status(), probe.url).toBe(probe.expectStatus);
      const body = await res.text();
      expectNoErrorLeaks(body, probe.url);
    }

    // API errors stay structured JSON, not stack traces.
    const badSearch = await request.get(`${BASE_URL}/api/v1/search?q=%27`);
    const badJson = await badSearch.json();
    expect(badJson.success).toBe(false);
    expect(badJson.error.code).toBe('BAD_REQUEST');

    const missingPost = await request.get(
      `${BASE_URL}/api/v1/posts/99999999999999999999`,
    );
    const missingJson = await missingPost.json();
    expect(missingJson.success).toBe(false);
    expect(missingJson.error.code).toBe('NOT_FOUND');
    expect(missingJson.error.message).toContain('Post not found');

    const unknownRoute = await request.get(`${BASE_URL}/api/v1/doesnotexist`);
    const unknownJson = await unknownRoute.json();
    expect(unknownJson.success).toBe(false);
    expect(unknownJson.error.code).toBe('NOT_FOUND');
  });

  test('security headers and session cookie flags are set on document responses', async ({
    page,
    context,
  }) => {
    const headers = (await page.goto(`${BASE_URL}/`))?.headers() ?? {};

    // Clickjacking / MIME-sniffing / referrer hardening.
    expect(headers['x-frame-options']).toBe('sameorigin');
    expect(headers['x-content-type-options']).toBe('nosniff');
    expect(headers['content-security-policy']).toContain("default-src 'self'");
    expect(headers['content-security-policy']).toContain("frame-ancestors 'self'");
    expect(headers['referrer-policy']).toBe('strict-origin-when-cross-origin');
    expect(headers['strict-transport-security']).toContain('max-age=');

    // Session cookie must be unreadable by script and not sent cross-site.
    const cookies = await context.cookies();
    const session = cookies.find((cookie) => cookie.name === '_scriptlog');
    expect(session, '_scriptlog cookie must be issued').toBeDefined();
    expect(session?.httpOnly).toBe(true);
    expect(session?.sameSite).toBe('Lax');
    expect(session?.secure).toBe(true);
  });
});
