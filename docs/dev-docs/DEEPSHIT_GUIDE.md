# Deepshit Guide

How to run `deepshit.sh` on Debian or Ubuntu Linux.

Applies to `deepshit.sh` version 2.1.0 (in the repository root). The target site
is not baked into the script. Pass `--site-url https://your-site.example` or
export `SITE_URL=https://your-site.example`. The examples below use the
Blogware site.

This guide explains how to run `deepshit.sh`. The script profiles the blog site
with Xdebug, runs load tests, and saves the results in your home directory.

Everything below is taken from the script source or was verified on this host.
If a command on your machine outputs something different from this guide, trust
the command output, not this guide.

## 1. What the script does and does not do

The script does:

- Profile the live site with Xdebug by writing a trigger-based drop-in ini for
  the PHP 8.5 FPM pool (`/etc/php/8.5/fpm/conf.d/99-xdebug-profiler.ini`).
- Create and own the profiler directory `/var/log/xdebug/blog-profile` (owner
  `www-data`, mode `0775`).
- Back up and remove the stale 8.4 Xdebug block from
  `/etc/php/8.4/mods-available/xdebug.ini` (Phase 0).
- Send one trigger request per endpoint and record which `cachegrind.out.*`
  file each request produced (`profile-manifest.tsv`).
- Run ApacheBench and siege load tests (Phase 4).
- Write a run log, backups, a manifest, and a results report under
  `$HOME/blogware-performance-results/`.

The script does NOT:

- Modify any application PHP source code. Its system changes are limited to
  `/etc/php/*` and `/var/log/xdebug/*`.
- Store or use the administrator password.
- Restart Apache. Web profiling is enabled by restarting `php8.5-fpm` only.
- Touch files owned by `www-data` inside the repository.

## 2. System requirements and prerequisites

`deepshit.sh` was created and verified on Debian/Ubuntu Linux. It assumes the
Debian/Ubuntu PHP layout (`/etc/php/<version>/...`), the `www-data` user,
systemd, and the `php<version>-fpm` service name. Debian and Ubuntu are the
supported platforms. Other Linux distributions are untested: their paths, users,
and service names differ, and `PHP_ETC_DIR` plus the PHP version variables only
change the paths, not the `www-data` owner or the service name.

Run the script on the server that hosts the site, from the repository root, in a
real terminal. Phases 0 and 1 and `--restore` call `sudo` interactively, so a
background job, CI runner, or cron entry cannot answer the password prompt.

The site must be served by PHP-FPM. Apache `mod_php` is not supported, because
the script enables profiling with an FPM drop-in file and restarts the FPM
service, never Apache.

The defaults target PHP 8.5. If your site uses a different PHP version, set
`PHP_FPM_VERSION`, `PHP_LEGACY_VERSION`, and `PHP_CLI_VERSION`, then re-verify
each phase.

### 2.1 Operating system and access

| Requirement | Why it matters |
|-------------|----------------|
| Debian or Ubuntu Linux | The script uses Debian/Ubuntu paths, the `www-data` user, and the `php8.5-fpm` service name. |
| systemd | `systemctl` checks and restarts the FPM service. |
| sudo with a password | Phases 0, 1, and `--restore` write under `/etc/php` and `/var/log/xdebug`. |
| A real terminal | The sudo prompt and phase confirmations need interactive input. |
| Network access to the target | Phase 1 sends HTTP requests and Phase 4 sends load. |
| A site served by PHP-FPM | Profiling is enabled through the FPM pool, not Apache `mod_php`. |

### 2.2 Required software

