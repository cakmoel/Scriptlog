// gdpr-fixtures.ts
// Shared DB seeding, cleanup, and admin-login helpers for the GDPR e2e suite.
// All traffic in Playwright comes from 127.0.0.1, so consent rows, DSAR request
// counters, login attempts and rate-limit files are shared/IP-keyed across the
// three serial browser projects (workers:1). Every spec re-seeds in beforeAll
// and cleans up only its disposable rows so the suite stays deterministic.
import { execFileSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import type { BrowserContext, Locator, Page } from '@playwright/test';

export const BASE_URL: string =
  process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8099';
export const ADMIN_USER: string = 'administrator';
export const ADMIN_PASS: string = '4dMin(*)^';
export const GDPR_USER: string = 'gdpr_user';
export const GDPR_EMAIL: string = 'gdpr.test@e2e.local';
export const GDPR_PASS: string = 'GdprTest!2026';
export const NOACCESS_USER: string = 'noaccess_user';
export const NOACCESS_EMAIL: string = 'noaccess@e2e.local';
export const NOACCESS_PASS: string = 'LowPriv!2026';

export const RATE_LIMIT_DIR: string =
  '/var/www/blogware/public_html/public/cache/rate_limit';
export const GDPR_POST_ID: number = 11; // seeded comment targets post 11
export const GDPR_POST_TITLE: string = 'GDPR E2E Post';

// bcrypt hashes generated with scriptlog_verify_password scheme:
// password_hash(base64_encode(hash('sha384', $pw, true)), PASSWORD_BCRYPT).
const GDPR_USER_HASH: string =
  '$2y$12$UDyTqkQDVC7xv8vJcVs9D.0CIoKazwUPKbpU0VYVOFVLXuxomgBWy';
const NOACCESS_USER_HASH: string =
  '$2y$12$Ir.NvbmoWd/m4gX9UEOBWeYBcfbQ6BVGuTFkXo72jcDG.sKhwRpQu';

// ---------------------------------------------------------------------------
// DB helpers (mysql CLI, mirroring the repo's fs-based cleanup pattern)
// ---------------------------------------------------------------------------

/**
 * Run a SQL statement against the dedicated e2e database.
 *
 * @param sql SQL statement to execute.
 * @returns mysql CLI stdout.
 */
export function runSql(sql: string): string {
  return execFileSync(
    'mysql',
    [
      '-h',
      '127.0.0.1',
      '-P',
      '3306',
      '-u',
      'blogwareuser',
      '-puserblogware',
      'blogware_e2e',
      '-e',
      sql,
    ],
    { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] },
  );
}

/**
 * Run a SQL SELECT and return the first-column value of the first row.
 *
 * @param sql SELECT statement to execute.
 * @returns First-column value of the first row, or '' when empty.
 */
export function dbScalar(sql: string): string {
  const out: string = runSql(sql).trim();
  if (out === '') return '';
  const lines: string[] = out.split('\n');
  const last: string | undefined = lines.pop();
  return (last ?? '').trim();
}

/**
 * Run a SQL SELECT COUNT(...) and return it as a number.
 *
 * @param sql COUNT statement to execute.
 * @returns Row count, or 0 when empty.
 */
export function dbCount(sql: string): number {
  const out: string = dbScalar(sql);
  return out === '' ? 0 : parseInt(out, 10);
}

/**
 * Read all rows from a SELECT as an array of plain objects.
 *
 * @param sql SELECT statement to execute.
 * @returns Rows keyed by column name.
 */
export function dbRows(sql: string): Array<Record<string, string>> {
  const out: string = runSql(sql).trim();
  if (out === '') return [];
  const lines: string[] = out.split('\n');
  const headerLine: string = lines[0] ?? '';
  const headers: string[] = headerLine.split('\t');
  return lines.slice(1).map((line: string): Record<string, string> => {
    const cells: string[] = line.split('\t');
    const row: Record<string, string> = {};
    headers.forEach((h: string, i: number): void => {
      row[h] = cells[i] ?? '';
    });
    return row;
  });
}

