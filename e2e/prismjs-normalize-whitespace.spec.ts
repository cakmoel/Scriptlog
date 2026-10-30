// prismjs-normalize-whitespace.spec.ts
// Suite I: bundled NormalizeWhitespace plugin behavior.
import { test, expect } from '@playwright/test';
import {
  INJECT_HTML_INDENTED,
  gotoFixturePost,
  injectFixture,
} from './prismjs-fixtures';

test.describe('prismjs normalize whitespace', () => {
  test('I1: common leading indent is stripped, inner indent kept', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_INDENTED);
    const text: string = await page
      .locator('.post-single .post-body code.language-php')
      .evaluate((el: Element): string => el.textContent || '');
    const lines: string[] = text.split('\n');
    expect(lines[0], 'first line dedented').toBe('function test() {');
    expect(lines[1], 'inner indent relative').toBe(
      '    return "indent";',
    );
    expect(lines[2], 'closing dedented').toBe('}');
  });

  test('I2: normalized block highlights and scrolls internally', async ({
    page,
  }) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_INDENTED);
    const tokens: number = await page
      .locator(
        '.post-single .post-body code.language-php span.token',
      )
      .count();
    expect(tokens).toBeGreaterThan(0);
    await expect(
      page.locator('.post-single .post-body code.language-php'),
    ).toBeVisible();
    const pageOverflow: number = await page.evaluate(
      (): number =>
        document.documentElement.scrollWidth - window.innerWidth,
    );
    expect(pageOverflow).toBeLessThanOrEqual(0);
  });
});