| Software | Debian/Ubuntu package | Command to check | Required for |
|----------|-----------------------|------------------|--------------|
| bash | `bash` | `command -v bash` | all phases |
| curl | `curl` | `command -v curl` | all phases |
| systemctl | `systemd` | `command -v systemctl` | all phases |
| sudo | `sudo` | `sudo -v` | phases 0, 1, `--restore` |
| PHP 8.5 CLI with Xdebug 3 | `php8.5-cli`, `php8.5-xdebug` | `php8.5 -m \| grep -i xdebug` | phases 0, 1, 3 |
| PHP 8.5 FPM | `php8.5-fpm` | `systemctl is-active php8.5-fpm` | phases 0, 1 |
| Python 3 | `python3` | `command -v python3` | Phase 0 stale-block cleanup, when a stale block is found |
| ApacheBench | `apache2-utils` | `command -v ab` | Phase 4, skipped if missing |
| siege | `siege` | `command -v siege` | Phase 4, skipped if missing |

Package names can differ by release and repository. Use the command column to
check what is installed, then install the missing tool with your package manager.

Xdebug 3 is required because the drop-in uses `xdebug.mode` and
`xdebug.start_with_request`. The verified host ran Xdebug 3.5.3.

### 2.3 Optional software

| Software | Command to check | Used by |
|----------|------------------|---------|
| flatpak | `command -v flatpak` | Phase 2 |
| KCachegrind | `flatpak info org.kde.kcachegrind` | Phase 2 |
| JMeter | `command -v jmeter` or `./apache-jmeter/bin/jmeter` | Phase 5 check only |

Phase 2 needs a graphical desktop with `DISPLAY` set. Without it, the phase
prints the manual `flatpak run` command instead of opening the window.

Phase 5 is only a check. It never installs JMeter or runs a load test.

### 2.4 Sanity checks (do these first)

```bash
# Live PHP SAPI. Expect fpm-fcgi. If xdebug-check.php has been removed, skip to the next check.
curl -sk https://blogware.site/xdebug-check.php

# Xdebug present in PHP
php8.5 -m | grep -i xdebug

# PHP 8.5 FPM pool running
systemctl is-active php8.5-fpm

# Required commands
command -v bash curl systemctl sudo python3

# Phase 4 load-test tools
command -v ab siege

# Optional: Phase 2 tools
command -v flatpak && flatpak info org.kde.kcachegrind

# Profiler output dir (created by Phase 0, owned by www-data)
ls -ld /var/log/xdebug/blog-profile
```

If any of these disagree with the script defaults (listed in section 8), set the
matching environment variable instead of editing the script.

## 3. Invocations

Run from the repository root (`/var/www/blogware/public_html`):

| Command | What it does | sudo? |
|---------|--------------|-------|
| `./deepshit.sh` | Interactive menu (0-6, `r` restore, `a` all, `q` quit) | per phase |
| `./deepshit.sh --help` | Print usage | no |
| `./deepshit.sh --version` | Print version | no |
| `./deepshit.sh --phase N` | Run exactly phase 0-6 | 0, 1 |
| `./deepshit.sh --all` | Run phases 0 through 6 in order, prompting | yes |
| `./deepshit.sh --restore` | Remove the Phase 1 drop-in and restart `php8.5-fpm` | yes |
| `./deepshit.sh --dry-run --phase N` | Show what would happen; makes no changes and skips the sudo password check | no |
| `./deepshit.sh --yes --phase N` | Auto-accept `y` for all confirmations | as needed |

> `--dry-run` is the safe way to see what a phase will do. It runs read-only
> checks and prints real values, but makes no changes and does not ask for your
> sudo password.

## 4. Phase-by-phase execution

### Phase 0: preparation and cleanup (`--phase 0`, needs sudo)

What it does, in order:

1. Creates `/var/log/xdebug/blog-profile` and sets owner `www-data:www-data`,
   mode `0775`.
2. If `/etc/php/8.4/mods-available/xdebug.ini` contains a stale "Blogware
   performance profiling" block, it asks before removing it and backs it up to
   `<run>/backups/xdebug.ini.php8.4.before-cleanup`.
3. Asks whether to delete existing `cachegrind.out.*` files in the profiler
   directory.
4. Verifies Xdebug is loaded for PHP 8.5.

It does not restart anything.

### Phase 1: profile the live web site (`--phase 1`, needs sudo)

