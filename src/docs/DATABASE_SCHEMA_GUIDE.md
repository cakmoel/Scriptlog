# Database Schema Guide

**Version:** 1.7.0 | **Last Updated:** August 2026

This guide documents every table in the Scriptlog database. It is written for developers of all levels, from beginners setting up their first installation to senior developers extending the schema.

## How to read this document

- Every table lists its purpose, its columns, its indexes, and its relationships.
- "Attributes" means the SQL column definition: data type, whether it allows NULL, and its default value.
- The column descriptions explain what the value is used for in the application.
- If a column or table is noted as "application-managed", there is no database foreign key. The application enforces the link with its own checks.

## Schema source of truth

The database is created by the installation wizard. The single source of truth for the schema is:

```
install/include/dbtable.php
```

The function `get_table_definitions($prefix)` in that file returns every `CREATE TABLE` statement plus a few seed inserts. The installer runs these statements in order. If you change the schema, change this file, never a live database by hand. See the section "Changing the schema" at the end.

## The table prefix

- Production databases use a table prefix that is generated randomly during installation (`generate_table_prefix(6)` produces six lowercase letters, e.g. `abc123_`). The wizard writes it into the created config under `db.prefix`, and it can be overridden in `config.php` (or via `DB_PREFIX` in `.env`). The `tpglkl_` value seen in the stock `config.php` is only a fallback placeholder. For example the posts table is `<prefix>tbl_posts`.
- The test database `blogware_test` uses no prefix. Its tables are named `tbl_posts`, `tbl_comments`, and so on.
- Every statement in `dbtable.php` builds its name from the prefix, so the same file works for both.

## Conventions shared by every table

- Engine: InnoDB.
- Character set: utf8mb4. The collation is `utf8mb4_general_ci` on every table except `tbl_api_keys`, which uses `utf8mb4_unicode_ci`.
- Primary keys: `ID` (uppercase) `BIGINT(20) UNSIGNED AUTO_INCREMENT` in most tables. The two exceptions are `tbl_menu` and `tbl_themes` (INT instead of BIGINT) and `tbl_api_keys` (lowercase `id`, INT).
- Dates: tables that track change over time use `TIMESTAMP` with `DEFAULT CURRENT_TIMESTAMP`. Content tables (`tbl_posts`, `tbl_comments`) use `DATETIME`.
- Status values are stored as either `VARCHAR` with a string value (`publish`, `pending`, `accepted`) or MySQL `ENUM` where the allowed set is fixed (`Y`/`N`, `ltr`/`rtl`).
- Only one table defines a real `FOREIGN KEY` constraint: `tbl_api_keys.user_id` references `tbl_users.ID` with `ON DELETE CASCADE`. All other relationships are application-managed.

## Table count

Scriptlog has 22 tables. The list below is the complete set.

| # | Table | Purpose |
|---|-------|---------|
| 1 | `tbl_users` | User accounts and login data |
| 2 | `tbl_user_token` | Persistent login tokens (remember me) |
| 3 | `tbl_login_attempt` | Failed login attempts by IP, used for lockout |
| 4 | `tbl_api_keys` | REST API keys issued to users |
| 5 | `tbl_posts` | Blog posts and static pages |
| 6 | `tbl_topics` | Categories (topics) |
| 7 | `tbl_post_topic` | Many-to-many link between posts and topics |
| 8 | `tbl_comments` | Comments, with nested replies |
| 9 | `tbl_media` | Media library entries |
| 10 | `tbl_mediameta` | Key-value metadata for media items |
| 11 | `tbl_media_download` | One-time secure download grants |
| 12 | `tbl_download_log` | Audit log of media downloads |
| 13 | `tbl_menu` | Navigation menu items |
| 14 | `tbl_settings` | Key-value configuration settings |
| 15 | `tbl_plugin` | Registered plugins |
| 16 | `tbl_themes` | Registered themes |
| 17 | `tbl_consents` | GDPR cookie and consent records |
| 18 | `tbl_data_requests` | GDPR data access and erasure requests |
| 19 | `tbl_privacy_logs` | Audit log of privacy actions |
| 20 | `tbl_privacy_policies` | Localized privacy policy content |
| 21 | `tbl_languages` | Supported languages |
| 22 | `tbl_translations` | UI translation strings per language |

---

## Users and authentication

### tbl_users

