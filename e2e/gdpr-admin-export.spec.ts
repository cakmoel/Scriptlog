// gdpr-admin-export.spec.ts
// Phase 4 of specs/gdpr.md: admin "Export Your Data" flow.
// A valid submit triggers a JSON download (Content-Disposition attachment);
// invalid input surfaces a client-side error alert and never downloads.
import { test, expect } from '@playwright/test';
import {
  ADMIN_USER,
  ADMIN_PASS,
  BASE_URL,
  GDPR_EMAIL,
  GDPR_POST_TITLE,
  adminLogin,
  clearConsentCookies,
  clearLoginAttempts,
  clearRateLimiters,
  noValidate,
  readJsonDownload,
  seedGdprFixtures,
  type ExportPayload,
} from './gdpr-fixtures';

const EXPORT_URL: string = `${BASE_URL}/admin/index.php?load=privacy&p=data-export`;

test.describe('GDPR admin data export', () => {
  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    seedGdprFixtures();
  });

  test.beforeEach(async ({ context }): Promise<void> => {
    await clearConsentCookies(context);
  });

  test('admin can export a subject JSON payload', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(EXPORT_URL);
    await expect(page.locator('form')).toBeVisible();

    await page.locator('#email').fill(GDPR_EMAIL);
    await page.locator('input[name="export_posts"]').check();
    await page.locator('input[name="export_comments"]').check();

    const json: ExportPayload = await readJsonDownload(page, () =>
      page.locator('button[type="submit"]').click(),
    );

    expect(json).toHaveProperty('email', GDPR_EMAIL);
    expect(json).toHaveProperty('profile');
    expect(json.profile as Record<string, unknown>).toHaveProperty(
      'user_login',
      'gdpr_user',
    );
    expect(json).toHaveProperty('posts');
    const posts = (json.posts ?? []) as Array<Record<string, unknown>>;
    const titles: unknown[] = posts.map(
      (p: Record<string, unknown>): unknown => p['post_title'],
    );
    expect(titles).toContain(GDPR_POST_TITLE);
    const comments = (json.comments ?? []) as Array<Record<string, unknown>>;
    const commentAuthors: unknown[] = comments.map(
      (c: Record<string, unknown>): unknown => c['comment_author_email'],
    );
    expect(commentAuthors).toContain(GDPR_EMAIL);
  });

  test('exporting requires a valid email address', async ({ page }) => {
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(EXPORT_URL);
    await expect(page.locator('form')).toBeVisible();

    await noValidate(page.locator('form'));
    await page.locator('#email').fill('not-an-email');
    await page.locator('button[type="submit"]').click();

    await expect(page.locator('.alert-danger')).toBeVisible();
    await expect(page.locator('.alert-danger')).toContainText('valid email');
  });

  test('unauthorised users cannot reach the export form', async ({ page }) => {
    await page.goto(EXPORT_URL);
    await expect(page).toHaveURL(/admin\/login\.php/);
  });
});
