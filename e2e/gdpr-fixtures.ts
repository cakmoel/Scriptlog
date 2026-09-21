// gdpr-fixtures.ts
// Shared DB seeding, cleanup, and admin-login helpers for the GDPR e2e suite.
// All traffic in Playwright comes from 127.0.0.1, so consent rows, DSAR request
// counters, login attempts and rate-limit files are shared/IP-keyed across the
// three serial browser projects (workers:1). Every spec re-seeds in beforeAll
// and cleans up only its disposable rows so the suite stays deterministic.
//
// SECURITY: no real credentials, PII, or production secrets live in this file
// or anywhere under e2e/. All passwords are resolved at runtime:
//   1. from the documented E2E_* environment variables (CI uses GitHub Secrets
//      or a per-run ephemeral value - see e2e/.env.example and
//      .github/workflows/playwright.yml), or
//   2. on CI without secrets, from a per-process cryptographically random
//      ephemeral password, or
//   3. locally only, from an obvious non-secret placeholder.
// The seed helpers hash whatever password was resolved (via the app's own
// scriptlog_verify_password scheme) and sync the e2e database to it, so the
// committed SQL dump's hashes are always overwritten before any login and no
// plaintext+hash pair is committed. Emails use synthetic @e2e.local domains.
import { execFileSync, execSync } from 'child_process';
import crypto from 'crypto';
import fs from 'fs';
import path from 'path';
import type { BrowserContext, Locator, Page } from '@playwright/test';

/**
 * Load local e2e/.env (gitignored) without adding a dotenv dependency.
 * CI injects real env vars; a local .env file only fills keys that are not
 * already set. Lines starting with # are ignored; surrounding quotes stripped.
 */
function loadLocalE2EEnv(): void {
  try {
    const envFile: string = path.join(process.cwd(), 'e2e', '.env');
    if (!fs.existsSync(envFile)) return;
    const lines: string[] = fs.readFileSync(envFile, 'utf8').split('\n');
    for (const line of lines) {
      const trimmed: string = line.trim();
      if (trimmed === '' || trimmed.startsWith('#')) continue;
      const eq: number = trimmed.indexOf('=');
      if (eq < 1) continue;
      const key: string = trimmed.slice(0, eq).trim();
      let value: string = trimmed.slice(eq + 1).trim();
      if (
        value.length >= 2 &&
        ((value.startsWith('"') && value.endsWith('"')) ||
          (value.startsWith("'") && value.endsWith("'")))
      ) {
        value = value.slice(1, -1);
      }
      if (key !== '' && process.env[key] === undefined) {
        process.env[key] = value;
      }
    }
  } catch {
    // A missing/unreadable .env is normal on CI - env comes from secrets.
  }
}

loadLocalE2EEnv();

export const BASE_URL: string =
  process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8099';

/**
 * Resolve a test-only secret without committing it.
 *
 * - Uses process.env[name] when set (CI injects via GitHub Secrets).
 * - On CI without a secret, generates a per-process ephemeral password so
 *   forks and PRs stay green without any committed fallback.
 * - Locally, falls back to an obvious placeholder that is never valid in
 *   production and is only used against the local ephemeral blogware_e2e DB.
 *
 * @param name Env var name (e.g. E2E_ADMIN_PASS).
 * @param localFallback Obvious non-secret placeholder for local runs only.
 * @param length Random bytes length for the CI ephemeral fallback.
 * @returns The resolved secret value.
 */
function resolveE2ESecret(
  name: string,
  localFallback: string,
  length: number = 18,
): string {
  const fromEnv: string | undefined = process.env[name];
  if (fromEnv !== undefined && fromEnv !== '') return fromEnv;
  if (process.env.CI) {
    return `e2e-ci-${crypto.randomBytes(length).toString('hex')}`;
  }
  return localFallback;
}

