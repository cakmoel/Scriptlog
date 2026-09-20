// gdpr-privacy-page.spec.ts
// Phase 3 of specs/gdpr.md: public privacy policy page.
// The e2e DB uses permalink_setting rewrite=no, so the canonical URL is
// `?privacy` (what the banner links to via get_privacy_policy_url()).
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  clearLoginAttempts,
  clearRateLimiters,
  seedGdprFixtures,
} from './gdpr-fixtures';
import { BLOG } from './blog-selectors';

test.describe('GDPR privacy policy page', () => {
  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    seedGdprFixtures();
  });

  test('?privacy renders the localized default policy content', async ({
    page,
  }) => {
    const response = await page.goto(`${BASE_URL}/?privacy`);
    expect(response?.status()).toBe(200);
    await expect(page.locator(BLOG.privacyCard)).toBeVisible();
    await expect(page.locator(BLOG.privacyHeaderTitle)).toHaveText(
      'E2E Privacy Policy',
    );
    await expect(page.locator(BLOG.privacyBody)).toContainText(
      'We collect minimal data for the e2e suite.',
    );
  });

  test('the banner learn-more target resolves to a page with the policy title', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/`);
    const banner = page.locator(BLOG.cookieBannerById);
    await expect(banner).toBeVisible();
    const url: string | null = await banner.getAttribute('data-privacy-url');
    expect(url).toBe(`${BASE_URL}?privacy`);
    await page.goto(url as string);
    await expect(page.locator(BLOG.privacyHeaderTitle)).toHaveText(
      'E2E Privacy Policy',
    );
  });

  test('privacy page shows a back-to-home link', async ({ page }) => {
    await page.goto(`${BASE_URL}/?privacy`);
    await expect(page.locator(BLOG.privacyBackBtn)).toBeVisible();
  });
});
