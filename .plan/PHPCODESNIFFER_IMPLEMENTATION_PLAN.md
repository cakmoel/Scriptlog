# PHP_CodeSniffer PSR12 Implementation Plan

## Executive Summary

This plan outlines the integration of PHP_CodeSniffer to ensure the codebase conforms to PSR12 coding standard. The project contains **3,028 PHP files**.

---

## PHP Version Compatibility

| Component | Version | Min PHP | Project Compatible? |
|-----------|---------|---------|---------------------|
| squizlabs/php_codesniffer | ^4.0 | 7.2 | ✅ Yes (7.4.33) |
| friendsofphp/php-cs-fixer | ^3.57 | 7.4 | ✅ Yes (7.4.33) |
| PSR12 Coding Standard | - | 7.0+ | ✅ Yes |

> **Note**: PSR12 is purely a code style standard (braces, spacing, indentation) - no PHP runtime features involved.

---

## Current Codebase Analysis

| Directory | PHP Files | Priority |
|-----------|-----------|----------|
| lib/ | 2,792 | High (core classes) |
| admin/ | 95 | High |
| public/themes/ | 22 | Medium |
| install/ | 11 | Low |
| api/ | 1 | Low |

### Identified PSR12 Violations (Sample)

1. **Class braces**: Opening brace on same line (should be new line)
2. **Method braces**: Opening brace on same line (should be new line)
3. **Indentation**: Inconsistent (mix of 2, 3, 4 spaces)
4. **Spacing**: Inconsistent around operators and parentheses
5. **Line length**: Some lines exceed 120 characters

---

## Implementation Strategy

### Phase 1: Installation & Configuration

1. Add to Composer:
   ```bash
   composer require --dev squizlabs/php_codesniffer:^4.0
   ```

2. Create `phpcs.xml` configuration:
   ```xml
   <?xml version="1.0"?>
   <ruleset name="BlogwarePSR12">
       <description>Blogware PSR12 Coding Standard</description>
       
       <!-- Base on PSR12 -->
       <rule ref="PSR12">
           <!-- Exclude vendor directory -->
           <exclude name="PSR12.Files.FileHeader.SpacingAfterBlock" />
       </rule>
       
       <!-- Custom exclusions for legacy code -->
       <rule ref="PSR12.Classes.ClassDeclaration">
           <exclude name="PSR12.Classes.ClassDeclaration.OpenBraceSameLine" />
       </rule>
       
       <!-- Exclude these directories -->
       <exclude-pattern>lib/vendor/*</exclude-pattern>
       <exclude-pattern>admin/assets/*</exclude-pattern>
       
       <!-- Include these directories -->
       <file>lib</file>
       <file>admin</file>
       <file>public/themes</file>
       <file>install</file>
       <file>api</file>
   </ruleset>
   ```

3. Excluded directories rationale:
   - `lib/vendor/` - Third-party code
   - `admin/assets/` - Frontend assets
   - `public/themes/assets/` - Theme assets

---

### Phase 2: Initial Analysis

1. Run initial scan:
   ```bash
   ./vendor/bin/phpcs --standard=PSR12 lib/ admin/ public/themes/ install/ api/
   ```

2. Generate error report:
   ```bash
   # JSON report for parsing
   ./vendor/bin/phpcs --standard=PSR12 --report-json lib/ > phpcs-report.json
   
   # Summary by severity
   ./vendor/bin/phpcs --standard=PSR12 --report=summary lib/
   ```

3. Categorize violations by type:
   - Brace placement (class/method)
   - Indentation
   - Spacing
   - Line length
   - Other

---

### Phase 3: Auto-Fix Issues

1. Install PHP-CS-Fixer:
   ```bash
   composer require --dev friendsofphp/php-cs-fixer:^3.57
   ```

