// @ts-check
// restoblog-fixtures.js
// Shared DB seeding, theme activation and cleanup helpers for the RestoBlog
// (Epicurean Dark) theme e2e suite. Mirrors tastybites-fixtures.js conventions
// (mysql CLI helpers, disposable namespaced rows, file-backed rate-limit
// cleanup) and the gdpr-fixtures.js conventions it grew from.
//
// CONCURRENCY RULE: this suite flips tbl_themes in the shared blogware_e2e
// database, so it must NEVER run at the same time as the default suite, the
// TastyBites suite or another invocation of itself. The dedicated
// playwright.restoblog.config.js activates RestoBlog in globalSetup and
// restores "blog" in globalTeardown.
import { execFileSync } from 'child_process';
import fs from 'fs';
import path from 'path';

export const BASE_URL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8099';
export const ADMIN_USER = 'administrator';

/** Directory holding the file-backed post-unlock rate limiter (md5(ip_post) keys). */
export const UNLOCK_RATE_LIMIT_DIR = path.join(
  process.cwd(),
  'public',
  'log',
  'unlock_attempts',
);

/** Tag shared by every disposable post this suite creates. */
export const RB_TAG = 'rb-e2e';

/** Slug prefix shared by every disposable post/page this suite creates. */
export const RB_SLUG_PREFIX = 'rb-e2e-';

/** Comment author email namespace claimed by this suite (removed on cleanup). */
export const RB_COMMENT_EMAIL = 'rb-e2e-comment@e2e.local';

/** Known password for the seeded protected post (plain value; hash below). */
export const RB_PROTECTED_PASSWORD = 'TastyLock!2026';

/**
 * bcrypt hash of RB_PROTECTED_PASSWORD generated with protect_post()'s scheme:
 * password_hash($password, PASSWORD_DEFAULT) - plain bcrypt, NOT the user
 * sha384-base64 scheme used for account passwords.
 */
const RB_PROTECTED_HASH = '$2y$12$uJ4IJHALZ0rFo1AvGSeKKuafhKV9XTJ.CqkX6cehooZ./DUgQHIn6';

/**
 * Passphrase key for the seeded protected post: hash('sha256', APP_KEY . password)
 * with the e2e APP_KEY (F5R5TE-WL7VSG-KKAZRH-377C04, set by e2e/router.php and
 * e2e/config.ci.php). Shared with tastybites-fixtures.js: both suites run
 * against the same blogware_e2e database.
 */
const RB_PROTECTED_PASSPHRASE = 'da0ed8fb4716b804f7ac5741efc7938e96c51d253982dcfe5f98b51300bdb73e';

/**
 * Encrypted content produced for RB_PROTECTED_SECRET under
 * RB_PROTECTED_PASSWORD using the legacy app_key AES path, which decrypt()
 * tries after the Defuse-derived key. That makes the fixture portable: it does
 * not depend on the per-checkout lib/utility/.lts cipher key. The secret is
 * stored only in encrypted form, exactly as the admin save path writes.
 */
const RB_PROTECTED_CONTENT =
  '7UsO2h090DHWk/bsA3re0mn/fxsboXNj5OYWWY+ntt1TB5NN9QB4y0PKyGBzIvGt8z6/2kmsfkAE7NPEe/lMfv8SOHj6+4nDtvS82MAJXfXg4x7dLsNI8ezEGiuTOX7JmYXmWcjEo788NnAWuU2eGKtSSKZTTfDmxXlGgwGE2j8/FpFuCCfgkHTiRa6BtSyj';

/** Slug of the seeded protected post. */
export const RB_PROTECTED_SLUG = 'rb-e2e-locked';

/**
 * Marker sentence embedded in the protected post content (asserted after
 * unlock). String is shared with the tastybites suite because the ciphertext
 * above was generated for it under the shared e2e key; the assertion itself is
 * still meaningful (byte-exact decrypt + rendering).
 */
export const RB_PROTECTED_SECRET = 'TastyBites secret content unlocked by the e2e suite.';

// ---------------------------------------------------------------------------
// DB helpers (mysql CLI, mirroring gdpr-fixtures.js / tastybites-fixtures.js)
// ---------------------------------------------------------------------------

/**
 * Run a SQL statement against the dedicated e2e database.
 *
 * @param {string} sql
 * @returns {string} mysql CLI stdout
 */
export function runSql(sql) {
  return execFileSync(
    'mysql',
    [
      '-h',
      process.env.E2E_DB_HOST || '127.0.0.1',
      '-P',
      process.env.E2E_DB_PORT || '3306',
      '-u',
      process.env.E2E_DB_USER || 'blogwareuser',
      `-p${process.env.E2E_DB_PASS || process.env.MYSQL_PWD || 'userblogware'}`,
      process.env.E2E_DB_NAME || 'blogware_e2e',
      '-e',
      sql,
    ],
    { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] },
  );
}

/**
 * Run a SQL SELECT and return the first-column value of the first row.
 *
 * @param {string} sql
 * @returns {string}
 */
