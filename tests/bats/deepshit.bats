#!/usr/bin/env bats
#
# BATS suite for deepshit.sh (ex-blogware-performance.sh), the automation for
# the six phases of plan/PERFORMANCE_PROFILING_PLAN.md.
#
# Run:   bats tests/bats
#
# The script has no guardable main (`main "$@"` runs unconditionally), so every
# test drives it black-box as a real process. The helper redirects every path
# and URL into the sandbox and installs doubles for sudo, systemctl, curl, ab,
# siege, flatpak and php, so nothing reaches the live host.
#
# Coverage map:
#   CLI contract      --help/--version/unknown flag/--phase arity/--phase range
#   input validation  every advertised env override, at the boundary
#   phase 0           profiler dir, stale legacy block, cachegrind cleanup
#   phase 1           drop-in content, FPM restart, 11-endpoint matrix,
#                     profile attribution by mtime, manifest schema
#   phase 2           flatpak guards, newest-profile selection, DISPLAY gating
#   phase 3           env-var driven guidance, xd-mode.sh presence
#   phase 4           authorization gate, health gate, exact ab/siege argv
#   phase 5           jmeter detection
#   phase 6           RESULTS.md inventory for every artifact kind
#   restore           drop-in removal + FPM restart
#   interactive menu  option dispatch, EOF/quit, invalid choice
#   helpers           path builders, mtime watermark, poll fallback

setup() {
    load 'deepshit-helper'
    ds_setup
}

# =====================================================================
# CLI contract
# =====================================================================

@test "the script is committed with the executable bit set in git" {
    # A lost +x bit fails every test at once with a bare "Permission denied",
    # which reads like a broken suite rather than a broken file mode. Assert
    # the INDEX mode (100755), not the working-copy mode, so the bug cannot
    # reappear in a fresh clone.
    local mode
    mode="$(git -C "$(dirname "$DEEPSHIT_SCRIPT")" ls-files -s deepshit.sh | awk '{print $1}')"
    [ "$mode" = "100755" ]
    [ -x "$DEEPSHIT_SCRIPT" ]
}

@test "--help lists the script under its new name and documents every flag" {
    run "$DEEPSHIT_SCRIPT" --help
    [ "$status" -eq 0 ]
    [[ "$output" == *"deepshit.sh"* ]]
    [[ "$output" != *"blogware-performance.sh"* ]]

    local flag
    for flag in --all --phase --restore --dry-run --yes --help --version; do
        [[ "$output" == *"$flag"* ]]
    done
}

@test "--help prints the effective environment, including the new PHP_ETC_DIR" {
    run "$DEEPSHIT_SCRIPT" --help
    [ "$status" -eq 0 ]
    [[ "$output" == *"PHP_ETC_DIR=$DS_ETC"* ]]
    [[ "$output" == *"PROFILE_DIR=$DS_PROFILE"* ]]
    [[ "$output" == *"SITE_URL=$SITE_URL"* ]]
    [[ "$output" == *"PROFILE_POLL_TRIES=$PROFILE_POLL_TRIES"* ]]
}

@test "a scripted run with no SITE_URL fails loudly instead of assuming a default host" {
    cd "$BATS_TEST_TMPDIR"
    run bash -c "env -u SITE_URL '$DEEPSHIT_SCRIPT' --yes --dry-run --phase 0 < /dev/null"
    [ "$status" -eq 1 ]
    [[ "$output" == *"No target site configured"* ]]
    [[ "$output" == *"--site-url"* ]]
}

@test "phase 3 skips the xd-mode.sh advisory when XD_MODE_SCRIPT is unset" {
    cd "$BATS_TEST_TMPDIR"
    run bash -c "env -u XD_MODE_SCRIPT '$DEEPSHIT_SCRIPT' --yes --dry-run --phase 3 < /dev/null"
    [ "$status" -eq 0 ]
    [[ "$output" == *"XD_MODE_SCRIPT is not set, skipping the xd-mode.sh advisory."* ]]
    [[ "$output" != *"not found or not executable: "* ]]
}

@test "the script hard-codes no default site URL or host-specific xd-mode path" {
    # A baked-in host would silently point Phase 4's load traffic at a machine
    # the operator never chose. Assert the source carries no such literal.
    run grep -nE 'blogware\.site|/home/alanmoe' "$DEEPSHIT_SCRIPT"
    [ "$status" -ne 0 ]
    [[ -z "$output" ]]
}

@test "--site-url sets the target site for a dry-run phase 0" {
    cd "$BATS_TEST_TMPDIR"
    run env -u SITE_URL "$DEEPSHIT_SCRIPT" --dry-run --yes --site-url https://custom.example.test --phase 0
    [ "$status" -eq 0 ]
    [[ "$output" == *"https://custom.example.test"* ]]
    [[ "$output" != *"blogware.site"* ]]
}

@test "--site-url strips a trailing slash to avoid double-slash paths" {
    cd "$BATS_TEST_TMPDIR"
    run env -u SITE_URL "$DEEPSHIT_SCRIPT" --dry-run --yes --site-url https://custom.example.test/ --phase 0
    [ "$status" -eq 0 ]
    [[ "$output" != *".test//"* ]]
}

@test "--site-url without a value fails" {
    cd "$BATS_TEST_TMPDIR"
    run env -u SITE_URL "$DEEPSHIT_SCRIPT" --site-url
    [ "$status" -eq 1 ]
    [[ "$output" == *"--site-url requires a URL."* ]]
    [[ "$output" != *"Unexpected error"* ]]
}

@test "an unknown option fails cleanly without an ERR-trap error" {
    cd "$BATS_TEST_TMPDIR"
    run "$DEEPSHIT_SCRIPT" --no-such-option
    [ "$status" -eq 1 ]
    [[ "$output" == *"Unknown option: --no-such-option"* ]]
    [[ "$output" != *"Unexpected error"* ]]
}

@test "a SITE_URL ending in a slash does not produce double-slash request paths" {
    export SITE_URL="https://slash.example.test/"
    cd "$BATS_TEST_TMPDIR"
    run "$DEEPSHIT_SCRIPT" --dry-run --yes --phase 0
    [ "$status" -eq 0 ]
    [[ "$output" != *"slash.example.test//"* ]]
}

@test "--version reports the script name, semver and author" {
    run "$DEEPSHIT_SCRIPT" --version
    [ "$status" -eq 0 ]
    [[ "$output" == "deepshit.sh v2.1.0 by M.Noermoehammad" ]]
}

@test "an unknown option is rejected with a pointer to --help" {
    ds_run --bogus
    [ "$status" -eq 1 ]
    [[ "$output" == *"Unknown option: --bogus. Use --help."* ]]
}

@test "--phase without a value is rejected" {
    ds_run --phase
    [ "$status" -eq 1 ]
    [[ "$output" == *"--phase requires a number."* ]]
}

@test "an out-of-range phase number is rejected and names the valid range" {
    ds_run --phase 7
    [ "$status" -eq 1 ]
    [[ "$output" == *"Invalid phase: 7. Valid phases are 0..6."* ]]
}

@test "a non-numeric phase is rejected" {
    ds_run --phase abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"Invalid phase: abc. Valid phases are 0..6."* ]]
}

@test "a negative phase is rejected" {
    ds_run --phase -1
    [ "$status" -eq 1 ]
    [[ "$output" == *"Valid phases are 0..6."* ]]
}

@test "every phase number 0..6 is accepted" {
    local p
    for p in 0 1 2 3 5 6; do
        ds_run --yes --dry-run --phase "$p"
        [ "$status" -eq 0 ]
    done
}

# =====================================================================
# Input validation at the boundary
# =====================================================================

