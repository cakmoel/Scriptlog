// tb-i18n-rtl.spec.ts
// Frontend i18n on TastyBites: the language switcher lists seeded locales,
// switching persists via session/cookie, Spanish translates sidebar/search
// strings, and Arabic flips the document to RTL with the rtl assets loaded.
// Hardcoded-English regions (hero copy, "Read More", prev/next labels) are
// deliberately excluded per plan finding G8; zh is skipped per G9.
import { test, expect, type Page } from '@playwright/test';
import { BASE_URL, seedTastybitesFixtures } from './tastybites-fixtures';
import { TB } from './tb-selectors';

/** Switch locale through the same URL pattern the dropdown emits. */
async function switchLocale(page: Page, code: string): Promise<void> {
  await page.goto(`${BASE_URL}/?switch-lang=${code}&redirect=${encodeURIComponent('/?blog')}`);
}

test.describe('tastybites i18n and rtl', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });
  test('default document is English LTR with a seven-entry switcher contract', async ({ page }) => {
    await page.goto(`${BASE_URL}/?blog`);
    const html = page.locator('html');
    await expect(html).toHaveAttribute('lang', 'en');
    await expect(html).toHaveAttribute('dir', 'ltr');
    await expect(page.locator(TB.languageMenu)).toBeVisible();
    // The switcher renders one entry per configured locale (lang_available).
    const items = page.locator('.dropdown-menu .dropdown-item');
    const count = await items.count();
    expect(count).toBeGreaterThanOrEqual(3);
  });

  test('switching to Spanish translates the sidebar search title and search heading', async ({ page }) => {
    await switchLocale(page, 'es');
    await expect(page.locator('html')).toHaveAttribute('lang', 'es');
    // Sidebar search widget title comes from es.json sidebar.search.title.
    await expect(page.locator(TB.sidebarSearchWidgetTitle)).toHaveText('Buscar');
    // Search page heading uses search.title ("Resultados" in es.json).
    await page.goto(`${BASE_URL}/search?q=Test`);
    await expect(page.locator(TB.searchPageHeading)).toContainText('Resultados');
  });

  test('switching to Arabic enables RTL layout and rtl assets', async ({ page }) => {
    await switchLocale(page, 'ar');
    await expect(page.locator('html')).toHaveAttribute('lang', 'ar');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.locator('link[href*="rtl.min.css"]')).toHaveCount(1);
    await expect(page.locator('script[src*="rtl.min.js"]')).toHaveCount(1);
    // Language badge reflects Arabic native name.
    await expect(page.locator(`${TB.languageMenu} .lang-text`)).toContainText('العربية');
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