/** Non-secret login identifiers (public usernames, overridable for isolation). */
export const ADMIN_USER: string =
  process.env.E2E_ADMIN_USER || 'administrator';
/** Synthetic fixture mailbox - RFC 2606-style test domain, not real PII. */
export const GDPR_EMAIL: string =
  process.env.E2E_GDPR_EMAIL || 'gdpr.test@e2e.local';
/** Synthetic fixture mailbox - RFC 2606-style test domain, not real PII. */
export const NOACCESS_EMAIL: string =
  process.env.E2E_NOACCESS_EMAIL || 'noaccess@e2e.local';
export const GDPR_USER: string = process.env.E2E_GDPR_USER || 'gdpr_user';
export const NOACCESS_USER: string =
  process.env.E2E_NOACCESS_USER || 'noaccess_user';

/** Test-only passwords - never committed, never production (see above). */
export const ADMIN_PASS: string = resolveE2ESecret(
  'E2E_ADMIN_PASS',
  'local-e2e-admin-only',
);
export const GDPR_PASS: string = resolveE2ESecret(
  'E2E_GDPR_PASS',
  'local-e2e-gdpr-only',
);
export const NOACCESS_PASS: string = resolveE2ESecret(
  'E2E_NOACCESS_PASS',
  'local-e2e-noaccess-only',
);

/** Ephemeral local/CI database connection (service container, destroyed per run). */
export const E2E_DB_HOST: string = process.env.E2E_DB_HOST || '127.0.0.1';
export const E2E_DB_PORT: string = process.env.E2E_DB_PORT || '3306';
export const E2E_DB_USER: string = process.env.E2E_DB_USER || 'blogwareuser';
export const E2E_DB_PASS: string =
  process.env.E2E_DB_PASS || process.env.MYSQL_PWD || 'userblogware';
export const E2E_DB_NAME: string = process.env.E2E_DB_NAME || 'blogware_e2e';

/**
 * Escape a value for interpolation into a single-quoted SQL string literal.
 *
 * @param value Raw value to escape.
 * @returns Value safe to embed between single quotes.
 */