@test "AB_REQUESTS=0 is rejected" {
    AB_REQUESTS=0 ds_run --phase 3
    [ "$status" -eq 1 ]
    [[ "$output" == *"AB_REQUESTS must be a positive integer."* ]]
}

@test "a non-numeric AB_REQUESTS is rejected" {
    AB_REQUESTS=lots ds_run --phase 3
    [ "$status" -eq 1 ]
    [[ "$output" == *"AB_REQUESTS must be a positive integer."* ]]
}

@test "AB_CONCURRENCY=0 is rejected" {
    AB_CONCURRENCY=0 ds_run --phase 3
    [ "$status" -eq 1 ]
    [[ "$output" == *"AB_CONCURRENCY must be a positive integer."* ]]
}

@test "SIEGE_CONCURRENCY=-1 is rejected" {
    SIEGE_CONCURRENCY=-1 ds_run --phase 3
    [ "$status" -eq 1 ]
    [[ "$output" == *"SIEGE_CONCURRENCY must be a positive integer."* ]]
}

@test "PROFILE_POLL_TRIES=0 is rejected" {
    PROFILE_POLL_TRIES=0 ds_run --phase 3
    [ "$status" -eq 1 ]
    [[ "$output" == *"PROFILE_POLL_TRIES must be a positive integer."* ]]
}

@test "a garbage SIEGE_DURATION is rejected instead of reaching siege" {
    SIEGE_DURATION=soon ds_run --phase 3
    [ "$status" -eq 1 ]
    [[ "$output" == *"SIEGE_DURATION must be a positive number of seconds"* ]]
}

@test "SIEGE_DURATION accepts bare seconds and s/m/h/d suffixes" {
    local d
    for d in 30 30s 5m 2h 1d; do
        SIEGE_DURATION="$d" ds_run --phase 3
        [ "$status" -eq 0 ]
    done
}

@test "a leading-zero SIEGE_DURATION is rejected" {
    SIEGE_DURATION=007s ds_run --phase 3
    [ "$status" -eq 1 ]
    [[ "$output" == *"SIEGE_DURATION must be a positive number of seconds"* ]]
}

@test "a non-HTTPS SITE_URL warns but does not abort" {
    run bash -c "SITE_URL='http://blogware.invalid' '$DEEPSHIT_SCRIPT' --yes --dry-run --phase 3"
    [ "$status" -eq 0 ]
    [[ "$output" == *"SITE_URL is not an HTTPS URL"* ]]
}

@test "validation runs before any logging directory is created" {
    AB_REQUESTS=0 ds_run --phase 3
    [ "$status" -eq 1 ]
    [ ! -d "$DS_LOG" ] || [ -z "$(ls -A "$DS_LOG")" ]
}

# =====================================================================
# Environment verification
# =====================================================================

@test "the environment section reports the site, the SAPI and the run dir" {
    ds_run --yes --dry-run --phase 3
    [ "$status" -eq 0 ]
    [[ "$output" == *"Environment verification"* ]]
    [[ "$output" == *"Site: https://blogware.invalid"* ]]
    [[ "$output" == *"Profiler output: $DS_PROFILE"* ]]
    [[ "$output" == *"Web SAPI: PHP 8.5 via PHP-FPM (php8.5-fpm)"* ]]
    [[ "$output" == *"Site is reachable."* ]]
}

@test "an inactive FPM service produces a warning, not a failure" {
    DS_SYSTEMCTL_ACTIVE=0 ds_run --yes --dry-run --phase 3
    [ "$status" -eq 0 ]
    [[ "$output" == *"FPM service not active: php8.5-fpm"* ]]
}

@test "Xdebug, KCachegrind, ab and siege detection are all reported" {
    ds_run --yes --dry-run --phase 3
    [ "$status" -eq 0 ]
    [[ "$output" == *"Xdebug loaded for PHP 8.5."* ]]
    [[ "$output" == *"KCachegrind Flatpak is installed."* ]]
    [[ "$output" == *"ApacheBench found:"* ]]
    [[ "$output" == *"siege found:"* ]]
}

@test "a missing PHP binary warns rather than running an empty command" {
    ds_hide_php
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    [[ "$output" == *"No php8.5 CLI binary found; cannot confirm effective FPM Xdebug settings."* ]]
}

@test "a successful run prints the log location on the EXIT trap" {
    ds_run --yes --dry-run --phase 3
    [ "$status" -eq 0 ]
    [[ "$output" == *"Finished successfully. Log: "* ]]
    [ -f "$(ds_run_dir)/run.log" ]
}

# =====================================================================
# Phase 0 - preparation and cleanup
# =====================================================================

@test "phase 0 creates the profiler dir, chowns it and reports the mode" {
    rm -rf "$DS_PROFILE"
    ds_run --yes --phase 0
    [ "$status" -eq 0 ]
    [ -d "$DS_PROFILE" ]
    ds_assert_no_real_system_access

    grep -q "^CHOWN-SIMULATED chown www-data:www-data $DS_PROFILE$" "$DS_SUDO_LOG"
    grep -q "^SUDO chmod 0775 $DS_PROFILE$" "$DS_SUDO_LOG"
    [[ "$output" == *"Profiler directory ready: $DS_PROFILE (owner www-data, mode 0775)"* ]]
}

@test "phase 0 refuses to start when sudo credentials cannot be obtained" {
    DS_SUDO_V_RC=1 ds_run --yes --phase 0
    [ "$status" -eq 1 ]
    [[ "$output" == *"Unable to obtain sudo privileges."* ]]
    [ ! -d "$DS_PROFILE" ] || [ -z "$(ls -A "$DS_PROFILE")" ]
}

@test "phase 0 cleans existing cachegrind files only when confirmed" {
    ds_seed_stale_profile
    ds_run --yes --phase 0
    [ "$status" -eq 0 ]
    [ ! -e "${DS_PROFILE}/cachegrind.out.index.php.0000000000" ]
    [[ "$output" == *"Profiler directory cleaned."* ]]
}

@test "phase 0 keeps cachegrind files when the confirmation is declined" {
    ds_seed_stale_profile
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --phase 0"
    [ "$status" -eq 0 ]
    [ -e "${DS_PROFILE}/cachegrind.out.index.php.0000000000" ]
    [[ "$output" != *"Profiler directory cleaned."* ]]
}

@test "phase 0 deletes only cachegrind files, never unrelated ones" {
    ds_seed_stale_profile
    printf 'keep me\n' > "${DS_PROFILE}/notes.txt"
    ds_run --yes --phase 0
    [ "$status" -eq 0 ]
    [ -e "${DS_PROFILE}/notes.txt" ]
}

@test "phase 0 does nothing destructive under --dry-run" {
    ds_seed_stale_profile
    ds_run --yes --dry-run --phase 0
    [ "$status" -eq 0 ]
    [ -e "${DS_PROFILE}/cachegrind.out.index.php.0000000000" ]
    [[ "$output" == *"DRY-RUN: would create/chown $DS_PROFILE"* ]]
    [[ "$output" == *"DRY-RUN: would delete cachegrind files"* ]]
    ! grep -q "CHOWN-SIMULATED" "$DS_SUDO_LOG"
}

@test "phase 0 skips the sudo password check entirely under --dry-run" {
    DS_SUDO_V_RC=1 ds_run --yes --dry-run --phase 0
    [ "$status" -eq 0 ]
    [[ "$output" == *"DRY-RUN: would create/chown"* ]]
}

# --- stale legacy Xdebug block cleanup ---------------------------------

