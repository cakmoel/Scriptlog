// @ts-check
// rb-single.spec.js
// Single-post anatomy on RestoBlog: title/meta rendering, prev/next adjacency
// across the three seeded posts (ordered by ID in findAdjacentPost), the tag
// link, and the comment surface (fetch-comments wiring + the native-HTML5
// comment form contract - this theme has no jQuery validation handler).
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  RB_TAG,
  dbScalar,
  seedRestoblogFixtures,
} from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

/** @param {string} name */
function rbSlug(name) {
  return `rb-e2e-${name}`;
}

/** @param {string} name @returns {string} seeded post id for the given trio member */
function seededId(name) {
  return dbScalar(`SELECT ID FROM tbl_posts WHERE post_slug = '${rbSlug(name)}'`);
}

test.describe('restoblog single post', () => {
  test.beforeAll(() => {
    seedRestoblogFixtures();
  });

  test('seeded middle post renders full anatomy', async ({ page }) => {
    const middle = seededId('middle');
    const response = await page.goto(`${BASE_URL}/?p=${middle}`);
    expect(response?.status()).toBe(200);
    await expect(page.locator(RB.postDetail)).toBeVisible();
    await expect(page.locator(RB.postTitle).first()).toContainText('RestoBlog E2E Middle');
    await expect(page.locator(RB.postContent)).toBeVisible();
  });

  test('prev/next navigation links adjacent seeded posts in both directions', async ({ page }) => {
    const oldest = seededId('oldest');
    const middle = seededId('middle');
    const newest = seededId('newest');

    // Middle post is wedged between oldest and newest by ID (the three rows are
    // consecutive AUTO_INCREMENT values), so both directions are deterministic.
    await page.goto(`${BASE_URL}/?p=${oldest}`);
    await expect(page.locator(RB.postNav)).toBeVisible();
    const nextHref = await page.locator(RB.nextPostLink).getAttribute('href');
    expect(nextHref).toContain(`p=${middle}`);

    await page.goto(`${BASE_URL}/?p=${middle}`);
    expect(await page.locator(RB.prevPostLink).getAttribute('href')).toContain(`p=${oldest}`);
    expect(await page.locator(RB.nextPostLink).getAttribute('href')).toContain(`p=${newest}`);

    await page.goto(`${BASE_URL}/?p=${newest}`);
    const prevHref = await page.locator(RB.prevPostLink).getAttribute('href');
    expect(prevHref).toContain(`p=${middle}`);
  });

  test('tag links on the seeded post route back to the tag listing', async ({ page }) => {
    const middle = seededId('middle');
    await page.goto(`${BASE_URL}/?p=${middle}`);
    const tagLink = page.locator(`a[href*="tag=${RB_TAG}"]`).first();
    await expect(tagLink).toBeAttached();
    const tagHref = await tagLink.getAttribute('href');
    expect(tagHref).toContain(`tag=${RB_TAG}`);
  });

  test('comments container wires fetch-comments and load-more to the post', async ({ page }) => {
    const middle = seededId('middle');
    const fetchPromise = page.waitForResponse(
      (r) => r.url().includes('/fetch-comments.php') && r.url().includes(`post_id=${middle}`),
    );
    await page.goto(`${BASE_URL}/?p=${middle}`);
    const fetchResponse = await fetchPromise;
    expect(fetchResponse.ok()).toBeTruthy();

    await expect(page.locator(RB.commentsSection)).toBeAttached();
    await expect(page.locator(RB.commentsList)).toHaveAttribute('data-post-id', middle);
    const settings = await page.evaluate(() => window.CommentSettings);
    expect(String(settings.postId)).toBe(middle);
    await expect(page.locator(RB.loadMoreButton)).toBeAttached();
  });

  test('comment form exposes required fields, csrf token and the comments-post action', async ({ page }) => {
    const middle = seededId('middle');
    await page.goto(`${BASE_URL}/?p=${middle}`);

    await expect(page.locator(RB.commentForm)).toBeVisible();
    await expect(page.locator(RB.commentTextarea)).toHaveAttribute('required', '');
    await expect(page.locator(RB.commentName)).toHaveAttribute('required', '');
    await expect(page.locator(RB.commentEmail)).toHaveAttribute('required', '');
    const csrf = await page.locator(RB.commentCsrf).inputValue();
    expect(csrf).not.toBe('');
    const action = await page.locator(RB.commentForm).getAttribute('action');
    expect(action).toContain('comments-post.php');
  });
});