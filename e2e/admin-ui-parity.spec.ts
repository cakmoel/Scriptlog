// admin-ui-parity.spec.ts
// Browser end-to-end regression net for the Admin Panel UI Standards
// (docs/dev-docs/DEVELOPER_GUIDE.md §29) and the posts/users/templates
// footer parity fixes (docs/dev-docs/TROUBLESHOOTING.md#44-47,
// plan/ADMIN_FOOTER_SYNC_PLAN.md).
//
// What it guards (read-only GETs, no seeding, no writes):
//   §29.1 - exactly one .main-footer, last in-flow child of .wrapper.
//   §29.2 - row-number + last two action columns never sortable
//           (DataTables `sorting_disabled` on first/last-two <th>).
//   §29.3 - sticky-footer flex intact (.wrapper flex, content flex-grow).
//   §29.4 - sidebar capped at viewport with internal menu scroll.
//   §29.5 - skin CSS carries a ?v= cache-buster.
//   T44-47 - no page-level horizontal overflow at desktop/mobile widths.
//
// NOTE: list pages paginate at 10 rows (e2e DB holds 49 posts), so vertical
// scroll on data pages is legitimate and is NOT asserted. Only horizontal
// page overflow is asserted, plus structural/footer-position parity.
import { test, expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import {
  ADMIN_PASS,
  ADMIN_USER,
  BASE_URL,
  adminLogin,
  clearLoginAttempts,
  clearRateLimiters,
  syncTestUserPasswords,
} from './gdpr-fixtures';

const PAGES: string[] = ['users', 'posts', 'templates'];

async function gotoAdmin(page: Page, load: string): Promise<void> {
  await page.goto(`${BASE_URL}/admin/index.php?load=${load}`);
  // DataTables enhances the list table on init (adds .dataTable class).
  await page.locator('#scriptlog-table.dataTable').waitFor({ timeout: 15000 });
}

test.describe('admin UI parity (DEVELOPER_GUIDE §29)', () => {
  test.beforeAll((): void => {
    // Same convention as the other admin specs: failed-login state triggers
    // a captcha/soft-ban, so reset it before the serial login attempts.
    clearRateLimiters();
    clearLoginAttempts();
    // Match the dump's admin hash to the runtime-resolved E2E_ADMIN_PASS.
    syncTestUserPasswords();
  });

  test.beforeEach(async ({ page }, testInfo): Promise<void> => {
    // Same convention as the other admin specs (admin-locale-dropdown,
    // security-remediation): adminLogin does not complete in webkit in this
    // dev env (Secure session cookie over plain HTTP is dropped).
    test.skip(
      testInfo.project.name === 'webkit',
      'adminLogin does not complete in webkit in this dev env (pre-existing, affects all admin e2e specs)',
    );
    // e2e-env quirk: e2e/router.php spoofs HTTPS for document navigations,
    // so admin HTML references https://127.0.0.1:8099 assets that the
    // HTTP-only dev server cannot serve (ERR_CONNECTION_CLOSED, no CSS/JS).
    // Rewrite those subresource requests to plain http so JS-dependent
    // assertions (DataTables markers, computed styles) actually execute.
    await page.context().route(
      'https://127.0.0.1:8099/**',
      async (route): Promise<void> => {
        const httpUrl: string = route
          .request()
          .url()
          .replace(/^https:/, 'http:');
        const res = await page.context().request.get(httpUrl);
        const headers: Record<string, string> = {};
        const contentType: string | undefined =
          res.headers()['content-type'];
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
    await adminLogin(page, ADMIN_USER, ADMIN_PASS);
  });

  for (const load of PAGES) {
    test(`${load}: single shared footer, last in .wrapper`, async ({
      page,
    }): Promise<void> => {
      await gotoAdmin(page, load);
      const footers = page.locator('.wrapper > footer.main-footer');
      await expect(footers).toHaveCount(1);
      // Footer must sit flush at the end of the wrapper: no gap-creating
      // in-flow siblings after it (only fixed .control-sidebar-bg follows).
      const flush = await page.evaluate((): boolean => {
        const wrapper = document.querySelector('.wrapper') as HTMLElement;
        const footer = document.querySelector(
          '.wrapper > footer.main-footer',
        ) as HTMLElement;
        return (
          footer.offsetTop + footer.offsetHeight === wrapper.scrollHeight
        );
      });
      expect(flush).toBe(true);
    });

    test(`${load}: row-number + Edit/Delete columns not sortable`, async ({
      page,
    }): Promise<void> => {
      await gotoAdmin(page, load);
      // §29.2: first <th> and last two <th> carry DataTables'
      // `sorting_disabled` marker (init targets [0, -1, -2]).
      const markers: boolean = await page.evaluate((): boolean => {
        const heads = Array.from(
          document.querySelectorAll('#scriptlog-table thead th'),
        );
        if (heads.length < 3) {
          return false;
        }
        const targets: Element[] = [
          heads[0],
          heads[heads.length - 2],
          heads[heads.length - 1],
        ];
        return targets.every((th): boolean =>
          th.classList.contains('sorting_disabled'),
        );
      });
      expect(markers).toBe(true);
    });

    test(`${load}: sticky-footer flex intact`, async ({
      page,
    }): Promise<void> => {
      await gotoAdmin(page, load);
      const wrapperDisplay: string = await page.evaluate(
        (): string =>
          getComputedStyle(
            document.querySelector('.wrapper') as HTMLElement,
          ).display,
      );
      expect(wrapperDisplay).toBe('flex');
      const flexGrow: string = await page.evaluate(
        (): string =>
          getComputedStyle(
            document.querySelector('.content-wrapper') as HTMLElement,
          ).flexGrow,
      );
      expect(flexGrow).toBe('1');
    });

    test(`${load}: no page-level horizontal overflow (desktop)`, async ({
      page,
    }): Promise<void> => {
      await page.setViewportSize({ width: 1366, height: 768 });
      await gotoAdmin(page, load);
      const overflow: boolean = await page.evaluate(
        (): boolean =>
          document.documentElement.scrollWidth <= window.innerWidth + 1,
      );
      expect(overflow).toBe(true);
    });

    test(`${load}: no page-level horizontal overflow (mobile)`, async ({
      page,
    }): Promise<void> => {
      await page.setViewportSize({ width: 390, height: 844 });
      await gotoAdmin(page, load);
      const overflow: boolean = await page.evaluate(
        (): boolean =>
          document.documentElement.scrollWidth <= window.innerWidth + 1,
      );
      expect(overflow).toBe(true);
    });
  }

  test('sidebar capped at viewport with internal scroll (desktop)', async ({
    page,
  }): Promise<void> => {
    // §29.4: an expanded submenu (Posts page) must not stretch the page.
    await page.setViewportSize({ width: 1366, height: 768 });
    await gotoAdmin(page, 'posts');
    const cap: string = await page.evaluate(
      (): string =>
        getComputedStyle(
          document.querySelector('.main-sidebar') as HTMLElement,
        ).maxHeight,
    );
    expect(cap).toBe('768px');
    const innerOverflow: string = await page.evaluate(
      (): string =>
        getComputedStyle(
          document.querySelector('.main-sidebar > .sidebar') as HTMLElement,
        ).overflowY,
    );
    expect(innerOverflow).toBe('auto');
  });

  test('skin CSS carries a cache-busting ?v= param', async ({
    page,
  }): Promise<void> => {
    // §29.5 / T47: unversioned skin CSS can stick in browsers for 30 days.
    await gotoAdmin(page, 'posts');
    const href: string | null = await page.evaluate(
      (): string | null =>
        document
          .querySelector('link[href*="skins/scriptlog-skin.css"]')
          ?.getAttribute('href') ?? null,
    );
    expect(href).not.toBeNull();
    expect(href as string).toMatch(/scriptlog-skin\.css\?v=.+/);
  });
});