// ---------------------------------------------------------------------------
// Rate limiter / login-attempt / cookie cleanup
// ---------------------------------------------------------------------------

/**
 * Delete every file-backed rate-limit counter (API read/write + DSAR namespaces).
 * Mirrors theme-protected.spec.ts clearRateLimit().
 */
export function clearRateLimiters(): void {
  if (!fs.existsSync(RATE_LIMIT_DIR)) return;
  for (const f of fs.readdirSync(RATE_LIMIT_DIR)) {
    fs.rmSync(path.join(RATE_LIMIT_DIR, f), { force: true });
  }
}

/**
 * Clear failed-login state so the admin captcha (>=5 attempts) and soft ban
 * (>=20) can never surface. Clears both the per-IP attempt table and any
 * user-level lockout/ban counters left by earlier runs.
 */
export function clearLoginAttempts(): void {
  runSql("DELETE FROM tbl_login_attempt WHERE ip_address = '127.0.0.1'");
  runSql(`
    UPDATE tbl_users
    SET user_locked_until = NULL, user_banned = 0, user_signin_count = 0
    WHERE user_login IN ('${ADMIN_USER}','${GDPR_USER}','${NOACCESS_USER}')
  `);
}

/**
 * Clear all browser cookies so consent banner state is deterministic.
 *
 * @param context Browser context whose cookies should be cleared.
 */
export async function clearConsentCookies(
  context: BrowserContext,
): Promise<void> {
  await context.clearCookies();
}

/**
 * Disable HTML5 form validation so server-side negative tests can submit
 * forms that would otherwise be blocked by required/type=email in the browser.
 *
 * @param locator Form locator to mark as novalidate.
 */
export async function noValidate(locator: Locator): Promise<void> {
  await locator.evaluate((f: HTMLFormElement): void => {
    f.noValidate = true;
  });
}

// ---------------------------------------------------------------------------
// Admin login helper
// ---------------------------------------------------------------------------

/**
 * Log into the admin area by submitting the real rendered form. Always uses
 * correct credentials so the >=5-failed-attempt captcha never appears.
 *
 * @param page Playwright page to log in with.
 * @param username Admin username.
 * @param password Admin password.
 */
export async function adminLogin(
  page: Page,
  username: string = ADMIN_USER,
  password: string = ADMIN_PASS,
): Promise<void> {
  await page.goto(`${BASE_URL}/admin/login.php`);
  await page.locator('#inputLogin').fill(username);
  await page.locator('#inputPassword').fill(password);
  await Promise.all([
    page.waitForURL(/index\.php\?load=dashboard/),
    page.locator('input[name="LogIn"]').click(),
  ]);
}

// ---------------------------------------------------------------------------
// Seeding / cleanup
// ---------------------------------------------------------------------------

/**
 * Idempotently wipe and re-seed every GDPR fixture row. Safe to run in
 * beforeAll of each spec under serial project execution. Admin ID 767 is
 * NEVER deleted. The disposable users are never ID 1 or 767.
 */
