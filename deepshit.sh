#!/usr/bin/env bash
#
# deepshit.sh
#
# Author: M.Noermoehammad
#
# Automates the Scriptlog Performance Profiling Plan (plan/PERFORMANCE_PROFILING_PLAN.md):
#   Phase 0  - preparation / Xdebug log cleanup (PHP 8.5 FPM profiler dir + stale 8.4 block)
#   Phase 1  - HTTP/Xdebug profiling via a PHP 8.5 FPM drop-in ini
#   Phase 2  - KCachegrind opening
#   Phase 3  - optional PHP CLI profiling / coverage (env-var driven)
#   Phase 4  - ApacheBench / siege load tests
#   Phase 5  - optional JMeter detection / guidance
#   Phase 6  - results inventory and report
#
# IMPORTANT:
# - This script does NOT modify application PHP source code.
# - It does NOT store or use the administrator password from the plan.
# - System changes require sudo (password prompt) and are backed up before modification.
# - The live site is served by PHP-FPM (currently 8.5), NOT mod_php. Web profiling therefore
#   configures the PHP-FPM pool and restarts php8.5-fpm - never Apache.
# - Load testing can create significant traffic. It requires explicit confirmation.
# - By default, the script performs conservative tests.
#
# Usage:
#   ./deepshit.sh
#   ./deepshit.sh --phase 0
#   ./deepshit.sh --phase 1
#   ./deepshit.sh --phase 2
#   ./deepshit.sh --phase 3
#   ./deepshit.sh --phase 4
#   ./deepshit.sh --phase 5
#   ./deepshit.sh --phase 6
#   ./deepshit.sh --all
#   ./deepshit.sh --restore
#   ./deepshit.sh --dry-run --all
#   ./deepshit.sh --site-url https://your-site.example --phase 1
#   ./deepshit.sh --help
#
# Optional environment overrides:
#   SITE_URL=https://your-site.example   (REQUIRED - no built-in default; an
#                                       interactive run prompts for it)
#   PROFILE_DIR=/var/log/xdebug/blog-profile
#   LOG_DIR="$HOME/blogware-performance-results"
#   MONTHLY_ARCHIVE_PATH='/?a=202607'
#   AB_REQUESTS=1000
#   AB_CONCURRENCY=10
#   SIEGE_DURATION=60s
#   SIEGE_CONCURRENCY=10
#   XD_MODE_SCRIPT=/path/to/xd-mode.sh   (OPTIONAL - unset skips the advisory)
#   PHP_FPM_VERSION=8.5     (SAPI that serves the live site)
#   PHP_LEGACY_VERSION=8.4  (stale Xdebug block cleanup target)
#   PHP_CLI_VERSION=8.5
#   PHP_ETC_DIR=/etc/php    (php.ini root; also the BATS sandbox root)
#   PROFILE_POLL_TRIES=20   (Phase 1: polls for the cachegrind file)
#   PROFILE_POLL_INTERVAL=0.1
#
# Testing:
#   tests/bats/deepshit.bats - 137 hermetic cases. Nothing in this script may touch
#   the real /etc/php, /var/log/xdebug or the host sudo: the suite redirects
#   PHP_ETC_DIR and PROFILE_DIR into a sandbox and puts command doubles for sudo,
#   curl, systemctl, php, flatpak, ab and siege first on PATH.
#
set -Eeuo pipefail
IFS=$'\n\t'

SCRIPT_NAME="$(basename "$0")"
readonly SCRIPT_NAME
readonly SCRIPT_VERSION="2.1.0"
readonly SCRIPT_AUTHOR="M.Noermoehammad"

# Target site origin. Deliberately has NO built-in default: Phase 4 generates
# real load traffic, so the operator must state the target explicitly with
# SITE_URL=... or --site-url. An interactive run prompts for it when empty.
SITE_URL="${SITE_URL:-}"
SITE_URL="${SITE_URL%/}"
PROFILE_DIR="${PROFILE_DIR:-/var/log/xdebug/blog-profile}"
LOG_DIR="${LOG_DIR:-$HOME/blogware-performance-results}"
MONTHLY_ARCHIVE_PATH="${MONTHLY_ARCHIVE_PATH:-/?a=202607}"
AB_REQUESTS="${AB_REQUESTS:-1000}"
AB_CONCURRENCY="${AB_CONCURRENCY:-10}"
SIEGE_DURATION="${SIEGE_DURATION:-60s}"
SIEGE_CONCURRENCY="${SIEGE_CONCURRENCY:-10}"

# Optional advisory helper. No default path: it lives wherever the operator put
# it. Phase 3 only REPORTS on it and never executes it, so an unset value just
# skips that advisory block.
XD_MODE_SCRIPT="${XD_MODE_SCRIPT:-}"
PROFILE_POLL_TRIES="${PROFILE_POLL_TRIES:-20}"
PROFILE_POLL_INTERVAL="${PROFILE_POLL_INTERVAL:-0.1}"

# Root of the PHP ini trees. Exists as an override (rather than a hardcoded
# /etc/php) so the FPM drop-in and legacy-ini paths can be redirected - which
# is what lets the BATS suite exercise the config-writing phases without ever
# touching the live host's PHP configuration.
PHP_ETC_DIR="${PHP_ETC_DIR:-/etc/php}"

# PHP versions from the verified environment.
PHP_FPM_VERSION="${PHP_FPM_VERSION:-8.5}"
PHP_LEGACY_VERSION="${PHP_LEGACY_VERSION:-8.4}"
PHP_CLI_VERSION="${PHP_CLI_VERSION:-8.5}"

DRY_RUN=0
ASSUME_YES=0
PHASE=""
RESTORE=0
RUN_ID="$(date '+%Y%m%d-%H%M%S')"
RUN_DIR=""
BACKUP_DIR=""
MAIN_LOG=""

declare -a CLEANUP_FILES=()

