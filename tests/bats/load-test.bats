#!/usr/bin/env bats
#
# BATS suite for load-test.sh (mixed-concurrency ApacheBench load tester).
#
# Run:   bats tests/bats
#
# load-test.sh is a top-level shell script with no guardable main, so every
# test exercises it black-box (real process) rather than sourcing it. The
# load-test-helper installs deterministic mocks for ab and curl on a
# sandboxed PATH; real awk/bc/mktemp/date stay untouched. Assertions read
# three artifacts: exit status, merged stdout+stderr ($output), the CSV at -r,
# the per-invocation ab log ($AB_LOG), and the metadata JSON sidecar.
#
# Deterministic fixture (lt_run default args, base 40, weights 45/40/5/10):
#   MIN_ROUND_SAMPLES 30 floors every request count to 30; per-scenario
#   concurrency is static=9, dynamic=8, login=1, notfound=2. Mock ab emits
#   request counts run1..N -> nearest-rank percentiles p50/p95/p99 are fixed
#   and points at 15/29/30 for a 30-sample round. CSV data rows are appended
#   by 4 concurrent workers, so row ORDER is nondeterministic: tests select
#   rows by scenario (field 4) and reduce reproducibility to sorted columns.

setup() {
    load 'load-test-helper'
    lt_setup
    LOAD_TEST_OUT="${BATS_TEST_TMPDIR}/out.csv"
}

# Return the CSV data row (header excluded) for a scenario (field 4).
_lt_row() {
    awk -F, -v s="$1" 'NR > 1 && $4 == s { print; exit }' "$LOAD_TEST_OUT"
}

# ---------------------------------------------------------------------
# CLI validation (P2/P6 - usage errors, type/range/bounds checking)
# ---------------------------------------------------------------------

@test "requires -u, -s and -g" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /a.css
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -g LOGIN_PATH is required."* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -g /l.php
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -s STATIC_PATH is required"* ]]

    run bash "$LOAD_TEST_SCRIPT" -s /a.css -g /l.php
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -u BASE_URL is required."* ]]
}

@test "rejects non-integer -i, -b, -c, -W and -m" {
    for flag in i b c W m; do
        run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -"$flag" abc -r "$LOAD_TEST_OUT"
        [ "$status" -eq 1 ]
        [[ "$output" == *"must be an integer"* ]]
        [[ "$output" == *"got 'abc'"* ]]
    done
}

@test "rejects zero and negative concurrency/request flags" {
    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -W 0 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"-W (warm-up requests per endpoint) must be >= 1"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -c 0 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"-c (total concurrency) must be >= 1, got '0'"* ]]
}

@test "rejects out-of-range and non-numeric -e" {
    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -e 150 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"-e (max error rate) must be between 0 and 100, got '150'"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -e abc -r "$LOAD_TEST_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"must be a number between 0 and 100, got 'abc'"* ]]
}

@test "rejects non-integer -R seed" {
    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -R abc -r "$LOAD_TEST_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"-R (seed) must be a non-negative integer, got 'abc'"* ]]
}