export function seedGdprFixtures(): void {
  runSql(`
    DELETE FROM tbl_privacy_logs;
    DELETE FROM tbl_data_requests;
    DELETE FROM tbl_consents;
    DELETE FROM tbl_privacy_policies;
    DELETE FROM tbl_login_attempt;
    DELETE FROM tbl_settings WHERE setting_name IN ('consent_retention_days','privacy_log_retention_days');
    DELETE FROM tbl_user_token WHERE user_login IN ('${GDPR_USER}','${NOACCESS_USER}');
    DELETE FROM tbl_comments WHERE comment_author_email IN ('${GDPR_EMAIL}','${NOACCESS_EMAIL}');
    DELETE FROM tbl_posts WHERE post_author IN (SELECT ID FROM tbl_users WHERE user_login IN ('${GDPR_USER}','${NOACCESS_USER}'));
    DELETE FROM tbl_users WHERE user_login IN ('${GDPR_USER}','${NOACCESS_USER}');
    DELETE FROM tbl_languages WHERE lang_code IN ('en','ar','zh','fr','ru','es','id');
  `);

  runSql(`
    UPDATE tbl_users
    SET user_locked_until = NULL, user_banned = 0, user_signin_count = 0
    WHERE user_login = '${ADMIN_USER}'
  `);

  runSql(`
    INSERT INTO tbl_languages
      (lang_code, lang_name, lang_native, lang_locale, lang_direction, lang_sort, lang_is_default, lang_is_active)
    VALUES
      ('en','English','English','en_US','ltr',1,1,1),
      ('ar','Arabic','العربية','ar_SA','rtl',2,0,1),
      ('zh','Chinese','中文','zh_CN','ltr',3,0,1),
      ('fr','French','Français','fr_FR','ltr',4,0,1),
      ('ru','Russian','Русский','ru_RU','ltr',5,0,1),
      ('es','Spanish','Español','es_ES','ltr',6,0,1),
      ('id','Indonesian','Bahasa Indonesia','id_ID','ltr',7,0,1);
  `);

  runSql(`
    INSERT INTO tbl_users
      (user_login, user_email, user_pass, user_level, user_fullname, user_url, user_registered,
       user_activation_key, user_reset_key, user_reset_complete, user_session, user_banned, user_signin_count, user_locked_until)
    VALUES
      ('${GDPR_USER}','${GDPR_EMAIL}','${GDPR_USER_HASH}',
       'author','GDPR Test User','https://e2e.local','2026-01-01 08:00:00','','','No','',0,0,NULL),
      ('${NOACCESS_USER}','${NOACCESS_EMAIL}','${NOACCESS_USER_HASH}',
       'author','No Access User','','2026-01-01 08:00:00','','','No','',0,0,NULL);

    INSERT INTO tbl_posts
      (media_id, post_author, post_date, post_title, post_slug, post_content, post_status, post_visibility, post_type, post_locale, comment_status)
    VALUES
      (0, (SELECT ID FROM tbl_users WHERE user_login='${GDPR_USER}'), '2026-02-01 10:00:00',
       '${GDPR_POST_TITLE}', 'gdpr-e2e-post',
       '<p>Content authored by the GDPR e2e subject.</p>',
       'publish','public','blog','en','open');

    INSERT INTO tbl_comments
      (comment_post_id, comment_parent_id, comment_author_name, comment_author_ip, comment_author_email, comment_content, comment_status, comment_date)
    VALUES
      (${GDPR_POST_ID}, 0, 'GDPR Test User', '127.0.0.1', '${GDPR_EMAIL}',
       'A comment left by the GDPR e2e subject.', 'approved', '2026-02-02 10:00:00');

    INSERT INTO tbl_data_requests (request_type, request_email, request_status, request_ip, request_note, request_date) VALUES
      ('access','access@e2e.local','pending','127.0.0.1','Access request','2026-07-01 09:00:00'),
      ('rectification','rectify@e2e.local','pending','127.0.0.1','Rectify my name','2026-07-01 09:05:00'),
      ('deletion','${GDPR_EMAIL}','processing','127.0.0.1','GDPR delete me','2026-07-01 09:10:00'),
      ('erasure','erasure@e2e.local','processing','127.0.0.1','Right to be forgotten','2026-07-01 09:15:00'),
      ('deletion','old-done@e2e.local','completed','127.0.0.1','Done','2026-06-01 09:00:00'),
      ('access','rejected@e2e.local','rejected','127.0.0.1','Nope','2026-06-02 09:00:00');

    INSERT INTO tbl_privacy_logs (log_action, log_type, log_user_id, log_email, log_details, log_ip, log_date) VALUES
      ('data_request_created','access',NULL,'access@e2e.local','Data access request created','127.0.0.1','2026-07-01 09:00:00'),
      ('request_status_updated','deletion',NULL,'${GDPR_EMAIL}','Request status changed to: processing','127.0.0.1','2026-07-01 09:11:00'),
      ('data_exported','export',767,'admin@blogware.site','User data exported','127.0.0.1','2026-07-02 09:00:00');

    INSERT INTO tbl_privacy_policies (locale, policy_title, policy_content, is_default, created_at) VALUES
      ('en','E2E Privacy Policy','<h2>Our policy</h2><p>We collect minimal data for the e2e suite.</p>',1,'2026-01-01 00:00:00'),
      ('fr','Politique de confidentialité E2E','<h2>Notre politique</h2><p>Nous collectons un minimum de données.</p>',0,'2026-01-01 00:00:00');
  `);
}

