# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## Quick Links

- [Latest Release](#140---2026-07-06)
- [All Releases](#releases)

---

## Releases

## [1.4.0] - 2026-07-06

### Added
- **`ThemeRendererInterface`**: New interface for theme rendering with constructor dependency injection
- **`ThemeResolutionException`**: New exception class with factory methods for common failure modes
- **`IServiceThrowable` interface**: Replaces deprecated `IEventThrowable`; extends `IThrowable` for consistent service-layer exception handling
- **Unit tests**: 3 new test files (`IServiceThrowableTest`, `ThemeRendererInterfaceTest`, `ThemeResolutionExceptionTest`) — 4 tests

### Changed
- **ThemeRenderer**: Refactored to implement `ThemeRendererInterface` with constructor injection, documented public API, and improved theme directory resolution
- **Core library classes**: Refactored Bootstrap, Dispatcher, HandleRequest, ApiRouter, Authentication, CSRFGuard, and other core classes with improved type safety, modular private methods, and code quality
- **All controllers**: Refactored PostController, UserController, MediaController, MenuController, PageController, PluginController, ThemeController, ReplyController, DownloadAdminController, and all API controllers with improved type safety
- **DAO layer**: Updated PostDao, PageDao, PluginDao, UserTokenDao, TopicDao, ThemeDao, MenuDao, PostTopicDao with improved type safety
- **Service layer**: Updated PostService, PageService, UserService, MigrationService, MediaService, PluginService, TranslationService, and other services
- **Theme templates**: Updated header, footer, download, and functions templates with deferred CSS loading for performance
- **Admin UI templates**: Updated all admin templates with security improvements
- **Installation wizard**: Updated `setup-db.php` and `finish.php` with improved setup logic
- **Autoloader**: Updated with new class mappings for new interfaces and classes
- **Utility files**: Updated with improved security and type safety
- **Dependencies**: Updated `composer.json` (added explicit PHP extension requirements: `ext-json`, `ext-mbstring`, `ext-pdo`, `ext-fileinfo`, `ext-openssl`, `ext-gd`; removed `guzzlehttp/psr7`); updated `composer.lock`

### Fixed
- **Test fixes**: Fixed failing tests for Bootstrap property rename, NumberCpus code changes, PerformanceOptimization CSS deferral, and ThemeRenderer refactor

### Removed
- **`IEventThrowable`**: Deprecated interface removed, replaced by `IServiceThrowable`
- **Avatar images**: Removed unused avatar images from admin assets (`avatar.png`, `avatar04.png`, `avatar2.png`, `avatar3.png`, `avatar5.png`, `boxed-bg.png`, `default-150x150.png`, `default-50x50.gif`, `icons.png`)
- **`$config` global**: Removed unused `$config = array()` from `common.php`
- **`start-session-on-site.php`**: Removed unused utility

### Style
- **Code formatting**: Array bracket alignment fix in `utility-loader.php`

### Docs
- **`src/readme.html`**: Synced with README.md (reduced from 285 lines)

### Security
- **Utility files**: Updated with improved security and type safety across utility functions

### Tests
- **New test suites**: 3 new test files for `IServiceThrowable`, `ThemeRendererInterface`, `ThemeResolutionException`
- **Test fixes**: Updated BootstrapTest, NumberCpusTest, PerformanceOptimizationTest, ThemeRendererTest for compatibility with refactored code

### Notes
Maintenance release focused on code quality improvements across the entire codebase — refactoring core library classes with new interfaces, improved type safety, deferred CSS loading for performance, updated dependencies, and expanded test coverage. All unit tests passing.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.3.1
- **Changes since v1.3.1**: 12 commits

---

## [1.3.1] - 2026-07-02

### Fixed
- **`I18nManager` magic methods**: Added `__unserialize()` method to `I18nManager` singleton to align with PHP 8.5 deprecation of `__wakeup` and prevent serialization bypasses.

### Notes
Patch release addressing PHP 8.5 magic method deprecation and hardening singleton serialization.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.3.0
- **Changes since v1.3.0**: 8 commits

---

## [1.3.0] - 2026-07-01

### Added
- **`referrer_policy()` function**: New security header function for Referrer-Policy configuration
- **`permissions_policy()` function**: New security header function for Permissions-Policy configuration
- **Security headers to API entry point**: X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, and Permissions-Policy headers integrated into `api/index.php`
- **Bootstrap security header integration**: `referrer_policy` and `permissions_policy` wired into `Bootstrap::applySecurity()`
- **CSRF protection for API write endpoints**: `validateCsrfForWrite()` and `generateCsrfToken()` methods in `ApiAuth`
- **CSRF validation in API controllers**: Integrated CSRF validation into MediaApiController and other API controllers
- **`create_encoded_key()` hardening**: Defuse cipher key derivation with `_try_decrypt()` helper for multi-path backward-compatible decryption
- **Controller test suite**: 5 new test files (PostController, UserController, CommentController, MediaController, TopicController) — 34 tests
- **Core class test suite**: 6 new test files (SessionMaker, Paginator, Sanitize, DbFactory, Dispatcher, View) — 65 tests
- **phpunit.xml suite directories**: Added Controller Tests and Core Tests directories to the PHPUnit configuration

### Changed
- **Password hashing**: Upgraded from md5 to sha256 in `protect_post()` and `setPassPhrase()` for stronger hashing
- **CSP hardening**: Removed `unsafe-eval` from Content-Security-Policy; added Report-Only header for migration monitoring
- **Dynamic tests badge**: Replaced static badge with dynamic GitHub Actions badge in README

### Fixed
- **Test infrastructure**: `Registry::set('dbc')` added for Dao, MediaDao, Authentication constructors in integration tests
- **Locale column length**: Shortened `lang_code` from VARCHAR(20) to VARCHAR(10) to match schema
- **Unique locale collision**: Fixed via counter in test data setup
- **`is_html` cast**: Cast to integer 0 instead of boolean false in translation tests
- **DownloadUtilityTest race condition**: Resolved `time() + 1` race condition in data provider
- **DownloadCreateLinkTest paths**: Corrected file paths for `src/` directory structure
- **Theme auto-activate test**: Deactivate all themes first to ensure clean state
- **Removed stale tests**: Removed tests for non-existent `setCredential()` method and `crendential` property
- **Translation insert transaction**: Wrapped translation insert in transaction in setup-db; added hard lock detection for fully installed databases
- **`generate_request()` null safety**: Removed redundant `isset($data) &&` before `array_key_exists()` calls

### Style
- **Code formatting**: Cleanup across service layer, utility loader, core classes, admin utilities, import files, and PostController
- **Utility loader**: Fixed blank line after opening PHP tag and array bracket alignment

### Docs
- **README**: Updated Tests badge to reflect 1240 passed; documentation updates
- **DEVELOPER_GUIDE.md**: Updated version to v1.2.3; documentation updates
- **TESTING_GUIDE.md**: Updated to v1.2.0 with 1240 tests, 2584 assertions, database setup fixes

### Tests
- **Security headers**: Tests for `referrer_policy()`, `permissions_policy()`, secure HTTP headers (CSP, Report-Only)
- **API auth CSRF**: Unit tests for `validateCsrfForWrite()`, `hasApiOrBearerAuth()`, `generateCsrfToken()` — additional test cases
- **Encrypt-decrypt**: Tests for `create_encoded_key()`, `_try_decrypt()`, multi-path backward-compatible decryption
- **Password hashing**: Verification that `PostService::setPassPhrase()` uses sha256 instead of md5
- **Controller tests**: 8 PostController, 8 UserController, 6 CommentController, 6 MediaController, 6 TopicController tests
- **Core class tests**: 8 SessionMaker, 8 Paginator, 15 Sanitize, 4 DbFactory, 6 Dispatcher, 6 View tests
- **HTTP headers test**: Rewritten using subprocess instead of `runInSeparateProcess`

### Security
- **Password hashing**: Upgraded from md5 to sha256 for protected post passwords
- **CSP hardening**: Removed `unsafe-eval` from Content-Security-Policy
- **Security headers**: X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy added to API
- **API CSRF protection**: CSRF validation for API write endpoints

### Notes
Feature release with security hardening (CSRF API protection, CSP hardening, password hashing upgrade, security headers), expanded test suite (+99 tests across controllers and core classes), and code quality improvements. All unit tests passing (950 tests, 2031 assertions).

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.2.3
- **Changes since v1.2.3**: 50 commits

---

## [1.2.3] - 2026-06-26

### Added
- **`check_session_dir()` function**: New installer requirements check for sessions directory writability
- **Sessions directory check**: Added to installer requirements UI for user visibility during setup
- **`.gitkeep` and `index.html`**: Protect `public/files/cache/sessions/` directory from directory listing

### Changed
- **`resolve_session_save_path()`**: Added chmod fallback logic to attempt permission recovery when sessions directory exists but is not writable, with fallback to `sys_get_temp_dir()`

### Fixed
- **Session initialization**: Used `resolve_session_save_path()` in both Bootstrap and API session init to ensure consistent session storage location
- **PHP 7.4 backwards compatibility**: Removed mixed return type declarations that caused fatal errors on PHP 7.4
- **`number_cpus()` mac branch**: Removed redundant `is_resource` guard and replaced `false` comparison with `is_resource` for `popen()` in all branches

### Security
- **Sessions directory protection**: Added `index.html` to prevent directory listing of session files

### Tests
- **Bootstrap tests**: Added tests for `\Throwable` catch and `instanceof PDO` guard in theme renderer
- **`number_cpus()` tests**: Comprehensive unit tests covering Linux, Windows, and Mac branches
- **`generate_request` and `sanitize_urls` tests**: Full coverage for request generation and URL sanitization
- **`db-mysqli` function tests**: Unit tests with `is_table_exists` guard coverage

### Removed
- **Stale translation caches**: Cleared stale `en.json` and `es.json` for clean regeneration

### Notes
Maintenance release focused on session path reliability, PHP 7.4 compatibility, expanded test coverage, and installer hardening. All unit tests passing (829 tests, 1854 assertions).

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.2.2
- **Changes since v1.2.2**: 24 commits

---

## [1.2.2] - 2026-06-25

### Added
- **Local placeholder SVG image**: Replaced external placeholder URLs with a local `placeholder.svg` asset across all blog templates (archive, category, tag, blog, single, page, home)
- **THEME_DEVELOPER_GUIDE.md**: Comprehensive standalone theme reference documentation
- **Language indicator widget**: i18n sidebar locale bootstrap with ARIA attributes for accessibility
- **Extended locale support**: `lang_available` now includes Arabic, Chinese, French, Russian, Spanish, and Indonesian during installation
- **SidebarNavigationTest**: Unit tests for admin sidebar navigation
- **MIT license file**: Added `LICENSE` file to the blog theme

### Changed
- **Utility loader ordering**: `sanitize-urls.php` now loads before `generate-request.php` project-wide to resolve dependency ordering across multiple load paths
- **Translation caches**: English, Spanish, and Indonesian translation caches restructured, expanded, and streamlined with full key-value pairs
- **RELEASE_GUIDE.md**: Overhauled with Packagist version immutability guidance
- **Theme cookie expiry**: Reduced from 365 to 30 days

### Fixed
- **ThemeRenderer DB guard**: Multiple hardening passes — replaced empty/null checks with `instanceof \PDO` type check, caught `\Throwable` instead of `Exception`, removed redundant guard conditions
- **`is_table_exists` redeclaration**: Wrapped in `function_exists()` guard to prevent fatal errors when the function is loaded multiple times
- **`generate-request.php` require ordering**: Moved `require_once` before function declaration to comply with PHP best practices
- **Session initialization**: Reordered session start before authenticator include to prevent `headers_sent` errors
- **Front controller**: Added `ob_start()` for proper output buffering
- **Theme fallback**: Added directory fallback when `header.php` not found in active theme
- **Comment endpoint**: Fixed `fetch-comments.php` to use dynamic `site_url`
- **Copyright year**: Corrected start year from 2021 to 2018
- **Void return types**: Removed incorrect `void` return type from `install_i18n_data` and `convert_memory_used`
- **Error suppression**: Suppressed `file_put_contents`/`file_get_contents` warnings with `@` operator
- **ScriptlogCryptonizeException**: Skip error logging for expected encryption exceptions in decrypt method
- **Dependabot config**: Corrected target-branch and directory; updated CodeQL action versions
- **Autoloader path**: Fixed composer autoload and PHPUnit paths for `lib/vendor` structure
- **Bootstrap error handling**: Improved exception handling and SQL query logging in core Bootstrap
- **Install system**: Improved error handling, DB connection validation, and security hardening in installation wizard

### Security
- **SRI integrity hashes**: Added Subresource Integrity hashes to all CSS and JavaScript assets; removed legacy IE tweaks
- **Frontend JS hardening**: Validated cookie values and anchor targets in `front.js`
- **XSS prevention**: Sanitized Summernote databasic and specialchars plugins
- **Download identifier sanitization**: Sanitized download identifiers with `preg_replace` to prevent injection attacks
- **Prepared statements**: Migrated previous/next post queries to prepared statements; escaped permalink in `format_topics`
- **CVE-2026-55766**: Updated `guzzlehttp/psr7` to 2.12.1 to fix CRLF injection vulnerability
- **Colour select sanitization**: Sanitized colour select input in admin `front.js`
- **CodeQL scanning**: Added PHP to CodeQL analysis targets

### Removed
- **`restoblog` and `tastybites` themes**: Removed from repository (not yet production-ready)
- **Redundant `echo`**: Removed extraneous `echo` before `sidebar_navigation()` call
- **Stale translation caches**: Cleared stale `en.json` and `es.json` caches for clean regeneration

### Refactored
- **External dependency removal**: Replaced all external placeholder image URLs with local `placeholder.svg` across archive, category, tag, blog, single, page, and home templates
- **AJAX error handling**: Simplified error handling in comment submission and search functionality

### Style
- **Install wizard responsiveness**: Added mobile breakpoint styles to install wizard CSS

### Notes
Security-focused release with 10+ vulnerability fixes, comprehensive utility loader stabilization, i18n expansion, and removal of external image dependencies. All unit tests passing (764 tests, 1753 assertions).

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.2.1
- **Changes since v1.2.1**: 79 commits

---

## [1.2.1] - 2026-06-16

### Added
- **CSS design tokens**: Custom properties for colors, spacing, and typography in the blog theme
- **Dark mode support**: Full theme support via `prefers-color-scheme: dark` media query
- **Responsive improvements**: Enhanced layout adaptability for mobile, tablet, and desktop viewports

### Changed
- Blog theme templates: replaced `<main>` with `<div>` for semantic consistency across 8 templates (archive, archives, blog, category, page, single, tag, homepage)
- Improved header navigation with better layout and accessibility
- Enhanced footer template layout
- Updated homepage template
- Minified `style.sea.css` asset
- Updated test bootstrap and file paths to match project structure

### Fixed
- PHPUnit binary path corrected from `vendor/bin/phpunit` to `lib/vendor/bin/phpunit` in CI workflow
- Added `workflow_dispatch` trigger to CI workflow for manual test runs
- Fixed stale tastybites theme test files (theme was previously removed)
- Corrected test file paths from `../../lib/` to `../../src/lib/` project-wide

### Removed
- Stale test files for deleted `tastybites` theme (4 files)

### Notes
Minor release focused on theme modernization with CSS design tokens, dark mode support, responsive improvements, and CI/test infrastructure cleanup. This version re-releases the same content as v1.2.0 under a new tag to avoid the upstream tag mutation lock on Packagist.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.1.0
- **Changes since v1.1.0**: 18 commits

---

## [1.1.0] - 2026-06-13

### Added
- **Handler system**: New front-end request handling pipeline with dedicated handlers for each content type (`ArchiveHandler`, `BlogHandler`, `CategoryHandler`, `DownloadHandler`, `FrontRequestHandler`, `HomeHandler`, `PageHandler`, `PostHandler`, `PrivacyHandler`, `TagHandler`) and a central `HandlerRegistry`
- **FrontService**: New service layer for front-end content retrieval and rendering
- **ThemeRenderer**: New core class for theme rendering
- **CSRF/XSS protection**: Comprehensive protection added across all admin UI templates (comments, downloads, export, import, media library, pages, plugins, posts, privacy, settings, users)
- **Blog theme improvements**: Enhanced header navigation, footer layout, single post view, download handling, sidebar, and 404 page
- **Bootstrap hardening**: Improved error handling, null safety, and graceful failure when database credentials are missing
- **Session management**: Enhanced `SessionMaker` with improved session handling and security
- **Dispatcher refactoring**: URL routing and content validation improvements with canonical URL enforcement
- **FrontHelper enhancements**: Improved front-end content retrieval methods
- **HandleRequest upgrades**: Comprehensive query string handling and 404 management
- **Test suite expansion**: 25+ new test files covering handlers, services, download features, integration tests, and smoke tests
- **Test infrastructure**: New `tests/core/`, `tests/smoke/`, and `tests/unit/handlers/` test directories
- **Psalm static analysis**: Configuration files (`psalm.xml`, `psalm-baseline.xml`, `psalm-autoload.php`) for improved code quality enforcement

### Changed
- Updated `composer.json` and `composer.lock` dependencies
- Revamped blog theme files (`404.php`, `footer.php`, `functions.php`, `header.php`, `home.php`, `sidebar.php`, `download_file.php`, `load-comment.min.js`)
- Updated all admin UI templates with modernized markup and security hardening
- Updated all controllers, DAOs, services, and utility functions for PHP 8.x compatibility
- Updated `Bootstrap.php`, `Dispatcher.php`, `HandleRequest.php`, `FrontHelper.php`, `SessionMaker.php` with significant improvements
- Enhanced `DbMySQLi.php` for PDO/mysqli compatibility
- Updated documentation files

### Fixed
- Resolved PHP 8.x compatibility issues across the codebase
- Fixed null safety in utility functions and controllers
- Fixed PDO/mysqli compatibility in database access layer
- Corrected argument order in configuration write function
- Fixed table prefix handling in Medoo integration
- Resolved various edge cases in post editing and protected post handling
- Fixed `total_comment()` null safety in `single.php` to prevent array access on falsy return
- Removed empty `load_more_comments()` function stub from blog theme `functions.php`
- **Test infrastructure**: Corrected 30+ test file paths from `../../lib/` to `../../src/lib/` to match project structure
- **CI workflow**: Fixed `phpunit` binary path from `vendor/bin/phpunit` to `lib/vendor/bin/phpunit` and added `workflow_dispatch` trigger
- Removed stale tastybites theme test files (theme was previously removed)
- Fixed `InstallationTest.php`, `DownloadHandlerTest.php`, `ConfigFileGenerationTest.php`, `OpenApiSpecVerificationTest.php` test logic
- Updated `.gitignore` to exclude `lib/vendor/` directory

### Removed
- Unused CSS from blog theme (`custom.css` trimmed by 269 lines)
- Deprecated code and unused utility files
- Stale test files for deleted `tastybites` theme (4 files)

### Notes
Minor release introducing a new handler-based front-end architecture, comprehensive CSRF/XSS protection, significant test suite expansion, theme improvements, test infrastructure fixes, and numerous stability improvements across the entire codebase.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia. This remarkable bird, known for its distinctive bony casque and unique reproductive strategy, is one of the world's most fascinating creatures. Maleos are monogamous pairs that dig deep pits in which a single egg is laid—incubated by geothermal heat at inland forested sites or by the sun at beach nesting grounds. The chicks hatch fully feathered and immediately fly into the forest, independent from birth. With population declined by over 90% since the 1950s and fewer than 10,000 individuals remaining, the maleo is listed as Critically Endangered on the IUCN Red List and protected under CITES Appendix I. Major threats include over-harvesting of eggs, habitat destruction, and predation by introduced species. Conservation efforts by the Wildlife Conservation Society (WCS) Indonesia and the Alliance for Tompotika Conservation have released over 10,000 chicks into the wild since 2001, working to protect nesting grounds and establish semi-natural hatcheries.

### Comparison
- **Previous release**: v1.0.8
- **Changes since v1.0.8**: 300 commits

---

## [1.0.8] - 2026-05-14

### Fixed
- Fixed terms-of-use link path in signup form (`src/admin/signup.php`)
- Changed recommended permissions from `777` to `755` for `public/cache/` and `public/log/` directories (`README.md`, `src/docs/DEVELOPER_GUIDE.md`)

### Notes
Patch release fixing the terms-of-use link URL and hardening directory permissions.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.0.7
- **Changes since v1.0.7**: 4 commits

---

## [1.0.7] - 2026-05-02

### Changed
- Revamped `src/public/themes/blog/single.php`
- Revamped `src/public/themes/blog/footer.php`
- Revamped `src/public/themes/blog/assets/js/load-comment.js` and minified version
- Updated `src/docs/DEVELOPER_GUIDE.md`

### Fixed
- Fixed `src/lib/model/TopicModel.php` to handle updated categories in both protected and public posts
- Fixed `src/lib/dao/PostDao.php` to accommodate unchanged password in a protected post when edited
- Fixed bug in update method when a protected post was edited without changing the password

### Added
- Updated PHP docs in `src/lib/utility/generate-filename.php`

### Removed
- Removed unused `src/api/media-upload.php`

### Notes
Patch release addressing blog theme improvements, post editing bug fixes, and documentation updates.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.0.6
- **Changes since v1.0.6**: 10 commits

---
## [1.0.6] - 2026-05-01

### Changed
- Revamped blog theme files (`functions.php`, `download.php`, `download_file.php`)
- Revamped `src/lib/utility/import-wordpress.php`
- Revamped `src/lib/utility/download-settings.php`
- Revamped `src/lib/utility/admin-tag-title.php`
- Updated blog theme CSS assets (`custom.css`, `custom.min.css`)
- Updated `src/lib/utility-loader.php`

### Fixed
- Updated Laminas Crypt API usage in ScriptlogCryptonize (BlockCipher factory pattern)
- Added missing `$strong` variable in random_bytes fallback
- Resolved PHP 8.x compatibility issues in import-wordpress and app-info
- Cleaned up deprecated `libxml_disable_entity_loader` references
- Cleaned up `.gitignore` by removing `.plan` entry

### Notes
Maintenance release focusing on PHP 8.x compatibility fixes, code cleanup, and blog theme improvements.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.0.5
- **Changes since v1.0.5**: 21 commits

---

## [1.0.5] - 2026-04-28

### Added
- Menu and template management features with HATEOAS API support

### Changed
- Revamped `src/lib/utility/upload-theme.php`
- Revamped `src/lib/utility/permalinks.php`
- Updated libxml entity loading for PHP 8.1+ compatibility
- Added tmp/minify.php configuration
- Updated `tests/unit/ThemeUploadTest.php`

### Fixed
- Corrected test paths from lib/utility to src/lib/utility
- Set APP_URL environment variable for CI tests
- Create config.php for CI before running tests
- Suppress deprecated libxml warnings in PHP 8.1+
- Resolved deprecated libxml_disable_entity_loader issue

### Removed
- Unused `comment.php` from blog theme
- Unused pictures from codebase

### Notes
Patch release addressing PHP 8.1+ compatibility, CI workflow improvements, and new menu/template management features.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.0.4
- **Changes since v1.0.4**: 13 commits

---

## [1.0.4] - 2026-04-22

### Added
- OpenAPI specification verification tests (34 new tests)
- LanguageSwitcherTest for i18n functionality
- NavigationI18nTest for permalink URL generation

### Changed
- Extended API HATEOAS with new endpoints (GDPR, languages, translations, media)
- Updated OpenAPI specs (src/docs/)

### Fixed
- 530 unit tests now passing
- Test path corrections (lib -> src/lib structure)
- ApiHateoas config path to src/config.php
- ImageDisplayTest utility-loader path
- TranslationLoaderTest cache expiry flaky test

### Removed
- storage/keys from repo tracking
- tests/COVERAGE.md artifact

### Notes
Hotfix release focusing on test infrastructure improvements and path corrections.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.0.3
- **Changes since v1.0.3**: 23 commits

---

## [1.0.3] - 2026-04-13

### Added
- 40 unit tests for UtilityLoader class
- OpenAPI specification files for Blogware RESTful API
- Enhanced i18n implementation for admin panel

### Changed
- Enhanced i18N implementation for admin panel
- Updated documentation

### Fixed
- **Security**: Fixed CSP blocking legitimate resources
- **Encryption**: Fixed "Invalid ciphertext: HMAC verification failed" error
- **i18n**: Fixed language switcher not working
- **i18n**: Fixed sidebar menu not reflecting language changes
- **Admin**: Fixed link to privacy-policy page
- **Bug**: Fixed i18N feature bug
- **Bug**: Fixed undefined `load_core_utilities` error
- **Bug**: Fixed undefined `get_table_prefix` error

### Removed
- `.lts` directory containing sensitive keys
- PHPUnit result cache from git tracking

### Details
This patch release addresses bug fixes and reliability improvements across the framework.

### Codename
**Maleo Senkawor** - Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi and Buton Island, Indonesia. This remarkable bird, known for its distinctive bony casque and unique reproductive strategy, is one of the world's most fascinating creatures. Maleos are monogamous pairs that dig deep pits in which a single egg is laid—incubated by geothermal heat at inland forested sites or by the sun at beach nesting grounds. The chicks hatch fully feathered and immediately fly into the forest, independent from birth. With population declined by over 90% since the 1950s and fewer than 10,000 individuals remaining, the maleo is listed as Critically Endangered on the IUCN Red List and protected under CITES Appendix I. Major threats include over-harvesting of eggs, habitat destruction, and predation by introduced species. Conservation efforts by the Wildlife Conservation Society (WCS) Indonesia and the Alliance for Tompotika Conservation have released over 10,000 chicks into the wild since 2001, working to protect nesting grounds and establish semi-natural hatcheries.

### Comparison
- **Previous release**: v1.0.2
- **Changes since v1.0.2**: 21 commits

---

## [1.0.2] - 2026-04-10

### Changed
- Updated utility loader generator to prevent function redeclaration errors
- Improved Bootstrap reliability

### Fixed
- **Bootstrap**: Database connection fails gracefully when credentials are missing or invalid
- **Bootstrap**: DAOs and services only instantiate when a valid database connection exists
- **Bootstrap**: Added null coalescing operators for array keys that may not exist
- **Bootstrap**: Added guards to prevent session operations in CLI/header-sent scenarios
- **Bootstrap**: Made `applySecurity()` resilient to utility function errors
- **Utility Loader**: Fixed duplicate function declaration error for `load_core_utilities()`

### Added
- 43 unit tests for Bootstrap class (89 assertions)
- Updated test bootstrap to properly load critical utility functions

### Notes
This patch release addresses several reliability and robustness issues in the Bootstrap process and utility loader.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.0.1
- **Changes since v1.0.1**: 13 commits

---

## [1.0.1] - 2026-04-09

### Added
- Standard visual identity (scriptlog mascot assets)

### Changed
- Updated DEVELOPER GUIDE documentation
- Updated general documentation

### Fixed
- **Security**: Resolved all Dependabot-detected vulnerabilities
- **Quality**: Removed unnecessary files from the codebase

### Notes
This patch addresses security vulnerabilities detected by Dependabot and removes unnecessary files to improve codebase quality. It also includes documentation updates and adds standard visual identity assets.

### Comparison
- **Previous release**: v1.0.0
- **Changes since v1.0.0**: 4 commits

---

## [1.0.0] - 2026-04-09

### Added
- Initial stable release of Scriptlog
- Complete PHP library with modern architecture
- Support for MVC, database, validation, encryption, and more

---

## Version History

| Version | Date | Status |
|---------|------|--------|
| 1.4.0 | 2026-07-06 | Stable |
| 1.3.1 | 2026-07-02 | Stable |
| 1.3.0 | 2026-07-01 | Stable |
| 1.2.3 | 2026-06-26 | Stable |
| 1.2.2 | 2026-06-25 | Stable |
| 1.2.1 | 2026-06-16 | Stable |
| 1.1.0 | 2026-06-13 | Stable |
| 1.0.8 | 2026-05-14 | Stable |
| 1.0.7 | 2026-05-02 | Stable |
| 1.0.6 | 2026-05-01 | Stable |
| 1.0.5 | 2026-04-28 | Stable |
| 1.0.4 | 2026-04-22 | Stable |
| 1.0.3 | 2026-04-13 | Stable |
| 1.0.2 | 2026-04-10 | Stable |
| 1.0.1 | 2026-04-09 | Stable |
| 1.0.0 | 2026-04-09 | Initial Release |
