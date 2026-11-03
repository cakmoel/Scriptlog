#!/usr/bin/env bats
#
# BATS suite for spike-test.sh (baseline/spike/recovery cycle tester).
#
# Run:   bats tests/bats
#
# Everything is hermetic: the helper installs deterministic ab/curl mocks on
# a sandboxed PATH; real awk/bc/mktemp/date stay untouched. Assertions read
# four artifacts: exit status, merged stdout+stderr ($output), the CSV, and
# the per-invocation ab log ($SPIKE_LOG).
#
# Determinism notes (mock fixture):
#   * the CSV verdicts are computed from the REPORTED rps (column 11) and the
#     report-block p95 (column 14, fixed at 28 ms in the mock), so the mock's
#     `AB_RPS_SEQ` switch directly scripts the phase outcome in call order
#     (call 1 = warm-up, then baseline, spike, recovery per cycle);
#   * baseline and recovery invocations share identical -n/-c, which is why
#     the mock keys its answers by CALL ORDER rather than by ab arguments.
#
# CSV row ORDER within a cycle is strictly sequential (baseline -> spike ->
# recovery), so positional assertions are stable here (unlike load-test.bats,
# whose concurrent workers interchange rows).

setup() {
    load 'spike-test-helper'
    sp_setup
}

# ---------------------------------------------------------------------
# CLI validation
# ---------------------------------------------------------------------

@test "requires -u BASE_URL and validates target selection" {
    run bash "$SPIKE_SCRIPT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -u BASE_URL is required."* ]]

    run bash "$SPIKE_SCRIPT" -u https://x.test -q static
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -s STATIC_PATH is required when -q static"* ]]

    run bash "$SPIKE_SCRIPT" -u https://x.test -q login
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -g LOGIN_PATH is required when -q login"* ]]

    run bash "$SPIKE_SCRIPT" -u https://x.test -q 404
    [ "$status" -eq 1 ]
    [[ "$output" == *"must be one of: dynamic, static, login"* ]]
}

@test "rejects non-integer, zero, and out-of-range values" {
    sp_run -i abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -i (cycles) must be an integer, got 'abc'."* ]]

    sp_run -i 0
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -i (cycles) must be >= 1, got '0'."* ]]

    sp_run -B abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-B (baseline concurrency) must be an integer"* ]]

    sp_run -R -3
    [ "$status" -eq 1 ]
    [[ "$output" == *"-R (baseline requests) must be an integer, got '-3'."* ]]

    sp_run -G abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-G (spike requests) must be an integer, got 'abc'."* ]]

    sp_run -S abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-S (spike concurrency) must be an integer, got 'abc'."* ]]

    sp_run -W 0
    [ "$status" -eq 1 ]
    [[ "$output" == *"-W (warm-up requests per endpoint) must be >= 1, got '0'."* ]]

    sp_run -P abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-P (pause between cycles) must be a non-negative integer, got 'abc'."* ]]

    sp_run -e abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-e (max error rate) must be a number between 0 and 100, got 'abc'."* ]]

    sp_run -e 150
    [ "$status" -eq 1 ]
    [[ "$output" == *"-e (max error rate) must be between 0 and 100, got '150'."* ]]
}

@test "rejects a spike (-S) that does not exceed the baseline (-B)" {
    sp_run -B 10 -S 10
    [ "$status" -eq 1 ]
    [[ "$output" == *"-S spike concurrency (10) must exceed -B baseline concurrency (10)"* ]]

    sp_run -B 20 -S 5
    [ "$status" -eq 1 ]
    [[ "$output" == *"-S spike concurrency (5) must exceed -B baseline concurrency (20)"* ]]
}

@test "rejects malformed -u (scheme, host) like load-test.sh" {
    run bash "$SPIKE_SCRIPT" -u ftp://x -r "$SPIKE_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"must start with http:// or https://"* ]]

    run bash "$SPIKE_SCRIPT" -u https:// -r "$SPIKE_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"missing a host component"* ]]
}

@test "refuses to overwrite an existing CSV without -f and allows it with -f" {
    printf 'x,x\n' > "$SPIKE_OUT"
    sp_run -i 1 -B 2 -R 20 -S 10 -G 40 -r "$SPIKE_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"output file already exists"* ]]

    sp_run -i 1 -B 2 -R 20 -S 10 -G 40 -f -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    [[ "$(sp_csv_rows "$SPIKE_OUT")" == "3" ]]
}