cleanup() {
    local rc=$?
    for f in "${CLEANUP_FILES[@]:-}"; do
        if [[ -n "$f" && -f "$f" ]]; then
            rm -f -- "$f"
        fi
    done
    if [[ -n "${MAIN_LOG:-}" ]]; then
        if (( rc == 0 )); then
            log_success "Finished successfully. Log: $MAIN_LOG"
        else
            log_error "Stopped with exit code $rc. Review: $MAIN_LOG"
        fi
    fi
    exit "$rc"
}
trap cleanup EXIT
trap 'log_error "Unexpected error at line $LINENO: $BASH_COMMAND"' ERR

supports_color() {
    [[ -t 1 ]] && [[ -z "${NO_COLOR:-}" ]]
}

if supports_color; then
    C_RESET=$'\033[0m'
    C_BOLD=$'\033[1m'
    C_DIM=$'\033[2m'
    C_RED=$'\033[31m'
    C_GREEN=$'\033[32m'
    C_YELLOW=$'\033[33m'
    C_BLUE=$'\033[34m'
    C_CYAN=$'\033[36m'
else
    C_RESET=""; C_BOLD=""; C_DIM=""
    C_RED=""; C_GREEN=""; C_YELLOW=""; C_BLUE=""; C_CYAN=""
fi

timestamp() { date '+%Y-%m-%d %H:%M:%S'; }

ensure_log_dir() {
    mkdir -p "$LOG_DIR"
    RUN_DIR="$LOG_DIR/$RUN_ID"
    mkdir -p "$RUN_DIR"
    BACKUP_DIR="$RUN_DIR/backups"
    mkdir -p "$BACKUP_DIR"
    MAIN_LOG="$RUN_DIR/run.log"
    touch "$MAIN_LOG"
}

log() {
    local level="$1"; shift
    local msg="$*"
    # validate_inputs() runs before ensure_log_dir() on purpose, so a rejected
    # invocation never litters the results tree. That means a validation
    # warning can arrive while MAIN_LOG is still empty, and redirecting to ""
    # would fail the command, trip the ERR trap, and abort a run that only
    # asked for a warning. Until the run log exists, report to the terminal
    # only; once it exists every line is persisted as usual.
    [[ -n "$MAIN_LOG" ]] || return 0
    printf '%s [%s] %s\n' "$(timestamp)" "$level" "$msg" >> "$MAIN_LOG" || true
}

log_info() {
    printf '%bℹ%b %s\n' "$C_CYAN" "$C_RESET" "$*"
    log INFO "$*"
}
log_success() {
    printf '%b✔%b %s\n' "$C_GREEN" "$C_RESET" "$*"
    log OK "$*"
}
log_warn() {
    printf '%b⚠%b %s\n' "$C_YELLOW" "$C_RESET" "$*"
    log WARN "$*"
}
log_error() {
    printf '%b✖%b %s\n' "$C_RED" "$C_RESET" "$*" >&2
    if [[ -n "${MAIN_LOG:-}" ]]; then
        log ERROR "$*"
    fi
    return 0
}
section() {
    printf '\n%b%s%b\n' "$C_BOLD" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" "$C_RESET"
    printf '%b%s%b\n' "$C_BOLD" "$*" "$C_RESET"
    printf '%b%s%b\n' "$C_BOLD" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" "$C_RESET"
    log INFO "SECTION: $*"
}

die() {
    log_error "$*"
    exit 1
}

usage() {
    cat <<EOF
$SCRIPT_NAME v$SCRIPT_VERSION

Automates the Scriptlog performance profiling plan.

Usage:
  $SCRIPT_NAME                  Interactive menu
  $SCRIPT_NAME --all            Run all safe phases; prompts before system/load actions
  $SCRIPT_NAME --phase N        Run phase N (0..6)
  $SCRIPT_NAME --restore        Remove the Phase 1 profiler drop-in and restart PHP-FPM
  $SCRIPT_NAME --dry-run --all  Show what would be changed/run
  $SCRIPT_NAME --yes --phase N  Skip confirmations where safe

Options:
  --phase N       Run one phase: 0,1,2,3,4,5,6
  --all           Run phases 0 through 6
  --restore       Disable web profiling (remove FPM drop-in, restart php8.5-fpm)
  --dry-run       Do not modify system or execute destructive/high-impact actions
  --yes           Accept normal confirmations
  --site-url URL  Target site to profile (required; overrides \$SITE_URL)
  --help          Show this help
  --version       Show version and author

Important:
  The live site is served by PHP $PHP_FPM_VERSION via PHP-FPM, NOT mod_php.
  Phase 0 and Phase 1 change PHP-FPM configuration and require sudo.
  Phase 4 performs load testing. Run it only against systems you are authorized to test.

Environment:
  SITE_URL=$SITE_URL
  PROFILE_DIR=$PROFILE_DIR
  LOG_DIR=$LOG_DIR
  MONTHLY_ARCHIVE_PATH=$MONTHLY_ARCHIVE_PATH
  AB_REQUESTS=$AB_REQUESTS
  AB_CONCURRENCY=$AB_CONCURRENCY
  SIEGE_DURATION=$SIEGE_DURATION
  SIEGE_CONCURRENCY=$SIEGE_CONCURRENCY
  XD_MODE_SCRIPT=$XD_MODE_SCRIPT
  PHP_FPM_VERSION=$PHP_FPM_VERSION
  PHP_LEGACY_VERSION=$PHP_LEGACY_VERSION
  PHP_CLI_VERSION=$PHP_CLI_VERSION
  PHP_ETC_DIR=$PHP_ETC_DIR
  PROFILE_POLL_TRIES=$PROFILE_POLL_TRIES
  PROFILE_POLL_INTERVAL=$PROFILE_POLL_INTERVAL
EOF
}

