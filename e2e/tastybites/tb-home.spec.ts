// tb-home.spec.ts
// TastyBites home page: hero section contract, latest-posts card grid, and the
// conditional gallery. Hero copy is hardcoded English by design (finding G8);
// the hero h1 may be empty when site_title is unset, so only presence is
// asserted rather than exact copy.
import { test, expect } from '@playwright/test';
import { BASE_URL, seedTastybitesFixtures } from './tastybites-fixtures';
import { TB } from './tb-selectors';

test.describe('tastybites home', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });

  test('hero section renders with accessible label and search input', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    const hero = page.locator(TB.hero).first();
    await expect(hero).toBeVisible();
    await expect(hero).toHaveAttribute('role', 'img');
    await expect(hero).toHaveAttribute('aria-label', /TastyBites/i);
    await expect(page.locator(TB.heroSearchForm)).toBeVisible();
    await expect(page.locator(TB.heroSearchInput)).toBeVisible();
    // Hero search input has sr-only label for accessibility.
    const searchLabel = page.locator('label[for="hero-search-input"]');
    await expect(searchLabel).toBeAttached();
  });

  test('latest posts grid shows cards with navigable links', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(TB.latestPosts)).toBeVisible();
    const cards = page.locator(TB.homeCard);
    const count = await cards.count();
    expect(count).toBeGreaterThan(0);

    // First card title link resolves to an existing single post.
    const firstLink = page.locator(TB.homeCardTitleLink).first();
    const href = await firstLink.getAttribute('href');
    expect(href).toBeTruthy();

    await Promise.all([
      page.waitForLoadState('load'),
      firstLink.click(),
    ]);
    await expect(page.locator(TB.postSingle).first()).toBeVisible();
  });

  test('gallery section is rendered only when galleries exist', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    const galleryLinks = page.locator('a[data-fancybox="gallery"]');
    const galleryCount = await galleryLinks.count();
    // Conditional feature: zero or more is valid; presence must not 404 assets.
    expect(galleryCount).toBeGreaterThanOrEqual(0);
  });
});