Stores every user account. It is the table that powers login, permissions, and account security.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | User identifier |
| `user_login` | VARCHAR(60) NOT NULL, UNIQUE | Login name |
| `user_email` | VARCHAR(100) NOT NULL, UNIQUE | Email address |
| `user_pass` | VARCHAR(255) NOT NULL | Password hash. Always created with `password_hash()`, never plaintext |
| `user_level` | VARCHAR(20) NOT NULL | Role: `administrator`, `manager`, `editor`, `author`, `contributor`, or `subscriber` |
| `user_fullname` | VARCHAR(120) DEFAULT NULL | Display name |
| `user_url` | VARCHAR(100) DEFAULT NULL | Website or profile URL |
| `user_registered` | DATETIME NOT NULL DEFAULT '1988-07-01 08:00:00' | Registration date |
| `user_activation_key` | VARCHAR(255) NOT NULL DEFAULT '' | Email activation key, when used |
| `user_reset_key` | VARCHAR(255) DEFAULT NULL | Password reset token |
| `user_reset_complete` | VARCHAR(3) DEFAULT 'No' | Whether the password reset finished (`Yes`/`No`) |
| `user_session` | VARCHAR(255) NOT NULL | Stored session marker |
| `user_banned` | TINYINT NOT NULL DEFAULT 0 | 1 bans the account |
| `user_signin_count` | INT NOT NULL DEFAULT 0 | Number of successful sign-ins |
| `user_locked_until` | DATETIME DEFAULT NULL | When a temporary lockout expires, if any |
| `login_time` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Last login or last profile change |

Indexes: PRIMARY KEY (`ID`), UNIQUE (`user_login`), UNIQUE (`user_email`).

### tbl_user_token

Holds the "remember me" tokens used by persistent authentication. Each token is split into a selector (stored directly) and a verifier hash.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Token row identifier |
| `user_login` | VARCHAR(60) NOT NULL | Login name of the token owner, matches `tbl_users.user_login` |
| `pwd_hash` | VARCHAR(255) NOT NULL | Hash of the token verifier half |
| `selector_hash` | VARCHAR(255) NOT NULL | Selector half used to look up the token |
| `is_expired` | INT NOT NULL DEFAULT 0 | 1 marks the token expired |
| `expired_date` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Expiry timestamp |

Indexes: PRIMARY KEY (`ID`). The link to `tbl_users` is application-managed.

### tbl_login_attempt

