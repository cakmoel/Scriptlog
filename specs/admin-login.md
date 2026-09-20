# Admin Login & Authentication — Test Specification

## Purpose

The Admin Login & Authentication system is the gateway to the Scriptlog content management
backend. It provides:

- Secure authentication via username or email + password
- Session management with encrypted storage and fingerprinting
- "Remember Me" persistent login with token rotation
- Multi-layer rate limiting against brute-force attacks
- CAPTCHA enforcement after repeated failures
- Honeypot anti-bot fields
- CSRF-protected form submission
- Password reset flow via email
- Role-based access control (6 user levels)
- Session hijacking detection and prevention

This specification covers the complete login flow, authentication lifecycle, session
management, rate limiting, password reset, and access control enforcement.

---

## URL

| Page                    | URL                                      |
|-------------------------|------------------------------------------|
| Admin Login             | `<base_url>/admin/login.php`             |
| Admin Dashboard         | `<base_url>/admin/index.php?load=dashboard` |
| Password Reset          | `<base_url>/admin/reset-password.php`    |
| CAPTCHA Image (Login)   | `<base_url>/admin/captcha-login.php`     |
| CAPTCHA Image (Reset)   | `<base_url>/admin/captcha-forgot-pwd.php`|

---

## Preconditions

1. **Application installed**: `config.php` exists with valid DB credentials, database
   tables created, admin user seeded.
2. **Browser state**: No active session cookies; no cached login tokens.
3. **Admin user exists**: One user with `user_level = 'administrator'` and known
   credentials.
4. **Session storage**: File-based sessions enabled and writable.

---

## User Actions

### 1. Page Load (GET `/admin/login.php`)

| Action               | Detail                                      |
|----------------------|---------------------------------------------|
| Direct URL access    | Enter URL in address bar                    |
| Bookmark navigation  | Click bookmarked link                       |
| Redirect from auth   | Attempt to access dashboard without session |
| Browser refresh      | Press F5 / Ctrl+R / Cmd+R                   |
| Back button          | Navigate back from dashboard (post-logout)  |
| Forward button       | Navigate forward to login                   |

### 2. Form Interaction

| Action                           | Detail                                          |
|----------------------------------|-------------------------------------------------|
| Type into username/email field   | Single character, paste, autofill                |
| Type into password field         | Single character, paste, autofill                |
| Type into CAPTCHA field          | Only visible after 5 failed attempts             |
| Toggle "Remember Me" checkbox    | Click label or checkbox                          |
| Click "Log In" button            | Submit form                                      |
| Press Enter in any field         | Submit form                                      |
| Click "Lost your password?" link | Navigate to reset-password.php                   |
| Click "Register" link            | Navigate to signup.php (if registration enabled) |
| Click error dismiss button       | Dismiss `alert-danger` banner                    |
| Tab through fields               | Keyboard navigation                              |
| Shift+Tab through fields         | Reverse keyboard navigation                      |

### 3. Form Submission (POST)

| Action                              | Detail                                                |
|-------------------------------------|-------------------------------------------------------|
| Valid credentials (username)        | Expected: session created, redirect to dashboard       |
| Valid credentials (email)           | Expected: same as username login                       |
| Invalid password                    | Expected: error message, no redirect                   |
| Non-existent username               | Expected: generic error, no enumeration                |
| Non-existent email                  | Expected: generic error, no enumeration                |
| Empty username/email                | Expected: client-side alert, no submit                 |
| Empty password                      | Expected: client-side alert, no submit                 |
| Both empty                          | Expected: client-side alert, no submit                 |
| Honeypot fields filled (bot)        | Expected: silent redirect to dashboard (pretend success) |
| Invalid CSRF token                  | Expected: "Session expired or invalid request" error    |
| Expired/missing session ID          | Expected: 400 Bad Request                               |
| Wrong unique key                    | Expected: 400 Bad Request - Key Mismatch                |
| 429 after 20+ attempts             | Expected: "Too many attempts. Please try again in 15 minutes." |
| Incorrect CAPTCHA (≥5 attempts)    | Expected: "Incorrect Captcha code."                     |
| Banned user account                 | Expected: "Invalid username, email, or password."       |
| Locked user account                 | Expected: "Invalid username, email, or password."       |

### 4. "Remember Me" Flow

| Action                                    | Detail                                                |
|-------------------------------------------|-------------------------------------------------------|
| Login with "Remember Me" checked          | Expected: 3 cookies set (scriptlog_auth, scriptlog_validator, scriptlog_selector) |
| Login without "Remember Me" checked       | Expected: session-only, no persistent cookies          |
| Close browser, reopen, visit dashboard    | Expected: if cookies present, auto-authenticated        |
| Manually delete cookies                   | Expected: redirected to login                           |
| Expired token (≥1 hour)                   | Expected: token rejected, cookies cleared, redirect to login |
| Corrupt/tampered cookie value             | Expected: decryption fails, cookies cleared, redirect to login |
| Token replay (copied cookie to other browser) | Expected: selector/validator mismatch, rejected, redirect to login |

### 5. Session Timeout

| Action                                         | Detail                                                |
|------------------------------------------------|-------------------------------------------------------|
| Remain idle on dashboard for >60 minutes       | Expected: session expires, redirect to login           |
| Actively use dashboard (data refresh)          | Expected: `_last_activity` updated, session maintained |
| Change IP address mid-session                  | Expected: `isGenuine()` mismatch, session destroyed    |
| Change User-Agent mid-session                  | Expected: fingerprint mismatch, session destroyed      |

### 6. Logout

| Action                              | Detail                                                |
|-------------------------------------|-------------------------------------------------------|
| Click Logout link                   | Expected: all cookies cleared, session destroyed, redirect to login |
| Try to access dashboard after logout | Expected: redirected to login                           |
| Click browser back after logout     | Expected: login page (not cached dashboard)             |

### 7. Password Reset Flow

