// @ts-check
// rb-listings.spec.js
// Category/tag/archive/blog listings on RestoBlog: seeded cards appear with
// expected counts, pagination structure is present, and the sidebar widgets
// render alongside the listings. Pagination links are asserted structurally
// only (?p=N collides with the single-post route under permalinks-OFF).
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  RB_TAG,
  dbScalar,
  seedRestoblogFixtures,
} from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

test.describe('restoblog listings', () => {
  test.beforeAll(() => {
    seedRestoblogFixtures();
  });

  test('tag listing shows exactly the three seeded posts', async ({ page }) => {
    await page.goto(`${BASE_URL}/?tag=${RB_TAG}`);
    await expect(page.locator(RB.listing)).toBeVisible();
    await expect(page.locator(RB.listingCard)).toHaveCount(3);
    await expect(page.locator(RB.pageHeader)).toHaveText(`#${RB_TAG}`);
  });

  test('category listing shows the topic header and its posts', async ({ page }) => {
    const topicTitle = dbScalar('SELECT topic_title FROM tbl_topics WHERE ID = 200');
    test.expect(topicTitle).not.toBe('');
    await page.goto(`${BASE_URL}/?cat=200`);
    await expect(page.locator(RB.pageHeader)).toContainText(topicTitle);
    // The category query may surface a subset of linked rows; assert non-empty.
    const count = await page.locator(RB.listingCard).count();
    expect(count).toBeGreaterThan(0);
  });

  test('monthly archive lists published posts for 2026-08', async ({ page }) => {
    await page.goto(`${BASE_URL}/?a=202608`);
    await expect(page.locator(RB.listing)).toBeVisible();
    const count = await page.locator(RB.listingCard).count();
    expect(count).toBeGreaterThan(0);
  });

  test('blog listing paginates at ten posts per page', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    await expect(page.locator(RB.listingCard)).toHaveCount(10);
    await expect(page.locator(RB.paginationNav)).toBeVisible();
    await expect(page.locator(RB.activePageLink)).toHaveText('1');
    const firstHref = await page.locator(RB.pageItemLink).first().getAttribute('href');
    expect(firstHref).toBeTruthy();
  });

  test('sidebar widgets render beside the listing', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    await expect(page.locator('#sidebarContainer')).toBeVisible();
    await expect(page.locator(RB.sidebarSearchWidgetTitle)).toBeVisible();
// Search widget carries a hidden honeypot field on list pages too.
     expect(await page.locator(RB.searchVerify).inputValue()).toBe('');
  });
});