confirm() {
    local prompt="$1"
    (( DRY_RUN )) && { log_info "DRY-RUN: would ask: $prompt"; return 0; }
    (( ASSUME_YES )) && return 0
    read -r -p "$prompt [y/N] " answer
    [[ "$answer" =~ ^[Yy]([Ee][Ss])?$ ]]
}

run_cmd() {
    # The global IFS is newline+tab, so a bare "$*" would join the argv with
    # newlines and shred every privileged command across several lines of the
    # audit trail. Join on spaces explicitly, matching the DRY-RUN echo below.
    local rendered
    rendered="$(printf '%s ' "$@")"
    log INFO "RUN: ${rendered% }"
    if (( DRY_RUN )); then
        printf '%bDRY-RUN%b %s\n' "$C_DIM" "$C_RESET" "${rendered% }"
        return 0
    fi
    "$@" 2>&1 | tee -a "$MAIN_LOG"
    return "${PIPESTATUS[0]}"
}

run_shell() {
    log INFO "RUN: $*"
    if (( DRY_RUN )); then
        local IFS=' '
        printf '%bDRY-RUN%b %s\n' "$C_DIM" "$C_RESET" "$*"
        return 0
    fi
    bash -c "$*" 2>&1 | tee -a "$MAIN_LOG"
    return "${PIPESTATUS[0]}"
}

need_cmd() {
    command -v "$1" >/dev/null 2>&1
}

require_cmd() {
    need_cmd "$1" || die "Required command not found: $1"
}

require_sudo() {
    need_cmd sudo || die "sudo is required for this phase."
    (( DRY_RUN )) && return 0
    if ! sudo -v; then
        die "Unable to obtain sudo privileges."
    fi
}

php_cmd() {
    local version="$1"
    if command -v "php$version" >/dev/null 2>&1; then
        command -v "php$version"
    elif [[ "$version" == "$PHP_CLI_VERSION" && "$(command -v php || true)" ]]; then
        command -v php
    else
        return 1
    fi
}

fpm_service() {
    printf 'php%s-fpm' "$PHP_FPM_VERSION"
}

fpm_xdebug_dropin() {
    printf '%s/php/%s/fpm/conf.d/99-xdebug-profiler.ini' "$PHP_ETC_DIR" "$PHP_FPM_VERSION"
}

legacy_xdebug_ini() {
    printf '%s/php/%s/mods-available/xdebug.ini' "$PHP_ETC_DIR" "$PHP_LEGACY_VERSION"
}

# Highest mtime currently present in the profiler dir, as a bare epoch string
# (empty when the dir is missing or holds no cachegrind files). Used as the
# watermark that a single request must beat for its profile to be attributed
# to it.
newest_profile_mtime() {
    find "$PROFILE_DIR" -maxdepth 1 -type f -name 'cachegrind.out.*' \
        -printf '%T@\n' 2>/dev/null | sort -nr | head -1
}

# Locate the Phase 1 manifest to inline into the Phase 6 report. Phases are
# routinely run as separate invocations ($SCRIPT_NAME --phase 1, then --phase
# 6) and every invocation gets its own RUN_ID, so the manifest usually lives
# in an EARLIER run directory. Reading only $RUN_DIR made the documented
# one-phase-per-invocation workflow report "no manifest" even though Phase 1
# had just written one. Prefer this run's own manifest, else the newest one.
latest_manifest() {
    if [[ -f "$RUN_DIR/profile-manifest.tsv" ]]; then
        printf '%s' "$RUN_DIR/profile-manifest.tsv"
        return 0
    fi
    find "$LOG_DIR" -mindepth 2 -maxdepth 2 -type f -name 'profile-manifest.tsv' 2>/dev/null |
        sort | tail -1 || true
}

# Locate the load-test directory to inline into the Phase 6 report. Same
# cross-invocation problem as latest_manifest(): Phase 4 and Phase 6 are
# routinely separate runs with different RUN_IDs, so reading only $RUN_DIR
# reported "No load-test directory was generated" right after a load test.
# Prefer this run's own directory, else the newest one by mtime.
latest_load_tests_dir() {
    if [[ -d "$RUN_DIR/load-tests" ]]; then
        printf '%s' "$RUN_DIR/load-tests"
        return 0
    fi
    find "$LOG_DIR" -mindepth 2 -maxdepth 2 -type d -name 'load-tests' -printf '%T@ %p\n' 2>/dev/null |
        sort -nr | head -1 | cut -d' ' -f2- || true
}

# Newest cachegrind file whose mtime is strictly greater than the $1 watermark,
# printed as a bare file name. An empty/absent watermark means "anything counts",
# which is the correct behaviour for an empty profiler dir. Ordering is by mtime
# (authoritative) with the file name as a stable tie-break, matching the order
# Phase 2 already uses to pick the profile to open.
newest_profile_since() {
    # The watermark is the FIRST and ONLY argument. Reading $2 here aborted
    # every call under `set -u`, which silently disabled mtime attribution.
    local since="${1:-}"
    find "$PROFILE_DIR" -maxdepth 1 -type f -name 'cachegrind.out.*' \
        -printf '%T@ %f\n' 2>/dev/null |
        awk -v since="${since:-0}" 'NF && ($1 + 0) > (since + 0)' |
        sort -k1,1nr -k2,2r |
        head -1 |
        cut -d' ' -f2-
}

