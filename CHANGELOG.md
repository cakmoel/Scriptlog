# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## Quick Links

- [Latest Release](#105---2026-04-28)
- [All Releases](#releases)

---

## Releases

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
| 1.0.5 | 2026-04-28 | Stable |
| 1.0.4 | 2026-04-22 | Stable |
| 1.0.3 | 2026-04-13 | Stable |
| 1.0.2 | 2026-04-10 | Stable |
| 1.0.1 | 2026-04-09 | Stable |
| 1.0.0 | 2026-04-09 | Initial Release |