| Action                              | Detail                                                |
|-------------------------------------|-------------------------------------------------------|
| Access `/admin/reset-password.php`  | Expected: form with email, CAPTCHA, CSRF token         |
| Submit valid email                  | Expected: success message, email sent (if transport configured) |
| Submit invalid email format         | Expected: "Please enter a valid email address"          |
| Submit non-existent email           | Expected: "No user account was found"                   |
| Submit empty email                  | Expected: "Please enter email address"                  |
| Incorrect CAPTCHA                   | Expected: "Please enter correct captcha code"           |
| Honeypot fields filled              | Expected: "anomaly behaviour detected!"                 |
| Invalid CSRF token                  | Expected: "Sorry, there was a security issue"           |

---

## Expected Behaviour

### Successful Login (Username)
1. Browser navigates to `/admin/login.php`
2. Login form renders with all components
3. User enters valid username and password
4. User clicks "Log In"
5. Form submits via POST to `/admin/login.php?action=LogIn&Id=<id>&uniqueKey=<key>`
6. Server validates CSRF, honeypot, login ID, unique key
7. Server verifies password against bcrypt hash
8. Session created with 7 variables + fingerprint
9. Session ID regenerated
10. `user_session` column updated in database
11. If "Remember Me" unchecked: no persistent cookies
12. If "Remember Me" checked: 3 cookies set (scriptlog_auth, scriptlog_validator, scriptlog_selector) with 1-hour expiry
13. IP login attempts deleted from `tbl_login_attempt`
14. User's `user_signin_count` reset to 0, `user_locked_until` cleared
15. Redirect (302) to `/admin/index.php?load=dashboard`

### Successful Login (Email)
Same as username, except `UserDao::checkUserPasswordByEmail()` is used.

### Failed Login
1. All same steps until password verification
2. Verification fails
3. `create_login_attempt($ip)` records attempt in `tbl_login_attempt`
4. If ≥3 attempts: `sleep()` of `2^(n-2)` seconds (max 30) (exponential backoff)
5. `sign_in_count` incremented for the user
6. If user's `sign_in_count % 5 == 0`: `user_locked_until` set (5 minutes × multiplier, max 60 min)
7. Generic error message: "Invalid username, email, or password." shown in `alert-danger`
8. CAPTCHA field appears when `failed_attempt_count >= 5`
9. Page re-renders with form, error banner, and (if applicable) CAPTCHA

### Logout
1. User clicks logout link (`?action=logout`)
2. `Authentication::logout()` called
3. `removeCookies()` called: deletes `scriptlog_auth`, `scriptlog_validator`, `scriptlog_selector` from browser
4. `clearAuthCookies()` called: deletes token row from `tbl_user_token` where `is_expired=1`
5. `clearAllAuthCookies()` called: sets all 3 cookies to expired (-86400s)
6. Session data cleared (`$_SESSION = []`)
7. Session cookie deleted
8. `session_destroy()` called
9. Redirect (302) to `login.php`

### Remember Me Auto-Login
1. User visits `/admin/index.php` without active session
2. `authenticator.php` detects `scriptlog_auth`, `scriptlog_validator`, `scriptlog_selector` cookies
3. Decrypts `scriptlog_auth` to get username
4. `findTokenByLogin()` looks up token in `tbl_user_token`
5. Validates `pwd_hash` against `scriptlog_validator` via `Tokenizer::setRandomPasswordProtected()`
6. Validates `selector_hash` against `scriptlog_selector` via `Tokenizer::setRandomSelectorProtected()`
7. Checks `expired_date >= current_date`
8. On success: renews all 3 cookies (token rotation), marks old token as expired
9. On failure: marks token expired, clears all cookies, redirects to login

---

## Validation Rules

### Login Form

| Field          | Rule                              | Validation Type      | Error Message                                  |
|----------------|-----------------------------------|----------------------|-------------------------------------------------|
| login          | Required; max 186 chars           | Client + Server      | "Please fill in all required fields." (generic) |
| user_pass      | Required; max 50 chars            | Client + Server      | "Please fill in all required fields." (generic) |
| csrf           | Required; valid single-use token  | Server               | "Session expired or invalid request."           |
| captcha_login  | Required when ≥5 attempts         | Server               | "Incorrect Captcha code."                        |
| scriptpot_name | Must be empty (honeypot)          | Server               | Silently returns (false) => "Anomaly detected."  |
| scriptpot_email| Must be empty (honeypot)          | Server               | Silently returns (false) => "Anomaly detected."  |
| remember       | Optional checkbox                 | -                    | -                                               |

### Password Reset Form

| Field          | Rule                              | Validation Type      | Error Message                                  |
|----------------|-----------------------------------|----------------------|-------------------------------------------------|
| user_email     | Required; valid email format      | Client + Server      | "Please enter email address" / "Please enter a valid email address" |
| csrf           | Required; valid single-use token  | Server               | "Sorry, there was a security issue"             |
| captcha_code   | Required; matches session value   | Server               | "Please enter correct captcha code"             |
| scriptpot_name | Must be empty (honeypot)          | Server               | "anomaly behaviour detected!"                    |
| scriptpot_email| Must be empty (honeypot)          | Server               | "anomaly behaviour detected!"                    |

### Password Constraints (User creation/update)

| Rule                           | Detail                                          |
|--------------------------------|--------------------------------------------------|
| Minimum length                 | 8 characters                                     |
| Requires lowercase             | At least 1                                       |
| Requires uppercase             | At least 1                                       |
| Requires digit                 | At least 1                                       |
| Requires special character     | At least 1                                       |
| Error message                  | "Password requires at least 8 characters with lowercase, uppercase letters, numbers and special characters" |

---

## Network Behaviour

### Login POST Request

```
POST /admin/login.php?action=LogIn&Id=<int>&uniqueKey=<md5_hash>
Content-Type: application/x-www-form-urlencoded

login=<username|email>&user_pass=<password>&csrf=<token>&
remember=on&scriptpot_name=&scriptpot_email=&captcha_login=
```

| Scenario                    | Status Code | Redirect     | Cookies                   |
|-----------------------------|-------------|--------------|---------------------------|
| Valid credentials           | 302         | index.php?load=dashboard | `scriptlog_session` session cookie; optionally 3 persistent cookies |
| Invalid password            | 200         | -            | None                      |
| CSRF failure                | 200         | -            | None                      |
| Honeypot triggered          | 200         | -            | None                      |
| 429 (≥20 attempts)         | 429         | -            | `Retry-After: 900` header |
| 400 (invalid login ID)     | 400         | -            | Body: "400 Bad Request"   |
| 400 (invalid unique key)   | 400         | -            | Body: "400 Bad Request - Key Mismatch" |
| 413 (whitelist violation)  | 413         | -            | Body: "413 Payload Too Large", `Retry-After: 3600` |

