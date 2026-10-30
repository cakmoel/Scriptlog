// prismjs-highlighting.spec.ts
// Suite B: core syntax highlighting on the blog theme.
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  FIXTURE_POST_URL,
  INJECT_HTML_FULL,
  LANGUAGE_CASES,
  gotoFixturePost,
  injectFixture,
  isPrismActive,
} from './prismjs-fixtures';

const VIEWPORTS: number[] = [320, 768, 1024, 1440];

test.describe('prismjs core highlighting', () => {
  test('B1: server-rendered fixture keeps language classes', async ({
    page,
  }) => {
    const raw: string = await (
      await page.request.get(`${BASE_URL}${FIXTURE_POST_URL}`)
    ).text();
    for (const cls of [
      'code class="language-php"',
      'code class="language-js"',
      'code class="language-css"',
      'code class="language-sql"',
      'pre class="line-numbers"',
    ]) {
      expect(raw, cls).toContain(cls);
    }
  });

  test('B2: prism bundle is active on the fixture post', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    expect(await isPrismActive(page)).toBe(true);
  });

  test('B3: server-rendered blocks auto-highlight on load', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    for (const lang of ['php', 'js', 'css', 'sql']) {
      const tokens: number = await page
        .locator(`.post-single .post-body code.language-${lang} span.token`)
        .count();
      expect(tokens, `language-${lang}`).toBeGreaterThan(0);
    }
  });

  test('B4: every bundled language sample tokenizes', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    const html: string = LANGUAGE_CASES.map(
      (c) => `<pre><code class="language-${c.lang}">${c.sample}</code></pre>`,
    ).join('');
    await injectFixture(page, html);
    for (const c of LANGUAGE_CASES) {
      const tokens: number = await page
        .locator(
          `.post-single .post-body code.language-${c.lang} span.token`,
        )
        .count();
      expect(tokens, `language-${c.lang}`).toBeGreaterThan(0);
    }
  });

  test('B5: pre background is the default theme color', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    const bg: string = await page
      .locator('.post-single .post-body pre[class*="language-"]')
      .first()
      .evaluate(
        (el: Element): string => getComputedStyle(el).backgroundColor,
      );
    expect(bg).toBe('rgb(245, 242, 240)');
  });

  for (const width of VIEWPORTS) {
    test(`B6: no page overflow at ${width}px with code blocks`, async ({
      page,
    }) => {
      await page.setViewportSize({ width, height: 800 });
      await gotoFixturePost(page);
      await injectFixture(page, INJECT_HTML_FULL);
      const overflow: number = await page.evaluate(
        (): number =>
          document.documentElement.scrollWidth - window.innerWidth,
      );
      expect(overflow, `overflow at ${width}px`).toBeLessThanOrEqual(0);
      const preOverflowX: string = await page
        .locator('.post-single .post-body pre[class*="language-"]')
        .first()
        .evaluate(
          (el: Element): string => getComputedStyle(el).overflowX,
        );
      expect(preOverflowX).toBe('auto');
    });
  }
});
