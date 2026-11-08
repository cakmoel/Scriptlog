// security-remediation.spec.ts
// e2e verification for the RCE audit remediation
// (plan/RCE_REMEDIATION_PLAN.md, report/RCE_AUDIT_REPORT_20260816_081632.md).
//
// Covers:
//   F9  - enforced CSP carries `script-src-attr 'none'` (inline handlers dead).
//   F9  - enforced CSP carries a per-request script nonce (RCE R2).
//   F9  - admin pages ship no inline event-handler attributes.
//   F1  - admin post editor sanitizes a stored XSS payload (onerror stripped).
//   R1  - editor also strips handlers outside the legacy 12-name deny list
//         (onpointerenter/ontoggle) and neutralizes javascript: URLs.
//   R3  - post editor and theme installer use separated per-form CSRF slots,
//         so a token harvested on one form cannot authorize the other.
//   F5  - API keys authenticate only via the X-API-Key header, never the query
//         string, and invalid keys are rejected.
//   F6  - the API rate-limit bucket is keyed on the client IP: rotating
//         X-API-Key values cannot buy a fresh bucket.
//
// NOTE: admin *write* forms (POST) currently fail a pre-existing CSRF check in
// this dev environment (verified against the pre-fix baseline), so this spec
// deliberately avoids admin POST forms. Post content for the F1 case is seeded
// directly via the DB and restored afterwards.
import { test, expect } from '@playwright/test';
import type { APIResponse } from '@playwright/test';
import {
  ADMIN_PASS,
  ADMIN_USER,
  BASE_URL,
  adminLogin,
  clearLoginAttempts,
  clearRateLimiters,
  dbScalar,
  runSql,
  sqlEscape,
  syncTestUserPasswords,
} from './gdpr-fixtures';
import { execSync } from 'child_process';
import crypto from 'crypto';

const XSS_POST_ID: number = 11;
const XSS_PAYLOAD: string = '<p><img src="x" onerror="alert(1)"></p>';
const EDIT_URL: string = `${BASE_URL}/admin/index.php?load=posts&action=editPost&Id=${XSS_POST_ID}`;

// Ephemeral per-run API key: the raw value exists only in this process and is
// never committed. Its bcrypt hash is seeded for the admin user and removed in
// afterAll, so no key material persists in the repo or the dump.
const API_KEY: string = process.env.E2E_REMEDIATION_API_KEY || `e2e-remediation-${crypto.randomBytes(24).toString('hex')}`;
const API_KEY_DESC: string = `e2e remediation key ${crypto.randomBytes(4).toString('hex')}`;
const ADMIN_USER_ID: number = 767;

/**
 * Hash a raw API key with plain bcrypt (tbl_api_keys.key_hash uses
 * password_hash() of the raw key - see table comment in blogware_e2e.sql).
 * Key travels via stdin so it never appears in ps output.
 *
 * @param rawKey Raw API key to hash.
 * @returns bcrypt hash string.
 */
function hashApiKey(rawKey: string): string {
  const out: string = execSync(
    `php -r '$k=stream_get_contents(STDIN); echo password_hash($k, PASSWORD_BCRYPT, ["cost"=>4]);'`,
    { encoding: 'utf8', input: rawKey, stdio: ['pipe', 'pipe', 'ignore'] },
  );
  return out.trim();
}

// Post 11's original content is restored after the F1 test.
let originalContent: string = '';

