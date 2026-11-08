// @ts-check
// rb-security.spec.js
// Security-facing output checks on RestoBlog: the stored-XSS sample post
// renders its title escaped (no injected <img> element), search forms carry
// a hidden honeypot field (search_verify), CSRF tokens are present on
// state-changing surfaces, and error paths never leak stack traces.
import { test, expect } from '@playwright/test';
import { BASE_URL, RB_PROTECTED_SECRET, seedRestoblogFixtures } from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

const XSS_POST_ID = 159;

test.describe('restoblog security surfaces', () => {
  test.beforeAll(() => {
    seedRestoblogFixtures();
  });

  test('stored XSS sample title renders escaped on the blog listing', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    // No <img> element may exist inside a card title anywhere on the listing.
    await expect(page.locator('.archive-list .post-card h2 img')).toHaveCount(0);
    // If the XSS card is present, the hostile markup must appear as escaped text.
    const xssTitle = page
      .locator('.archive-list .post-card h2 a', { hasText: 'XSS E2E' })
      .first();
    if (await xssTitle.count()) {
      const titleText = await xssTitle.innerText();
      expect(titleText).toContain('XSS E2E');
    }
  });

  test('stored XSS sample post single view renders escaped', async ({ page }) => {
    const response = await page.goto(`${BASE_URL}/?p=${XSS_POST_ID}`);
    expect(response?.status()).toBe(200);
    await expect(page.locator(RB.titleImgXss)).toHaveCount(0);
    await expect(page.locator(RB.postTitle).first()).toContainText('XSS E2E');
  });

test('search forms carry a hidden honeypot field', async ({ page }) => {
      await page.goto(`${BASE_URL}/?p=11`);
      expect(await page.locator(RB.searchVerify).inputValue()).toBe('');
      expect(await page.locator(RB.commentCsrf).inputValue()).not.toBe('');
    });

    test('honeypot rejection: search_verify populated returns 403', async ({ page }) => {
      const response = await page.goto(`${BASE_URL}/search?q=test&search_verify=bot`);
      expect(response?.status()).toBe(403);
    });

   test('unknown post and broken download links show no stack traces', async ({ page }) => {
    for (const path of ['/?p=999999', '/download/00000000-0000-0000-0000-000000000000']) {
      const response = await page.goto(`${BASE_URL}${path}`);
      expect(response?.status()).toBeGreaterThanOrEqual(400);
      const body = (await page.content()).toUpperCase();
      expect(body).not.toContain('FATAL ERROR');
      expect(body).not.toContain('STACK TRACE');
      expect(body).not.toContain('WARNING:');
      expect(body).not.toContain('WHOOPS');
    }
  });

  test('protected post content is not present in listing payloads', async ({ page }) => {
    // Listing queries filter post_visibility = 'public', so the protected post
    // never reaches the home latest-posts or blog listings, and its plaintext
    // secret must never appear in the rendered payloads.
    for (const path of ['/', '/?blog']) {
      await page.goto(`${BASE_URL}${path}`);
      const body = await page.content();
      expect(body, `secret leaked on ${path}`).not.toContain(RB_PROTECTED_SECRET);
    }
  });
});