### Logout GET Request

```
GET /admin/index.php?action=logout
```

| Scenario                    | Status Code | Redirect     | Cookies Cleared           |
|-----------------------------|-------------|--------------|---------------------------|
| Authenticated (session)     | 302         | login.php    | Session cookie + 3 auth cookies |
| Already logged out          | 302         | login.php    | -                         |
| Non-existent session        | 302         | login.php    | -                         |

### Password Reset POST Request

```
POST /admin/reset-password.php
Content-Type: application/x-www-form-urlencoded

user_email=<email>&csrf=<token>&captcha_code=<code>&
Reset=Get+New+Password&scriptpot_name=&scriptpot_email=
```

| Scenario                    | Status Code | Redirect/Response          |
|-----------------------------|-------------|----------------------------|
| Valid email + CAPTCHA      | 200         | Success message; email sent|
| Invalid email               | 200         | Error banner on same page  |
| CAPTCHA mismatch            | 200         | "Please enter correct captcha code" |
| CSRF failure                | 200         | "Sorry, there was a security issue" |

### CAPTCHA Image Requests

```
GET /admin/captcha-login.php
GET /admin/captcha-forgot-pwd.php
```

| Scenario                    | Status Code | Response Headers           |
|-----------------------------|-------------|----------------------------|
| GD extension available      | 200         | `Content-Type: image/jpeg`; `Cache-Control: no-store, no-cache, must-revalidate`; `Expires: Mon, 01 Jul 1988 05:00:00 GMT` |
| GD extension missing        | 200         | Empty body (silent catch)  |
| Session active              | 200         | `$_SESSION['captcha_login']` or `$_SESSION['forgot_pwd']` set |

---

## UI Components

### Login Page

| Component                    | CSS Selector / Identifier          | Behavior                               |
|------------------------------|-------------------------------------|----------------------------------------|
| Logo image                   | `img[alt="scriptlog-logo"]`        | 72x72, links to `#`                    |
| Username/Email input         | `#inputLogin` `input[name="login"]` | `maxlength=186`, `autofocus`, `required` |
| Password input               | `#inputPassword` `input[name="user_pass"]` | `maxlength=50`, `required`, type=password |
| CAPTCHA input                | `#inputCaptcha` `input[name="captcha_login"]` | Visible only when `$failed_login_attempt >= 5` |
| CAPTCHA image                | `img[alt="captcha"]`               | Source: `/admin/captcha-login.php`      |
| Honeypot name field          | `input[name="scriptpot_name"]`     | Display: none (CSS hidden)              |
| Honeypot email field         | `input[name="scriptpot_email"]`    | Display: none (CSS hidden)              |
| "Remember Me" checkbox       | `#remember-me` `input[name="remember"]` | Pre-checked if `scriptlog_auth` cookie exists |
| "Log In" submit button       | `input[value="Log In"]`            | Type=submit, class=`btn btn-primary btn-block btn-flat` |
| Hidden CSRF token            | `input[name="csrf"]`               | Generated by `generate_form_token('login_form', 40)` |
| Error alert container        | `.alert.alert-danger`              | Shown when `$errors['errorMessage']` is set |
| Status alert                 | `.alert.alert-info`                | Shown when `$_GET['status']` is `changed` or `actived` |
| "Lost your password?" link   | `a[href*="reset-password.php"]`    | Navigates to password reset form         |
| "Register" link              | `a[href*="signup.php"]`            | Only visible when `is_registration_unable() === true` |

### Password Reset Page

| Component                    | CSS Selector / Identifier          | Behavior                               |
|------------------------------|-------------------------------------|----------------------------------------|
| Email input                  | `#inputEmail` `input[name="user_email"]` | `type=email`, `autofocus`, `required` |
| CAPTCHA input                | `#inputCaptcha` `input[name="captcha_code"]` | Required |
| CAPTCHA image                | `img[alt="image_captcha"]`         | Source: `/admin/captcha-forgot-pwd.php` |
| Honeypot fields              | `input[name="scriptpot_name"]` `input[name="scriptpot_email"]` | CSS class `.scriptpot` (opacity:0, position:absolute) |
| Hidden CSRF token            | `input[name="csrf"]`               | Generated by `generate_form_token('reset_pwd', 24)` |
| "Get New Password" button    | `input[value="Get New Password"]`  | Type=submit                              |
| Error alert                  | `.alert.alert-danger`              | Shown on validation errors                |
| Success alert                | `.alert.alert-success`             | Shown when `$_GET['status'] == 'reset'`   |
| "Log in" link                | `a[href*="login.php"]`             | Navigate back to login                    |

### Dashboard (Post-Login)

| Component                    | Detail                               |
|------------------------------|--------------------------------------|
| Sidebar navigation           | Role-based menu items                 |
| Header user info             | User full name, level, avatar         |
| "Logout" link                | `?action=logout`                      |

---

## Accessibility

### Login Page

| Requirement                  | Implementation                       |
|------------------------------|--------------------------------------|
| Form labels                  | `<label for="inputLogin">`, `<label for="inputPassword">`, `<label for="inputCaptcha">` |
| Autofocus                    | `autofocus` on username/email input   |
| Required fields              | `required` attribute on all mandatory inputs |
| Error announcements          | Error in `.alert-danger` div, not `aria-live` |
| Keyboard navigation          | Tab order: login → password → CAPTCHA (if shown) → Remember Me → Log In |
| Focus indicators             | Bootstrap default `:focus` styles    |
| Color contrast               | Bootstrap defaults                    |
| ARIA labels                  | Missing on logo link, CAPTCHA image  |
| CAPTCHA accessibility        | No audio CAPTCHA alternative         |

### Password Reset Page

