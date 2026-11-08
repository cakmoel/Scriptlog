// @ts-check
// rb-i18n-rtl.spec.js
// Frontend i18n on RestoBlog: the language switcher lists seeded locales,
// switching persists via session/cookie, Spanish translates the sidebar search
// title and search heading, and Arabic flips the document to RTL. This theme
// ships no rtl.min.css/rtl.min.js assets (the single style.min.css carries all
// RTL variables), so those TastyBites-specific assertions are intentionally
// absent; zh is skipped per the TastyBites plan finding G9.
import { test, expect } from '@playwright/test';
import { BASE_URL, seedRestoblogFixtures } from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

/** Switch locale through the same URL pattern the dropdown emits. */
async function switchLocale(page, code) {
  await page.goto(`${BASE_URL}/?switch-lang=${code}&redirect=${encodeURIComponent('/?blog')}`);
}

test.describe('restoblog i18n and rtl', () => {
  test.beforeAll(() => {
    seedRestoblogFixtures();
  });

  test('default document is English LTR with a multilingual switcher contract', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    const html = page.locator('html');
    await expect(html).toHaveAttribute('lang', 'en');
    await expect(html).toHaveAttribute('dir', 'ltr');
    await expect(page.locator(RB.languageMenu)).toBeVisible();
    // The switcher renders one entry per configured locale (lang_available).
    const items = page.locator(RB.langDropdownItem);
    const count = await items.count();
    expect(count).toBeGreaterThanOrEqual(3);
  });

  test('language dropdown toggles open on click and closes on outside click/Escape', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    const menu = page.locator('.language-switcher .dropdown-menu');
    await expect(menu).toBeHidden();

    // main.js drives the toggle (no Bootstrap JS is loaded by this theme).
    await page.locator(RB.languageMenu).click();
    await expect(menu).toBeVisible();
    await expect(page.locator(RB.languageMenu)).toHaveAttribute('aria-expanded', 'true');

    // Outside click closes it.
    await page.mouse.click(10, 500);
    await expect(menu).toBeHidden();
    await expect(page.locator(RB.languageMenu)).toHaveAttribute('aria-expanded', 'false');

    // Escape closes it as well.
    await page.locator(RB.languageMenu).click();
    await expect(menu).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(menu).toBeHidden();
  });

  test('switching to Spanish translates the sidebar search title and search heading', async ({ page }) => {
    await switchLocale(page, 'es');
    await expect(page.locator('html')).toHaveAttribute('lang', 'es');
    // Sidebar search widget title comes from es.json sidebar.search.title.
    await expect(page.locator(RB.sidebarSearchWidgetTitle)).toHaveText('Buscar');
    // Search page heading uses search.title (es "Buscar").
    await page.goto(`${BASE_URL}/search?q=RestoBlog`);
    await expect(page.locator(RB.pageHeader)).toContainText('Buscar');
  });

  test('switching to Arabic enables RTL layout', async ({ page }) => {
    await switchLocale(page, 'ar');
    await expect(page.locator('html')).toHaveAttribute('lang', 'ar');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    // Language badge reflects the Arabic native name.
    await expect(page.locator(RB.langText)).toContainText('العربية');
  });

  test('locale choice persists across reloads within the session', async ({ page }) => {
    await switchLocale(page, 'es');
    await page.reload();
    await expect(page.locator('html')).toHaveAttribute('lang', 'es');

    // Back to English for later specs in the same worker context.
    await switchLocale(page, 'en');
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
  });
});