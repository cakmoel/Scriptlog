#!/usr/bin/env bats
#
# BATS suite for soak-test.sh (wall-clock-bounded checkpoint soak).
#
# Run:   bats tests/bats
#
# Everything is hermetic: the helper installs deterministic mocks for ab, curl,
# date, and sleep on a sandboxed PATH. The mocked sleep ADVANCES a fake wall
# clock ($SOAK_FAKECLOCK) instead of sleeping, so a -d 60 -I 30 run (two
# checkpoints) executes instantly with exact cadence/elapsed math.
#
# Determinism notes (mock fixture):
#   * CSV verdicts are computed from the REPORTED rps (column 11) and the
#     report-block p95 (column 14), so `AB_RPS_SEQ` / `AB_P95_SEQ` script the
#     drift analysis directly in call order (call 1 = warm-up, then one call
#     per checkpoint);
#   * successive checkpoints share identical -n/-c, which is why the mock keys
#     its answers by CALL ORDER rather than by ab arguments;
#   * elapsed_seconds in the CSV is 0 for checkpoint 1 and exactly the
#     cadence (-I) for checkpoint 2 under the fake clock.
#
# CSV row ORDER is strictly sequential (checkpoint 1..N), so positional
# assertions are stable here (unlike load-test.bats).

setup() {
    load 'soak-test-helper'
    so_setup
}

# ---------------------------------------------------------------------
# CLI validation
# ---------------------------------------------------------------------

@test "requires -u BASE_URL and validates target selection" {
    run bash "$SOAK_SCRIPT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -u BASE_URL is required."* ]]

    run bash "$SOAK_SCRIPT" -u https://x.test -q static
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -s STATIC_PATH is required when -q static"* ]]

    run bash "$SOAK_SCRIPT" -u https://x.test -q login
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -g LOGIN_PATH is required when -q login"* ]]

    run bash "$SOAK_SCRIPT" -u https://x.test -q 404
    [ "$status" -eq 1 ]
    [[ "$output" == *"must be one of: dynamic, static, login"* ]]
}

@test "rejects non-integer values on shape flags" {
    so_run -d abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -d (soak duration seconds) must be an integer, got 'abc'."* ]]

    so_run -I abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-I (checkpoint cadence seconds) must be an integer, got 'abc'."* ]]

    so_run -c abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-c (sustained concurrency) must be an integer, got 'abc'."* ]]

    so_run -b abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-b (requests per checkpoint) must be an integer, got 'abc'."* ]]

    so_run -e abc
    [ "$status" -eq 1 ]
    [[ "$output" == *"-e (max error rate) must be a number between 0 and 100, got 'abc'."* ]]

    so_run -e 150
    [ "$status" -eq 1 ]
    [[ "$output" == *"-e (max error rate) must be between 0 and 100, got '150'."* ]]
}

@test "applies the soak-specific duration/cadence floors" {
    so_run -d 59
    [ "$status" -eq 1 ]
    [[ "$output" == *"-d (soak duration) must be >= 60 seconds (a shorter horizon is a load test, not a soak test"* ]]

    so_run -I 29
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -I (checkpoint cadence) must be >= 30 seconds."* ]]

    so_run -I 90
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR: -I checkpoint cadence (90) exceeds -d duration (60)."* ]]
}

@test "rejects malformed -u (scheme, host) like load-test.sh" {
    run bash "$SOAK_SCRIPT" -u ftp://x -r "$SOAK_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"must start with http:// or https://"* ]]

    run bash "$SOAK_SCRIPT" -u https:// -r "$SOAK_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"missing a host component"* ]]
}

@test "refuses to overwrite an existing CSV without -f and allows it with -f" {
    printf 'x,x\n' > "$SOAK_OUT"
    so_run -b 40 -c 5 -r "$SOAK_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"output file already exists"* ]]

    so_run -b 40 -c 5 -f -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    [[ "$(so_csv_rows "$SOAK_OUT")" == "2" ]]
}

# ---------------------------------------------------------------------
# --dry-run (no network I/O, no files)
# ---------------------------------------------------------------------