# Poll for the profile produced by the request just fired. Xdebug writes the
# cachegrind file at request shutdown, so the file can appear a moment after
# curl returns; polling removes the fixed-sleep race. Falls back to a filename
# set-difference when the poll finds nothing, which covers filesystems whose
# timestamps are too coarse to separate two adjacent requests.
await_profile_file() {
    local since="$1" before="$2" waited=0 newest=""

    while (( waited < PROFILE_POLL_TRIES )); do
        newest="$(newest_profile_since "$since" || true)"
        [[ -n "$newest" ]] && { printf '%s' "$newest"; return 0; }
        waited=$((waited + 1))
        sleep "$PROFILE_POLL_INTERVAL"
    done

    # Fall back to a filename set-difference. This must run even when $before
    # is empty: an empty snapshot legitimately means "every existing file is
    # new", which is precisely the first request against a fresh profiler dir.
    # Gating the fallback on a non-empty snapshot silently dropped the
    # attribution for that first request.
    local after
    after="$(find "$PROFILE_DIR" -maxdepth 1 -type f -name 'cachegrind.out.*' \
        -printf '%f\n' 2>/dev/null | sort || true)"
    comm -13 <(printf '%s\n' "$before") <(printf '%s\n' "$after") | head -1 || true
}

validate_integer() {
    [[ "$1" =~ ^[0-9]+$ ]]
}

