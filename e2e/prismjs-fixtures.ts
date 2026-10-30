// prismjs-fixtures.ts
// Shared source of truth for the Prism.js e2e suites (blog theme).
// Values verified against public/themes/blog/assets/vendor/prism/ (v1.30.0)
// and the live DOM. The server-rendered fixture post 99901 lives in the
// scratch blogware_e2e database only (see FIXTURE_SQL).
import { expect, type Page } from '@playwright/test';
import { BLOG_BASE_URL } from './blog-selectors';

/** Scratch e2e post carrying code blocks through the real render pipeline. */
export const FIXTURE_POST_ID: number = 99901;
export const FIXTURE_POST_URL: string = `/?p=${FIXTURE_POST_ID}`;
export const BASE_URL: string = BLOG_BASE_URL;

/** Expected SRI values (verified with openssl dgst -sha384 -binary). */
export const PRISM_JS_SRI: string =
  'sha384-Uo7StAor23ikLRSSe1KnYLOqudG1iH6SdjPpkuqmRZcmGDlbiKultKemHaJ9yzbd';
export const PRISM_CSS_SRI: string =
  'sha384-+GkyFJeCCbBA3eRVrMwWcofk/aYW0UuRmirJN+mz8EfLFObEXVbVNYYmqROjxjDM';
export const OVERRIDE_CSS_SRI: string =
  'sha384-FynhRoijFnUp6ZpaZDOqkePRzt72UFrc55fFeNxubraxvcyw+ndiqR63Q5nd05nV';

/** One highlightable sample per language with its exact toolbar label. */
export type LanguageCase = {
  lang: string;
  label: string;
  /** Entity-escaped code text (safe for innerHTML injection). */
  sample: string;
};

export const LANGUAGE_CASES: LanguageCase[] = [
  {
    lang: 'php',
    label: 'PHP',
    sample: '&lt;?php\necho "Hello";\n$name = \'World\';\n?&gt;',
  },
  {
    lang: 'javascript',
    label: 'JavaScript',
    sample:
      'const greet = (name) =&gt; {\n    console.log(`Hi ${name}`);\n    return name;\n};',
  },
  {
    lang: 'js',
    label: 'JavaScript',
    sample: 'const total = items.map((i) =&gt; i * 2);',
  },
  {
    lang: 'html',
    label: 'HTML',
    sample:
      '&lt;div class="box"&gt;\n    &lt;p&gt;Hi&lt;/p&gt;\n&lt;/div&gt;',
  },
  {
    lang: 'css',
    label: 'CSS',
    sample: '.box {\n    display: flex;\n    gap: 1rem;\n}',
  },
  {
    lang: 'bash',
    label: 'Bash',
    sample:
      '#!/bin/bash\nif [ -f file ]; then\n    echo "yes"\nfi',
  },
  {
    lang: 'python',
    label: 'Python',
    sample: 'def greet(name):\n    return "hi " + name',
  },
  {
    lang: 'sql',
    label: 'SQL',
    sample: 'SELECT id, username FROM users WHERE active = 1;',
  },
  {
    lang: 'json',
    label: 'JSON',
    sample: '{\n    "name": "Scriptlog",\n    "n": 1\n}',
  },
  {
    lang: 'yaml',
    label: 'YAML',
    sample: 'host: localhost\nport: 3306\nenabled: true',
  },
  {
    lang: 'markdown',
    label: 'Markdown',
    sample: '# Title\n\n**bold** and *italic* text',
  },
  {
    lang: 'diff',
    label: 'Diff',
    sample:
      '- old_function_call();\n+ new_function_call();\n  unchanged();',
  },
];

/** Full injected fixture: line-numbered pres, one bare pre, inline codes. */
export const INJECT_HTML_FULL: string =
  '<pre class="line-numbers"><code class="language-php">&lt;?php\necho "Hello World";\n$greet = function ($name) {\n    return "Hello, " . $name;\n};\n?&gt;</code></pre>' +
  '<pre class="line-numbers"><code class="language-js">const greet = (name) =&gt; {\n    console.log(`Hello, ${name}!`);\n    return name;\n};\ngreet("World");</code></pre>' +
  '<pre><code class="language-css">.container {\n    display: flex;\n    gap: 1rem;\n}</code></pre>' +
  '<p>Inline <code>plain code</code> and <code class="language-php">$variable = 1;</code> samples.</p>';

/** Indented block exercising the bundled NormalizeWhitespace plugin. */
export const INJECT_HTML_INDENTED: string =
  '<pre><code class="language-php">    function test() {\n        return "indent";\n    }</code></pre>';

/** SQL reference for recreating the scratch fixture post (blogware_e2e only). */
export const FIXTURE_SQL: string = [
  "INSERT INTO tbl_posts (ID, media_id, post_author, post_date, post_title,",
  "post_slug, post_content, post_summary, post_status, post_visibility,",
  "post_tags, post_headlines, post_sticky, post_type, post_locale,",
  "comment_status) VALUES (99901, 0, 1, NOW(),",
  "'Prism.js Syntax Highlighting Fixture', 'prismjs-fixture-post',",
  "'<fixture html with pre/code language-* classes>', 'Prism fixture post',",
  "'publish', 'public', 'prism,e2e', 0, 0, 'blog', 'en', 'closed')",
  "ON DUPLICATE KEY UPDATE post_status='publish', post_visibility='public';",
].join('\n');

