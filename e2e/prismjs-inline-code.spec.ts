// prismjs-inline-code.spec.ts
// Suite D: inline <code> stays free of block chrome.
import { test, expect } from '@playwright/test';
import {
  INJECT_HTML_FULL,
  gotoFixturePost,
  injectFixture,
} from './prismjs-fixtures';

test.describe('prismjs inline code', () => {
  test('D1: plain inline code keeps theme pill styling', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const inline = page.locator(
      '.post-single .post-body p > code:not([class*="language-"])',
    );
    await expect(inline).toHaveCount(1);
    expect(
      await inline.locator('span.token').count(),
      'no prism tokens',
    ).toBe(0);
    const style = await inline.evaluate(
      (el: Element): { bg: string; fg: string } => ({
        bg: getComputedStyle(el).backgroundColor,
        fg: getComputedStyle(el).color,
      }),
    );
    expect(style.bg).toBe('rgb(244, 244, 244)');
    expect(style.fg).toBe('rgb(232, 62, 140)');
  });

  test('D2: language inline code highlights without chrome', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const inline = page.locator(
      '.post-single .post-body p > code.language-php',
    );
    await expect(inline).toHaveCount(1);
    expect(
      await inline.locator('span.token').count(),
      'has prism tokens',
    ).toBeGreaterThan(0);
    // Not wrapped in a toolbar…
    expect(
      await page
        .locator(
          '.post-single .post-body p div.code-toolbar',
        )
        .count(),
    ).toBe(0);
    // …and no line numbers attached.
    const parentHasLineNumbers: boolean = await inline.evaluate(
      (el: Element): boolean =>
        el.parentElement !== null &&
        el.parentElement.classList.contains('line-numbers'),
    );
    expect(parentHasLineNumbers).toBe(false);
  });

  test('D3: no page overflow at 320px with inline code', async ({
    page,
  }) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const overflow: number = await page.evaluate(
      (): number =>
        document.documentElement.scrollWidth - window.innerWidth,
    );
    expect(overflow).toBeLessThanOrEqual(0);
  });
});