1. Writes `/etc/php/8.5/fpm/conf.d/99-xdebug-profiler.ini` with these settings:

   ```ini
   xdebug.mode=profile
   xdebug.start_with_request=trigger
   xdebug.output_dir=/var/log/xdebug/blog-profile
   xdebug.profiler_output_name=cachegrind.out.%s.%u
   ```

2. Asks to restart `php8.5-fpm` (needed to load the drop-in). The site may return
   502 or 503 for a moment during the restart. This is expected.
3. Prints the effective FPM Xdebug settings, read from the FPM conf.d scan
   directory. Expect `mode=profile`, `start_with_request=trigger`, the correct
   `output_dir`, and `profiler_output_name`.
4. Sends one trigger request per endpoint with the cookie `XDEBUG_PROFILE=1`,
   waits for a new `cachegrind.out.*` file per request, and records the mapping
   in `<run>/profile-manifest.tsv`. The 11 endpoints are hard-coded: `/`,
   `/?p=1`, `/?cat=1`, `/?tag=php`, `/?a=202607` (override with
   `MONTHLY_ARCHIVE_PATH`), `/blog`, `/search?q=test`, `/?privacy`,
   `/admin/login.php`, `/?p=2`, and `/?q=php`.
5. Asks whether to disable profiling again (remove the drop-in and restart
   `php8.5-fpm`). Saying yes is recommended. If you say no, run
   `./deepshit.sh --restore` later.

> The trigger cookie only affects the requests that carry it. Normal traffic is
> not profiled while the drop-in is installed.

### Phase 2: open a profile in KCachegrind (`--phase 2`)

Opens the newest `cachegrind.out.*` in the profiler directory with
`flatpak run org.kde.kcachegrind`. It asks for confirmation first and needs
`DISPLAY` to be set. Reading order: start with the Flat Profile (sorted by Total
Self), then Callers/Callees, then Source/Cost. Without a display it prints the
manual command instead.

### Phase 3: CLI profiling and coverage (`--phase 3`, no sudo)

Prints recommended command templates. They use Xdebug environment variables,
which work even though `xd-mode.sh` cannot add a missing `xdebug.mode` line to
the stock ini:

```bash
XDEBUG_MODE=profile XDEBUG_TRIGGER=1 php8.5 \
  -d xdebug.output_dir=/var/log/xdebug/blog-profile \
  -d xdebug.profiler_output_name=cachegrind.out.%s.%u path/to/script.php

XDEBUG_MODE=coverage php -d error_reporting='E_ALL' \
  lib/vendor/bin/phpunit --coverage-text
```

**It does not run `xd-mode.sh`.** Versions up to 2.0.0 called `xd-mode.sh status`
to display the current mode. That is unsafe, and 2.1.0 no longer does it:

- `xd-mode.sh` performs its cleanup block **before it looks at its own
  argument**, so even `xd-mode.sh status` runs `sudo mkdir -p /var/log/xdebug`
  and `sudo find /var/log/xdebug -type f -mtime +1 -delete` as root. A status
  query destroyed every profile in the profiler directory older than one day.
- It also rewrites `20-xdebug.ini` and restarts `php8.5-fpm`, which silently
  disables the Phase 1 profiler drop-in.

Phase 3 is documented as read-only, so it now only reports whether `xd-mode.sh`
is present and prints the exact command if you want to inspect it yourself.
Phase 3's own templates use `XDEBUG_MODE` and `XDEBUG_TRIGGER` environment
variables, which override the ini and do not need `xd-mode.sh` at all.

Do not run `xd-mode.sh` while the Phase 1 drop-in is installed.

### Phase 4: load testing (`--phase 4`, no sudo, prompts)

1. Asks for explicit authorization to load-test the target. Declining aborts the
   phase.
2. Health-checks `$SITE_URL/` and refuses to run if it fails.
3. Warns if the Phase 1 drop-in is still installed. Trigger-based profiling adds
   no overhead to normal requests, but restoring first is cleaner.