@test "rejects -w with wrong field count, non-integers, negatives or bad sum" {
    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -w 25,25,25
    [ "$status" -eq 1 ]
    [[ "$output" == *"-w expects exactly 4 comma-separated values"* ]]
    [[ "$output" == *"got 3: '25,25,25'"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -w a,b,c,d
    [ "$status" -eq 1 ]
    [[ "$output" == *"is not a non-negative integer"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -w 25,25,25,26
    [ "$status" -eq 1 ]
    [[ "$output" == *"weights must sum to 100 (got 101: 25,25,25,26)"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -w 25,25,25,-25
    [ "$status" -eq 1 ]
    [[ "$output" == *"is not a non-negative integer"* ]]
}

@test "rejects malformed -u (scheme, host, trailing) and normalizes paths" {
    run bash "$LOAD_TEST_SCRIPT" -u ftp://x -s /a.css -g /l.php
    [ "$status" -eq 1 ]
    [[ "$output" == *"must start with http:// or https://"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https:// -s /a.css -g /l.php
    [ "$status" -eq 1 ]
    [[ "$output" == *"missing a host component"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://x.test/ -s a.css -g l.php -n nope --dry-run -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"target           : https://x.test"* ]]
    [[ "$output" == *"/a.css"* ]]
    if [[ "$output" == *"static         : a.css"* ]]; then
        echo "static path was not normalized to a leading slash" >&2
        return 1
    fi
}

@test "unknown flags are rejected via getopts" {
    run bash "$LOAD_TEST_SCRIPT" -Z
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: unknown option -Z."* ]]
}

# ---------------------------------------------------------------------
# output-file handling (P8)
# ---------------------------------------------------------------------

@test "refuses an existing output file unless -f is given" {
    : > "$LOAD_TEST_OUT"
    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -r "$LOAD_TEST_OUT" --dry-run
    [ "$status" -eq 1 ]
    [[ "$output" == *"output file already exists"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php -r "$LOAD_TEST_OUT" --dry-run -f
    [ "$status" -eq 0 ]
}

@test "creates nested output directories for -r" {
    local nested="${BATS_TEST_TMPDIR}/deep/a/b/out.csv"
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /a.css -g /l.php \
        -i 1 -W 5 -R 7 -r "$nested"
    [ "$status" -eq 0 ]
    [ -f "$nested" ]
    [ -f "${nested}.meta.json" ]
}

@test "default output naming follows results_label_timestamp.csv" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /a.css -g /l.php \
        -i 1 -W 5 -R 7 -l "my label" --dry-run
    [ "$status" -eq 0 ]
    [[ "$output" == *"output csv       : ./results_my_label_"*".csv"* ]]
}

# ---------------------------------------------------------------------
# dry-run (no ab/curl invoked; workload math visible)
# ---------------------------------------------------------------------

@test "dry-run prints the planned workload and executes nothing" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 12345 --dry-run -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"DRY RUN - planned workload for 'site'"* ]]
    [[ "$output" == *"target           : https://example.test"* ]]
    [[ "$output" == *"static         : /assets/app.css   weight=45  -> 30 req/round  concurrency=9"* ]]
    [[ "$output" == *"dynamic        : /dynamic-page.php   weight=40  -> 30 req/round  concurrency=8"* ]]
    [[ "$output" == *"login          : /admin/login.php   weight=5  -> 30 req/round  concurrency=1"* ]]
    [[ "$output" == *"notfound       : /this-page-is-not-real   weight=10  -> 30 req/round  concurrency=2"* ]]
    [[ "$output" == *"rounds           : 1"* ]]
    [[ "$output" == *"total concurrency: 20"* ]]
    [[ "$output" == *"min samples/round: 30"* ]]
    [[ "$output" == *"warm-up          : 5 req x 4 endpoints (unmeasured)"* ]]
    [[ "$output" == *"seed             : 12345   (reproduce with -R 12345)"* ]]
    [[ "$output" == *"endpoint reachability is NOT validated"* ]]

    [ ! -s "$AB_LOG" ]
}

@test "dry-run honours custom -m (lower floor exposes weight math)" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 \
        -m 5 -w 50,25,12,13 --dry-run -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"static         : /assets/app.css   weight=50  -> 20 req/round  concurrency=10"* ]]
    [[ "$output" == *"dynamic        : /dynamic-page.php   weight=25  -> 10 req/round  concurrency=5"* ]]
    [[ "$output" == *"login          : /admin/login.php   weight=12  -> 5 req/round  concurrency=2"* ]]
    [[ "$output" == *"notfound       : /this-page-is-not-real   weight=13  -> 5 req/round  concurrency=2"* ]]
}

@test "dry-run honours --relax-min-samples (floor 10 keeps weight math)" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 \
        --relax-min-samples --dry-run -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"min samples/round: 10"* ]]
    [[ "$output" == *"static         : /assets/app.css   weight=45  -> 18 req/round  concurrency=9"* ]]
    [[ "$output" == *"dynamic        : /dynamic-page.php   weight=40  -> 16 req/round  concurrency=8"* ]]
    [[ "$output" == *"login          : /admin/login.php   weight=5  -> 10 req/round  concurrency=1"* ]]
    [[ "$output" == *"notfound       : /this-page-is-not-real   weight=10  -> 10 req/round  concurrency=2"* ]]

    [ ! -s "$AB_LOG" ]
}

@test "--relax-min-samples never overrides an explicit -m" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 \
        --relax-min-samples -m 5 -w 50,25,12,13 --dry-run -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"min samples/round: 5"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 \
        -m 5 -w 50,25,12,13 --relax-min-samples --dry-run -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"min samples/round: 5"* ]]
}

