// theme-performance.spec.ts
// Asset loading and N+1 guardrails: bounded network requests, lazy images with explicit
// dimensions, no 4xx/5xx asset responses, LCP content present.
import { test, expect } from '@playwright/test';
import { BLOG, BLOG_BASE_URL } from './blog-selectors';

const BASE_URL: string = BLOG_BASE_URL;

// Bound is generous: page + css/js bundles + a few images + fonts. The guardrail is that
// request count does not explode per card (no per-post query fan-out observable as
// duplicate asset loads).
const MAX_REQUESTS: number = 80;

test.describe('theme performance', () => {
  test('home page network request count is bounded', async ({ page }) => {
    const requests: string[] = [];
    page.on('request', (req) => requests.push(req.url()));
    await page.goto(`${BASE_URL}/`);
    await page.waitForLoadState('networkidle');
    const assetRequests: string[] = requests.filter(
      (u: string): boolean => !u.startsWith('data:'),
    );
    expect(assetRequests.length).toBeLessThanOrEqual(MAX_REQUESTS);
  });

  test('no 4xx/5xx asset responses on home and blog', async ({ page }) => {
    const failed: string[] = [];
    page.on('response', (res) => {
      if (res.status() >= 400) {
        failed.push(`${res.status()} ${res.url()}`);
      }
    });
    await page.goto(`${BASE_URL}/`);
    await page.goto(`${BASE_URL}/?blog`);
    expect(failed).toEqual([]);
  });

  test('every post card image is lazy with explicit dimensions', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/?blog`);
    const images = page.locator(BLOG.cardThumbImg);
    const count: number = await images.count();
    expect(count).toBeGreaterThanOrEqual(1);
    for (let i = 0; i < count; i++) {
      const img = images.nth(i);
      const loading: string | null = await img.getAttribute('loading');
      const width: string | null = await img.getAttribute('width');
      const height: string | null = await img.getAttribute('height');
      expect(loading, `img ${i} loading`).toBe('lazy');
      expect(width, `img ${i} width`).toBeTruthy();
      expect(height, `img ${i} height`).toBeTruthy();
    }
  });

  test('home has LCP-worthy content (hero or latest-posts grid)', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/`);
    const hero = page.locator('section.hero');
    const latest = page.locator(BLOG.latestPosts);
    const heroVisible: boolean =
      (await hero.count()) > 0 ? await hero.first().isVisible() : false;
    const latestVisible: boolean = await latest.first().isVisible();
    expect(heroVisible || latestVisible).toBe(true);
  });

  test('no duplicate identical same-origin asset URLs on a single page load', async ({
    page,
  }) => {
    const urls: string[] = [];
    page.on('request', (req) => urls.push(req.url()));
    await page.goto(`${BASE_URL}/`);
    await page.waitForLoadState('networkidle');
    // Only same-origin theme assets are under our control. Third-party CDNs
    // (Google Fonts) legitimately re-request the same woff2 in some engines.
    const sameOrigin: string[] = urls.filter((u: string): boolean =>
      u.startsWith(BASE_URL),
    );
    const seen = new Set<string>();
    const dupes: string[] = sameOrigin.filter((u: string): boolean => {
      if (seen.has(u)) return true;
      seen.add(u);
      return false;
    });
    expect(dupes).toEqual([]);
  });
});
