# Remember Me (Persistent Login) — End-to-End Test Specification

> **Application**: Scriptlog (PHP 7.4+ / MariaDB / Bootstrap / jQuery)
> **Spec Version**: 1.0
> **Feature Owner**: Blogware Team
> **Bootstrap**: `e2e/seed.spec.ts`

---

## Table of Contents

1. [Overview](#1-overview)
2. [Datasets & Fixtures](#2-datasets--fixtures)
3. [Login Page — Remember Me Checkbox](#3-login-page--remember-me-checkbox)
4. [Login With Remember Me](#4-login-with-remember-me)
5. [Login Without Remember Me](#5-login-without-remember-me)
6. [Auto-Login (Persistent Cookie Path)](#6-auto-login-persistent-cookie-path)
7. [Token Renewal / Rotation](#7-token-renewal--rotation)
8. [Expired Token](#8-expired-token)
9. [Corrupt / Tampered Cookies](#9-corrupt--tampered-cookies)
10. [Logout & Cookie Cleanup](#10-logout--cookie-cleanup)
11. [Username Prefill](#11-username-prefill)
12. [Concurrent Tabs & Sessions](#12-concurrent-tabs--sessions)
13. [Security Expectations](#13-security-expectations)
14. [Accessibility](#14-accessibility)
15. [Responsive Behaviour](#15-responsive-behaviour)
16. [Browser Compatibility](#16-browser-compatibility)
17. [Failure Scenarios](#17-failure-scenarios)
18. [Recovery Behaviour](#18-recovery-behaviour)
19. [Screenshots to Capture](#19-screenshots-to-capture)
20. [Console & Network Expectations](#20-console--network-expectations)
21. [Test Scenarios](#21-test-scenarios)

---

## 1. Overview

### Purpose

"Remember Me" is an optional persistent-login feature on the admin login page. When enabled,
the server issues three HttpOnly cookies (`scriptlog_auth`, `scriptlog_validator`,
`scriptlog_selector`) with a **30-day** expiry (`Authentication::COOKIE_EXPIRE = 2592000`).
On subsequent visits, `admin/authenticator.php` detects the three cookies, verifies them
against a database token row in `tbl_user_token`, and auto-authenticates the user without
re-entering credentials. Every successful cookie-based login **rotates** the cookies and the
DB token (old token marked expired, new token issued), which limits replay damage.

### Key Files Under Test

| Layer | File | Role |
|-------|------|------|
| View | `admin/login.php` | Login form; renders Remember Me checkbox |
| Auth Guard | `admin/authenticator.php` | Session + persistent-login auto-auth decision |
| Core | `lib/core/Authentication.php` | `login()` cookie issuance; `logout()` cleanup |
| Core | `lib/core/Tokenizer.php` | Validator/selector token generation + verification |
| Core | `lib/core/ScriptlogCryptonize.php` | Encrypt/decrypt `scriptlog_auth` (Defuse) |
| Core | `lib/core/Session.php` | Session data + fingerprint |
| DAO | `lib/dao/UserTokenDao.php` | `tbl_user_token` CRUD |
| Utility | `lib/utility/cookies-baked.php` | `set_cookies_scl()` — SameSite/HttpOnly/secure flags |
| Utility | `lib/utility/regenerate-session.php` | Session ID regeneration + duplicate cookie dedup |
| Utility | `lib/utility/human-login.php` | Login context validation, rate limiting |
| Utility | `lib/utility/get-remembered-username.php` | Decrypts `scriptlog_auth` for username prefill |
| Admin Page | `admin/logout.php` | Logout endpoint (`action=logout&logOutId=...`) |
| Admin Nav | `admin/navigation.php` | Logout link with `do_logout_id()` token |
| Schema | `install/include/dbtable.php` | `tbl_user_token` DDL |

### How It Works (Summary)

1. **Issuance**: On login with `remember` checked, `Authentication::login()`:
   - Clears any existing active token for the login.
   - `scriptlog_auth` = Defuse ciphertext of the username (30 days).
   - `scriptlog_validator` = 128-char random token (30 days); stored as **bcrypt** `pwd_hash`.
   - `scriptlog_selector` = 128-char random token (30 days); stored as **encrypted SHA-256** `selector_hash`.
   - Inserts one row in `tbl_user_token` (`is_expired=0`, `expired_date = now + 30 days`).
2. **Auto-login**: On a request with all three cookies present and no live session,
   `authenticator.php` decrypts `scriptlog_auth`, looks up the non-expired token, and verifies
   validator (`password_verify`) + selector (`Tokenizer::isSelectorValid`) + expiry. On success it
   sets the session and **rotates** cookies + token. On failure it marks the token expired and
   clears cookies.
3. **Logout**: `Authentication::logout()` removes the three cookies, deletes expired DB tokens,
   destroys the session, and redirects to `login.php`.

### URL Formats

| Resource | URL |
|----------|-----|
| Admin login | `admin/login.php` |
| Dashboard | `admin/index.php?load=dashboard` |
| Logout | `admin/logout.php?action=logout&logOutId={id}` (nav link via `generate_request`) |
| Logout (alt) | `admin/index.php?action=logout` |
| CAPTCHA image | `admin/captcha-login.php` |

---

## 2. Datasets & Fixtures

### 2.1 Pre-seeded Admin Account

| Field | Value |
|-------|-------|
| Username | `administrator` |
| Password | `$E2E_ADMIN_PASS` |
| Role | `administrator` |

### 2.2 Cookie Set (after Remember Me login)

| Cookie | Content | Max-Age | HttpOnly | SameSite | Secure |
|--------|---------|---------|----------|----------|--------|
| `scriptlog_auth` | Defuse-encrypted username | 2592000s (30 d) | Yes | Strict | if SSL |
| `scriptlog_validator` | 128-char random | 2592000s | Yes | Strict | if SSL |
| `scriptlog_selector` | 128-char random | 2592000s | Yes | Strict | if SSL |

### 2.3 DB Token Row (`tbl_user_token`)

| Column | Value |
|--------|-------|
| `user_login` | `administrator` |
| `pwd_hash` | bcrypt hash of `scriptlog_validator` |
| `selector_hash` | Defuse-encrypted bcrypt of SHA-256(`scriptlog_selector`) |
| `is_expired` | `0` |
| `expired_date` | now + 30 days |

### 2.4 Pre-Seeded Expired Token (for negative tests)

| Column | Value |
|--------|-------|
| `user_login` | `administrator` |
| `is_expired` | `0` |
| `expired_date` | now - 1 day |

### 2.5 Pre-Seeded is_expired=1 Token (for replay tests)

| Column | Value |
|--------|-------|
| `user_login` | `administrator` |
| `is_expired` | `1` |

---

## 3. Login Page — Remember Me Checkbox

### URL

`admin/login.php`

### Preconditions

- Application installed; admin user seeded
- **No** `scriptlog_auth` cookie present (fresh browser context)

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Load login page | — | Checkbox rendered, **unchecked** |
| Inspect checkbox | — | `input#remember-me`, `name="remember"`, label "Remember Me" |
| Check the box | — | Checkbox becomes checked (iCheck styled) |
| Load login page with `scriptlog_auth` cookie | — | Checkbox **pre-checked** |

### UI Components

| Component | Selector | Notes |
|-----------|----------|-------|
| Remember Me checkbox | `input#remember-me[name="remember"]` | Pre-checked when `$_COOKIE['scriptlog_auth']` exists |
| Checkbox label | `label[for="remember-me"]` | "Remember Me" |
| Checkbox wrapper | `.checkbox.icheck` | iCheck plugin styling |
| Hidden CSRF | `input[name="csrf"]` | `generate_form_token('login_form', 40)` |
| Submit | `input[value="Log In"]` | `name="LogIn"` |

### Validation Rules

| Rule | Detail |
|------|--------|
| Checkbox optional | Absence = session-only login |
| Pre-check logic | `isset($_COOKIE['scriptlog_auth']) ? 'checked' : ''` |
| Form action | `login.php?action=LogIn&Id={human_login_id()}&uniqueKey={md5(app_key().$ip)}` |

---

## 4. Login With Remember Me

### URL

`admin/login.php` → POST `login.php?action=LogIn&Id={id}&uniqueKey={key}`

### Preconditions

- Fresh browser context (no existing cookies)
- Admin user exists

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Enter username | `administrator` | Field populated |
| Enter password | `$E2E_ADMIN_PASS` | Password masked |
| Check Remember Me | — | Box checked |
| Click Log In | — | 302 → dashboard |
| Inspect cookies | — | 3 cookies set with 30-day expiry |

### Expected Behaviour (server-side)

1. `processing_human_login()` validates context (CSRF, honeypot, login id, unique key)
2. `Authentication::validateUserAccount()` → bcrypt verify OK
3. `signin_count_to_zero()`, `locked_down_to_null()` reset
4. Session vars set (id, email, level, login, fullname, agent, ip, fingerprint, last_active)
5. `regenerate_session()` → new session ID; `user_session` updated in `tbl_users`
6. `remember_me` truthy → cookies issued:
   - `scriptlog_auth` = `ScriptlogCryptonize::scriptlogCipher($login, key)`
   - `scriptlog_validator` = `Tokenizer::createToken(128)`
   - `scriptlog_selector` = `Tokenizer::createToken(128)`
   - Each `set_cookies_scl(..., time()+COOKIE_EXPIRE, '/', domain, secure, true)` (SameSite Strict)
7. Existing active token for login marked expired (`updateTokenExpired`)
8. New token row created (`pwd_hash` bcrypt of validator; `selector_hash` encrypted SHA-256 of selector)
9. `delete_login_attempt($ip)` clears IP log
10. 302 redirect to `admin/index.php?load=dashboard`

### Validation Rules

| Rule | Detail |
|------|--------|
| Exactly 3 cookies | No more, no fewer |
| HttpOnly on all 3 | Not readable via JS (`document.cookie`) |
| Max-Age ≈ 30 days | `COOKIE_EXPIRE = 2592000` |
| SameSite=Strict | Cookie attributes |
| Secure | Only when SSL (`is_cookies_secured()`) |
| DB token `is_expired=0` | One active row per login |
| DB token expiry | now + 30 days |

### Network Behaviour

| Request | Method | Status | Cookies |
|---------|--------|--------|---------|
| POST `login.php?action=LogIn&...` | POST | 302 → dashboard | `scriptlog_auth`, `scriptlog_validator`, `scriptlog_selector` |

---

## 5. Login Without Remember Me

### Preconditions

- Fresh browser context
- Admin user exists

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Enter credentials | `administrator` / `$E2E_ADMIN_PASS` | |
| Leave Remember Me unchecked | — | |
| Click Log In | — | 302 → dashboard |
| Inspect cookies | — | **No** `scriptlog_*` auth cookies |

### Expected Behaviour

- `Authentication::login()` takes the `!$remember_me` branch
- `clearAuthCookies($user_login)` called → deletes expired token rows + clears any cookies
- Only the PHP session cookie persists

### Validation Rules

| Rule | Detail |
|------|--------|
| No persistent cookies | `scriptlog_auth`/`validator`/`selector` absent |
| No token row created | `tbl_user_token` empty for this login |
| Close browser → revisit | Session-only; login form shown again |

---

## 6. Auto-Login (Persistent Cookie Path)

### URL

`admin/index.php?load=dashboard` (or any admin page)

### Preconditions

- Remember Me cookies present from a prior login
- **No** active PHP session (browser closed / session expired)

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Close browser, reopen | — | Session gone |
| Navigate to dashboard | — | **Auto-authenticated**; dashboard renders |
| Navigate to any admin page | — | Remains logged in (cookie auth) |
| Visit login page | — | Redirected to dashboard (already logged in) |
| Incognito (no cookies) | — | Login form shown |

### Auto-Login Decision (`authenticator.php` Path 2)

| Check | Condition |
|-------|-----------|
| All 3 cookies present | `scriptlog_auth` && `validator` && `selector` non-empty |
| `scriptlog_auth` decrypts | Defuse decrypt yields a string |
| Token found | `findTokenByLogin($decrypt_auth, 0)` returns row |
| Validator valid | `Tokenizer::isPasswordValid(cookie, pwd_hash)` |
| Selector valid | `Tokenizer::isSelectorValid(selector_hash, cookie, secret)` |
| Not expired | `expired_date >= current_date` |

All pass → `$loggedIn = true`; token renewal runs (see §7).

### Validation Rules

| Rule | Detail |
|------|--------|
| Any missing cookie → no auto-login | All three required |
| Corrupt `scriptlog_auth` → cookies cleared | Try/catch → `clearAuthCookies('')` |
| Validator mismatch → token invalidated | `markCookieAsExpired()` + `clearAuthCookies()` |
| Selector mismatch → token invalidated | Same |
| Expired token → invalidated | `expired_date < now` |
| Successful auto-login → session populated | `scriptlog_session_login = token_info.user_login` |

### Network Behaviour

| Request | Method | Status |
|---------|--------|--------|
| `admin/index.php` with valid cookies | GET | 200 (dashboard) |
| `admin/index.php` with invalid cookies | GET | 200 → redirect to login (cookies cleared) |

---

## 7. Token Renewal / Rotation

### Preconditions

- Valid Remember Me cookies + matching DB token

### User Actions

| Action | Expected Behaviour |
|--------|-------------------|
| Auto-login (cookie path) | Old token `is_expired` → 1; new token row created |
| Observe cookies after reload | `validator`/`selector` values **changed** |
| Replay old cookie values | Rejected (old token now expired) |

### Rotation Sequence

1. `authenticator.php` verifies old cookies
2. `markCookieAsExpired($token_info['ID'])` → old token `is_expired = 1`
3. New `scriptlog_auth` (re-encrypted), new `validator`, new `selector` cookies issued
4. New `pwd_hash` (bcrypt of new validator) + `selector_hash` stored
5. `renewPersistentLogin()` updates token row for `user_login`
6. Expiry reset to now + 30 days

### Validation Rules

| Rule | Detail |
|------|--------|
| Token rotated on every auto-login | Old cookie values invalid after success |
| Replay of stolen old cookies fails | `findTokenByLogin(login, 0)` no longer returns old row |
| Session established | `scriptlog_session_login` set from token |

---

## 8. Expired Token

### Preconditions

- `tbl_user_token` has an active (`is_expired=0`) row whose `expired_date` is in the past

### User Actions

| Action | Expected Behaviour |
|--------|-------------------|
| Navigate to dashboard with expired-token cookies | Auto-login **rejected** |
| Observe DB | Token marked `is_expired = 1` |
| Observe cookies | `scriptlog_auth`/`validator`/`selector` cleared |
| Result | Login form shown; must re-authenticate |

### Validation Rules

| Rule | Detail |
|------|--------|
| `expired_date >= current_date` required | String comparison of `Y-m-d H:i:s` |
| Failure path | `markCookieAsExpired()` + `clearAuthCookies($decrypt_auth)` |

---

## 9. Corrupt / Tampered Cookies

### Preconditions

- Valid session cookies for a logged-in Remember Me user

### User Actions

| Action | Detail | Expected Behaviour |
|--------|--------|-------------------|
| Tamper `scriptlog_auth` | Change one char | Decrypt fails → cookies cleared → login form |
| Tamper `scriptlog_validator` | Change one char | `password_verify` fails → token invalidated → login form |
| Tamper `scriptlog_selector` | Change one char | `isSelectorValid` fails → token invalidated → login form |
| Submit incomplete set | Only `auth` + `validator` | Auto-login not attempted (all 3 required) |
| Copy cookies to another browser | Replay | Old token already rotated → rejected |

### Expected Behaviour

1. `authenticator.php` path 2 entered only with all 3 cookies
2. Decrypt exception → `$decrypt_auth = ""` → `clearAuthCookies('')` (token not found branch)
3. Validator/selector failure with existing token → `markCookieAsExpired(ID)` + `clearAuthCookies($decrypt_auth)`
4. User lands on login page

---

## 10. Logout & Cookie Cleanup

### URL

`admin/logout.php?action=logout&logOutId={do_logout_id()}` (nav link) or `admin/index.php?action=logout`

### Preconditions

- Logged in (session and/or Remember Me cookies)

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Click "Log Out" in nav | — | Logout link generated with `logOutId` token |
| Redirect | — | 302 → `login.php` |
| Inspect cookies | — | All 3 `scriptlog_*` cookies removed (expired) |
| Revisit dashboard | — | Login form shown |
| Back button | — | Login page (not cached dashboard) |

### Logout Sequence (`Authentication::logout`)

1. Capture `user_login` from session, then constructor cookies, then decrypt cookie
2. `removeCookies()` → `clearAllAuthCookies()` (expire all 3) + unset
3. `clearAuthCookies($userLogin)` → `deleteUserToken($user_login)` (deletes rows with `is_expired=1`) + clear cookies
4. `$_SESSION = []`
5. Session cookie deleted; `session_destroy()`
6. `direct_page('login.php', 302)`

### Logout Guards (`admin/logout.php`)

| Condition | Result |
|-----------|--------|
| Valid `logOutId` token | Logout proceeds |
| Invalid `logOutId` + Remember Me cookie present | Logout proceeds (Remember Me fallback) |
| Invalid `logOutId` + no Remember Me cookie | **400** — "URL Redirection to Untrusted Site" |
| Unauthorized (not logged in) | 403 → `index.php?load=403&forbidden=...` |

### Validation Rules

| Rule | Detail |
|------|--------|
| Cookies cleared on logout | `expires = time() - 86400` |
| DB tokens cleaned | Only `is_expired=1` rows deleted by `deleteUserToken` |
| Session destroyed | `session_destroy()` |
| Logout link tokenized | `do_logout_id()` + `verify_logout_id()` |

---

## 11. Username Prefill

### URL

`admin/login.php`

### Preconditions

- `scriptlog_auth` cookie present (from Remember Me login)

### User Actions

| Action | Input | Expected Behaviour |
|--------|-------|-------------------|
| Load login page | — | Username input **prefilled** with remembered username |
| Inspect input | — | `value` = decrypted username |
| Reload | — | Still prefilled |
| Corrupt `scriptlog_auth` | — | Empty value (silent fallback) |

### Implementation

- `get_remembered_username()` decrypts `$_COOKIE['scriptlog_auth']` via `ScriptlogCryptonize::scriptlogDecipher`
- Returns `''` on empty cookie, missing class, missing key, or decrypt failure
- Input: `value="<?= ... get_remembered_username() ?>"`

### Validation Rules

| Rule | Detail |
|------|--------|
| Corrupt cookie → no fatal | Silently returns empty string |
| Cookie absent → empty field | No stale prefill |
| Value escaped | `htmlspecialchars()` on output |

---

## 12. Concurrent Tabs & Sessions

### Preconditions

- Remember Me cookies set (user logged in)

### User Actions

| # | Action | Tab | Expected Behaviour |
|---|--------|-----|-------------------|
| 1 | Open admin in Tab 1 | 1 | Authenticated (session) |
| 2 | Open admin in Tab 2 | 2 | Authenticated (shared session) |
| 3 | Logout in Tab 1 | 1 | Session destroyed; cookies cleared |
| 4 | Navigate in Tab 2 | 2 | Login form (session gone; cookies cleared) |
| 5 | Login again in Tab 1 | 1 | New token; Tab 2 refresh still logged out (new cookies) |

### Validation Rules

| Rule | Detail |
|------|--------|
| Logout invalidates shared session | All tabs affected |
| New login issues new cookies | Fresh token; old one not restored |

---

## 13. Security Expectations

### Cookie Attributes

| Property | Value |
|----------|-------|
| Names | `scriptlog_auth`, `scriptlog_validator`, `scriptlog_selector` |
| Path | `/` |
| SameSite | `Strict` |
| HttpOnly | `true` |
| Secure | `is_cookies_secured()` (true under SSL) |
| Expiry | `time() + 2592000` (30 days) |

### Token Storage (`tbl_user_token`)

| Column | Protection |
|--------|------------|
| `pwd_hash` | bcrypt (cost via `finding_pwd_cost(0.05, 8)`) |
| `selector_hash` | `hash('sha256', selector)` → base64 → `password_hash` → Defuse-cipher |
| `expired_date` | Plain timestamp |

### Threat Mitigations

| Threat | Mitigation |
|--------|------------|
| Cookie theft (XSS) | HttpOnly + SameSite=Strict |
| Cookie replay | Token rotation on every auto-login |
| Stolen old cookies | Old token marked `is_expired=1` after rotation |
| Brute-force on validator | bcrypt hashing |
| Selector DB compromise | Encrypted + hashed (double layer) |
| Session hijacking | Fingerprint (IP + UA) + timeout checks |
| CSRF on logout | `logOutId` session token verified |

### Sensitive Information

| Item | Exposed? |
|------|----------|
| Username (plaintext) | Only inside encrypted `scriptlog_auth` |
| Validator/selector plaintext | Never in DB; only in HttpOnly cookies |
| Session fingerprint | In `$_SESSION`, compared per request |
| Token hashes | In DB only |

### Important Caveats to Verify

| Concern | Detail |
|---------|--------|
| `deleteUserToken` only removes `is_expired=1` | On logout, active (`is_expired=0`) rows may remain in DB — verify expected cleanup policy |
| `getTokenByLogin` returns `false` when empty | `(empty($userToken)) ?: $userToken` idiom |
| Cookie comparison of `expired_date` | String `>=` on `Y-m-d H:i:s`; timezone-sensitive |
| Logout fallback trusts `scriptlog_auth` presence | Invalid `logOutId` + any `scriptlog_auth` value → logout allowed |

---

## 14. Accessibility

### Login Form

| Element | Expected |
|---------|----------|
| Checkbox label | `label[for="remember-me"]` → "Remember Me" |
| Checkbox focus | iCheck keeps native input focusable |
| Click target | Label + checkbox both toggle |
| Keyboard | Tab → checkbox → Log In; Space toggles |

### Issues to Verify

| Issue | WCAG Criterion | Severity |
|-------|----------------|----------|
| iCheck may hide native input | 2.1.1 Keyboard, 4.1.2 Name/Role/Value | Verify focus + screen-reader label |
| No `aria-label` on checkbox | 4.1.2 | Verify |

---

## 15. Responsive Behaviour

| Breakpoint | Behaviour |
|------------|-----------|
| Desktop ≥992px | Checkbox (col-xs-8) + Log In button (col-xs-4) inline |
| Tablet 768-991px | Same grid layout |
| Mobile <768px | Grid stacks; checkbox and button full-width; touch target adequate |

---

## 16. Browser Compatibility

| Browser | Minimum Version | Notes |
|---------|-----------------|-------|
| Chrome | 90+ | SameSite Strict support |
| Firefox | 90+ | SameSite Strict support |
| Safari | 14+ | SameSite support (older: partial) |
| Edge | 90+ | Chromium-based |

### Cross-Browser Checks

| Feature | Check |
|---------|-------|
| SameSite=Strict cookies | Supported in all modern browsers |
| HttpOnly cookie attributes | `document.cookie` must NOT reveal auth cookies |
| 30-day persistence | Cookie `Max-Age` respected across browser restarts |
| Defuse encryption | Server-side only; no browser dependency |

---

## 17. Failure Scenarios

### 17.1 Login With Remember Me — Wrong Password

| Action | Expected |
|--------|----------|
| Check Remember Me, enter wrong password | Error banner; **no** cookies issued; IP attempt logged |
| Backoff | Sleep on ≥3rd attempt |

### 17.2 Auto-Login Failures

| Scenario | Expected |
|----------|----------|
| Cookie set missing a member | Login form (no auto-login attempted) |
| `scriptlog_auth` corrupt | Cookies cleared; login form |
| Token row missing | Login form; no session |
| Token expired | Token invalidated; login form |
| Validator/selector mismatch | Token invalidated + cookies cleared |
| Session fingerprint mismatch | `do_logout()` — session destroyed |

### 17.3 DB Unavailable

| Scenario | Expected |
|----------|----------|
| `tbl_user_token` read fails | Auto-login fails gracefully; login form (verify no 500) |

### 17.4 Logout Failures

| Scenario | Expected |
|----------|----------|
| Logout link without `logOutId` | 400 "URL Redirection to Untrusted Site" (unless Remember Me cookie present) |
| Logout when not logged in | 403 redirect |
| Cookie clearing on logout | All 3 cookies expired |

### 17.5 Offline

| Scenario | Expected |
|----------|----------|
| Login form submission offline | Browser error; no cookies issued |

---

## 18. Recovery Behaviour

### After Failed Auto-Login

1. Token (if found) marked `is_expired=1`
2. Auth cookies cleared from browser
3. Login form presented
4. User re-enters credentials (with Remember Me if desired)

### After Logout

1. Cookies expired/removed
2. Session destroyed
3. Next visit → login form
4. DB retains `is_expired=0` rows (see caveat in §13)

### After Token Rotation

1. Old cookies no longer valid (replay rejected)
2. New cookies + new token active for another 30 days

### After Session Timeout / Fingerprint Change

1. `do_logout()` clears session
2. If Remember Me cookies still valid → auto-login re-establishes on next request
3. If not → login form

---

## 19. Screenshots to Capture

| # | Screen | Viewport | Description |
|---|--------|----------|-------------|
| 1 | Login page — Remember Me unchecked | Desktop 1280×720 | Fresh context |
| 2 | Login page — Remember Me checked | Desktop | iCheck checked state |
| 3 | Login page — username prefilled | Desktop | From `scriptlog_auth` cookie |
| 4 | Dashboard after Remember Me login | Desktop | Authenticated |
| 5 | Developer tools — cookies | Desktop | 3 auth cookies + attributes (HttpOnly, SameSite, Expires) |
| 6 | DB query — `tbl_user_token` | Terminal | Active token row (hashes) |
| 7 | After logout — login page | Desktop | Cookies cleared |
| 8 | After token rotation — DB query | Terminal | Old `is_expired=1`, new `is_expired=0` |

---

## 20. Console & Network Expectations

### Console

| Scenario | Expected |
|----------|----------|
| Page load | No errors |
| `document.cookie` inspection | Auth cookies **not** exposed (HttpOnly) |
| Cookie corruption test | No JS errors |

### Network

| Request | Expected | Unacceptable |
|---------|----------|--------------|
| POST login (Remember Me) | 302 → dashboard | 500 |
| Auto-login request | 200 dashboard | 500, redirect to login (when cookies valid) |
| Expired-token request | Redirect to login | 200 dashboard |
| Logout | 302 → login.php | 500 |
| Corrupt-cookie request | Login page | 500 |

### Cookie Attributes (Set-Cookie response headers)

| Header | Value |
|--------|-------|
| `Path=/` | present |
| `HttpOnly` | present |
| `SameSite=Strict` | present |
| `Max-Age=2592000` | present |
| `Secure` | present under HTTPS only |

---

## 21. Test Scenarios

### 21.1 Critical Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| C01 | Remember Me login issues 3 cookies | Positive | Login with Remember Me | `scriptlog_auth`, `validator`, `selector` set; 30-day expiry |
| C02 | Auto-login after browser restart | Positive | Login w/ Remember Me → close context → reopen → dashboard | Auto-authenticated |
| C03 | Logout clears cookies + session | Positive | Logout via nav | 302 → login; 3 cookies gone; session destroyed |
| C04 | Login without Remember Me issues no cookies | Negative | Login unchecked | No `scriptlog_*` cookies |
| C05 | Corrupt `scriptlog_auth` rejected | Security | Tamper cookie → load dashboard | Cookies cleared; login form |
| C06 | Tampered validator rejected | Security | Tamper `scriptlog_validator` | Token invalidated; login form |
| C07 | Token rotation on auto-login | Security | Auto-login, capture cookie values, reload | Validator/selector values changed; old token expired |
| C08 | Replay of pre-rotation cookies | Security | Reuse captured old cookie values | Rejected; login form |

### 21.2 High Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| H01 | Expired token auto-login rejected | Negative | Set `expired_date` in past → visit admin | Login form; token marked expired; cookies cleared |
| H02 | Expired_date boundary | Boundary | `expired_date == now` | `>=` passes (verify) |
| H03 | Username prefill from cookie | Positive | Cookie present → load login | Input prefilled |
| H04 | Prefill absent without cookie | Positive | Fresh context → load login | Empty input |
| H05 | DB token row created | Data | Login w/ Remember Me → query | Row with `pwd_hash`, `selector_hash`, `expired_date` |
| H06 | Missing cookie member → no auto-login | Negative | Delete `scriptlog_selector` | Login form; no partial auth |
| H07 | Logout without `logOutId` → 400 | Negative | GET logout without token, no cookie | 400 "URL Redirection to Untrusted Site" |
| H08 | Logout fallback with Remember Me | Positive | Invalid `logOutId` + auth cookie | Logout proceeds |
| H09 | Cookies HttpOnly | Security | `document.cookie` in browser | Auth cookies absent |
| H10 | SameSite=Strict attribute | Security | Inspect Set-Cookie | `SameSite=Strict` present |

### 21.3 Medium Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| M01 | Multi-tab logout invalidation | Concurrent | 2 tabs; logout tab 1; navigate tab 2 | Login form in tab 2 |
| M02 | Remember Me pre-check | UI | Set `scriptlog_auth` cookie → load login | Checkbox checked |
| M03 | Session fingerprint mismatch → logout | Security | Change UA/IP mid-session | `do_logout()` triggered |
| M04 | Session idle timeout | Security | Simulate `last_active` older than 30 days | Logout |
| M05 | Wrong password with Remember Me | Negative | Check box + wrong password | No cookies; error banner |
| M06 | Remember Me + CAPTCHA flow | Integration | ≥5 attempts then correct login | CAPTCHA passed; cookies issued |
| M07 | Remember Me checkbox keyboard | Accessibility | Tab to checkbox, Space | Toggles; submit works |
| M08 | Cookie path `/` | Data | Inspect Set-Cookie | `Path=/` |
| M09 | Secure flag under HTTPS | Data | HTTPS env | `Secure` present; over HTTP absent |
| M10 | Logout cleans `is_expired=1` rows | Data | Logout after rotation | Expired rows removed (verify) |

### 21.4 Low Priority

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| L01 | Active (`is_expired=0`) rows after logout | Edge | Logout; query DB | Rows may persist (document behaviour) |
| L02 | Remember Me on mobile viewport | Responsive | 375×667 | Grid stacks; usable |
| L03 | Cookie expiry = 30 days exact | Boundary | Inspect Max-Age | `2592000` |
| L04 | Login by email + Remember Me | Positive | Use `admin@example.com` | Works (email token path) |
| L05 | Second Remember Me login rotates prior token | Data | Login twice with Remember Me | Only latest token active |
| L06 | Auto-login after session GC | Edge | Session expired via GC, cookies valid | Auto-login restores session |
| L07 | `scriptlog_auth` decrypt key mismatch | Security | Rotate app key → load admin | Decrypt fails; cookies cleared (verify) |
| L08 | Very long cookie value handling | Boundary | 128-char validator/selector | Accepted (createToken(128)) |

### 21.5 Edge Cases

| ID | Scenario | Type | Steps | Expected |
|----|----------|------|-------|----------|
| E01 | Remember Me with admin disabled account | Error | Ban admin user; auto-login | Login attempt fails per account status |
| E02 | Cookie `expired_date` timezone skew | Boundary | Set DB timestamp in different TZ | Compare on string equality (verify) |
| E03 | Simultaneous auto-login from 2 devices | Race | Both with same cookies | First rotation wins; second rejected |
| E04 | Empty `pwd_hash` / `selector_hash` in DB | Error | Manually null them | Verification fails → login form |
| E05 | Remember Me with special-char password | Boundary | Password `$E2E_ADMIN_PASS` | Unaffected (bcrypt) |