# ---------------------------------------------------------------------
# --dry-run (no network I/O, no files)
# ---------------------------------------------------------------------

@test "dry-run prints the resolved cycle plan and creates nothing" {
    sp_run -i 2 -B 3 -R 20 -S 30 -G 80 -r "$SPIKE_OUT" --dry-run
    [ "$status" -eq 0 ]
    [[ "$output" == *"DRY RUN - planned spike cycles for 'site' (nothing was executed)"* ]]
    [[ "$output" == *"target            : https://example.test/dynamic-page.php"* ]]
    [[ "$output" == *"cycles            : 2"* ]]
    [[ "$output" == *"baseline        : 20 requests @ concurrency 3"* ]]
    [[ "$output" == *"spike           : 80 requests @ concurrency 30"* ]]
    [[ "$output" == *"recovery        : 20 requests @ concurrency 3"* ]]
    [[ "$output" == *"pause between     : 0s"* ]]
    [[ "$output" == *"spike absorbable : spike combined err <= 5.0% AND spike rps >= 25% of baseline rps"* ]]
    [[ "$output" == *"CI error gate     : pooled combined rate must stay <= 5.0%"* ]]
    [[ "$output" == *"output csv        : $SPIKE_OUT"* ]]
    [ ! -e "$SPIKE_OUT" ]
    [ ! -e "$SPIKE_OUT.meta.json" ]
    [ "$(wc -l < "$SPIKE_LOG")" -eq 0 ]
}

# ---------------------------------------------------------------------
# A healthy run end-to-end (mocked)
# ---------------------------------------------------------------------

@test "run executes warm-up then baseline/spike/recovery in call order" {
    AB_RPS_SEQ="1000,900,950" \
        sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    # Warm-up: -W 3 requests at concurrency 5 (kept from load-test.sh convention).
    grep -q "^ab -n 3 -c 5 " "$SPIKE_LOG"
    # Measured runs carry ab -g: baseline 40@5, spike 90@30, recovery 40@5.
    grep -q "ab -n 40 -c 5 -g " "$SPIKE_LOG"
    grep -q "ab -n 90 -c 30 -g " "$SPIKE_LOG"
    [ "$(grep -c 'ab -n 40 -c 5 -g ' "$SPIKE_LOG")" -eq 2 ]
    [ "$(sp_csv_rows "$SPIKE_OUT")" -eq 3 ]
    # Phase order within cycle 1.
    awk -F',' 'NR == 2 { print $4 } NR == 3 { print $4 } NR == 4 { print $4 }' "$SPIKE_OUT" | grep -q '^baseline$'
    awk -F',' 'NR == 3 { print $4 }' "$SPIKE_OUT" | grep -q '^spike$'
    awk -F',' 'NR == 4 { print $4 }' "$SPIKE_OUT" | grep -q '^recovery$'
}