validate_positive_integer() {
    validate_integer "$1" && (( 10#$1 > 0 ))
}

# siege accepts a bare seconds count or a <n><unit> suffix (s/m/h/d). Reject
# anything else up front so a typo fails fast instead of turning into a siege
# usage error in the middle of Phase 4.
validate_duration() {
    [[ "$1" =~ ^([1-9][0-9]*[smhd]?|[1-9][0-9]*)$ ]]
}

validate_inputs() {
    validate_positive_integer "$AB_REQUESTS" || die "AB_REQUESTS must be a positive integer."
    validate_positive_integer "$AB_CONCURRENCY" || die "AB_CONCURRENCY must be a positive integer."
    validate_positive_integer "$SIEGE_CONCURRENCY" || die "SIEGE_CONCURRENCY must be a positive integer."
    validate_positive_integer "$PROFILE_POLL_TRIES" || die "PROFILE_POLL_TRIES must be a positive integer."
    validate_duration "$SIEGE_DURATION" ||
        die "SIEGE_DURATION must be a positive number of seconds, optionally suffixed with s, m, h or d (e.g. 60s)."
    [[ -n "$SITE_URL" ]] ||
        die "No target site configured. Set SITE_URL=https://your-site.example or pass --site-url https://your-site.example."
    [[ "$SITE_URL" =~ ^https://[^[:space:]]+$ ]] ||
        log_warn "SITE_URL is not an HTTPS URL: $SITE_URL"
}

check_environment() {
    section "Environment verification"

    require_cmd bash
    require_cmd curl
    require_cmd systemctl

    log_info "Site: $SITE_URL"
    log_info "Profiler output: $PROFILE_DIR"
    log_info "Results: $RUN_DIR"
    log_info "Web SAPI: PHP $PHP_FPM_VERSION via PHP-FPM ($(fpm_service))"

    if curl -fsS --connect-timeout 5 --max-time 15 -o /dev/null "$SITE_URL/"; then
        log_success "Site is reachable."
    else
        log_warn "Site could not be reached from this machine. Profiling/load phases may fail."
    fi

    if systemctl is-active --quiet "$(fpm_service)" 2>/dev/null; then
        log_success "FPM service active: $(fpm_service)"
    else
        log_warn "FPM service not active: $(fpm_service)"
    fi

    local fpm_php
    fpm_php="$(php_cmd "$PHP_FPM_VERSION" || true)"
    [[ -n "$fpm_php" ]] && log_info "PHP $PHP_FPM_VERSION: $("$fpm_php" -v | head -1)"

    if [[ -n "$fpm_php" ]]; then
        if "$fpm_php" -m 2>/dev/null | grep -qi '^xdebug$'; then
            log_success "Xdebug loaded for PHP $PHP_FPM_VERSION."
        else
            log_warn "Xdebug not reported by PHP $PHP_FPM_VERSION CLI; verify the FPM SAPI directly."
        fi
    fi

    if need_cmd flatpak; then
        if flatpak info org.kde.kcachegrind >/dev/null 2>&1; then
            log_success "KCachegrind Flatpak is installed."
        else
            log_warn "KCachegrind Flatpak org.kde.kcachegrind is not installed."
        fi
    else
        log_warn "flatpak not found; Phase 2 cannot open KCachegrind."
    fi

    if need_cmd ab; then
        log_success "ApacheBench found: $(ab -V 2>&1 | head -1 || true)"
    else
        log_warn "ApacheBench (ab) not found."
    fi
    if need_cmd siege; then
        log_success "siege found: $(siege --version 2>&1 | head -1 || true)"
    else
        log_warn "siege not found."
    fi
}

backup_file() {
    local src="$1"
    local label="$2"
    local dst="$BACKUP_DIR/$label"

    if [[ -e "$src" ]]; then
        if (( DRY_RUN )); then
            log_info "DRY-RUN: would back up $src -> $dst"
        else
            sudo cp -a -- "$src" "$dst"
            log_success "Backup created: $dst"
        fi
    else
        log_warn "File does not exist, no backup created: $src"
    fi
}

ensure_profiler_dir() {
    if (( DRY_RUN )); then
        log_info "DRY-RUN: would create/chown $PROFILE_DIR"
        return 0
    fi
    sudo mkdir -p "$PROFILE_DIR"
    sudo chown www-data:www-data "$PROFILE_DIR"
    sudo chmod 0775 "$PROFILE_DIR"
    log_success "Profiler directory ready: $PROFILE_DIR (owner www-data, mode 0775)"
}

cleanup_stale_legacy_block() {
    local ini
    ini="$(legacy_xdebug_ini)"

    if [[ ! -f "$ini" ]]; then
        log_warn "Legacy Xdebug ini not found, nothing to clean: $ini"
        return 0
    fi

    if ! grep -qE '(Scriptlog|Blogware) performance profiling' "$ini"; then
        log_info "No stale profiler block found in $ini."
        return 0
    fi

    log_warn "Stale profiler block found in $ini (legacy plan, inert for the live SAPI)."
    if ! confirm "Remove the stale block (backed up first)?"; then
        log_info "Skipped stale-block cleanup."
        return 0
    fi

    backup_file "$ini" "xdebug.ini.php${PHP_LEGACY_VERSION}.before-cleanup"

    if (( DRY_RUN )); then
        log_info "DRY-RUN: would remove the stale profiler block from $ini"
        return 0
    fi

    sudo python3 - "$ini" <<'PY'
import sys
from pathlib import Path

path = Path(sys.argv[1])
lines = path.read_text().splitlines()
out = []
skip = False
for line in lines:
    if 'Scriptlog performance profiling' in line or 'Blogware performance profiling' in line:
        skip = True
        continue
    if skip:
        if line.startswith('xdebug.'):
            continue
        skip = False
    out.append(line)
path.write_text("\n".join(out).rstrip() + "\n")
PY
    log_success "Stale profiler block removed from $ini."
}

write_profiler_dropin() {
    local dropin
    dropin="$(fpm_xdebug_dropin)"
    local content
    content="$(
        cat <<EOF
xdebug.mode=profile
xdebug.start_with_request=trigger
xdebug.output_dir=$PROFILE_DIR
xdebug.profiler_output_name=cachegrind.out.%s.%u
EOF
    )"

    if (( DRY_RUN )); then
        log_info "DRY-RUN: would write profiler drop-in $dropin"
        return 0
    fi

    printf '%s\n' "$content" | sudo tee "$dropin" >/dev/null
    sudo chmod 0644 "$dropin"
    log_success "Profiler drop-in written: $dropin"
}

remove_profiler_dropin() {
    local dropin
    dropin="$(fpm_xdebug_dropin)"

    if (( DRY_RUN )); then
        log_info "DRY-RUN: would remove profiler drop-in $dropin"
        return 0
    fi

    if [[ -f "$dropin" ]]; then
        sudo rm -f -- "$dropin"
        log_success "Profiler drop-in removed: $dropin"
    else
        log_info "No profiler drop-in present: $dropin"
    fi
}

restart_fpm() {
    run_cmd sudo systemctl restart "$(fpm_service)"
}

phase0() {
    section "Phase 0: preparation and cleanup"

    require_sudo

    ensure_profiler_dir

    cleanup_stale_legacy_block

    if [[ -d "$PROFILE_DIR" ]]; then
        if confirm "Delete existing cachegrind files under $PROFILE_DIR?"; then
            if (( DRY_RUN )); then
                log_info "DRY-RUN: would delete cachegrind files under $PROFILE_DIR"
            else
                sudo find "$PROFILE_DIR" -maxdepth 1 -type f -name 'cachegrind.out.*' -delete
                log_success "Profiler directory cleaned."
            fi
        fi
    fi

    local fpm_php
    fpm_php="$(php_cmd "$PHP_FPM_VERSION" || true)"
    if [[ -n "$fpm_php" ]]; then
        if "$fpm_php" -m 2>/dev/null | grep -qi '^xdebug$'; then
            log_success "Xdebug loaded for PHP $PHP_FPM_VERSION."
        else
            log_warn "PHP $PHP_FPM_VERSION CLI did not report Xdebug. Verify the FPM SAPI directly."
        fi
    fi

    log_success "Phase 0 complete."
}

phase1() {
    section "Phase 1: profile live web endpoints with Xdebug (PHP $PHP_FPM_VERSION FPM)"

    require_sudo
    require_cmd curl

    write_profiler_dropin

    if confirm "Restart $(fpm_service) to activate the profiler drop-in?"; then
        restart_fpm
    else
        # The drop-in was already written, so declining leaves a half-applied
        # state: the file is present but not loaded, and the NEXT unrelated
        # FPM restart (reboot, package upgrade) would silently switch the live
        # SAPI into profile mode. Say so, and offer the exact undo.
        log_warn "Restart declined, so the drop-in is present but NOT active:"
        log_warn "  $(fpm_xdebug_dropin)"
        log_warn "It is inert until the next $(fpm_service) restart, at which point web"
        log_warn "requests will start writing profiles again. Remove it with:"
        log_warn "  sudo rm -f $(fpm_xdebug_dropin)"
        die "FPM restart declined. Phase 1 cannot proceed."
    fi

    log_info "Verifying effective FPM Xdebug settings..."
    local fpm_php settings
    fpm_php="$(php_cmd "$PHP_FPM_VERSION" || true)"
    if [[ -z "$fpm_php" ]]; then
        log_warn "No php$PHP_FPM_VERSION CLI binary found; cannot confirm effective FPM Xdebug settings."
    else
        settings="$(
            PHP_INI_SCAN_DIR="${PHP_ETC_DIR}/php/${PHP_FPM_VERSION}/fpm/conf.d" \
                "$fpm_php" -i 2>/dev/null \
                | grep -E '^xdebug\.(mode|start_with_request|output_dir|profiler_output_name)' || true
        )"
        if [[ -n "$settings" ]]; then
            log_success "Effective FPM Xdebug settings:"
            printf '%s\n' "$settings" | tee -a "$MAIN_LOG"
        else
            log_warn "Could not confirm effective FPM Xdebug settings; check the FPM SAPI manually."
        fi
    fi

    log_info "Profiling endpoints. One request per endpoint, with XDEBUG_PROFILE=1."

    # The 11 endpoints of the plan's Phase 1.2 matrix. Order matters: each row
    # is timed against its own mtime watermark, so a profile is always credited
    # to the request that produced it and not to a neighbouring one.
    declare -a endpoints=(
        "/"
        "/?p=1"
        "/?cat=1"
        "/?tag=php"
        "$MONTHLY_ARCHIVE_PATH"
        "/blog"
        "/search?q=test"
        "/?privacy"
        "/admin/login.php"
        "/?p=2"
        "/?q=php"
    )

    local manifest="$RUN_DIR/profile-manifest.tsv"
    printf 'timestamp\tendpoint\thttp_code\tprofile_file\n' > "$manifest"

    local endpoint before since newest http_code
    for endpoint in "${endpoints[@]}"; do
        log_info "Profiling: $endpoint"

        if (( DRY_RUN )); then
            log_info "DRY-RUN: curl trigger -> $SITE_URL$endpoint"
            printf '%s\t%s\t%s\t%s\n' "$(timestamp)" "$endpoint" "DRY-RUN" "-" >> "$manifest"
            continue
        fi

        # Watermark and filename snapshot taken before the request is fired.
        before="$(find "$PROFILE_DIR" -maxdepth 1 -type f -printf '%f\n' 2>/dev/null | sort || true)"
        since="$(newest_profile_mtime || true)"

        http_code="$(
            curl -ksS \
                --connect-timeout 5 \
                --max-time 30 \
                -o /dev/null \
                -w '%{http_code}' \
                -H 'Cookie: XDEBUG_PROFILE=1' \
                "$SITE_URL$endpoint" || printf '000'
        )"

        newest="$(await_profile_file "$since" "$before" || true)"

        if [[ -n "$newest" ]]; then
            log_success "$endpoint -> HTTP $http_code -> $newest"
        else
            log_warn "$endpoint -> HTTP $http_code -> no new cachegrind file detected"
        fi

        printf '%s\t%s\t%s\t%s\n' "$(timestamp)" "$endpoint" "$http_code" "${newest:--}" >> "$manifest"
    done

    log_success "Profile manifest: $manifest"
    log_success "Phase 1 complete."

    if confirm "Disable web profiling now (remove drop-in, restart $(fpm_service))? Recommended before load tests."; then
        remove_profiler_dropin
        restart_fpm
    else
        log_warn "Profiler drop-in left installed. Run '$SCRIPT_NAME --restore' when finished."
    fi
}

phase2() {
    section "Phase 2: analyze profiles with KCachegrind"

    if ! need_cmd flatpak; then
        die "flatpak is not installed."
    fi
    if ! flatpak info org.kde.kcachegrind >/dev/null 2>&1; then
        die "KCachegrind Flatpak org.kde.kcachegrind is not installed."
    fi

    local profile
    profile="$(find "$PROFILE_DIR" -maxdepth 1 -type f -name 'cachegrind.out.*' -printf '%T@ %p\n' 2>/dev/null |
        sort -nr | head -1 | cut -d' ' -f2- || true)"

    if [[ -z "$profile" ]]; then
        log_warn "No cachegrind files found in $PROFILE_DIR."
        log_info "Run Phase 1 first."
        return 0
    fi

    log_info "Newest profile: $profile"
    log_info "KCachegrind reading order: Flat Profile -> Callers/Callees -> Source/Cost."
    log_info "Sort Flat Profile by Total Self to identify CPU-heavy functions."

    if [[ -n "${DISPLAY:-}" ]]; then
        if confirm "Open the newest profile in KCachegrind?"; then
            run_cmd flatpak run org.kde.kcachegrind "$profile"
        fi
    else
        log_warn "DISPLAY is not set. GUI cannot be opened from this shell."
        log_info "Open manually with: flatpak run org.kde.kcachegrind '$profile'"
    fi

    log_success "Phase 2 complete."
}

