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
    grep -q '"ab_version": "ApacheBench/2.4.41' "$meta"
}

@test "metadata sidecar records the keep-alive flag passed to ab" {
    # -K was given above, so ab_extra_flags must list exactly -k. The previous
    # assertion used a tautological `a || b` pair that matched regardless.
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -K -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    grep -q '"ab_extra_flags": \["-k"\],' "${LOAD_TEST_OUT}.meta.json"

    # Without -K the array must be genuinely empty, not merely "not -k".
    local plain="${BATS_TEST_TMPDIR}/plain.csv"
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$plain"
    [ "$status" -eq 0 ]
    grep -q '"ab_extra_flags": \[\],' "${plain}.meta.json"
}

@test "metadata sidecar is parseable JSON with the documented key set" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -l "site" -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    command -v php >/dev/null 2>&1 || skip "php not available for JSON validation"

    local meta="${LOAD_TEST_OUT}.meta.json"
    run php -r '
        $d = json_decode(file_get_contents($argv[1]), true);
        if ($d === null) { fwrite(STDERR, "invalid JSON: " . json_last_error_msg() . PHP_EOL); exit(1); }
        $need = ["generated_at_utc","host","script","target_base_url","site_label",
                 "endpoints","rounds","total_concurrency","ab_requests_base",
                 "min_round_samples","warmup_requests","weights","keep_alive",
                 "strict_preflight","max_error_rate_pct","run_seed","ab_extra_flags",
                 "ab_version","kernel"];
        foreach ($need as $k) {
            if (!array_key_exists($k, $d)) { fwrite(STDERR, "missing key: $k" . PHP_EOL); exit(1); }
        }
        if ($d["script"] !== "load-test.sh") { fwrite(STDERR, "wrong script key" . PHP_EOL); exit(1); }
        if ($d["max_error_rate_pct"] !== null) { fwrite(STDERR, "gate should be null" . PHP_EOL); exit(1); }
        exit(0);
    ' "$meta"
    [ "$status" -eq 0 ]
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

    # The WARN must be emitted for every scenario (both the per-run counter and
    # the summary aggregation) instead of silently yielding zero samples.
    # Count OCCURRENCES, not lines. Each warning is emitted by awk to
    # /dev/stderr, which is unbuffered, while the surrounding stdout is
    # block-buffered. The two streams therefore interleave unpredictably and
    # two warnings can end up sharing one line, so a line count flapped between
    # 7 and 8 for identical runs. The number of warnings is what matters.
    [ "$(grep -o 'WARN: ab -g header layout unrecognized' <<< "$output" | wc -l)" -eq 8 ]
    [[ "$output" == *'using $(NF - 1) fallback'* ]]
    [[ "$output" == *"-> global percentiles over 30 pooled raw samples (ttime ms): p50=15.000 p95=29.000 p99=30.000"* ]]

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

# ---------------------------------------------------------------------
# workload sizing (weight matrix + floors) in the REAL run loop, not just
# the dry-run arithmetic
# ---------------------------------------------------------------------

@test "measured request counts and concurrency follow the weight matrix" {
    # base 100, weights 45/40/5/10, floor 2: the weight math is the only
    # binding constraint, so each ab invocation must carry the exact
    # (requests, concurrency) pair derived from TOTAL_CONCURRENCY=20.
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -b 100 -m 2 -w 45,40,5,10 \
        -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [ "$(grep -c 'ab -n 45 -c 9 -g ' "$AB_LOG")" -eq 1 ]
    [ "$(grep -c 'ab -n 40 -c 8 -g ' "$AB_LOG")" -eq 1 ]
    [ "$(grep -c 'ab -n 5 -c 1 -g ' "$AB_LOG")" -eq 1 ]
    [ "$(grep -c 'ab -n 10 -c 2 -g ' "$AB_LOG")" -eq 1 ]

    local row
    row=$(_lt_row "Login")
    [[ "$row" == *,1,Login,*,1,5,5,0,* ]]
}

@test "zero-weight scenarios fall back to the concurrency and request floors" {
    # 100% of the traffic on Static: the other three collapse to
    # concurrency 1 and request count 2 (the hard floor ab needs for a
    # percentile table, which -m 1 alone would not provide).
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -b 40 -m 1 -w 100,0,0,0 \
        -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [ "$(grep -c 'ab -n 40 -c 20 -g ' "$AB_LOG")" -eq 1 ]
    [ "$(grep -c 'ab -n 2 -c 1 -g ' "$AB_LOG")" -eq 3 ]

    local row
    row=$(_lt_row "404")
    [[ "$row" == *,1,404,*,1,2,2,0,* ]]
    row=$(_lt_row "Static")
    [[ "$row" == *,1,Static,*,20,40,40,0,* ]]
}

