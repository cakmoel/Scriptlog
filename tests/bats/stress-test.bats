#!/usr/bin/env bats
#
# BATS suite for stress-test.sh (step-load ApacheBench stress ramp).
#
# Run:   bats tests/bats
#
# Everything is hermetic: the helper installs deterministic ab/curl mocks on
# a sandboxed PATH; real awk/bc/mktemp/date stay untouched. Assertions read
# four artifacts: exit status, merged stdout+stderr ($output), the CSV, and
# the per-invocation ab log ($AB_LOG).
#
# Determinism notes (mock fixture):
#   * pooled percentiles come from ab -g dumps with ttime = 1..N per run; every
#     measured run of a level is identical, so the pooled sample holds each
#     value v repeated HOLD_RUNS times and the pooled p95 (ms) equals the
#     value at sorted index ceil(0.95 * HOLD_RUNS * n) = ceil(index/HOLD_RUNS).
#     With a single run (-N 1) of n requests, p95 = ceil(0.95 * n) directly,
#     e.g. n=150 -> p95 = 143 ms (row value 143).
#   * per-run p95 in the report block is fixed at 28 ms; the pooled value
#     above is what the stop conditions consume.
#
# The CSV row ORDER within a level follows the sequential run loop
# (level-major then run), so positional assertions are stable here (unlike
# load-test.bats, whose concurrent workers interchange rows).

setup() {
    load 'stress-test-helper'
    st_setup
}

# ---------------------------------------------------------------------
# CLI validation
# ---------------------------------------------------------------------

@test "requires -u BASE_URL and validates target selection" {
    run bash "$STRESS_SCRIPT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -u BASE_URL is required."* ]]

    run bash "$STRESS_SCRIPT" -u https://x.test -q static
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -s STATIC_PATH is required when -q static"* ]]

    run bash "$STRESS_SCRIPT" -u https://x.test -q login
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -g LOGIN_PATH is required when -q login"* ]]

    run bash "$STRESS_SCRIPT" -u https://x.test -q 404
    [ "$status" -eq 1 ]
    [[ "$output" == *"must be one of: dynamic, static, login"* ]]
}

@test "rejects non-integer and out-of-order numeric flags" {
    st_run -r "$STRESS_OUT" -C abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-C (max concurrency ceiling) must be an integer, got 'abc'"* ]]

    st_run -r "$STRESS_OUT" -L abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-L (p95 latency budget) must be an integer number of ms"* ]]

    st_run -r "$STRESS_OUT" -z abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"must be 'auto' or a positive integer, got 'abc'"* ]]

    st_run -r "$STRESS_OUT" -e 150
    [ "$status" -eq 1 ]
    [[ "$output" == *"must be between 0 and 100, got '150'"* ]]

    st_run -r "$STRESS_OUT" -c 10 -C 5
    [ "$status" -eq 1 ]
    [[ "$output" == *"starting concurrency (10) exceeds -C ceiling (5)"* ]]
}

@test "rejects malformed -u (scheme, host) like load-test.sh" {
    run bash "$STRESS_SCRIPT" -u ftp://x -r "$STRESS_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"must start with http:// or https://"* ]]

    run bash "$STRESS_SCRIPT" -u https:// -r "$STRESS_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"missing a host component"* ]]
}

@test "refuses to overwrite an existing CSV without -f and allows it with -f" {
    printf 'x,x\n' > "$STRESS_OUT"
    st_run -c 2 -z 2 -i 6 -L 500 -b 4 -N 1 -p 0 -r "$STRESS_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"output file already exists"* ]]

    st_run -c 2 -z 2 -i 6 -L 500 -b 4 -N 1 -p 0 -f -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    [[ "$(st_csv_rows "$STRESS_OUT")" == "3" ]]
}

# ---------------------------------------------------------------------
# --dry-run (no network I/O, no files)
# ---------------------------------------------------------------------

@test "dry-run prints the resolved auto-doubled ramp and creates nothing" {
    st_run -c 5 -C 200 -z auto -b 7 -L 1500 -e 2.5 -r "$STRESS_OUT" --dry-run
    [ "$status" -eq 0 ]
    [[ "$output" == *"ramp levels      : 5 10 20 40 80 160 200"* ]]
    [[ "$output" == *"run size         : per level max(7, level) requests per ab run"* ]]
    [[ "$output" == *"error rate     : combined > 2.5%"* ]]
    [[ "$output" == *"latency budget : pooled p95 > 1500ms"* ]]
    [[ "$output" == *"output csv       : $STRESS_OUT"* ]]
    [ ! -e "$STRESS_OUT" ]
    [ ! -e "$STRESS_OUT.meta.json" ]
    [ "$(wc -l < "$AB_LOG")" -eq 0 ]
}