phase3() {
    section "Phase 3: CLI profiling / coverage"

    log_info "The live web SAPI and the CLI both run PHP $PHP_CLI_VERSION."
    log_info "CLI profiling uses Xdebug environment variables (they override the ini and do"
    log_info "not depend on xd-mode.sh, whose sed cannot add a missing xdebug.mode line)."

    printf '\n  XDEBUG_MODE=profile XDEBUG_TRIGGER=1 php%s -d xdebug.output_dir=%q \\\n' \
        "$PHP_CLI_VERSION" "$PROFILE_DIR"
    printf '    -d xdebug.profiler_output_name=cachegrind.out.%%s.%%u path/to/script.php\n\n'

    printf '  XDEBUG_MODE=coverage php -d error_reporting=... lib/vendor/bin/phpunit --coverage-text\n\n'

    # xd-mode.sh is REPORTED, never executed. Its cleanup block is
    # unconditional - it runs `sudo mkdir -p /var/log/xdebug` and
    # `sudo find /var/log/xdebug -type f -mtime +1 -delete` before it looks at
    # its own argument, so even `xd-mode.sh status` escalates to root and
    # destroys profiler output on the live host. Phase 3 is documented as
    # read-only guidance, and the plan drives CLI profiling with the
    # XDEBUG_MODE/XDEBUG_TRIGGER environment variables instead, so the
    # correct behaviour here is to print the command and let the operator
    # decide.
    if [[ -z "$XD_MODE_SCRIPT" ]]; then
        log_info "XD_MODE_SCRIPT is not set, skipping the xd-mode.sh advisory."
        log_info "Set XD_MODE_SCRIPT=/path/to/xd-mode.sh if you want this phase to report on it."
    elif [[ -x "$XD_MODE_SCRIPT" ]]; then
        log_info "xd-mode.sh found: $XD_MODE_SCRIPT"
        log_warn "xd-mode.sh is NOT run by this phase: it restarts $(fpm_service), rewrites its 20-xdebug.ini,"
        log_warn "and unconditionally does 'sudo mkdir -p /var/log/xdebug' plus"
        log_warn "'sudo find /var/log/xdebug -type f -mtime +1 -delete' before it reads its argument."
        log_info "Inspect it yourself if you need its status: '$XD_MODE_SCRIPT' status"
        log_warn "Do not run it while the Phase 1 profiler drop-in is installed."
    else
        log_warn "xd-mode.sh not found or not executable: $XD_MODE_SCRIPT"
    fi

    log_info "Return the FPM SAPI to its default mode when finished: '$SCRIPT_NAME --restore'"

    log_success "Phase 3 complete."
}

