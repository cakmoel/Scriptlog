# PHP Runtime Hardening — CVE-2026-109618 & CVE-2026-109619

**Version:** 1.0 | **Last Updated:** November 2026

---

## 1. Why this document exists

Two critical PHP engine vulnerabilities were published in October 2026:

| Advisory | Name | Impact |
|----------|------|--------|
| `CVE-2026-109618` / `GHSA-w8hw-w9fx-j79w` | RCE via OOM corruption | Unauthenticated heap use-after-free and RCE when an out-of-memory condition interrupts request setup |
| `CVE-2026-109619` / `GHSA-6wmf-4rv5-p3rp` | RCE via stale global state | Request-owned state survives into the next request on the same worker, producing use-after-free and RCE |
| `CVE-2026-115560` (informational) | Session upload progress | Narrow path that needs an app to switch `session.serialize_handler` at runtime |

These are **PHP core** defects. Application code cannot patch them. The only complete
fix is a patched runtime; application work is limited to (a) refusing to run on an
affected build, (b) removing the documented producer/consumer paths, and (c) making a
regression visible.

---

## 2. Fixed releases

| Branch | First fixed release |
|--------|---------------------|
| 8.5 | **8.5.12** |
| 8.4 | **8.4.27** |
| 8.3 | **8.3.36** |
| 8.2 | **8.2.35** |
| 8.1 and older | End-of-life — no upstream fix |

Scriptlog keeps PHP 7.4-compatible **source** (the PHPCompatibility sniff enforces it),
but a PHP 7.4 runtime is **not** a supported production runtime: it cannot be patched
for either CVE.

---

## 3. Verify a deployment

### 3.1 Runtime versions

```bash
php -v
php8.2 -v; php8.3 -v; php8.4 -v; php8.5 -v   # installed CLI SAPIs

# Apache
apache2ctl -M | grep -i php
ls -l /etc/apache2/mods-enabled/php*.load
ls -l /etc/apache2/conf-enabled/ | grep -i php

# FPM
systemctl list-units --type=service --all | grep -i fpm
grep -RInE 'SetHandler|ProxyPassMatch|proxy:unix' /etc/apache2/conf-enabled/ /etc/apache2/sites-enabled/
```

Every handler that can serve `/var/www/blogware/public_html` must resolve to a
fixed release. A vhost with no `SetHandler` uses the loaded `libphp*.so`; a vhost
covered by an enabled `php*-fpm.conf` uses that socket.

### 3.2 Effective PHP configuration

```bash
php -r 'foreach (["memory_limit","post_max_size","upload_max_filesize",
"max_input_vars","max_input_nesting_level","session.upload_progress.enabled",
"session.serialize_handler","fatal_error_backtraces","display_errors",
"log_errors","expose_php"] as $k) printf("%-36s = %s\n", $k, var_export(ini_get($k), true));'
```

For the web SAPI, confirm the same values through `phpinfo()` on a staging host or an
FPM pool status page. The CLI values are not automatically the web values.

### 3.3 Application guard

```bash
php bin/php-security-check.php          # human-readable, exit 1 when affected
php bin/php-security-check.php --json   # machine-readable for CI/deploy gates
```

At request time the same logic runs in `lib/main.php` through
`Scriptlog\Core\PhpRuntimeSecurityGuard::report()` and writes a single
`[scriptlog][php-runtime-security]` line to the PHP error log when the runtime is
affected. Configuration warnings (upload progress, fatal backtraces, `display_errors`)
are constant per host, so they are surfaced by the CLI gate rather than logged on
every request.

Set `SCRIPTLOG_STRICT_PHP_SECURITY=1` (environment variable or constant) to make an
affected runtime fail closed instead of only logging.

---

## 4. Observed state on this host (2026-11-08)

| SAPI | Version | Verdict |
|------|---------|---------|
| CLI | 8.5.12 | Patched |
| Apache mod_php (`libphp8.4.so`) | 8.4.27 | Patched |
| `php8.5-fpm` (globally enabled handler) | 8.5.12 | Patched |
| `php8.4-fpm` | 8.4.27 | Patched |
| `php8.3-fpm` | 8.3.36 | Patched |
| `php7.4-fpm` | 7.4.33 | **EOL — affected** |

`php7.4-fpm` is running (and enabled) but no Apache vhost or enabled configuration
routes this project to its socket. It should still be stopped if no legacy site
needs it.

### 4.1 Which handler actually serves this project

Verified on 2026-11-08:

| Check | Result |
|-------|--------|
| `SetHandler` / `ProxyPassMatch` in `/etc/apache2/sites-enabled/*` | none |
| `php_admin_value` / `php_flag` in vhosts | none |
| Apache modules | `php_module` (`libphp8.4.so`) loaded, `proxy_fcgi` loaded |
| `conf-enabled/php8.5-fpm.conf` | enabled; wraps its `SetHandler "proxy:unix:/run/php/php8.5-fpm.sock"` in `<IfModule !mod_php8.c>` |
| Module source name | `./sapi/apache2handler/mod_php.c`, so the `!mod_php8.c` guard does **not** match and the FPM `SetHandler` is applied |
| CPU accounting, 50 requests | `php-fpm8.5` workers +93 ticks, Apache workers unchanged |

