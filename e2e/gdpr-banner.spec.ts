// gdpr-banner.spec.ts
// Phase 1 of specs/gdpr.md: the cookie-consent banner.
// Deterministic: clears consent cookies and login-attempt rows in beforeAll,
// then re-seeds the shared fixture DB so the serial projects never race.
import { test, expect } from '@playwright/test';
import type { Cookie } from '@playwright/test';
import {
  BASE_URL,
  clearLoginAttempts,
  clearRateLimiters,
  seedGdprFixtures,
} from './gdpr-fixtures';
import { BLOG } from './blog-selectors';

test.describe('GDPR cookie consent banner', () => {
  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    seedGdprFixtures();
  });

  test('banner appears on first visit without consent', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(BLOG.cookieBanner)).toBeVisible();
    const version: string | null = await page
      .locator(BLOG.cookieBanner)
      .getAttribute('data-consent-version');
    expect(version).not.toBeNull();
  });

  test('accepting stores cookies and hides the banner', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await page.locator(BLOG.cookieAccept).click();
    await expect(page.locator(BLOG.cookieBanner)).toBeHidden();

    const cookies: Cookie[] = await page.context().cookies();
    const consent: Cookie | undefined = cookies.find(
      (c: Cookie): boolean => c.name === 'cookie_consent',
    );
    const version: Cookie | undefined = cookies.find(
      (c: Cookie): boolean => c.name === 'cookie_consent_version',
    );
    expect(consent).toBeDefined();
    expect(consent?.value).toBe('accepted');
    expect(version).toBeDefined();

    const rows: string = await page.evaluate(async (): Promise<string> => {
      const res: Response = await fetch('/api/v1/gdpr/consent');
      const json: unknown = await res.json();
      return JSON.stringify(json);
    });
    expect(rows).toContain('"consent_given":true');
    expect(rows).toContain('"consent_type":"cookie"');
  });

  test('rejecting stores rejection and hides the banner', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await page.locator(BLOG.cookieReject).click();
    await expect(page.locator(BLOG.cookieBanner)).toBeHidden();

    const cookies: Cookie[] = await page.context().cookies();
    const consent: Cookie | undefined = cookies.find(
      (c: Cookie): boolean => c.name === 'cookie_consent',
    );
    expect(consent).toBeDefined();
    expect(consent?.value).toBe('rejected');
  });

  test('banner is not shown once a valid consent cookie exists', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/`);
    await page.locator(BLOG.cookieAccept).click();
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(BLOG.cookieBanner)).toBeHidden();
  });

  test('learn more links to the privacy policy', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    const link = page.locator(BLOG.cookieLearnMore);
    await link.click();
    await expect(page).toHaveURL(/\?privacy|\/privacy/);
  });
});
