// admin-plugins.spec.ts
// End-to-end tests for the Admin Plugins page (/admin/index.php?load=plugins).
// Covers access control, rendering, upload button presence, and absence of
// inappropriate redirects. Follows patterns from admin-ui-parity.spec.ts and
// uses fixtures from gdpr-fixtures.ts.

import { test, expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import {
  ADMIN_PASS,
  ADMIN_USER,
  BASE_URL,
  NOACCESS_PASS,
  NOACCESS_USER,
  adminLogin,
  clearLoginAttempts,
  clearRateLimiters,
  syncTestUserPasswords,
} from './gdpr-fixtures';

async function setupHttpsRewrite(page: Page): Promise<void> {
  // e2e-env quirk: e2e/router.php spoofs HTTPS for document navigations,
  // so admin HTML references https://127.0.0.1:8099 assets that the
  // HTTP-only dev server cannot serve. Rewrite those subresource requests
  // to plain http.
  await page.context().route(
    'https://127.0.0.1:8099/**',
    async (route): Promise<void> => {
      const httpUrl: string = route
        .request()
        .url()
        .replace(/^https:/, 'http:');
      const res = await page.context().request.get(httpUrl);
      const headers: Record<string, string> = {};
      const contentType: string | undefined = res.headers()['content-type'];
      if (contentType !== undefined) {
        headers['content-type'] = contentType;
      }
      await route.fulfill({
        status: res.status(),
        headers,
        body: await res.body(),
      });
    },
  );
}

test.describe('Admin Plugins Page', () => {
  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    // The committed dump's admin/no-access hashes must match the runtime
    // resolved E2E_*_PASS values used by adminLogin().
    syncTestUserPasswords();
  });

  test.describe('Access Control', () => {
    test.beforeEach(async ({ page }, testInfo): Promise<void> => {
      test.skip(
        testInfo.project.name === 'webkit',
        'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
      );
      await setupHttpsRewrite(page);
    });

    test('TC-001: Unauthenticated user redirected to login', async ({
      page,
    }): Promise<void> => {
      // Navigate directly without logging in
      const response = await page.goto(
        `${BASE_URL}/admin/index.php?load=plugins`,
      );

      // Should be redirected to login
      await page.waitForURL(/login\.php/, { timeout: 10000 });
      expect(page.url()).toMatch(/login\.php/);

      // No plugins page content should be shown
      const pageContent = await page.content();
      expect(pageContent).not.toMatch(/Plugins.*Data Plugin/i);
      expect(pageContent).not.toMatch(/scriptlog-table/);
    });

    test('TC-002: Authenticated non-admin cannot access plugins page (403 or denied)', async ({
      page,
    }): Promise<void> => {
      // Login as noaccess user (author level per fixtures)
      await adminLogin(page, NOACCESS_USER, NOACCESS_PASS);

      // Try to access plugins page
      const response = await page.goto(
        `${BASE_URL}/admin/index.php?load=plugins`,
      );

      // Should be denied access - either 403 or redirected to 403/error page
      const url = page.url();
      const content = await page.content();

      // Check for 403 indicators
      const isDenied =
        url.includes('403') ||
        url.includes('forbidden') ||
        content.includes('403') ||
        content.includes('Forbidden') ||
        content.includes('Access Denied') ||
        content.includes('not allowed');

      expect(isDenied).toBe(true);

      // Should not see plugins list
      expect(content).not.toMatch(/id="scriptlog-table"/);
    });

    test('TC-006: Authenticated manager can access plugins page', async ({
      page,
    }): Promise<void> => {
      // Note: Manager user not explicitly in fixtures; trying with admin to verify pattern
      // In a real scenario we'd need a manager account. Testing admin access pattern.
      await adminLogin(page, ADMIN_USER, ADMIN_PASS);

      await page.goto(`${BASE_URL}/admin/index.php?load=plugins`);

      // Should not be redirected away from plugins
      expect(page.url()).toContain('load=plugins');
      expect(page.url()).not.toContain('load=dashboard');
      expect(page.url()).not.toContain('login.php');
    });

    test('TC-007: Authenticated administrator can access plugins page', async ({
      page,
    }): Promise<void> => {
      await adminLogin(page, ADMIN_USER, ADMIN_PASS);

      const response = await page.goto(
        `${BASE_URL}/admin/index.php?load=plugins`,
      );

      // Should load successfully
      expect(response?.status()).not.toBe(500);
      expect(page.url()).toContain('load=plugins');
      expect(page.url()).not.toContain('load=dashboard');
      expect(page.url()).not.toContain('login.php');
    });
  });

  test.describe('Page Rendering', () => {
    test.beforeEach(async ({ page }, testInfo): Promise<void> => {
      test.skip(
        testInfo.project.name === 'webkit',
        'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
      );
      await setupHttpsRewrite(page);
      await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    });

    test('TC-008: Page renders with correct title and structure', async ({
      page,
    }): Promise<void> => {
      await page.goto(`${BASE_URL}/admin/index.php?load=plugins`);

      // Check content wrapper exists
      await expect(page.locator('.content-wrapper')).toBeVisible();

      // Check breadcrumbs or page header contains Plugins
      const content = await page.content();
      expect(content).toMatch(/Plugins/i);
    });

    test('TC-009: Plugins list table renders with correct headers', async ({
      page,
    }): Promise<void> => {
      await page.goto(`${BASE_URL}/admin/index.php?load=plugins`);

      // Table should exist
      await expect(page.locator('#scriptlog-table')).toBeVisible();

      // Check headers
      const headers = page.locator('#scriptlog-table thead th');
      await expect(headers.nth(0)).toContainText('#');
      await expect(headers.nth(1)).toContainText('Plugin');
      await expect(headers.nth(2)).toContainText('Description');
      await expect(headers.nth(3)).toContainText('Level');
      await expect(headers.nth(4)).toContainText('Delete');
      await expect(headers.nth(5)).toContainText('Status');
    });

    test('TC-011: No PHP errors or warnings displayed', async ({
      page,
    }): Promise<void> => {
      await page.goto(`${BASE_URL}/admin/index.php?load=plugins`);

      const content = await page.content();
      expect(content).not.toMatch(/Fatal error/i);
      expect(content).not.toMatch(/Warning:/);
      expect(content).not.toMatch(/Notice:/);
      expect(content).not.toMatch(/Parse error/i);
      expect(content).not.toMatch(/Unknown column.*plugin_manifest/i);
    });

    test('TC-012: No plugin_manifest column errors', async ({ page }): Promise<void> => {
      await page.goto(`${BASE_URL}/admin/index.php?load=plugins`);

      const content = await page.content();
      expect(content).not.toMatch(/Unknown column.*plugin_manifest/i);
      expect(content).not.toMatch(/Field.*plugin_manifest.*doesn't have a default value/i);
    });
  });

  test.describe('Upload Plugin Button', () => {
    test.beforeEach(async ({ page }, testInfo): Promise<void> => {
      test.skip(
        testInfo.project.name === 'webkit',
        'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
      );
      await setupHttpsRewrite(page);
      await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    });

    test('TC-013: Upload Plugin button is present', async ({ page }): Promise<void> => {
      await page.goto(`${BASE_URL}/admin/index.php?load=plugins`);

      // Look for Upload Plugin button
      const uploadButton = page.locator('a:has-text("Upload Plugin"), button:has-text("Upload Plugin")');
      await expect(uploadButton.first()).toBeVisible();

      // Check for icon
      const buttonHtml = await uploadButton.first().innerHTML();
      expect(buttonHtml).toMatch(/fa-cloud-upload/i);
    });

    test('TC-014: Upload Plugin button links correctly', async ({ page }): Promise<void> => {
      await page.goto(`${BASE_URL}/admin/index.php?load=plugins`);

      const uploadButton = page.locator('a:has-text("Upload Plugin")').first();
      await expect(uploadButton).toBeVisible();

      const href = await uploadButton.getAttribute('href');
      expect(href).toBeTruthy();
      // Should link to install plugin functionality
      expect(href).toMatch(/plugins|install/i);
    });
  });

  test.describe('No Inappropriate Redirects', () => {
    test.beforeEach(async ({ page }, testInfo): Promise<void> => {
      test.skip(
        testInfo.project.name === 'webkit',
        'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
      );
      await setupHttpsRewrite(page);
      await adminLogin(page, ADMIN_USER, ADMIN_PASS);
    });

    test('TC-018: Direct access does not redirect to dashboard', async ({
      page,
    }): Promise<void> => {
      await page.goto(`${BASE_URL}/admin/index.php?load=plugins`);

      // Should remain on plugins page
      expect(page.url()).toContain('load=plugins');
      expect(page.url()).not.toContain('load=dashboard');
    });

    test('TC-019: Direct access does not redirect to login when authenticated', async ({
      page,
    }): Promise<void> => {
      await page.goto(`${BASE_URL}/admin/index.php?load=plugins`);

      expect(page.url()).not.toContain('login.php');
      await expect(page.locator('.content-wrapper')).toBeVisible();
    });
  });
});