| Requirement                  | Implementation                       |
|------------------------------|--------------------------------------|
| Form labels                  | `<label for="inputEmail">`, `<label for="inputCaptcha">` |
| Autofocus                    | `autofocus` on email input            |
| Keyboard navigation          | Tab order: email → CAPTCHA → honeypot (hidden) → Get New Password |
| ARIA labels                  | `aria-label="Log In"` on login link, `aria-label="Sign Up"` on register link |
| Context menu disabled        | Right-click disabled on images and page |
| Ctrl+C/V/U disabled          | Client-side JavaScript                |
| F12/DevTools disabled        | Client-side JavaScript                |

### Issues

| Issue                         | WCAG Criterion   | Severity |
|--------------------------------|------------------|----------|
| CAPTCHA has no text/audio alternative | 1.1.1 Non-text Content | High |
| No `aria-live` region for error announcements | 4.1.3 Status Messages | Medium |
| Right-click and keyboard shortcuts disabled (reset password) | 2.1.1 Keyboard | High |
| No `aria-describedby` linking inputs to error messages | 1.3.1 Info and Relationships | Medium |

---

## Responsive Behaviour

| Breakpoint   | Layout                         | Observations                          |
|--------------|--------------------------------|---------------------------------------|
| Desktop ≥1024| Centered card ~360px wide      | Normal display                        |
| Tablet 768   | Same centered card             | Slightly smaller margins              |
| Mobile 320   | Full-width form                | Padding may be insufficient on small screens |

The login page uses `AdminLTE.min.css` `.login-box` with `hold-transition login-page` body
class. The `.login-box` is centered via CSS. On mobile, the box shrinks to fit the
viewport width with some padding.

---

## Browser Compatibility

| Browser       | Support                          |
|---------------|----------------------------------|
| Chrome 90+    | Full                             |
| Firefox 88+   | Full                             |
| Safari 14+    | Full                             |
| Edge 90+      | Full                             |
| IE 11         | Not supported (no polyfills)     |

Dependencies on `fetch()` and `FormData` in JavaScript, and `SameSite` cookie attribute
(PHP 7.3+). The application gracefully degrades for capabilities detection via
`class_exists()` guards and function existence checks throughout.

---

## Security Expectations

### CSRF Protection

- Login form: `generate_form_token('login_form', 40)` + `verify_form_token('login_form', csrf)`
- Password reset form: `generate_form_token('reset_pwd', 24)` + `verify_form_token('reset_pwd', csrf)`
- Tokens are single-use (deleted from `$_SESSION` after verification)
- Tokens use cryptographic randomness (`random_bytes()` → `openssl_random_pseudo_bytes()` → `ircmaxell_random_generator()`)

### XSS Protection

- All form output uses `htmlspecialchars()` or `safe_html()`
- Login input sanitized with `FILTER_SANITIZE_SPECIAL_CHARS`
- Error messages are plain text (no HTML in user-controlled messages)
- Password field NOT sanitized (preserved for bcrypt verification)

### SQL Injection Protection

- All queries use prepared statements via PDO and `db_prepared_query()`
- User-supplied values never interpolated into SQL strings
- `$this->db->prepare()` + `$stmt->execute($params)` pattern throughout

### Session Management

| Property            | Value                              |
|---------------------|------------------------------------|
| Session name        | `_scriptlog`                       |
| Session storage     | Encrypted files (AES-256-CBC + HMAC) |
| HttpOnly            | Yes                                |
| Secure              | Conditional (`is_cookies_secured()`) |
| SameSite            | `Lax`                              |
| Idle timeout        | 60 minutes                         |
| Regeneration        | On login; 20% chance on each start |
| Fingerprinting      | IP + User Agent + Accept headers   |

### Authentication Tokens

| Cookie              | Content                           | HttpOnly | Secure | SameSite | Expiry |
|---------------------|-----------------------------------|----------|--------|----------|--------|
| `scriptlog_auth`    | Defuse-encrypted username          | Yes      | Conditional | Strict | 1 hour |
| `scriptlog_validator` | 128-char random token             | Yes      | Conditional | Strict | 1 hour |
| `scriptlog_selector`  | 128-char random token             | Yes      | Conditional | Strict | 1 hour |

### Rate Limiting

| Layer               | Threshold | Action                          |
|---------------------|-----------|---------------------------------|
| IP attempts (24h)   | >= 5      | CAPTCHA required                |
| IP attempts (24h)   | >= 20     | HTTP 429, Retry-After: 900s     |
| User failures       | Every 5   | Account locked (5min - 60min)   |
| Login attempts      | >= 3      | Exponential backoff sleep       |

### Session Hijacking Protection

| Layer               | Mechanism                        |
|---------------------|----------------------------------|
| `SessionMaker::isGenuine()` | `md5(user_agent + (ip & 255.255.0.0))` vs `$_SESSION['_genuine']` |
| `authenticator.php` (fingerprint) | `hash_hmac('sha256', uagent, hash('sha256', ip))` vs `$_SESSION['scriptlog_fingerprint']` |
| `Authentication::getUserAuthSession()` | IP + Accept headers composite fingerprint |

### Sensitive Information

| Item                         | Exposure                        |
|------------------------------|---------------------------------|
| Password hash                | Stored in DB (bcrypt), never exposed |
| Password (plaintext)         | Never stored, never logged       |
| User session key             | `user_session` column in DB, HTTP cookie |
| Encryption key path          | Config file + DB + `.env`        |
| CSRF tokens                  | `$_SESSION`, single-use          |
| Login attempt records        | `tbl_login_attempt` table        |

### Information Disclosure Prevention

- Generic error message on all login failures: "Invalid username, email, or password."
- No distinction between "user not found" and "wrong password"
- No disclosure of whether email exists in password reset (if email not found, shows "No user account was found")
- CAPTCHA appears only after 5 attempts (no disclosure of attempt count to unauthorized user)

---

## Failure Scenarios

### 1. Invalid Input

| Scenario                         | Expected Behaviour                     |
|----------------------------------|----------------------------------------|
| Empty username/email             | Client alert: "Please enter username or email address" |
| Empty password                   | Client alert: "Please enter your password" |
| Both empty                       | Client alert for username first        |
| XSS in username field            | `htmlspecialchars()` encodes output    |
| Very long input (10000 chars)    | Truncated or handled by PHP limits     |

