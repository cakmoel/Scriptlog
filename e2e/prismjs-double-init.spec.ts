// prismjs-double-init.spec.ts
// Suite J: single Prism bundle load, idempotent re-highlighting.
import { test, expect } from '@playwright/test';
import {
  INJECT_HTML_FULL,
  gotoFixturePost,
  injectFixture,
} from './prismjs-fixtures';

test.describe('prismjs double initialization', () => {
  test('J1: exactly one prism.js script tag', async ({ page }) => {
    await gotoFixturePost(page);
    await expect(
      page.locator('script[src*="vendor/prism/prism.js"]'),
    ).toHaveCount(1);
  });

  test('J2: re-highlight is idempotent', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const snapshot = async (): Promise<{ html: string; tokens: number }> =>
      page.evaluate((): { html: string; tokens: number } => {
        const body: Element | null = document.querySelector(
          '.post-single .post-body',
        );
        return {
          html: body ? body.innerHTML : '',
          tokens: body
            ? body.querySelectorAll('span.token').length
            : 0,
        };
      });
    const before = await snapshot();
    expect(before.tokens).toBeGreaterThan(0);
    await page.evaluate((): void => {
      (
        window as unknown as {
          Prism: { highlightAll: () => void };
        }
      ).Prism.highlightAll();
    });
    await page.waitForTimeout(500);
    const after = await snapshot();
    // Nested span.token pairs are legitimate Prism grammar output
    // (e.g. entities inside attr-values), so idempotency is proven by
    // byte-identical markup and a stable token count instead.
    expect(after.tokens, 'token count stable').toBe(before.tokens);
    expect(after.html, 'markup byte-identical').toBe(before.html);
  });

  test('J3: exactly one toolbar per block after re-highlight', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    await page.evaluate((): void => {
      (
        window as unknown as {
          Prism: { highlightAll: () => void };
        }
      ).Prism.highlightAll();
    });
    await page.waitForTimeout(500);
    const toolbars = page.locator(
      '.post-single .post-body div.code-toolbar',
    );
    expect(await toolbars.count()).toBe(3);
    for (let i = 0; i < 3; i++) {
      expect(
        await toolbars.nth(i).locator(':scope > .toolbar').count(),
        `toolbar ${i}`,
      ).toBe(1);
    }
  });
});