@test "min-samples floor warning names every scenario it overrides" {
    # Default weights with base 40 yield 18/16/2/4 requests per round, all
    # below MIN_ROUND_SAMPLES=30, so every scenario must be reported.
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [[ "$output" == *"WARN  Static: weight-based request count (18) < MIN_ROUND_SAMPLES (30) -> using 30 req/round"* ]]
    [[ "$output" == *"WARN  Dynamic: weight-based request count (16) < MIN_ROUND_SAMPLES (30) -> using 30 req/round"* ]]
    [[ "$output" == *"WARN  Login: weight-based request count (2) < MIN_ROUND_SAMPLES (30) -> using 30 req/round"* ]]
    [[ "$output" == *"WARN  404: weight-based request count (4) < MIN_ROUND_SAMPLES (30) -> using 30 req/round"* ]]
}

# ---------------------------------------------------------------------
# percentile aggregation
# ---------------------------------------------------------------------

@test "global percentiles pool raw samples across every round" {
    # Two rounds x 30 requests = 60 pooled samples. The mock writes ttime
    # 1..30 per round, so the pooled series is 1,1,2,2,...,30,30 and
    # nearest-rank gives p50=v[30]=15, p95=v[57]=29, p99=v[60]=30.
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 2 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [ "$(grep -c 'global percentiles over 60 pooled raw samples (ttime ms): p50=15.000 p95=29.000 p99=30.000' <<< "$output")" -eq 4 ]
}

@test "per-round CSV percentiles are preserved alongside the pooled figure" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    local row
    row=$(_lt_row "Static")
    # ab's own percentile table (p50=12, p95=20, p99=25) must NOT be confused
    # with the pooled raw percentiles computed in the summary.
    [[ "$row" == *,12,20,25,* ]]
    [[ "$output" == *"global percentiles over 30 pooled raw samples (ttime ms): p50=15.000 p95=29.000 p99=30.000"* ]]
}

# ---------------------------------------------------------------------
# CSV structural integrity
# ---------------------------------------------------------------------

@test "every CSV row carries the full 21-column schema" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 2 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    # Concurrent append()s are documented as safe below PIPE_BUF; this asserts
    # that no row was torn or interleaved, i.e. NF is uniform and the
    # round/scenario columns stay well-formed.
    [ -z "$(awk -F, 'NF != 21 { print NR ": " NF }' "$LOAD_TEST_OUT")" ]
    [ "$(awk -F, 'NR>1 && $3 !~ /^[0-9]+$/' "$LOAD_TEST_OUT" | wc -l)" -eq 0 ]
    [ "$(awk -F, 'NR>1 && $3 != 1 && $3 != 2' "$LOAD_TEST_OUT" | wc -l)" -eq 0 ]
    [ "$(awk -F, 'NR>1 && $4 !~ /^(Static|Dynamic|Login|404)$/' "$LOAD_TEST_OUT" | wc -l)" -eq 0 ]
}

# ---------------------------------------------------------------------
# label / path hygiene
# ---------------------------------------------------------------------

@test "site labels are stripped of CSV field separators and quotes" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -l 'we"ird,label' -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    local row
    row=$(_lt_row "Static")
    # Commas become semicolons; double quotes are dropped entirely.
    [[ "$row" == *,weird\;label,1,Static,* ]]
    [[ "$output" == *"Test Summary - weird;label (1 rounds, mixed concurrent traffic)"* ]]

    # The default filename path uses the same sanitized label.
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -g /login.php -i 1 -W 5 -R 7 -l 'a,b' --dry-run
    [ "$status" -eq 0 ]
    [[ "$output" == *"output csv       : ./results_a;b_"*".csv"* ]]
}

