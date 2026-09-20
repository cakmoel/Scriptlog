// theme-protected.spec.ts
// Password-protected post unlock flow: form shows, wrong password errors, correct password
// reveals content, and the rate limiter engages after 5 failed attempts.
import { test, expect } from '@playwright/test';
import type { APIRequestContext, APIResponse } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { BLOG, BLOG_BASE_URL } from './blog-selectors';

const BASE_URL: string = BLOG_BASE_URL;
const PROTECTED_POST: number = 12;
const SECRET_TEXT: string =
  'This is the secret content of a protected e2e post.';
const RATE_LIMIT_DIR: string =
  '/var/www/blogware/public_html/public/log/unlock_attempts';
const RETRIES: number = 3;

/** Unlock API success payload. */
type UnlockSuccessBody = {
  success: boolean;
  data: { content: string };
};

/** Unlock API error payload. */
type UnlockErrorBody = {
  success: boolean;
  error: { message: string };
};

/**
 * Delete every file-backed rate-limit counter.
 *
 * All three browser projects share the single dev server and its file-backed
 * unlock rate limiter (keyed by md5(ip_post)). Running the projects in parallel
 * therefore races on that shared state; each test clears it before and retries
 * on interference so the assertions stay deterministic.
 */
function clearRateLimit(): void {
  if (!fs.existsSync(RATE_LIMIT_DIR)) return;
  for (const f of fs.readdirSync(RATE_LIMIT_DIR)) {
    fs.rmSync(path.join(RATE_LIMIT_DIR, f), { force: true });
  }
}

/**
 * POST the unlock endpoint, tolerating rate-limit state left by a parallel
 * project. Returns the response that matches the expected status.
 *
 * @param request Playwright API request context.
 * @param password Password to submit to the unlock endpoint.
 * @param expectedStatus HTTP status we are waiting for.
 * @returns The matching API response.
 */
async function unlockWithRetry(
  request: APIRequestContext,
  password: string,
  expectedStatus: number,
): Promise<APIResponse> {
  let last: APIResponse | null = null;
  for (let i = 0; i < RETRIES; i++) {
    clearRateLimit();
    const res: APIResponse = await request.post(
      `${BASE_URL}/api/v1/posts/${PROTECTED_POST}/unlock`,
      { data: { password } },
    );
    if (res.status() === expectedStatus) return res;
    last = res;
  }
  return last as APIResponse;
}

test.describe('theme protected posts', () => {
  test('protected post shows password form and hides content', async ({
    page,
  }) => {
    const response = await page.goto(`${BASE_URL}/?p=${PROTECTED_POST}`);
    expect(response?.status()).toBe(200);
    await expect(page.locator(BLOG.unlockForm)).toBeVisible();
    const content: string = await page.content();
    expect(content).not.toContain(SECRET_TEXT);
    // The reveal container exists in the DOM but must stay hidden until unlock.
    await expect(
      page.locator(`#unlocked-content-${PROTECTED_POST}`),
    ).toBeHidden();
  });

  test('wrong password via API returns 401 and reveals nothing', async ({
    request,
  }) => {
    const res: APIResponse = await unlockWithRetry(
      request,
      'definitely-wrong',
      401,
    );
    expect(res.status()).toBe(401);
    const body = (await res.json()) as UnlockErrorBody;
    expect(body.success).toBe(false);
    expect(body.error.message).toBe('Incorrect password');
  });

  test('correct password unlocks and reveals the secret content', async ({
    page,
  }) => {
    for (let i = 0; i < RETRIES; i++) {
      clearRateLimit();
      await page.goto(`${BASE_URL}/?p=${PROTECTED_POST}`);
      await page.locator(BLOG.unlockInput).fill('e2e-secret');
      await page.locator(BLOG.unlockButton).click();
      const revealed: boolean = await page
        .locator('#unlocked-content-12')
        .isVisible();
      if (revealed) break;
    }
    await expect(page.locator('#unlocked-content-12')).toBeVisible({
      timeout: 10000,
    });
    await expect(page.locator('#unlocked-content-12')).toContainText(
      SECRET_TEXT,
    );
  });

  test('correct password via API returns 200 with content', async ({
    request,
  }) => {
    const res: APIResponse = await unlockWithRetry(request, 'e2e-secret', 200);
    expect(res.status()).toBe(200);
    const body = (await res.json()) as UnlockSuccessBody;
    expect(body.success).toBe(true);
    expect(body.data.content).toContain(SECRET_TEXT);
  });

  test('five failed attempts trigger the rate limit message', async ({
    page,
    request,
  }) => {
    // Warm the app / auth plumbing once before hammering the endpoint.
    await request.get(`${BASE_URL}/`);
    let limited: APIResponse | null = null;
    for (let attempt = 0; attempt < RETRIES; attempt++) {
      clearRateLimit();
      for (let i = 0; i < 5; i++) {
        const res: APIResponse = await request.post(
          `${BASE_URL}/api/v1/posts/${PROTECTED_POST}/unlock`,
          { data: { password: `wrong-${i}` } },
        );
        expect(res.status()).toBe(401);
      }
      limited = await request.post(
        `${BASE_URL}/api/v1/posts/${PROTECTED_POST}/unlock`,
        { data: { password: 'e2e-secret' } },
      );
      if (limited.status() === 429) break;
    }
    expect(limited?.status()).toBe(429);
    const body = (await (limited as APIResponse).json()) as UnlockErrorBody;
    expect(body.error.message).toContain('Too many failed attempts');
    // Keep the page fixture referenced so the signature stays honest.
    await expect(page).toBeTruthy();
  });
});
