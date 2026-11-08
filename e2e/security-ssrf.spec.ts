// security-ssrf.spec.ts
//
// SSRF boundary campaign (specs/security-ssrf.md).
//
// The request-path code audit found no user-controllable server-side fetch
// sink, so this suite pins the boundaries of the two server-side request
// primitives that DO exist:
//
//   1. Admin-configurable SMTP host - tbl_settings.smtp_host is read by
//      NotificationService::loadConfiguration() and concatenated into the
//      mailer DSN (admin-only SSRF primitive; no host validation exists -
//      accepted risk, asserted as current behaviour).
//   2. Theme license activation - ThemeLicenseService posts to the FIXED
//      upstream base https://myblog.local; $_SERVER['HTTP_HOST'] only enters
//      the JSON POST body as the "domain" field. Activation must fail closed
//      without leaking transport internals or touching tbl_themes.
//
// Host-header forging of the outbound request cannot be exercised from
// Chromium (manual Host header -> net::ERR_INVALID_ARGUMENT, verified), and
// real out-of-band SSRF detection needs a collaborator server - both are
// documented as out of E2E reach in the plan instead of faked here.

import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  adminLogin,
  clearLoginAttempts,
  dbRows,
  dbScalar,
  runSql,
  syncTestUserPasswords,
} from './gdpr-fixtures';

/** Baseline SMTP host (TEST-NET-3 documentation range, never routed). */
const SENTINEL_SMTP_HOST = '198.51.100.55';

/** Arbitrary internal address used to prove the primitive accepts any host. */
const PROBE_SMTP_HOST = '169.254.169.254';

/** Fake license key - the upstream mock always rejects it. */
const FAKE_LICENSE_KEY = 'E2EFAKEKEY123';

/**
 * Transport/internals markers that must never surface in the license
 * activation error flash (cURL/resolver output, upstream URL, secrets,
 * stack traces).
 */
const LICENSE_LEAK_PATTERNS: string[] = [
  'cURL',
  'Could not resolve',
  'Connection refused',
  'stream_socket',
  'Stack trace',
  'Fatal error',
  'PDOException',
  'myblog.local',
  'license_secret',
  'SSL',
];

/** smtp_% settings rows recorded before the suite mutates them. */
let originalSmtpRows: Array<Record<string, string>> = [];

/**
 * Delete every smtp_* setting and re-insert the rows recorded in beforeAll,
 * restoring the environment exactly (also runs when a test fails).
 */
