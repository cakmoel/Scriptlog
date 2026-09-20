// gdpr-policy-editor.spec.ts
// Phase 8 of specs/gdpr.md: privacy policy editor CRUD. Create, set default,
// edit, and delete reflect on the editor table and the public ?privacy page.
// The Set Default / Delete buttons open a native confirm() dialog.
import { test, expect } from '@playwright/test';
import type { Dialog, Page } from '@playwright/test';
import {
  ADMIN_PASS,
  ADMIN_USER,
  BASE_URL,
  adminLogin,
  clearConsentCookies,
  clearLoginAttempts,
  clearRateLimiters,
  dbCount,
  dbScalar,
  noValidate,
  seedGdprFixtures,
} from './gdpr-fixtures';
import { BLOG } from './blog-selectors';

const EDITOR_URL: string = `${BASE_URL}/admin/index.php?load=privacy-policy`;

/**
 * Attach a native-dialog auto-accept handler for the current page.
 *
 * @param page Playwright page that will open a confirm dialog.
 */
function acceptDialogs(page: Page): void {
  page.once('dialog', (dialog: Dialog): void => {
    void dialog.accept();
  });
}

test.describe('GDPR privacy policy editor', () => {
  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    seedGdprFixtures();
  });

  test.beforeEach(async ({ context }): Promise<void> => {
    await clearConsentCookies(context);
    clearRateLimiters();
    seedGdprFixtures();
  });

  test('editor lists the seeded policies with the default badge', async ({
    page,
  }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(EDITOR_URL);
    await expect(page.locator('#policyTable tbody tr')).toHaveCount(2);
    await expect(page.locator('#policyTable tbody')).toContainText(
      'E2E Privacy Policy',
    );
    await expect(page.locator('#policyTable tbody')).toContainText('EN');
    await expect(page.locator('#policyTable tbody')).toContainText('Default');
  });

  test('creating a policy requires title and content', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(`${EDITOR_URL}&action=new-policy`);
    await expect(page.locator('#policyForm')).toBeVisible();

    await noValidate(page.locator('#policyForm'));
    await page.locator('#policy_title').fill('');
    await page.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-danger')).toContainText(
      'Policy title is required.',
    );
  });

  test('creating a policy persists it and reflects on the public page', async ({
    page,
  }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(`${EDITOR_URL}&action=new-policy`);

    await page.locator('#locale').selectOption('es');
    await page.locator('#policy_title').fill('Spanish E2E Policy');
    await page
      .locator('#policy_content')
      .fill('<h2>Nuestra política</h2><p>Datos mínimos.</p>');
    await page.locator('button[type="submit"]').click();

    await expect(page).toHaveURL(EDITOR_URL);
    await expect(page.locator('.alert-success')).toContainText(
      'created successfully',
    );
    await expect(page.locator('#policyTable tbody')).toContainText(
      'Spanish E2E Policy',
    );

    const esRow = page
      .locator('#policyTable tbody tr')
      .filter({ hasText: 'ES' });
    await esRow.locator('a', { hasText: 'Edit' }).click();
    await page.locator('#policy_title').fill('Spanish E2E Policy v2');
    await page.locator('button[type="submit"]').click();
    await expect(page.locator('.alert-success')).toContainText(
      'updated successfully',
    );
    await expect(page.locator('#policyTable tbody')).toContainText(
      'Spanish E2E Policy v2',
    );

    await page.goto(`${BASE_URL}/?privacy`);
    await expect(page.locator(BLOG.privacyHeaderTitle)).toHaveText(
      'E2E Privacy Policy',
    );
  });

  test('setting a policy as default updates the badge', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(EDITOR_URL);

    const frRow = page
      .locator('#policyTable tbody tr')
      .filter({ hasText: 'FR' });
    acceptDialogs(page);
    await frRow.locator('button', { hasText: 'Set Default' }).click();

    await expect(page.locator('.alert-success')).toContainText(
      'Default policy set',
    );
    expect(
      dbScalar(`SELECT is_default FROM tbl_privacy_policies WHERE locale='fr'`),
    ).toBe('1');
    expect(
      dbScalar(`SELECT is_default FROM tbl_privacy_policies WHERE locale='en'`),
    ).toBe('0');
  });

  test('deleting a policy removes it from the table', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(EDITOR_URL);

    const frRow = page
      .locator('#policyTable tbody tr')
      .filter({ hasText: 'FR' });
    acceptDialogs(page);
    await frRow.locator('button', { hasText: 'Delete' }).click();

    await expect(page.locator('.alert-success')).toContainText(
      'deleted successfully',
    );
    await expect(page.locator('#policyTable tbody tr')).toHaveCount(1);
    expect(
      dbCount(`SELECT COUNT(*) FROM tbl_privacy_policies WHERE locale='fr'`),
    ).toBe(0);
  });
});