@test "dry-run honours -b base request budget" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 \
        -b 100 --dry-run -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"static         : /assets/app.css   weight=45  -> 45 req/round  concurrency=9"* ]]
    [[ "$output" == *"dynamic        : /dynamic-page.php   weight=40  -> 40 req/round  concurrency=8"* ]]
}

@test "keep-alive and strict flags surface in dry-run plan" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /a.css -g /l.php \
        -i 1 -W 5 -R 7 -K -S --dry-run -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"keep-alive       : on"* ]]
    [[ "$output" == *"strict preflight : on"* ]]
}

# ---------------------------------------------------------------------
# clean full run (happy path)
# ---------------------------------------------------------------------

@test "full run exits 0 and fires warm-up then measured ab with right flags" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 12345 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    # warm-up: -n 5 -c 5 for each of the 4 endpoints, no -g
    [ "$(grep -c 'ab -n 5 -c 5 ' "$AB_LOG")" -eq 4 ]

    # measured: -g raw dumps with per-scenario concurrency and request count
    [ "$(grep -c 'ab -n 30 -c 9 -g ' "$AB_LOG")" -eq 1 ]
    [ "$(grep -c 'ab -n 30 -c 8 -g ' "$AB_LOG")" -eq 1 ]
    [ "$(grep -c 'ab -n 30 -c 1 -g ' "$AB_LOG")" -eq 1 ]
    [ "$(grep -c 'ab -n 30 -c 2 -g ' "$AB_LOG")" -eq 1 ]

    # one ab -V call for the metadata sidecar
    [ "$(grep -c '^ab -V$' "$AB_LOG")" -eq 1 ]
}

@test "full run writes a header plus one CSV row per scenario" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 12345 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [ "$(wc -l < "$LOAD_TEST_OUT")" -eq 5 ]

    local header
    header=$(head -n 1 "$LOAD_TEST_OUT")
    [ "$header" = "timestamp,site,round,scenario,url,concurrency,requests_sent,complete_requests,failed_requests,rps,mean_latency_ms,p50_ms,p95_ms,p99_ms,non2xx_requests,connect_errors,receive_errors,length_errors,exceptions,ab_exit,raw_samples" ]
}