@test "dry-run prints the resolved soak plan and creates nothing" {
    so_run -b 40 -c 5 -r "$SOAK_OUT" --dry-run
    [ "$status" -eq 0 ]
    [[ "$output" == *"DRY RUN - planned soak for 'site' (nothing was executed)"* ]]
    [[ "$output" == *"target            : https://example.test/dynamic-page.php"* ]]
    [[ "$output" == *"duration          : 60s"* ]]
    [[ "$output" == *"checkpoint cadence: 30s (expected ~2 checkpoints)"* ]]
    [[ "$output" == *"concurrency       : 5 (constant)"* ]]
    [[ "$output" == *"per-checkpoint run: 40 requests (ab -n)"* ]]
    [[ "$output" == *"rps ratio < 0.85 -> THROUGHPUT-DRIFT down"* ]]
    [[ "$output" == *"p95 ratio > 1.30 -> LATENCY-DRIFT up"* ]]
    [[ "$output" == *"CI error gate     : pooled combined rate must stay <= 1.0%"* ]]
    [[ "$output" == *"output csv        : $SOAK_OUT"* ]]
    [[ "$output" == *"pidstat -urd -h -p <php-fpm_pid> 60 > fpm-soak.log"* ]]
    [ ! -e "$SOAK_OUT" ]
    [ ! -e "$SOAK_OUT.meta.json" ]
    [ "$(wc -l < "$SOAK_LOG")" -eq 0 ]
}

# ---------------------------------------------------------------------
# A healthy run end-to-end (mocked, fake clock)
# ---------------------------------------------------------------------

@test "run executes warm-up and exactly cadence-quantized checkpoints" {
    AB_RPS_SEQ="1000,950" so_run -b 40 -c 5 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    # Warm-up: -W 3 requests at concurrency 5 (kept from load-test.sh convention).
    grep -q "^ab -n 3 -c 5 " "$SOAK_LOG"
    # Two measured checkpoints: ab -n 40 -c 5 -g each.
    [ "$(grep -c 'ab -n 40 -c 5 -g ' "$SOAK_LOG")" -eq 2 ]
    [ "$(so_csv_rows "$SOAK_OUT")" -eq 2 ]
    # elapsed_seconds: 0 for checkpoint 1, exactly the -I cadence for ckpt 2.
    awk -F',' 'NR == 2 { print $4 }' "$SOAK_OUT" | grep -q '^0$'
    awk -F',' 'NR == 3 { print $4 }' "$SOAK_OUT" | grep -q '^30$'
    [[ "$output" == *"Soak measurement complete: 2 checkpoint(s) over 60s of wall time (budget 60s)."* ]]
    [[ "$output" == *"no drift flags: rps ratio >= 0.85 and p95 ratio <= 1.30 within this run's horizon"* ]]
    [[ "$output" == *"Error-rate gate passed: pooled combined 0.00% <= 1.0% (-e)."* ]]
}

