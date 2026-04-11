# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.2] - 2026-04-10

### Changed
- Updated utility loader generator to prevent function redeclaration errors
- Bootstrap reliability improvements

### Fixed
- **Bootstrap**: Database connection now fails gracefully when credentials are missing or invalid
- **Bootstrap**: DAOs and services now only instantiate when a valid database connection exists
- **Bootstrap**: Added null coalescing operators for array keys that may not exist
- **Bootstrap**: Added guards to prevent session operations in CLI/header-sent scenarios
- **Bootstrap**: Made applySecurity() resilient to utility function errors
- **Utility Loader**: Fixed duplicate function declaration error for load_core_utilities()

### Added
- Comprehensive unit tests for Bootstrap class (43 tests, 89 assertions)
- Updated test bootstrap to properly load critical utility functions

### Details
This patch release addresses several reliability and robustness issues in the Bootstrap process and utility loader.

### Codename
**Maleo Senkawor** - Honoring *Macrocephalon maleo*, the critically endangered megapode endemic to Sulawesi, Indonesia. This large bird, known for its distinctive bony casque and remarkable incubation strategy using geothermal and solar heat, faces severe threats from habitat loss and egg poaching. With only an estimated 4,000–7,000 breeding pairs remaining in the wild, the maleo is listed as Critically Endangered on the IUCN Red List and protected under CITES Appendix I. Conservation efforts by organizations like the Wildlife Conservation Society and the Alliance for Tompotika Conservation are vital to safeguarding this unique species and its habitat.

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

### Details
This patch addresses security vulnerabilities detected by Dependabot and removes unnecessary files to improve codebase quality. It also includes documentation updates and adds standard visual identity assets.

### Comparison
- **Previous release**: v1.0.0
- **Changes since v1.0.0**: 4 commits

---

## [1.0.0] - Initial Release

### Added
- Initial stable release of Scriptlog
- Complete PHP library with modern architecture
- Support for MVC, database, validation, encryption, and more