/**
 * Remove only the disposable GDPR fixture rows. Admin ID 767 and the fallback
 * author are never touched.
 */
export function cleanupGdprFixtures(): void {
  runSql(`
    DELETE FROM tbl_privacy_logs;
    DELETE FROM tbl_data_requests;
    DELETE FROM tbl_consents;
    DELETE FROM tbl_privacy_policies;
    DELETE FROM tbl_login_attempt;
    DELETE FROM tbl_settings WHERE setting_name IN ('consent_retention_days','privacy_log_retention_days');
    DELETE FROM tbl_user_token WHERE user_login IN ('${GDPR_USER}','${NOACCESS_USER}');
    DELETE FROM tbl_comments WHERE comment_author_email IN ('${GDPR_EMAIL}','${NOACCESS_EMAIL}');
    DELETE FROM tbl_posts WHERE post_author IN (SELECT ID FROM tbl_users WHERE user_login IN ('${GDPR_USER}','${NOACCESS_USER}'));
    DELETE FROM tbl_users WHERE user_login IN ('${GDPR_USER}','${NOACCESS_USER}');
  `);
}

/**
 * Re-seed deterministic consent/log rows for the retention phase. Expects
 * seedGdprFixtures() to have already run (clears the consent/log tables).
 */
export function seedRetentionFixtures(): void {
  runSql(`
    DELETE FROM tbl_consents;
    DELETE FROM tbl_privacy_logs;
    INSERT INTO tbl_consents (consent_type, consent_status, consent_ip, consent_date) VALUES
      ('cookie','accepted','127.0.0.1', NOW() - INTERVAL 400 DAY),
      ('cookie','rejected','127.0.0.1', NOW() - INTERVAL 400 DAY),
      ('cookie','accepted','127.0.0.1', NOW() - INTERVAL 1 DAY);
    INSERT INTO tbl_privacy_logs (log_action, log_type, log_email, log_details, log_ip, log_date) VALUES
      ('data_request_created','access','retention@e2e.local','Old log','127.0.0.1', NOW() - INTERVAL 400 DAY),
      ('data_exported','export','retention@e2e.local','Old export','127.0.0.1', NOW() - INTERVAL 400 DAY),
      ('request_status_updated','access','retention@e2e.local','New log','127.0.0.1', NOW() - INTERVAL 1 DAY);
  `);
}

// ---------------------------------------------------------------------------
// JSON download helper
// ---------------------------------------------------------------------------

/** Minimal shape of the admin data-export JSON payload. */
export type ExportPayload = {
  email: string;
  profile?: Record<string, unknown>;
  posts?: Array<Record<string, unknown>>;
  comments?: Array<Record<string, unknown>>;
  [key: string]: unknown;
};

/**
 * Trigger a click that produces a JSON download and return the parsed body.
 *
 * @param page Page that will receive the download event.
 * @param clickAction Callback that triggers the download.
 * @returns Parsed JSON payload of the downloaded file.
 */
export async function readJsonDownload(
  page: Page,
  clickAction: () => Promise<unknown>,
): Promise<ExportPayload> {
  const [download] = await Promise.all([
    page.waitForEvent('download'),
    clickAction(),
  ]);
  const filename: string = download.suggestedFilename();
  if (!/^user_data_\d+\.json$/.test(filename)) {
    throw new Error(`Unexpected download filename: ${filename}`);
  }
  const filePath: string | null = await download.path();
  if (filePath === null) {
    throw new Error('Download path is not available yet');
  }
  return JSON.parse(fs.readFileSync(filePath, 'utf8')) as ExportPayload;
}