Used for rate limiting and lockout. It records failed login attempts grouped by IP address.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ip_address` | VARCHAR(255) NOT NULL | Client IP that made the attempt |
| `login_date` | DATETIME NOT NULL DEFAULT '1989-06-12 12:00:00' | When the attempt happened |

No primary key. The authentication layer counts rows newer than a time window to decide whether to lock the account, and deletes rows older than 24 hours.

### tbl_api_keys

REST API keys issued to users. The raw key is shown once at creation; only its hash is stored.

| Column | Attributes | Description |
|--------|------------|-------------|
| `id` | INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Key identifier (note the lowercase name) |
| `user_id` | BIGINT(20) UNSIGNED NOT NULL | Owner, references `tbl_users.ID` |
| `key_hash` | VARCHAR(255) NOT NULL | `password_hash()` of the raw key |
| `description` | VARCHAR(255) DEFAULT NULL | Human-readable label for the key |
| `created_at` | DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP | Creation time |
| `expires_at` | DATETIME DEFAULT NULL | Optional expiry; NULL means no expiry |
| `last_used_at` | DATETIME DEFAULT NULL | Updated on each successful authentication |
| `is_revoked` | TINYINT(1) NOT NULL DEFAULT 0 | 1 revokes the key |

Indexes: PRIMARY KEY (`id`), KEY `idx_user_id` (`user_id`), KEY `idx_key_hash` (`key_hash`(191)).

Relationships: `CONSTRAINT fk_api_keys_user FOREIGN KEY (user_id) REFERENCES tbl_users(ID) ON DELETE CASCADE`. This is the only real foreign key in the database.

---

## Content

### tbl_posts

Stores both blog posts and static pages. The column `post_type` decides which: `blog` for posts, `page` for pages.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Content identifier |
| `media_id` | BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 | Featured media, matches `tbl_media.ID` |
| `post_author` | BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 | Author, matches `tbl_users.ID` |
| `post_date` | DATETIME NOT NULL DEFAULT '1989-06-12 12:00:00' | Publication date |
| `post_modified` | DATETIME DEFAULT NULL | Last modification date |
| `post_title` | TINYTEXT NOT NULL | Title |
| `post_slug` | VARCHAR(255) NOT NULL | URL slug, indexed and used for canonical URLs |
| `post_content` | LONGTEXT NOT NULL | Main content body |
| `post_summary` | MEDIUMTEXT DEFAULT NULL | Short excerpt used in listings |
| `post_keyword` | TEXT DEFAULT NULL | SEO keywords |
| `post_status` | VARCHAR(20) NOT NULL DEFAULT 'publish' | `publish`, `draft`, `pending`, or similar |
| `post_visibility` | VARCHAR(20) NOT NULL DEFAULT 'public' | Visibility level |
| `post_password` | VARCHAR(255) DEFAULT NULL | Password for password-protected posts |
| `post_tags` | TEXT DEFAULT NULL | Comma-separated tag list |
| `post_headlines` | INT NOT NULL DEFAULT 0 | Number of headline entries in the content |
| `post_sticky` | INT NOT NULL DEFAULT 0 | 1 pins the post to the top of listings |
| `post_type` | VARCHAR(120) NOT NULL DEFAULT 'blog' | `blog` or `page` |
| `post_locale` | VARCHAR(10) NOT NULL DEFAULT 'en' | Content language code |
| `comment_status` | VARCHAR(20) NOT NULL DEFAULT 'open' | `open` or `closed` |
| `passphrase` | VARCHAR(255) DEFAULT NULL | Hash used by the protected post unlock flow |

Indexes: PRIMARY KEY (`ID`), KEY `author_id` (`post_author`), KEY `post_media` (`media_id`), KEY `idx_post_slug` (`post_slug`), KEY `idx_post_locale` (`post_locale`), FULLTEXT KEY (`post_tags`, `post_title`, `post_content`).

The FULLTEXT index powers the search engine. The slug index supports canonical URL validation.

### tbl_topics

Categories, called topics in this application.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Topic identifier |
| `topic_title` | VARCHAR(255) NOT NULL | Display name |
| `topic_slug` | VARCHAR(255) NOT NULL | URL slug |
| `topic_status` | ENUM('Y','N') NOT NULL DEFAULT 'Y' | `Y` active, `N` hidden |
| `topic_locale` | VARCHAR(10) NOT NULL DEFAULT 'en' | Category language code |

Indexes: PRIMARY KEY (`ID`), KEY `idx_topic_slug` (`topic_slug`), KEY `idx_topic_locale` (`topic_locale`).

### tbl_post_topic

Many-to-many join between posts and topics. A post can be in several topics and a topic can contain several posts.

| Column | Attributes | Description |
|--------|------------|-------------|
| `post_id` | BIGINT(20) UNSIGNED NOT NULL | References `tbl_posts.ID` |
| `topic_id` | BIGINT(20) UNSIGNED NOT NULL | References `tbl_topics.ID` |

Indexes: PRIMARY KEY (`post_id`, `topic_id`). The composite primary key also prevents duplicate links.

### tbl_comments

Comments on posts, including nested replies. The reply relationship is modeled with `comment_parent_id` instead of a separate table.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Comment identifier |
| `comment_post_id` | BIGINT(20) UNSIGNED NOT NULL | Parent post, references `tbl_posts.ID` |
| `comment_parent_id` | BIGINT(20) NOT NULL DEFAULT 0 | 0 for a top-level comment, otherwise the parent comment ID |
| `comment_author_name` | VARCHAR(60) NOT NULL | Commenter name |
| `comment_author_ip` | VARCHAR(100) NOT NULL | Commenter IP, used for moderation |
| `comment_author_email` | VARCHAR(100) DEFAULT NULL | Commenter email |
| `comment_content` | TEXT NOT NULL | Comment body |
| `comment_status` | VARCHAR(20) NOT NULL DEFAULT 'pending' | `pending`, `approved`, or `spam` |
| `comment_date` | DATETIME NOT NULL DEFAULT '1988-07-01 08:00:00' | Submission date |

Indexes: PRIMARY KEY (`ID`), KEY `id_comment_post` (`comment_post_id`).

---

## Media and downloads

### tbl_media

The media library. Each row describes one uploaded file.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Media identifier |
| `media_filename` | VARCHAR(200) DEFAULT NULL | Stored file name |
| `media_caption` | VARCHAR(200) DEFAULT NULL | Caption text |
| `media_type` | VARCHAR(90) NOT NULL | MIME type or media kind |
| `media_target` | VARCHAR(20) NOT NULL DEFAULT 'blog' | Where the media is used (`blog`, `post`, and so on) |
| `media_user` | VARCHAR(20) NOT NULL | Uploading user |
| `media_access` | VARCHAR(10) NOT NULL DEFAULT 'public' | Access level |
| `media_status` | INT NOT NULL DEFAULT 0 | Status flag |

Indexes: PRIMARY KEY (`ID`).

### tbl_mediameta

Key-value metadata attached to media items. Used for extra attributes that do not have their own column.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Metadata identifier |
| `media_id` | BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 | References `tbl_media.ID` |
| `meta_key` | VARCHAR(255) NOT NULL | Metadata key |
| `meta_value` | LONGTEXT DEFAULT NULL | Metadata value |

Indexes: PRIMARY KEY (`ID`), KEY `media_id` (`media_id`), KEY `meta_key` (`meta_key`(191)).

### tbl_media_download

Represents a secure download grant. When a visitor gets a download link, a row is created here with a unique identifier. The link only works while the grant has not expired.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Grant identifier |
| `media_id` | BIGINT(20) UNSIGNED NOT NULL | References `tbl_media.ID` |
| `media_identifier` | CHAR(36) NOT NULL, UNIQUE | UUID used in the download URL |
| `before_expired` | VARCHAR(50) NOT NULL | Expiry time window for the grant |
| `ip_address` | VARCHAR(50) NOT NULL | IP that requested the grant |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Creation time |

Indexes: PRIMARY KEY (`ID`), UNIQUE (`media_identifier`), KEY `id_media` (`media_id`).

### tbl_download_log

Audit trail for actual file downloads.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Log identifier |
| `media_id` | BIGINT(20) UNSIGNED NOT NULL | References `tbl_media.ID` |
| `media_identifier` | CHAR(36) NOT NULL | The grant identifier used |
| `ip_address` | VARCHAR(50) NOT NULL | Downloading IP |
| `user_agent` | VARCHAR(255) DEFAULT NULL | Client user agent |
| `downloaded_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Download time |
| `status` | VARCHAR(20) NOT NULL DEFAULT 'success' | Result of the download |

