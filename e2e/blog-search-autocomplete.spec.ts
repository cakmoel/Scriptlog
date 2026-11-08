// blog-search-autocomplete.spec.ts
// Plan: specs/blog-search-autocomplete.md
//
// End-to-end coverage for the blog theme sidebar live-search widget
// (public/themes/blog/sidebar.php + assets/js/search.js) against the real
// GET /api/v1/search endpoint.
//
// Regression focus: the widget used to always render "An error occurred during
// search. Please try again later." because production error handling answered
// every API request with a 301 redirect to /api/index.php (a suppressed PHP
// warning was promoted to an exception). The response assertions below pin the
// contract: the XHR must return 200 and must not be redirected away from the
// API URL.
import { test, expect } from '@playwright/test';
import type { Page, Request, Response } from '@playwright/test';
import { BLOG, BLOG_BASE_URL } from './blog-selectors';
import { clearRateLimiters, runSql } from './gdpr-fixtures';

const BASE_URL: string = BLOG_BASE_URL;

/** Scratch e2e post backing every suggestion assertion. */
const FIXTURE_POST_ID: number = 99960;
const FIXTURE_POST_TITLE: string = 'Sidebar Autocomplete Beacon';
const FIXTURE_KEYWORD: string = 'Beacon';
/** Keyword guaranteed to match nothing in the scratch database. */
const NO_HIT_KEYWORD: string = 'zzzqqqxxnohitbeacon';
/** Keyword backing the three-post keyboard-navigation fixture. */
const KEYBOARD_KEYWORD: string = 'keynav';
/** Ids of the keyboard-navigation fixture posts. */
const KEYBOARD_POST_IDS: number[] = [99961, 99962, 99963];

/**
 * Seed (or refresh) the fixture post in the scratch e2e database.
 *
 * Idempotent: a re-run rewrites title/content/status/tags, so suggestion
 * assertions never depend on leftover state from an earlier run.
 *
 * @returns void
 */
function seedSearchFixture(): void {
  runSql(`
    INSERT INTO tbl_posts (
      ID, media_id, post_author, post_date, post_title, post_slug,
      post_content, post_summary, post_status, post_visibility, post_tags,
      post_headlines, post_sticky, post_type, post_locale, comment_status
    ) VALUES (
      ${FIXTURE_POST_ID}, 0, 1, NOW(),
      '${FIXTURE_POST_TITLE}', 'sidebar-autocomplete-beacon',
      '<p>The sidebar autocomplete beacon verifies live search suggestions.</p>',
      'Sidebar autocomplete beacon summary',
      'publish', 'public', 'beacon,e2e', 0, 0, 'blog', 'en', 'closed'
    )
    ON DUPLICATE KEY UPDATE
      post_title = VALUES(post_title),
      post_slug = VALUES(post_slug),
      post_content = VALUES(post_content),
      post_summary = VALUES(post_summary),
      post_status = 'publish',
      post_visibility = 'public',
      post_tags = VALUES(post_tags),
      post_type = 'blog',
      post_locale = 'en'
  `);
}

/**
 * Seed (or refresh) the three-post keyboard-navigation fixture.
 *
 * Multi-hit results are required to prove that consecutive ArrowDown presses
 * advance the roving highlight instead of being swallowed by the focused
 * suggestion link. The keyword (`keynav`) is deliberately unrelated to
 * FIXTURE_KEYWORD so the single-result assertions stay stable.
 *
 * @returns void
 */
function seedKeyboardFixture(): void {
  KEYBOARD_POST_IDS.forEach((postId: number, index: number): void => {
    const label: string = ['Alpha', 'Beta', 'Gamma'][index];
    runSql(`
      INSERT INTO tbl_posts (
        ID, media_id, post_author, post_date, post_title, post_slug,
        post_content, post_summary, post_status, post_visibility, post_tags,
        post_headlines, post_sticky, post_type, post_locale, comment_status
      ) VALUES (
        ${postId}, 0, 1, NOW(),
        'Keynav ${label}', 'keynav-${label.toLowerCase()}',
        '<p>Keynav ${label} exercises keyboard navigation in the sidebar widget.</p>',
        'Keynav ${label} summary',
        'publish', 'public', 'keynav,e2e', 0, 0, 'blog', 'en', 'closed'
      )
      ON DUPLICATE KEY UPDATE
        post_title = VALUES(post_title),
        post_slug = VALUES(post_slug),
        post_content = VALUES(post_content),
        post_summary = VALUES(post_summary),
        post_status = 'publish',
        post_visibility = 'public',
        post_tags = VALUES(post_tags),
        post_type = 'blog',
        post_locale = 'en'
    `);
  });
}