function restoreSmtpRows(): void {
  runSql(`DELETE FROM tbl_settings WHERE setting_name LIKE 'smtp_%'`);
  for (const row of originalSmtpRows) {
    const name = String(row.setting_name ?? '').replace(/'/g, "''");
    const value = String(row.setting_value ?? '').replace(/'/g, "''");
    runSql(
      `INSERT INTO tbl_settings (setting_name, setting_value) VALUES ('${name}', '${value}')`,
    );
  }
}

/**
 * Snapshot of every tbl_themes column license activation could touch.
 *
 * @returns Theme state keyed by column meaning.
 */
function themeState(): Record<string, string> {
  return {
    blogStatus: dbScalar(
      `SELECT theme_status FROM tbl_themes WHERE theme_directory = 'blog'`,
    ),
    premiumStatus: dbScalar(
      `SELECT theme_status FROM tbl_themes WHERE theme_directory = 'restoblog'`,
    ),
    premiumLicenseStatus: dbScalar(
      `SELECT license_status FROM tbl_themes WHERE theme_directory = 'restoblog'`,
    ),
    premiumLicenseKey: dbScalar(
      `SELECT license_key FROM tbl_themes WHERE theme_directory = 'restoblog'`,
    ),
  };
}

test.describe.serial('security: SSRF boundaries (SMTP primitive, license)', () => {
  test.beforeAll(() => {
    // Admin login uses the runtime-resolved E2E_ADMIN_PASS; keep the dumped
    // hash in sync so the access-control assertions are deterministic.
    syncTestUserPasswords();
    originalSmtpRows = dbRows(
      `SELECT setting_name, setting_value FROM tbl_settings WHERE setting_name LIKE 'smtp_%'`,
    );
    // Deterministic baseline for the access-control/CSRF tests.
    runSql(`DELETE FROM tbl_settings WHERE setting_name LIKE 'smtp_%'`);
    runSql(
      `INSERT INTO tbl_settings (setting_name, setting_value) VALUES ('smtp_host', '${SENTINEL_SMTP_HOST}')`,
    );
    clearLoginAttempts();
  });

  test.afterAll(() => {
    restoreSmtpRows();
    clearLoginAttempts();
  });

  test('unauthenticated POST to the mail settings endpoint is redirected and changes nothing', async ({
    request,
  }) => {
    const res = await request.post(
      `${BASE_URL}/admin/index.php?load=option-mail&action=mailConfig&Id=0`,
      {
        form: {
          smtp_host: PROBE_SMTP_HOST,
          mailConfigSubmit: 'Save Settings',
          csrfToken: 'x',
        },
        maxRedirects: 0,
      },
    );

    expect(res.status()).toBe(302);
    expect(res.headers()['location'] ?? '').toContain('admin/login.php');

    // The SSRF primitive is unreachable without an authenticated admin.
    expect(
      dbScalar(
        `SELECT setting_value FROM tbl_settings WHERE setting_name = 'smtp_host'`,
      ),
    ).toBe(SENTINEL_SMTP_HOST);
  });

  test('tampered CSRF token on the mail settings form is rejected with 400', async ({
    page,
  }) => {
    await adminLogin(page);
    await page.goto(`${BASE_URL}/admin/index.php?load=option-mail`);

    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('option-mail') && r.request().method() === 'POST',
      ),
      page.evaluate(() => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?load=option-mail&action=mailConfig&Id=0';
        const add = (name: string, value: string): void => {
          const input = document.createElement('input');
          input.name = name;
          input.value = value;
          form.appendChild(input);
        };
        add(
          'csrfToken',
          'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',
        );
        add('smtp_host', '10.0.0.1');
        add('smtp_port', '25');
        add('mailConfigSubmit', 'Save Settings');
        document.body.appendChild(form);
        form.submit();
      }),
    ]);

    expect(resp.status()).toBe(400);
    expect(
      dbScalar(
        `SELECT setting_value FROM tbl_settings WHERE setting_name = 'smtp_host'`,
      ),
    ).toBe(SENTINEL_SMTP_HOST);
  });

  test('admin can store an arbitrary internal SMTP host (documented admin-only SSRF primitive)', async ({
    page,
  }) => {
    // Accepted-risk inventory: tbl_settings.smtp_host flows unsanitised into
    // the NotificationService DSN when mail is sent. Save-only - no outbound
    // connection happens here. If host validation/allow-listing is ever
    // added, UPDATE this expectation instead of deleting the test.
    await adminLogin(page);
    await page.goto(`${BASE_URL}/admin/index.php?load=option-mail`);

    await page.locator('input[name="smtp_host"]').fill(PROBE_SMTP_HOST);
    await Promise.all([
      page.waitForURL(/status=mailConfigUpdated/),
      page.locator('input[name="mailConfigSubmit"]').click(),
    ]);

    expect(
      dbScalar(
        `SELECT setting_value FROM tbl_settings WHERE setting_name = 'smtp_host'`,
      ),
    ).toBe(PROBE_SMTP_HOST);
  });

  test('premium license activation fails closed without leaking transport internals', async ({
    page,
  }) => {
    const premiumId = dbScalar(
      `SELECT ID FROM tbl_themes WHERE theme_directory = 'restoblog' AND theme_type = 'premium'`,
    );
    test.skip(premiumId === '', 'restoblog premium theme row not installed');

    await adminLogin(page);

    const before = themeState();
    const dialogs: string[] = [];
    page.on('dialog', (dialog) => {
      dialogs.push(dialog.message());
      void dialog.dismiss().catch(() => undefined);
    });

    await page.goto(
      `${BASE_URL}/admin/index.php?load=templates&action=activateTheme&Id=${premiumId}&license_key=${FAKE_LICENSE_KEY}`,
      { waitUntil: 'networkidle', timeout: 30_000 },
    );

    // Failure redirects back to the theme list with an error flag...
    expect(page.url()).toContain('error=themeLicenseError');

    // ...and the surfaced flash carries a generic license error only.
    const flashText = (
      await page.locator('.alert, .callout').allInnerTexts()
    ).join('\n');
    expect(flashText).toContain('License Error');
    for (const leak of LICENSE_LEAK_PATTERNS) {
      expect(flashText, `flash must not disclose "${leak}"`).not.toContain(leak);
    }
    expect(dialogs).toEqual([]);

    // Fail closed: no theme state changed, nothing written to the license row.
    expect(themeState()).toEqual(before);
  });

  test('premium theme cannot be activated without a license key (no upstream call, no state change)', async ({
    page,
  }) => {
    const premiumId = dbScalar(
      `SELECT ID FROM tbl_themes WHERE theme_directory = 'restoblog' AND theme_type = 'premium'`,
    );
    test.skip(premiumId === '', 'restoblog premium theme row not installed');

    await adminLogin(page);

    const before = themeState();
    const dialogs: string[] = [];
    page.on('dialog', (dialog) => {
      dialogs.push(dialog.message());
      void dialog.dismiss().catch(() => undefined);
    });

    // No license_key parameter - the controller returns before any HTTP call.
    await page.goto(
      `${BASE_URL}/admin/index.php?load=templates&action=activateTheme&Id=${premiumId}`,
      { waitUntil: 'networkidle', timeout: 30_000 },
    );

    expect(page.url()).toContain('error=themeLicenseError');

    const flashText = (
      await page.locator('.alert, .callout').allInnerTexts()
    ).join('\n');
    expect(flashText.toLowerCase()).toContain('license key');
    expect(dialogs).toEqual([]);

    expect(themeState()).toEqual(before);
  });
});