@test "phase 0 removes the stale 8.4 profiler block and backs the ini up first" {
    cat > "${DS_ETC}/php/8.4/mods-available/xdebug.ini" <<'INI'
zend_extension=xdebug.so
xdebug.mode=develop
; Blogware performance profiling
xdebug.mode=profile
xdebug.start_with_request=trigger
xdebug.output_dir=/var/log/xdebug/blog-profile
xdebug.profiler_output_name=cachegrind.out.%p
INI
    ds_run --yes --phase 0
    [ "$status" -eq 0 ]
    ds_assert_no_real_system_access

    local ini="${DS_ETC}/php/8.4/mods-available/xdebug.ini"
    ! grep -q "Blogware performance profiling" "$ini"
    ! grep -q "start_with_request" "$ini"
    ! grep -q "blog-profile" "$ini"
    # the pre-existing, unrelated directives must survive
    grep -q "^zend_extension=xdebug.so$" "$ini"
    grep -q "^xdebug.mode=develop$" "$ini"

    [ -f "$(ds_run_dir)/backups/xdebug.ini.php8.4.before-cleanup" ]
    grep -q "Blogware performance profiling" \
        "$(ds_run_dir)/backups/xdebug.ini.php8.4.before-cleanup"
    [[ "$output" == *"Stale profiler block removed"* ]]
}

@test "phase 0 also detects a stale block written with the Scriptlog marker" {
    cat > "${DS_ETC}/php/8.4/mods-available/xdebug.ini" <<'INI'
zend_extension=xdebug.so
xdebug.mode=develop
; Scriptlog performance profiling
xdebug.mode=profile
xdebug.start_with_request=trigger
INI
    ds_run --yes --phase 0
    [ "$status" -eq 0 ]
    ds_assert_no_real_system_access

    local ini="${DS_ETC}/php/8.4/mods-available/xdebug.ini"
    ! grep -q "Scriptlog performance profiling" "$ini"
    ! grep -q "start_with_request" "$ini"
    grep -q "^xdebug.mode=develop$" "$ini"
    [[ "$output" == *"Stale profiler block removed"* ]]
}

@test "phase 0 leaves a clean legacy ini untouched and logs no backup" {
    printf 'zend_extension=xdebug.so\nxdebug.mode=develop\n' \
        > "${DS_ETC}/php/8.4/mods-available/xdebug.ini"
    ds_run --yes --phase 0
    [ "$status" -eq 0 ]
    [[ "$output" == *"No stale profiler block found in"* ]]
    [ ! -d "$(ds_run_dir)/backups" ] || [ -z "$(ls -A "$(ds_run_dir)/backups")" ]
}

@test "phase 0 warns and continues when the legacy ini is absent" {
    rm -f "${DS_ETC}/php/8.4/mods-available/xdebug.ini"
    ds_run --yes --phase 0
    [ "$status" -eq 0 ]
    [[ "$output" == *"Legacy Xdebug ini not found, nothing to clean"* ]]
}

@test "phase 0 keeps the stale block when the confirmation is declined" {
    local ini="${DS_ETC}/php/8.4/mods-available/xdebug.ini"
    cat > "$ini" <<'INI'
zend_extension=xdebug.so
; Blogware performance profiling
xdebug.mode=profile
xdebug.start_with_request=trigger
INI
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --phase 0"
    [ "$status" -eq 0 ]
    grep -q "Blogware performance profiling" "$ini"
    [[ "$output" == *"Skipped stale-block cleanup."* ]]
}

# =====================================================================
# Phase 1 - live web profiling
# =====================================================================

@test "phase 1 writes the exact profiler drop-in for the FPM SAPI" {
    run bash -c "printf 'y\nn\n' | '$DEEPSHIT_SCRIPT' --phase 1"
    [ "$status" -eq 0 ]
    ds_assert_no_real_system_access

    local dropin="${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini"
    [ -f "$dropin" ]
    run cat "$dropin"
    [[ "$output" == *"xdebug.mode=profile"* ]]
    [[ "$output" == *"xdebug.start_with_request=trigger"* ]]
    [[ "$output" == *"xdebug.output_dir=$DS_PROFILE"* ]]
    [[ "$output" == *"xdebug.profiler_output_name=cachegrind.out.%s.%u"* ]]
    # trigger-based, so untriggered production traffic keeps zero overhead
    ! grep -q "start_with_request=yes" "$dropin"
}

@test "phase 1 restarts php8.5-fpm and never Apache" {
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    grep -qx "SYSTEMCTL restart php8.5-fpm" "$DS_SYSTEMCTL_LOG"
    ! grep -q "apache" "$DS_SYSTEMCTL_LOG"
    ! grep -q "apache" "$DS_SUDO_LOG"
}

@test "phase 1 dies when the FPM restart is declined" {
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --phase 1"
    [ "$status" -eq 1 ]
    [[ "$output" == *"FPM restart declined. Phase 1 cannot proceed."* ]]
}

@test "phase 1 confirms the effective FPM xdebug settings" {
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    [[ "$output" == *"Effective FPM Xdebug settings:"* ]]
    [[ "$output" == *"xdebug.mode => profile => profile"* ]]
    [[ "$output" == *"xdebug.start_with_request => trigger => trigger"* ]]
}

@test "phase 1 profiles exactly the 11 endpoints of the plan matrix, in order" {
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    run ds_manifest_endpoints
    [ "$status" -eq 0 ]
    [ "${#lines[@]}" -eq 11 ]
    [ "${lines[0]}" = "/" ]
    [ "${lines[1]}" = "/?p=1" ]
    [ "${lines[2]}" = "/?cat=1" ]
    [ "${lines[3]}" = "/?tag=php" ]
    [ "${lines[4]}" = "/?a=202607" ]
    [ "${lines[5]}" = "/blog" ]
    [ "${lines[6]}" = "/search?q=test" ]
    [ "${lines[7]}" = "/?privacy" ]
    [ "${lines[8]}" = "/admin/login.php" ]
    [ "${lines[9]}" = "/?p=2" ]
    [ "${lines[10]}" = "/?q=php" ]
}

@test "phase 1 hits every endpoint with the XDEBUG_PROFILE trigger cookie" {
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    [ "$(ds_count_log "$DS_CURL_LOG")" -eq 12 ]   # 11 trigger requests + the reachability probe
    run grep -c "^CURL https://blogware.invalid/admin/login.php$" "$DS_CURL_LOG"
    [ "$output" = "1" ]
}

@test "phase 1 manifest carries a header and one row per endpoint" {
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    run head -1 "$(ds_run_dir)/profile-manifest.tsv"
    [ "$output" = $'timestamp\tendpoint\thttp_code\tprofile_file' ]
    [ "$(wc -l < "$(ds_run_dir)/profile-manifest.tsv")" -eq 12 ]
}

@test "phase 1 credits each endpoint with the profile that request produced" {
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    run ds_manifest_rows
    local endpoint code file missing=0
    while IFS=$'\t' read -r endpoint code file; do
        [ -n "$endpoint" ] || continue
        if [ ! -f "${DS_PROFILE}/${file}" ]; then
            printf 'manifest credits a missing file: %s -> %s\n' "$endpoint" "$file" >&2
            missing=1
        fi
    done <<< "$output"
    [ "$missing" -eq 0 ]
}

@test "phase 1 attributes the admin profile by mtime, not by name order" {
    # Regression test. The admin entry sorts BEFORE index.php alphabetically,
    # so a set-difference sorted by name credits the previous index.php
    # request. The mtime watermark is what makes this correct.
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    run ds_manifest_file_for "/admin/login.php"
    [[ "$output" == *admin_login.php* ]]
    run ds_manifest_file_for "/?privacy"
    [[ "$output" == *index.php* ]]
}