@test "CSV rows carry the full shared schema with a phase column" {
    AB_RPS_SEQ="1000,900,950" \
        sp_run -i 1 -B 4 -R 40 -S 20 -G 60 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    header=$(head -1 "$SPIKE_OUT")
    for col in timestamp label cycle phase endpoint url concurrency requests_sent complete_requests failed_requests rps mean_latency_ms p50_ms p95_ms p99_ms non2xx_requests connect_errors receive_errors length_errors exceptions ab_exit; do
        grep -q "$col" <<< "$header"
    done
    # Baseline row: cycle 1, phase baseline, conc 4, requests 40, rps 1000.
    [[ $(awk -F',' 'NR == 2 { print; exit }' "$SPIKE_OUT") == *,1,baseline,dynamic,https://example.test/dynamic-page.php,4,40,40,0,1000,* ]]
    # Spike row: cycle 1, phase spike, conc 20, requests 60, rps 900.
    [[ $(awk -F',' 'NR == 3 { print; exit }' "$SPIKE_OUT") == *,1,spike,dynamic,https://example.test/dynamic-page.php,20,60,60,0,900,* ]]
}

@test "verdicts: RESILIENT when the spike is absorbed and recovery is >= 90%" {
    # spike 900 >= 25% of baseline 1000; recovery 950/1000 = 0.95 >= 0.90.
    AB_RPS_SEQ="1000,900,950" \
        sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"baseline : rps=1000.0 p95=28ms combined-err=0.00%"* ]]
    [[ "$output" == *"spike    : rps=900.0 p95=28ms combined-err=0.00% (gate 5.00%) -> absorbed/ok"* ]]
    [[ "$output" == *"recovery : rps=950.0 p95=28ms combined-err=0.00% -> RECOVERED"* ]]
    [[ "$output" == *"VERDICT: RESILIENT - spikes were absorbed and every cycle recovered to baseline."* ]]
    [[ "$output" == *"spike collapses (throughput under 25% of baseline):      0 cycle(s)"* ]]
    [[ "$output" == *"degraded recoveries (rps<90% of baseline or p95>1.2x):   0 cycle(s)"* ]]
    [[ "$output" == *"Error-rate gate passed: pooled combined 0.00% <= 5.0% (-e)."* ]]
    [ "$(sp_csv_rows "$SPIKE_OUT")" -eq 3 ]
}

@test "multi-cycle runs append 3 rows per cycle and pool the best verdict" {
    # Cycle 1: healthy. Cycle 2 calls repeat the last seq value (950), which
    # stays absorbed and recovered - verdict stays RESILIENT.
    AB_RPS_SEQ="1000,900,950" \
        sp_run -i 2 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    [ "$(sp_csv_rows "$SPIKE_OUT")" -eq 6 ]
    [[ "$output" == *"VERDICT: RESILIENT"* ]]
    grep -q '"cycles": 2,' "$SPIKE_OUT.meta.json"
    awk -F',' 'NR == 5 { print $4 }' "$SPIKE_OUT" | grep -q '^baseline$'
    awk -F',' 'NR == 7 { print $4 }' "$SPIKE_OUT" | grep -q '^recovery$'
}

@test "NOT RESILIENT when the spike collapses throughput" {
    # spike 50 < 25% of baseline 1000 (250) -> COLLAPSED.
    AB_RPS_SEQ="1000,50,950" \
        sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"spike    : rps=50.0 p95=28ms combined-err=0.00% (gate 5.00%) -> COLLAPSED/ok"* ]]
    [[ "$output" == *"spike/baseline rps ratio=0.05 | spike p95 = 1.0x baseline p95"* ]]
    [[ "$output" == *"VERDICT: NOT RESILIENT - see the per-cycle verdicts above for the failing phase."* ]]
    [[ "$output" == *"spike collapses (throughput under 25% of baseline):      1 cycle(s)"* ]]
    [[ "$output" == *"degraded recoveries (rps<90% of baseline or p95>1.2x):   0 cycle(s)"* ]]
}

@test "NOT RESILIENT when recovery degrades below 90% of baseline" {
    # spike 800 is absorbed; recovery 850/1000 = 0.85 < 0.90 -> DEGRADED.
    AB_RPS_SEQ="1000,800,850" \
        sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"recovery : rps=850.0 p95=28ms combined-err=0.00% -> DEGRADED"* ]]
    [[ "$output" == *"recovery rps ratio=0.85 (>=0.90 recovered) | p95 ratio=1.00 (<=1.20 ok)"* ]]
    [[ "$output" == *"degraded recoveries (rps<90% of baseline or p95>1.2x):   1 cycle(s)"* ]]
    [[ "$output" == *"VERDICT: NOT RESILIENT"* ]]
}

# ---------------------------------------------------------------------
# Gates and failure taxonomy
# ---------------------------------------------------------------------

@test "CI pooled error gate (-e) exits 1 when the run exceeds the ceiling" {
    # 3 non-2xx responses over a total of 40+90+40 = 170 sent -> 1.76% > 1%.
    AB_NON2XX=1 sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -e 1 -r "$SPIKE_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR-RATE GATE FAILED: pooled combined error rate 1.76% exceeds -e ceiling of 1%."* ]]
}

@test "server-down runs record all requests failed and trip the gate" {
    CURL_TRANSPORT_FAIL=1 AB_MODE=total-fail \
        sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ab exited 1 with no report"* ]]
    # Every requested request is counted failed so nothing is silently dropped.
    fail_sum=$(awk -F',' 'NR > 1 { s += $10 } END { print s }' "$SPIKE_OUT")
    sent_sum=$(awk -F',' 'NR > 1 { s += $8 } END { print s }' "$SPIKE_OUT")
    [ "$fail_sum" -eq 170 ]
    [ "$sent_sum" -eq 170 ]
    # The pooled combined rate (100%) exceeds the default 5% gate.
    [[ "$output" == *"ERROR-RATE GATE FAILED"* ]]
}