@test "CSV data rows carry deterministic mock ab values per scenario" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 12345 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    # Static: concurrency 9, 30 sent / 30 complete, rps/match mock report,
    # zero taxonomy, ab exit 0, 30 raw samples -> p50=15.000/p95=29.000.
    local row
    row=$(_lt_row "Static")
    [[ "$row" == *,1,Static,https://example.test/assets/app.css,9,30,30,0,1234.56,12.345,12,20,25,0,0,0,0,0,0,30 ]]

    row=$(_lt_row "Dynamic")
    [[ "$row" == *,1,Dynamic,https://example.test/dynamic-page.php,8,30,30,0,1234.56,12.345,12,20,25,0,0,0,0,0,0,30 ]]

    row=$(_lt_row "Login")
    [[ "$row" == *,1,Login,https://example.test/admin/login.php,1,30,30,0,1234.56,12.345,12,20,25,0,0,0,0,0,0,30 ]]

    row=$(_lt_row "404")
    [[ "$row" == *,1,404,https://example.test/this-page-is-not-real,2,30,30,0,1234.56,12.345,12,20,25,0,0,0,0,0,0,30 ]]
}

@test "summary reports per-scenario totals and global raw percentiles" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 12345 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [[ "$output" == *"Test Summary - site (1 rounds, mixed concurrent traffic)"* ]]
    [[ "$output" == *"NOTE: tail latencies (p95/p99) are LOWER BOUNDS"* ]]
    [[ "$output" == *"global percentiles over 30 pooled raw samples (ttime ms): p50=15.000 p95=29.000 p99=30.000"* ]]

    for s in Static Dynamic Login 404; do
        # Scenario label is left-padded to 8 chars: "<%-8s>: rounds=..."
        [[ "$output" == *"$s"":"*"rounds=1 sent=30 complete=30 | transport-err=0 (0.00%) non2xx=0 (0.00%) combined=0.00%"* ]]
    done
    [[ "$output" == *"taxonomy: connect=0 receive=0 length=0 exceptions=0"* ]]
    [[ "$output" == *"rps mean=1234.560 sd=0.000 cv=0.0%"* ]]
}

@test "launch seed is echoed and -R reproduces identical sorted CSV rows" {
    local run1="${BATS_TEST_TMPDIR}/r1.csv" run2="${BATS_TEST_TMPDIR}/r2.csv"

    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 999 -r "$run1"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Launch-order randomization seed: 999"* ]]

    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 999 -r "$run2"
    [ "$status" -eq 0 ]

    # CSV rows are appended concurrently, so order varies across runs; strip
    # the timestamp column and compare sorted rows (cols 2..21).
    local t1 t2
    t1=$(awk 'NR > 1 { $1 = ""; sub(/^,/, ""); print }' "$run1" | sort)
    t2=$(awk 'NR > 1 { $1 = ""; sub(/^,/, ""); print }' "$run2" | sort)
    [ "$t1" = "$t2" ]
}

@test "metadata sidecar captures seed, weights, ab version and flags" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 12345 -K -e 20 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    local meta="${LOAD_TEST_OUT}.meta.json"
    [ -f "$meta" ]
    grep -q '"run_seed": 12345' "$meta"
    grep -q '"target_base_url": "https://example.test"' "$meta"
    grep -q '"rounds": 1' "$meta"
    grep -q '"total_concurrency": 20' "$meta"
    grep -q '"keep_alive": true' "$meta"
    grep -q '"max_error_rate_pct": 20' "$meta"
    grep -q '"weights": { "static": 45, "dynamic": 40, "login": 5, "notfound404": 10 }' "$meta"
    grep -q '"ab_extra_flags": \[\],' "$meta" || grep -q '"ab_extra_flags": \[' "$meta"
    grep -q '"ab_version": "ApacheBench/2.4.41' "$meta"
}

@test "-K passes -k to every ab invocation" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -K -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    # 4 warm-up + 4 measured; every one carries -k. The ab -V metadata call
    # must not carry it.
    [ "$(grep -c 'ab .*-k ' "$AB_LOG")" -eq 8 ]
    [ "$(grep -c 'ab -V' "$AB_LOG")" -eq 1 ]
}

@test "clean ab exits with non-2xx responses warn and are recorded" {
    AB_NON2XX=3 run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [[ "$output" == *"WARN  round 1 Static: clean ab exit but 3 non-2xx response(s)"* ]]
    local row
    row=$(_lt_row "Static")
    [[ "$row" == *,1,Static,*,9,30,30,0,1234.56,12.345,12,20,25,3,0,0,0,0,0,30 ]]
}

# ---------------------------------------------------------------------
# failure taxonomy (F-10/F-11/F-13/F-15 remediation)
# ---------------------------------------------------------------------

@test "ab total failure records zero complete, ab_exit=1 and raw_samples=0" {
    AB_MODE=total-fail run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [[ "$output" == *"WARN  round 1 Static: ab exited 1 with no report (apachebench: Could not connect to server on"* ]]
    [[ "$output" == *"WARN  round 1: one or more scenario workers reported failures"* ]]

    local row
    row=$(_lt_row "Static")
    [[ "$row" == *,1,Static,*9,30,0,30,0,0,0,0,0,0,0,0,0,0,1,0 ]]
}

@test "ab partial failure records numbers from the report it did emit" {
    AB_MODE=partial run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [[ "$output" == *"WARN  round 1 Static: ab exited 1 with a PARTIAL report (complete=30, failed=0)"* ]]

    local row
    row=$(_lt_row "Static")
    [[ "$row" == *,1,Static,*9,30,30,0,1234.56,12.345,12,20,25,0,0,0,0,0,1,30 ]]
}

@test "an unrecognized ab -g header warns but falls back to the last-but-one column" {
    AB_RAW_HEADER=bad run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [[ "$output" == *"WARN: ab -g header layout unrecognized; using \$(NF - 1) fallback"* ]]
    [[ "$output" == *"-> global percentiles over 30 pooled raw samples (ttime ms): p50=15.000 p95=29.000 p99=30.000"* ]]
    [[ ! "$output" == *"raw latency extraction disabled"* ]]

    local row
    row=$(_lt_row "Static")
    [[ "$row" == *,1,Static,*9,30,30,0,1234.56,12.345,12,20,25,0,0,0,0,0,0,30 ]]
}

# ---------------------------------------------------------------------
# CI error-rate gate (-e)
# ---------------------------------------------------------------------

@test "-e gate passes when combined error rate is under the ceiling" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -e 20 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Error-rate gate passed: combined 0.00% <= 20% (-e)."* ]]
}

@test "-e gate fails and exits non-zero when non-2xx exceeds the ceiling" {
    AB_NON2XX=3 run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -e 2 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR-RATE GATE FAILED: combined error rate 10.00% exceeds -e ceiling of 2%."* ]]
}

# ---------------------------------------------------------------------
# pre-flight checks
# ---------------------------------------------------------------------

@test "pre-flight passes for a healthy site and prints per-scenario status" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"  OK    Static   https://example.test/assets/app.css           -> HTTP 200 (expected 200)"* ]]
    [[ "$output" == *"  OK    404      https://example.test/this-page-is-not-real    -> HTTP 404 (expected 404)"* ]]
    [[ "$output" == *"  OK    Dynamic  https://example.test/dynamic-page.php         -> HTTP 200 (expected 200)"* ]]
    [[ "$output" == *"  OK    Login    https://example.test/admin/login.php          -> HTTP 200 (expected 200)"* ]]
}