2. Create `.php-cs-fixer.dist.php`:
   ```php
   <?php
   
   $finder = PhpCsFixer\Finder::create()
       ->in(['lib', 'admin', 'public/themes', 'install', 'api'])
       ->exclude('vendor')
       ->exclude('assets')
       ->notName('*.min.css')
       ->notName('*.min.js')
       ->files();
   
   return (new PhpCsFixer\Config())
       ->setRules([
           '@PSR12' => true,
           'braces' => ['position_after_functions_and_declarations' => 'next'],
           'indentation_type' => true,
           'line_ending' => true,
           'no_trailing_whitespace' => true,
           'no_unused_imports' => true,
       ])
       ->setFinder($finder);
   ```

3. Run auto-fix:
   ```bash
   # Preview changes
   ./vendor/bin/php-cs-fixer fix --dry-run --diff
   
   # Apply fixes
   ./vendor/bin/php-cs-fixer fix --diff
   ```

---

### Phase 4: Manual Fixes

| Category | Estimated Fixes | Approach |
|----------|-----------------|----------|
| Class braces | ~500 | Regex + IDE batch |
| Method braces | ~800 | Regex + IDE batch |
| Indentation | Variable | Manual per file |
| Control structures | Variable | php-cs-fixer |
| Complex files | 20-50 | Manual review |

Priority order:
1. lib/core/Bootstrap.php
2. lib/core/Dispatcher.php
3. lib/dao/*.php
4. lib/service/*.php
5. admin/*.php

---

### Phase 5: CI/CD Integration

Create `.github/workflows/phpcs.yml`:

```yaml
name: Code Quality

on: [push, pull_request]

jobs:
  phpcs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          
      - name: Install dependencies
        run: composer install --no-interaction
        
      - name: Run PHP_CodeSniffer
        run: ./vendor/bin/phpcs --standard=PSR12 --colors lib/ admin/ public/themes/
        
      - name: Run PHP-CS-Fixer (check mode)
        run: ./vendor/bin/php-cs-fixer fix --dry-run --diff
```

Optional: Add pre-commit hook.

---

### Phase 6: Verification

1. Final scan:
   ```bash
   ./vendor/bin/phpcs --standard=PSR12 lib/ admin/ public/themes/ install/ api/
   # Exit code 0 = no errors
   ```

2. Run tests:
   ```bash
   ./vendor/bin/phpunit
   ```

---

## Timeline Estimate

| Phase | Duration |
|-------|----------|
| Phase 1: Setup | 1 day |
| Phase 2: Analysis | 1-2 days |
| Phase 3: Auto-fix | 1 day |
| Phase 4: Manual fix | 5 days |
| Phase 5: CI/CD | 1 day |
| Phase 6: Verification | 1 day |

**Total: ~10-12 days**

---

## Commands Reference

```bash
# Install
composer require --dev squizlabs/php_codesniffer:^4.0
composer require --dev friendsofphp/php-cs-fixer:^3.57

# Check code
./vendor/bin/phpcs --standard=PSR12 lib/
./vendor/bin/phpcs --standard=PSR12 --report=summary lib/

# Auto-fix
./vendor/bin/php-cs-fixer fix --diff

# Specific directory
./vendor/bin/phpcs --standard=PSR12 lib/core/

# CI check
./vendor/bin/phpcs --standard=PSR12 --colors lib/ admin/ public/themes/
```

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking production code | High | Run full test suite before commit |
| Auto-fix corruption | Medium | Use git, test frequently |
| Time for full fix | High | Prioritize core files first |
| CI build failures | Medium | Fix violations incrementally |

---

## Success Criteria

- [ ] PHP_CodeSniffer installed via Composer
- [ ] phpcs.xml configuration created
- [ ] Initial scan completed with error report
- [ ] PHP-CS-Fixer applied for auto-fixable issues
- [ ] Manual fixes applied to remaining violations
- [ ] CI/CD workflow created
- [ ] Full scan passes with exit code 0
- [ ] All PHPUnit tests pass