@test "dry-run resolves an explicit integer step and custom ceiling" {
    st_run -c 10 -C 30 -z 10 -r "$STRESS_OUT" --dry-run
    [ "$status" -eq 0 ]
    [[ "$output" == *"ramp levels      : 10 20 30"* ]]
}

# ---------------------------------------------------------------------
# A healthy run end-to-end (mocked)
# ---------------------------------------------------------------------

@test "run executes warm-up and per-level ab with floored run sizes" {
    st_run -c 2 -z 2 -C 4 -N 1 -b 6 -L 5000 -p 0 -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    # Warm-up: -W 3 requests at concurrency 5 (kept from load-test.sh convention).
    grep -q "ab -n 3 -c 5 https://example.test/dynamic-page.php" "$AB_LOG"
    # Measured runs carry ab -g before the URL: level 4 bulks to max(6, 4)=6.
    grep -q "ab -n 6 -c 4 -g " "$AB_LOG"
    # Level 2 also floors to 6.
    grep -q "ab -n 6 -c 2 -g " "$AB_LOG"
    [ "$(st_csv_rows "$STRESS_OUT")" -eq 2 ]
    [[ "$output" == *"VERDICT: NO-BREAKDOWN ceiling reached"* ]]
    [[ "$output" == *"Peak steady throughput ~ 1234.6 rps at level 2."* ]]
}

@test "CSV rows carry the full shared schema plus level/run columns" {
    st_run -c 2 -z auto -C 8 -N 2 -b 10 -L 5000 -p 0 -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    header=$(head -1 "$STRESS_OUT")
    for col in timestamp label level run_index endpoint url concurrency requests_sent complete_requests failed_requests rps mean_latency_ms p50_ms p95_ms p99_ms non2xx_requests connect_errors receive_errors length_errors exceptions ab_exit; do
        grep -q "$col" <<< "$header"
    done
    # Rows: levels 2,4,8 x (N=2 runs) = 6 data rows.
    [ "$(st_csv_rows "$STRESS_OUT")" -eq 6 ]
    lvl4_row=$(awk -F',' 'NR > 1 && $3 == 4 { print; exit }' "$STRESS_OUT")
    [[ "$lvl4_row" == *",4,1,dynamic,https://example.test/dynamic-page.php,4,10,10,0,1234.56,"* ]]
}

@test "meta.json sidecar is written even when -S strict pre-flight aborts" {
    CURL_TRANSPORT_FAIL=1 st_run -c 2 -i 3 -L 5000 -b 6 -N 1 -p 0 -S -r "$STRESS_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"STRICT MODE (-S): aborting"* ]]
    # Header-written but zero measured rows (same convention as load-test).
    [ "$(st_csv_rows "$STRESS_OUT")" -eq 0 ]
    # Provenance exists BEFORE measurement (so even an aborted run is auditable).
    grep -q '"script": "stress-test.sh"' "$STRESS_OUT.meta.json"
    grep -q '"target_endpoint": "dynamic"' "$STRESS_OUT.meta.json"
}

@test "static and login targets pre-flight their own endpoints" {
    st_run -q static -s /assets/app.css -r "$STRESS_OUT" -c 2 -i 3 -L 5000 -b 6 -N 1 -p 0
    [ "$status" -eq 0 ]
    [[ "$output" == *"https://example.test/assets/app.css -> HTTP 200"* ]]
}

# ---------------------------------------------------------------------
# Stop conditions
# ---------------------------------------------------------------------

@test "latency budget stop (-L) halts the ramp and reports SATURATED" {
    # Single run (-N 1) of n requests gives pooled p95 = ceil(0.95 * n) because
    # the mock's ttime rows are 1..n: with -b 150 -> p95 = 143 ms. Tight -L 100
    # stops the ramp at the very first level.
    st_run -c 2 -z auto -C 100 -N 1 -b 150 -L 100 -p 0 -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"VERDICT: SATURATED latency budget at level 2"* ]]
    [[ "$output" == *"Pooled p95 crossed the -L latency budget"* ]]
    # Only level 2 executed.
    [ "$(st_csv_rows "$STRESS_OUT")" -eq 1 ]
    awk -F',' 'NR == 2 { c = $7 } END { print c }' "$STRESS_OUT" | grep -q '^2$'
}

