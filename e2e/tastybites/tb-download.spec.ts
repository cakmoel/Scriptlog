// tb-download.spec.ts
// Download page on TastyBites. In the e2e environment (permalinks OFF,
// rewrite=no) downloads are dispatched through the query string (?download=),
// not the SEO path /download/{identifier} (which falls back to a bare 404 -
// plan runtime finding R9). An invalid identifier renders the themed download
// template with the error alert and a 404 status; an empty identifier
// redirects home. The actual file streaming endpoint is not tested here.
import { test, expect } from '@playwright/test';
import { BASE_URL, seedTastybitesFixtures } from './tastybites-fixtures';
import { TB } from './tb-selectors';

const INVALID_UUID = '00000000-0000-0000-0000-000000000000';

test.describe('tastybites download page', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });

  test('invalid download identifier renders the themed error, no stack traces', async ({ page }) => {
    const response = await page.goto(`${BASE_URL}/?download=${INVALID_UUID}`);
    expect(response?.status()).toBe(404);
    await expect(page.locator(TB.downloadError)).toBeVisible();
    const body = (await page.content()).toUpperCase();
    expect(body).not.toContain('FATAL ERROR');
    expect(body).not.toContain('STACK TRACE');
    expect(body).not.toContain('WHOOPS');
  });

  test('empty download identifier redirects home', async ({ page }) => {
    // page.goto follows redirects, so use the request API with redirects
    // disabled to inspect the raw 302 response.
    const response = await page.request.get(`${BASE_URL}/?download=`, { maxRedirects: 0 });
    expect(response.status()).toBeGreaterThanOrEqual(300);
    expect(response.status()).toBeLessThan(400);
    const location = response.headers()['location'] ?? '';
    expect(location).toBe(`${BASE_URL}/`);
  });

  test('download error display is reachable with an accessible alert role', async ({ page }) => {
    await page.goto(`${BASE_URL}/?download=${INVALID_UUID}`);
    const error = page.locator(`${TB.downloadError}[role="alert"]`);
    await expect(error).toBeVisible();
    await expect(error).toContainText(/download/i);
  });
});