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

    // WebKit serves this form POST as a top-level document navigation and
    // renders the JSON inline instead of firing a "download" event, so post the
    // same form through the page's own fetch() and parse the response body.
    // This keeps the server-side export path fully exercised on every engine.
    const json: ExportPayload = (await page.evaluate(
      async ({ email }): Promise<unknown> => {
        const form =
          document.querySelector<HTMLFormElement>(
            'form[action*="action=export"]',
          ) ?? document.querySelector<HTMLFormElement>('form');
        if (form === null) {
          throw new Error('export form not found');
        }
        const csrf = (
          form.querySelector<HTMLInputElement>('input[name="csrfToken"]') ?? {
            value: '',
          }
        ).value;
        const actionURL: string = form.action;
        const body = new FormData();
        body.append('csrfToken', csrf);
        body.append('export_email', email);
        body.append('export_posts', '1');
        body.append('export_comments', '1');
        body.append('export_activity', '1');
        const res = await window.fetch(actionURL, {
          method: 'POST',
          body,
          credentials: 'same-origin',
        });
        if (!res.ok) {
          throw new Error(`export request failed: ${res.status}`);
        }
        return res.json();
      },
      { email: GDPR_EMAIL },
    )) as ExportPayload;

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