@test "-n overrides the not-found path and normalizes it" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -n this-page-is-not-real-alt \
        -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    # Mock curl answers 404 for any URL containing this-page-is-not-real.
    # The replacement path is 45 chars, i.e. exactly the status column width,
    # so no padding is emitted before the arrow.
    [[ "$output" == *"OK    404      https://example.test/this-page-is-not-real-alt -> HTTP 404 (expected 404)"* ]]
    [[ "$output" != *"this-page-is-not-real/this-page-is-not-real"* ]]

    local row
    row=$(_lt_row "404")
    [[ "$row" == *,1,404,https://example.test/this-page-is-not-real-alt,2,30,30,0,* ]]
}

# ---------------------------------------------------------------------
# dependency guard and help
# ---------------------------------------------------------------------

@test "the declared dependency guard fires before any other validation" {
    local stripped
    for tool in ab curl awk bc; do
        stripped=$(lt_stripped_path "$tool")
        PATH="$stripped" run "$BASH" "$LOAD_TEST_SCRIPT" -u https://x.test -s /a.css -g /l.php
        [ "$status" -eq 1 ]
        [[ "$output" == *"ERROR: required tool '$tool' not found in PATH."* ]]
        # The guard precedes argument validation and any I/O.
        [[ "$output" != *"-g LOGIN_PATH is required"* ]]
    done
}

@test "-h prints usage and exits 1" {
    # usage() always terminates with exit 1, even for the explicit help flag.
    run bash "$LOAD_TEST_SCRIPT" -h
    [ "$status" -eq 1 ]
    [[ "$output" == *"Usage:"* ]]
    [[ "$output" == *"  -u  Base URL"* ]]
    [[ "$output" == *"  -e  CI gate"* ]]
    [[ "$output" == *"--relax-min-samples"* ]]
}

# ---------------------------------------------------------------------
# warm-up phase
# ---------------------------------------------------------------------

@test "warm-up failures are surfaced but never abort the run" {
    AB_MODE=total-fail run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [ "$(grep -c 'WARN  warm-up run exited nonzero' <<< "$output")" -eq 4 ]
    [[ "$output" == *"Warm-up finished with warnings (see stderr)."* ]]
    [[ "$output" == *"=== Warm-up: 5 requests per endpoint ==="* ]]
}

@test "warm-up is fired concurrently per endpoint with a fixed -c 5 budget" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 9 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    # -W scales the request count only; concurrency stays at 5 and no -g dump
    # is requested for unmeasured warm-up traffic.
    [ "$(grep -c 'ab -n 9 -c 5 ' "$AB_LOG")" -eq 4 ]
    [ "$(grep -c 'ab -n 9 -c 5 -g ' "$AB_LOG")" -eq 0 ]
}

# ---------------------------------------------------------------------
# raw-data retention hook
# ---------------------------------------------------------------------

@test "LOAD_TEST_KEEP_RAW=1 preserves the workdir and reports its path" {
    LOAD_TEST_KEEP_RAW=1 run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    local workdir
    workdir=$(sed -n 's/^NOTE: LOAD_TEST_KEEP_RAW=1 -> raw data retained in //p' <<< "$output")
    [ -n "$workdir" ]
    [ -d "$workdir" ]
    # The per-run ab -g dumps are what makes independent re-analysis possible.
    [ -f "${workdir}/raw_Static_1.tsv" ]
    [ -f "${workdir}/raw_404_1.tsv" ]
    rm -rf "$workdir"
}

@test "the workdir is removed by default" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" != *"LOAD_TEST_KEEP_RAW"* ]]
}

# ---------------------------------------------------------------------
# dry-run side-effect freedom
# ---------------------------------------------------------------------

@test "dry-run writes neither the CSV nor the metadata sidecar" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 --dry-run -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [ ! -e "$LOAD_TEST_OUT" ]
    [ ! -e "${LOAD_TEST_OUT}.meta.json" ]
    [ ! -s "$AB_LOG" ]
}

# ---------------------------------------------------------------------
# CI gate arithmetic
# ---------------------------------------------------------------------

@test "-e accepts a fractional ceiling and echoes it in the verdict" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -e 2.5 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Error-rate gate passed: combined 0.00% <= 2.5% (-e)."* ]]
}

@test "-e gate fails with a full abort when every scenario fails" {
    # 4 scenarios x 30 requests sent, 30 counted failed each: combined 100%.
    AB_MODE=total-fail run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -e 50 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR-RATE GATE FAILED: combined error rate 100.00% exceeds -e ceiling of 50%."* ]]
    [[ "$output" == *"transport-err=30 (100.00%) non2xx=0 (0.00%) combined=100.00%"* ]]
}

@test "-e gate counts non-2xx responses on top of transport failures" {
    # 3 non-2xx per scenario x 4 = 12 out of 120 sent -> combined 10%.
    AB_NON2XX=3 run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 1 -W 5 -R 7 -e 9 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR-RATE GATE FAILED: combined error rate 10.00% exceeds -e ceiling of 9%."* ]]
}