Indexes: PRIMARY KEY (`ID`), KEY `idx_media_id` (`media_id`), KEY `idx_downloaded_at` (`downloaded_at`), KEY `idx_media_identifier` (`media_identifier`).

---

## Navigation and configuration

### tbl_menu

Navigation menu items. Menus support nesting through `parent_id` and ordering through `menu_sort`.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Menu item identifier |
| `menu_label` | VARCHAR(200) NOT NULL | Display label |
| `menu_link` | VARCHAR(255) DEFAULT NULL | Link URL |
| `menu_status` | ENUM('Y','N') NOT NULL DEFAULT 'N' | `Y` visible, `N` hidden |
| `menu_visibility` | VARCHAR(20) NOT NULL DEFAULT 'public' | Who can see the item |
| `parent_id` | INT(11) UNSIGNED NOT NULL DEFAULT 0 | 0 for a top-level item, otherwise the parent item ID |
| `menu_sort` | INT(11) UNSIGNED NOT NULL DEFAULT 0 | Ordering within its level |
| `menu_locale` | VARCHAR(10) NOT NULL DEFAULT 'en' | Menu language code |

Indexes: PRIMARY KEY (`ID`), KEY `idx_menu_locale` (`menu_locale`).

### tbl_settings

Key-value configuration store. Application settings that are not hard-coded live here. The value is always stored as text.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Setting identifier |
| `setting_name` | VARCHAR(255) NOT NULL | Setting key |
| `setting_value` | TEXT DEFAULT NULL | Setting value |

Indexes: PRIMARY KEY (`ID`), KEY `setting_name` (`setting_name`(191)), KEY `setting_value` (`setting_value`(191)).

### tbl_plugin

Registered plugins.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Plugin identifier |
| `plugin_name` | VARCHAR(100) NOT NULL | Plugin name |
| `plugin_link` | VARCHAR(255) NOT NULL DEFAULT '#' | Plugin URL |
| `plugin_directory` | VARCHAR(100) NOT NULL | Directory the plugin loads from |
| `plugin_desc` | TINYTEXT DEFAULT NULL | Description |
| `plugin_status` | ENUM('Y','N') NOT NULL DEFAULT 'N' | `Y` active, `N` inactive |
| `plugin_level` | VARCHAR(20) NOT NULL | Minimum user level allowed to use it |
| `plugin_sort` | INT DEFAULT NULL | Ordering |

Indexes: PRIMARY KEY (`ID`).

### tbl_themes