phase4() {
    section "Phase 4: load testing with ApacheBench and siege"

    require_cmd curl

    log_warn "Load testing generates repeated requests. Only continue if you are authorized to test $SITE_URL."
    confirm "I am authorized to load-test this target and want to continue." ||
        { log_info "Load testing cancelled."; return 0; }

    local load_dir="$RUN_DIR/load-tests"
    mkdir -p "$load_dir"

    # Verify the target before generating load.
    if ! curl -fsS --connect-timeout 5 --max-time 15 -o /dev/null "$SITE_URL/"; then
        die "Target health check failed. Refusing to start load tests."
    fi

    if [[ -f "$(fpm_xdebug_dropin)" ]]; then
        log_warn "The Phase 1 profiler drop-in is still installed. Profiling is trigger-based so"
        log_warn "normal requests carry no profiling overhead, but consider '$SCRIPT_NAME --restore'"
        log_warn "to return the FPM SAPI to its default mode before measuring."
    fi

    if need_cmd ab; then
        local -a ab_tests=(
            "homepage|$SITE_URL/|$AB_REQUESTS|$AB_CONCURRENCY"
            "single-post|$SITE_URL/?p=1|500|$AB_CONCURRENCY"
            "search|$SITE_URL/search?q=test|500|$AB_CONCURRENCY"
            "404|$SITE_URL/?p=99999|500|$AB_CONCURRENCY"
        )

        local test name url requests concurrency outfile
        for test in "${ab_tests[@]}"; do
            IFS='|' read -r name url requests concurrency <<< "$test"
            outfile="$load_dir/ab-${name}.txt"
            log_info "ApacheBench: $name ($requests requests, $concurrency concurrent)"

            if (( DRY_RUN )); then
                log_info "DRY-RUN: ab -n $requests -c $concurrency -k $url"
            else
                if ab -n "$requests" -c "$concurrency" -k "$url" > "$outfile" 2>&1; then
                    log_success "Saved: $outfile"
                else
                    log_warn "ApacheBench returned non-zero for $name. Report retained: $outfile"
                fi
            fi
        done
    else
        log_warn "ab is not installed; skipping ApacheBench."
    fi

    if need_cmd siege; then
        local urls_file="$load_dir/siege-urls.txt"
        cat > "$urls_file" <<EOF
$SITE_URL/
$SITE_URL/?p=1
$SITE_URL/?cat=1
$SITE_URL/search?q=test
EOF

        local outfile="$load_dir/siege-mixed.txt"
        log_info "siege: ${SIEGE_CONCURRENCY} concurrent users for ${SIEGE_DURATION}"

        if (( DRY_RUN )); then
            log_info "DRY-RUN: siege -c $SIEGE_CONCURRENCY -t $SIEGE_DURATION -f $urls_file"
        else
            if siege -c "$SIEGE_CONCURRENCY" -t "$SIEGE_DURATION" -f "$urls_file" > "$outfile" 2>&1; then
                log_success "Saved: $outfile"
            else
                log_warn "siege returned non-zero. Report retained: $outfile"
            fi
        fi
    else
        log_warn "siege is not installed; skipping siege."
    fi

    log_success "Load-test results directory: $load_dir"
    log_success "Phase 4 complete."
}

phase5() {
    section "Phase 5: optional JMeter"

    if need_cmd jmeter; then
        log_success "JMeter found: $(jmeter --version 2>&1 | head -1 || true)"
        log_info "Use the official/current JMeter distribution rather than relying on an old distro package."
    elif [[ -x "./apache-jmeter/bin/jmeter" ]]; then
        log_success "Local JMeter distribution found: ./apache-jmeter/bin/jmeter"
    else
        log_info "JMeter is optional. Xdebug + Kcachegrind + ab + siege already cover the core workflow."
        log_info "If you need authenticated multi-user, CSRF-aware scenarios and HTML reports, install a current official JMeter release."
    fi

    log_success "Phase 5 complete."
}

phase6() {
    section "Phase 6: results inventory and deliverables"

    local report="$RUN_DIR/RESULTS.md"

    {
        printf '# Scriptlog Performance Run\n\n'
        printf -- "- Run ID: \`%s\`\n" "$RUN_ID"
        printf -- "- Site: \`%s\`\n" "$SITE_URL"
        printf -- "- Started: \`%s\`\n" "$(date '+%Y-%m-%d %H:%M:%S')"
        printf -- "- Web SAPI: PHP \`%s\` via PHP-FPM (\`%s\`)\n" "$PHP_FPM_VERSION" "$(fpm_service)"
        printf -- "- PHP CLI target: \`%s\`\n\n" "$PHP_CLI_VERSION"

        printf '## Profile files\n\n'
        if [[ -d "$PROFILE_DIR" ]]; then
            find "$PROFILE_DIR" -maxdepth 1 -type f -name 'cachegrind.out.*' -printf '%TY-%Tm-%Td %TH:%TM:%TS\t%f\t%s bytes\n' 2>/dev/null |
                sort -r || true
        else
            printf "Profiler directory does not exist: \`%s\`\n" "$PROFILE_DIR"
        fi

        printf '\n## Phase 1 manifest\n\n'
        local manifest_src
        manifest_src="$(latest_manifest)"
        if [[ -n "$manifest_src" ]]; then
            printf '```text\n'
            cat "$manifest_src"
            printf '```\n'
        else
            printf 'No Phase 1 manifest was generated.\n'
        fi

        printf '\n## Load-test reports\n\n'
        local load_src
        load_src="$(latest_load_tests_dir)"
        if [[ -n "$load_src" && -d "$load_src" ]]; then
            find "$load_src" -maxdepth 1 -type f -printf '%f\n' | sort || true
        else
            printf 'No load-test directory was generated.\n'
        fi

        printf '\n## Interpretation reminder\n\n'
        printf '%s\n' '- Do not infer bottlenecks from the historical April 2026 numbers; the supplied plan explicitly says those numbers are not directly comparable to the current Apache + PHP-FPM 8.5 stack.'
        printf '%s\n' '- Use KCachegrind Self/Inclusive cost to identify the actual hot functions.'
        printf '%s\n' '- Compare load-test failures separately from ApacheBench Length mismatch warnings; Scriptlog pages are byte-stable since the CSRF token fix (see report/WP-VS-SCRIPTLOG-LOAD-TESTING-ANALYSIS-REPORT.md, sections 5.4 and 9.2).'
        printf '%s\n' '- Code optimization is outside the scope of this automation.'
    } > "$report"

    log_success "Results report: $report"

    printf '\n%bArtifacts%b\n' "$C_BOLD" "$C_RESET"
    printf '  Run directory : %s\n' "$RUN_DIR"
    printf '  Main log      : %s\n' "$MAIN_LOG"
    printf '  Results       : %s\n' "$report"
    [[ -n "$(latest_manifest)" ]] && printf '  Profile map   : %s\n' "$(latest_manifest)"
    local load_dir_artifact
    load_dir_artifact="$(latest_load_tests_dir)"
    [[ -n "$load_dir_artifact" ]] && printf '  Load reports  : %s\n' "$load_dir_artifact"

    log_success "Phase 6 complete."
}

