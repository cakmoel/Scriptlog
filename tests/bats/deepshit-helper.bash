#!/usr/bin/env bash
#
# Shared setup for the deepshit.sh BATS suite.
#
# deepshit.sh (ex-blogware-performance.sh) automates the six profiling phases
# of plan/PERFORMANCE_PROFILING_PLAN.md. It runs `main "$@"` unconditionally at
# the bottom with no guard, so - like load-test.sh - every test drives it
# black-box as a real process rather than sourcing it.
#
# The script touches three things that must never happen during a test run:
#   1. the live PHP-FPM config under /etc/php (drop-in write/remove),
#   2. the real init system (systemctl restart php8.5-fpm),
#   3. the live site at SITE_URL (11 profiling requests + 4x ab + siege).
#
# Hermeticity is achieved in two layers:
#
#   * deepshit.sh honours PHP_ETC_DIR / PROFILE_DIR / LOG_DIR / SITE_URL, so
#     every path and URL is redirected into $BATS_TEST_TMPDIR. ds_setup
#     REFUSES to run if any of them still points outside the sandbox, which
#     turns a helper bug into a loud failure instead of a surprise reboot.
#   * Deterministic doubles for sudo, systemctl, curl, ab, siege, flatpak and
#     jmeter are prepended to PATH. The sudo double records every argv and then
#     delegates only an allowlist of harmless commands; it never gains
#     privilege, so a mistake in the sandbox wiring cannot reach the host.
#
# Artifacts tests assert on: exit status, merged stdout+stderr ($output), the
# exact sudo/systemctl/curl/ab/siege argv logs, the run directory (run.log,
# profile-manifest.tsv, RESULTS.md, backups/, load-tests/), and the mocked
# /etc/php and profiler trees under the sandbox.

# Absolute path to the tool under test (repo-relative to this helper).
DEEPSHIT_SCRIPT="${BATS_TEST_DIRNAME}/../../deepshit.sh"