4. Runs four ApacheBench tests with keep-alive (`ab -k`): the homepage, a single
   post, a search, and a 404 page. The homepage uses `$AB_REQUESTS` requests at
   `$AB_CONCURRENCY` concurrency. The other three use 500 requests each. Reports
   are saved to `<run>/load-tests/ab-<name>.txt`.
5. Runs one siege mix against `/`, `/?p=1`, `/?cat=1`, and `/search?q=test`, and
   saves the report to `<run>/load-tests/siege-mixed.txt`.

> `ab` prints a "Length mismatch" line when a response body differs in length
> from the first response. Dynamic pages can do this. Judge real failures by the
> `Failed requests` count and the non-2xx count, not by a Length mismatch line
> alone.

### Phase 5: optional JMeter check (`--phase 5`, no sudo)

Checks whether JMeter is installed (system-wide or at
`./apache-jmeter/bin/jmeter`). No JMeter is installed, so this phase is
informational.

### Phase 6: results inventory (`--phase 6`, no sudo)

Writes `<run>/RESULTS.md` with the profiler files, the Phase 1 manifest, and the
load-test reports. No analysis is done. It is an inventory.

### `--restore`: disable web profiling (needs sudo)

Removes `/etc/php/8.5/fpm/conf.d/99-xdebug-profiler.ini` and asks to restart
`php8.5-fpm`. Run this any time after Phase 1 if you declined the end-of-phase
restore prompt.

## 5. Outputs and where they live

Every run creates `$HOME/blogware-performance-results/<RUN_ID>/` (for example
`20260815-210220`):

| Path | Created by | Contents |
|------|-----------|----------|
| `run.log` | every run | full transcript, including every executed command |
| `backups/` | Phase 0 (stale-block cleanup) | copies of the 8.4 ini before modification |
| `profile-manifest.tsv` | Phase 1 | timestamp, endpoint, http_code, cachegrind file |
| `load-tests/` | Phase 4 | `ab-*.txt`, `siege-urls.txt`, `siege-mixed.txt` |
| `RESULTS.md` | Phase 6 | inventory of the above |

Profiler output itself lives in
`/var/log/xdebug/blog-profile/cachegrind.out.index.php.<usec>` (one file per
trigger request). The name says `index.php` because all frontend endpoints route
through it.

## 6. Verifying each phase actually worked

After Phase 1, confirm in the output:

- The "Effective FPM Xdebug settings" block shows `mode=profile`,
  `start_with_request=trigger`, `output_dir=/var/log/xdebug/blog-profile`, and
  `profiler_output_name=cachegrind.out.%s.%u`.
- Every endpoint row ends with `HTTP 200` followed by a
  `cachegrind.out.index.php.<usec>` file (or `HTTP 404` for `/?p=2`).
  `HTTP 000` means the request itself failed.
- `profile-manifest.tsv` has 11 rows plus the header.

