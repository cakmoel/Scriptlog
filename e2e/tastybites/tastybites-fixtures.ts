// tastybites-fixtures.ts
// Shared DB seeding, theme activation and cleanup helpers for the TastyBites
// theme e2e suite. Mirrors gdpr-fixtures.ts conventions (mysql CLI helpers,
// disposable namespaced rows, file-backed rate-limit cleanup).
//
// CONCURRENCY RULE: this suite flips tbl_themes in the shared blogware_e2e
// database, so it must NEVER run at the same time as the default suite or
// another invocation of itself. The dedicated playwright.tastybites.config.ts
// activates TastyBites in globalSetup and restores "blog" in globalTeardown.
import { execFileSync } from 'child_process';
import fs from 'fs';
import path from 'path';

export const BASE_URL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8099';
export const ADMIN_USER = 'administrator';

/** Directory holding the file-backed post-unlock rate limiter (md5(ip_post) keys). */
export const UNLOCK_RATE_LIMIT_DIR = '/var/www/blogware/public_html/public/log/unlock_attempts';

/** Tag shared by every disposable post this suite creates. */
export const TB_TAG = 'tb-e2e';

/** Slug prefix shared by every disposable post/page this suite creates. */
export const TB_SLUG_PREFIX = 'tb-e2e-';

/** Slug of the seeded static page. */
export const TB_PAGE_SLUG = 'tb-e2e-about';

/**
 * Numeric ID of the seeded static page. The `pg` query-string route resolves
 * pages by numeric ID (FrontService::getSimplePage casts to (int)), so tests
 * must look up the auto-increment ID after seeding rather than pass a slug.
 * Returns 0 if the page is not seeded yet.
 */
export function tbPageId(): number {
  const out = dbScalar(
    `SELECT ID FROM tbl_posts WHERE post_slug='${TB_PAGE_SLUG}' AND post_type='page' LIMIT 1`,
  );
  return out === '' ? 0 : parseInt(out, 10);
}

/** Comment author email namespace used by comment-form tests. */
export const TB_COMMENT_EMAIL = 'tb-e2e-comment@e2e.local';

/** Known password for the seeded protected post (plain value; hash below). */
export const TB_PROTECTED_PASSWORD = 'TastyLock!2026';

/**
 * bcrypt hash of TB_PROTECTED_PASSWORD generated with protect_post()'s scheme:
 * password_hash($password, PASSWORD_DEFAULT) - plain bcrypt, NOT the user
 * sha384-base64 scheme used for account passwords.
 */
const TB_PROTECTED_HASH = '$2y$12$uJ4IJHALZ0rFo1AvGSeKKuafhKV9XTJ.CqkX6cehooZ./DUgQHIn6';

/**
 * Passphrase key for the seeded protected post: hash('sha256', APP_KEY . password)
 * with the e2e APP_KEY, generated through protect_post() itself so decryption
 * during the unlock flow reproduces byte-exact behavior.
 */
const TB_PROTECTED_PASSPHRASE = '62b5d5f60f1762fcca682bd32c7c757b0a83866e9258b5c77a3138aa33d1d51c';

/**
 * Encrypted content produced by protect_post() for TB_PROTECTED_SECRET under
 * TB_PROTECTED_PASSWORD with the Defuse cipher key active (same bootstrap as
 * the web server). The secret is stored only in encrypted form, mirroring what
 * the admin save path writes.
 */
const TB_PROTECTED_CONTENT =
  '9gh1cNdnbdUFFrujsD1JM2y3/HM+OEuRfi8gL9mL255KjdlMbq+Do1/oablveqBTTgx4tMm35xJuMRcwfJ0bncVEhPw01gBZEFWMGSH19HCMZE7cNCJ8MCzHgr+4K/le5DpUeKbRKCTADQ08BmVtV4X3cyryoE8z7SpTILWvVpeanfMyc9t+Q8/oKLDVIfjtZ2GKV2ICucTtNu+h7iWf/w==';

/** Slug of the seeded protected post. */
export const TB_PROTECTED_SLUG = 'tb-e2e-locked';

/** Marker sentence embedded in the protected post content (asserted after unlock). */
export const TB_PROTECTED_SECRET = 'TastyBites secret content unlocked by the e2e suite.';

// ---------------------------------------------------------------------------
// DB helpers (mysql CLI, mirroring gdpr-fixtures.js)
// ---------------------------------------------------------------------------

/**
 * Run a SQL statement against the dedicated e2e database.
 */
export function runSql(sql: string): string {
  return execFileSync(
    'mysql',
    ['-h', '127.0.0.1', '-P', '3306', '-u', 'blogwareuser', '-puserblogware', 'blogware_e2e', '-e', sql],
    { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] },
  );
}

/**
 * Run a SQL SELECT and return the first-column value of the first row.
 */
export function dbScalar(sql: string): string {
  const out = runSql(sql).trim();
  return out === '' ? '' : out.split('\n').pop()?.trim() ?? '';
}

/**
 * Run a SQL SELECT COUNT(...) and return it as a number.
 */
export function dbCount(sql: string): number {
  const out = dbScalar(sql);
  return out === '' ? 0 : parseInt(out, 10);
}

// ---------------------------------------------------------------------------
// Theme activation / restore
// ---------------------------------------------------------------------------

/**
 * Make TastyBites the single active frontend theme. Idempotent: registers the
 * theme row if missing, deactivates everything else, then activates TastyBites.
 */
