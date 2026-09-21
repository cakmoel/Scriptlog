// gdpr-audit-retention.spec.ts
// Phase 7 of specs/gdpr.md: audit log table + data retention settings + cleanup.
// Cleanup is guarded by confirm_cleanup and prunes rows older than the window.
import { test, expect } from '@playwright/test';
import {
  ADMIN_PASS,
  ADMIN_USER,
  BASE_URL,
  adminLogin,
  clearConsentCookies,
  clearLoginAttempts,
  clearRateLimiters,
  dbCount,
  noValidate,
  seedGdprFixtures,
  seedRetentionFixtures,
} from './gdpr-fixtures';

const AUDIT_URL: string = `${BASE_URL}/admin/index.php?load=privacy&p=audit-logs`;
const RETENTION_URL: string = `${BASE_URL}/admin/index.php?load=privacy&p=retention`;

test.describe('GDPR audit logs and retention', () => {
  // These tests share (re-seed) the same consent/log tables in beforeAll and
  // mid-file, so they must run in declaration order on one worker instead of
  // racing across fullyParallel workers.
  test.describe.configure({ mode: 'serial' });

  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    seedGdprFixtures();
  });

  test.beforeEach(async ({ context }): Promise<void> => {
    await clearConsentCookies(context);
  });

  test('audit logs page lists all records with action/email/details', async ({
    page,
  }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(AUDIT_URL);
    // 3 seeded rows are guaranteed present; other specs may append logs of
    // their own concurrently, so assert presence of the seeded markers rather
    // than an exact row count.
    await expect(page.locator('#scriptlog-table tbody')).toContainText(
      'Export',
    );
    await expect(page.locator('#scriptlog-table tbody')).toContainText(
      'Data access request created',
    );
    await expect(page.locator('#scriptlog-table tbody')).toContainText(
      'access@e2e.local',
    );
  });

  test('retention page shows saved windows', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(RETENTION_URL);
    await expect(page.locator('#consent_retention_days')).toHaveValue('365');
    await expect(page.locator('#privacy_log_retention_days')).toHaveValue(
      '365',
    );
  });

  test('saving retention windows persists the settings', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(RETENTION_URL);
    await page.locator('#consent_retention_days').fill('90');
    await page.locator('#privacy_log_retention_days').fill('120');
    await page.locator('button[name="save_retention"]').click();

    await expect(page.locator('.alert-success')).toContainText(
      'Retention windows updated.',
    );
    await expect(page.locator('#consent_retention_days')).toHaveValue('90');
    await expect(page.locator('#privacy_log_retention_days')).toHaveValue(
      '120',
    );
  });

  test('cleanup without the confirm guard is refused', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    seedRetentionFixtures();
    await page.goto(RETENTION_URL);

    const cleanupForm = page.locator('form', {
      has: page.locator('button[name="run_cleanup"]'),
    });
    await noValidate(cleanupForm);
    await cleanupForm.locator('button[name="run_cleanup"]').click();

    await expect(page.locator('.alert-danger')).toContainText(
      'confirm the retention cleanup',
    );
  });

  test('confirmed cleanup removes rows older than the retention window', async ({
    page,
  }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    seedRetentionFixtures();
    await page.goto(RETENTION_URL);

    // 2 old (400-day) rows + 1 recent (1-day) row are seeded for each table.
    expect(
      dbCount(
        'SELECT COUNT(*) FROM tbl_consents WHERE consent_date < NOW() - INTERVAL 30 DAY',
      ),
    ).toBe(2);
    expect(
      dbCount(
        'SELECT COUNT(*) FROM tbl_privacy_logs WHERE log_date < NOW() - INTERVAL 30 DAY',
      ),
    ).toBe(2);
    expect(
      dbCount(
        `SELECT COUNT(*) FROM tbl_privacy_logs WHERE log_details = 'New log'`,
      ),
    ).toBe(1);

    const cleanupForm = page.locator('form', {
      has: page.locator('button[name="run_cleanup"]'),
    });
    await cleanupForm.locator('input[name="confirm_cleanup"]').check();
    await cleanupForm.locator('button[name="run_cleanup"]').click();

    await expect(page.locator('.alert-success')).toContainText(
      'Cleanup complete',
    );
    // Old rows pruned by the retention window; the 1-day-old row survives.
    expect(
      dbCount(
        'SELECT COUNT(*) FROM tbl_consents WHERE consent_date < NOW() - INTERVAL 30 DAY',
      ),
    ).toBe(0);
    expect(
      dbCount(
        'SELECT COUNT(*) FROM tbl_privacy_logs WHERE log_date < NOW() - INTERVAL 30 DAY',
      ),
    ).toBe(0);
    expect(
      dbCount(
        `SELECT COUNT(*) FROM tbl_privacy_logs WHERE log_details = 'New log'`,
      ),
    ).toBe(1);
  });
});