@test "CSV rows carry the full shared schema with checkpoint/elapsed columns" {
    AB_RPS_SEQ="1000,950" so_run -c 7 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    header=$(head -1 "$SOAK_OUT")
    for col in timestamp label checkpoint elapsed_seconds endpoint url concurrency requests_sent complete_requests failed_requests rps mean_latency_ms p50_ms p95_ms p99_ms non2xx_requests connect_errors receive_errors length_errors exceptions ab_exit; do
        grep -q "$col" <<< "$header"
    done
    [[ $(awk -F',' 'NR == 2 { print; exit }' "$SOAK_OUT") == *,1,0,dynamic,https://example.test/dynamic-page.php,7,40,40,0,1000,* ]]
    [[ $(awk -F',' 'NR == 3 { print; exit }' "$SOAK_OUT") == *,2,30,dynamic,https://example.test/dynamic-page.php,7,40,40,0,950,* ]]
}

@test "-b below the 30-request floor is raised with a NOTE" {
    so_run -c 5 -b 10 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"NOTE: -b 10 raises to the 30-request floor for valid per-checkpoint percentiles."* ]]
    grep -q "ab -n 30 -c 5 -g " "$SOAK_LOG"
    grep -q '"requests_per_checkpoint": 30,' "$SOAK_OUT.meta.json"
}

@test "-b below the concurrency is raised to it (ab -n >= -c)" {
    so_run -c 60 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    grep -q "ab -n 60 -c 60 -g " "$SOAK_LOG"
    grep -q '"requests_per_checkpoint": 60,' "$SOAK_OUT.meta.json"
}

@test "one checkpoint (-I = -d) completes with no drift analysis possible" {
    so_run -c 5 -b 40 -I 60 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    [ "$(so_csv_rows "$SOAK_OUT")" -eq 1 ]
    [[ "$output" == *"Fewer than 2 checkpoints were recorded; no drift analysis is possible."* ]]
    # Gate not evaluated on a single-checkpoint run.
    [[ "$output" != *"Error-rate gate passed"* ]]
}

@test "meta.json sidecar records the soak shape and provenance" {
    AB_RPS_SEQ="1000,950" so_run -c 6 -b 40 -l acme -K -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    grep -q '"script": "soak-test.sh"' "$SOAK_OUT.meta.json"
    grep -q '"site_label": "acme",' "$SOAK_OUT.meta.json"
    grep -q '"target_endpoint": "dynamic",' "$SOAK_OUT.meta.json"
    grep -q '"duration_seconds": 60,' "$SOAK_OUT.meta.json"
    grep -q '"checkpoint_seconds": 30,' "$SOAK_OUT.meta.json"
    grep -q '"requests_per_checkpoint": 40,' "$SOAK_OUT.meta.json"
    grep -q '"concurrency": 6,' "$SOAK_OUT.meta.json"
    grep -q '"max_error_pct": 1.0,' "$SOAK_OUT.meta.json"
    grep -q '"keep_alive": true,' "$SOAK_OUT.meta.json"
    grep -q '"strict_preflight": false,' "$SOAK_OUT.meta.json"
    grep -q '"ab_version": "ApacheBench/2.4.41 (mock version for soak-test BATS suite)",' "$SOAK_OUT.meta.json"
}

# ---------------------------------------------------------------------
# Drift analysis
# ---------------------------------------------------------------------

@test "THROUGHPUT-DRIFT down flags decaying rps across the run" {
    # Window of 1 checkpoint (2 rows -> w=1): first=1000, last=400.
    AB_RPS_SEQ="1000,400" so_run -c 5 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"rps  first=1000.0 last=400.0  ratio=0.40"* ]]
    [[ "$output" == *"FLAG: THROUGHPUT-DRIFT down (rps ratio < 0.85) - throughput decaying over time (memory leak, connection leak, or accumulating state are classic causes)"* ]]
    [[ "$output" == *"Drift analysis (windows of 1 checkpoint(s): first-third vs last-third):"* ]]
    # No latency drift: p95 stays 28.
    [[ "$output" == *"  p95  first=28.0ms last=28.0ms  ratio=1.00"* ]]
}

@test "LATENCY-DRIFT up flags growing p95 across the run" {
    AB_P95_SEQ="20,40" so_run -c 5 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"p95  first=20.0ms last=40.0ms  ratio=2.00"* ]]
    [[ "$output" == *"FLAG: LATENCY-DRIFT up (p95 ratio > 1.30) - tail latency growing over time"* ]]
    [[ "$output" != *"THROUGHPUT-DRIFT"* ]]
    # No throughput drift: rps stays 1000.
    [[ "$output" == *"rps  first=1000.0 last=1000.0  ratio=1.00"* ]]
}

@test "both drift flags appear together when rps decays and p95 grows" {
    AB_RPS_SEQ="1000,400" AB_P95_SEQ="20,40" so_run -c 5 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"FLAG: THROUGHPUT-DRIFT down (rps ratio < 0.85)"* ]]
    [[ "$output" == *"FLAG: LATENCY-DRIFT up (p95 ratio > 1.30)"* ]]
    # The run still passes the error gate (drift is advisory, not a gate).
    [[ "$output" == *"Error-rate gate passed: pooled combined 0.00% <= 1.0% (-e)."* ]]
}

# ---------------------------------------------------------------------
# Gates and failure taxonomy
# ---------------------------------------------------------------------

@test "CI pooled error gate (-e) exits 1 beyond the tight soak default" {
    # 2 checkpoints x 1 non-2xx over 80 sent -> 2.50% > default 1.0%.
    AB_NON2XX=1 so_run -c 5 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"WARN  checkpoint 2: clean ab exit but 1 non-2xx response(s)"* ]]
    [[ "$output" == *"ERROR-RATE GATE FAILED: pooled combined error rate 2.50% exceeds -e ceiling of 1.0%."* ]]
}

@test "ab failure at the first checkpoint aborts and durably records it" {
    AB_FAIL_AT_SEQ=1 so_run -c 5 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"WARN  checkpoint 1: ab exited 1 with no report (server down or refusing connections?)"* ]]
    [[ "$output" == *"ERROR checkpoint 1: ab run failed; aborting the soak run (CSV has 1 checkpoints)."* ]]
    # The failed checkpoint's requests are recorded, never dropped.
    [ "$(so_csv_rows "$SOAK_OUT")" -eq 1 ]
    awk -F',' 'NR == 2 { print $10 }' "$SOAK_OUT" | grep -q '^40$'
    [[ "$output" != *"ERROR-RATE GATE FAILED"* ]]
}

