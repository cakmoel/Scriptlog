// tb-security.spec.ts
// Security-facing output checks on TastyBites: the stored-XSS sample post
// renders its title escaped (no injected <img> element), search forms carry
// a hidden honeypot field (search_verify), and error paths never leak stack
// traces. The invalid download UUID returns a bare 404 at the Dispatcher
// (runtime finding R9), which is asserted as behavior only (finding G5).
// Honeypot rejection (search_verify populated) returns HTTP 403.
import { test, expect } from '@playwright/test';
import { BASE_URL, seedTastybitesFixtures, TB_PROTECTED_SECRET } from './tastybites-fixtures';
import { TB } from './tb-selectors';

const XSS_POST_ID = 159;

test.describe('tastybites security surfaces', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });

  test('stored XSS sample title renders escaped on the home listing', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(TB.titleImgXss)).toHaveCount(0);
    // The escaped sample must appear as text inside a card title anchor.
    const titleText = await page
      .locator('.card-title a', { hasText: 'XSS E2E' })
      .first()
      .innerText();
    expect(titleText).toContain('XSS E2E');
    // innerText decodes the escaped entities, so the literal "<img" string is
    // expected here; the real escape guarantee is the zero-element assertion
    // above (no <img> node may exist inside the title).
  });

  test('stored XSS sample post single view renders escaped', async ({ page }) => {
    // Fixed (plan runtime finding R4): /?p=<XSS sample> returned a 500 because
    // its NULL post_tags hit strtolower(null) in TagModel::getLinkTag() - a
    // deprecation that strict error handlers escalate. Any post with NULL tags
    // crashed its single view. The view now renders (200) with the hostile
    // title fully escaped.
    const response = await page.goto(`${BASE_URL}/?p=${XSS_POST_ID}`);
    expect(response?.status()).toBe(200);
    await expect(page.locator(TB.titleImgXss)).toHaveCount(0);
    await expect(page.locator(`${TB.articleRole} h1`).first()).toContainText('XSS E2E');
  });

test('search forms carry a hidden honeypot field', async ({ page }) => {
     await page.goto(`${BASE_URL}/?p=11`);
     expect(await page.locator(TB.searchVerify).inputValue()).toBe('');
     expect(await page.locator(`${TB.commentForm} input[name="csrf"]`).inputValue()).not.toBe('');
   });

   test('honeypot rejection: search_verify populated returns 403', async ({ page }) => {
     const response = await page.goto(`${BASE_URL}/search?q=test&search_verify=bot`);
     expect(response?.status()).toBe(403);
   });

  test('unknown post and broken download links show no stack traces', async ({ page }) => {
    for (const path of [`/?p=999999`, '/download/00000000-0000-0000-0000-000000000000']) {
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
    // Fixed (plan runtime finding R2): listing queries now filter
    // post_visibility = 'public' (PostModel, TopicModel, ArchivesModel,
    // TagModel, SearchFinder), so the protected post never reaches the home
    // latest-posts listing. Single-post view still enforces its own gate
    // (covered in tb-protected.spec.ts).
    await page.goto(`${BASE_URL}/`);
    const body = await page.content();
    expect(body).not.toContain(TB_PROTECTED_SECRET);
  });
});