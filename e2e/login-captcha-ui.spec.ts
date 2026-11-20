// login-captcha-ui.spec.ts
// Browser end-to-end verification that the admin login CAPTCHA keeps working
// after the captcha-login.php hardening (CSPRNG code generation + visual
// obfuscation). Guards the UI/UX contract from specs/admin-login.md:
//   CP-01 - the CAPTCHA image renders as a valid JPEG in the browser and stays
//           inside a layout-safe size envelope after randomized obfuscation.
//   CP-02/RL-02 - a wrong code surfaces the server error and the CAPTCHA
//           remains visible on the re-rendered login form.
//
// NOTE: adminLogin does not complete in webkit in this dev env (pre-existing,
// affects all admin e2e specs), so this spec skips the webkit project.
import { test, expect } from '@playwright/test';
import {
  ADMIN_PASS,
  ADMIN_USER,
  BASE_URL,
  clearLoginAttempts,
  clearRateLimiters,
  runSql,
} from './gdpr-fixtures';

const CAPTCHA_URL: string = `${BASE_URL}/admin/captcha-login.php`;
const CAPTCHA_THRESHOLD_SEED_SQL: string =
  'INSERT INTO tbl_login_attempt (ip_address, login_date) VALUES ' +
  "('127.0.0.1', NOW()), ('127.0.0.1', NOW()), ('127.0.0.1', NOW()), " +
  "('127.0.0.1', NOW()), ('127.0.0.1', NOW());";

async function expectLayoutSafeCaptcha(page: import('@playwright/test').Page): Promise<void> {
  const image = page.locator('img[alt="captcha"]');
  await expect(image).toBeVisible();
  await expect(image).toHaveAttribute('src', /captcha-login\.php$/);

  const dimensions = await image.evaluate(
    (el): { width: number; height: number } => {
      const element = el as HTMLImageElement;
      return { width: element.naturalWidth, height: element.naturalHeight };
    },
  );
  // Obfuscation randomizes dimensions, palette, position, font and rotation.
  // The rendered image must still fit the login form without shifting layout.
  expect(dimensions.width).toBeGreaterThan(0);
  expect(dimensions.width).toBeLessThan(140);
  expect(dimensions.height).toBeGreaterThan(0);
  expect(dimensions.height).toBeLessThan(100);
}

test.describe.serial('admin login captcha UI', () => {
  test.beforeEach(({ browserName, page }): void => {
    test.skip(
      browserName === 'webkit',
      'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
    );
    clearRateLimiters();
    page.setDefaultTimeout(15000);
    page.setDefaultNavigationTimeout(15000);
  });

  test.afterAll((): void => {
    clearLoginAttempts();
    clearRateLimiters();
  });

  test('captcha endpoint renders as a valid, layout-safe JPEG in the browser', async ({
    page,
  }) => {
    const response = await page.goto(CAPTCHA_URL, { waitUntil: 'load' });

    expect(response).not.toBeNull();
    expect(response!.status()).toBe(200);
    expect(response!.headers()['content-type']).toContain('image/jpeg');

    const image = page.locator('img');
    await expect(image).toBeVisible();
    const dimensions = await image.evaluate(
      (el): { width: number; height: number } => {
        const element = el as HTMLImageElement;
        return { width: element.naturalWidth, height: element.naturalHeight };
      },
    );
    expect(dimensions.width).toBeGreaterThan(0);
    expect(dimensions.width).toBeLessThan(140);
    expect(dimensions.height).toBeGreaterThan(0);
    expect(dimensions.height).toBeLessThan(100);
  });

  test('a reload presents a freshly rendered captcha', async ({ page }) => {
    await page.goto(CAPTCHA_URL, { waitUntil: 'load' });
    const first = await page.screenshot();

    await page.reload({ waitUntil: 'load' });
    const second = await page.screenshot();

    expect(Buffer.compare(first, second)).not.toBe(0);
  });

  test('wrong captcha keeps the re-rendered login form usable', async ({
    page,
  }) => {
    clearLoginAttempts();
    await page.goto(`${BASE_URL}/admin/login.php`, {
      waitUntil: 'domcontentloaded',
    });
    await expect(page.locator('#inputCaptcha')).toHaveCount(0);

    // The form is rendered on a GET (below threshold) without a CAPTCHA field.
    // Seed the threshold, then submit the real form with a wrong code; the
    // POST response is where login.php renders the CAPTCHA field and image.
    clearLoginAttempts();
    runSql(CAPTCHA_THRESHOLD_SEED_SQL);
    await page.evaluate((): void => {
      const form = document.querySelector(
        'form[name="formlogin"]',
      ) as HTMLFormElement;
      const input = document.createElement('input');
      input.type = 'text';
      input.name = 'captcha_login';
      input.value = '000000';
      form.appendChild(input);
    });
    await page.locator('#inputLogin').fill(ADMIN_USER);
    await page.locator('#inputPassword').fill(ADMIN_PASS);
    await page.locator('input[name="LogIn"]').click();

    await expect(page.locator('.alert-danger')).toContainText(
      'Incorrect Captcha code.',
    );
    await expect(page.locator('#inputCaptcha')).toBeVisible();
    await expectLayoutSafeCaptcha(page);
  });
});
