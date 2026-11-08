// @ts-check
// rb-protected.spec.js
// Password-protected post unlock flow on RestoBlog: password box hides the
// content, a wrong password surfaces the error box (401 from the unlock API),
// and the correct password reveals the seeded secret and survives a reload via
// the session. The file-backed unlock rate limiter is cleared before the run so
// earlier attempts can never poison assertions.
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  RB_PROTECTED_PASSWORD,
  RB_PROTECTED_SECRET,
  clearUnlockRateLimit,
  dbScalar,
  seedRestoblogFixtures,
} from './restoblog-fixtures.js';
import { RB } from './rb-selectors.js';

test.describe('restoblog protected post', () => {
  let protectedId = '0';

  test.beforeAll(() => {
    seedRestoblogFixtures();
    clearUnlockRateLimit();
    protectedId = dbScalar(
      "SELECT ID FROM tbl_posts WHERE post_slug = 'rb-e2e-locked'",
    );
  });

  test('protected post shows password form and hides secret content', async ({ page }) => {
    await page.goto(`${BASE_URL}/?p=${protectedId}`);
    await expect(page.locator(RB.passwordBoxPrefix)).toBeVisible();
    await expect(page.locator(RB.unlockInput)).toBeVisible();
    await expect(page.locator(RB.unlockedContentPrefix)).toBeHidden();
    const content = await page.content();
    expect(content).not.toContain(RB_PROTECTED_SECRET);
  });

  test('wrong password shows error and keeps content locked', async ({ page }) => {
    await page.goto(`${BASE_URL}/?p=${protectedId}`);
    const responsePromise = page.waitForResponse(
      (r) => r.url().includes(`/api/v1/posts/${protectedId}/unlock`) && r.request().method() === 'POST',
    );
    await page.locator(RB.unlockInput).fill('definitely-wrong-password');
    await page.locator(RB.unlockButton).click();
    const response = await responsePromise;
    expect(response.status()).toBe(401);
    await expect(page.locator(RB.unlockError)).toBeVisible();
    await expect(page.locator(RB.unlockedContentPrefix)).toBeHidden();
  });

  test('correct password reveals secret content via the unlock API', async ({ page }) => {
    await page.goto(`${BASE_URL}/?p=${protectedId}`);
    const responsePromise = page.waitForResponse(
      (r) => r.url().includes(`/api/v1/posts/${protectedId}/unlock`) && r.request().method() === 'POST',
    );
    await page.locator(RB.unlockInput).fill(RB_PROTECTED_PASSWORD);
    await page.locator(RB.unlockButton).click();
    const response = await responsePromise;
    expect(response.ok()).toBeTruthy();

    await expect(page.locator(RB.passwordBoxPrefix)).toBeHidden();
    await expect(page.locator(RB.unlockedContentPrefix)).toContainText(RB_PROTECTED_SECRET);
  });

  test('unlock state persists across a reload via the session', async ({ page }) => {
    await page.goto(`${BASE_URL}/?p=${protectedId}`);
    const responsePromise = page.waitForResponse(
      (r) => r.url().includes(`/api/v1/posts/${protectedId}/unlock`) && r.request().method() === 'POST',
    );
    await page.locator(RB.unlockInput).fill(RB_PROTECTED_PASSWORD);
    await page.locator(RB.unlockButton).click();
    const response = await responsePromise;
    expect(response.ok()).toBeTruthy();

    await page.reload();
    // Server-side unlock branch renders the decrypted content directly in
    // .post-detail-content (the AJAX container only exists in locked markup).
    await expect(page.locator('[id^="password-protected-"]')).toHaveCount(0);
    await expect(page.locator(RB.postContent)).toContainText(RB_PROTECTED_SECRET);
  });
});