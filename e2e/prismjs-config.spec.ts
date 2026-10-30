// prismjs-config.spec.ts
// Suite A: Prism.js asset loading on the blog theme (header/footer contract).
import { createHash } from 'node:crypto';
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  FIXTURE_POST_URL,
  OVERRIDE_CSS_SRI,
  PRISM_CSS_SRI,
  PRISM_JS_SRI,
} from './prismjs-fixtures';

test.describe('prismjs asset loading', () => {
  test('A1: prism.css is linked non-blocking with SRI', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/`);
    const link = page.locator(
      'head link[href*="vendor/prism/prism.css"]',
    );
    await expect(link).toHaveCount(1);
    // Non-blocking handoff: served as media="print", flipped to "all" by
    // the header activation script once stylesheets are ready.
    await expect(link).toHaveAttribute('media', 'all');
    await expect(link).toHaveAttribute('integrity', PRISM_CSS_SRI);
    await expect(link).toHaveAttribute('crossorigin', 'anonymous');
    const raw: string = await (
      await page.request.get(`${BASE_URL}/`)
    ).text();
    expect(raw).toContain('vendor/prism/prism.css');
    expect(raw).toContain('media="print"');
  });

  test('A2: prism.css noscript fallback exists', async ({ page }) => {
    // With JS enabled noscript content stays inert text, so assert the
    // fallback markup in the served HTML instead of the live DOM.
    const raw: string = await (
      await page.request.get(`${BASE_URL}/`)
    ).text();
    expect(raw).toContain(
      `<noscript><link rel="stylesheet" href="${BASE_URL}/public/themes/blog/assets/vendor/prism/prism.css`,
    );
    expect(raw).toContain(PRISM_CSS_SRI);
  });

  test('A3: prism-override.min.css is loaded with SRI', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/`);
    const link = page.locator(
      'head link[href*="prism-override.min.css"]',
    );
    await expect(link).toHaveCount(1);
    await expect(link).toHaveAttribute('integrity', OVERRIDE_CSS_SRI);
    await expect(link).toHaveAttribute('crossorigin', 'anonymous');
  });

  test('A4: prism.js is deferred with SRI', async ({ page }) => {
    await page.goto(`${BASE_URL}${FIXTURE_POST_URL}`);
    const script = page.locator(
      'script[src*="vendor/prism/prism.js"]',
    );
    await expect(script).toHaveCount(1);
    // Boolean defer attribute: presence is the contract.
    expect(await script.getAttribute('defer')).not.toBeNull();
    await expect(script).toHaveAttribute('integrity', PRISM_JS_SRI);
    await expect(script).toHaveAttribute('crossorigin', 'anonymous');
  });

  test('A5: integrity hashes match the files on disk', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/`);
    const targets: Array<{ selector: string; attr: string; sri: string }> =
      [
        {
          selector: 'head link[href*="vendor/prism/prism.css"]',
          attr: 'href',
          sri: PRISM_CSS_SRI,
        },
        {
          selector: 'head link[href*="prism-override.min.css"]',
          attr: 'href',
          sri: OVERRIDE_CSS_SRI,
        },
        {
          selector: 'script[src*="vendor/prism/prism.js"]',
          attr: 'src',
          sri: PRISM_JS_SRI,
        },
      ];
    for (const target of targets) {
      const url: string | null = await page
        .locator(target.selector)
        .first()
        .getAttribute(target.attr);
      expect(url, target.selector).not.toBeNull();
      const response = await page.request.get(url as string);
      expect(response.status(), url as string).toBe(200);
      const bytes: Buffer = Buffer.from(await response.body());
      const digest: string =
        'sha384-' +
        createHash('sha384').update(bytes).digest('base64');
      expect(digest, url as string).toBe(target.sri);
    }
  });

  test('A6: no prism/theme asset 404s', async ({ page }) => {
    const failed: string[] = [];
    page.on('response', (res) => {
      if (res.status() >= 400 && res.url().includes('/assets/')) {
        failed.push(`${res.status()} ${res.url()}`);
      }
    });
    await page.goto(`${BASE_URL}/`);
    await page.waitForLoadState('networkidle');
    await page.goto(`${BASE_URL}${FIXTURE_POST_URL}`);
    await page.waitForLoadState('networkidle');
    expect(failed).toEqual([]);
  });
});
