// prismjs-console.spec.ts
// Suite H: no JS errors or Prism warnings on highlight/copy paths.
import { test, expect } from '@playwright/test';
import {
  INJECT_HTML_FULL,
  gotoFixturePost,
  grantClipboard,
  hoverAndClickCopy,
  injectFixture,
  isReportOnlyCspNoise,
} from './prismjs-fixtures';

function collectErrors(page: import('@playwright/test').Page): {
  errors: string[];
  pageErrors: string[];
} {
  const errors: string[] = [];
  const pageErrors: string[] = [];
  page.on('console', (msg): void => {
    if (msg.type() === 'error' && !isReportOnlyCspNoise(msg.text())) {
      errors.push(msg.text());
    }
  });
  page.on('pageerror', (err: Error): void => {
    pageErrors.push(err.message);
  });
  return { errors, pageErrors };
}

test.describe('prismjs console', () => {
  test('H1: clean load through highlight completion', async ({
    page,
  }) => {
    const { errors, pageErrors } = collectErrors(page);
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    expect(errors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('H2: clean copy on every block', async ({
    page,
    context,
  }) => {
    await grantClipboard(context);
    const { errors, pageErrors } = collectErrors(page);
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const buttons = page.locator(
      '.post-single .post-body button.copy-to-clipboard-button',
    );
    const total: number = await buttons.count();
    for (let i = 0; i < total; i++) {
      await hoverAndClickCopy(page, i);
      await page.waitForTimeout(200);
    }
    expect(errors).toEqual([]);
    expect(pageErrors).toEqual([]);
  });

  test('H3: no prism unknown-language warnings', async ({ page }) => {
    const warnings: string[] = [];
    page.on('console', (msg): void => {
      if (
        msg.type() === 'warning' &&
        /prism|unknown language|not found/i.test(msg.text())
      ) {
        warnings.push(msg.text());
      }
    });
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    // An unbundled language exercises the warning path if one exists.
    await page.evaluate((): void => {
      const body: Element | null = document.querySelector(
        '.post-single .post-body',
      );
      if (body) {
        body.insertAdjacentHTML(
          'beforeend',
          '<pre><code class="language-krypton">x = 1</code></pre>',
        );
        (
          window as unknown as {
            Prism: { highlightAll: () => void };
          }
        ).Prism.highlightAll();
      }
    });
    await page.waitForTimeout(500);
    expect(warnings).toEqual([]);
  });
});