test.describe.serial('security remediation', () => {
  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    // Sync the dumped admin hash to the runtime-resolved E2E_ADMIN_PASS
    // (no full GDPR reseed - this spec manages post 11 + API keys itself).
    syncTestUserPasswords();
    originalContent = dbScalar(
      `SELECT post_content FROM tbl_posts WHERE ID = ${XSS_POST_ID}`,
    );

    // Seed a valid bcrypt-hashed API key for the admin user (idempotent).
    // The hash is computed from the ephemeral raw key at runtime, so the repo
    // never carries a working key+hash pair.
    const apiKeyHash: string = hashApiKey(API_KEY);
    runSql(`DELETE FROM tbl_api_keys WHERE description = '${sqlEscape(API_KEY_DESC)}'`);
    runSql(
      `INSERT INTO tbl_api_keys (user_id, key_hash, description, created_at)
       VALUES (${ADMIN_USER_ID}, '${apiKeyHash}', '${sqlEscape(API_KEY_DESC)}', NOW())`,
    );
  });

  test.afterAll((): void => {
    // Restore the seeded post content and remove the seeded API key.
    runSql(
      `UPDATE tbl_posts SET post_content = '${originalContent.replace(/'/g, "''")}' WHERE ID = ${XSS_POST_ID}`,
    );
    runSql(`DELETE FROM tbl_api_keys WHERE description = '${sqlEscape(API_KEY_DESC)}'`);
    clearRateLimiters();
  });

  test('enforced CSP header carries script-src-attr none', async ({
    request,
  }) => {
    const res: APIResponse = await request.get(`${BASE_URL}/`);
    expect(res.status()).toBe(200);

    const csp: string = res.headers()['content-security-policy'] || '';
    expect(csp).toContain("script-src-attr 'none'");
    // script-src is nonce-only: every inline script must carry the per-request
    // nonce, and 'unsafe-inline' is no longer accepted as a fallback. (style-src
    // still allows 'unsafe-inline' because themes rely on inline style attrs.)
    expect(csp).toMatch(/script-src 'self' 'nonce-[^']+'/);
    expect(csp).not.toContain("script-src 'self' 'unsafe-inline'");
  });

  test('admin pages carry no inline event-handler attributes', async ({
    page,
  }, testInfo) => {
    test.skip(
      testInfo.project.name === 'webkit',
      'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
    );
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(`${BASE_URL}/admin/index.php?load=privacy-policy`);
    await expect(page.locator('#policyTable')).toBeVisible();

    const html: string = await page.content();
    expect(html).not.toMatch(
      /on(?:click|change|submit|input|load|error|mouseover|keyup|focus)\s*="/i,
    );
  });

  test('admin post editor sanitizes a stored XSS payload (RCE F1)', async ({
    page,
  }, testInfo) => {
    test.skip(
      testInfo.project.name === 'webkit',
      'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
    );
    runSql(
      `UPDATE tbl_posts SET post_content = '${XSS_PAYLOAD}' WHERE ID = ${XSS_POST_ID}`,
    );
    try {
      await adminLogin(page, ADMIN_USER, ADMIN_PASS);
      const response = await page.goto(EDIT_URL);
      expect(response?.status()).toBe(200);
      await expect(page.locator('#summernote')).toBeVisible();

      const html: string = await page.content();
      // The onerror attribute must be stripped by sanitize_post_content.
      expect(html).not.toContain('onerror');
      // The sanitized img survives (escaped as text inside the textarea) with
      // the attribute htmLawed adds when it drops the event handler.
      expect(html).toContain('alt="image"');
    } finally {
      runSql(
        `UPDATE tbl_posts SET post_content = '${originalContent.replace(/'/g, "''")}' WHERE ID = ${XSS_POST_ID}`,
      );
    }
  });

  test('admin post editor strips handlers outside the legacy deny list (RCE R1)', async ({
    page,
  }, testInfo) => {
    test.skip(
      testInfo.project.name === 'webkit',
      'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
    );
    // Handlers NOT in the old 12-name deny list plus a javascript: URL.
    // Seeded raw (not entity-encoded) to cover the non-encoded input path.
    const payload: string =
      '<p><img src="x" onpointerenter="alert(1)">' +
      '<a href="javascript:alert(1)">x</a>' +
      '<details ontoggle="alert(1)">y</details></p>';
    runSql(`UPDATE tbl_posts SET post_content = '${payload}' WHERE ID = ${XSS_POST_ID}`);
    try {
      await adminLogin(page, ADMIN_USER, ADMIN_PASS);
      const response = await page.goto(EDIT_URL);
      expect(response?.status()).toBe(200);
      await expect(page.locator('#summernote')).toBeVisible();

      const html: string = await page.content();
      expect(html).not.toContain('onpointerenter');
      expect(html).not.toContain('ontoggle');
      expect(html).not.toContain('javascript:alert');
    } finally {
      runSql(
        `UPDATE tbl_posts SET post_content = '${originalContent.replace(/'/g, "''")}' WHERE ID = ${XSS_POST_ID}`,
      );
    }
  });

  test('post editor and theme installer use separated CSRF slots (RCE R3)', async ({
    page,
  }, testInfo) => {
    test.skip(
      testInfo.project.name === 'webkit',
      'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
    );
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);

    // Post editor carries the post-form slot, never the installer slot.
    const edit = await page.goto(EDIT_URL);
    expect(edit?.status()).toBe(200);
    await expect(page.locator('#summernote')).toBeVisible();
    await expect(page.locator('input[name="csrfPostForm"]')).toHaveCount(1);
    await expect(page.locator('input[name="csrfThemeInstall"]')).toHaveCount(0);

    // Theme installer carries the installer slot, never the post slot.
    const install = await page.goto(
      `${BASE_URL}/admin/index.php?load=templates&action=installTheme`,
    );
    expect(install?.status()).toBe(200);
    await expect(page.locator('input[name="zip_file"]')).toBeVisible();
    await expect(page.locator('input[name="csrfThemeInstall"]')).toHaveCount(1);
    await expect(page.locator('input[name="csrfPostForm"]')).toHaveCount(0);
  });

  test('API key authenticates only via header, never query string (RCE F5)', async ({
    request,
  }) => {
    // Valid key in the header authenticates (422 = auth passed, validation failed).
    const header: APIResponse = await request.post(
      `${BASE_URL}/api/v1/posts`,
      {
        headers: { 'X-API-Key': API_KEY },
        data: {},
      },
    );
    expect([422, 200, 201]).toContain(header.status());

    // The same valid key in the query string must NOT authenticate.
    const query: APIResponse = await request.post(
      `${BASE_URL}/api/v1/posts?api_key=${API_KEY}`,
      { data: {} },
    );
    expect(query.status()).toBe(403);

    // An invalid key is rejected.
    const bad: APIResponse = await request.post(`${BASE_URL}/api/v1/posts`, {
      headers: { 'X-API-Key': 'z'.repeat(40) },
      data: {},
    });
    expect(bad.status()).toBe(403);
  });

  test('rate limit cannot be bypassed by rotating X-API-Key (RCE F6)', async ({
    request,
  }) => {
    clearRateLimiters();

    // Exhaust the shared per-IP read bucket while rotating a fresh key on
    // every request. If buckets were keyed by X-API-Key this would never
    // trigger a 429; because they are IP-keyed it must.
    let saw429: boolean = false;
    let first429At: number = -1;
    let remainingOn429: string | null = null;
    for (let i = 0; i < 70; i++) {
      const key: string = `rotate_${i}_${'x'.repeat(40)}`;
      const res: APIResponse = await request.get(
        `${BASE_URL}/api/v1/health`,
        { headers: { 'X-API-Key': key } },
      );
      if (res.status() === 429) {
        saw429 = true;
        first429At = i;
        remainingOn429 = res.headers()['x-ratelimit-remaining'] ?? null;
        break;
      }
    }
    expect(saw429, 'expected the shared per-IP read bucket to exhaust').toBe(
      true,
    );
    expect(first429At).toBeLessThanOrEqual(61);
    // The rejection must carry the real bucket state (Remaining: 0), not
    // an empty header: the stored rate result has to be assigned before
    // the rejection branch exits.
    expect(remainingOn429, 'rejection must report zero remaining').toBe('0');

    // Fresh keys AFTER exhaustion must remain blocked (no per-key bucket).
    for (let i = 0; i < 5; i++) {
      const res: APIResponse = await request.get(
        `${BASE_URL}/api/v1/health`,
        {
          headers: { 'X-API-Key': `rotate_after_${i}_${'y'.repeat(40)}` },
        },
      );
      expect(
        res.status(),
        'fresh key after exhaustion must stay blocked',
      ).toBe(429);
    }
  });
});