After Phase 4, check the `ab` report for `Requests per second`, `Time per
request`, `Failed requests`, `Non-2xx responses`, and the `Percentage served
within` lines. Compare `siege`'s `Availability`, `Response time`, and `Failed
transactions`.

## 7. Troubleshooting

| Symptom | Cause or check | Fix |
|---------|----------------|-----|
| `sudo: a password is required` | run in a non-interactive shell | run the phase in a terminal that can answer the password prompt |
| `no new cachegrind file detected` for an endpoint | trigger not reaching FPM, or the drop-in not loaded | re-run Phase 1 and confirm the "Effective FPM Xdebug settings" block; `ls -ld /var/log/xdebug/blog-profile` must show `www-data`; inspect `run.log` |
| profile files land in `/tmp` instead of the profiler directory | the drop-in's `output_dir` was not applied | re-run Phase 1 (it rewrites the drop-in and restarts FPM) |
| Phase 2: "DISPLAY is not set" | GUI not reachable from the shell | open manually with the printed `flatpak run ...` command |
| `php8.5-fpm` fails to restart | ini syntax error (very unlikely; drop-in is fixed content) | `./deepshit.sh --restore`, then `systemctl status php8.5-fpm` |
| `xd-mode.sh` unexpectedly changed FPM config | it rewrites `20-xdebug.ini` and restarts FPM | run it only after `--restore`; then `./deepshit.sh --restore` again if needed |
| profiler directory owned by `root` | Phase 0 was skipped | run `./deepshit.sh --phase 0` (it changes ownership to `www-data`) |
| `/?a=202607` returns HTTP 000 | archive empty or path invalid | override with `MONTHLY_ARCHIVE_PATH=/` or a real archive, for example `MONTHLY_ARCHIVE_PATH='/?a=202607'` (default) |

## 8. Environment overrides

Set these before running to change the defaults (values shown are the script
defaults). Run `./deepshit.sh --help` to print the values that are effective on
your machine.

The Default column shows the value the script uses when you do not set the
variable. `no default` means the script has nothing to fall back on. `required`
means the run cannot continue without a value. `optional` means the run continues
without it.

| Variable | Default | Meaning |
|----------|---------|---------|
| `SITE_URL` | **no default, required** | target site address. You must supply it, because Phase 4 generates real load traffic. Use `SITE_URL=https://your-site.example ./deepshit.sh` or `./deepshit.sh --site-url https://your-site.example`. An interactive run prompts for it; a scripted run with neither fails immediately. A trailing `/` is stripped. |
| `PROFILE_DIR` | `/var/log/xdebug/blog-profile` | Xdebug output directory |
| `LOG_DIR` | `$HOME/blogware-performance-results` | results root |
| `MONTHLY_ARCHIVE_PATH` | `/?a=202607` | monthly archive endpoint in the Phase 1 matrix |
| `AB_REQUESTS` | `1000` | homepage `ab -n` |
| `AB_CONCURRENCY` | `10` | `ab -c` for all tests |
| `SIEGE_DURATION` | `60s` | siege run duration |
| `SIEGE_CONCURRENCY` | `10` | siege concurrency |
| `XD_MODE_SCRIPT` | **no default, optional** | helper **reported but never executed** by Phase 3 (see Phase 3 in section 4). Leaving it unset means Phase 3 skips that advisory; set it to `/path/to/xd-mode.sh` to have it reported. |
| `PHP_FPM_VERSION` | `8.5` | FPM SAPI that serves the site (do not change without re-verifying) |
| `PHP_LEGACY_VERSION` | `8.4` | SAPI whose stale Xdebug block Phase 0 cleans |
| `PHP_CLI_VERSION` | `8.5` | CLI PHP used by Phase 3 examples |
| `PHP_ETC_DIR` | `/etc/php` | root of the PHP config tree; overridden only by the Bats suite |
| `PROFILE_POLL_TRIES` | `20` | how many times Phase 1 polls for a request's cachegrind file |
| `PROFILE_POLL_INTERVAL` | `0.1` | seconds between those polls (about 2 seconds total by default) |

Example:

```bash
AB_REQUESTS=200 AB_CONCURRENCY=5 SIEGE_DURATION=30s ./deepshit.sh --phase 4
```

## 9. Recommended end-to-end sequence

The script has no default target site. A scripted command, meaning any command
with `--phase`, `--all`, or `--restore`, stops with this message when the target
is missing:

```text
✖ No target site configured. Set SITE_URL=https://your-site.example or pass --site-url https://your-site.example.
```

Only an interactive run with no phase arguments prompts for the site. The
commands in this sequence are scripted, so set the target first. The sequence
starts with that export. If you do not want to export it, add
`--site-url https://your-site.example` to each command instead. A trailing
slash is stripped by the script.

Quick check. Run these three commands before the full sequence. They prove that
the target is set and that Phase 0 works in preview mode.

| Command | What you should see |
|---------|---------------------|
| `./deepshit.sh --dry-run --phase 0` | Exit 1 and the message above. This is expected when no target is set. |
| `SITE_URL=https://your-site.example ./deepshit.sh --dry-run --phase 0` | Exit 0, and a line `Site: https://your-site.example`. |
| `./deepshit.sh --site-url https://your-site.example/ --dry-run --phase 0` | Exit 0, and the same `Site:` line with the trailing slash removed. |