### 2. Slow Network

| Scenario                         | Expected Behaviour                     |
|----------------------------------|----------------------------------------|
| 3-second form submission         | Form submits normally                  |
| 30-second form submission        | May hit PHP `max_execution_time`       |
| CAPTCHA image fails to load      | `alt` text shown; user cannot complete CAPTCHA |

### 3. Offline

| Scenario                         | Expected Behaviour                     |
|----------------------------------|----------------------------------------|
| Page loaded then network drops   | Form submits, page shows error/timeout |
| Page never loaded                | Browser "Page not available" error     |
| CAPTCHA image fails to load offline | Form unusable (no CAPTCHA alternative) |

### 4. Database Unavailable

| Scenario                         | Expected Behaviour                     |
|----------------------------------|----------------------------------------|
| MySQL connection refused         | `Bootstrap::initialize()` throws exception; page may not render |
| MySQL timeout                    | Login processing timeout; generic error |
| `tbl_users` table missing        | `UserDao` queries fail; login may throw |

### 5. Expired Session

| Scenario                         | Expected Behaviour                     |
|----------------------------------|----------------------------------------|
| Session cleaned by garbage collector | No session data; CSRF validation fails; "Session expired or invalid request" |
| Session file deleted manually    | Same as above                           |
| Session cookie expired           | No session; redirected to login         |

### 6. Double Submit

| Scenario                         | Expected Behaviour                     |
|----------------------------------|----------------------------------------|
| Click "Log In" twice rapidly     | CSRF token consumed on first; second fails |
| Browser page refresh after POST  | Duplicate POST (browser warning); CSRF consumed on first |
| Re-submit with back button       | CSRF token no longer in session; fails  |

### 7. Refresh During Submission

| Scenario                         | Expected Behaviour                     |
|----------------------------------|----------------------------------------|
| Refresh after successful login before redirect | Session created; page refreshes the POST; depending on browser, may resubmit |
| Refresh during 429 response      | Page refreshes showing same error       |

### 8. Multiple Browser Tabs

| Scenario                         | Expected Behaviour                     |
|----------------------------------|----------------------------------------|
| Login in Tab A, then Tab B       | Both tabs share session; both authenticated |
| Logout in Tab A                  | Session destroyed; Tab B needs refresh to detect |
| Login, then open Tab B           | Both tabs authenticated (persistent login or session cookie) |

---

## Recovery Behaviour

### After Failed Login

1. Error message displayed in `.alert.alert-danger` banner
2. CAPTCHA shown after 5 attempts (persists through page refreshes)
3. Users can retry immediately (unless rate-limited)
4. Rate limit resets after 900 seconds (15 min) for 429
5. Account lockout resets with successful login or by admin intervention
6. No exponential backoff visual indication (appears as slow page load)

### After Session Timeout

1. User clicks a link or submits a form
2. Request checked by `authenticator.php` or `userAccessControl()`
3. Session determined invalid
4. `do_logout()` called → session destroyed, cookies cleared
5. Redirect to `login.php?status=timeout` (if status handling added) or plain redirect

### After Password Reset

1. User receives email with reset key (if mail transport configured)
2. User clicks link in email → navigates to reset confirmation page
3. User sets new password
4. Redirect to login with `?status=changed`
5. User logs in with new password

### After 429 Rate Limit

1. Response body: "Too many attempts. Please try again in 15 minutes."
2. `Retry-After: 900` header in response
3. User must wait 15 minutes before next attempt
4. All IP's login attempts are deleted from `tbl_login_attempt`

---

## Console

### Expected JavaScript Console Messages

| Scenario                     | Message                         | Severity |
|------------------------------|---------------------------------|----------|
| Username field empty on submit | "Please enter username or email address" (alert) | Info |
| Password field empty on submit | "Please enter your password" (alert) | Info |
| CAPTCHA image fails to load  | Image 404 in Network tab         | Warning  |
| iCheck plugin initialization | None (internal JS)              | -        |
| Session timeout (if JS sets) | None by default                 | -        |

### Expected PHP Error Log Messages (server-side)

| Scenario                     | Log Message                                |
|------------------------------|--------------------------------------------|
| Session decryption failure   | `Session decryption failed for both methods...` |
| Encryption key not found     | Generated and saved (logged in bootstrap)  |
| DB connection failure        | `Bootstrap::initialize()` throws exception |

---

## Network

### Expected Failed Requests

| Request                      | When                              | Status |
|------------------------------|-----------------------------------|--------|
| CAPTCHA image                | GD extension not available        | 200 (empty) |
| CAPTCHA image cache          | No-cache headers prevent caching  | 200    |
| 413 Payload Too Large        | POST with unexpected form fields  | 413    |
| 400 Bad Request              | Invalid/expired login ID          | 400    |
| 400 Bad Request - Key Mismatch | Wrong unique key                | 400    |
| 429 Too Many Requests        | ≥20 attempts in 24h              | 429    |
| 302 Redirect                 | Successful login/logout           | 302    |
| 302 Redirect (no config)     | config.php doesn't exist          | 302    |

---

## Test Scenarios

### Positive Tests (Critical/High Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| L-01 | Login with valid username and password          | Enter valid username + password → click "Log In"                      | Redirect to dashboard with session      | Critical |
| L-02 | Login with valid email and password             | Enter valid email + password → click "Log In"                         | Same as L-01                            | Critical |
| L-03 | Login with "Remember Me" checked                | Enter valid credentials → check "Remember Me" → Log In                | 3 persistent cookies set; auto-login on return | High |
| L-04 | Login without "Remember Me" checked             | Enter valid credentials → uncheck "Remember Me" → Log In              | Session-only; no persistent cookies      | High    |
| L-05 | Logout from dashboard                           | Click logout link                                                     | Cookies cleared; session destroyed; redirect to login | Critical |
| L-06 | Remember Me auto-login after browser close       | Login with Remember Me → close browser → reopen → visit dashboard     | Auto-authenticated (token valid)         | High    |
| L-07 | Full login flow with all checks                 | Navigate to login → enter credentials → submit → land on dashboard    | All steps complete; session active       | Critical |
| L-08 | Access dashboard when already logged in          | While authenticated, navigate to index.php directly                   | Dashboard renders; no redirect           | High    |