/**
 * Open the sidebar-bearing page under test.
 *
 * The sidebar widget is not rendered by home.php, so a single post is used.
 *
 * @param page Playwright page.
 * @returns void
 */
async function gotoWidgetPage(page: Page): Promise<void> {
  // The plain GET fallback targets the permalink-aware search URL; intercept
  // nothing else - the widget must hit the API on its own.
  const response = await page.goto(`${BASE_URL}/?p=${FIXTURE_POST_ID}`);
  expect(response?.status(), 'fixture post status').toBe(200);
  await expect(page.locator(BLOG.searchInput)).toBeVisible();
}

/**
 * Wait for the widget's search XHR and assert the success contract.
 *
 * @param page Playwright page.
 * @returns The captured API response.
 */
function waitForSearchResponse(page: Page): Promise<Response> {
  return page.waitForResponse(
    (response: Response): boolean =>
      response.url().includes('/api/v1/search') && response.request().method() === 'GET',
  );
}

test.describe('blog sidebar search autocomplete', () => {
  test.beforeAll((): void => {
    // Every e2e request comes from 127.0.0.1, so the read bucket is shared
    // with the other suites; start from an empty counter.
    clearRateLimiters();
    seedSearchFixture();
    seedKeyboardFixture();
  });

  test('renders live suggestions from a 200 API response', async ({ page }) => {
    const pageErrors: string[] = [];
    const consoleErrors: string[] = [];
    page.on('pageerror', (error: Error): void => {
      pageErrors.push(error.message);
    });
    page.on('console', (message): void => {
      // Firefox/WebKit report CSP `Report-Only` violations as console errors
      // ("[Report Only] Refused to apply a stylesheet ...", "The Content
      // Security Policy directive 'frame-ancestors' is ignored ..."). They
      // come from the theme shell and post content, not from the search
      // widget, and appear on every blog page.
      const text: string = message.text();
      const isCspNoise: boolean = /content.security.policy|\[report only\]/i.test(text);
      if (message.type() === 'error' && !isCspNoise) {
        consoleErrors.push(text);
      }
    });

    await gotoWidgetPage(page);

    const apiResponse: Promise<Response> = waitForSearchResponse(page);
    await page.locator(BLOG.searchInput).fill(FIXTURE_KEYWORD);
    const response: Response = await apiResponse;

    // Regression guard: no 301/Location detour, response stays on the API URL.
    expect(response.status(), 'search API status').toBe(200);
    expect(response.url(), 'search API url').toContain('/api/v1/search');

    await expect(page.locator(BLOG.searchResults)).toHaveClass(/show/);
    await expect(page.locator(BLOG.searchInput)).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator(BLOG.searchResultItem).first()).toBeVisible();
    await expect(page.locator(BLOG.searchResultTitle).first()).toHaveText(FIXTURE_POST_TITLE);
    await expect(page.locator(BLOG.searchResultCount)).toHaveText('Found 1 result(s)');
    await expect(page.locator(BLOG.searchError)).toHaveText('');

    expect(pageErrors, 'uncaught page errors').toEqual([]);
    expect(consoleErrors, 'console errors').toEqual([]);
  });

  test('requests only q and type with GET', async ({ page }) => {
    await gotoWidgetPage(page);

    const apiResponse: Promise<Response> = waitForSearchResponse(page);
    await page.locator(BLOG.searchInput).fill(FIXTURE_KEYWORD);
    const response: Response = await apiResponse;

    const request: Request = response.request();
    expect(request.method()).toBe('GET');

    const url: URL = new URL(response.url());
    expect(url.searchParams.get('q')).toBe(FIXTURE_KEYWORD);
    expect(url.searchParams.get('type')).toBe('all');
    // The widget must not ship the honeypot or any other stray parameter.
    expect(Array.from(url.searchParams.keys()).sort()).toEqual(['q', 'type']);
  });

  test('does not query the API below the two character minimum', async ({ page }) => {
    await gotoWidgetPage(page);

    const apiCalls: string[] = [];
    page.on('request', (request: Request): void => {
      if (request.url().includes('/api/v1/search')) {
        apiCalls.push(request.url());
      }
    });

    await page.locator(BLOG.searchInput).fill('B');
    // Debounce is 300 ms; wait past it before asserting silence.
    await page.waitForTimeout(900);

    expect(apiCalls, 'no API call for a single character').toEqual([]);
    await expect(page.locator(BLOG.searchInput)).toHaveAttribute('aria-expanded', 'false');
    await expect(page.locator(BLOG.searchResults)).not.toHaveClass(/show/);
  });

  test('shows the empty state for a keyword without hits', async ({ page }) => {
    await gotoWidgetPage(page);

    const apiResponse: Promise<Response> = waitForSearchResponse(page);
    await page.locator(BLOG.searchInput).fill(NO_HIT_KEYWORD);
    const response: Response = await apiResponse;

    expect(response.status()).toBe(200);
    await expect(page.locator(BLOG.searchResults)).toHaveClass(/show/);
    await expect(page.locator(BLOG.searchNoResults)).toBeVisible();
    await expect(page.locator(BLOG.searchNoResults)).toContainText(NO_HIT_KEYWORD);
    await expect(page.locator(BLOG.searchError)).toHaveText('');
  });

  test('supports keyboard navigation and Escape dismissal', async ({ page }) => {
    await gotoWidgetPage(page);

    const apiResponse: Promise<Response> = waitForSearchResponse(page);
    await page.locator(BLOG.searchInput).fill(FIXTURE_KEYWORD);
    await apiResponse;
    await expect(page.locator(BLOG.searchResultItem).first()).toBeVisible();

    const firstItem = page.locator(BLOG.searchResultItem).first();

    await page.keyboard.press('ArrowDown');
    await expect(firstItem).toHaveAttribute('aria-selected', 'true');
    await expect(firstItem).toHaveClass(/active/);

    // The combobox owns the interaction: focus must not move onto the link,
    // otherwise the next ArrowDown/Escape is delivered to the <a>.
    await expect(page.locator(BLOG.searchInput)).toBeFocused();
    await expect(page.locator(BLOG.searchInput)).toHaveAttribute(
      'aria-activedescendant',
      'search-results-option-0',
    );

    await page.keyboard.press('Escape');
    await expect(page.locator(BLOG.searchResults)).not.toHaveClass(/show/);
    await expect(page.locator(BLOG.searchInput)).toHaveAttribute('aria-expanded', 'false');
    await expect(page.locator(BLOG.searchInput)).not.toHaveAttribute('aria-activedescendant');
  });

  test('keeps DOM focus on the combobox while navigating suggestions', async ({ page }) => {
    // Regression guard for the accessibility defect where moveActive() called
    // link.focus() and cleared aria-activedescendant: the second ArrowDown,
    // ArrowUp or Escape then reached the suggestion link instead of the
    // combobox, so the highlight stuck on the first hit and Escape could not
    // close the popup.
    await gotoWidgetPage(page);

    const apiResponse: Promise<Response> = waitForSearchResponse(page);
    await page.locator(BLOG.searchInput).fill(KEYBOARD_KEYWORD);
    await apiResponse;

    const items = page.locator(BLOG.searchResultItem);
    await expect(items).toHaveCount(3);

    const input = page.locator(BLOG.searchInput);

    // First hit.
    await page.keyboard.press('ArrowDown');
    await expect(input).toBeFocused();
    await expect(items.nth(0)).toHaveAttribute('aria-selected', 'true');
    await expect(input).toHaveAttribute('aria-activedescendant', 'search-results-option-0');

    // Second hit - the press the old code swallowed.
    await page.keyboard.press('ArrowDown');
    await expect(input).toBeFocused();
    await expect(items.nth(1)).toHaveAttribute('aria-selected', 'true');
    await expect(items.nth(0)).toHaveAttribute('aria-selected', 'false');
    await expect(input).toHaveAttribute('aria-activedescendant', 'search-results-option-1');

    // Back up, then wrap past the first hit to the last one.
    await page.keyboard.press('ArrowUp');
    await expect(input).toBeFocused();
    await expect(items.nth(0)).toHaveAttribute('aria-selected', 'true');

    await page.keyboard.press('ArrowUp');
    await expect(input).toBeFocused();
    await expect(items.nth(2)).toHaveAttribute('aria-selected', 'true');
    await expect(input).toHaveAttribute('aria-activedescendant', 'search-results-option-2');

    // Escape still reaches the combobox after several moves.
    await page.keyboard.press('Escape');
    await expect(input).toBeFocused();
    await expect(page.locator(BLOG.searchResults)).not.toHaveClass(/show/);
    await expect(input).not.toHaveAttribute('aria-activedescendant');
  });

  test('scrolls the active suggestion into view inside the popup', async ({ page }) => {
    // The popup is max-height: 400px / overflow-y: auto. Focus used to scroll
    // the highlight into view for free; with focus on the input the widget has
    // to do it explicitly or keyboard users lose track of the active option.
    await gotoWidgetPage(page);

    const apiResponse: Promise<Response> = waitForSearchResponse(page);
    await page.locator(BLOG.searchInput).fill(KEYBOARD_KEYWORD);
    await apiResponse;
    await expect(page.locator(BLOG.searchResultItem).first()).toBeVisible();

    // Squeeze the popup to roughly one hit so the third suggestion cannot be
    // visible without scrolling.
    const items = page.locator(BLOG.searchResultItem);
    const itemHeight: number = await items.first().evaluate(
      (element: HTMLElement): number => element.getBoundingClientRect().height,
    );
    await page.locator(BLOG.searchResults).evaluate((element: HTMLElement, height: number): void => {
      element.style.maxHeight = `${height + 2}px`;
    }, itemHeight);

    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('ArrowDown');
    await expect(items.nth(2)).toHaveAttribute('aria-selected', 'true');

    const visible: boolean = await items.nth(2).evaluate((element: HTMLElement): boolean => {
      const rect: DOMRect = element.getBoundingClientRect();
      const container: DOMRect = (element.parentElement as HTMLElement).getBoundingClientRect();
      return rect.top >= container.top - 1 && rect.bottom <= container.bottom + 1;
    });
    expect(visible, 'active suggestion scrolled into view').toBe(true);
  });

  test('Enter on a highlighted suggestion opens the post', async ({ page }) => {
    await gotoWidgetPage(page);

    const apiResponse: Promise<Response> = waitForSearchResponse(page);
    await page.locator(BLOG.searchInput).fill(FIXTURE_KEYWORD);
    await apiResponse;
    await expect(page.locator(BLOG.searchResultItem).first()).toBeVisible();

    const href: string | null = await page
      .locator(BLOG.searchResultLink)
      .first()
      .getAttribute('href');
    expect(href).toContain(`p=${FIXTURE_POST_ID}`);

    await page.keyboard.press('ArrowDown');
    await Promise.all([
      page.waitForURL((url: URL): boolean => url.searchParams.get('p') === String(FIXTURE_POST_ID)),
      page.keyboard.press('Enter'),
    ]);

    await expect(page.locator(BLOG.postSingleTitle)).toContainText(FIXTURE_POST_TITLE);
  });

  test('submitting without a highlight opens the full search page', async ({ page }) => {
    await gotoWidgetPage(page);

    const apiResponse: Promise<Response> = waitForSearchResponse(page);
    await page.locator(BLOG.searchInput).fill(FIXTURE_KEYWORD);
    await apiResponse;
    await expect(page.locator(BLOG.searchResultItem).first()).toBeVisible();

    await Promise.all([
      page.waitForURL((url: URL): boolean => url.searchParams.get('q') === FIXTURE_KEYWORD),
      page.locator(`${BLOG.searchForm} button[type="submit"]`).click(),
    ]);

    await expect(page.locator(BLOG.searchResultsPage)).toBeVisible();
  });

  test('clear button resets the widget', async ({ page }) => {
    await gotoWidgetPage(page);

    const clearButton = page.locator(BLOG.searchClear);
    await expect(clearButton).toBeHidden();

    const apiResponse: Promise<Response> = waitForSearchResponse(page);
    await page.locator(BLOG.searchInput).fill(FIXTURE_KEYWORD);
    await apiResponse;
    await expect(page.locator(BLOG.searchResults)).toHaveClass(/show/);
    await expect(clearButton).toBeVisible();

    await clearButton.click();

    await expect(page.locator(BLOG.searchInput)).toHaveValue('');
    await expect(page.locator(BLOG.searchResults)).not.toHaveClass(/show/);
    await expect(page.locator(BLOG.searchInput)).toHaveAttribute('aria-expanded', 'false');
    await expect(page.locator(BLOG.searchError)).toHaveText('');
    await expect(clearButton).toBeHidden();
  });
});
