// prismjs-accessibility.spec.ts
// Suite E: accessibility of the highlighting layer. Contrast and naming
// assertions encode the FACTUAL state (including two documented gaps in the
// vendored Default theme), so a future theme fix will visibly flip them.
import { test, expect } from '@playwright/test';
import {
  INJECT_HTML_FULL,
  contrastRatio,
  gotoFixturePost,
  injectFixture,
  parseRgb,
} from './prismjs-fixtures';

const COMMENT_HTML: string =
  '<pre><code class="language-php">// note\necho "x";</code></pre>';

test.describe('prismjs accessibility', () => {
  test('E1: copy button contract (no accessible name — known gap)', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const buttons = page.locator(
      '.post-single .post-body button.copy-to-clipboard-button',
    );
    expect(await buttons.count()).toBeGreaterThan(0);
    for (let i = 0; i < (await buttons.count()); i++) {
      await expect(buttons.nth(i)).toHaveAttribute('type', 'button');
      // Factual: Prism 1.30 ships no aria-label here. Documented as a
      // known gap (improvement: aria-label="Copy code"); the test locks
      // the current state so the fix is deliberate, not accidental.
      expect(
        await buttons.nth(i).getAttribute('aria-label'),
        `button ${i} aria-label (known gap: none set)`,
      ).toBeNull();
    }
  });

  test('E2: line numbers hidden from assistive tech', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const rows = page.locator(
      '.post-single .post-body .line-numbers-rows',
    );
    expect(await rows.count()).toBeGreaterThan(0);
    for (let i = 0; i < (await rows.count()); i++) {
      await expect(rows.nth(i)).toHaveAttribute(
        'aria-hidden',
        'true',
      );
    }
  });

  test('E3: pre elements carry no bogus role', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const pres = page.locator(
      '.post-single .post-body pre[class*="language-"]',
    );
    expect(await pres.count()).toBeGreaterThan(0);
    for (let i = 0; i < (await pres.count()); i++) {
      // Improvement opportunity (role="region" + aria-label) is
      // documented, not asserted: today there must simply be no role.
      expect(await pres.nth(i).getAttribute('role')).toBeNull();
    }
  });

  test('E4: token contrast ratios are factual', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, COMMENT_HTML);
    const bg: string = await page
      .locator('.post-single .post-body pre[class*="language-"]')
      .first()
      .evaluate(
        (el: Element): string => getComputedStyle(el).backgroundColor,
      );
    const ratioOf = async (tokenClass: string): Promise<number> => {
      const fg: string = await page
        .locator(
          `.post-single .post-body span.token.${tokenClass}`,
        )
        .first()
        .evaluate(
          (el: Element): string => getComputedStyle(el).color,
        );
      return contrastRatio(parseRgb(fg), parseRgb(bg));
    };
    const keyword: number = await ratioOf('keyword');
    // Factual (measured 4.47): even .token.keyword (#07a) falls marginally
    // below WCAG 2.1 AA 4.5:1 on #f5f2f0 — the Default theme as vendored
    // meets AA for no token type. The band locks the current state.
    expect(keyword, `keyword ratio ${keyword.toFixed(2)}`).toBeGreaterThanOrEqual(
      4.4,
    );
    expect(keyword, `keyword ratio ${keyword.toFixed(2)}`).toBeLessThan(
      4.5,
    );
    // Known limitation of the Prism Default theme on #f5f2f0: comment
    // (#708090) and punctuation (#999) fall below WCAG 2.1 AA 4.5:1.
    const comment: number = await ratioOf('comment');
    expect(comment, `comment ratio ${comment.toFixed(2)}`).toBeLessThan(
      4.5,
    );
    const punctuation: number = await ratioOf('punctuation');
    expect(
      punctuation,
      `punctuation ratio ${punctuation.toFixed(2)}`,
    ).toBeLessThan(4.5);
  });

  test('E5: inline-code contrast gap is documented', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const style = await page
      .locator(
        '.post-single .post-body p > code:not([class*="language-"])',
      )
      .evaluate(
        (el: Element): { fg: string; bg: string } => ({
          fg: getComputedStyle(el).color,
          bg: getComputedStyle(el).backgroundColor,
        }),
      );
    const ratio: number = contrastRatio(
      parseRgb(style.fg),
      parseRgb(style.bg),
    );
    // Known gap: #e83e8c on #f4f4f4 (~3.5:1) is below WCAG 2.1 AA 4.5:1.
    expect(ratio, `inline ratio ${ratio.toFixed(2)}`).toBeLessThan(4.5);
  });

  test('E6: copy button shows a focus indicator', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    // Reach the button through the real keyboard path so :focus-visible
    // (the global header rule) applies, exactly as a keyboard user does.
    const button = page.locator(
      '.post-single .post-body button.copy-to-clipboard-button',
    );
    let focused = false;
    for (let i = 0; i < 60; i++) {
      await page.keyboard.press('Tab');
      const match: boolean = await page.evaluate(
        (): boolean =>
          document.activeElement !== null &&
          document.activeElement.classList.contains(
            'copy-to-clipboard-button',
          ),
      );
      if (match) {
        focused = true;
        break;
      }
    }
    expect(focused, 'copy button focused via Tab').toBe(true);
    const outlineStyle: string = await button
      .first()
      .evaluate(
        (el: Element): string => getComputedStyle(el).outlineStyle,
      );
    expect(outlineStyle, 'global :focus-visible outline').not.toBe(
      'none',
    );
  });

  test('E7: copy button is keyboard reachable', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    await page.locator('.post-single .post-body').first().click();
    let reached = false;
    for (let i = 0; i < 60; i++) {
      await page.keyboard.press('Tab');
      const match: boolean = await page.evaluate(
        (): boolean =>
          document.activeElement !== null &&
          document.activeElement.classList.contains(
            'copy-to-clipboard-button',
          ),
      );
      if (match) {
        reached = true;
        break;
      }
    }
    expect(reached, 'copy button reached via Tab').toBe(true);
  });
});