Conclusion: `blogware.site` is served by **php8.5-fpm 8.5.12** (patched). mod_php
8.4.27 stays loaded and is also patched, so the site is safe under either handler.
Because the Debian `php*-fpm.conf` guard keys on `mod_php8.c` while PHP 8 registers
`mod_php.c`, the FPM handler silently wins whenever `proxy_fcgi` is enabled - keep
that in mind before enabling another `php*-fpm.conf`, since the last loaded
`SetHandler` for `\.php` decides which pool serves the vhost.

Configuration review:

| Directive | Value | Notes |
|-----------|-------|-------|
| `memory_limit` | `128M` | Keep. Do **not** set `-1` while patched. |
| `post_max_size` | `8M` | Matches advisory guidance for a no-upload app. |
| `upload_max_filesize` | `2M` | Below `post_max_size`. |
| `max_input_vars` / nesting | `1000` / `64` | Bounded. |
| `session.upload_progress.enabled` | `1` | Disable (see §5). |
| `session.serialize_handler` | `php` | Default; never change it at runtime. |
| `fatal_error_backtraces` | `1` (8.5) | Disable on 8.5 (see §5). |
| `display_errors` | `On` on some SAPIs | Turn off in production. |
| `expose_php` | `Off` | No version disclosure. |

No `.user.ini` exists in the project. No project `.htaccess` sets a
diagnostic-producing `php_value`/`php_flag`; `public/files/docs/.htaccess` only sets
`php_flag engine off`, which emits no diagnostic. No FPM pool overrides INI values.

Application code review found no `session.serialize_handler` or
`session.upload_progress` usage, no `ini_set('memory_limit', ...)` in web code, and a
fixed default serialize handler. The CVE-2026-115560 precondition is therefore absent.

---

## 5. Hardening configuration

Apply these at **startup** scope (php.ini, FPM pool, or Apache `php_admin_value`).
The advisories explicitly state that `ini_set()` and FPM `.user.ini` are applied too
late for the vulnerable window.

### 5.1 php.ini (or an FPM pool / Apache `php_admin_value`)

```ini
; Defense in depth for CVE-2026-109619 (confirmed producer/consumer knobs)
session.upload_progress.enabled = 0
fatal_error_backtraces = 0        ; PHP 8.5+ only

; Never change this at runtime (CVE-2026-115560 precondition)
session.serialize_handler = php

; Production diagnostics
display_errors = Off
log_errors = On
error_reporting = E_ALL
expose_php = Off

; Request-size bounds (advisory guidance)
memory_limit = 128M
post_max_size = 8M
upload_max_filesize = 2M
max_input_vars = 1000
max_input_nesting_level = 64
```

### 5.2 Apache mod_php (vhost or `<Directory>`)

```apache
php_admin_flag display_errors off
php_admin_value session.upload_progress.enabled 0
php_admin_value fatal_error_backtraces 0
php_admin_value session.serialize_handler php
```

`php_admin_value` cannot be overridden by `.htaccess` or `ini_set()`.

### 5.3 FPM pool (`/etc/php/8.x/fpm/pool.d/www.conf`)

```ini
php_admin_flag[display_errors] = off
php_admin_value[session.upload_progress.enabled] = 0
php_admin_value[fatal_error_backtraces] = 0
php_admin_value[session.serialize_handler] = php
```

Restart every worker after any change:

```bash
systemctl restart php8.3-fpm php8.4-fpm php8.5-fpm apache2
```

### 5.4 What **not** to do

- Do **not** rely on `.user.ini` — it is applied after normal request initialization.
- Do **not** rely on `ini_set()` in application code for these directives.
- Do **not** set `memory_limit=-1` while the runtime is patched; it removes an
  availability boundary. Keep `128M` and use cgroup/container limits.
- Do **not** add per-request INI overrides that emit deprecations or warnings before
  request initialization.

---

## 6. Decommissioning PHP 7.4

PHP 7.4 has no fix for either CVE. If no site requires it:

```bash
sudo systemctl stop php7.4-fpm
sudo systemctl disable php7.4-fpm
sudo systemctl mask php7.4-fpm
```

If a legacy site does require it, isolate that site on its own host/container and
apply the advisory's interim mitigations: `memory_limit=-1` with a cgroup kill
policy, a low `post_max_size`, `session.upload_progress.enabled=0`,
`fatal_error_backtraces=0`, and `pm.max_requests=1`. Those measures reduce exposure;
they do not make the runtime safe.

---

## 7. Ongoing controls

| Control | Where |
|---------|-------|
| Runtime gate in every request | `lib/main.php` → `PhpRuntimeSecurityGuard::report()` / `enforce()` |
| Deploy/CI gate | `php bin/php-security-check.php` (exit `1` on affected runtime) |
| Unit coverage | `tests/unit/PhpRuntimeSecurityGuardTest.php` |
| Audit/remediation record | `plan/PHP_RCE_CVE_2026_109618_109619_PLAN.md` |

Treat any PHP execution timeout and any attacker-reachable fatal error as a security
signal, not only a reliability issue: both advisories use fatal interruptions as the
producer step.
