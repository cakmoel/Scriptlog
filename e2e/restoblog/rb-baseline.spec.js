// @ts-check
// rb-baseline.spec.js
// RestoBlog smoke suite: single shell instance, valid routes render their key
// containers, theme assets load without 404s, and the 404 page contract holds.
// Console errors are captured and attached (soft) - main.js is intentionally
// minimal (nav drawer + localStorage cookie consent) so meaningful errors are
// surfaced without breaking the run.
import { test, expect } from '@playwright/test';
import { BASE_URL, seedRestoblogFixtures } from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

test.describe('restoblog baseline', () => {
  test.beforeAll(() => {
    seedRestoblogFixtures();
  });

  test('shell renders header, main, footer and skip link exactly once', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });
    page.on('pageerror', (err) => consoleErrors.push(String(err)));

    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(RB.headerNav)).toHaveCount(1);
    await expect(page.locator(RB.main)).toHaveCount(1);
    await expect(page.locator(RB.footer)).toHaveCount(1);
    await expect(page.locator(RB.skipLink)).toHaveCount(1);
    await expect(page.locator(RB.skipLink)).toHaveAttribute('href', '#main-content');
    await expect(page.locator(RB.brandTitle).first()).not.toHaveText('');
    // Brand sitename links back to the application home.
    await expect(page.locator(RB.brand)).toHaveCount(1);
    const brandHref = await page.locator(RB.brand).getAttribute('href');
    expect(brandHref).toBeTruthy();

    test.info().attach('console-errors-home.txt', {
      body: consoleErrors.join('\n') || '(none)',
      contentType: 'text/plain',
    });
  });

  const VALID_ROUTES = [
    { url: '/', selector: 'section.hero', name: 'home' },
    { url: '/?blog', selector: '.archive-list', name: 'blog listing' },
    { url: '/?p=11', selector: 'article.post-detail', name: 'single post' },
    { url: '/?cat=200', selector: '.archive-list', name: 'category' },
    { url: '/?tag=rb-e2e', selector: '.archive-list', name: 'tag' },
    { url: '/?a=202608', selector: '.archive-list', name: 'monthly archive' },
    // Query-string mode fact: /privacy routes through the ?privacy key.
    { url: '/?privacy', selector: '.privacy-body', name: 'privacy policy' },
    { url: '/search?q=RestoBlog', selector: '.page-header h1', name: 'search page' },
  ];

  for (const route of VALID_ROUTES) {
    test(`route ${route.url} renders ${route.name}`, async ({ page }) => {
      const response = await page.goto(`${BASE_URL}${route.url}`);
      expect(response?.status()).toBe(200);
      await expect(page.locator(route.selector).first()).toBeVisible();
    });
  }

  test('theme assets resolve without client errors across key pages', async ({ page }) => {
    /** @type {string[]} */
    const failedAssets = [];
    page.on('response', (response) => {
      const url = response.url();
      if (url.includes('/themes/restoblog/assets/') && response.status() >= 400) {
        failedAssets.push(`${response.status()} ${url}`);
      }
    });
    for (const path of ['/', '/?blog', '/?p=11', '/?privacy']) {
      await page.goto(`${BASE_URL}${path}`);
    }
    expect(failedAssets).toEqual([]);
  });

  test('unknown post id returns the styled 404 page without stack traces', async ({ page }) => {
    const response = await page.goto(`${BASE_URL}/?p=999999`);
    expect(response?.status()).toBe(404);
    await expect(page.locator(RB.notFoundContainer)).toBeVisible();
    await expect(page.locator(RB.notFoundCode)).toHaveText('404');
    await expect(page.locator(RB.backHomeButton)).toBeVisible();
    const errorText = (await page.content()).toUpperCase();
    expect(errorText).not.toContain('FATAL ERROR');
    expect(errorText).not.toContain('STACK TRACE');
  });
});