A `--dry-run` command does not need the site to be reachable. If the site cannot
be reached, the output adds `Site could not be reached from this machine` and
still exits 0. That is fine for a preview. Phases 1 and 4 need a reachable site.

| Option or setting | What it does |
|-------------------|--------------|
| `SITE_URL` (environment variable) | Target site. Required for a scripted run unless you pass `--site-url`. |
| `--site-url URL` | Target site for this command. Overrides `SITE_URL`. |
| `--help` | Print the full usage text and exit. |
| `--version` | Print the version and author, then exit. |
| `--dry-run` | Preview the phase. Make no changes and skip the sudo password check. |
| `--phase N` | Run exactly one phase. N is 0, 1, 2, 3, 4, 5 or 6. |
| `--all` | Run phases 0 to 6 in order, pausing at each confirmation prompt. |
| `--restore` | Remove the Phase 1 profiler drop-in, then ask before restarting `php8.5-fpm`. |
| `--yes` | Accept normal confirmation prompts without typing `y`. |

```bash
cd /var/www/blogware/public_html

# 0. Set the target once for this terminal.
export SITE_URL=https://your-site.example

# 1. --dry-run --phase 0
#    Preview Phase 0. No files are changed and no sudo password is requested.
./deepshit.sh --dry-run --phase 0

# 2. --phase 0
#    Prepare: create the profiler directory, clean the stale 8.4 block,
#    optionally delete old profiles, and check that Xdebug is loaded.
./deepshit.sh --phase 0

# 3. --phase 1
#    Profile the 11 endpoints. Say "yes" to the end-of-phase restore prompt.
./deepshit.sh --phase 1

# 4. --phase 2
#    Open the newest profile in KCachegrind. Needs a graphical desktop.
./deepshit.sh --phase 2

# 5. --phase 4
#    Run the ApacheBench and siege load tests. Asks for authorization first.
./deepshit.sh --phase 4

# 6. --phase 6
#    Write RESULTS.md with the profile list, manifest, and load-test reports.
./deepshit.sh --phase 6

# --all
#    Run phases 0 to 6 in one invocation. Answer the prompts interactively.
./deepshit.sh --all
```

If you want to run one command without exporting `SITE_URL`, put the variable
in front of that command:

```bash
SITE_URL=https://your-site.example ./deepshit.sh --dry-run --phase 0
```

Or pass the option:

```bash
./deepshit.sh --site-url https://your-site.example --dry-run --phase 0
```

To accept normal confirmation prompts automatically, add `--yes` to a command.
Only add `--yes` when you have read what the phase does.

Phase 3 is optional and read-only. Run `./deepshit.sh --phase 3` to print the
CLI profiling and coverage commands. Phase 5 only checks whether JMeter is
installed. Run `./deepshit.sh --phase 5` if you need that check.

If a phase was interrupted, run `./deepshit.sh --restore`. It removes the
profiler drop-in and asks before restarting PHP-FPM. Answer yes so the running
pool stops loading the drop-in.

## 10. After profiling

1. Copy the relevant `cachegrind.out.*` files and the `profile-manifest.tsv`
   mapping.
2. Open `docs/dev-docs/DEEPSHIT_REPORT_TABLE.md` and fill in one row per endpoint
   in the Phase 6 results table. Use `profile-manifest.tsv` for the HTTP code and
   cachegrind file name, KCachegrind for the self and inclusive percentages, and
   the `ab-*.txt` reports for the load-test columns.
3. Identify hot functions by `Total Self` cost in KCachegrind.
4. Record the main findings under the table in
   `docs/dev-docs/DEEPSHIT_REPORT_TABLE.md`.
5. Code optimization (page cache, settings caching, query rewrites) is a separate
   follow-up plan. This workflow only measures.
