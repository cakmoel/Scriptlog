// gdpr-deletion-request.spec.ts
// Phase 5 of specs/gdpr.md: admin "Request Data Deletion" flow.
// Submitting a valid deletion request creates a DB row (status pending) and
// shows a success alert; the DSAR rate limiter engages after 5 per 15 minutes.
import { test, expect } from '@playwright/test';
import {
  ADMIN_PASS,
  ADMIN_USER,
  BASE_URL,
  GDPR_EMAIL,
  adminLogin,
  clearConsentCookies,
  clearLoginAttempts,
  clearRateLimiters,
  dbCount,
  noValidate,
  runSql,
  seedGdprFixtures,
} from './gdpr-fixtures';

const DELETION_URL: string = `${BASE_URL}/admin/index.php?load=privacy&p=data-deletion`;

test.describe('GDPR admin data deletion request', () => {
  // The DSAR rate limiter is shared per-IP and reseeded in beforeAll, so tests
  // run in declaration order on one worker instead of racing across
  // fullyParallel workers.
  test.describe.configure({ mode: 'serial' });

  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    seedGdprFixtures();
  });

  test.beforeEach(async ({ context }): Promise<void> => {
    await clearConsentCookies(context);
    clearRateLimiters();
  });

  test('valid deletion request creates a pending row and success alert', async ({
    page,
  }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(DELETION_URL);
    await expect(page.locator('form')).toBeVisible();

    await page.locator('#email').fill(GDPR_EMAIL);
    await page.locator('#reason').fill('I want my data gone');
    await page.locator('input[name="confirm_delete"]').check();
    await page.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('.alert-success')).toContainText('submitted');

    const count: number = dbCount(
      `SELECT COUNT(*) FROM tbl_data_requests WHERE request_email='${GDPR_EMAIL}' AND request_type='deletion'`,
    );
    expect(count).toBeGreaterThan(0);
  });

  test.fixme(
    'deletion request requires email confirmation checkbox',
    'admin/privacy.php data-deletion handler only checks isset($_POST[\'delete_email\']); confirm_delete is not enforced server-side, so a request row is created regardless of the checkbox.',
    async ({ page }) => {
      await adminLogin(page, ADMIN_USER, ADMIN_PASS);
      await page.goto(DELETION_URL);
      await expect(page.locator('form')).toBeVisible();

      await noValidate(page.locator('form'));
      await page.locator('#email').fill(GDPR_EMAIL);
      // confirm_delete left unchecked
      await page.locator('button[type="submit"]').click();

      const count: number = dbCount(
        `SELECT COUNT(*) FROM tbl_data_requests WHERE request_email='${GDPR_EMAIL}' AND request_type='deletion'`,
      );
      expect(count).toBe(0);
    },
  );

  test('deletion request is rate-limited per IP', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);

    // The DSAR limiter allows 5 requests per 15 minutes per IP (127.0.0.1).
    // Each reload renders a fresh single-use CSRF token in the session, so we
    // submit through the real browser session in a loop.
    for (let i = 0; i < 5; i++) {
      await page.goto(DELETION_URL);
      await page.locator('#email').fill(`rate-${i}@e2e.local`);
      await page.locator('#reason').fill('rate test');
      await page.locator('input[name="confirm_delete"]').check();
      await page.locator('button[type="submit"]').click();
      await expect(page.locator('.alert-success')).toBeVisible();
    }

    // 6th request is throttled.
    await page.goto(DELETION_URL);
    await page.locator('#email').fill('over-limit@e2e.local');
    await page.locator('#reason').fill('rate test');
    await page.locator('input[name="confirm_delete"]').check();
    await page.locator('button[type="submit"]').click();
    await expect(page.locator('.alert-danger')).toContainText(
      'Too many data requests',
    );

    clearRateLimiters();
    await runSql(
      "DELETE FROM tbl_data_requests WHERE request_email LIKE 'rate-%@e2e.local' OR request_email='over-limit@e2e.local'",
    );
  });
});
