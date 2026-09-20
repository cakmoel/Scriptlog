// theme-baseline.spec.ts
// Smoke-test every public frontend surface of the active "blog" theme and lock the
// baseline UI contract (header/footer single instance, valid routes 200).
// NOTE: the 404-template assertions live canonically in theme-404.spec.ts and
// the nav-link assertions in theme-helper-functions.spec.ts; they are
// intentionally not duplicated here.
import { test, expect } from '@playwright/test';
import { BLOG, BLOG_BASE_URL, type BlogRoute } from './blog-selectors';

const BASE_URL: string = BLOG_BASE_URL;

const VALID_ROUTES: BlogRoute[] = [
  { url: '/', selector: BLOG.latestPosts },
  { url: '/?blog', selector: BLOG.postsListing },
  { url: '/?p=11', selector: BLOG.postSingle },
  { url: '/?cat=200', selector: BLOG.postsListing },
  { url: '/?tag=e2e-tag', selector: BLOG.postsListing },
  { url: '/?a=202608', selector: BLOG.postsListing },
  { url: '/search?q=Test', selector: BLOG.searchResultsPage },
  { url: '/privacy', selector: 'main' },
];

test.describe('theme baseline', () => {
  test('home renders header, main and footer exactly once', async ({ page }) => {
    const response = await page.goto(`${BASE_URL}/`);
    expect(response?.status()).toBe(200);

    await expect(page.locator(BLOG.html)).toHaveCount(1);
    await expect(page.locator(BLOG.banner)).toHaveCount(1);
    await expect(page.locator(BLOG.main)).toHaveCount(1);
    await expect(page.locator(BLOG.footer)).toHaveCount(1);
    await expect(page.locator(BLOG.skipLink)).toHaveCount(1);
  });

  test('home shows latest-posts cards with the shared card markup', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/`);
    const cards = page.locator(BLOG.latestCard);
    await expect(cards.first()).toBeVisible();
    const titles = page.locator(BLOG.latestCardTitle);
    await expect(titles.first()).toBeVisible();
    expect(await titles.count()).toBe(await cards.count());
    expect(await titles.count()).toBeGreaterThanOrEqual(3);
  });

  test('each valid route returns 200 and renders its distinct template content', async ({
    page,
  }) => {
    for (const route of VALID_ROUTES) {
      const response = await page.goto(`${BASE_URL}${route.url}`);
      expect(response?.status(), `route ${route.url}`).toBe(200);
      const selector: string = route.selector as string;
      await expect(
        page.locator(selector).first(),
        `selector ${selector}`,
      ).toBeVisible();
    }
  });

  test('asset bundle (css/js) loads without 404s', async ({ page }) => {
    const failed: string[] = [];
    page.on('response', (res) => {
      if (res.status() >= 400 && res.url().includes('/assets/')) {
        failed.push(`${res.status()} ${res.url()}`);
      }
    });
    await page.goto(`${BASE_URL}/`);
    await page.goto(`${BASE_URL}/?blog`);
    expect(failed).toEqual([]);
  });
});
