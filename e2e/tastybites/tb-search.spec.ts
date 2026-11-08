// tb-search.spec.ts
// Search surfaces on TastyBites: the sidebar widget and hero search are
// AJAX-only against GET /api/v1/search (plan finding G1 - no form-GET fallback),
// while the search page renders server-side result/no-result/empty states.
// Honeypot (search_verify) and rate limiting are verified separately in
// tb-security.spec.ts (403 on honeypot) and the rate limit test below.
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  seedTastybitesFixtures,
} from './tastybites-fixtures';
import { TB } from './tb-selectors';

test.describe('tastybites search', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });

  test('sidebar widget queries the API and renders results', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    const apiPromise = page.waitForResponse(
      (r) => r.url().includes('/api/v1/search') && r.request().method() === 'GET',
    );
    await page.locator(TB.sidebarSearchInput).fill('TastyBites');
    const response = await apiPromise;
    expect(response.status()).toBe(200);
    await expect(page.locator(TB.sidebarResultItem).first()).toBeVisible();
  });

  test('hero search returns up to five quick results', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await page.locator(TB.heroSearchInput).fill('Test');
    await expect(page.locator(`${TB.heroSearchResults} ${TB.heroSearchItem}`).first()).toBeVisible();
    const count = await page.locator(`${TB.heroSearchResults} ${TB.heroSearchItem}`).count();
    expect(count).toBeGreaterThan(0);
    expect(count).toBeLessThanOrEqual(5);
  });

  test('search page lists matching posts for a keyword', async ({ page }) => {
    await page.goto(`${BASE_URL}/search?q=TastyBites`);
    // The heading contains the translated search.title key which includes the keyword.
    // Assert the archive-header h1 is visible and contains the keyword.
    await expect(page.locator(TB.searchPageHeading)).toBeVisible();
    const headingText = await page.locator(TB.searchPageHeading).innerText();
    expect(headingText.toLowerCase()).toContain('tastybites');
    const resultLinks = page.locator(`${TB.searchPageResults} a[href]`);
    await expect(resultLinks.first()).toBeVisible();
    const href = await resultLinks.first().getAttribute('href');
    // Results may mix blog posts (?p=) and pages (?pg=) depending on relevance.
    expect(href).toMatch(/\?(p|pg)=/);
  });

  test('search page shows the no-results guidance alert', async ({ page }) => {
    await page.goto(`${BASE_URL}/search?q=zzzqqqxxxnohit`);
    await expect(page.locator(TB.searchPageAlert).first()).toBeVisible();
  });

  test('search page without a keyword returns the styled 404 page', async ({ page }) => {
    // Dispatcher validates /search without a q parameter as non-existent
    // content, so the template's enter-keyword branch is unreachable by URL.
    const response = await page.goto(`${BASE_URL}/search`);
    expect(response?.status()).toBe(404);
    await expect(page.locator(TB.notFoundDisplay)).toBeVisible();
  });

  test('search page renders a refine-search input field', async ({ page }) => {
    await page.goto(`${BASE_URL}/search?q=Test`);
    const searchInput = page.locator('#search-page-input');
    await expect(searchInput).toBeVisible();
    const value = await searchInput.inputValue();
    expect(value).toBe('Test');
  });

test('sidebar search carries a hidden honeypot field', async ({ page }) => {
     await page.goto(`${BASE_URL}/?blog`);
     const verify = await page.locator(TB.searchVerify).inputValue();
     expect(verify).toBe('');
   });

   test('search rate limit returns 429 after repeated requests', async ({ page }, testInfo) => {
     test.skip(testInfo.project.name === 'webkit', 'rate limit file-backed counter is not shared across browsers in this dev env');
     let saw429 = false;
     for (let i = 0; i < 35; i++) {
       const response = await page.goto(`${BASE_URL}/search?q=test${i}`);
       if (response?.status() === 429) {
         saw429 = true;
         break;
       }
     }
     expect(saw429).toBe(true);
   });
 });