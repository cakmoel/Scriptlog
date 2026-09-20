// admin-locale-dropdown.spec.ts
// e2e verification for the DB-synchronized admin locale dropdowns
// (plan/LOCALE_DROPDOWN_SYNC_PLAN.md).
//
// The post/page editors' "Language" select must be driven by the active
// languages in tbl_languages (native names), with the legacy hardcoded
// 20-locale lists (de, it, ja, ...) gone.
//
// NOTE: admin *write* forms (POST) currently fail a pre-existing CSRF check in
// this dev environment (see security-remediation.spec.ts), so this spec only
// exercises GET rendering of the editor forms.
import { test, expect } from '@playwright/test';
import {
  ADMIN_PASS,
  ADMIN_USER,
  BASE_URL,
  adminLogin,
  clearLoginAttempts,
  clearRateLimiters,
  dbRows,
} from './gdpr-fixtures';

const NEW_POST_URL: string = `${BASE_URL}/admin/index.php?load=posts&action=newPost`;
const NEW_PAGE_URL: string = `${BASE_URL}/admin/index.php?load=pages&action=newPage`;

test.describe.serial('admin locale dropdown', () => {
  let activeLanguages: Array<Record<string, string>>;

  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();

    activeLanguages = dbRows(
      'SELECT lang_code, lang_native FROM tbl_languages WHERE lang_is_active = 1 ORDER BY lang_sort',
    );
    expect(activeLanguages.length).toBeGreaterThanOrEqual(7);
  });

  test.afterAll((): void => {
    clearRateLimiters();
  });

  test('post editor language dropdown is synchronized with tbl_languages', async ({
    page,
  }, testInfo) => {
    test.skip(
      testInfo.project.name === 'webkit',
      'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
    );

    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(NEW_POST_URL);

    const localeSelect = page.locator('#post_locale');
    await expect(localeSelect).toBeVisible();
    await expect(page.locator('label[for="post_locale"]')).toBeVisible();

    const options = localeSelect.locator('option');
    await expect(options).toHaveCount(activeLanguages.length);

    for (const lang of activeLanguages) {
      await expect(
        localeSelect.locator(`option[value="${lang.lang_code}"]`),
      ).toHaveText(lang.lang_native);
    }

    // Unsupported legacy locales must be gone from the hardcoded era.
    await expect(localeSelect.locator('option[value="de"]')).toHaveCount(0);
    await expect(localeSelect.locator('option[value="ja"]')).toHaveCount(0);
    await expect(localeSelect.locator('option[value="ko"]')).toHaveCount(0);
  });

  test('page editor language dropdown is synchronized with tbl_languages', async ({
    page,
  }, testInfo) => {
    test.skip(
      testInfo.project.name === 'webkit',
      'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
    );

    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    await page.goto(NEW_PAGE_URL);

    const localeSelect = page.locator('#post_locale');
    await expect(localeSelect).toBeVisible();
    await expect(page.locator('label[for="post_locale"]')).toBeVisible();

    const options = localeSelect.locator('option');
    await expect(options).toHaveCount(activeLanguages.length);

    for (const lang of activeLanguages) {
      await expect(
        localeSelect.locator(`option[value="${lang.lang_code}"]`),
      ).toHaveText(lang.lang_native);
    }

    await expect(localeSelect.locator('option[value="hi"]')).toHaveCount(0);
  });
});