@test "ab failure mid-soak keeps every earlier checkpoint and skips the gate" {
    # -d 90 -I 30 -> exactly 3 checkpoints: two healthy ones, then the abort.
    AB_RPS_SEQ="1000,1000" AB_FAIL_AT_SEQ=3 so_run -c 5 -b 40 -d 90 -r "$SOAK_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ERROR checkpoint 3: ab run failed; aborting the soak run (CSV has 3 checkpoints)."* ]]
    [ "$(so_csv_rows "$SOAK_OUT")" -eq 3 ]
    # Healthy checkpoints persisted before the abort.
    awk -F',' 'NR == 2 { print $11 }' "$SOAK_OUT" | grep -q '^1000$'
    awk -F',' 'NR == 3 { print $11 }' "$SOAK_OUT" | grep -q '^1000$'
    [[ "$output" == *"Soak run was ABORTED by an ab failure; the error gate is not evaluated on a partial run."* ]]
}

@test "total server outage still records the run and exits nonzero" {
    AB_MODE=total-fail so_run -c 5 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"ab exited 1 with no report"* ]]
    [ "$(so_csv_rows "$SOAK_OUT")" -eq 1 ]
    fail_sum=$(awk -F',' 'NR == 2 { print $10 }' "$SOAK_OUT")
    [ "$fail_sum" -eq 40 ]
}

@test "strict pre-flight (-S) aborts with a metadata sidecar and zero rows" {
    CURL_TRANSPORT_FAIL=1 so_run -c 5 -b 40 -S -r "$SOAK_OUT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"STRICT MODE (-S): aborting because the target failed pre-flight. Nothing was measured."* ]]
    [ "$(so_csv_rows "$SOAK_OUT")" -eq 0 ]
    grep -q '"script": "soak-test.sh"' "$SOAK_OUT.meta.json"
    grep -q '"target_endpoint": "dynamic"' "$SOAK_OUT.meta.json"
}

@test "non-strict pre-flight failure warns and still completes the soak" {
    CURL_DYNAMIC_STATUS=500 AB_RPS_SEQ="1000,950" so_run -c 5 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARN  https://example.test/dynamic-page.php -> HTTP 500 (expected 200)"* ]]
    [[ "$output" == *"NOTE: results will measure whatever https://example.test/dynamic-page.php actually returns,"* ]]
    [ "$(so_csv_rows "$SOAK_OUT")" -eq 2 ]
}

@test "warm-up failure is tolerated (warns, soak continues)" {
    AB_RPS_SEQ="1000,950" AB_WARMUP_FAIL=1 so_run -c 5 -b 40 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Warm-up finished with warnings (endpoint may be unreachable or rejecting load)."* ]]
    [ "$(so_csv_rows "$SOAK_OUT")" -eq 2 ]
}

@test "static and login targets pre-flight their own endpoints" {
    run bash "$SOAK_SCRIPT" -u https://example.test -q static -s /assets/app.css \
        -W 3 -d 60 -I 30 -c 5 -b 40 -r "${SOAK_OUT}.static.csv"
    [ "$status" -eq 0 ]
    [[ "$output" == *"https://example.test/assets/app.css -> HTTP 200"* ]]

    run bash "$SOAK_SCRIPT" -u https://example.test -q login -g /admin/login.php \
        -W 3 -d 60 -I 30 -c 5 -b 40 -r "${SOAK_OUT}.login.csv"
    [ "$status" -eq 0 ]
    [[ "$output" == *"https://example.test/admin/login.php -> HTTP 200"* ]]
}

@test "connect errors are counted in the CSV and warn via the soak taxonomy" {
    # 4 failed requests of 80 sent = 5.00%; a permissive -e 100 keeps the run
    # green so the assertion isolates the taxonomy/CSV counting.
    AB_CONNERR=2 so_run -c 5 -b 40 -e 100 -r "$SOAK_OUT"
    [ "$status" -eq 0 ]
    [[ "$output" == *"WARN  checkpoint 1: 2 connect error(s) (check the client's ulimit -n before blaming the server)"* ]]
    conn_sum=$(awk -F',' 'NR > 1 { s += $17 } END { print s }' "$SOAK_OUT")
    [ "$conn_sum" -eq 4 ]
}
