// @ts-check
// rb-search.spec.js
// Search surfaces on RestoBlog: the sidebar widget is a plain GET form that
// submits ?q= to the app root, with a live autocomplete dropdown (search.js)
// that fetches /api/v1/search while typing. The search page renders
// server-side result/no-result/empty states. Rate-limit UI is not asserted.
import { test, expect } from '@playwright/test';
import { BASE_URL, seedRestoblogFixtures } from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

test.describe('restoblog search', () => {
  test.beforeAll(() => {
    seedRestoblogFixtures();
  });

  test('sidebar search form submits ?q= and lands on the search page', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    await expect(page.locator(RB.sidebarSearchForm)).toBeVisible();
    const action = await page.locator(RB.sidebarSearchForm).getAttribute('action');
    expect(action).toBeTruthy();

    await page.locator(RB.sidebarSearchInput).fill('RestoBlog');
    await page.locator('#ajax-search-form button[type="submit"]').click();
    await page.waitForURL(/q=RestoBlog/);
    await expect(page.locator(RB.pageHeader)).toBeVisible();
    const cards = page.locator(RB.searchResultCard);
    const count = await cards.count();
    expect(count).toBeGreaterThan(0);
  });

  test('header nav search form submits ?q= and lands on the search page', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    await expect(page.locator(RB.navSearchForm)).toBeVisible();
    const action = await page.locator(RB.navSearchForm).getAttribute('action');
    expect(action).toBeTruthy();

    await page.locator(RB.navSearchInput).fill('RestoBlog');
    // Distinct ids from the sidebar widget (#ajax-search-form / #search-keyword).
    await expect(page.locator('#ajax-search-form')).toHaveCount(1);
    await expect(page.locator('#search-keyword')).toHaveCount(1);

    await page.locator('.nav-search-form button[type="submit"]').click();
    await page.waitForURL(/q=RestoBlog/);
    await expect(page.locator(RB.pageHeader)).toBeVisible();
    const cards = page.locator(RB.searchResultCard);
    const count = await cards.count();
    expect(count).toBeGreaterThan(0);
  });

  test('search page lists matching posts with post links', async ({ page }) => {
    await page.goto(`${BASE_URL}/search?q=RestoBlog`);
    await expect(page.locator(RB.pageHeader)).toContainText('Search');
    const resultLinks = page.locator(RB.searchResultLink).first();
    await expect(resultLinks).toBeVisible();
    const href = await resultLinks.getAttribute('href');
    expect(href).toContain('p=');
  });

  test('search page shows the no-results guidance card', async ({ page }) => {
    await page.goto(`${BASE_URL}/search?q=zzzqqqxxxnohit`);
    await expect(page.locator(RB.searchNoResultsCard).first()).toBeVisible();
  });

  test('search page without a keyword returns the styled 404 page', async ({ page }) => {
    // Dispatcher validates /search without a q parameter as non-existent
    // content, so the template's enter-keyword branch is unreachable by URL.
    const response = await page.goto(`${BASE_URL}/search`);
    expect(response?.status()).toBe(404);
    await expect(page.locator(RB.notFoundContainer)).toBeVisible();
  });

test('sidebar search carries a hidden honeypot field', async ({ page }) => {
      await page.goto(`${BASE_URL}/?blog`);
      const verify = await page.locator(RB.searchVerify).inputValue();
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

   test('sidebar search shows a live autocomplete suggestions dropdown', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    const input = page.locator(RB.sidebarSearchInput);

    // Typing at least 2 chars debounces into an /api/v1/search request.
    const apiResponse = page.waitForResponse(
      (r) => r.url().includes('/api/v1/search') && new URL(r.url()).searchParams.has('q'),
    );
    await input.fill('RestoBlog');
    const response = await apiResponse;
    expect(response.status()).toBe(200);

    const box = page.locator('#search-results');
    await expect(box).toHaveClass(/active/);
    // Seeded public posts share the "RestoBlog" token, so at least one
    // suggestion linking to a ?p= post must appear.
    const suggestionLink = box.locator('a[href*="p="]').first();
    await expect(suggestionLink).toBeVisible();
    await expect(box).toContainText('RestoBlog');
  });
});