// tb-cookie-consent.spec.ts
// Cookie consent banner on TastyBites: appears for fresh contexts, Accept and
// Reject both POST to the GDPR consent API, set the cookie_consent cookie and
// keep the banner hidden afterwards. Consent rows are namespaced by the fixed
// e2e client IP and removed by the global teardown cleanup.
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  dbCount,
  seedTastybitesFixtures,
} from './tastybites-fixtures';
import { TB } from './tb-selectors';

test.describe('tastybites cookie consent', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });

  test('banner appears for fresh contexts and accept persists consent', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();

    const consentPost = page.waitForResponse(
      (r) => r.url().includes('/api/v1/gdpr/consent') && r.request().method() === 'POST',
    );
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(TB.cookieBanner)).toBeVisible();

    await page.locator(TB.cookieAccept).click();
    const response = await consentPost;
    expect(response.ok()).toBeTruthy();

    await expect(page.locator(TB.cookieBanner)).toBeHidden();
    const cookie = await context.cookies();
    expect(cookie.some((c) => c.name === 'cookie_consent' && c.value === 'accepted')).toBeTruthy();

    // Banner stays gone on the next visit in the same context.
    await page.goto(`${BASE_URL}/?blog`);
    await expect(page.locator(TB.cookieBanner)).toBeHidden();
    await context.close();
  });

  test('reject records a rejected consent row', async ({ browser }) => {
    const before = dbCount(
      "SELECT COUNT(*) FROM tbl_consents WHERE consent_status='rejected'",
    );
    const context = await browser.newContext();
    const page = await context.newPage();

    const consentPost = page.waitForResponse(
      (r) => r.url().includes('/api/v1/gdpr/consent') && r.request().method() === 'POST',
    );
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(TB.cookieBanner)).toBeVisible();
    await page.locator(TB.cookieReject).click();
    const response = await consentPost;
    expect(response.ok()).toBeTruthy();

    await expect(page.locator(TB.cookieBanner)).toBeHidden();
    expect(dbCount("SELECT COUNT(*) FROM tbl_consents WHERE consent_status='rejected'"))
      .toBeGreaterThan(before);
    await context.close();
  });

  test('learn more navigates to the privacy policy', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(`${BASE_URL}/`);
    // The control is a <button> that navigates via JS to the banner's
    // data-privacy-url (runtime finding R6: no href attribute; JS navigates).
    await expect(page.locator('#cookie-consent-banner')).toHaveAttribute('data-privacy-url', /privacy/i);
    await page.locator(TB.cookieLearnMore).click();
    await page.waitForURL(/privacy/i);
    await expect(page.locator('.privacy-card')).toBeVisible();
    await context.close();
  });
});