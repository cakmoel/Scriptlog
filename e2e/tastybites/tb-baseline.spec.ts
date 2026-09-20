// tb-baseline.spec.ts
// TastyBites smoke suite: single shell instance, valid routes render their key
// containers, theme assets load without 404s, the 404 page contract holds, and
// CSS loads without async onload handlers (CSP-safe). Console errors are
// captured and attached (soft) because front.js carries known dead bindings
// (plan/TASTYBITES_E2E_TEST_PLAN.md finding G4).
import { test, expect } from '@playwright/test';
import { BASE_URL, tbPageId, seedTastybitesFixtures } from './tastybites-fixtures';
import { TB, type TastybitesRoute } from './tb-selectors';

test.describe('tastybites baseline', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });

  test('shell renders header, main and footer exactly once', async ({ page }) => {
    const consoleErrors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });
    page.on('pageerror', (err) => consoleErrors.push(String(err)));

    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(TB.navbar)).toHaveCount(1);
    await expect(page.locator(TB.main)).toHaveCount(1);
    await expect(page.locator(TB.footer)).toHaveCount(1);

    test.info().attach('console-errors-home.txt', {
      body: consoleErrors.join('\n') || '(none)',
      contentType: 'text/plain',
    });
  });

  const VALID_ROUTES: TastybitesRoute[] = [
    { url: '/', selector: '.latest-posts', name: 'home' },
    { url: '/?blog', selector: '.posts-listing', name: 'blog listing' },
    { url: '/?p=11', selector: '.post-single', name: 'single post' },
    { url: '/?cat=200', selector: '.posts-listing', name: 'category' },
    { url: '/?tag=tb-e2e', selector: '.posts-listing', name: 'tag' },
    { url: '/?a=202608', selector: '.posts-listing', name: 'monthly archive' },
    // Query-string mode fact (plan runtime finding R8): /privacy and /search are
    // only dispatched through their query keys; bare /archives 404s, so the
    // archives index is intentionally absent from this list.
    { url: '/?privacy', selector: '.privacy-card', name: 'privacy policy' },
    { url: '/search?q=Test', selector: '.archive-header', name: 'search page' },
  ];

  for (const route of VALID_ROUTES) {
    test(`route ${route.url} renders ${route.name}`, async ({ page }) => {
      const response = await page.goto(`${BASE_URL}${route.url}`);
      expect(response?.status()).toBe(200);
      await expect(page.locator(route.selector).first()).toBeVisible();
    });
  }

  test('static page route (?pg={id}) renders the page body', async ({ page }) => {
    // The pg route resolves pages by numeric ID (rewrite=no mode), so the
    // seeded page ID is looked up after seeding rather than a slug.
    const pageId = tbPageId();
    expect(pageId).toBeGreaterThan(0);
    const response = await page.goto(`${BASE_URL}/?pg=${pageId}`);
    expect(response?.status()).toBe(200);
    await expect(page.locator(TB.postSingle).first()).toBeVisible();
  });

  test('theme assets resolve without client errors across key pages', async ({ page }) => {
    const failedAssets: string[] = [];
    page.on('response', (response) => {
      const url = response.url();
      if (url.includes('/themes/tastybites/assets/') && response.status() >= 400) {
        failedAssets.push(`${response.status()} ${url}`);
      }
    });
    for (const path of ['/', '/?blog', '/?p=11']) {
      await page.goto(`${BASE_URL}${path}`);
    }
    expect(failedAssets).toEqual([]);
  });

  test('stylesheets load without async onload handlers (CSP-safe)', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    const asyncSheets = page.locator(TB.asyncStylesheet);
    await expect(asyncSheets).toHaveCount(0);
    // Verify key CSS files are loaded as plain <link rel="stylesheet">
    const cssLinks = page.locator('link[rel="stylesheet"][href*="/themes/tastybites/"]');
    const count = await cssLinks.count();
    expect(count).toBeGreaterThan(3);
  });

  test('footer renders site name, copyright and social links', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(TB.footer)).toBeVisible();
    await expect(page.locator(TB.copyright)).toBeVisible();
    await expect(page.locator(TB.socialLinks)).toBeVisible();
    const socialCount = await page.locator(`${TB.socialLinks} a`).count();
    expect(socialCount).toBeGreaterThanOrEqual(3);
  });

  test('theme.ini metadata matches directory name', async ({ page }) => {
    // Verify the theme renders the TastyBites brand in the navbar
    await page.goto(`${BASE_URL}/`);
    const brandText = await page.locator(`${TB.navbar} .navbar-brand`).innerText();
    expect(brandText.length).toBeGreaterThan(0);
  });

  test('unknown post id returns the styled 404 page', async ({ page }) => {
    const response = await page.goto(`${BASE_URL}/?p=999999`);
    expect(response?.status()).toBe(404);
    await expect(page.locator(TB.notFoundDisplay)).toBeVisible();
    await expect(page.locator(TB.backHomeButton)).toBeVisible();
    const errorText = (await page.content()).toUpperCase();
    expect(errorText).not.toContain('FATAL ERROR');
    expect(errorText).not.toContain('STACK TRACE');
  });
});