# Fails fast rather than letting a mis-wired sandbox reach the live host.
_ds_assert_sandboxed() {
    local name="$1" value="$2"
    case "$value" in
        "${BATS_TEST_TMPDIR}"/*) : ;;
        *) printf 'deepshit-helper: %s must live under BATS_TEST_TMPDIR, got: %s\n' \
               "$name" "$value" >&2
           return 1 ;;
    esac
}

ds_setup() {
    export LC_ALL=C
    export NO_COLOR=1

    DS_REAL_PATH="$PATH"

    # ------------------------------------------------------------------
    # Sandbox layout. Everything the script may read or write lives here.
    # ------------------------------------------------------------------
    DS_SANDBOX="${BATS_TEST_TMPDIR}/sandbox"
    DS_ETC="${DS_SANDBOX}/etc"
    DS_PROFILE="${DS_SANDBOX}/var-log-xdebug"
    DS_LOG="${DS_SANDBOX}/home-results"

    # PHP version numbers are fictional on purpose: the resulting paths only
    # exist inside the sandbox, so a missed redirection fails loudly.
    mkdir -p "${DS_ETC}/php/8.5/fpm/conf.d"
    mkdir -p "${DS_ETC}/php/8.4/mods-available"
    mkdir -p "${DS_PROFILE}" "${DS_LOG}"

    export SITE_URL="https://blogware.invalid"
    export PHP_ETC_DIR="${DS_ETC}"
    export PROFILE_DIR="${DS_PROFILE}"
    export LOG_DIR="${DS_LOG}"
    export PHP_FPM_VERSION="8.5"
    export PHP_LEGACY_VERSION="8.4"
    export PHP_CLI_VERSION="8.5"
    export MONTHLY_ARCHIVE_PATH="/?a=202607"

    # Keep the profile poll snappy: the default is 20 x 0.1s.
    export PROFILE_POLL_TRIES="3"
    export PROFILE_POLL_INTERVAL="0.01"

    # php8.5 is resolved through php_cmd(); the sandbox has no php8.5 binary,
    # so export a PATH shim (see _ds_install_php_mock) that reports a fixed
    # version and module list, including the effective xdebug.* settings the
    # script greps out of `php -i`.
    DS_PHP_FPM_VERSION="8.5.11"
    DS_PHP_MODULES=$'PDO\npdo_mysql\nxdebug\n'

    _ds_assert_sandboxed PROFILE_DIR "$PROFILE_DIR"
    _ds_assert_sandboxed LOG_DIR "$LOG_DIR"
    _ds_assert_sandboxed PHP_ETC_DIR "$PHP_ETC_DIR"

    # ------------------------------------------------------------------
    # Per-test state files, truncated so no test can inherit another's calls.
    # ------------------------------------------------------------------
    DS_MOCK_BIN="${BATS_TEST_TMPDIR}/mockbin"
    mkdir -p "${DS_MOCK_BIN}"

    DS_SUDO_LOG="${BATS_TEST_TMPDIR}/sudo.log"
    DS_SYSTEMCTL_LOG="${BATS_TEST_TMPDIR}/systemctl.log"
    DS_CURL_LOG="${BATS_TEST_TMPDIR}/curl.log"
    DS_AB_LOG="${BATS_TEST_TMPDIR}/ab.log"
    DS_SIEGE_LOG="${BATS_TEST_TMPDIR}/siege.log"
    : > "${DS_SUDO_LOG}"
    : > "${DS_SYSTEMCTL_LOG}"
    : > "${DS_CURL_LOG}"
    : > "${DS_AB_LOG}"
    : > "${DS_SIEGE_LOG}"
    export DS_SUDO_LOG DS_SYSTEMCTL_LOG DS_CURL_LOG DS_AB_LOG DS_SIEGE_LOG

    # Behaviour switches. Each is unset so a test only ever sees what it set.
    #
    #   DS_SUDO_V_RC      exit status of `sudo -v` (default 0 = authorised)
    #   DS_CURL_STATUS    status code the curl double prints (default 200)
    #   DS_CURL_FAIL      URL substring that makes curl fail at transport level
    #   DS_CURL_NO_PROFILE=1  do not write a cachegrind file (Xdebug absent)
    #   DS_CURL_PROFILE_DELAY  seconds to wait before writing the profile file
    #   DS_AB_RC / DS_SIEGE_RC   non-zero exit for the load-test doubles
    #   DS_FLATPAK_PRESENT=0    pretend the kcachegrind Flatpak is not installed
    #   DS_JMETER_PRESENT=1     pretend `jmeter` is on PATH
    #   DS_SYSTEMCTL_ACTIVE=0    pretend the FPM service is down
    unset DS_SUDO_V_RC DS_CURL_STATUS DS_CURL_FAIL DS_CURL_NO_PROFILE \
          DS_CURL_PROFILE_DELAY DS_AB_RC DS_SIEGE_RC DS_FLATPAK_PRESENT \
          DS_JMETER_PRESENT DS_SYSTEMCTL_ACTIVE DS_SANDBOX_E2E

    _ds_install_sudo_mock
    _ds_install_systemctl_mock
    _ds_install_curl_mock
    _ds_install_ab_mock
    _ds_install_siege_mock
    _ds_install_flatpak_mock
    _ds_install_php_mock

    PATH="${DS_MOCK_BIN}:${PATH}"
    export PATH
}

# -------------------------------------------------------------------------
# sudo double
#
# Never elevates. Records `SUDO <argv>` then, for an allowlist of commands
# whose arguments are all inside the sandbox, execs them so the real side
# effect happens against the fixture. chown/chmod are deliberately recorded
# but NOT executed: running them on the sandbox would strip the test user's
# write access and break every later test in the same run.
# -------------------------------------------------------------------------
_ds_install_sudo_mock() {
    cat > "${DS_MOCK_BIN}/sudo" <<'SUDO_MOCK_EOF'
#!/usr/bin/env bash
# Mock sudo for the deepshit.sh BATS suite: logs argv, never elevates.
set -u

log="${DS_SUDO_LOG:-/dev/null}"
printf 'SUDO %s\n' "$*" >> "$log"

args=("$@")
if [[ "${args[0]:-}" == "-v" || "${args[0]:-}" == "-n" ]]; then
    exit "${DS_SUDO_V_RC:-0}"
fi
if [[ "${#args[@]}" -eq 0 ]]; then
    exit 0
fi

# Commands safe to run for real because every path they receive has been
# redirected into the sandbox by ds_setup.
case "${args[0]}" in
    systemctl|find|python3|tee|cp|mkdir|rm|chmod)
        exec "${args[@]}"
        ;;
    chown)
        # Ownership changes are simulated, never applied.
        printf 'CHOWN-SIMULATED %s\n' "$*" >> "$log"
        exit 0
        ;;
esac

# Anything else: recorded, treated as a no-op success.
exit 0
SUDO_MOCK_EOF
    chmod +x "${DS_MOCK_BIN}/sudo"
}

_ds_install_systemctl_mock() {
    cat > "${DS_MOCK_BIN}/systemctl" <<'SYSTEMCTL_MOCK_EOF'
#!/usr/bin/env bash
# Mock systemctl. `is-active` honours DS_SYSTEMCTL_ACTIVE so the
# "FPM service not active" warning branch is reachable.
set -u
printf 'SYSTEMCTL %s\n' "$*" >> "${DS_SYSTEMCTL_LOG:-/dev/null}"

if [[ "${1:-}" == "is-active" ]]; then
    [[ "${DS_SYSTEMCTL_ACTIVE:-1}" == "1" ]] && exit 0
    exit 3
fi
exit 0
SYSTEMCTL_MOCK_EOF
    chmod +x "${DS_MOCK_BIN}/systemctl"
}

# -------------------------------------------------------------------------
# curl double
#
# Two jobs. On a plain GET (no -w) it just answers the reachability probe.
# On the Phase 1 trigger request it ALSO writes a cachegrind file into
# PROFILE_DIR, exactly as Xdebug would, using %s = script basename so
# /admin/login.php produces a name that sorts BEFORE the index.php ones.
# That ordering is deliberate: it is the case the alphabetical set-difference
# got wrong, so the mtime watermark is what makes attribution correct.
#
#   DS_CURL_STATUS=404    -> %{http_code} substitution returns 404
#   DS_CURL_FAIL=nomatch  -> URL contains it: exit 22 with empty output
#   DS_CURL_NO_PROFILE=1  -> do not write a cachegrind file
#   DS_CURL_PROFILE_DELAY -> sleep N seconds before writing the file
# -------------------------------------------------------------------------
_ds_install_curl_mock() {
    cat > "${DS_MOCK_BIN}/curl" <<'CURL_MOCK_EOF'
#!/usr/bin/env bash
# Mock curl for the deepshit.sh BATS suite. See helper header.
set -u

url="${!#}"
printf 'CURL %s\n' "$url" >> "${DS_CURL_LOG:-/dev/null}"

if [[ -n "${DS_CURL_FAIL:-}" && "$url" == *"${DS_CURL_FAIL}"* ]]; then
    printf 'curl: (22) mock transport failure\n' >&2
    exit 22
fi

status="${DS_CURL_STATUS:-200}"
is_trigger=0
for arg in "$@"; do
    # The script passes -H 'Cookie: XDEBUG_PROFILE=1', so match the substring.
    if [[ "$arg" == *"XDEBUG_PROFILE=1"* ]]; then
        is_trigger=1
    fi
done

if (( is_trigger )) && [[ "${DS_CURL_NO_PROFILE:-0}" != "1" ]]; then
    case "$url" in
        *admin/login.php*) stem="cachegrind.out.admin_login.php" ;;
        *)                 stem="cachegrind.out.index.php" ;;
    esac
    # Monotonic counter keeps every file distinct even within one second.
    seq_file="${DS_CURL_LOG:-/dev/null}.seq"
    n=$(( $(cat "$seq_file" 2>/dev/null || printf 0) + 1 ))
    printf '%s' "$n" > "$seq_file"
    [[ -n "${DS_CURL_PROFILE_DELAY:-}" ]] && sleep "$DS_CURL_PROFILE_DELAY"
    printf 'mock cachegrind payload for %s\n' "$url" \
        > "${PROFILE_DIR}/${stem}.000000000${n}"
fi

printf '%s' "$status"
exit 0
CURL_MOCK_EOF
    chmod +x "${DS_MOCK_BIN}/curl"
}

_ds_install_ab_mock() {
    cat > "${DS_MOCK_BIN}/ab" <<'AB_MOCK_EOF'
#!/usr/bin/env bash
# Mock ApacheBench. Logs exact argv, then emits a fixed report.
set -u
printf 'ab %s\n' "$*" >> "${DS_AB_LOG:-/dev/null}"

if [[ "${1:-}" == "-V" ]]; then
    printf '%s\n' 'ApacheBench/2.3 <$Revision: 1903618 $> (mock for deepshit BATS suite)'
    exit 0
fi

printf '%s\n' "mock ab report for $*" \
    "Complete requests:      1000" \
    "Failed requests:        0" \
    "Requests per second:    1234.56 [#/sec] (mean)"
exit "${DS_AB_RC:-0}"
AB_MOCK_EOF
    chmod +x "${DS_MOCK_BIN}/ab"
}

_ds_install_siege_mock() {
    cat > "${DS_MOCK_BIN}/siege" <<'SIEGE_MOCK_EOF'
#!/usr/bin/env bash
# Mock siege. Logs exact argv and echoes back the -f url list it was given.
set -u
printf 'siege %s\n' "$*" >> "${DS_SIEGE_LOG:-/dev/null}"

if [[ "${1:-}" == "--version" ]]; then
    printf '%s\n' 'SIEGE 4.0.7 (mock for deepshit BATS suite)'
    exit 0
fi

if [[ "$*" == *"-f "* ]]; then
    list="${*##*-f }"
    list="${list%% *}"
    printf 'siege url list: %s\n' "$(tr '\n' ' ' < "$list")"
    printf '%s\n' 'Transactions: 100' 'Availability: 100.00 %'
fi
exit "${DS_SIEGE_RC:-0}"
SIEGE_MOCK_EOF
    chmod +x "${DS_MOCK_BIN}/siege"
}

_ds_install_flatpak_mock() {
    cat > "${DS_MOCK_BIN}/flatpak" <<'FLATPAK_MOCK_EOF'
#!/usr/bin/env bash
# Mock flatpak. `info` reports the kcachegrind app as installed unless
# DS_FLATPAK_PRESENT=0, which exercises the Phase 2 guard clauses.
set -u
if [[ "${1:-}" == "info" ]]; then
    [[ "${DS_FLATPAK_PRESENT:-1}" == "1" ]] && exit 0
    printf 'error: No such ref\n' >&2
    exit 1
fi
printf 'FLATPAK %s\n' "$*"
exit 0
FLATPAK_MOCK_EOF
    chmod +x "${DS_MOCK_BIN}/flatpak"
}

# php_cmd() looks for php8.5 first and falls back to plain php. The shim
# reports a fixed version/module list and, for -i, the four xdebug settings
# Phase 1 greps for - so both the "settings confirmed" and the
# "no php binary" branches are deterministic.
_ds_install_php_mock() {
    cat > "${DS_MOCK_BIN}/php8.5" <<PHP_MOCK_EOF
#!/usr/bin/env bash
set -u
case "\${1:-}" in
    -v)
        printf 'PHP %s (cli) (mock for deepshit BATS suite)\n' "${DS_PHP_FPM_VERSION}"
        exit 0
        ;;
    -m)
        printf '%s\n' '${DS_PHP_MODULES}'
        exit 0
        ;;
    -i)
        printf 'xdebug.mode => profile => profile\n'
        printf 'xdebug.start_with_request => trigger => trigger\n'
        printf 'xdebug.output_dir => %s => %s\n' "\${PROFILE_DIR}" "\${PROFILE_DIR}"
        printf 'xdebug.profiler_output_name => cachegrind.out.%%s.%%u => cachegrind.out.%%s.%%u\n'
        exit 0
        ;;
esac
printf 'PHP %s (cli) (mock for deepshit BATS suite)\n' "${DS_PHP_FPM_VERSION}"
exit 0
PHP_MOCK_EOF
    chmod +x "${DS_MOCK_BIN}/php8.5"
}

# Remove the php8.5 shim so php_cmd() has nothing to resolve. Phase 1 must
# then warn instead of silently reporting nothing.
ds_hide_php() {
    rm -f "${DS_MOCK_BIN}/php8.5" "${DS_MOCK_BIN}/php"
    # The host really does have a `php` on PATH, and php_cmd() falls back to
    # it whenever the requested version equals $PHP_CLI_VERSION. Strip both
    # the versioned and the bare binary so the "no PHP for this SAPI" path is
    # actually reachable. Keep the mock bin first: sudo/curl/systemctl live
    # there, and dropping them would make the run fail for the wrong reason.
    PATH="${DS_MOCK_BIN}:$(ds_stripped_path php php8.5)"
    export PATH
}

# Seed a stale profiler file from "a previous run". The mtime is forced into
# the past so a test can prove the watermark logic ignores it even though its
# name sorts LAST alphabetically - the exact case the old code mis-attributed.
ds_seed_stale_profile() {
    local name="${1:-cachegrind.out.index.php.0000000000}"
    printf 'stale\n' > "${PROFILE_DIR}/${name}"
    touch -d '2001-01-01 00:00:00' "${PROFILE_DIR}/${name}"
}

# Run the tool under test. Extra flags are appended after the canonical
# sandbox env, so a test can override SITE_URL, DRY_RUN, etc.
ds_run() {
    run "$DEEPSHIT_SCRIPT" "$@"
}

# The single run directory produced by the most recent ds_run, if any.
# Ordered by mtime, not by name: RUN_ID has one-second resolution, so a fast
# phase pair can land in the same directory while a slower pair straddles a
# second boundary. Sorting by name then picking the last entry returned the
# WRONG run whenever a test's final phase did not sort last by name.
ds_run_dir() {
    local newest
    newest="$(find "${DS_LOG}" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' 2>/dev/null |
        sort -nr | head -1 | cut -d' ' -f2-)"
    printf '%s' "$newest"
}

# The TSV manifest Phase 1 writes, with the timestamp column dropped so
# assertions are stable.
ds_manifest_rows() {
    local dir
    dir="$(ds_run_dir)"
    [[ -n "$dir" && -f "${dir}/profile-manifest.tsv" ]] || return 1
    awk -F'\t' 'NR > 1 { print $2 "\t" $3 "\t" $4 }' "${dir}/profile-manifest.tsv"
}

# The endpoint column of the manifest, in order.
ds_manifest_endpoints() {
    ds_manifest_rows | cut -f1
}

# endpoint -> profile file name credited to it by the manifest.
ds_manifest_file_for() {
    ds_manifest_rows | awk -F'\t' -v want="$1" '$1 == want { print $3; exit }'
}

# endpoint -> HTTP status recorded in the manifest.
ds_manifest_code_for() {
    ds_manifest_rows | awk -F'\t' -v want="$1" '$1 == want { print $2; exit }'
}

# Run a load tool the way the script's own authorization gate would, i.e. with
# an interactive "yes" on stdin. `--yes` is NOT equivalent: it pre-accepts
# every confirm, including the "I am authorized to load-test" gate, so the
# cancellation path can only be reached by answering at the prompt.
ds_run_yes_stdin() {
    run bash -c "printf 'y\n' | '$DEEPSHIT_SCRIPT' \"\$@\"" _ "$@"
}

# Number of lines in a *_LOG file, used for "was this called exactly once".
ds_count_log() {
    local file="$1"
    [[ -f "$file" ]] || { printf '0'; return 0; }
    wc -l < "$file" | tr -d ' '
}

# Every argument the sudo double was handed, one per line (no "SUDO " prefix).
ds_sudo_argv() {
    [[ -f "${DS_SUDO_LOG}" ]] || return 0
    sed -e 's/^SUDO //' -e 's/^CHOWN-SIMULATED /chown /' "${DS_SUDO_LOG}"
}

# Assert the script only ever aimed sudo at the sandbox. The default
# /etc/php and /var/log/xdebug must never appear in a delegated argv; if a
# test forgets to redirect PHP_ETC_DIR/PROFILE_DIR this is the tripwire.
ds_assert_no_real_system_access() {
    local argv
    argv="$(ds_sudo_argv)"
    ! grep -q '/etc/php' <<< "$argv"
    ! grep -q '/var/log/xdebug' <<< "$argv"
    ! grep -q '/home/' <<< "$argv"
}

# Build a PATH that contains only the tools the script legitimately needs, so
# a test can prove what happens when an OPTIONAL tool is missing. This mirrors
# lt_stripped_path in load-test-helper.bash. Without it, "flatpak is absent"
# cannot be tested on a host that really has flatpak installed: deleting the
# mock just exposes the real binary further along PATH.
#
#   PATH="$(ds_stripped_path flatpak)" ds_run --yes --phase 2
ds_stripped_path() {
    local dir="${BATS_TEST_TMPDIR}/bin-stripped"
    local -a exclude=("$@")
    local tool real skip
    rm -rf "$dir"
    mkdir -p "$dir"
    for tool in bash cat cut sort head tail comm awk grep find stat date sleep \
                mkdir touch rm cp mv ls wc uniq tr sed tee seq id whoami diff \
                python3 dirname basename mktemp xargs env printf echo \
                chmod chown install; do
        skip=0
        for e in "${exclude[@]}"; do
            [[ "$e" == "$tool" ]] && skip=1
        done
        (( skip )) && continue
        real="$(command -v "$tool" 2>/dev/null)" || continue
        [[ -n "$real" ]] || continue
        ln -sf "$real" "${dir}/${tool}"
    done
    printf '%s' "$dir"
}

# -------------------------------------------------------------------------
# Primitive-level testing
#
# deepshit.sh ends with an unconditional `main "$@"`, so it cannot be sourced.
# For the pure helpers (path builders, the mtime watermark, the profile poll,
# the duration validator) this lifts the named function definitions straight
# out of the source with awk and sources only those. The alternative - a
# `[[ ${BASH_SOURCE[0]} == $0 ]]` guard in production code - was rejected: it
# would add a test hook to a tool whose whole job is mutating the live host.
#
# ds_load_primitives fpm_xdebug_dropin validate_duration ... > a file you can
# source. Stubs for die/log_warn/confirm are provided so the lifted functions
# do not need the script's logging machinery.
# -------------------------------------------------------------------------
ds_load_primitives() {
    awk -v want="$*" '
        BEGIN { n = split(want, a, " "); for (i = 1; i <= n; i++) w[a[i]] = 1 }
        /^[a-zA-Z_][a-zA-Z0-9_]*\(\) \{/ {
            name = $0
            sub(/\(\).*/, "", name)
            capturing = (name in w)
            if (capturing) print "# --- " name " (lifted from deepshit.sh) ---"
        }
        capturing { print }
        capturing && /^\}/ { capturing = 0 }
    ' "$DEEPSHIT_SCRIPT"
}

