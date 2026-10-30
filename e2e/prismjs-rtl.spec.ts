// prismjs-rtl.spec.ts
// Suite F: highlighting on RTL pages (Arabic locale).
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  INJECT_HTML_FULL,
  gotoFixturePost,
  grantClipboard,
  hoverAndClickCopy,
  injectFixture,
  isPrismActive,
} from './prismjs-fixtures';

/** Switch to Arabic, then open the server-rendered fixture post. */
async function gotoRtlFixturePost(
  page: import('@playwright/test').Page,
): Promise<void> {
  await page.goto(`${BASE_URL}/?switch-lang=ar`);
  await page.waitForLoadState('networkidle');
  await gotoFixturePost(page);
}

test.describe('prismjs rtl', () => {
  test('F1: rtl page keeps prism assets and adds rtl assets', async ({
    page,
  }) => {
    await gotoRtlFixturePost(page);
    const dir: string | null = await page
      .locator('html')
      .getAttribute('dir');
    expect(dir).toBe('rtl');
    await expect(
      page.locator('head link[href*="vendor/prism/prism.css"]'),
    ).toHaveCount(1);
    await expect(
      page.locator('script[src*="vendor/prism/prism.js"]'),
    ).toHaveCount(1);
    // RTL extras layer on top — they never replace Prism assets.
    await expect(
      page.locator('head link[href*="rtl.min.css"]'),
    ).toHaveCount(1);
  });

  test('F2: code blocks highlight in rtl', async ({ page }) => {
    await gotoRtlFixturePost(page);
    expect(await isPrismActive(page)).toBe(true);
    await injectFixture(page, INJECT_HTML_FULL);
    for (const lang of ['php', 'js', 'css']) {
      const tokens: number = await page
        .locator(`.post-single .post-body code.language-${lang} span.token`)
        .count();
      expect(tokens, `language-${lang}`).toBeGreaterThan(0);
    }
  });

  test('F3: line numbers are intact and visible in rtl', async ({
    page,
  }) => {
    await gotoRtlFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const rows = page.locator(
      '.post-single .post-body .line-numbers-rows',
    );
    expect(await rows.count()).toBeGreaterThan(0);
    await expect(rows.first()).toHaveAttribute(
      'aria-hidden',
      'true',
    );
    const box = await rows.first().boundingBox();
    expect(box, 'rows have layout box').not.toBeNull();
    const viewport = page.viewportSize();
    expect(box as object).not.toBeNull();
    if (box && viewport) {
      expect(box.x, 'rows inside viewport').toBeGreaterThanOrEqual(0);
      expect(box.x + box.width).toBeLessThanOrEqual(viewport.width);
    }
  });

  test('F4: copy works in rtl', async ({ page, context }) => {
    const clipboardGranted: boolean = await grantClipboard(context);
    await gotoRtlFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const button = page
      .locator(
        '.post-single .post-body button.copy-to-clipboard-button',
      )
      .first();
    const expected: string = await page
      .locator('.post-single .post-body div.code-toolbar code')
      .first()
      .evaluate((el: Element): string => el.textContent || '');
    await hoverAndClickCopy(page, 0);
    await expect(button).toContainText('Copied!');
    let clipboard = '';
    try {
      clipboard = await page.evaluate(
        (): Promise<string> => navigator.clipboard.readText(),
      );
    } catch {
      test.skip(true, 'Clipboard API unavailable in this browser');
    }
    if (clipboardGranted) {
      expect(clipboard).toBe(expected);
    }
  });

  test('F5: language labels render in rtl', async ({ page }) => {
    await gotoRtlFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const labels = page.locator(
      '.post-single .post-body div.code-toolbar .toolbar-item span',
    );
    expect(await labels.count()).toBeGreaterThan(0);
    await expect(labels.first()).toHaveText('PHP');
  });
});
