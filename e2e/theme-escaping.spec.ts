// theme-escaping.spec.ts
// XSS resistance: user-controlled content (title/content/summary/search query) rendered
// inert. Content mirrors the app's write-path (HTMLPurifier) state; title is escaped on
// read. No dialog may fire and no executable script may appear in the DOM.
import { test, expect } from '@playwright/test';
import { BLOG, BLOG_BASE_URL } from './blog-selectors';

const BASE_URL: string = BLOG_BASE_URL;
const XSS_POST_URL: string = `${BASE_URL}/?p=159`;
const XSS_POST_TITLE: string = 'XSS E2E';

test.describe('theme escaping', () => {
  test('XSS post loads without firing any dialog', async ({ page }) => {
    let dialogFired: boolean = false;
    page.on('dialog', async (dialog) => {
      dialogFired = true;
      await dialog.dismiss().catch(() => undefined);
    });
    const response = await page.goto(XSS_POST_URL);
    expect(response?.status()).toBe(200);
    await page.waitForLoadState('networkidle');
    expect(dialogFired).toBe(false);
  });

  test('no executable script element is present in the single post page', async ({
    page,
  }) => {
    await page.goto(XSS_POST_URL);
    const rawScripts = page.locator(`${BLOG.postBody} script`);
    expect(await rawScripts.count()).toBe(0);
    const body: string = await page.content();
    expect(body).not.toContain('<script>alert(4)');
  });

  test('XSS title payload is rendered as escaped text, not markup', async ({
    page,
  }) => {
    await page.goto(XSS_POST_URL);
    const h1 = page.locator(BLOG.postSingleTitle);
    await expect(h1).toContainText(XSS_POST_TITLE);
    const raw: string = await h1.innerHTML();
    expect(raw).not.toContain('onerror=');
    expect(raw).not.toContain('<img');
    expect(raw).not.toContain('<script>');
  });

  test('content scripts are stripped, safe markup is preserved', async ({
    page,
  }) => {
    await page.goto(XSS_POST_URL);
    const body = page.locator(BLOG.postBody);
    await expect(body).toContainText('Hello');
    await expect(body).toContainText('world');
    await expect(body.locator('b').first()).toBeVisible();
    expect(await body.locator('script').count()).toBe(0);
    expect(await body.locator('[onmouseover]').count()).toBe(0);
  });

  test('search query payload is escaped in the results header', async ({
    page,
  }) => {
    const payload: string = encodeURIComponent('<script>alert(9)</script>');
    await page.goto(`${BASE_URL}/search?q=${payload}`);
    const heading = page.locator(BLOG.searchHeading);
    await expect(heading).toBeVisible();
    const html: string = await page.content();
    expect(html).not.toContain('<script>alert(9)');
  });
});