export function sqlEscape(value: string): string {
  return value.replace(/\\/g, '\\\\').replace(/'/g, "''");
}

/**
 * Hash a user password with the app's scriptlog_verify_password scheme
 * (password_hash(base64_encode(hash('sha384', pw, true)), PASSWORD_BCRYPT)).
 * Password travels via stdin so it never appears in ps output. Cost 4 keeps
 * per-seed hashing fast; password_verify() accepts any cost.
 *
 * @param password Plaintext password to hash.
 * @returns bcrypt hash string.
 */
export function hashUserPassword(password: string): string {
  const out: string = execSync(
    `php -r '$pw=stream_get_contents(STDIN);` +
      ` echo password_hash(base64_encode(hash("sha384",$pw,true)),` +
      ` PASSWORD_BCRYPT, ["cost"=>4]);'`,
    { encoding: 'utf8', input: password, stdio: ['pipe', 'pipe', 'ignore'] },
  );
  return out.trim();
}

export const RATE_LIMIT_DIR: string = path.join(
  process.cwd(),
  'public',
  'cache',
  'rate_limit',
);
export const GDPR_POST_ID: number = 11; // seeded comment targets post 11
export const GDPR_POST_TITLE: string = 'GDPR E2E Post';

// NOTE: no bcrypt hashes are committed here. seedGdprFixtures() hashes the
// runtime-resolved passwords via hashUserPassword() (app's own scheme) and
// syncs the test DB to them, overwriting any hashes carried by the SQL dump.

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
      E2E_DB_HOST,
      '-P',
      E2E_DB_PORT,
      '-u',
      E2E_DB_USER,
      `-p${E2E_DB_PASS}`,
      E2E_DB_NAME,
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
 * @returns The first-column value of the first row, or '' when no row matches.
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
  const users: string = [ADMIN_USER, GDPR_USER, NOACCESS_USER]
    .map((u: string): string => `'${sqlEscape(u)}'`)
    .join(',');
  runSql(`
    UPDATE tbl_users
    SET user_locked_until = NULL, user_banned = 0, user_signin_count = 0
    WHERE user_login IN (${users})
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
  // WebKit refuses to store the Secure session cookie that the router's HTTPS
  // spoof (document navigations only, see e2e/router.php) causes. Pre-warm the
  // session with a plain non-document request first so the cookie is storable;
  // otherwise the form POST opens a fresh session and the login CSRF check
  // fails with "Session expired or invalid request" on webkit.
  await page.context().request.get(`${BASE_URL}/admin/login.php`);
  await page.goto(`${BASE_URL}/admin/login.php`);
  await page.locator('#inputLogin').fill(username);
  await page.locator('#inputPassword').fill(password);
  await Promise.all([
    // The dashboard page is server-rendered; waiting on "load" stalls on
    // webkit because the single-threaded php -S test server keeps serving the
    // dashboard's asset queue past the test timeout. DOMContentLoaded + URL
    // match is a robust cross-engine signal that the login succeeded.
    page.waitForURL(/index\.php\?load=dashboard/, {
      waitUntil: 'domcontentloaded',
    }),
    page.locator('input[name="LogIn"]').click(),
  ]);
}

// ---------------------------------------------------------------------------
// Seeding / cleanup
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Seed serialization
// ---------------------------------------------------------------------------

const SEED_LOCK_DIR: string = path.join(
  process.cwd(),
  'public',
  'cache',
  '.e2e-seed.lock',
);

/**
 * Serialize DB re-seeding across parallel workers.
 *
 * Multiple spec files run their beforeAll hooks concurrently (local runs use
 * more than one worker), and two racing seedGdprFixtures() calls both DELETE
 * then both INSERT, so the second INSERT trips a duplicate key (e.g.
 * tbl_languages). The lock is a directory created atomically via mkdir; workers
 * that lose the race poll until the holder removes it. No-op on single-worker
 * CI runs but keeps local multi-worker runs deterministic.
 *
 * @param fn Mutating work to run while holding the seed lock.
 * @returns The return value of fn.
 */
function withSeedLock<T>(fn: () => T): T {
  for (;;) {
    try {
      fs.mkdirSync(SEED_LOCK_DIR);
      break;
    } catch {
      Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, 50);
    }
  }
  try {
    return fn();
  } finally {
    fs.rmdirSync(SEED_LOCK_DIR);
  }
}

/**
 * Sync the committed test accounts to the runtime-resolved passwords without
 * wiping any fixture tables. Specs that log in but must NOT reseed (they
 * assert on dump state) call this in beforeAll instead of seedGdprFixtures().
 * The admin row comes from blogware_e2e.sql; its dumped hash is overwritten
 * here so the old committed password never grants access.
 */
export function syncTestUserPasswords(): void {
  withSeedLock(() => {
    const adminHash: string = hashUserPassword(ADMIN_PASS);
    const adminUser: string = sqlEscape(ADMIN_USER);
    runSql(`
      UPDATE tbl_users
      SET user_pass = '${adminHash}', user_locked_until = NULL, user_banned = 0, user_signin_count = 0
      WHERE user_login = '${adminUser}';
    `);
    // Disposable users only exist after a seed; sync them when present so a
    // spec mixing sync + seed stays consistent within one worker process.
    try {
      const gdprHash: string = hashUserPassword(GDPR_PASS);
      const noaccessHash: string = hashUserPassword(NOACCESS_PASS);
      runSql(`
        UPDATE tbl_users SET user_pass = '${gdprHash}'
        WHERE user_login = '${sqlEscape(GDPR_USER)}';
      `);
      runSql(`
        UPDATE tbl_users SET user_pass = '${noaccessHash}'
        WHERE user_login = '${sqlEscape(NOACCESS_USER)}';
      `);
    } catch {
      // Hashing already succeeded for admin; ignore disposable-user failures.
    }
  });
}

/**
 * Idempotently wipe and re-seed every GDPR fixture row. Safe to run in
 * beforeAll of each spec; concurrent beforeAll calls are serialized by a
 * filesystem lock so worker-parallel runs stay deterministic. Admin ID 767 is
 * NEVER deleted. The disposable users are never ID 1 or 767.
 */
export function seedGdprFixtures(): void {
  // Hash the runtime-resolved passwords (env or ephemeral) with the app's own
  // scheme, then sync the DB to them. This overwrites whatever hashes the SQL
  // dump carried, so no committed plaintext+hash pair ever grants access.
  const adminHash: string = hashUserPassword(ADMIN_PASS);
  const gdprHash: string = hashUserPassword(GDPR_PASS);
  const noaccessHash: string = hashUserPassword(NOACCESS_PASS);
  const adminUser: string = sqlEscape(ADMIN_USER);
  const gdprUser: string = sqlEscape(GDPR_USER);
  const noaccessUser: string = sqlEscape(NOACCESS_USER);
  const gdprEmail: string = sqlEscape(GDPR_EMAIL);
  const noaccessEmail: string = sqlEscape(NOACCESS_EMAIL);
  withSeedLock(() => {
    // The fixture tables are wiped and re-created in one transaction so
    // concurrently-running tests on other workers never observe a half-seeded
    // state (e.g. an empty tbl_data_requests between the DELETEs and INSERTs).
    runSql(`
    START TRANSACTION;
    DELETE FROM tbl_privacy_logs;
    DELETE FROM tbl_data_requests;
    DELETE FROM tbl_consents;
    DELETE FROM tbl_privacy_policies;
    DELETE FROM tbl_login_attempt;
    DELETE FROM tbl_settings WHERE setting_name IN ('consent_retention_days','privacy_log_retention_days');
    DELETE FROM tbl_user_token WHERE user_login IN ('${gdprUser}','${noaccessUser}');
    DELETE FROM tbl_comments WHERE comment_author_email IN ('${gdprEmail}','${noaccessEmail}');
    DELETE FROM tbl_posts WHERE post_author IN (SELECT ID FROM tbl_users WHERE user_login IN ('${gdprUser}','${noaccessUser}'));
    DELETE FROM tbl_users WHERE user_login IN ('${gdprUser}','${noaccessUser}');
    DELETE FROM tbl_languages WHERE lang_code IN ('en','ar','zh','fr','ru','es','id');
    UPDATE tbl_users
    SET user_pass = '${adminHash}', user_locked_until = NULL, user_banned = 0, user_signin_count = 0
    WHERE user_login = '${adminUser}';
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
    INSERT INTO tbl_users
      (user_login, user_email, user_pass, user_level, user_fullname, user_url, user_registered,
       user_activation_key, user_reset_key, user_reset_complete, user_session, user_banned, user_signin_count, user_locked_until)
    VALUES
      ('${gdprUser}','${gdprEmail}','${gdprHash}',
       'author','GDPR Test User','https://e2e.local','2026-01-01 08:00:00','','','No','',0,0,NULL),
      ('${noaccessUser}','${noaccessEmail}','${noaccessHash}',
       'author','No Access User','','2026-01-01 08:00:00','','','No','',0,0,NULL);
    INSERT INTO tbl_posts
      (media_id, post_author, post_date, post_title, post_slug, post_content, post_status, post_visibility, post_type, post_locale, comment_status)
    VALUES
      (0, (SELECT ID FROM tbl_users WHERE user_login='${gdprUser}'), '2026-02-01 10:00:00',
       '${GDPR_POST_TITLE}', 'gdpr-e2e-post',
       '<p>Content authored by the GDPR e2e subject.</p>',
       'publish','public','blog','en','open');
    INSERT INTO tbl_comments
      (comment_post_id, comment_parent_id, comment_author_name, comment_author_ip, comment_author_email, comment_content, comment_status, comment_date)
    VALUES
      (${GDPR_POST_ID}, 0, 'GDPR Test User', '127.0.0.1', '${gdprEmail}',
       'A comment left by the GDPR e2e subject.', 'approved', '2026-02-02 10:00:00');
    INSERT INTO tbl_data_requests (request_type, request_email, request_status, request_ip, request_note, request_date) VALUES
      ('access','access@e2e.local','pending','127.0.0.1','Access request','2026-07-01 09:00:00'),
      ('rectification','rectify@e2e.local','pending','127.0.0.1','Rectify my name','2026-07-01 09:05:00'),
      ('deletion','${gdprEmail}','processing','127.0.0.1','GDPR delete me','2026-07-01 09:10:00'),
      ('access','erasure@e2e.local','processing','127.0.0.1','Right to be forgotten','2026-07-01 09:15:00'),
      ('deletion','old-done@e2e.local','completed','127.0.0.1','Done','2026-06-01 09:00:00'),
      ('access','rejected@e2e.local','rejected','127.0.0.1','Nope','2026-06-02 09:00:00');
    INSERT INTO tbl_privacy_logs (log_action, log_type, log_user_id, log_email, log_details, log_ip, log_date) VALUES
      ('data_request_created','access',NULL,'access@e2e.local','Data access request created','127.0.0.1','2026-07-01 09:00:00'),
      ('request_status_updated','deletion',NULL,'${gdprEmail}','Request status changed to: processing','127.0.0.1','2026-07-01 09:11:00'),
      ('data_exported','export',767,'admin@blogware.site','User data exported','127.0.0.1','2026-07-02 09:00:00');
    INSERT INTO tbl_privacy_policies (locale, policy_title, policy_content, is_default, created_at) VALUES
      ('en','E2E Privacy Policy','<h2>Our policy</h2><p>We collect minimal data for the e2e suite.</p>',1,'2026-01-01 00:00:00'),
      ('fr','Politique de confidentialité E2E','<h2>Notre politique</h2><p>Nous collectons un minimum de données.</p>',0,'2026-01-01 00:00:00');
    COMMIT;
  `);
  });
}

/**
 * Remove only the disposable GDPR fixture rows. Admin ID 767 and the fallback
 * author are never touched.
 */
export function cleanupGdprFixtures(): void {
  const gdprUser: string = sqlEscape(GDPR_USER);
  const noaccessUser: string = sqlEscape(NOACCESS_USER);
  const gdprEmail: string = sqlEscape(GDPR_EMAIL);
  const noaccessEmail: string = sqlEscape(NOACCESS_EMAIL);
  runSql(`
    DELETE FROM tbl_privacy_logs;
    DELETE FROM tbl_data_requests;
    DELETE FROM tbl_consents;
    DELETE FROM tbl_privacy_policies;
    DELETE FROM tbl_login_attempt;
    DELETE FROM tbl_settings WHERE setting_name IN ('consent_retention_days','privacy_log_retention_days');
    DELETE FROM tbl_user_token WHERE user_login IN ('${gdprUser}','${noaccessUser}');
    DELETE FROM tbl_comments WHERE comment_author_email IN ('${gdprEmail}','${noaccessEmail}');
    DELETE FROM tbl_posts WHERE post_author IN (SELECT ID FROM tbl_users WHERE user_login IN ('${gdprUser}','${noaccessUser}'));
    DELETE FROM tbl_users WHERE user_login IN ('${gdprUser}','${noaccessUser}');
  `);
}

/**
 * Re-seed deterministic consent/log rows for the retention phase. Expects
 * seedGdprFixtures() to have already run (clears the consent/log tables).
 */
export function seedRetentionFixtures(): void {
  withSeedLock(() => {
  runSql(`
    START TRANSACTION;
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
    COMMIT;
  `);
  });
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
