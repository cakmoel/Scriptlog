// @ts-check
// rb-mobile-nav.spec.js
// Mobile-first navigation on RestoBlog: the hamburger appears below the desktop
// breakpoint, the drawer opens/closes with proper ARIA + focus management, the
// language switcher is reachable from the drawer (M4), no duplicate languageMenu
// ids are emitted, drawer taps close it, and the header stays inside the
// viewport on small phones (M5).
import { test, expect } from '@playwright/test';
import { BASE_URL, seedRestoblogFixtures } from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

const MOBILE_VIEWPORT = { width: 360, height: 740 };
const SMALL_PHONE_VIEWPORT = { width: 320, height: 640 };
const DESKTOP_VIEWPORT = { width: 1280, height: 800 };

test.describe('restoblog mobile nav', () => {
  test.beforeEach(async ({ page }) => {
    await seedRestoblogFixtures();
    await page.goto(`${BASE_URL}/?blog`);
  });

  test('hamburger is hidden on desktop and visible on mobile; nav-links collapse', async ({ page }) => {
    await page.setViewportSize(DESKTOP_VIEWPORT);
    await page.reload();
    await expect(page.locator(RB.menuToggle)).toBeHidden();
    await expect(page.locator(RB.navDrawer)).toBeHidden();

    await page.setViewportSize(MOBILE_VIEWPORT);
    await page.reload();
    await expect(page.locator(RB.menuToggle)).toBeVisible();
    await expect(page.locator(RB.navDrawer)).toBeHidden();
    // The desktop link bar is intentionally collapsed on mobile.
    await expect(page.locator('.header-nav .nav-links')).toBeHidden();
  });

  test('drawer opens with ARIA + focus management and closes on Escape', async ({ page }) => {
    await page.setViewportSize(MOBILE_VIEWPORT);
    await page.reload();

    const toggle = page.locator(RB.menuToggle);
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(toggle).toHaveAttribute('aria-controls', 'navDrawer');

    await toggle.click();
    await expect(page.locator(RB.navDrawer)).toBeVisible();
    await expect(page.locator(RB.drawerOverlay)).toBeVisible();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    // Focus starts on the close button so the keyboard user can dismiss it.
    await expect(page.locator(RB.navDrawerClose)).toBeFocused();
    // No duplicate desktop dropdown id leaks into the drawer.
    await expect(page.locator('#languageMenu')).toHaveCount(1);

    // Escape closes the drawer and returns focus to the toggle.
    await page.keyboard.press('Escape');
    await expect(page.locator(RB.navDrawer)).toBeHidden();
    await expect(page.locator(RB.drawerOverlay)).toBeHidden();
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(toggle).toBeFocused();
    await expect(page.locator('body')).not.toHaveCSS('overflow', 'hidden');
  });

  test('close button and overlay tap both dismiss the drawer', async ({ page }) => {
    await page.setViewportSize(MOBILE_VIEWPORT);
    await page.reload();

    await page.locator(RB.menuToggle).click();
    await expect(page.locator(RB.navDrawer)).toBeVisible();
    await page.locator(RB.navDrawerClose).click();
    await expect(page.locator(RB.navDrawer)).toBeHidden();

    await page.locator(RB.menuToggle).click();
    await expect(page.locator(RB.navDrawer)).toBeVisible();
    // Overlay covers the whole viewport outside the drawer; click its left edge.
    await page.mouse.click(10, 300);
    await expect(page.locator(RB.navDrawer)).toBeHidden();
  });

  test('drawer language switcher lists every configured locale and navigates', async ({ page }) => {
    await page.setViewportSize(MOBILE_VIEWPORT);
    await page.reload();

    // The drawer must expose the language switch that the collapsed nav-links
    // bar otherwise hides on mobile (M4).
    await page.locator(RB.menuToggle).click();
    await expect(page.locator(RB.navDrawer)).toBeVisible();

    await expect(page.locator(RB.drawerLangLabel)).toBeVisible();
    await expect(page.locator(RB.drawerLangLabel)).not.toBeEmpty();
    const localeLinks = page.locator(RB.drawerLangItems);
    const count = await localeLinks.count();
    expect(count).toBeGreaterThanOrEqual(3);
    const hrefs = await localeLinks.evaluateAll((nodes) => nodes.map((n) => n.getAttribute('href')));
    expect(hrefs.join(' ')).toContain('switch-lang=ar');
    expect(hrefs.join(' ')).toContain('switch-lang=es');

    // Tapping the Arabic drawer link switches the document to RTL.
    await page.locator(RB.drawerLangList).getByLabel('Arabic', { exact: true }).click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'ar');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');

    // Back to English so later specs in this worker start clean.
    await page.goto(`${BASE_URL}/?switch-lang=en&redirect=${encodeURIComponent('/?blog')}`);
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
  });

  test('header stays inside the viewport on a small phone (no horizontal overflow)', async ({ page }) => {
    await page.setViewportSize(SMALL_PHONE_VIEWPORT);
    await page.reload();

    const headerBox = await page.locator(RB.headerNav).evaluate((el) => el.getBoundingClientRect());
    expect(headerBox.right).toBeLessThanOrEqual(SMALL_PHONE_VIEWPORT.width);
    expect(headerBox.left).toBeGreaterThanOrEqual(0);

    const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
    const innerWidth = await page.evaluate(() => window.innerWidth);
    expect(scrollWidth).toBeLessThanOrEqual(innerWidth + 1);

    // Search stays usable on the smallest phones.
    await expect(page.locator(RB.navSearchInput)).toBeVisible();
    const inputWidth = await page.locator(RB.navSearchInput).evaluate((el) => el.getBoundingClientRect().width);
    expect(inputWidth).toBeGreaterThanOrEqual(60);
    expect(inputWidth).toBeLessThanOrEqual(160);
  });

  test('tapping a drawer link closes the drawer after navigation', async ({ page }) => {
    await page.setViewportSize(MOBILE_VIEWPORT);
    await page.reload();

    await page.locator(RB.menuToggle).click();
    await expect(page.locator(RB.navDrawer)).toBeVisible();

    await page.locator(RB.drawerHomeLink).click();
    await page.waitForLoadState('load');
    await expect(page.locator(RB.navDrawer)).toBeHidden();
    await expect(page.locator(RB.drawerOverlay)).toBeHidden();
  });
});