### Negative Tests (Critical/High Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| N-01 | Login with wrong password                       | Valid username + wrong password                                      | Error message; no redirect; attempt counted | Critical |
| N-02 | Login with non-existent username               | Non-existent username + any password                                  | Generic error: "Invalid username, email, or password." | Critical |
| N-03 | Login with non-existent email                   | Non-existent email + any password                                     | Generic error (same as N-02)             | Critical |
| N-04 | Login with empty username                       | Leave username empty → submit                                          | Client-side alert                        | High    |
| N-05 | Login with empty password                       | Enter username → leave password empty → submit                        | Client-side alert                        | High    |
| N-06 | Login with both fields empty                    | Leave both empty → submit                                             | Client-side alert (username first)       | High    |
| N-07 | Login with invalid CSRF token                   | Tamper with hidden CSRF field → submit                                | "Session expired or invalid request" error | Critical |
| N-08 | Login with consumed CSRF token                  | Submit login → back button → submit same token again                  | CSRF validation fails                    | High    |
| N-09 | Login with expired session ID                   | Wait for session to expire → submit                                   | Session expired; validation fails        | High    |

### Rate Limiting Tests (High Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| RL-01 | CAPTCHA appears after 5 failed attempts         | Submit 5 wrong passwords → observe page                               | CAPTCHA field visible after 5th attempt  | High    |
| RL-02 | Wrong CAPTCHA is rejected                       | After 5 attempts, enter wrong CAPTCHA                                 | "Incorrect Captcha code." error          | High    |
| RL-03 | 429 after 20 failed attempts                    | Submit 20+ wrong passwords                                            | 429 Too Many Requests; "Please try again in 15 minutes" | High |
| RL-04 | Retry-After header on 429                       | Same as RL-03 → inspect response headers                              | `Retry-After: 900` header present        | High    |
| RL-05 | Rate limit resets after 15 minutes              | Get 429 → wait 15 minutes → submit valid credentials                  | Login succeeds                           | High    |
| RL-06 | Rate limit is IP-specific                       | Get 429 from IP A → IP B can still try                                | IP B not affected                        | High    |
| RL-07 | Exponential backoff after 3 attempts            | Submit 3 wrong passwords quickly → measure response time              | Response time increases (2^n-2 seconds)   | Medium  |

### Lockout Tests (High Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| LO-01 | Account locked after 5 failed attempts          | Submit 5 wrong passwords for a valid username                         | User's `user_locked_until` set          | High    |
| LO-02 | Locked user gets generic error                  | Account locked → try correct password                                 | "Invalid username, email, or password."  | High    |
| LO-03 | Lockout duration increases with attempts        | Fail 10 times → lockout longer than after 5                           | `user_locked_until` further in future    | Medium  |
| LO-04 | Lockout clears on successful login              | Wait for lockout expiry → login with correct credentials              | Success; `user_signin_count` reset to 0  | High    |
| LO-05 | Banned user cannot login                        | Set `user_banned = 1` → try login                                     | Generic error; no redirect               | High    |

### Session Tests (Critical/High Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| S-01 | Session created on successful login             | Login → inspect PHP session                                           | Session variables: scriptlog_session_id, scriptlog_session_level, etc. | Critical |
| S-02 | Session fingerprint matches after login          | Login → check `scriptlog_fingerprint`                                 | Matches `hash_hmac('sha256', ua, hash('sha256', ip))` | Critical |
| S-03 | Session destroyed on logout                     | Login → logout → try to access dashboard                              | Redirect to login                       | Critical |
| S-04 | Session timeout (idle > 60 minutes)            | Login → wait 61 minutes → click link                                  | Session expired; redirect to login       | High    |
| S-05 | Session regenerated on login                    | Login → note session ID → Logout → Login again → session ID changed   | `session_regenerate_id(true)` called     | High    |
| S-06 | IP change triggers session invalidation         | Login from IP A → change to IP B (different /16 subnet) → make request | `isGenuine()` or fingerprint check fails | High    |
| S-07 | User-Agent change triggers invalidation         | Login with Chrome → refresh with Firefox UA → make request            | Fingerprint mismatch; session destroyed  | High    |

### Remember Me / Token Tests (High Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| RM-01 | Cookies set for Remember Me                    | Login with Remember Me → inspect browser cookies                      | `scriptlog_auth`, `scriptlog_validator`, `scriptlog_selector` set | High |
| RM-02 | Token created in database                       | Login with Remember Me → query `tbl_user_token`                      | Row inserted with `pwd_hash`, `selector_hash`, `expired_date` | High |
| RM-03 | Token auto-renewed on re-auth                   | Login with Remember Me → wait 30 min → revisit site                   | Cookie values rotated (new tokens)       | High    |
| RM-04 | Expired token rejected                          | Wait 1 hour+ → revisit site                                           | Cookies cleared; redirect to login       | High    |
| RM-05 | Corrupted auth cookie rejected                  | Tamper with `scriptlog_auth` value → reload                           | Decryption fails; cookies cleared; redirect to login | High |
| RM-06 | Tampered validator cookie rejected              | Tamper with `scriptlog_validator` value → reload                      | Hash mismatch; token expired; redirect   | High    |
| RM-07 | Token expiration on logout                      | Login with Remember Me → logout → inspect cookies                     | All 3 cookies cleared from browser       | High    |
| RM-08 | Token deletion on logout                        | Login with Remember Me → logout → query `tbl_user_token`              | Token row deleted (or is_expired=1)      | High    |