export function activateTastybitesTheme(): void {
  runSql(`
    UPDATE tbl_themes SET theme_status='N' WHERE theme_status='Y';
    INSERT INTO tbl_themes (theme_title, theme_desc, theme_designer, theme_directory, theme_status)
      SELECT 'Tastybites','Blog theme for food blogger','tastybites','tastybites','Y'
      WHERE NOT EXISTS (SELECT 1 FROM tbl_themes WHERE theme_directory='tastybites');
    UPDATE tbl_themes SET theme_status='Y' WHERE theme_directory='tastybites';
  `);
}

/**
 * Restore the reference "blog" theme so the default Playwright suite keeps its
 * baseline assumptions. Idempotent.
 */
export function restoreBlogTheme(): void {
  runSql(`
    UPDATE tbl_themes SET theme_status='N' WHERE theme_status='Y';
    UPDATE tbl_themes SET theme_status='Y' WHERE theme_directory='blog';
  `);
}

// ---------------------------------------------------------------------------
// Seeding / cleanup
// ---------------------------------------------------------------------------

/**
 * Idempotently wipe and re-seed the disposable TastyBites fixture rows:
 * three public posts sharing tag tb-e2e (for listing + prev/next adjacency),
 * linked to topic 200, plus one password-protected post with a known password.
 * Safe to call from beforeAll under serial project execution.
 */
export function seedTastybitesFixtures(): void {
  cleanupTastybitesFixtures();

  // Seed the locale-switcher settings: LocaleDetector falls back to ['en']
  // when lang_available is absent, which would hide the es/ar menu entries.
  runSql(`
    DELETE FROM tbl_settings WHERE setting_name IN ('lang_default','lang_available','lang_auto_detect');
    INSERT INTO tbl_settings (setting_name, setting_value) VALUES
      ('lang_default','en'),
      ('lang_available','en,es,ar'),
      ('lang_auto_detect','0');
  `);

  runSql(`
    INSERT INTO tbl_posts
      (media_id, post_author, post_date, post_title, post_slug, post_content, post_summary,
       post_status, post_visibility, post_type, post_locale, post_tags, comment_status,
       post_password, passphrase)
    VALUES
      (0, (SELECT ID FROM tbl_users WHERE user_login='${ADMIN_USER}'), '2026-08-10 08:00:00',
       'TastyBites E2E Oldest', '${TB_SLUG_PREFIX}oldest',
       '<p>Oldest seeded body.</p>', 'Oldest summary.',
       'publish','public','blog','en','${TB_TAG}','open','',''),
      (0, (SELECT ID FROM tbl_users WHERE user_login='${ADMIN_USER}'), '2026-08-11 08:00:00',
       'TastyBites E2E Middle', '${TB_SLUG_PREFIX}middle',
       '<p>Middle seeded body.</p>', 'Middle summary.',
       'publish','public','blog','en','${TB_TAG}','open','',''),
      (0, (SELECT ID FROM tbl_users WHERE user_login='${ADMIN_USER}'), '2026-08-12 08:00:00',
       'TastyBites E2E Newest', '${TB_SLUG_PREFIX}newest',
       '<p>Newest seeded body.</p>', 'Newest summary.',
       'publish','public','blog','en','${TB_TAG}','open','',''),
      (0, (SELECT ID FROM tbl_users WHERE user_login='${ADMIN_USER}'), '2026-08-13 08:00:00',
       'TastyBites E2E Locked', '${TB_PROTECTED_SLUG}',
       '${TB_PROTECTED_CONTENT}', '',
       'publish','protected','blog','en','','closed',
       '${TB_PROTECTED_HASH}', '${TB_PROTECTED_PASSPHRASE}');

    INSERT INTO tbl_post_topic (post_id, topic_id)
    SELECT ID, 200 FROM tbl_posts WHERE post_slug LIKE '${TB_SLUG_PREFIX}%'
      AND post_visibility = 'public';

    INSERT INTO tbl_posts
      (media_id, post_author, post_date, post_title, post_slug, post_content, post_summary,
       post_status, post_visibility, post_type, post_locale, post_tags, comment_status,
       post_password, passphrase)
    VALUES
      (0, (SELECT ID FROM tbl_users WHERE user_login='${ADMIN_USER}'), '2026-08-14 08:00:00',
       'TastyBites E2E About Page', '${TB_PAGE_SLUG}',
       '<p>This is the TastyBites E2E about page content.</p>', 'About page summary.',
       'publish','public','page','en','','closed','','');
  `);
}

/**
 * Remove only the disposable TastyBites fixture rows (slug prefix tb-e2e-,
 * comment author email namespace, related topic links and comments).
 */
export function cleanupTastybitesFixtures(): void {
  runSql(`
    DELETE FROM tbl_settings WHERE setting_name IN ('lang_default','lang_available','lang_auto_detect');
    DELETE FROM tbl_comments WHERE comment_author_email = '${TB_COMMENT_EMAIL}';
    DELETE FROM tbl_post_topic WHERE post_id IN (
      SELECT ID FROM (
        SELECT ID FROM tbl_posts WHERE post_slug LIKE '${TB_SLUG_PREFIX}%'
      ) AS tb_ids
    );
    DELETE FROM tbl_posts WHERE post_slug LIKE '${TB_SLUG_PREFIX}%';
    DELETE FROM tbl_posts WHERE post_slug = '${TB_PAGE_SLUG}';
  `);
}

/**
 * Delete every file-backed unlock rate-limit counter so wrong-password tests
 * never trip the limiter left behind by earlier runs/projects.
 */
export function clearUnlockRateLimit(): void {
  if (!fs.existsSync(UNLOCK_RATE_LIMIT_DIR)) return;
  for (const f of fs.readdirSync(UNLOCK_RATE_LIMIT_DIR)) {
    fs.rmSync(path.join(UNLOCK_RATE_LIMIT_DIR, f), { force: true });
  }
}