@test "phase 1 ignores a stale profile that predates the run" {
    ds_seed_stale_profile
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    run ds_manifest_rows
    ! grep -q "cachegrind.out.index.php.0000000000" <<< "$output"
    [[ "$output" != *"0000000000"* ]]
}

@test "phase 1 reports and records a profile-less response without aborting" {
    DS_CURL_NO_PROFILE=1 ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    [[ "$output" == *"no new cachegrind file detected"* ]]
    run ds_manifest_file_for "/"
    [ "$output" = "-" ]
}

@test "phase 1 records the HTTP status each endpoint returned" {
    DS_CURL_STATUS=404 ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    run ds_manifest_code_for "/?p=2"
    [ "$output" = "404" ]
}

@test "phase 1 records 000 for an endpoint whose transport failed" {
    DS_CURL_FAIL="p=2" ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    run ds_manifest_code_for "/?p=2"
    [ "$output" = "000" ]
}

@test "phase 1 polls for a profile that lands after curl returns" {
    DS_CURL_PROFILE_DELAY=0.15 ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    run ds_manifest_rows
    ! grep -q $'\t-$' <<< "$output"
    [[ "$output" != *"no new cachegrind file detected"* ]]
}

@test "phase 1 credits every one of the 11 endpoints a distinct profile file" {
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    run ds_manifest_rows
    local total distinct
    total="$(wc -l <<< "$output" | tr -d ' ')"
    distinct="$(cut -f3 <<< "$output" | sort -u | wc -l | tr -d ' ')"
    [ "$total" -eq 11 ]
    [ "$distinct" -eq 11 ]
}