Registered themes.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Theme identifier |
| `theme_title` | VARCHAR(100) NOT NULL | Theme name |
| `theme_desc` | TINYTEXT DEFAULT NULL | Description |
| `theme_designer` | VARCHAR(90) NOT NULL | Author |
| `theme_directory` | VARCHAR(100) NOT NULL | Directory the theme loads from |
| `theme_status` | ENUM('Y','N') NOT NULL DEFAULT 'N' | `Y` active, `N` inactive |

Indexes: PRIMARY KEY (`ID`).

---

## GDPR and privacy

### tbl_consents

Records of cookie and consent choices, kept for GDPR compliance.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Consent identifier |
| `consent_type` | VARCHAR(50) NOT NULL | Consent category, for example analytics or marketing |
| `consent_status` | ENUM('accepted','rejected') NOT NULL | The visitor's choice |
| `consent_ip` | VARCHAR(45) NOT NULL | IP of the visitor |
| `consent_user_agent` | VARCHAR(255) DEFAULT NULL | Browser user agent |
| `consent_date` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | When the choice was made |
| `consent_updated` | TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP | Last update to the record |

Indexes: PRIMARY KEY (`ID`), KEY `consent_type` (`consent_type`), KEY `consent_date` (`consent_date`).

### tbl_data_requests

GDPR data access and erasure requests submitted by users.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Request identifier |
| `request_type` | VARCHAR(50) NOT NULL | `access` or `erasure` |
| `request_email` | VARCHAR(100) NOT NULL | Email of the requester |
| `request_status` | ENUM('pending','processing','completed','rejected') NOT NULL DEFAULT 'pending' | Lifecycle state |
| `request_ip` | VARCHAR(45) NOT NULL | IP of the requester |
| `request_note` | TEXT DEFAULT NULL | Free-text note from the requester |
| `request_date` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | Submission time |
| `request_updated` | TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP | Last status change |
| `request_completed_date` | DATETIME DEFAULT NULL | When the request finished |

Indexes: PRIMARY KEY (`ID`), KEY `request_type` (`request_type`), KEY `request_status` (`request_status`), KEY `request_email` (`request_email`).

### tbl_privacy_logs

Audit log of privacy-related actions, such as exporting data or handling a request.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Log identifier |
| `log_action` | VARCHAR(50) NOT NULL | Action performed |
| `log_type` | VARCHAR(50) NOT NULL | Category of the action |
| `log_user_id` | BIGINT(20) UNSIGNED DEFAULT NULL | Admin user who performed it, if any |
| `log_email` | VARCHAR(100) DEFAULT NULL | Related email, if any |
| `log_details` | TEXT DEFAULT NULL | Free-form details |
| `log_ip` | VARCHAR(45) NOT NULL | IP of the actor |
| `log_date` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | When the action happened |

Indexes: PRIMARY KEY (`ID`), KEY `log_action` (`log_action`), KEY `log_type` (`log_type`), KEY `log_date` (`log_date`).

### tbl_privacy_policies

Localized privacy policy content. One row per language.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Policy identifier |
| `locale` | VARCHAR(10) NOT NULL DEFAULT 'en', UNIQUE | Language code |
| `policy_title` | VARCHAR(255) NOT NULL | Policy title |
| `policy_content` | LONGTEXT NOT NULL | Full policy text |
| `is_default` | TINYINT(1) NOT NULL DEFAULT 0 | 1 marks the default policy |
| `created_at` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP | Last update |

Indexes: PRIMARY KEY (`ID`), UNIQUE (`locale`).

---

## Internationalization

### tbl_languages

Supported languages. The default installation seeds English (`en`); the full set is `en`, `ar`, `zh`, `fr`, `ru`, `es`, `id`.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Language identifier |
| `lang_code` | VARCHAR(10) NOT NULL, UNIQUE | ISO language code, for example `en` |
| `lang_name` | VARCHAR(50) NOT NULL | English name of the language |
| `lang_native` | VARCHAR(50) NOT NULL | Native name of the language |
| `lang_locale` | VARCHAR(10) DEFAULT NULL | Locale string, for example `en_US` |
| `lang_direction` | ENUM('ltr','rtl') NOT NULL DEFAULT 'ltr' | Text direction |
| `lang_sort` | INT NOT NULL DEFAULT 0 | Display order |
| `lang_is_default` | TINYINT(1) NOT NULL DEFAULT 0 | 1 marks the default language |
| `lang_is_active` | TINYINT(1) NOT NULL DEFAULT 1 | 1 means the language is available |
| `lang_created_at` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | Creation time |