@test "partial ab report (nonzero exit with data) is surfaced with a WARN" {
    AB_RPS_SEQ="1000,900,950" AB_MODE=partial \
        sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    grep -q "ab exited 1 with a PARTIAL report" <<< "$output"
    [ "$(sp_csv_rows "$SPIKE_OUT")" -eq 3 ]
}

@test "client-side connect errors trigger the ulimit advisory and are counted" {
    # 2 connect errors per measured run; the default 5% gate is permissive.
    AB_CONNERR=2 sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"ADVISORY  cycle 1 spike: 2 connect error(s). If spiking from the CLIENT's host"* ]]
    [[ "$output" == *"ulimit -n, the client's file descriptors can be the bottleneck."* ]]
    conn_sum=$(awk -F',' 'NR > 1 { s += $17 } END { print s }' "$SPIKE_OUT")
    [ "$conn_sum" -eq 6 ]
}

@test "strict pre-flight (-X) aborts with a metadata sidecar and zero rows" {
    CURL_TRANSPORT_FAIL=1 sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -X -r "$SPIKE_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"STRICT MODE (-X): aborting because the target failed pre-flight. Nothing was measured."* ]]
    # Header-written but zero measured rows (same convention as load-test).
    [ "$(sp_csv_rows "$SPIKE_OUT")" -eq 0 ]
    grep -q '"script": "spike-test.sh"' "$SPIKE_OUT.meta.json"
    grep -q '"target_endpoint": "dynamic"' "$SPIKE_OUT.meta.json"
}

@test "non-strict pre-flight failure warns and still completes the run" {
    CURL_DYNAMIC_STATUS=500 sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARN  https://example.test/dynamic-page.php -> HTTP 500 (expected 200)"* ]]
    [[ "$output" == *"NOTE: results will measure whatever https://example.test/dynamic-page.php actually returns,"* ]]
    [ "$(sp_csv_rows "$SPIKE_OUT")" -eq 3 ]
}

@test "warm-up failure is tolerated (warns, run continues)" {
    AB_RPS_SEQ="1000,900,950" AB_WARMUP_FAIL=1 \
        sp_run -i 1 -B 5 -R 40 -S 30 -G 90 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Warm-up finished with warnings (endpoint may be unreachable or rejecting load)."* ]]
    [ "$(sp_csv_rows "$SPIKE_OUT")" -eq 3 ]
}

@test "static and login targets pre-flight their own endpoints" {
    sp_run -q static -s /assets/app.css -r "$SPIKE_OUT" -i 1 -B 5 -R 40 -S 30 -G 90
    [ "$status" -eq 0 ]
    [[ "$output" == *"https://example.test/assets/app.css -> HTTP 200"* ]]

    sp_run -q login -g /admin/login.php -r "${SPIKE_OUT}.login.csv" -i 1 -B 5 -R 40 -S 30 -G 90
    [ "$status" -eq 0 ]
    [[ "$output" == *"https://example.test/admin/login.php -> HTTP 200"* ]]
}

@test "meta.json sidecar records cycle shape and provenance" {
    AB_RPS_SEQ="1000,900,950" \
        sp_run -i 2 -B 5 -R 40 -S 30 -G 90 -l acme -K -P 7 -r "$SPIKE_OUT"
    [ "$status" -eq 0 ]
    grep -q '"script": "spike-test.sh"' "$SPIKE_OUT.meta.json"
    grep -q '"site_label": "acme",' "$SPIKE_OUT.meta.json"
    grep -q '"cycles": 2,' "$SPIKE_OUT.meta.json"
    grep -q '"baseline": { "concurrency": 5, "requests": 40 },' "$SPIKE_OUT.meta.json"
    grep -q '"spike": { "concurrency": 30, "requests": 90 },' "$SPIKE_OUT.meta.json"
    grep -q '"pause_between_cycles": 7,' "$SPIKE_OUT.meta.json"
    grep -q '"keep_alive": true,' "$SPIKE_OUT.meta.json"
    grep -q '"strict_preflight": false,' "$SPIKE_OUT.meta.json"
    grep -q '"ab_version": "ApacheBench/2.4.41 (mock version for spike-test BATS suite)",' "$SPIKE_OUT.meta.json"
}
