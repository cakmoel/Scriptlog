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
    await expect(page.locator('#scriptlog-table tbody tr')).toHaveCount(3);
    await expect(page.locator('#scriptlog-table tbody')).toContainText(
      'data_exported',
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
      has: page.locator('input[name="run_cleanup"]'),
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

    // 3 consents (2 old + 1 recent) and 3 logs (2 old + 1 recent) seeded.
    expect(dbCount('SELECT COUNT(*) FROM tbl_consents')).toBe(3);
    expect(dbCount('SELECT COUNT(*) FROM tbl_privacy_logs')).toBe(3);

    const cleanupForm = page.locator('form', {
      has: page.locator('input[name="run_cleanup"]'),
    });
    await cleanupForm.locator('input[name="confirm_cleanup"]').check();
    await cleanupForm.locator('button[name="run_cleanup"]').click();

    await expect(page.locator('.alert-success')).toContainText(
      'Cleanup complete',
    );
    // Only the recent (1-day-old) rows survive the default 365-day window.
    expect(dbCount('SELECT COUNT(*) FROM tbl_consents')).toBe(1);
    expect(dbCount('SELECT COUNT(*) FROM tbl_privacy_logs')).toBe(1);
  });
});