restore_profiler() {
    section "Restore: disable web profiling"

    require_sudo

    remove_profiler_dropin

    if confirm "Restart $(fpm_service) to apply the removal?"; then
        restart_fpm
    else
        log_warn "FPM restart declined; the drop-in is removed but still loaded by the running pool."
    fi

    log_success "Restore complete."
}

run_phase() {
    case "$1" in
        0) phase0 ;;
        1) phase1 ;;
        2) phase2 ;;
        3) phase3 ;;
        4) phase4 ;;
        5) phase5 ;;
        6) phase6 ;;
        *) die "Invalid phase: $1. Valid phases are 0..6." ;;
    esac
}

prompt_site_url() {
    printf 'No SITE_URL configured. Enter the site to profile (e.g. https://your-site.example).\n' >&2
    local answer=""
    if ! read -r -p "Target site URL: " answer; then
        die "No target site configured; cannot continue."
    fi
    answer="${answer%/}"
    [[ -n "$answer" ]] || die "No target site configured; cannot continue."
    SITE_URL="$answer"
}

interactive_menu() {
    while true; do
        printf '\n%bScriptlog Performance Profiler v%s%b\n' "$C_BOLD" "$SCRIPT_VERSION" "$C_RESET"
        printf '%bTarget:%b %s (PHP %s FPM)\n\n' "$C_BLUE" "$C_RESET" "$SITE_URL" "$PHP_FPM_VERSION"
        printf '  0) Preparation / cleanup\n'
        printf '  1) Profile web endpoints with Xdebug\n'
        printf '  2) Open latest profile in KCachegrind\n'
        printf '  3) CLI profiling / coverage helper\n'
        printf '  4) ApacheBench + siege load tests\n'
        printf '  5) Optional JMeter check\n'
        printf '  6) Generate results report\n'
        printf '  r) Restore (disable web profiling)\n'
        printf '  a) Run all phases\n'
        printf '  q) Quit\n\n'

        # EOF (piped/closed stdin) must behave like "quit", not like a crash:
        # read returns non-zero and set -e would otherwise abort with status 1.
        if ! read -r -p "Choose an option: " choice; then
            printf '\n'
            log_info "No further input; goodbye."
            return 0
        fi
        case "$choice" in
            0|1|2|3|4|5|6) run_phase "$choice" ;;
            r|R) restore_profiler ;;
            a|A)
                for p in 0 1 2 3 4 5 6; do
                    run_phase "$p"
                done
                ;;
            q|Q) log_info "Goodbye."; return 0 ;;
            *) log_warn "Invalid choice." ;;
        esac
    done
}

main() {
    local all=0

    while (($#)); do
        case "$1" in
            --phase)
                [[ $# -ge 2 ]] || die "--phase requires a number."
                PHASE="$2"
                shift 2
                ;;
            --all) all=1; shift ;;
            --restore) RESTORE=1; shift ;;
            --dry-run) DRY_RUN=1; shift ;;
            --yes) ASSUME_YES=1; shift ;;
            --site-url)
                [[ $# -ge 2 ]] || die "--site-url requires a URL."
                SITE_URL="${2%/}"
                shift 2
                ;;
            --help|-h) usage; exit 0 ;;
            --version|-V) printf '%s v%s by %s\n' "$SCRIPT_NAME" "$SCRIPT_VERSION" "$SCRIPT_AUTHOR"; exit 0 ;;
            *) die "Unknown option: $1. Use --help." ;;
        esac
    done

    # Only an interactive, non-phase run may prompt. A scripted invocation
    # (--phase/--all/--restore, or a closed stdin) must fail loudly rather
    # than block on a read, so validate_inputs() reports the missing URL.
    if [[ -z "$SITE_URL" && -z "$PHASE" && $all -eq 0 && $RESTORE -eq 0 ]] && [[ -t 0 ]]; then
        prompt_site_url
    fi

    validate_inputs
    ensure_log_dir

    printf '%bScriptlog Performance Automation%b\n' "$C_BOLD" "$C_RESET"
    printf 'Run ID: %s\n' "$RUN_ID"
    printf 'Results: %s\n' "$RUN_DIR"

    check_environment

    if (( RESTORE )); then
        restore_profiler
    elif (( all )); then
        for p in 0 1 2 3 4 5 6; do
            run_phase "$p"
        done
    elif [[ -n "$PHASE" ]]; then
        run_phase "$PHASE"
    else
        interactive_menu
    fi
}

main "$@"