### Access Control Tests (Critical/High Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| AC-01 | Administrator accesses USERS page               | Login as admin → navigate to user management                          | Granted                                  | Critical |
| AC-02 | Subscriber denied USERS page                    | Login as subscriber → navigate to user management                     | 403 Forbidden / redirect                 | Critical |
| AC-03 | Administrator accesses PRIVACY page             | Login as admin → navigate to privacy settings                         | Granted                                  | High    |
| AC-04 | Editor denied PRIVACY page                      | Login as editor → navigate to privacy settings                        | 403 Forbidden / redirect                 | High    |
| AC-05 | Editor accesses TOPICS page                     | Login as editor → navigate to topics                                  | Granted                                  | High    |
| AC-06 | Author accesses COMMENTS page                   | Login as author → navigate to comments                                | Granted                                  | High    |
| AC-07 | Contributor accesses POSTS page                 | Login as contributor → navigate to posts                              | Granted                                  | High    |
| AC-08 | Subscriber accesses DASHBOARD                   | Login as subscriber → navigate to dashboard                           | Granted (all levels have dashboard)      | High    |
| AC-09 | Manager accesses PLUGINS page                   | Login as manager → navigate to plugins                                | Granted                                  | High    |
| AC-10 | Author denied PLUGINS page                      | Login as author → navigate to plugins                                 | 403 Forbidden / redirect                 | High    |
| AC-11 | All access levels tested                        | Create user for each role → test each ActionConst                     | Role-appropriate access granted/denied   | High    |

### Honeypot Tests (High Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| H-01 | Honeypot fields present but hidden              | Inspect HTML of login page                                            | Two hidden fields: scriptpot_name, scriptpot_email | High |
| H-02 | Bot fills honeypot fields                       | Submit with values in honeypot fields                                 | Silently returns with generic error; no DB query | High |
| H-03 | Human leaves honeypot fields empty              | Normal login (honepot empty)                                          | Honeypot check passes                    | High    |

### CSRF Tests (Critical)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| C-01 | CSRF token present in login form                | Inspect HTML of login form                                            | Hidden `csrf` input with token value     | Critical |
| C-02 | CSRF token consumed after use                  | Login → inspect `$_SESSION` for login_form_csrf                      | Token consumed (no longer in session)    | Critical |
| C-03 | CSRF missing returns error                      | Remove CSRF field → submit                                            | "Session expired or invalid request"     | Critical |
| C-04 | CSRF present in password reset form             | Inspect HTML of reset form                                            | Hidden `csrf` input with token value     | High    |
| C-05 | Password reset CSRF consumed after use          | Submit reset → inspect session                                        | Token consumed                           | High    |

### Password Reset Tests (High Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| PR-01 | Show reset form                                 | Navigate to `/admin/reset-password.php`                                | Form with email, CAPTCHA, CSRF           | High    |
| PR-02 | Submit valid email                              | Enter valid admin email → submit                                       | Success message "password has been reset. check your e-mail !" | High |
| PR-03 | Submit invalid email format                     | Enter "not-an-email" → submit                                         | "Please enter a valid email address"     | High    |
| PR-04 | Submit non-existent email                       | Enter "nobody@example.com" → submit                                   | "No user account was found"              | High    |
| PR-05 | Submit empty email                              | Leave email blank → submit                                            | "Please enter email address"             | High    |
| PR-06 | Incorrect CAPTCHA on reset                     | Enter correct email → wrong CAPTCHA → submit                          | "Please enter correct captcha code"      | High    |
| PR-07 | CSRF failure on reset                          | Remove CSRF → submit                                                   | "Sorry, there was a security issue"      | High    |
| PR-08 | Honeypot filled on reset                       | Fill honeypot → submit                                                 | "anomaly behaviour detected!"            | High    |
| PR-09 | Reset key generated in database                | Submit valid reset → query `tbl_users` for `user_reset_key`           | SHA-256 hash present in `user_reset_key` | High    |
| PR-10 | Reset key sent via email                        | Submit valid reset → inspect mail delivery                            | Email sent with reset instructions       | Medium  |

### CAPTCHA Tests (Medium Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| CP-01 | CAPTCHA image renders                           | Navigate to `/admin/captcha-login.php` directly                       | JPEG image returned                      | Medium  |
| CP-02 | CAPTCHA stored in session                       | Load CAPTCHA → inspect `$_SESSION['captcha_login']`                  | 6-char alphanumeric string               | Medium  |
| CP-03 | CAPTCHA image has anti-caching headers          | Load CAPTCHA → inspect response headers                               | `Cache-Control: no-store, no-cache, must-revalidate` | Medium  |
| CP-04 | CAPTCHA changes on each request                 | Load CAPTCHA twice → compare images                                   | Different CAPTCHA code each time         | Medium  |
| CP-05 | Login CAPTCHA hidden before 5 attempts          | Load login page fresh (0 attempts)                                    | No CAPTCHA field visible                 | Medium  |
| CP-06 | Login CAPTCHA shown after 5 attempts            | Fail 5 times → observe page                                           | CAPTCHA field and image shown            | Medium  |

### Boundary Tests (Medium Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| B-01 | Max length username                             | Enter 186-character username → submit                                  | Accepted or truncated (server-side limit) | Low    |
| B-02 | Max length password                             | Enter 50-character password → submit                                  | Accepted                                  | Low    |
| B-03 | Unicode username                                | Enter username with Unicode characters → submit                        | Handled (sanitized as string)            | Low    |
| B-04 | SQL injection in username field                 | Enter `' OR '1'='1` → submit                                          | Prepared statement prevents injection     | Critical |
| B-05 | HTML injection in username field                | Enter `<script>alert('xss')</script>` → observe rendered page         | `htmlspecialchars()` encodes output       | Critical |

### Security Tests (Critical Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| SE-01 | Brute-force simulation (100 rapid attempts)    | Script 100 rapid login attempts with random credentials                | Rate limiting kicks in; 429 after 20     | Critical |
| SE-02 | Brute-force simulation with valid username     | Script 100 attempts with known username, random passwords             | Rate limiting + captcha + lockout        | Critical |
| SE-03 | Session fixation attempt                       | Set known session ID → login → check if session ID changed            | Session ID regenerated on login          | Critical |
| SE-04 | SQL injection in whitelist                     | Submit form with `; DROP TABLE tbl_users; --` as extra field          | 413 Payload Too Large (whitelist rejects) | Critical |
| SE-05 | Directory traversal in URL                     | Access `/admin/../config.php`                                         | 404 (short-circuit in lib/main.php)      | High    |
| SE-06 | Cookie theft simulation (XSS)                  | Try `document.cookie` from console                                    | Cookies are HttpOnly (not accessible)    | Critical |
| SE-07 | Cookie replay from different IP                | Copy cookies to another machine → access dashboard                    | Token hash mismatch; rejected            | High    |
| SE-08 | Register link hidden when disabled             | Set membership `user_can_register=0` → observe login page             | Register link not shown                  | Medium  |
| SE-09 | Registration disabled → no signup access       | Set `user_can_register=0` → navigate to signup.php                    | Access denied or redirect               | Medium  |

