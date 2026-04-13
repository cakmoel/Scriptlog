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

## [1.0.3] - 2026-04-13

### Fixed
- **Security**: Fixed CSP blocking legitimate resources
- **Encryption**: Fixed "Invalid ciphertext: HMAC verification failed" error
- **i18n**: Fixed language switcher not working
- **i18n**: Fixed sidebar menu not reflecting language changes
- **Admin**: Fixed link to privacy-policy page

### Added
- Enhanced i18n implementation for admin panel

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
| 1.0.3 | 2026-04-13 | Stable |
| 1.0.2 | 2026-04-10 | Stable |
| 1.0.1 | 2026-04-09 | Stable |
| 1.0.0 | 2026-04-09 | Initial Release |
