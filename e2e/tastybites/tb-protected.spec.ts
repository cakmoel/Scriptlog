// tb-protected.spec.ts
// Password-protected post unlock flow on TastyBites: password box hides the
// content, a wrong password surfaces the error box (401 from the unlock API),
// and the correct password reveals the seeded secret and survives a reload via
// the session. The file-backed unlock rate limiter is cleared before the run so
// earlier attempts can never poison assertions.
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  TB_PROTECTED_PASSWORD,
  TB_PROTECTED_SECRET,
  clearUnlockRateLimit,
  dbScalar,
  seedTastybitesFixtures,
} from './tastybites-fixtures';
import { TB } from './tb-selectors';

test.describe('tastybites protected post', () => {
  let protectedId = '0';

  test.beforeAll(() => {
    seedTastybitesFixtures();
    clearUnlockRateLimit();
    protectedId = dbScalar(
      "SELECT ID FROM tbl_posts WHERE post_slug = 'tb-e2e-locked'",
    );
  });

  test('protected post shows password form and hides secret content', async ({ page }) => {
    await page.goto(`${BASE_URL}/?p=${protectedId}`);
    await expect(page.locator(TB.passwordBoxPrefix)).toBeVisible();
    await expect(page.locator(TB.unlockInput)).toBeVisible();
    await expect(page.locator(TB.unlockedContentPrefix)).toBeHidden();
    const content = await page.content();
    expect(content).not.toContain(TB_PROTECTED_SECRET);
  });

  test('wrong password shows error and keeps content locked', async ({ page }) => {
    await page.goto(`${BASE_URL}/?p=${protectedId}`);
    const responsePromise = page.waitForResponse(
      (r) => r.url().includes(`/api/v1/posts/${protectedId}/unlock`) && r.request().method() === 'POST',
    );
    await page.locator(TB.unlockInput).fill('definitely-wrong-password');
    await page.locator(TB.unlockButton).click();
    const response = await responsePromise;
    expect(response.status()).toBe(401);
    await expect(page.locator(TB.unlockError)).toBeVisible();
    await expect(page.locator(TB.unlockedContentPrefix)).toBeHidden();
  });

  test('correct password reveals secret content and persists after reload', async ({ page }) => {
    await page.goto(`${BASE_URL}/?p=${protectedId}`);
    const responsePromise = page.waitForResponse(
      (r) => r.url().includes(`/api/v1/posts/${protectedId}/unlock`) && r.request().method() === 'POST',
    );
    await page.locator(TB.unlockInput).fill(TB_PROTECTED_PASSWORD);
    await page.locator(TB.unlockButton).click();
    const response = await responsePromise;
    expect(response.ok()).toBeTruthy();

    await expect(page.locator(TB.passwordBoxPrefix)).toBeHidden();
    await expect(page.locator(TB.unlockedContentPrefix)).toContainText(TB_PROTECTED_SECRET);
  });

  test('unlock state persists across a reload via the session', async ({ page }) => {
    // Fixed (plan runtime finding R7): SessionMaker fell back to an empty cookie
    // path ('session.cookies_path' ini is unset), so the unlock endpoint's
    // session cookie was scoped to /api/v1/posts/<id>/ and never reached the
    // frontend. The path now falls back to session.cookie_path ('/'), letting
    // single.php resolve $_SESSION['unlocked_posts'] and decrypt server-side.
    await page.goto(`${BASE_URL}/?p=${protectedId}`);
    const responsePromise = page.waitForResponse(
      (r) => r.url().includes(`/api/v1/posts/${protectedId}/unlock`) && r.request().method() === 'POST',
    );
    await page.locator(TB.unlockInput).fill(TB_PROTECTED_PASSWORD);
    await page.locator(TB.unlockButton).click();
    const response = await responsePromise;
    expect(response.ok()).toBeTruthy();

    await page.reload();
    // Server-side unlock branch renders the decrypted content directly in
    // .post-body (the AJAX container only exists in the locked markup).
    await expect(page.locator('[id^="password-protected-"]')).toHaveCount(0);
    await expect(page.locator('.post-body')).toContainText(TB_PROTECTED_SECRET);
  });
});