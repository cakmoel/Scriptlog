// prismjs-plugins.spec.ts
// Suite C: Prism plugin behavior (line-numbers, toolbar, copy, show-language).
import { test, expect } from '@playwright/test';
import {
  INJECT_HTML_FULL,
  LANGUAGE_CASES,
  gotoFixturePost,
  grantClipboard,
  hoverAndClickCopy,
  injectFixture,
} from './prismjs-fixtures';

test.describe('prismjs plugins', () => {
  test('C1: line-numbers pres get line rows', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const pres = page.locator(
      '.post-single .post-body pre.line-numbers',
    );
    expect(await pres.count()).toBeGreaterThanOrEqual(2);
    for (let i = 0; i < (await pres.count()); i++) {
      const rows: number = await pres
        .nth(i)
        .locator('.line-numbers-rows > span')
        .count();
      expect(rows, `pre ${i}`).toBeGreaterThan(0);
    }
  });

  test('C2: line-number count matches code lines', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const pres = page.locator(
      '.post-single .post-body pre.line-numbers',
    );
    for (let i = 0; i < (await pres.count()); i++) {
      const lines: number = await pres
        .nth(i)
        .locator('code')
        .evaluate(
          (el: Element): number =>
            (el.textContent || '').split('\n').length,
        );
      const rows: number = await pres
        .nth(i)
        .locator('.line-numbers-rows > span')
        .count();
      expect(rows, `pre ${i}`).toBe(lines);
    }
  });

  test('C3: line rows are hidden from assistive tech', async ({
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

  test('C4: bare pre gets no line numbers', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const bare = page.locator(
      '.post-single .post-body pre.language-css',
    );
    await expect(bare).toHaveCount(1);
    await expect(
      bare.locator('.line-numbers-rows'),
    ).toHaveCount(0);
  });

  test('C5: each block has one copy button', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const toolbars = page.locator(
      '.post-single .post-body div.code-toolbar',
    );
    expect(await toolbars.count()).toBe(3);
    const buttons = page.locator(
      '.post-single .post-body button.copy-to-clipboard-button',
    );
    expect(await buttons.count()).toBe(3);
    for (let i = 0; i < 3; i++) {
      await expect(buttons.nth(i)).toHaveAttribute('type', 'button');
      await expect(buttons.nth(i)).toContainText('Copy');
    }
  });

  test('C6: copy button copies the block text', async ({
    page,
    context,
  }) => {
    const clipboardGranted: boolean = await grantClipboard(context);
    await gotoFixturePost(page);
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
    await expect(button).toHaveAttribute(
      'data-copy-state',
      'copy-success',
    );
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

  test('C7: language labels match the bundle map', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    const html: string = LANGUAGE_CASES.map(
      (c) => `<pre><code class="language-${c.lang}">${c.sample}</code></pre>`,
    ).join('');
    await injectFixture(page, html);
    for (const c of LANGUAGE_CASES) {
      const label = page
        .locator(
          `.post-single .post-body div.code-toolbar:has(code.language-${c.lang}) .toolbar-item span`,
        )
        .first();
      await expect(label, c.lang).toHaveText(c.label);
    }
  });

  test('C8: toolbar is hidden at rest', async ({ page }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const opacity: string = await page
      .locator('.post-single .post-body div.code-toolbar .toolbar')
      .first()
      .evaluate(
        (el: Element): string => getComputedStyle(el).opacity,
      );
    expect(opacity).toBe('0');
  });

  test('C9: toolbar reveals on hover and on focus-within', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    const toolbar = page.locator(
      '.post-single .post-body div.code-toolbar .toolbar',
    );
    // Hover the <pre> itself (large, stable target): :hover applies to the
    // ancestor div.code-toolbar just the same. Center it first so the
    // theme's fixed navbar cannot cover the hover point.
    const firstPre = page.locator(
      '.post-single .post-body div.code-toolbar pre',
    );
    await firstPre
      .first()
      .evaluate((el: Element): void => {
        el.scrollIntoView({ block: 'center', inline: 'center' });
      });
    await firstPre.first().hover();
    await expect
      .poll(
        (): Promise<string> =>
          toolbar
            .first()
            .evaluate(
              (el: Element): string => getComputedStyle(el).opacity,
            ),
        { timeout: 5000 },
      )
      .toBe('1');
    await page
      .locator(
        '.post-single .post-body button.copy-to-clipboard-button',
      )
      .first()
      .focus();
    const focusOpacity: string = await toolbar
      .first()
      .evaluate(
        (el: Element): string => getComputedStyle(el).opacity,
      );
    expect(focusOpacity).toBe('1');
  });
});