export function dbScalar(sql) {
  const out = runSql(sql).trim();
  return out === '' ? '' : out.split('\n').pop().trim();
}

/**
 * Run a SQL SELECT COUNT(...) and return it as a number.
 *
 * @param {string} sql
 * @returns {number}
 */
export function dbCount(sql) {
  const out = dbScalar(sql);
  return out === '' ? 0 : parseInt(out, 10);
}

// ---------------------------------------------------------------------------
// Theme activation / restore
// ---------------------------------------------------------------------------

/**
 * Make RestoBlog the single active frontend theme. Idempotent: registers the
 * theme row if missing, deactivates everything else, then activates RestoBlog.
 */
export function activateRestoblogTheme() {
  runSql(`
    UPDATE tbl_themes SET theme_status='N' WHERE theme_status='Y';
    INSERT INTO tbl_themes (theme_title, theme_desc, theme_designer, theme_directory, theme_status)
      SELECT 'RestoBlog','Epicurean Dark culinary experience theme','restoblog','restoblog','Y'
      WHERE NOT EXISTS (SELECT 1 FROM tbl_themes WHERE theme_directory='restoblog');
    UPDATE tbl_themes SET theme_status='Y' WHERE theme_directory='restoblog';
  `);
}

/**
 * Restore the reference "blog" theme so the default Playwright suite keeps its
 * baseline assumptions. Idempotent.
 */
export function restoreBlogTheme() {
  runSql(`
    UPDATE tbl_themes SET theme_status='N' WHERE theme_status='Y';
    UPDATE tbl_themes SET theme_status='Y' WHERE theme_directory='blog';
  `);
}

// ---------------------------------------------------------------------------
// Seeding / cleanup
// ---------------------------------------------------------------------------

/**
 * Idempotently wipe and re-seed the disposable RestoBlog fixture rows:
 * three public posts sharing tag rb-e2e (for listing + prev/next adjacency),
 * linked to topic 200, plus one password-protected post with a known password.
 * Safe to call from beforeAll under serial project execution.
 */
export function seedRestoblogFixtures() {
  cleanupRestoblogFixtures();

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
       'RestoBlog E2E Oldest', '${RB_SLUG_PREFIX}oldest',
       '<p>Oldest seeded body.</p>', 'Oldest summary.',
       'publish','public','blog','en','${RB_TAG}','open','',''),
      (0, (SELECT ID FROM tbl_users WHERE user_login='${ADMIN_USER}'), '2026-08-11 08:00:00',
       'RestoBlog E2E Middle', '${RB_SLUG_PREFIX}middle',
       '<p>Middle seeded body.</p>', 'Middle summary.',
       'publish','public','blog','en','${RB_TAG}','open','',''),
      (0, (SELECT ID FROM tbl_users WHERE user_login='${ADMIN_USER}'), '2026-08-12 08:00:00',
       'RestoBlog E2E Newest', '${RB_SLUG_PREFIX}newest',
       '<p>Newest seeded body.</p>', 'Newest summary.',
       'publish','public','blog','en','${RB_TAG}','open','',''),
      (0, (SELECT ID FROM tbl_users WHERE user_login='${ADMIN_USER}'), '2026-08-13 08:00:00',
       'RestoBlog E2E Locked', '${RB_PROTECTED_SLUG}',
       '${RB_PROTECTED_CONTENT}', '',
       'publish','protected','blog','en','','closed',
       '${RB_PROTECTED_HASH}', '${RB_PROTECTED_PASSPHRASE}');

    INSERT INTO tbl_post_topic (post_id, topic_id)
    SELECT ID, 200 FROM tbl_posts WHERE post_slug LIKE '${RB_SLUG_PREFIX}%'
      AND post_visibility = 'public';
  `);
}

/**
 * Remove only the disposable RestoBlog fixture rows (slug prefix rb-e2e-,
 * comment author email namespace, related topic links and comments).
 */
export function cleanupRestoblogFixtures() {
  runSql(`
    DELETE FROM tbl_settings WHERE setting_name IN ('lang_default','lang_available','lang_auto_detect');
    DELETE FROM tbl_comments WHERE comment_author_email = '${RB_COMMENT_EMAIL}';
    DELETE FROM tbl_post_topic WHERE post_id IN (
      SELECT ID FROM (
        SELECT ID FROM tbl_posts WHERE post_slug LIKE '${RB_SLUG_PREFIX}%'
      ) AS rb_ids
    );
    DELETE FROM tbl_posts WHERE post_slug LIKE '${RB_SLUG_PREFIX}%';
  `);
}

/**
 * Delete every file-backed unlock rate-limit counter so wrong-password tests
 * never trip the limiter left behind by earlier runs/projects.
 */
export function clearUnlockRateLimit() {
  if (!fs.existsSync(UNLOCK_RATE_LIMIT_DIR)) return;
  for (const f of fs.readdirSync(UNLOCK_RATE_LIMIT_DIR)) {
    fs.rmSync(path.join(UNLOCK_RATE_LIMIT_DIR, f), { force: true });
  }
}