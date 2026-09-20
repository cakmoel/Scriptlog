// tb-archives.spec.ts
// Monthly archive listing on TastyBites. In the e2e environment (permalinks
// OFF, rewrite=no) the archive *index* has no query-string route and the bare
// /archives path 404s (plan runtime finding R8), so the reachable archive
// surface is the monthly listing ?a=YYYYMM. This spec covers that listing:
// header title/count, published cards, and the empty-state fallback.
import { test, expect } from '@playwright/test';
import { BASE_URL, seedTastybitesFixtures } from './tastybites-fixtures';
import { TB } from './tb-selectors';

test.describe('tastybites monthly archive', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });

  test('monthly archive renders the header with title and post count', async ({ page }) => {
    await page.goto(`${BASE_URL}/?a=202608`);
    await expect(page.locator(TB.archiveHeader)).toBeVisible();
    await expect(page.locator(`${TB.archiveHeader}`)).toContainText(/Archive/i);
    // The three public seeded posts are all published in August 2026.
    await expect(page.locator(TB.archiveDescription)).toBeVisible();
    const description = await page.locator(TB.archiveDescription).innerText();
    expect(description).toMatch(/\d+\s+post/);
  });

  test('monthly archive lists the published seeded posts', async ({ page }) => {
    await page.goto(`${BASE_URL}/?a=202608`);
    await expect(page.locator(TB.postsListing)).toBeVisible();
    const cards = page.locator(TB.listingCard);
    expect(await cards.count()).toBeGreaterThanOrEqual(1);
    // The protected post and the static page must not appear in the listing.
    const body = await page.content();
    expect(body).not.toContain('TastyBites E2E Locked');
    expect(body).not.toContain('TastyBites E2E About Page');
  });

  test('monthly archive cards link to permalink-aware post urls', async ({ page }) => {
    await page.goto(`${BASE_URL}/?a=202608`);
    const firstLink = page.locator(`${TB.listingCard} .post-details a[href][title]`).first();
    await expect(firstLink).toBeVisible();
    const href = await firstLink.getAttribute('href');
    expect(href).toBeTruthy();
    // Permalinks OFF means each card links to ?p=<id>.
    expect(href).toContain('?p=');
  });

  test('monthly archive without posts renders the empty state', async ({ page }) => {
    await page.goto(`${BASE_URL}/?a=199501`);
    await expect(page.locator(TB.archiveEmptyState)).toBeVisible();
  });
});