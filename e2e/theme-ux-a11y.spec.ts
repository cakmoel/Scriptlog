// theme-ux-a11y.spec.ts
// Responsive layout + accessibility acceptance: no horizontal overflow at breakpoints,
// skip-link works, images carry alt, focus visibility, reduced-motion tolerance.
import { test, expect } from '@playwright/test';
import { BLOG, BLOG_BASE_URL } from './blog-selectors';

const BASE_URL: string = BLOG_BASE_URL;
const VIEWPORTS: number[] = [320, 768, 1024, 1440];

test.describe('theme UX and accessibility', () => {
  for (const width of VIEWPORTS) {
    test(`no horizontal overflow at ${width}px`, async ({ page }) => {
      await page.setViewportSize({ width, height: 800 });
      await page.goto(`${BASE_URL}/`);
      const overflow: number = await page.evaluate(
        (): number =>
          document.documentElement.scrollWidth - window.innerWidth,
      );
      expect(overflow, `overflow at ${width}px`).toBeLessThanOrEqual(0);
    });
  }

  test('single post has no horizontal overflow at 320px', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await page.goto(`${BASE_URL}/?p=11`);
    const overflow: number = await page.evaluate(
      (): number => document.documentElement.scrollWidth - window.innerWidth,
    );
    expect(overflow).toBeLessThanOrEqual(0);
  });

  test('skip-link is the first tab target and reaches main content', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/`);
    await page.keyboard.press('Tab');
    const activeClass: string = await page.evaluate(
      (): string =>
        (document.activeElement && document.activeElement.className) || '',
    );
    expect(String(activeClass)).toContain('skip-link');

    await page.locator(BLOG.skipLink).press('Enter');
    await page.waitForTimeout(100);
    // main#main-content has no tabindex, so focus may not move in every
    // browser; the skip-link must at least navigate to the #main-content hash.
    const activeId: string = await page.evaluate(
      (): string => (document.activeElement && document.activeElement.id) || '',
    );
    const hash: string = await page.evaluate(
      (): string => window.location.hash,
    );
    expect(activeId === 'main-content' || hash === '#main-content').toBe(true);
  });

  test('semantic landmarks are present', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(BLOG.banner)).toHaveCount(1);
    await expect(page.locator(BLOG.main)).toHaveCount(1);
    await expect(page.locator('nav[aria-label]').first()).toBeVisible();
    await expect(page.locator(BLOG.footer)).toHaveCount(1);
  });

  test('all images on home and single carry alt text', async ({ page }) => {
    for (const route of ['/', '/?p=11']) {
      await page.goto(`${BASE_URL}${route}`);
      const images = page.locator('img');
      const count: number = await images.count();
      expect(count).toBeGreaterThanOrEqual(1);
      for (let i = 0; i < count; i++) {
        const alt: string | null = await images.nth(i).getAttribute('alt');
        expect(alt, `img ${i} on ${route}`).not.toBeNull();
      }
    }
  });

  test('nav toggle button has an accessible aria-label', async ({ page }) => {
    // The Bootstrap navbar toggle (#al) is only visible below the mobile
    // breakpoint, so assert its accessibility contract at a mobile viewport.
    await page.setViewportSize({ width: 320, height: 800 });
    await page.goto(`${BASE_URL}/`);
    const toggler = page.locator(BLOG.navToggle);
    await expect(toggler).toBeVisible();
    const label: string | null = await toggler.getAttribute('aria-label');
    expect(label).toBeTruthy();
  });

  test('reduced-motion preference causes no JS errors', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const errors: string[] = [];
    page.on('pageerror', (err: Error) => errors.push(err.message));
    await page.goto(`${BASE_URL}/`);
    await page.waitForLoadState('networkidle');
    expect(errors).toEqual([]);
  });

  test('language switcher is present in the header', async ({ page }) => {
    await page.goto(`${BASE_URL}/`);
    await expect(page.locator(BLOG.languageMenu)).toBeVisible();
  });
});