@test "error-rate stop (-e) halts the ramp and reports BREAKING-POINT" {
    # 3 runs x 6 requests = 18 sent per level, 2 non-2xx per run = 6 -> 33.33%
    AB_NON2XX=2 st_run -c 2 -z auto -C 100 -N 3 -b 6 -e 5 -p 0 -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"VERDICT: BREAKING-POINT error saturation at level 2"* ]]
    [[ "$output" == *"combined error rate crossed the -e ceiling"* ]]
    [ "$(st_csv_rows "$STRESS_OUT")" -eq 3 ]
}

@test "throughput collapse stops the ramp after a genuine peak" {
    # Levels 5(rps 1000), 10(1000), 20(100): 100 < 0.5*1000 -> COLLAPSED.
    AB_RPS_BY_C="5=1000,10=1000,20=100" \
        st_run -c 5 -z auto -C 100 -N 1 -b 20 -L 5000 -p 0 -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"VERDICT: COLLAPSED throughput at level 20"* ]]
    [[ "$output" == *"Mean throughput halved against the best level"* ]]
    [ "$(st_csv_rows "$STRESS_OUT")" -eq 3 ]
}

@test "non-2xx responses are captured in CSV and counted in the error rate" {
    AB_NON2XX=1 st_run -c 2 -z auto -C 4 -N 2 -b 10 -e 100 -p 0 -L 5000 -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    # No BREAKING-POINT expected against the 100% permissive gate, but the
    # WARN on the clean-exit-with-non2xx path fires.
    [[ "$output" == *"clean ab exit but 1 non-2xx response(s)"* ]]
    n2xx_sum=$(awk -F',' 'NR > 1 { s += $16 } END { print s }' "$STRESS_OUT")
    [ "$n2xx_sum" -eq 4 ]
}

# ---------------------------------------------------------------------
# Serving degradations (mock ab failures)
# ---------------------------------------------------------------------

@test "server-down runs are recorded with all requests failed, never dropped" {
    CURL_TRANSPORT_FAIL=1 AB_MODE=total-fail \
        st_run -c 2 -z auto -C 4 -N 1 -b 6 -e 99 -p 0 -L 5000 -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"ab exited 1 with no report"* ]]
    # ERR gate is permissive (99) so the run completes; error rate must show it.
    row=$(awk -F',' 'NR > 1 { print; exit }' "$STRESS_OUT")
    [[ "$row" == *",6,0,6,0,"* ]]  # sent=6 complete=0 failed=6 rps=0
    [[ "$output" == *"VERDICT: BREAKING-POINT error saturation"* ]]
}

@test "partial ab report (nonzero exit with data) is surfaced with a WARN" {
    AB_MODE=partial st_run -c 2 -z auto -C 4 -N 1 -b 6 -e 99 -L 5000 -p 0 -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"ab exited 1 with a PARTIAL report (complete=6, failed=0)"* ]]
    [ "$(st_csv_rows "$STRESS_OUT")" -eq 2 ]
}

@test "client-side connect errors trigger the ulimit advisory" {
    # AB_CONNERR=2 marks 2 of the 6 floor-size requests as connect failures:
    # Both level 2 and level 4 complete against the permissive -e 99 gate, each
    # logging 2 connect errors in column 17 alongside the ADVISORY on stderr.
    AB_CONNERR=2 st_run -c 2 -z auto -C 4 -N 1 -b 6 -e 99 -L 5000 -p 0 -r "$STRESS_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"ADVISORY  level 2 run 1: 2 connect error(s). On stressed clients the LOCAL"* ]]
    [[ "$output" == *"ulimit -n (open file descriptors) can be the bottleneck"* ]]
    conn_sum=$(awk -F',' 'NR > 1 { s += $17 } END { print s }' "$STRESS_OUT")
    [ "$conn_sum" -eq 4 ]
    # The 2 failed requests per level are still counted as error-rate input.
    failed_sum=$(awk -F',' 'NR > 1 { s += $10 } END { print s }' "$STRESS_OUT")
    [ "$failed_sum" -eq 4 ]
}