@test "phase 1 offers to remove the drop-in when it finishes" {
    ds_run --yes --phase 1
    [ "$status" -eq 0 ]
    [ ! -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
    grep -qx "SYSTEMCTL restart php8.5-fpm" "$DS_SYSTEMCTL_LOG"
}

@test "phase 1 leaves the drop-in installed and says so when declined" {
    run bash -c "printf 'y\nn\n' | '$DEEPSHIT_SCRIPT' --phase 1"
    [ "$status" -eq 0 ]
    [ -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
    [[ "$output" == *"Profiler drop-in left installed. Run 'deepshit.sh --restore' when finished."* ]]
}

@test "phase 1 --dry-run writes no config and issues no trigger request" {
    ds_run --yes --dry-run --phase 1
    [ "$status" -eq 0 ]
    [ ! -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
    [ -z "$(ls -A "$DS_PROFILE")" ]
    [ "$(ds_count_log "$DS_CURL_LOG")" -eq 1 ]   # reachability probe only
    [[ "$output" == *"DRY-RUN: would write profiler drop-in"* ]]
}

@test "phase 1 --dry-run still writes a manifest with a row per endpoint" {
    ds_run --yes --dry-run --phase 1
    [ "$status" -eq 0 ]
    run ds_manifest_rows
    [ "$status" -eq 0 ]
    [ "${#lines[@]}" -eq 11 ]
    run ds_manifest_file_for "/"
    [ "$output" = "-" ]
}

# =====================================================================
# Phase 2 - KCachegrind
# =====================================================================

@test "phase 2 opens the newest profile when a DISPLAY is available" {
    ds_run --yes --phase 1
    ds_seed_stale_profile "cachegrind.out.index.php.0000000000"
    touch "${DS_PROFILE}/cachegrind.out.admin_login.php.9999999999.0000000099"

    DISPLAY=:0 ds_run --yes --phase 2
    [ "$status" -eq 0 ]
    [[ "$output" == *"Newest profile: $DS_PROFILE/cachegrind.out.admin_login.php.9999999999.0000000099"* ]]
    [[ "$output" == *"FLATPAK run org.kde.kcachegrind $DS_PROFILE/cachegrind.out.admin_login.php.9999999999.0000000099"* ]]
    [[ "$output" == *"Sort Flat Profile by Total Self"* ]]
}

@test "phase 2 does not launch a GUI when DISPLAY is unset" {
    ds_run --yes --phase 1
    unset DISPLAY
    ds_run --yes --phase 2
    [ "$status" -eq 0 ]
    [[ "$output" == *"DISPLAY is not set. GUI cannot be opened from this shell."* ]]
    [[ "$output" == *"Open manually with: flatpak run org.kde.kcachegrind"* ]]
}

@test "phase 2 tells the user to run phase 1 when the dir is empty" {
    ds_run --yes --phase 2
    [ "$status" -eq 0 ]
    [[ "$output" == *"No cachegrind files found in $DS_PROFILE."* ]]
    [[ "$output" == *"Run Phase 1 first."* ]]
}

@test "phase 2 dies when the kcachegrind Flatpak is not installed" {
    DS_FLATPAK_PRESENT=0 ds_run --yes --phase 2
    [ "$status" -eq 1 ]
    [[ "$output" == *"KCachegrind Flatpak org.kde.kcachegrind is not installed."* ]]
}

@test "phase 2 dies when flatpak is absent from PATH altogether" {
    # Deleting only the mock would expose the host's real flatpak further along
    # PATH, so strip it from PATH as well and keep the mock bin (sudo, curl,
    # systemctl live there) or the run dies before it reaches Phase 2.
    rm -f "${DS_MOCK_BIN}/flatpak"
    PATH="${DS_MOCK_BIN}:$(ds_stripped_path flatpak)"; export PATH
    ds_run --yes --phase 2
    [ "$status" -eq 1 ]
    [[ "$output" == *"flatpak is not installed."* ]]
}

# =====================================================================
# Phase 3 - CLI profiling / coverage
# =====================================================================

@test "phase 3 documents the env-var driven profiler and coverage invocations" {
    ds_run --yes --phase 3
    [ "$status" -eq 0 ]
    [[ "$output" == *"XDEBUG_MODE=profile XDEBUG_TRIGGER=1 php8.5"* ]]
    [[ "$output" == *"xdebug.output_dir=$DS_PROFILE"* ]]
    [[ "$output" == *"cachegrind.out.%s.%u path/to/script.php"* ]]
    [[ "$output" == *"XDEBUG_MODE=coverage php -d error_reporting=... lib/vendor/bin/phpunit --coverage-text"* ]]
}

@test "phase 3 explains why xd-mode.sh cannot be relied on" {
    ds_run --yes --phase 3
    [ "$status" -eq 0 ]
    [[ "$output" == *"do"* ]]
    [[ "$output" == *"not depend on xd-mode.sh, whose sed cannot add a missing xdebug.mode line"* ]]
}

@test "phase 3 warns about xd-mode.sh clobbering the FPM ini when it is present" {
    cat > "${BATS_TEST_TMPDIR}/xd-mode.sh" <<'XDM'
#!/usr/bin/env bash
echo "xd-mode status: full"
XDM
    chmod +x "${BATS_TEST_TMPDIR}/xd-mode.sh"
    XD_MODE_SCRIPT="${BATS_TEST_TMPDIR}/xd-mode.sh" ds_run --yes --phase 3
    [ "$status" -eq 0 ]
    [[ "$output" == *"xd-mode.sh found:"* ]]
    # xd-mode.sh does `sudo mkdir -p /var/log/xdebug` and
    # `sudo find /var/log/xdebug -type f -mtime +1 -delete` BEFORE it reads its
    # own argument, so even `xd-mode.sh status` escalates to root and destroys
    # profiler output on the live host. Phase 3 is documented as read-only, so
    # it must report the tool without ever running it.
    [[ "$output" == *"xd-mode.sh is NOT run by this phase"* ]]
    [[ "$output" == *"sudo find /var/log/xdebug -type f -mtime +1 -delete"* ]]
    [[ "$output" == *"Do not run it while the Phase 1 profiler drop-in is installed."* ]]
}

@test "phase 3 warns when xd-mode.sh is missing" {
    XD_MODE_SCRIPT="${BATS_TEST_TMPDIR}/absent.sh" ds_run --yes --phase 3
    [ "$status" -eq 0 ]
    [[ "$output" == *"xd-mode.sh not found or not executable"* ]]
}

@test "phase 3 never executes xd-mode.sh even when it is executable" {
    local fixture="${BATS_TEST_TMPDIR}/xd-mode-exec.sh"
    printf '#!/usr/bin/env bash\nprintf "xd-mode EXECUTED\\n"\n' > "$fixture"
    chmod +x "$fixture"
    XD_MODE_SCRIPT="$fixture" ds_run --yes --phase 3
    [ "$status" -eq 0 ]
    [[ "$output" == *"xd-mode.sh found: $fixture"* ]]
    [[ "$output" != *"xd-mode EXECUTED"* ]]
}

@test "phase 3 is read-only: no sudo call at all" {
    ds_run --yes --phase 3
    [ "$status" -eq 0 ]
    [ ! -s "$DS_SUDO_LOG" ]
}

# =====================================================================
# Phase 4 - load testing
# =====================================================================

@test "phase 4 refuses to run without the load-test authorization gate" {
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --phase 4"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Load testing cancelled."* ]]
    ! grep -q '^ab -n' "$DS_AB_LOG"
    ! grep -q '^siege -c' "$DS_SIEGE_LOG"
}

@test "phase 4 aborts when the target health check fails" {
    DS_CURL_FAIL=blogware.invalid ds_run --yes --phase 4
    [ "$status" -eq 1 ]
    [[ "$output" == *"Target health check failed. Refusing to start load tests."* ]]
    ! grep -q '^ab -n' "$DS_AB_LOG"
    ! grep -q '^siege -c' "$DS_SIEGE_LOG"
}

@test "phase 4 runs the four ApacheBench scenarios with the documented flags" {
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    run grep '^ab -n' "$DS_AB_LOG"
    [ "${lines[0]}" = "ab -n 1000 -c 10 -k https://blogware.invalid/" ]
    [ "${lines[1]}" = "ab -n 500 -c 10 -k https://blogware.invalid/?p=1" ]
    [ "${lines[2]}" = "ab -n 500 -c 10 -k https://blogware.invalid/search?q=test" ]
    [ "${lines[3]}" = "ab -n 500 -c 10 -k https://blogware.invalid/?p=99999" ]
}

@test "phase 4 honours AB_REQUESTS and AB_CONCURRENCY overrides" {
    AB_REQUESTS=200 AB_CONCURRENCY=4 ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    run grep '^ab -n' "$DS_AB_LOG"
    [[ "${lines[0]}" = "ab -n 200 -c 4 -k https://blogware.invalid/" ]]
    [[ "${lines[2]}" = "ab -n 500 -c 4 -k https://blogware.invalid/search?q=test" ]]
}

@test "phase 4 runs siege with the configured concurrency and duration" {
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    run grep '^siege -c' "$DS_SIEGE_LOG"
    [ "${lines[0]}" = "siege -c 10 -t 60s -f $(ds_run_dir)/load-tests/siege-urls.txt" ]
}

@test "phase 4 honours SIEGE_DURATION and SIEGE_CONCURRENCY overrides" {
    SIEGE_DURATION=30s SIEGE_CONCURRENCY=25 ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    run grep '^siege -c' "$DS_SIEGE_LOG"
    [[ "${lines[0]}" = "siege -c 25 -t 30s -f "* ]]
}

@test "phase 4 builds the siege url list from the four mixed endpoints" {
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    run cat "$(ds_run_dir)/load-tests/siege-urls.txt"
    [ "${#lines[@]}" -eq 4 ]
    [ "${lines[0]}" = "https://blogware.invalid/" ]
    [ "${lines[1]}" = "https://blogware.invalid/?p=1" ]
    [ "${lines[2]}" = "https://blogware.invalid/?cat=1" ]
    [ "${lines[3]}" = "https://blogware.invalid/search?q=test" ]
}

@test "phase 4 saves one report file per ab scenario plus the siege report" {
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    local dir="$(ds_run_dir)/load-tests" f
    for f in ab-homepage.txt ab-single-post.txt ab-search.txt ab-404.txt siege-mixed.txt; do
        [ -f "${dir}/${f}" ]
    done
    grep -q "mock ab report" "${dir}/ab-homepage.txt"
    [[ "$output" == *"Load-test results directory: $dir"* ]]
}

@test "phase 4 keeps the report and warns when ab returns non-zero" {
    DS_AB_RC=1 ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    [[ "$output" == *"ApacheBench returned non-zero for"* ]]
    [ -f "$(ds_run_dir)/load-tests/ab-homepage.txt" ]
}

@test "phase 4 keeps the report and warns when siege returns non-zero" {
    DS_SIEGE_RC=1 ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    [[ "$output" == *"siege returned non-zero. Report retained"* ]]
    [ -f "$(ds_run_dir)/load-tests/siege-mixed.txt" ]
}

@test "phase 4 skips the ab block when ab is missing and still runs siege" {
    rm -f "${DS_MOCK_BIN}/ab"
    PATH="${DS_MOCK_BIN}:$(ds_stripped_path ab)"; export PATH
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    [[ "$output" == *"ab is not installed; skipping ApacheBench."* ]]
    grep -q '^siege -c' "$DS_SIEGE_LOG"
}

@test "phase 4 skips the siege block when siege is missing and still runs ab" {
    rm -f "${DS_MOCK_BIN}/siege"
    PATH="${DS_MOCK_BIN}:$(ds_stripped_path siege)"; export PATH
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    [[ "$output" == *"siege is not installed; skipping siege."* ]]
    grep -q '^ab -n' "$DS_AB_LOG"
}

@test "phase 4 warns that the profiler drop-in is still installed" {
    # Accept the restart, decline the trailing removal, so the drop-in stays.
    run bash -c "printf 'y\nn\n' | '$DEEPSHIT_SCRIPT' --phase 1"
    [ "$status" -eq 0 ]
    [ -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    [[ "$output" == *"The Phase 1 profiler drop-in is still installed."* ]]
    [[ "$output" == *"before measuring."* ]]
}

@test "phase 4 does not warn about the drop-in once it has been restored" {
    ds_run --yes --phase 1
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    [[ "$output" != *"profiler drop-in is still installed"* ]]
}

@test "phase 4 --dry-run starts no load tool and writes no report" {
    ds_run --yes --dry-run --phase 4
    [ "$status" -eq 0 ]
    ! grep -q '^ab -n' "$DS_AB_LOG"
    ! grep -q '^siege -c' "$DS_SIEGE_LOG"
    [[ "$output" == *"DRY-RUN: ab -n 1000 -c 10 -k https://blogware.invalid/"* ]]
    [[ "$output" == *"DRY-RUN: siege -c 10 -t 60s -f"* ]]
}

@test "phase 4 is read-only with respect to the PHP-FPM configuration" {
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    ! grep -q "tee\|99-xdebug-profiler" "$DS_SUDO_LOG"
}

# =====================================================================
# Phase 5 - optional JMeter
# =====================================================================

@test "phase 5 reports JMeter as optional and not installed" {
    ds_run --yes --phase 5
    [ "$status" -eq 0 ]
    [[ "$output" == *"JMeter is optional. Xdebug + Kcachegrind + ab + siege already cover the core workflow."* ]]
    [[ "$output" == *"install a current official JMeter release"* ]]
}

@test "phase 5 detects a jmeter on PATH and recommends the official release" {
    cat > "${DS_MOCK_BIN}/jmeter" <<'JM'
#!/usr/bin/env bash
printf '5.6.3\n'
JM
    chmod +x "${DS_MOCK_BIN}/jmeter"
    ds_run --yes --phase 5
    [ "$status" -eq 0 ]
    [[ "$output" == *"JMeter found: 5.6.3"* ]]
    [[ "$output" == *"Use the official/current JMeter distribution rather than relying on an old distro package."* ]]
}

@test "phase 5 detects a local apache-jmeter checkout" {
    mkdir -p "${BATS_TEST_TMPDIR}/cwd/apache-jmeter/bin"
    cat > "${BATS_TEST_TMPDIR}/cwd/apache-jmeter/bin/jmeter" <<'JM'
#!/usr/bin/env bash
printf '5.6.3\n'
JM
    chmod +x "${BATS_TEST_TMPDIR}/cwd/apache-jmeter/bin/jmeter"
    cd "${BATS_TEST_TMPDIR}/cwd"
    run "$DEEPSHIT_SCRIPT" --yes --phase 5
    cd "$OLDPWD" 2>/dev/null || cd "${BATS_TEST_DIRNAME}"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Local JMeter distribution found: ./apache-jmeter/bin/jmeter"* ]]
}

# =====================================================================
# Phase 6 - results inventory
# =====================================================================

@test "phase 6 writes RESULTS.md with the run identity and SAPI" {
    ds_run --yes --phase 6
    [ "$status" -eq 0 ]
    local report="$(ds_run_dir)/RESULTS.md"
    [ -f "$report" ]
    run cat "$report"
    [[ "$output" == *"# Scriptlog Performance Run"* ]]
    grep -qF 'Site: `https://blogware.invalid`' <<< "$output"
    grep -qF 'Web SAPI: PHP `8.5` via PHP-FPM (`php8.5-fpm`)' <<< "$output"
    grep -qF 'PHP CLI target: `8.5`' <<< "$output"
}

@test "phase 6 inventories the profiler files newest first" {
    ds_run --yes --phase 1
    ds_run --yes --phase 6
    [ "$status" -eq 0 ]
    run cat "$(ds_run_dir)/RESULTS.md"
    [[ "$output" == *"## Profile files"* ]]
    [[ "$output" == *"cachegrind.out.index.php.0000000001"* ]]
    [[ "$output" == *"bytes"* ]]
}

@test "phase 6 inlines the phase 1 manifest" {
    ds_run --yes --phase 1
    ds_run --yes --phase 6
    [ "$status" -eq 0 ]
    run cat "$(ds_run_dir)/RESULTS.md"
    [[ "$output" == *"## Phase 1 manifest"* ]]
    grep -qF "$(printf 'endpoint\thttp_code\tprofile_file')" <<< "$output"
    [[ "$output" == *"/admin/login.php"* ]]
}

@test "phase 6 says so when no manifest exists" {
    ds_run --yes --phase 6
    [ "$status" -eq 0 ]
    run cat "$(ds_run_dir)/RESULTS.md"
    [[ "$output" == *"No Phase 1 manifest was generated."* ]]
}

@test "phase 6 lists the load-test reports" {
    ds_run --yes --phase 4
    ds_run --yes --phase 6
    [ "$status" -eq 0 ]
    run cat "$(ds_run_dir)/RESULTS.md"
    [[ "$output" == *"## Load-test reports"* ]]
    [[ "$output" == *"ab-homepage.txt"* ]]
    [[ "$output" == *"siege-mixed.txt"* ]]
}

@test "phase 6 finds load-test reports from a separate earlier invocation" {
    # Phase 4 and Phase 6 are separate processes with separate RUN_IDs, so the
    # reports live in an EARLIER run directory. Reading only this run's own
    # directory reported "No load-test directory was generated" immediately
    # after a successful load test.
    ds_run --yes --phase 4
    [ "$status" -eq 0 ]
    ds_run --yes --phase 6
    [ "$status" -eq 0 ]
    run cat "$(ds_run_dir)/RESULTS.md"
    [[ "$output" == *"ab-homepage.txt"* ]]
    [[ "$output" == *"siege-mixed.txt"* ]]
    [[ "$output" != *"No load-test directory was generated."* ]]
}

@test "phase 6 states the interpretation caveats" {
    ds_run --yes --phase 6
    [ "$status" -eq 0 ]
    run cat "$(ds_run_dir)/RESULTS.md"
    [[ "$output" == *"## Interpretation reminder"* ]]
    [[ "$output" == *"not directly comparable to the current Apache + PHP-FPM 8.5 stack"* ]]
    [[ "$output" == *"Use KCachegrind Self/Inclusive cost"* ]]
    [[ "$output" == *"Compare load-test failures separately from ApacheBench Length mismatch"* ]]
    [[ "$output" == *"Code optimization is outside the scope of this automation."* ]]
}

@test "phase 6 prints the artifact map for every artifact kind" {
    local phase1_dir
    ds_run --yes --phase 1
    phase1_dir="$(ds_run_dir)"
    ds_run --yes --phase 6
    [ "$status" -eq 0 ]
    [[ "$output" == *"Artifacts"* ]]
    [[ "$output" == *"Main log      : $(ds_run_dir)/run.log"* ]]
    [[ "$output" == *"Results       : $(ds_run_dir)/RESULTS.md"* ]]
    # Each invocation gets its own RUN_ID, so the profile map has to point back
    # at the run directory that actually holds the manifest.
    [[ "$output" == *"Profile map   : $phase1_dir/profile-manifest.tsv"* ]]
    [ -f "$phase1_dir/profile-manifest.tsv" ]
}

@test "phase 6 notes a missing profiler dir instead of failing" {
    rm -rf "$DS_PROFILE"
    ds_run --yes --phase 6
    [ "$status" -eq 0 ]
    run cat "$(ds_run_dir)/RESULTS.md"
    grep -qF "Profiler directory does not exist: \`$DS_PROFILE\`" <<< "$output"
}

# =====================================================================
# --restore
# =====================================================================

@test "--restore removes the drop-in and restarts the FPM service" {
    ds_run --yes --phase 1
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --phase 1"
    [ -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]

    ds_run --yes --restore
    [ "$status" -eq 0 ]
    ds_assert_no_real_system_access
    [ ! -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
    grep -qx "SYSTEMCTL restart php8.5-fpm" "$DS_SYSTEMCTL_LOG"
    [[ "$output" == *"Restore complete."* ]]
}

@test "--restore is idempotent when no drop-in is present" {
    ds_run --yes --restore
    [ "$status" -eq 0 ]
    [[ "$output" == *"No profiler drop-in present"* ]]
    [[ "$output" == *"Restore complete."* ]]
}

@test "--restore warns that the running pool still has the config loaded" {
    ds_run --yes --phase 1
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --phase 1"
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --restore"
    [ "$status" -eq 0 ]
    [ ! -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
    [[ "$output" == *"FPM restart declined; the drop-in is removed but still loaded by the running pool."* ]]
}

@test "--restore fails when sudo credentials are unavailable" {
    DS_SUDO_V_RC=1 ds_run --yes --restore
    [ "$status" -eq 1 ]
    [[ "$output" == *"Unable to obtain sudo privileges."* ]]
}

@test "--restore --dry-run keeps the drop-in and issues no restart" {
    ds_run --yes --phase 1
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --phase 1"
    : > "$DS_SYSTEMCTL_LOG"

    ds_run --yes --dry-run --restore
    [ "$status" -eq 0 ]
    [ -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
    [[ "$output" == *"DRY-RUN: would remove profiler drop-in"* ]]
    [[ "$output" == *"DRY-RUN sudo systemctl restart php8.5-fpm"* ]]
}

# =====================================================================
# Interactive menu
# =====================================================================

@test "the menu quits cleanly on q without running any phase" {
    run bash -c "printf 'q\n' | '$DEEPSHIT_SCRIPT'"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Scriptlog Performance Profiler v2.1.0"* ]]
    [[ "$output" == *"Goodbye."* ]]
    [[ "$output" != *"Phase 1: profile"* ]]
    [ ! -f "$(ds_run_dir)/profile-manifest.tsv" ]
}

@test "the menu quits cleanly on EOF instead of aborting" {
    # Regression test: a bare `read` under `set -e` made Ctrl-D exit with 1.
    run bash -c "'$DEEPSHIT_SCRIPT' < /dev/null"
    [ "$status" -eq 0 ]
    [[ "$output" == *"No further input; goodbye."* ]]
}

@test "the menu re-prompts on an invalid choice and then quits" {
    run bash -c "printf 'x\nzz\nq\n' | '$DEEPSHIT_SCRIPT'"
    [ "$status" -eq 0 ]
    [ "${#lines[@]}" -ge 3 ]
    [[ "$output" == *"Invalid choice."* ]]
    [[ "$output" == *"Goodbye."* ]]
}

@test "the menu dispatches to phase 3 for choice 3" {
    run bash -c "printf '3\nq\n' | '$DEEPSHIT_SCRIPT' --yes"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Phase 3: CLI profiling / coverage"* ]]
}

@test "the menu dispatches to restore for choice r" {
    ds_run --yes --phase 1
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --phase 1"
    run bash -c "printf 'r\ny\nq\n' | '$DEEPSHIT_SCRIPT'"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Restore: disable web profiling"* ]]
    [ ! -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
}

@test "the menu header names the target and the SAPI version" {
    run bash -c "printf 'q\n' | '$DEEPSHIT_SCRIPT'"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Target: https://blogware.invalid (PHP 8.5 FPM)"* ]]
}

# =====================================================================
# --all
# =====================================================================

@test "--all runs phases 0 through 6 in order under --dry-run" {
    ds_run --yes --dry-run --all
    [ "$status" -eq 0 ]
    [[ "$output" == *"Phase 0: preparation and cleanup"* ]]
    [[ "$output" == *"Phase 1: profile live web endpoints"* ]]
    [[ "$output" == *"Phase 2: analyze profiles with KCachegrind"* ]]
    [[ "$output" == *"Phase 3: CLI profiling / coverage"* ]]
    [[ "$output" == *"Phase 4: load testing with ApacheBench and siege"* ]]
    [[ "$output" == *"Phase 5: optional JMeter"* ]]
    [[ "$output" == *"Phase 6: results inventory and deliverables"* ]]

    local n
    for n in "Phase 0:" "Phase 1:" "Phase 2:" "Phase 3:" "Phase 4:" "Phase 5:" "Phase 6:"; do
        [ "$(grep -c "$n" <<< "$output")" -eq 1 ]
    done
}

@test "--all leaves the system untouched under --dry-run" {
    ds_seed_stale_profile
    ds_run --yes --dry-run --all
    [ "$status" -eq 0 ]
    [ -e "${DS_PROFILE}/cachegrind.out.index.php.0000000000" ]
    [ ! -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
    ! grep -q '^ab -n' "$DS_AB_LOG"
    ! grep -q '^siege -c' "$DS_SIEGE_LOG"
}

@test "--all prefers --restore when both --all and --restore are given" {
    ds_run --yes --phase 1
    run bash -c "printf 'n\n' | '$DEEPSHIT_SCRIPT' --phase 1"
    ds_run --yes --all --restore
    [ "$status" -eq 0 ]
    [[ "$output" == *"Restore: disable web profiling"* ]]
    [[ "$output" != *"Phase 0: preparation"* ]]
    [ ! -f "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
}

@test "each invocation gets its own run directory" {
    ds_run --yes --phase 3
    local first
    first="$(ds_run_dir)"
    sleep 1
    ds_run --yes --phase 3
    local second
    second="$(ds_run_dir)"
    [ "$first" != "$second" ]
    [ -f "${first}/run.log" ]
    [ -f "${second}/run.log" ]
}

@test "the run log records every executed command" {
    ds_run --yes --phase 1
    run cat "$(ds_run_dir)/run.log"
    [[ "$output" == *"[INFO] SECTION: Phase 1"* ]]
    [[ "$output" == *"[INFO] RUN: sudo systemctl restart php8.5-fpm"* ]]
    [[ "$output" == *"[OK] Phase 1 complete."* ]]
}

# =====================================================================
# Helper-level unit tests for the profile-attribution primitives
# =====================================================================

# Each test writes a small body that exercises the lifted primitives, then
# sources the generated snippet. Keeping the body in a file (rather than a
# nested `bash -c` one-liner) keeps the assertions readable.
_ds_body() {
    cat > "${BATS_TEST_TMPDIR}/body.sh"
}

_ds_run_body() {
    _ds_body
    local snippet="${BATS_TEST_TMPDIR}/snippet.sh"
    ds_primitive_script "$@" > "$snippet"
    run bash -c "source '$snippet'; source '${BATS_TEST_TMPDIR}/body.sh'"
}

@test "fpm_xdebug_dropin and legacy_xdebug_ini derive from PHP_ETC_DIR" {
    _ds_run_body fpm_xdebug_dropin legacy_xdebug_ini fpm_service <<'BODY'
printf '%s\n' "$(fpm_xdebug_dropin)"
printf '%s\n' "$(legacy_xdebug_ini)"
printf '%s\n' "$(fpm_service)"
BODY
    [ "$status" -eq 0 ]
    [ "${lines[0]}" = "${DS_ETC}/php/8.5/fpm/conf.d/99-xdebug-profiler.ini" ]
    [ "${lines[1]}" = "${DS_ETC}/php/8.4/mods-available/xdebug.ini" ]
    [ "${lines[2]}" = "php8.5-fpm" ]
}

@test "the path builders target the FPM SAPI, never mod_php" {
    _ds_run_body fpm_xdebug_dropin legacy_xdebug_ini <<'BODY'
printf '%s\n' "$(fpm_xdebug_dropin)"
printf '%s\n' "$(legacy_xdebug_ini)"
BODY
    [ "$status" -eq 0 ]
    [[ "${lines[0]}" == */fpm/conf.d/* ]]
    [[ "${lines[1]}" == */8.4/mods-available/* ]]
}

@test "newest_profile_mtime reports the highest cachegrind mtime" {
    ds_seed_stale_profile "cachegrind.out.index.php.0000000000"
    printf 'new\n' > "${DS_PROFILE}/cachegrind.out.index.php.0000000001"
    _ds_run_body newest_profile_mtime <<'BODY'
printf '%s\n' "$(newest_profile_mtime)"
BODY
    [ "$status" -eq 0 ]
    # find -printf %T@ is fractional (seconds.nanoseconds); the date is only
    # the current era, not a wall-clock assertion.
    run awk -v t="$output" 'BEGIN { exit !(t > 0 && t < 2000000000) }'
    [ "$status" -eq 0 ]
}

@test "newest_profile_mtime ignores non-cachegrind files" {
    printf 'notes\n' > "${DS_PROFILE}/notes.txt"
    touch -d '2030-01-01' "${DS_PROFILE}/notes.txt"
    printf 'a\n' > "${DS_PROFILE}/cachegrind.out.index.php.0000000001"
    _ds_run_body newest_profile_mtime <<'BODY'
printf '%s\n' "$(newest_profile_mtime)"
BODY
    [ "$status" -eq 0 ]
    run awk -v t="$output" 'BEGIN { exit !(t > 0 && t < 2000000000) }'
    [ "$status" -eq 0 ]
}

@test "newest_profile_mtime is empty for an absent profiler dir" {
    _ds_run_body newest_profile_mtime <<'BODY'
PROFILE_DIR="${PROFILE_DIR}/does-not-exist"
printf '[%s]\n' "$(newest_profile_mtime)"
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "[]" ]
}

@test "newest_profile_since returns only files past the watermark" {
    printf 'old\n' > "${DS_PROFILE}/cachegrind.out.index.php.0000000000"
    touch -d '2002-01-01' "${DS_PROFILE}/cachegrind.out.index.php.0000000000"
    printf 'new\n' > "${DS_PROFILE}/cachegrind.out.index.php.0000000001"
    _ds_run_body newest_profile_mtime newest_profile_since <<'BODY'
printf 'all=[%s]\n' "$(newest_profile_since 0)"
printf 'none=[%s]\n' "$(newest_profile_since "$(newest_profile_mtime)")"
BODY
    [ "$status" -eq 0 ]
    [[ "${lines[0]}" = "all=[cachegrind.out.index.php.0000000001]" ]]
    [[ "${lines[1]}" = "none=[]" ]]
}

@test "newest_profile_since picks by mtime, not by name order" {
    # The regression: the admin profile sorts BEFORE the index ones, so a
    # name-ordered comparison would credit the wrong file.
    printf 'stale\n' > "${DS_PROFILE}/cachegrind.out.index.php.9999999999.0000000099"
    touch -d '2003-01-01' "${DS_PROFILE}/cachegrind.out.index.php.9999999999.0000000099"
    printf 'fresh\n' > "${DS_PROFILE}/cachegrind.out.admin_login.php.0000000001"
    _ds_run_body newest_profile_since <<'BODY'
printf '%s\n' "$(newest_profile_since 0)"
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "cachegrind.out.admin_login.php.0000000001" ]
}

@test "newest_profile_since breaks an mtime tie on the file name" {
    printf 'a\n' > "${DS_PROFILE}/cachegrind.out.index.php.0000000002"
    printf 'b\n' > "${DS_PROFILE}/cachegrind.out.index.php.0000000001"
    touch -d '2020-05-05 05:05:05' "${DS_PROFILE}"/cachegrind.out.index.php.000000000{1,2}
    _ds_run_body newest_profile_since <<'BODY'
printf '%s\n' "$(newest_profile_since 0)"
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "cachegrind.out.index.php.0000000002" ]
}

@test "newest_profile_since treats an empty watermark as zero" {
    printf 'a\n' > "${DS_PROFILE}/cachegrind.out.index.php.0000000001"
    _ds_run_body newest_profile_since <<'BODY'
printf '%s\n' "$(newest_profile_since '')"
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "cachegrind.out.index.php.0000000001" ]
}

@test "await_profile_file polls until a late profile appears" {
    _ds_run_body await_profile_file newest_profile_since <<'BODY'
# The harness budget is deliberately tiny (3 x 0.01s), so give this test a real
# budget: the point is that the poll keeps retrying until the file lands.
PROFILE_POLL_TRIES=200
PROFILE_POLL_INTERVAL=0.01
( sleep 0.4; printf 'late\n' > "${PROFILE_DIR}/cachegrind.out.index.php.0000000001" ) &
printf 'picked=%s\n' "$(await_profile_file 0 '')"
wait
BODY
    [ "$status" -eq 0 ]
    [[ "$output" == *"picked=cachegrind.out.index.php.0000000001"* ]]
}

@test "await_profile_file returns empty when nothing is ever written" {
    _ds_run_body await_profile_file newest_profile_since <<'BODY'
printf 'out=[%s]\n' "$(await_profile_file 9999999999 '')"
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "out=[]" ]
}

@test "await_profile_file falls back to the filename difference" {
    # Watermark already in the future, so the mtime branch can never match;
    # the set-difference fallback must still surface the new file.
    _ds_run_body await_profile_file newest_profile_since <<'BODY'
printf 'a\n' > "${PROFILE_DIR}/cachegrind.out.index.php.0000000000"
before="$(find "${PROFILE_DIR}" -maxdepth 1 -type f -printf '%f\n' | sort)"
printf 'b\n' > "${PROFILE_DIR}/cachegrind.out.admin_login.php.0000000001"
printf 'picked=%s\n' "$(await_profile_file 9999999999 "$before")"
BODY
    [ "$status" -eq 0 ]
    [[ "$output" == *"picked=cachegrind.out.admin_login.php.0000000001"* ]]
}

@test "await_profile_file honours PROFILE_POLL_TRIES as the retry budget" {
    _ds_run_body await_profile_file newest_profile_since <<'BODY'
# Budget of 5 x 0.01s must give up almost immediately; a run that ignored
# PROFILE_POLL_TRIES would sit here for the default 20 x 0.1s = 2s.
PROFILE_POLL_TRIES=5
PROFILE_POLL_INTERVAL=0.01
start=$(date +%s%N)
out="$(await_profile_file 9999999999 '')"
elapsed_ms=$(( ($(date +%s%N) - start) / 1000000 ))
printf 'out=[%s] under_500ms=%s\n' "$out" "$([[ $elapsed_ms -lt 500 ]] && echo yes || echo no)"
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "out=[] under_500ms=yes" ]
}

@test "validate_duration accepts siege syntax" {
    _ds_run_body validate_duration <<'BODY'
for d in 30 30s 5m 2h 1d 3600; do
    validate_duration "$d" || { echo "rejected $d"; exit 1; }
done
echo accepted
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "accepted" ]
}

@test "validate_duration rejects malformed values and shell metacharacters" {
    _ds_run_body validate_duration <<'BODY'
for d in '' 0 0s 00s 007s -5 '30; rm -rf /' '30 s' 3x 30S '$(id)' '30
'; do
    if validate_duration "$d"; then
        echo "WRONGLY ACCEPTED: [$d]"
        exit 1
    fi
done
echo all-rejected
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "all-rejected" ]
}

@test "validate_positive_integer rejects zero, negatives and non-numbers" {
    _ds_run_body validate_integer validate_positive_integer <<'BODY'
validate_positive_integer 1 || exit 1
validate_positive_integer 007 || exit 1
for v in 0 -1 '' 1.5 abc 1e3 ' '; do
    if validate_positive_integer "$v"; then echo "WRONGLY ACCEPTED: [$v]"; exit 1; fi
done
echo ok
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "ok" ]
}

@test "validate_inputs rejects each bad override and dies with a reason" {
    _ds_run_body validate_integer validate_positive_integer validate_duration validate_inputs <<'BODY'
t() {
    local var="$1" val="$2"
    ( export "$var=$val"; validate_inputs >/dev/null 2>&1 ) && { echo "ACCEPTED $var=$val"; exit 1; }
    echo "rejected $var"
}
t AB_REQUESTS 0
t AB_CONCURRENCY x
t SIEGE_CONCURRENCY -2
t SIEGE_DURATION soon
t PROFILE_POLL_TRIES 0
echo done
BODY
    [ "$status" -eq 0 ]
    [ "${#lines[@]}" -eq 6 ]
    [[ "$output" == *"rejected SIEGE_DURATION"* ]]
}

@test "validate_inputs accepts a fully valid configuration" {
    _ds_run_body validate_integer validate_positive_integer validate_duration validate_inputs <<'BODY'
export AB_REQUESTS=1000 AB_CONCURRENCY=10 SIEGE_CONCURRENCY=10
export SIEGE_DURATION=60s PROFILE_POLL_TRIES=20 SITE_URL="https://example.invalid"
validate_inputs >/dev/null 2>&1 && echo ok
BODY
    [ "$status" -eq 0 ]
    [ "$output" = "ok" ]
}