### Boundary & Edge Case Tests (Medium Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| E-01 | Login immediately after password change         | Change password → login with new password                             | Success                                  | Medium  |
| E-02 | Login immediately after account creation        | Create user → login immediately                                       | Success                                  | Medium  |
| E-03 | Concurrent login from 2 locations              | Login from IP A → login from IP B → both active                       | Both sessions valid (Simultaneous logins allowed) | Medium |
| E-04 | Login with leading/trailing spaces in username | Enter " admin " → submit                                              | Username trimmed or fails (depends on DB) | Low    |
| E-05 | Login with uppercase username                  | Enter "ADMIN" → submit                                                | DB lookup may be case-sensitive or insensitive | Low    |
| E-06 | Session persists after browser crash           | Login → crash browser → reopen → navigate to dashboard                | Session cookie should restore if not expired | Low |

### Accessibility Tests (Medium Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| A-01 | Tab through login form                          | Press Tab repeatedly starting from URL bar                            | Focus: login → password → CAPTCHA (if shown) → Remember Me → Log In → Lost password link | Medium |
| A-02 | Submit form with Enter key                      | Focus on any field → press Enter                                     | Form submitted                           | Medium  |
| A-03 | Screen reader reads labels                      | Use screen reader on login form                                       | Labels read for all form fields          | Medium  |
| A-04 | Focus visible on all elements                   | Tab through all elements                                              | Visible focus ring on each element       | Medium  |
| A-05 | CAPTCHA has no alternative                      | Use screen reader on CAPTCHA                                          | No text/audio alternative (KNOWN ISSUE)  | Low     |

### Performance Tests (Low Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| P-01 | Login page load time (fresh)                    | Measure page load time                                                | < 500ms (without CAPTCHA)               | Low     |
| P-02 | Login page load time (with CAPTCHA)             | After 5 failures → measure page load time                             | < 1s (GD-based CAPTCHA generation)      | Low     |
| P-03 | Login POST response time (success)              | Measure POST → redirect                                               | < 500ms                                  | Low     |
| P-04 | Login POST response time (failure)              | Measure POST → error page (no exponential backoff)                    | < 300ms                                  | Low     |
| P-05 | Login POST with exponential backoff             | 5th failure → measure response time                                   | ~3-8 seconds (includes sleep())         | Low     |

### Security Scanner Tests (Critical)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| SC-01 | OWASP ZAP passive scan admin login              | Run ZAP proxy while interacting with login page                       | No high-severity alerts                  | Critical |
| SC-02 | OWASP ZAP active scan (authenticated)           | Login, set ZAP as authenticated, run active scan                      | No SQLi, XSS, or CSRF vulnerabilities   | Critical |
| SC-03 | Check response headers                          | Inspect login page response headers                                   | X-Frame-Options, CSP, etc. present      | High    |
| SC-04 | Check cookies have Secure flag                  | Inspect cookie attributes over HTTPS                                   | Secure flag present on all cookies       | High    |
| SC-05 | Check cookies have SameSite attribute           | Inspect cookie attributes                                             | SameSite=Strict on auth cookies; Lax on session | High |

### Browser Navigation Tests (Medium Priority)

| ID  | Scenario                                        | Steps                                                                 | Expected Result                          | Priority |
|-----|-------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|----------|
| BN-01 | Back button after login                         | Login → dashboard → click Back                                        | Returns to login page (no cached dashboard) | Medium |
| BN-02 | Forward button after logout                     | Login → logout → click Forward                                        | Returns to login page                    | Medium  |
| BN-03 | Refresh on login page                           | On login page → press F5                                              | Page refreshes; form state cleared       | Low     |
| BN-04 | Refresh after failed login (POST)              | Submit invalid → press F5                                             | Browser may warn about resubmitting      | Low     |
| BN-05 | Navigate login page from history                | Login → visit other site → press Back                                 | Login page loads fresh                   | Low     |

---

## Test Orchestration Notes

### Seed Data Requirements

For Playwright-based testing, the following seed data must exist before tests:

1. **Administrator user**: username=`administrator`, email=`admin@test.com`, password=`4dMin(*)^`, level=`administrator` (matching the project's test account from AGENTS.md)
2. **Additional test users** (for access control tests):
   - `manager_user` / `manager@test.com` / `Pass123$!` / level=`manager`
   - `editor_user` / `editor@test.com` / `Pass123$!` / level=`editor`
   - `author_user` / `author@test.com` / `Pass123$!` / level=`author`
   - `contributor_user` / `contributor@test.com` / `Pass123$!` / level=`contributor`
   - `subscriber_user` / `subscriber@test.com` / `Pass123$!` / level=`subscriber`
3. **Banned user**: `banned_user` / `banned@test.com` / `Pass123$!` / level=`administrator` / `user_banned=1`
4. **Session must be isolated** per test scenario (clear cookies, session between tests)
5. **Rate limiting tables** (`tbl_login_attempt`) must be truncated before each rate-limit test

### Playwright Configuration Hints

- Use `baseURL` set to the application URL
- Use `storageState` for authenticated session persistence (Remember Me tests)
- Use `context.addCookies()` to inject auth cookies for Remember Me tests
- Use `page.route()` to mock CAPTCHA image failures
- Use `page.on('response')` to capture response status codes and headers
- Use `page.on('console')` to capture console messages
- Use `request.post()` for direct API-style POST requests (rate limit tests)
- Use `page.waitForTimeout()` only for exponential backoff tests; prefer `waitForResponse()` for navigation

### Critical Test Dependencies

- Rate limiting and lockout tests share state: run in order, or reset state between tests
- Remember Me token tests: run immediately after login (tokens expire in 1 hour)
- Session timeout test: may need to manipulate session file timestamps or mock time
- Access control tests: each role needs to be tested with its own authenticated session
