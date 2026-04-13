# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## Quick Links

- [Latest Release](#103---2026-04-13)
- [All Releases](#releases)

---

## Releases

## [1.0.3] - 2026-04-13

### Added
- 40 unit tests for UtilityLoader class
- OpenAPI specification files for Blogware RESTful API

### Changed
- Enhanced i18N implementation for admin panel
- Updated documentation

### Fixed
- CSP blocking resources
- Privacy policy page link
- Admin sidebar menu language changes
- Language switcher functionality
- i18N feature bug
- Invalid ciphertext HMAC verification
- Undefined `load_core_utilitier` error
- Undefined `get_table_prefix` error

### Removed
- `.lts` directory containing sensitive keys
- PHPUnit result cache from git tracking

### Notes
Focuses on i18N improvements, security fixes, and bug corrections.

### Codename
**Maleo Senkawor** – Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia.

### Comparison
- **Previous release**: v1.0.2
- **Changes since v1.0.2**: 22 commits

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
| 1.0.3 | 2026-04-13 | Stable |
| 1.0.2 | 2026-04-10 | Stable |
| 1.0.1 | 2026-04-09 | Stable |
| 1.0.0 | 2026-04-09 | Initial Release |
