// tb-page.spec.ts
// Static page rendering on TastyBites: validates the ?pg={id} query-string
// route renders the seeded page with title, author, date, content, and the
// sidebar. The pg route resolves pages by numeric ID (rewrite=no mode), so the
// seeded page's auto-increment ID is looked up after seeding.
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  tbPageId,
  seedTastybitesFixtures,
} from './tastybites-fixtures';
import { TB } from './tb-selectors';

test.describe('tastybites static page', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });

  const pageUrl = () => `${BASE_URL}/?pg=${tbPageId()}`;

  test('page renders with title and content', async ({ page }) => {
    await page.goto(pageUrl());
    await expect(page.locator(TB.postSingle).first()).toBeVisible();
    await expect(page.locator(`${TB.postSingle} h1`).first()).toContainText(
      'TastyBites E2E About Page',
    );
    await expect(page.locator(TB.pageContent)).toContainText(
      'TastyBites E2E about page content',
    );
  });

  test('page displays author and date metadata', async ({ page }) => {
    await page.goto(pageUrl());
    await expect(page.locator(TB.pageAuthor)).toBeVisible();
    await expect(page.locator(TB.pageDate)).toBeVisible();
  });

  test('page includes the sidebar', async ({ page }) => {
    await page.goto(pageUrl());
    await expect(page.locator('aside.col-lg-4')).toBeVisible();
  });

  test('page has a featured image placeholder (SVG)', async ({ page }) => {
    await page.goto(pageUrl());
    // The seeded page has no media, so the SVG placeholder should render.
    const placeholder = page.locator('.post-thumbnail svg, .post-thumbnail-placeholder svg');
    await expect(placeholder.first()).toBeVisible();
  });
});