# The stubs the lifted functions expect, plus the tunables they read.
ds_primitive_prelude() {
    cat <<'PRELUDE_EOF'
die() { printf 'DIE %s\n' "$*" >&2; exit 1; }
log_warn() { printf 'WARN %s\n' "$*"; }
PROFILE_DIR="${PROFILE_DIR:?}"
PROFILE_POLL_TRIES="${PROFILE_POLL_TRIES:-20}"
PROFILE_POLL_INTERVAL="${PROFILE_POLL_INTERVAL:-0.1}"
PHP_ETC_DIR="${PHP_ETC_DIR:-/etc/php}"
PHP_FPM_VERSION="${PHP_FPM_VERSION:-8.5}"
PHP_LEGACY_VERSION="${PHP_LEGACY_VERSION:-8.4}"
PRELUDE_EOF
}

# Emit a runnable snippet that defines the requested primitives with their
# tunables already bound to the sandbox.
ds_primitive_script() {
    ds_primitive_prelude
    ds_load_primitives "$@"
}

# Run a primitive snippet in a subshell, echoing the path of the snippet so a
# failing test can be reproduced by hand.
ds_primitive_run() {
    local file="${BATS_TEST_TMPDIR}/primitives.$$.sh"
    ds_primitive_script "$@" > "$file"
    echo "$file" >&3
    run bash -c "source '$file'; main_primitive_test"
}