@test "non-strict pre-flight warns but still runs when an endpoint answers 500" {
    CURL_DYNAMIC_STATUS=500 run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARN  Dynamic  https://example.test/dynamic-page.php         -> HTTP 500 (expected 200)"* ]]
    [[ "$output" == *"One or more endpoints did not pass pre-flight."* ]]
    [ "$(wc -l < "$LOAD_TEST_OUT")" -eq 5 ]
}

@test "strict pre-flight (-S) aborts with nothing measured" {
    local strict_out="${BATS_TEST_TMPDIR}/strict.csv"
    CURL_DYNAMIC_STATUS=500 run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -S -r "$strict_out"
    [ "$status" -eq 1 ]
    [[ "$output" == *"STRICT MODE (-S): aborting because one or more endpoints failed"* ]]
    [[ "$output" == *"pre-flight verification. Nothing was measured."* ]]
    [ "$(wc -l < "$strict_out")" -eq 1 ]
}

@test "transport failure during pre-flight flags FAIL and continues non-strict" {
    CURL_TRANSPORT_FAIL=1 run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"FAIL  Static   https://example.test/assets/app.css           -> transport error (curl rc=1, http_code=)"* ]]
    [[ "$output" == *"FAIL  Login    https://example.test/admin/login.php          -> transport error (curl rc=1, http_code=)"* ]]
    [ "$(wc -l < "$LOAD_TEST_OUT")" -eq 5 ]
}

# ---------------------------------------------------------------------
# CSV robustness
# ---------------------------------------------------------------------

@test "commas in query strings are sanitized to semicolons in the CSV url" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s "/a.css?x=1,2" \
        -d "/d.php?tags=a,b" -g "/login.php" -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    local row
    row=$(_lt_row "Static")
    [[ "$row" == *,1,Static,https://example.test/a.css?x=1\;2,* ]]
    if [[ "$row" == *",1,2,"* ]]; then
        echo "comma leaked into the Static url field" >&2
        return 1
    fi
}

@test "-i 2 produces two rows per scenario across two rounds" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 2 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [ "$(wc -l < "$LOAD_TEST_OUT")" -eq 9 ]
    [ "$(awk -F, 'NR>1 && $4 == "Static"' "$LOAD_TEST_OUT" | wc -l)" -eq 2 ]
    # Progress line prints on the last round (and every 10th).
    [[ "$output" == *"round 2/2 complete"* ]]
}