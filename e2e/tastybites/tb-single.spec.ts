// tb-single.spec.ts
// Single-post anatomy on TastyBites: title/meta rendering, prev/next adjacency
// across the three seeded posts, and the comment surface (load-more wiring plus
// the rendered form contract). Comment row-count assertions tolerate the known
// double-handler hazard (plan finding G3) by asserting visibility of the
// success message rather than an exact insert count.
import { test, expect, type Dialog } from '@playwright/test';
import {
  BASE_URL,
  TB_TAG,
  dbScalar,
  seedTastybitesFixtures,
} from './tastybites-fixtures';
import { TB } from './tb-selectors';

function tbSlug(name: string): string {
  return `tb-e2e-${name}`;
}

/** Seeded post id for the given trio member. */
function seededId(name: string): string {
  return dbScalar(`SELECT ID FROM tbl_posts WHERE post_slug = '${tbSlug(name)}'`);
}

test.describe('tastybites single post', () => {
  test.beforeAll(() => {
    seedTastybitesFixtures();
  });

  test('seeded middle post renders full anatomy', async ({ page }) => {
    const middle = seededId('middle');
    const response = await page.goto(`${BASE_URL}/?p=${middle}`);
    expect(response?.status()).toBe(200);
    await expect(page.locator(TB.articleRole)).toBeVisible();
    await expect(page.locator(`${TB.articleRole} h1`).first()).toContainText('TastyBites E2E Middle');
    await expect(page.locator(TB.postSingle).first()).toBeVisible();
  });

  test('prev/next navigation links adjacent seeded posts in both directions', async ({ page }) => {
    const oldest = seededId('oldest');
    const middle = seededId('middle');
    const newest = seededId('newest');

    // Oldest post: next link points at the adjacent middle post (?p=<id> hrefs
    // in query mode; runtime finding R1 - prev/next use ?p=ID rather than slugs).
    await page.goto(`${BASE_URL}/?p=${oldest}`);
    await expect(page.locator(TB.postsNav)).toBeVisible();
    const nextHref = await page.locator(TB.nextPostLink).getAttribute('href');
    expect(nextHref).toContain(`p=${middle}`);

    // Newest post: prev link points at middle.
    await page.goto(`${BASE_URL}/?p=${newest}`);
    const prevHref = await page.locator(TB.prevPostLink).getAttribute('href');
    expect(prevHref).toContain(`p=${middle}`);
  });

  test('tag links on the seeded post route back to the tag listing', async ({ page }) => {
    const middle = seededId('middle');
    await page.goto(`${BASE_URL}/?p=${middle}`);
    // Filter on the archive-style href: single.php also renders one malformed
    // tag anchor whose href is the post permalink (runtime finding R5), so title alone is
    // ambiguous - exactly one anchor carries a ?tag= URL.
    const tagLink = page.locator(`a.tag[href*="tag=${TB_TAG}"]`).first();
    await expect(tagLink).toBeAttached();
    const tagHref = await tagLink.getAttribute('href');
    expect(tagHref).toContain(`tag=${TB_TAG}`);
  });

  test('comments container wires load-more to fetch-comments endpoint', async ({ page }) => {
    const middle = seededId('middle');
    await page.goto(`${BASE_URL}/?p=${middle}`);
    await expect(page.locator(TB.commentsSection)).toBeAttached();
    const settings = await page.evaluate(() => window.CommentSettings);
    expect(String(settings?.postId)).toBe(middle);
    await expect(page.locator(TB.loadMoreButton)).toBeAttached();
  });

  test('comment form exposes required fields, csrf token and client validation', async ({ page }) => {
    const middle = seededId('middle');
    await page.goto(`${BASE_URL}/?p=${middle}`);

    await expect(page.locator(TB.commentForm)).toBeVisible();
    await expect(page.locator(TB.commentTextarea)).toBeVisible();
    await expect(page.locator(TB.commentName)).toBeVisible();
    await expect(page.locator(TB.commentEmail)).toBeVisible();
    const csrf = await page.locator(`${TB.commentForm} input[name="csrf"]`).inputValue();
    expect(csrf).not.toBe('');

    // Client-side validation: empty submit must not leave the page nor succeed.
    // The validation lives in a jQuery submit handler bound on DOMContentLoaded;
    // under heavy parallel load WebKit can click before the binding lands,
    // which would navigate natively instead - so wait for the handler first.
    await page.waitForFunction(() => {
      const jq = window.jQuery;
      if (!jq) {
        return false;
      }
      const form = document.querySelector('#commentForm');
      const events = form ? jq._data?.(form, 'events') : null;
      return Boolean(events && events.submit);
    });
    // Both submit handlers fire on the empty submit (plan finding G3): the
    // front.js duplicate AJAX-POSTs an empty payload to /api/v1/comments and
    // calls alert() from the 422 error callback. The .is-invalid classes come
    // from comment-submission.js; the alert from front.js is async, so the
    // test can otherwise finish with a pending dialog and tear-down mid-alert
    // ("Page.handleJavaScriptDialog / session closed" flake). Keep the test
    // alive until that dialog is actually dismissed.
    let markDialogHandled: (() => void) | undefined;
    const dialogHandled = new Promise<void>((resolve) => {
      markDialogHandled = resolve;
    });
    const dialogHandler = (dialog: Dialog): void => {
      void dialog.dismiss().finally(() => markDialogHandled?.());
    };
    page.on('dialog', dialogHandler);
    const commentApi = page.waitForResponse(
      (r) => r.url().includes('/api/v1/comments') && r.request().method() === 'POST',
    );
    await page.locator(TB.commentForm).getByRole('button').click();
    await expect(
      page.locator('#comment.is-invalid, #comment ~ .help-block, .invalid-feedback').first(),
    ).toBeVisible();
    // Document finding G3: the duplicate handler's request still fails
    // validation (422) even though comment-submission.js blocked the insert.
    const apiResponse = await commentApi;
    expect(apiResponse.status()).toBe(422);
    await Promise.race([dialogHandled, page.waitForTimeout(1500)]);
    await page.off('dialog', dialogHandler);
  });
});