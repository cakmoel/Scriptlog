// @ts-check
// rb-home.spec.js
// RestoBlog home page: hero section contract, special-menu dish cards (random
// posts), the editorial articles-grid (featured cover card + row rail) of
// latest posts, the gallery mosaic, and navigation from the featured card into
// the single-post view. Hero copy may come from the featured post or the
// translated fallback (frontRandomHeadlines) so only presence is asserted, not
// exact text.
import { test, expect } from '@playwright/test';
import { BASE_URL, seedRestoblogFixtures } from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

test.describe('restoblog home', () => {
  test.beforeAll(() => {
    seedRestoblogFixtures();
  });

  test('hero section renders with a background image and a non-empty title', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    const hero = page.locator(RB.hero).first();
    await expect(hero).toBeVisible();
    const bgStyle = await hero.getAttribute('style');
    expect(bgStyle).toMatch(/background-image/);
    await expect(page.locator(RB.heroTitle)).not.toHaveText('');
    await expect(page.locator(RB.heroButtons)).toBeVisible();
    await expect(page.locator(RB.heroExploreButton)).toBeVisible();
  });

  test('special menu section shows at least one dish card with a link', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(RB.specialMenu)).toBeVisible();
    const cards = page.locator(RB.menuCard);
    await expect(cards.first()).toBeVisible();
    const count = await cards.count();
    expect(count).toBeGreaterThan(0);
    expect(count).toBeLessThanOrEqual(3);
  });

  test('articles grid shows a featured cover card and a rail of row cards', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(RB.articlesGrid)).toBeVisible();

    const cards = page.locator(RB.articleCard);
    const count = await cards.count();
    expect(count).toBeGreaterThan(0);
    expect(count).toBeLessThanOrEqual(3);

    const featured = page.locator(RB.featuredArticle);
    await expect(featured).toBeVisible();
    await expect(featured.locator('img')).toHaveAttribute('src', /./);

    const featuredLink = featured.locator('h3 a[href]').first();
    const href = await featuredLink.getAttribute('href');
    expect(href).toBeTruthy();

    const rail = page.locator(RB.articleRowCard);
    expect(await rail.count()).toBe(count - 1);

    await featuredLink.click();
    await expect(page.locator(RB.postDetail).first()).toBeVisible();
  });

  test('gallery mosaic renders thumbnail tiles wired to the Fancybox gallery', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(RB.gallerySection)).toBeVisible();
    const items = page.locator(RB.galleryItem);
    await expect(items.first()).toBeVisible();
    const count = await items.count();
    expect(count).toBeGreaterThan(0);
    expect(count).toBeLessThanOrEqual(6);
    await expect(page.locator(RB.galleryFeatured).first()).toBeVisible();

    for (let i = 0; i < count; i++) {
      const tile = items.nth(i);
      await expect(tile).toHaveAttribute('data-fancybox', 'gallery');
      const full = await tile.getAttribute('href');
      const thumb = await tile.getAttribute('data-thumb');
      const imgSrc = await tile.locator('img').getAttribute('src');
      expect(full).toBeTruthy();
      expect(thumb).toBeTruthy();
      expect(imgSrc).toBeTruthy();
      expect(imgSrc).toBe(thumb);
    }
  });

  test('gallery tiles open the Fancybox lightbox with a caption and close on Escape', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    // The cookie-consent dialog steals focus when the lightbox opens, racing
    // Fancybox's focus handling and occasionally closing it ~100ms later.
    // Accept the banner first (mirrors a returning user) for a stable open.
    const accept = page.getByRole('button', { name: 'Accept All' });
    if (await accept.count()) {
      await accept.click();
    }

    const firstTile = page.locator(RB.galleryFancyboxItem).first();
    await firstTile.click();

    const lightbox = page.locator(RB.galleryLightbox);
    await expect(lightbox).toBeVisible();
    const caption = lightbox.locator('.fancybox__slide.is-selected .fancybox__caption').first();
    await expect(caption).not.toBeEmpty();
    const closeBtn = lightbox.getByRole('button', { name: 'Close', exact: true });
    await expect(closeBtn).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(lightbox).toBeHidden();
  });

  test('gallery lightbox assets load only on the home page', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(RB.galleryAssetStyle)).toHaveCount(1);
    await expect(page.locator(RB.galleryAssetScript)).toHaveCount(1);

    await page.goto(`${BASE_URL}/?blog`);
    await expect(page.locator(RB.galleryAssetStyle)).toHaveCount(0);
    await expect(page.locator(RB.galleryAssetScript)).toHaveCount(0);
  });
});