Indexes: PRIMARY KEY (`ID`), UNIQUE (`lang_code`).

### tbl_translations

UI translation strings. Each row is one translated key for one language.

| Column | Attributes | Description |
|--------|------------|-------------|
| `ID` | BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY | Translation identifier |
| `lang_id` | INT(11) UNSIGNED NOT NULL | References `tbl_languages.ID` |
| `translation_key` | VARCHAR(255) NOT NULL | Translation key, for example `nav.dashboard` |
| `translation_value` | TEXT NOT NULL | Translated text |
| `translation_context` | VARCHAR(100) DEFAULT NULL | Optional context for the same key |
| `translation_plurals` | VARCHAR(255) DEFAULT NULL | Plural forms, when needed |
| `is_html` | TINYINT(1) NOT NULL DEFAULT 0 | 1 means the value contains HTML |
| `created_at` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP | Last update |

Indexes: PRIMARY KEY (`ID`), UNIQUE `lang_key` (`lang_id`, `translation_key`), KEY `lang_id` (`lang_id`), KEY `translation_key` (`translation_key`(191)).

The unique key on (`lang_id`, `translation_key`) prevents the same key being defined twice for one language.

---

## DAO coverage

Not every table has its own DAO class. These 19 DAOs live in `lib/dao/`:

| DAO | Tables it works with |
|-----|---------------------|
| `UserDao` | `tbl_users` |
| `UserTokenDao` | `tbl_user_token` |
| `PostDao` | `tbl_posts` |
| `PageDao` | `tbl_posts` (type `page`) |
| `TopicDao` | `tbl_topics` |
| `PostTopicDao` | `tbl_post_topic` |
| `CommentDao` | `tbl_comments` |
| `ReplyDao` | `tbl_comments` (nested replies) |
| `MediaDao` | `tbl_media` |
| `MenuDao` | `tbl_menu` |
| `PluginDao` | `tbl_plugin` |
| `ConfigurationDao` | `tbl_settings` |
| `ThemeDao` | `tbl_themes` |
| `ConsentDao` | `tbl_consents` |
| `DataRequestDao` | `tbl_data_requests` |
| `PrivacyLogDao` | `tbl_privacy_logs` |
| `PrivacyPolicyDao` | `tbl_privacy_policies` |
| `LanguageDao` | `tbl_languages` |
| `TranslationDao` | `tbl_translations` |

The remaining tables are accessed through services or utility functions rather than a dedicated DAO:

- `tbl_login_attempt` is managed by the authentication layer.
- `tbl_api_keys` is managed by `ApiAuth`.
- `tbl_mediameta` is handled by the media service.
- `tbl_media_download` and `tbl_download_log` are handled by the download service.

---

## Test database note

The development test database is `blogware_test`. It uses unprefixed table names and connects with the same credentials as the production database (`blogwareuser` / `userblogware`).

The tables for integration tests are created by `tests/setup_test_db.php`:

```bash
php tests/setup_test_db.php
```

Important: that script carries its own inline `CREATE TABLE` statements. It does not read `install/include/dbtable.php`, and it currently creates only a subset of the tables (users, posts, topics, post-topic, comments, media, mediameta, settings, menu, plugin, themes, languages, translations, privacy policies). It also seeds a test admin user (`admin` / `admin123`), six languages, and sample posts, topics, and menu items.

Newer tables such as `tbl_api_keys`, `tbl_media_download`, `tbl_download_log`, `tbl_consents`, `tbl_data_requests`, and `tbl_privacy_logs` are not defined there. If a test needs one of them, add its `CREATE TABLE` statement to `tests/setup_test_db.php` from the definitions in `install/include/dbtable.php`.

If schema-related tests fail, recreate the test database from scratch:

```bash
php tests/setup_test_db.php
```

---

## Changing the schema

1. Edit the `CREATE TABLE` statements in `install/include/dbtable.php`.
2. For new columns on existing tables, add an `ALTER TABLE` statement in the same file so existing installations are migrated. The locale columns on `tbl_posts`, `tbl_topics`, and `tbl_menu` are added this way.
3. If the change needs a real relationship, add a `FOREIGN KEY` constraint, as `tbl_api_keys` does.
4. Recreate the test database and run the test suite:
   ```bash
   php tests/setup_test_db.php
   lib/vendor/bin/phpunit
   ```
5. Ship the change as part of the release. Never edit a live database directly.