/**
 * Navigate to the server-rendered fixture post and wait for Prism to finish.
 */
export async function gotoFixturePost(page: Page): Promise<void> {
  const response = await page.goto(`${BASE_URL}${FIXTURE_POST_URL}`);
  expect(response?.status(), 'fixture post status').toBe(200);
  await page.waitForLoadState('networkidle');
  await waitForPrism(page);
}

/** True when the vendored Prism bundle is loaded and ready. */
export async function isPrismActive(page: Page): Promise<boolean> {
  return page.evaluate(
    (): boolean =>
      typeof (window as unknown as { Prism?: unknown }).Prism !==
        'undefined' &&
      (window as unknown as { Prism: { languages?: unknown } }).Prism
        .languages !== undefined,
  );
}

async function waitForPrism(page: Page): Promise<void> {
  await page.waitForFunction(
    (): boolean =>
      typeof (window as unknown as { Prism?: unknown }).Prism !==
      'undefined',
    null,
    { timeout: 15000 },
  );
  // Deferred Prism highlights on DOMContentLoaded; allow it to settle.
  await page.waitForTimeout(500);
}

/**
 * Replace the live post body with fixture HTML and highlight it with the
 * real vendored bundle (entities keep markup intact through innerHTML).
 */
export async function injectFixture(
  page: Page,
  html: string,
): Promise<void> {
  await page.evaluate((fixtureHtml: string): void => {
    const body: Element | null = document.querySelector(
      '.post-single .post-body',
    );
    if (body) {
      body.innerHTML = fixtureHtml;
      (
        window as unknown as {
          Prism: { highlightAll: () => void };
        }
      ).Prism.highlightAll();
    }
  }, html);
  await page.waitForTimeout(500);
}

/** Parse "rgb(r, g, b)" / "rgba(...)" into [r, g, b]. */
export function parseRgb(cssColor: string): [number, number, number] {
  const match: RegExpMatchArray | null = cssColor.match(
    /rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)/,
  );
  if (!match) {
    throw new Error(`Cannot parse color: ${cssColor}`);
  }
  return [Number(match[1]), Number(match[2]), Number(match[3])];
}

function linearize(channel: number): number {
  const c: number = channel / 255;
  return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
}

/** Relative luminance per WCAG 2.1. */
export function luminance(rgb: [number, number, number]): number {
  return (
    0.2126 * linearize(rgb[0]) +
    0.7152 * linearize(rgb[1]) +
    0.0722 * linearize(rgb[2])
  );
}

/** WCAG contrast ratio between two sRGB colors. */
export function contrastRatio(
  fg: [number, number, number],
  bg: [number, number, number],
): number {
  const l1: number = luminance(fg);
  const l2: number = luminance(bg);
  const lighter: number = Math.max(l1, l2);
  const darker: number = Math.min(l1, l2);
  return (lighter + 0.05) / (darker + 0.05);
}

/**
 * Reveal a code block's toolbar the way a real user does (hover), then
 * click its copy button with a real (non-forced) click.
 */
export async function hoverAndClickCopy(
  page: import('@playwright/test').Page,
  index: number,
): Promise<void> {
  const block = page.locator(
    '.post-single .post-body div.code-toolbar',
  );
  // Center the block first: the theme's fixed navbar can cover the top of
  // the viewport after Playwright's default scroll-into-view, which would
  // leave the mouse over the navbar instead of the code block.
  await block
    .nth(index)
    .locator('pre')
    .evaluate((el: Element): void => {
      el.scrollIntoView({ block: 'center', inline: 'center' });
    });
  await block.nth(index).locator('pre').hover();
  await expect
    .poll(
      (): Promise<string> =>
        block
          .nth(index)
          .locator('.toolbar')
          .evaluate(
            (el: Element): string => getComputedStyle(el).opacity,
          ),
      { timeout: 5000 },
    )
    .toBe('1');
  await block.nth(index).locator('button.copy-to-clipboard-button').click();
}

/**
 * Best-effort clipboard permission grant. Chromium accepts
 * clipboard-read/write; firefox/webkit throw "Unknown permission" — in
 * that case the caller asserts button state only and skips the
 * clipboard-text comparison (documented in the test plan).
 *
 * @returns true when the grant succeeded.
 */
export async function grantClipboard(
  context: import('@playwright/test').BrowserContext,
): Promise<boolean> {
  try {
    await context.grantPermissions([
      'clipboard-read',
      'clipboard-write',
    ]);
    return true;
  } catch {
    return false;
  }
}

/**
 * Report-Only CSP style notices (pre-existing theme inline styles flagged
 * by the Report-Only header's stricter style-src) surface as console
 * errors in firefox/webkit. The enforced header allows them, so suites
 * that assert "no console errors" must ignore exactly these notices.
 */
export function isReportOnlyCspNoise(text: string): boolean {
  return /report[\s-]*only/i.test(text);
}
