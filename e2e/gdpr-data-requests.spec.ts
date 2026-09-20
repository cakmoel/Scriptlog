// gdpr-data-requests.spec.ts
// Phase 6 of specs/gdpr.md: admin data-request management incl. the erasure
// lifecycle. Completing a deletion/erasure request requires the confirm_erasure
// guard; once confirmed, the subject's account is removed, comments are
// anonymized, and authored posts are reassigned to the fallback author (767).
import { test, expect } from '@playwright/test';
import type { Locator, Page } from '@playwright/test';
import {
  ADMIN_PASS,
  ADMIN_USER,
  BASE_URL,
  GDPR_EMAIL,
  GDPR_POST_TITLE,
  adminLogin,
  clearConsentCookies,
  clearLoginAttempts,
  clearRateLimiters,
  dbCount,
  dbScalar,
  noValidate,
  seedGdprFixtures,
} from './gdpr-fixtures';

const REQUESTS_URL: string = `${BASE_URL}/admin/index.php?load=privacy&p=data-requests`;

/**
 * Locate the table row that contains the given email address.
 *
 * @param page Playwright page holding the requests table.
 * @param email Email address identifying the row.
 * @returns Locator for the matching table row.
 */
function rowForEmail(page: Page, email: string): Locator {
  return page.locator('#scriptlog-table tbody tr').filter({ hasText: email });
}

test.describe('GDPR admin data requests', () => {
  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    seedGdprFixtures();
  });

  test.beforeEach(async ({ context }): Promise<void> => {
    await clearConsentCookies(context);
  });

  test('table lists all seeded requests with statuses', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(REQUESTS_URL);
    await expect(page.locator('#scriptlog-table tbody tr')).toHaveCount(6);
    await expect(rowForEmail(page, 'access@e2e.local')).toContainText(
      'Pending',
    );
    await expect(rowForEmail(page, GDPR_EMAIL)).toContainText('Processing');
    await expect(rowForEmail(page, 'old-done@e2e.local')).toContainText(
      'Completed',
    );
    await expect(rowForEmail(page, 'rejected@e2e.local')).toContainText(
      'Rejected',
    );
  });

  test('processing a pending request moves it to Processing', async ({
    page,
  }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(REQUESTS_URL);

    const row: Locator = rowForEmail(page, 'access@e2e.local');
    await row.locator('button', { hasText: 'Process' }).click();

    await expect(page.locator('.alert-success')).toContainText('updated');
    await expect(rowForEmail(page, 'access@e2e.local')).toContainText(
      'Processing',
    );
  });

  test('completing a non-erasure request succeeds without confirm_erasure', async ({
    page,
  }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(REQUESTS_URL);

    const row: Locator = rowForEmail(page, 'erasure@e2e.local');
    await row.locator('button', { hasText: 'Complete' }).click();

    await expect(page.locator('.alert-success')).toContainText('updated');
    await expect(rowForEmail(page, 'erasure@e2e.local')).toContainText(
      'Completed',
    );
  });

  test('rejecting a request moves it to Rejected', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(REQUESTS_URL);

    const row: Locator = rowForEmail(page, 'rectify@e2e.local');
    await row.locator('button', { hasText: 'Reject' }).click();

    await expect(page.locator('.alert-success')).toContainText('updated');
    await expect(rowForEmail(page, 'rectify@e2e.local')).toContainText(
      'Rejected',
    );
  });

  test('completing an erasure without the confirm guard is refused', async ({
    page,
  }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(REQUESTS_URL);

    const row: Locator = rowForEmail(page, GDPR_EMAIL);
    const completeForm: Locator = row.locator('form', {
      has: page.locator('input[name="action"][value="complete"]'),
    });
    await noValidate(completeForm);
    await completeForm.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-danger')).toContainText(
      'confirm the irreversible erasure',
    );
    // The subject still exists.
    expect(
      dbCount(`SELECT COUNT(*) FROM tbl_users WHERE user_login='gdpr_user'`),
    ).toBe(1);
  });

  test('confirmed erasure removes the account, anonymizes comments, reassigns posts', async ({
    page,
  }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(REQUESTS_URL);

    const row: Locator = rowForEmail(page, GDPR_EMAIL);
    const completeForm: Locator = row.locator('form', {
      has: page.locator('input[name="action"][value="complete"]'),
    });
    await completeForm.locator('input[name="confirm_erasure"]').check();
    await completeForm.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-success')).toContainText('updated');

    // Account gone.
    expect(
      dbCount(`SELECT COUNT(*) FROM tbl_users WHERE user_login='gdpr_user'`),
    ).toBe(0);
    // Comment anonymized.
    expect(
      dbCount(
        `SELECT COUNT(*) FROM tbl_comments WHERE comment_author_email='${GDPR_EMAIL}'`,
      ),
    ).toBe(0);
    expect(
      dbCount(
        `SELECT COUNT(*) FROM tbl_comments WHERE comment_author_name='Deleted User'`,
      ),
    ).toBe(1);
    // Post reassigned to fallback author 767.
    expect(
      dbScalar(
        `SELECT post_author FROM tbl_posts WHERE post_title='${GDPR_POST_TITLE}'`,
      ),
    ).toBe('767');
    // Request now completed.
    await expect(rowForEmail(page, GDPR_EMAIL)).toContainText('Completed');
  });
});
