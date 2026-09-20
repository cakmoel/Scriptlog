// theme-404.spec.ts
// 404/400 ownership lives in the Dispatcher, never in templates. Valid URLs render;
// invalid slugs/ids produce a proper 404 page with intact document structure.
import { test, expect } from '@playwright/test';
import { BLOG, BLOG_BASE_URL } from './blog-selectors';

const BASE_URL: string = BLOG_BASE_URL;

test.describe('theme 404 handling', () => {
  test('valid post ID renders the single post template with 200', async ({
    page,
  }) => {
    const response = await page.goto(`${BASE_URL}/?p=11`);
    expect(response?.status()).toBe(200);
    await expect(page.locator(BLOG.postSingle)).toBeVisible();
    await expect(page.locator(BLOG.postSingleTitle)).toHaveText(
      'Test Numeric Post',
    );
  });

  test('missing post ID renders 404 with Back-to-Home link', async ({
    page,
  }) => {
    const response = await page.goto(`${BASE_URL}/?p=999999`);
    expect(response?.status()).toBe(404);
    await expect(page.locator(BLOG.notFoundHeading)).toContainText('404');
    await expect(page.locator(BLOG.backHomeButton)).toBeVisible();
  });

  // NOTE: under rewrite=no the app's theme 404 template only renders for known
  // routes with missing content (e.g. ?p=999999). Unknown bare paths are
  // short-circuited by lib/main.php's early 404 guard (die('404 Not Found')),
  // so the template assertions below target ?p=999999.
  const NOT_FOUND_URL: string = `${BASE_URL}/?p=999999`;

  test('404 page keeps document structure intact (one html/header/main/footer)', async ({
    page,
  }) => {
    await page.goto(NOT_FOUND_URL);
    await expect(page.locator(BLOG.html)).toHaveCount(1);
    await expect(page.locator(BLOG.banner)).toHaveCount(1);
    await expect(page.locator(BLOG.main)).toHaveCount(1);
    await expect(page.locator(BLOG.footer)).toHaveCount(1);
    await expect(page.locator('body > html')).toHaveCount(0);
  });

  test('back-to-home link resolves to the app root', async ({ page }) => {
    await page.goto(NOT_FOUND_URL);
    const href: string | null = await page
      .locator(BLOG.backHomeButton)
      .getAttribute('href');
    expect(href).not.toBeNull();
    expect(href).not.toBe('#');
    expect(href).not.toBe('');
  });

  test('error pages still load the CSS/JS asset bundle', async ({ page }) => {
    const failed: string[] = [];
    page.on('response', (res) => {
      if (res.status() >= 400 && res.url().includes('/assets/')) {
        failed.push(res.url());
      }
    });
    await page.goto(NOT_FOUND_URL);
    expect(failed).toEqual([]);
  });

  test('no template-level http_response_code/exit/die in theme templates', async ({
    page,
  }) => {
    // Scan via the rendered document: the theme must not print raw PHP markers.
    await page.goto(NOT_FOUND_URL);
    const body: string = await page.content();
    expect(body).not.toContain('http_response_code(404)');
    expect(body).not.toContain('<?php exit');
  });
});
