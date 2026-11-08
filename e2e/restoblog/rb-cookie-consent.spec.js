// @ts-check
// rb-cookie-consent.spec.js
// Cookie consent banner on RestoBlog. Unlike the TastyBites suite (which posts
// to /api/v1/gdpr/consent), this theme persists consent through localStorage
// key "epicurean_cookie_consent" in main.js: the banner is shown on first
// visit, hidden after Accept/Reject, and stays hidden afterwards. No GDPR rows
// are written, so no DB cleanup is needed here.
import { test, expect } from '@playwright/test';
import { BASE_URL, seedRestoblogFixtures } from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

test.describe('restoblog cookie consent', () => {
  test.beforeAll(() => {
    seedRestoblogFixtures();
  });

  test('banner appears for fresh contexts and accept persists consent', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(RB.cookieBanner)).toBeVisible();

    await page.locator(RB.cookieAccept).click();
    await expect(page.locator(RB.cookieBanner)).toBeHidden();
    const stored = await page.evaluate(() => localStorage.getItem('epicurean_cookie_consent'));
    expect(stored).toBe('accepted');

    // Banner stays gone on the next visit in the same context.
    await page.goto(`${BASE_URL}/?blog`);
    await expect(page.locator(RB.cookieBanner)).toBeHidden();
    await context.close();
  });

  test('reject hides the banner and records a rejected choice', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(RB.cookieBanner)).toBeVisible();
    await page.locator(RB.cookieReject).click();
    await expect(page.locator(RB.cookieBanner)).toBeHidden();
    const stored = await page.evaluate(() => localStorage.getItem('epicurean_cookie_consent'));
    expect(stored).toBe('rejected');
    await context.close();
  });

  test('learn more navigates to the privacy policy', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(RB.cookieLearnMore)).toBeVisible();
    // The control is an <a> whose href is get_privacy_policy_url() (?privacy in
    // query-string mode), so clicking performs a normal navigation.
    await page.locator(RB.cookieLearnMore).click();
    await page.waitForURL(/privacy/i);
    await expect(page.locator(RB.privacyBody)).toBeVisible();
    await context.close();
  });
});