# ---------------------------------------------------------------------
# progress reporting
# ---------------------------------------------------------------------

@test "progress is reported on every tenth round and on the last round only" {
    run bash "$LOAD_TEST_SCRIPT" -u https://example.test -s /assets/app.css \
        -d /dynamic-page.php -g /admin/login.php -i 10 -W 5 -R 7 -r "$LOAD_TEST_OUT"
    [ "$status" -eq 0 ]

    [ "$(grep -cE '^  round [0-9]+/10 complete$' <<< "$output")" -eq 1 ]
    [[ "$output" == *"  round 10/10 complete"* ]]
    [ "$(wc -l < "$LOAD_TEST_OUT")" -eq 41 ]
}

# ---------------------------------------------------------------------
# end-to-end against a real fixture (opt-in: LOAD_TEST_E2E=1)
#
# Every other test mocks ab/curl. This one restores the pristine PATH so the
# real ApacheBench and curl drive load-test.sh against a local `php -S`
# server, proving the mocks match the tools' actual output formats.
# ---------------------------------------------------------------------

@test "e2e: real ab and curl produce a valid CSV and JSON sidecar" {
    [ "${LOAD_TEST_E2E:-}" = "1" ] || skip "set LOAD_TEST_E2E=1 to enable"
    command -v php >/dev/null 2>&1 || skip "php not available"
    command -v ab >/dev/null 2>&1 || skip "apachebench not installed"

    local info port docroot srv out
    info=$(lt_start_php_fixture) || skip "could not start the php fixture"
    read -r port docroot srv <<< "$info"

    out="${BATS_TEST_TMPDIR}/e2e.csv"
    PATH="$LT_REAL_PATH" run bash "$LOAD_TEST_SCRIPT" \
        -u "http://127.0.0.1:${port}" \
        -s /assets/app.css \
        -d /dynamic.php \
        -g /login.php \
        -n /no-such-page-here \
        -i 1 -W 2 -b 10 -m 2 -w 50,40,5,5 -R 4242 -e 100 -r "$out"

    kill "$srv" 2>/dev/null || true

    [ "$status" -eq 0 ]
    [ -s "$out" ]
    [ "$(awk -F, 'NF != 21' "$out" | wc -l)" -eq 0 ]
    [ "$(awk 'END { print NR }' "$out")" -eq 5 ]

    # Real ab output, parsed by load-test.sh: every scenario completed, so
    # there are zero transport failures and a non-zero raw sample count
    # (proof the ab -g header/ttime layout assumption holds for this ab).
    [ "$(awk -F, 'NR>1 && $9 != 0' "$out" | wc -l)" -eq 0 ]
    [ "$(awk -F, 'NR>1 && $21 <= 0' "$out" | wc -l)" -eq 0 ]

    # Weight matrix applied to the measured loop: static req 5 lifted to its
    # concurrency floor of 10, dynamic 4 lifted to 8, login/404 floored to 2.
    [ "$(awk -F, 'NR>1 && ($4 == "Static")  && ($6 == 10 && $7 == 10) { n++ } END { print n+0 }' "$out")" -eq 1 ]
    [ "$(awk -F, 'NR>1 && ($4 == "Dynamic") && ($6 == 8  && $7 == 8)  { n++ } END { print n+0 }' "$out")" -eq 1 ]
    [ "$(awk -F, 'NR>1 && ($4 == "Login")   && ($6 == 1  && $7 == 2)  { n++ } END { print n+0 }' "$out")" -eq 1 ]
    [ "$(awk -F, 'NR>1 && ($4 == "404")     && ($6 == 1  && $7 == 2)  { n++ } END { print n+0 }' "$out")" -eq 1 ]

    # The fixture's missing page answers HTTP 404: real ab reports those under
    # "Non-2xx responses", NOT under "Failed requests". Both the column and
    # the non-strict pre-flight WARN must reflect that distinction.
    [ "$(awk -F, 'NR>1 && $4 == "404" && $15 == 2 && $9 == 0 { n++ } END { print n+0 }' "$out")" -eq 1 ]
    [[ "$output" == *"WARN  round 1 404: clean ab exit but 2 non-2xx response(s)"* ]]
    [[ "$output" == *"pooled raw samples"* ]]
    [[ "$output" == *"Error-rate gate passed"* ]]

    php -r 'exit(json_decode(file_get_contents($argv[1]), true) === null ? 1 : 0);' \
        "${out}.meta.json"
}