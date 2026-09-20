# Scriptlog Installation Wizard — Specification

> Generated from code analysis of `install/` directory.
> Application: Scriptlog
> Starting page: `/install/`

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Screen 1 — System Requirements](#screen-1--system-requirements)
3. [Screen 2 — Language & Preferences](#screen-2--language--preferences)
4. [Screen 3 — Database Configuration](#screen-3--database-configuration)
5. [Screen 4 — Administrator Account](#screen-4--administrator-account)
6. [Installation Completion](#installation-completion)
7. [Cleanup Reminder](#cleanup-reminder)
8. [Appendix: File Map](#appendix-file-map)

---

## Architecture Overview

### Entry Points

| URL | File | Purpose |
|-----|------|---------|
| `/install/` | `install/index.php` | Full wizard (4-step flow) |
| `/install/setup-db.php` | `install/setup-db.php` | Resume flow when `config.php` exists but DB tables are missing |
| `/install/finish.php` | `install/finish.php` | Success/failure screen after installation completes |
| `/install/validate-db.php` | `install/validate-db.php` | AJAX endpoint for database credential validation |
| `/install/update-step.php` | `install/update-step.php` | AJAX endpoint for step progression |

### Step Flow

```
  Step 1 (Requirements) ──> Step 2 (Language) ──> Step 3 (Database) ──> Step 4 (Account) ──> finish.php
     │                        │                      │                       │
     │ CSRF token             │ No validation         │ AJAX validate-db.php  │ POST with all data
     │ session_start()        │                       │                       │
     │ session step tracking  │                       │                       │
```

### Session State Machine

| Session Key | Purpose | Set When |
|-------------|---------|----------|
| `install_step_reached` | Max step reached (1-4) | Step 1 init, AJAX `update-step.php`, AJAX `validate-db.php` |
| `csrf_token` | CSRF token (32-byte hex) | Page load if empty |
| `token` | App installation key (32-char random) | POST submission |
| `server_config` | Web server config metadata | POST submission |
| `install` | Installation flag (boolean) | POST submission |

### Hard Locks

- **config.php exists** → Block all wizard pages; show "Already Installed" message
- **Step bypass via URL** → Server enforces `$_SESSION['install_step_reached'] < 3` before allowing Step 4 submission
- **Client-side gatekeeper** → `goToStep()` JS won't advance beyond `reached + 1`
- **CSRF mismatch** → 403 exit with message

---

## Screen 1 — System Requirements

### Preconditions

- `config.php` does **not** exist in the project root
- `session_start()` has been called
- `$_SESSION['install_step_reached']` is initialised to `0` or `1`
- Session CSRF token exists (`bin2hex(random_bytes(32))`)
- PHP ≥ 7.4 running

### User Actions

1. Navigate to `/install/`
2. View the requirements dashboard showing system environment, PHP extensions, server modules, and directory permissions
3. If all requirements pass: click **"All Good! Continue →"** button
4. If any requirement fails: the button is disabled with **"Requirements Not Met 🔒"**; user must fix the issue server-side and refresh

### Expected UI

- **Step indicator** at top with 4 numbered circles: 1 (active), 2, 3, 4 (blocked/grey)
- **System Environment** section (3 cards in a grid):
  - PHP Version (green if ≥ 7.4, red otherwise) with "Meets requirement" / "Update PHP"
  - Operating System (green for Linux/freebsd/Windows/macOS) with "Compatible System"
  - Web Server (green for Apache/LiteSpeed/nginx/IIS) with version string
- **PHP Extensions** table:
  - PCRE UTF-8, SPL, Filters, Iconv, Mbstring, Fileinfo, GD, PDO MySQL
  - Each row: extension name (left), "Enabled" (green) or "Disabled" (red) + check/cross icon
- **Server Modules** section (Apache only):
  - Apache Mod_Rewrite with check icon
- **Directories & Permissions** table:
  - Main Engine (`lib/main.php`), Load Engine (`Autoloader.php`), Logs, Cache, Sessions, Themes, Plugins
  - Each row: name + path (left), "Writable" (green) or "Read Only" (red) + check/cross icon
- **Info alert**: "If any item above is marked in red, please fix it..."
- **Continue button** (green, rounded pill): visible only when `are_all_requirements_met()` returns `true`
- **Dark/light theme toggle** (fixed top-right circle button)

### Expected Validation

| Check | Method | Expected |
|-------|--------|----------|
| PHP version | `version_compare(PHP_VERSION, '7.4', '>=')` | ≥ 7.4 |
| PCRE UTF-8 | `preg_match('/^.$/u', 'ñ')` + `preg_match('/^\pL$/u', 'ñ')` | Both true |
| SPL | `function_exists('spl_autoload_register')` | True |
| Filters | `function_exists('filter_list')` | True |
| Iconv | `extension_loaded('iconv')` | True |
| Mbstring | `extension_loaded('mbstring')` | True |
| Fileinfo | `extension_loaded('fileinfo')` | True |
| GD | `function_exists('gd_info')` | True |
| PDO MySQL | `extension_loaded('pdo_mysql') && class_exists('PDO')` | True |
| Main engine | `is_file('lib/main.php')` | True |
| Loader | `file_exists('Autoloader.php')` | True |
| Log directory | `is_dir('public/log') && is_writable('public/log')` | True |
| Cache directory | `is_dir('public/cache') && is_writable('public/cache')` | True |
| Sessions directory | `is_dir('public/files/cache/sessions') && is_writable(...)` | True |
| Themes directory | `is_dir('public/themes') && is_writable(...)` | True |
| Plugins directory | `is_dir('admin/plugins') && is_writable(...)` | True |
| mod_rewrite (Apache) | `apache_get_modules()` contains `mod_rewrite` | True |
| URI determination | `$_SERVER['REQUEST_URI']` or `PHP_SELF` or `PATH_INFO` | Any set |

### Expected Network Behaviour

- **No network requests** on page load
- On **"All Good! Continue"** click:
  - `GET update-step.php?step=2` (fetch)
  - Response: `{"success": true, "step_reached": 2}`
  - Session updated on server side
- No external CDN calls except Google Fonts (Outfit) preconnect

### Expected Security Behaviour

- Session-based CSRF token generated on first load: `$_SESSION['csrf_token'] = bin2hex(random_bytes(32))`
- No user input on this screen (observational only)
- `config.php` existence is a hard lock: if it exists, the entire page is replaced with "Already Installed" message and no form/JS is rendered
- All PHP checks use server-side evaluation only

### Expected Accessibility Checks

- ARIA labels: `role="region"`, `aria-labelledby` on sections
- Screen reader status via `role="status"` on enabled/disabled indicators
- Images have `alt` text ("Scriptlog Logo")
- Keyboard navigable **theme toggle button**
- Focusable **Continue button**
- Semantic `<table>` with `aria-label` for requirements tables
- `<code>` elements for file paths (`aria-label="Path"`)
- Step indicator uses **visual state classes** (`.active`, `.completed`, `.blocked`) for colour distinction

### Failure Scenarios

| Scenario | Expected Behaviour |
|----------|-------------------|
| PHP < 7.4 | PHP version card shows red, Continue button disabled |
| PDO MySQL missing | Extension row shows "Disabled" red, Continue disabled |
| Log directory not writable | Directory row shows "Read Only" red, Continue disabled |
| `lib/main.php` missing | Main Engine row shows "Read Only" red, Continue disabled |
| mod_rewrite missing (Apache) | "Server Modules" section not rendered at all |
| config.php exists | Entire page replaced with "System Already Installed" card, no form |
| Session not started | PHP error/warning in logs, wizard may not function |

### Mobile Behaviour

| Breakpoint | Behaviour |
|------------|-----------|
| ≤ 768px | System info grid collapses to single column; card padding reduced; step numbers smaller |
| ≤ 576px | Step indicator becomes horizontally scrollable with fixed-width items; buttons go full-width; layout buttons (`d-flex justify-content-between`) stack vertically |
| ≤ 400px | Further size reduction on step numbers (1.5rem), cards (0.75rem padding), form controls; theme toggle shrinks |
| ≤ 350px | Step items min-width 50px, font sizes at minimum |

---

## Screen 2 — Language & Preferences

### Preconditions

- Step 1 requirements all met
- `$_SESSION['install_step_reached'] ≥ 1`
- Clicked **"All Good! Continue"** from Step 1

### User Actions

1. Select preferred language from the dropdown (7 options)
2. Click **"Next: Database →"** to proceed
3. Optionally click **"← Back to Requirements"** to return to Step 1

### Expected UI

- Step indicator: Step 1 (completed ✓), Step 2 (active), Step 3, Step 4
- Single card with centred layout:
  - Globe icon + "Step 2: Language & Region"
  - Large centred heading: "Choose your preferred language"
  - Large `<select>` dropdown with 7 options:

    | Value | Label |
    |-------|-------|
    | `en` | English (US) — selected by default |
    | `id` | Bahasa Indonesia |
    | `fr` | Français |
    | `es` | Español |
    | `ru` | Русский |
    | `zh` | 中文 |
    | `ar` | العربية |

  - Subtitle: "You can change this later in the settings menu."
  - Two buttons: **"← Back to Requirements"** (link-style) and **"Next: Database →"** (primary)

### Expected Validation

- **No client-side or server-side validation** on this screen
- The selected value is simply posted with the final form in Step 4
- Default: `en`

### Expected Network Behaviour

- Clicking **"Next: Database →"** triggers:
  - `GET update-step.php?step=3` (fetch)
  - Response: `{"success": true, "step_reached": 3}`
- Clicking **"← Back to Requirements"**:
  - No network call (purely client-side `goToStep(1)`)

### Expected Security Behaviour

- No input validation required (single dropdown, no free-text)
- Step can only be reached if session `install_step_reached ≥ 1`

### Expected Accessibility Checks

- `<select>` element with `<option>` labels in native languages
- Font size 1.25rem for readability
- Keyboard navigation between steps via Tab
- Back/Next buttons are `<button>` elements (not `<a>`)

### Failure Scenarios

| Scenario | Expected Behaviour |
|----------|-------------------|
| User tries to skip to Step 3 via URL | Server-side gatekeeper shows Step 1 (requirements page) |
| JavaScript disabled | `goToStep()` won't work, but form still submits correctly at final step |

### Mobile Behaviour

- Dropdown goes full-width (`.col-md-8` → effectively 100% on mobile)
- Buttons stack vertically (per CSS `@media max-width: 576px`)

---

## Screen 3 — Database Configuration

### Preconditions

- Steps 1–2 completed
- `$_SESSION['install_step_reached'] ≥ 2`
- Language preference selected (defaults to `en`)

### User Actions

1. Fill in **Database Host** (default: `localhost`)
2. Fill in **Port** (default: `3306`)
3. Fill in **Database Name** (name of pre-created database)
4. Fill in **Database Username** (MySQL user with access to the DB)
5. Fill in **Database Password**
6. View or accept auto-generated **Table Prefix** (e.g., `gmoagh_`)
7. Click **"Next: Account →"** — triggers AJAX validation
8. If validation succeeds, button changes to **"Continue to Account →"**; click to proceed
9. Or click **"← Back"** to return to Step 2

### Expected UI

- Step indicator: Step 1 (✓), Step 2 (✓), Step 3 (active), Step 4
- Card with database-themed header icon
- Form fields:
  - **Database Host** (text, required, placeholder: `localhost`) — ~75% width
  - **Port** (text/number, required, placeholder: `3306`) — ~25% width
  - **Database Name** (text, required, placeholder: `scriptlog_db`) — full width
  - **Database Username** (text, required) — 50% width
  - **Database Password** (password, required) — 50% width
  - **Table Prefix** (text, auto-filled with random 6-char alphanumeric + underscore, e.g., `x3k9m2_`)
- Help text below each field (e.g., "Standard host is usually 'localhost'.")
- Two buttons: **"← Back"** and **"Next: Account →"** (visible initially)
- After successful AJAX validation: a hidden **"Continue to Account →"** button appears and the original "Next" button hides

### Expected Validation

#### Client-side (JavaScript `validateDatabaseStep()`)

| Field | Check |
|-------|-------|
| Host | Not empty (`trim()`) |
| Database Name | Not empty (`trim()`) |
| Username | Not empty (`trim()`) |
| Any missing | `alert("Hard Error: Database Host, Name, and Username are required.")` |

#### AJAX endpoint (`validate-db.php`)

| Check | Implementation |
|-------|----------------|
| config.php exists | Block: `{"success": false, "message": "Installation is already complete."}` |
| CSRF token | Compare with `$_SESSION['csrf_token']`; fail: `{"success": false, "message": "CSRF validation failed."}` |
| Empty fields | `{"success": false, "message": "All database fields are required."}` |
| Port numeric | `ctype_digit()` check; fail: `{"success": false, "message": "Port must be a valid number."}` |
| MySQL connection | `new mysqli($host, $user, $pass, $name, $port)` with `@` suppression |
| Error 2002 | `{"success": false, "message": "Server '{host}' not found or unreachable."}` |
| Error 1045 | `{"success": false, "message": "Access denied for user '{user}'."}` |
| Error 1049 | `{"success": false, "message": "Database '{name}' not found. Please create it first."}` |
| Other error | `{"success": false, "message": "Database Error: {message}"}` |
| Success | `{"success": true, "message": "Connection successful! Database is online."}` + sets `$_SESSION['install_step_reached'] = 3` |

#### Server-side (final POST in `index.php`)

- `ctype_digit(strval($dbport))` — must be integer
- `empty($dbhost) || empty($dbname) || empty($dbuser) || empty($dbpass)` — all required
- Prefix regex: `/^[a-zA-Z0-9]+_$/` — must end with underscore

### Expected Network Behaviour

1. **"Next: Account →" click**:
   - `POST validate-db.php` with `FormData` containing `db_host`, `db_name`, `db_port`, `db_user`, `db_pass`, `csrf_token`
   - Button shows spinner + "Verifying..." (disabled)
   - On success: `{"success": true}` → hidden button revealed, session updated
   - On failure: `alert("Database Connection Failed: " + message)` → button re-enabled
   - On fetch error: `alert("System Error: Could not reach validation endpoint.")` → button re-enabled

2. **"Continue to Account →" click**:
   - `GET update-step.php?step=4` (fetch)
   - Response: `{"success": true, "step_reached": 4}`

### Expected Security Behaviour

- CSRF token validated on AJAX endpoint
- Input sanitized with `escapeHTML()` and `trim()` on all fields
- `mysqli_report(MYSQLI_REPORT_OFF)` — no internal error leakage
- Connection attempt uses `@` suppression on mysqli constructor
- Error messages are **user-friendly** and don't leak credentials
- Random table prefix generated on server (`generate_table_prefix(6)`)
- If user-provided prefix fails regex validation, falls back to generated prefix
- `config.php` existence blocks the entire endpoint

### Expected Accessibility Checks

- All `<label>` elements have `for` attributes matching `id` on inputs
- Help text uses `<span class="help-text">` for screen reader context
- Buttons have clear labels ("Next: Account", "Back")
- AJAX loading state disables button (`btn.disabled = true`)
- Error messages via native `alert()` (not great for a11y — no ARIA live region)

### Failure Scenarios

| Scenario | Expected Behaviour |
|----------|-------------------|
| Wrong host | `alert("Server '{host}' not found or unreachable.")` |
| Wrong credentials | `alert("Access denied for user '{user}'.")` |
| Database doesn't exist | `alert("Database '{name}' not found. Create it first.")` |
| Port not numeric | `{"success": false, "message": "Port must be a valid number."}` |
| Network error | `alert("System Error: Could not reach validation endpoint.")` |
| Expired CSRF token | `alert("Database Connection Failed: CSRF validation failed.")` |
| User skips AJAX, tries Step 4 via URL | Server-side gatekeeper only shows Step 1 |

### Mobile Behaviour

- Host and Port fields stack vertically (`.col-md-9` + `.col-md-3` collapse to full width)
- Username and Password fields stack vertically (`.col-md-6` pair collapses)
- Buttons stack vertically on narrow screens (per CSS `@media max-width: 576px`)
- Form controls maintain `form-control-lg` sizing (3.2rem height on mobile, 2.8rem on ≤400px)

---

## Screen 4 — Administrator Account

### Preconditions

- Steps 1–3 completed
- `$_SESSION['install_step_reached'] ≥ 3`
- Database credentials AJAX-validated successfully

### User Actions

1. Enter **Admin Username** (8–20 chars, alphanumeric + underscore + dot)
2. Enter **Email Address** (valid email format)
3. Enter **Password** (min 8 chars, uppercase + lowercase + number + special char)
4. Enter **Confirm Password** (must match)
5. Review password strength tip
6. Optionally click **"← Access Database"** to go back to Step 3
7. Click **"🚀 Launch My Blog"** to submit

### Expected UI

- Step indicator: Step 1 (✓), Step 2 (✓), Step 3 (completed), Step 4 (active)
- Card with user-circle header icon
- Form fields:
  - **Create Admin Username** (text, required, placeholder: `admin`)
  - **Email Address** (email, required, placeholder: `admin@example.com`)
  - **Password** (password, required)
  - **Confirm Password** (password, required)
- Password tip alert (shield icon): "Strong passwords improve your site security. Aim for 8+ characters with a mix of letters, numbers, and special characters."
- Hidden input: `setup = "install"`
- Two buttons: **"← Access Database"** (link-style) and **"🚀 Launch My Blog"** (success green, full-width on mobile)

### Expected Validation

#### Client-side

- Bootstrap native validation via `.needs-validation` + `was-validated`
- HTML5 `required` attribute on all fields
- `type="email"` for email field (browser-native format validation)

#### Server-side (`install/index.php` POST handler)

| Field | Rule | Error Message |
|-------|------|---------------|
| `db_host` | Not empty + `escapeHTML()` | "Database settings are missing required fields." |
| `db_name` | Not empty | "Database settings are missing required fields." |
| `db_user` | Not empty | "Database settings are missing required fields." |
| `db_pass` | Not empty | "Database settings are missing required fields." |
| `db_port` | `ctype_digit()` | "Database port must be a valid integer." |
| `user_login` | 8–20 chars, regex: `/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/` | "Username must be 8-20 characters long and contain only alphanumerics, underscores, and dots." |
| `user_email` | `FILTER_VALIDATE_EMAIL` | "Please enter a valid email address." |
| `user_pass1` | Regex: `/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[\W])(?=\S*[A-Z])(?=\S*[\d])\S*$/` and match `user_pass2` | "Password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters." |

### Expected Network Behaviour

1. **Form submit** → `POST /install/` with `Content-Type: application/x-www-form-urlencoded`
2. Payload includes: `csrf_token`, `db_host`, `db_name`, `db_port`, `db_user`, `db_pass`, `tbl_prefix`, `user_login`, `user_email`, `user_pass1`, `user_pass2`, `site_language`, `setup=install`
3. Server-side processing (sequential):
   - CSRF check (403 if invalid)
   - Step reached check (403 if `< 3`)
   - Input sanitisation + validation
   - MySQL connection (`make_connection()` with `@` mysqli)
   - MySQL version check (≥ 5.7)
   - Defuse key generation
   - Database table installation (~21 tables)
   - Configuration file write (`config.php` + `.env`)
   - Web server config generation
   - Session storage of `server_config` and `token`
4. On success: `302 Redirect` to `finish.php?status=success&token={key}`
5. On failure: Page re-renders with error alert box

### Expected Security Behaviour

- **CSRF token** validated against session
- **Step gatekeeper**: `$_SESSION['install_step_reached'] < 3` → 403 "Security Error: You must complete the database configuration"
- `remove_bad_characters()` applied to username (additional sanitisation beyond regex)
- `escapeHTML()` applied to db host, db pass, and password fields
- `filter_input(FILTER_SANITIZE_EMAIL)` on email
- `FILTER_VALIDATE_EMAIL` check on email
- Password stored as `password_hash(base64_encode(hash('sha384', $pass, true)), PASSWORD_DEFAULT)` — double hashed with SHA-384 + bcrypt
- Defuse encryption key generated **outside web root** (`storage/keys/` or fallback `lib/utility/.lts/`)
- `random_bytes()` for table prefix generation
- Table prefix validated via regex; invalid prefix silently replaced with auto-generated one
- `mysqli_report(MYSQLI_REPORT_OFF)` — suppression of internal error details
- Error messages are user-friendly, not raw SQL errors
- Database connection creds written to `config.php` with `$_ENV` pattern (env var overrides)
- `.env` file generated with all secrets

### Expected Accessibility Checks

- All `<input>` elements have associated `<label>` with `for` attribute
- Input types appropriate (`email`, `password`, `text`)
- Error messages shown in an alert box with `role="alert"`-like structure
- Password tip in an `<small>` element inside an alert
- Semantic HTML form with `novalidate` (Bootstrap handles client-side)
- Keyboard-navigable: Tab through all fields and buttons
- Submit button has clear label ("Launch My Blog")

### Failure Scenarios

| Scenario | Expected Behaviour |
|----------|-------------------|
| Username too short (< 8) | Error: "Username must be 8-20 characters..." |
| Username too long (> 20) | Error: "Username must be 8-20 characters..." |
| Username has special chars | Error: "… only alphanumerics, underscores, and dots." |
| Invalid email | Error: "Please enter a valid email address." |
| Password too weak | Error: "Password must be at least 8 characters…" |
| Passwords don't match | Error: same as weak password (comparison in same condition) |
| MySQL version < 5.7 | Runtime exception → error message |
| Defuse key generation fails | Falls back to `lib/utility/.lts/lts.php`, logs error |
| Config file not writable | Error: "Failed to create configuration files. Please check file permissions." |
| Database connection fails | RuntimeException with friendly message |
| Table creation fails | RuntimeException caught, error displayed on page |
| CSRF token mismatch | 403 "CSRF validation failed!" (no page render) |
| User lands on Step 4 without DB validation | 403 "Security Error: You must complete the database configuration" |

### Mobile Behaviour

- Username and Password/Confirm fields stack vertically (50% columns collapse to 100%)
- Buttons stack vertically (per `@media max-width: 576px`)
- Submit button full-width on mobile
- Same responsive behaviour as other screens (card padding, font sizes)

---

## Installation Completion

### Preconditions

- All 4 steps completed successfully
- `config.php` written to disk
- Database tables installed
- Redirected from `index.php` POST handler

### URL

`/install/finish.php?status=success&token={installation_key}`

### Expected Behaviour

**Success path** (valid `status=success` and `token` matches session):

1. Green checkmark icon (fa-check-circle)
2. "Installation Successful!" heading
3. "Your blog is ready to go. You can now log in to the admin panel and start blogging."
4. **Server-specific instructions**:
   - **Nginx**: Yellow warning box with `include /path/to/nginx-rewrites.conf;` instruction
   - **IIS**: Blue info box about `web.config` generation and required modules
   - **Apache/LiteSpeed**: No additional instructions (`.htaccess` generated automatically)
5. **"Log In to Dashboard"** button → links to `/admin/login.php`
6. Session cleanup: `purge_installation()` called

**Invalid/no token path**:

1. Orange warning icon (fa-exclamation-circle)
2. "Oops! Installation is already complete." message
3. Auto-redirect to `/admin/login.php` after 3 seconds (via `setTimeout`)

### Expected Security Behaviour

- **Token validation**: `$_GET['token']` compared against `$_SESSION['token']` — prevents unauthorised access to finish page
- `purge_installation()` cleans up session data: unset `install`, `token`, `install_step_reached`, `csrf_token`, `server_config`; session destroyed
- If no valid token, user is redirected away after 3 seconds

### Expected UI

- Centered card with Scriptlog logo
- Success or warning state
- Server-specific configuration hints
- Single CTA button ("Log In to Dashboard")
- Footer with copyright and memory/time stats

---

## Cleanup Reminder

### What the Installer Creates

| Path | Purpose | Should Clean Up? |
|------|---------|------------------|
| `config.php` | Application configuration | **No** — required for operation |
| `.env` | Environment variables | **No** — required for operation |
| `.htaccess` | Apache rewrite rules | **No** — required for operation |
| `nginx-rewrites.conf` | Nginx rewrite rules | **No** — required for operation |
| `web.config` | IIS rewrite rules | **No** — required for operation |
| `storage/keys/*.php` | Defuse encryption key | **No** — required for operation |
| `public/log/*` | Log files (empty initially) | **No** — required for operation |
| `public/cache/*` | Cache files | **No** — required for operation |
| `public/files/cache/sessions/*` | Session files | **No** — required for operation |

### What Should Be Removed

| Path | Reason |
|------|--------|
| `/install/` directory | Security: prevents re-installation and exposes installation code to attackers |
| `/install/assets/` | Included in install directory |
| `lib/utility/.lts/` | Only used as fallback if defuse key generation fails |

### Note

The `config.php` existence check acts as the **primary hard lock** against re-installation. Even if the install directory is not removed, the wizard will show "System Already Installed" and block all modification.

---

## Appendix: File Map

| File | Role |
|------|------|
| `install/index.php` | Main 4-step wizard entry point |
| `install/setup-db.php` | Resume wizard when config.php exists (admin account only) |
| `install/finish.php` | Completion screen |
| `install/validate-db.php` | AJAX DB validation endpoint |
| `install/update-step.php` | AJAX step progression endpoint |
| `install/install-layout.php` | `install_header()`, `install_footer()`, `get_sisfo()`, `required_settings()`, `check_mod_rewrite()`, `check_dir_file()`, `are_all_requirements_met()` |
| `install/include/settings.php` | Bootstrap, session start, constants |
| `install/include/check-engine.php` | All requirement check functions (PHP version, MySQL version, extensions, directories) |
| `install/include/setup.php` | Database functions, config generation, key generation, server config, i18n data installer |
| `install/include/dbtable.php` | SQL table definitions for all 21 tables |
| `install/assets/css/form-validation.css` | Styles with light/dark theme CSS custom properties and responsive breakpoints |
| `install/assets/vendor/bootstrap/` | Bootstrap 4.6, jQuery 3.3.1, Popper, Holder.js |
| `install/assets/vendor/font-awesome/` | Font Awesome 4.7 |
| `install/assets/img/` | Logo (icon612x612.png), favicon |
