#!/bin/bash
#
# load-test.sh
#
# Author: M.Noermoehammad
#
# Weighted, concurrent load test wrapper around Apache Bench (ab).
# Replaces the original abload.sh / wp-abload.sh pair: same underlying
# tool (ab), but fixes the issues found when reviewing those scripts:
#
#   1. Adds a true static-asset scenario (no PHP execution), so the
#      PHP-bootstrap scenarios have a real baseline to compare against.
#   2. Fires all scenarios CONCURRENTLY within each round, instead of
#      one scenario fully finishing before the next starts. Real traffic
#      never arrives as three clean back-to-back bursts.
#   3. Weights scenario frequency to approximate real traffic (static
#      assets and content views dominate; login and 404 are rare),
#      instead of testing all three equally. When MIN_ROUND_SAMPLES
#      floors low-weight scenarios, weights still govern concurrency
#      allocation but request counts are equalized (see P1 in plan/
#      LOAD_TEST_FORENSIC_REMEDIATION_PLAN.md).
#   4. Verifies each endpoint's HTTP status before the timed run starts,
#      and flags non-2xx / unexpected results instead of trusting ab
#      blindly.
#   5. Captures failed requests, mean latency, and p50/p95/p99 latency
#      per run, not only requests-per-second.
#   6. Never silently drops a failed run from the average. Every round
#      is logged to CSV, and failures are counted and reported.
#   7. Runs a warm-up phase before measurement, so opcode/query caches
#      are in a steady state before numbers are recorded.
#
# METHODOLOGY
#   Warm-up/steady-state : WARMUP_REQUESTS unmeasured hits per endpoint, fired
#                          CONCURRENTLY across all endpoints, so opcode caches,
#                          buffers, and connection pools reach steady state under
#                          concurrent load (matching the measured phase) before
#                          any number is recorded. Note this may not fully prime
#                          database buffer pools for very large datasets.
#   Weighted mix         : each round launches all four scenarios CONCURRENTLY,
#                          sized by traffic weights (-w) that sum to 100. When
#                          the per-scenario request count falls below
#                          MIN_ROUND_SAMPLES, the floor overrides the weight
#                          and all scenarios run equal request counts. Weights
#                          still govern concurrency allocation in that case.
#   Mixed-load caveat    : per-scenario throughput measured under a shared
#                          concurrency budget is NOT comparable to the same
#                          scenario run in isolation; contention effects are
#                          the point, but quote them as such.
#   Error rates          : denominators are requests SENT (not completed),
#                          so aborted rounds cannot dilute failure percentages.
#                          transport = failed/sent; http = non2xx/sent;
#                          combined = (failed+non2xx)/sent (the CI-gate figure).
#   Latency precision    : percentiles are nearest-rank over POOLED raw
#                          per-request times (ab -g), never averages of
#                          per-round percentiles; dispersion reported as
#                          sample stddev + CV.
#   Coordinated omission : like any closed-loop client, ab hides server stalls
#                          behind its own in-flight window; absolute tail
#                          latencies are lower bounds, not ground truth.
#   Reproducibility      : launch order is a seeded Fisher-Yates shuffle; the
#                          seed is printed and accepted via -R for exact reruns.
#
# REFERENCES
#   Little's Law: L = lambda * W bounds concurrency vs arrival rate.
#
# Requirements: ab (apache2-utils), curl, awk, bc.
#
# Usage:
#   ./load-test.sh -u https://scriptlog.ddev.site -l "Scriptlog" \
#       -s /path/to/real/static/asset.css -d "/?p=1" -g /admin/login.php \
#       -r ./results_scriptlog.csv
#
#   ./load-test.sh -u https://wordpress.ddev.site -l "WordPress" \
#       -s /wp-includes/css/dist/block-library/style.min.css -d "/?p=1" \
#       -g /wp-login.php -r ./results_wordpress.csv
#
# Run the same script against both sites with matching -i/-c/-t flags to
# keep the comparison apples-to-apples, exactly as the original two
# scripts did.

set -uo pipefail

# Deterministic numeric behavior across host locales: ab always prints dot
# decimals, but awk/printf/date parsing and formatting must not drift when
# the harness runs under a non-C locale.
export LC_ALL=C

# ---------------------------------------------------------------------------
# Defaults (override via flags; see usage() below)
# ---------------------------------------------------------------------------
BASE_URL=""
SITE_LABEL="site"
STATIC_PATH=""
DYNAMIC_PATH="/?p=1"
LOGIN_PATH=""
NOTFOUND_PATH="/this-page-is-not-real"

ROUNDS=200                 # number of measured rounds (replaces flat ITERATIONS=1000)
# Concurrency sizing rationale (Little's Law, L = lambda * W): the total
# concurrency approximates the number of requests in flight (L); achieved
# throughput (lambda) and per-request latency (W) are then MEASURED, not
# assumed. Each scenario receives TOTAL_CONCURRENCY * weight% concurrent
# connections as its private budget; the sum of per-scenario budgets equals
# TOTAL_CONCURRENCY (approximately, after integer truncation).
TOTAL_CONCURRENCY=20       # total concurrent connections spread across scenarios per round
AB_REQUESTS_BASE=40        # base request count per scenario per round before weighting
MIN_ROUND_SAMPLES=30       # per-round request floor per scenario: below ~10-30 samples,
                           # per-round percentiles are statistically meaningless
                           # (user-approved remediation decision; see P1 in plan/
                           # LOAD_TEST_FORENSIC_REMEDIATION_PLAN.md; the
                           # --relax-min-samples flag lowers this floor to 10)
WARMUP_REQUESTS=50         # throwaway requests per endpoint before measurement starts
                           # (was 20; 50 better primes DB buffer pools / query caches -
                           # see P3 in plan/LOAD_TEST_FORENSIC_REMEDIATION_PLAN.md)
KEEP_ALIVE=0               # -K: pass -k to ab (persistent connections)
RUN_SEED=""                # -R: RNG seed for launch-order shuffle (default: auto)

# Traffic mix weights (must sum to 100). Defaults approximate a typical
# content site: most traffic hits static assets and content pages;
# login and 404 are comparatively rare. Adjust with -w if your traffic
# profile differs.
WEIGHT_STATIC=45
WEIGHT_DYNAMIC=40
WEIGHT_LOGIN=5
WEIGHT_404=10

OUTPUT_CSV=""
CONNECT_TIMEOUT=5
PREFLIGHT_MAX_TIME=10   # hard ceiling (seconds) per pre-flight curl request
STRICT_PREFLIGHT=0      # -S: abort before measuring on any pre-flight failure
WEIGHTS_SPEC=""         # raw -w value, parsed and validated after getopts
FORCE_OVERWRITE=0       # -f: allow overwriting an existing CSV
MAX_ERROR_RATE=""       # -e: combined error-rate ceiling in percent (CI gate)
DRY_RUN=0               # --dry-run: print the planned workload and exit

usage() {
    cat <<EOF
Usage: $0 -u BASE_URL -s STATIC_PATH -d DYNAMIC_PATH -g LOGIN_PATH [options]

Required:
  -u  Base URL, e.g. https://scriptlog.ddev.site
  -s  Path to a REAL static asset (served by the webserver without
      invoking PHP), e.g. /assets/css/app.css
  -g  Path to the login controller, e.g. /admin/login.php

Optional:
  -l  Label for this site in output (default: "site")
  -d  Dynamic content path (default: "/?p=1")
  -n  404 path (default: "/this-page-is-not-real")
  -i  Number of measured rounds (default: $ROUNDS)
  -c  Concurrency budget: each scenario gets TOTAL_CONCURRENCY * weight% connections (default: $TOTAL_CONCURRENCY)
  -b  Base request count per scenario per round before weighting (default: $AB_REQUESTS_BASE)
  -m  Per-round request floor per scenario for statistical sufficiency
      of per-round percentiles (default: $MIN_ROUND_SAMPLES)
  -W  Throwaway warm-up requests per endpoint (default: $WARMUP_REQUESTS)
  -K  Use HTTP keep-alive (ab -k); default: new connection per request
  -R  Seed for scenario launch-order shuffle; identical seeds reproduce
      identical orders (default: random per run)
  -w  Weights as "static,dynamic,login,404": exactly four non-negative
      integers summing to 100 (default: ${WEIGHT_STATIC},${WEIGHT_DYNAMIC},${WEIGHT_LOGIN},${WEIGHT_404})
  -r  CSV output path (default: ./results_<label>_<timestamp>.csv)
  -f  Overwrite the CSV output file if it already exists (default: refuse)
  -S  Strict mode: abort before measuring if any endpoint fails its
      pre-flight check (transport error or unexpected HTTP status)
  -e  CI gate: exit nonzero when the COMBINED error rate
      ((failed + non-2xx) / sent) exceeds this percentage, e.g. -e 2.5
  --dry-run  Print the fully resolved workload (endpoints, weights,
      per-round request counts, seed, flags) and exit without
      touching the network or writing any files
  --relax-min-samples  Lower the per-round sample floor to 10 (still enough
      for meaningful per-round percentiles), so low-weight scenarios keep
      closer to their true request counts. Any explicit -m takes precedence.
  -h  Show this help
EOF
    exit 1
}

# Long-option shim: --dry-run and --relax-min-samples are the only long
# options. Strip them before getopts (which handles short options only) so
# they compose freely with the short flags. --relax-min-samples lowers the
# per-round sample floor to 10 so weights (not the floor) better govern
# request counts; an explicit -m still wins because getopts runs after this
# shim regardless of where each flag appears on the command line.
script_args=()
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
        --relax-min-samples) MIN_ROUND_SAMPLES=10 ;;
        *) script_args+=("$arg") ;;
    esac
done
set -- "${script_args[@]}"

 while getopts ":u:l:s:d:g:n:i:c:b:m:e:W:KR:r:w:fSh" opt; do
     case "$opt" in
         u) BASE_URL="$OPTARG" ;;
         l) SITE_LABEL="$OPTARG" ;;
         s) STATIC_PATH="$OPTARG" ;;
         d) DYNAMIC_PATH="$OPTARG" ;;
         g) LOGIN_PATH="$OPTARG" ;;
         n) NOTFOUND_PATH="$OPTARG" ;;
         i) ROUNDS="$OPTARG" ;;
         c) TOTAL_CONCURRENCY="$OPTARG" ;;
         b) AB_REQUESTS_BASE="$OPTARG" ;;
         m) MIN_ROUND_SAMPLES="$OPTARG" ;;
         W) WARMUP_REQUESTS="$OPTARG" ;;
         e) MAX_ERROR_RATE="$OPTARG" ;;
        K) KEEP_ALIVE=1 ;;
        R) RUN_SEED="$OPTARG" ;;
        w) WEIGHTS_SPEC="$OPTARG" ;;
        r) OUTPUT_CSV="$OPTARG" ;;
        f) FORCE_OVERWRITE=1 ;;
        S) STRICT_PREFLIGHT=1 ;;
        h) usage ;;
        :) echo "ERROR: option -$OPTARG requires an argument." >&2; usage ;;
        \?) echo "ERROR: unknown option -$OPTARG." >&2; usage ;;
    esac
done

# ---------------------------------------------------------------------------
# Validation
# ---------------------------------------------------------------------------
for bin in ab curl awk bc; do
    command -v "$bin" >/dev/null 2>&1 || { echo "ERROR: required tool '$bin' not found in PATH." >&2; exit 1; }
done

[[ -z "$BASE_URL" ]] && { echo "ERROR: -u BASE_URL is required." >&2; usage; }
[[ -z "$STATIC_PATH" ]] && { echo "ERROR: -s STATIC_PATH is required (point it at a real static asset, not a PHP script)." >&2; usage; }
[[ -z "$LOGIN_PATH" ]] && { echo "ERROR: -g LOGIN_PATH is required." >&2; usage; }

# Numeric tuning flags must be positive integers. Previously a zero, negative,
# or non-numeric value here produced broken loops or cryptic arithmetic errors
# deep inside the run loop instead of an immediate, actionable message.
require_positive_int() {
    local flag="$1" value="$2" name="$3"
    if ! [[ "$value" =~ ^[0-9]+$ ]]; then
        echo "ERROR: -$flag ($name) must be an integer, got '$value'." >&2
        exit 1
    fi
    if [[ "$value" -lt 1 ]]; then
        echo "ERROR: -$flag ($name) must be >= 1, got '$value'." >&2
        exit 1
    fi
}
require_positive_int i "$ROUNDS" "measured rounds"
require_positive_int c "$TOTAL_CONCURRENCY" "total concurrency"
require_positive_int b "$AB_REQUESTS_BASE" "base requests per scenario"
require_positive_int m "$MIN_ROUND_SAMPLES" "minimum samples per scenario per round"
require_positive_int W "$WARMUP_REQUESTS" "warm-up requests per endpoint"
if [[ -n "$RUN_SEED" ]] && ! [[ "$RUN_SEED" =~ ^[0-9]+$ ]]; then
    echo "ERROR: -R (seed) must be a non-negative integer, got '$RUN_SEED'." >&2
    exit 1
fi

# CI gate threshold: a percentage, so fractional values are allowed but the
# value must lie in [0, 100]. Compared with awk at end-of-run (bash cannot
# compare decimals natively).
if [[ -n "$MAX_ERROR_RATE" ]]; then
    if ! [[ "$MAX_ERROR_RATE" =~ ^[0-9]+([.][0-9]+)?$ ]] ; then
        echo "ERROR: -e (max error rate) must be a number between 0 and 100, got '$MAX_ERROR_RATE'." >&2
        exit 1
    fi
    if ! awk -v v="$MAX_ERROR_RATE" 'BEGIN { exit !(v >= 0 && v <= 100) }'; then
        echo "ERROR: -e (max error rate) must be between 0 and 100, got '$MAX_ERROR_RATE'." >&2
        exit 1
    fi
fi

# Traffic-mix weights: exactly four comma-separated non-negative integers that
# sum to exactly 100. Parsing is deliberately strict: wrong field counts and
# garbage fields are fatal errors rather than silently becoming zero weights.
if [[ -n "$WEIGHTS_SPEC" ]]; then
    IFS=',' read -r -a weight_parts <<< "$WEIGHTS_SPEC"
    if [[ "${#weight_parts[@]}" -ne 4 ]]; then
        echo "ERROR: -w expects exactly 4 comma-separated values (static,dynamic,login,404), got ${#weight_parts[@]}: '$WEIGHTS_SPEC'." >&2
        exit 1
    fi
    for widx in 0 1 2 3; do
        if ! [[ "${weight_parts[$widx]}" =~ ^[0-9]+$ ]]; then
            echo "ERROR: -w field $((widx + 1)) ('${weight_parts[$widx]}') is not a non-negative integer." >&2
            exit 1
        fi
    done
    WEIGHT_STATIC=${weight_parts[0]}
    WEIGHT_DYNAMIC=${weight_parts[1]}
    WEIGHT_LOGIN=${weight_parts[2]}
    WEIGHT_404=${weight_parts[3]}
fi

weight_sum=$(( WEIGHT_STATIC + WEIGHT_DYNAMIC + WEIGHT_LOGIN + WEIGHT_404 ))
if [[ "$weight_sum" -ne 100 ]]; then
    echo "ERROR: weights must sum to 100 (got $weight_sum: ${WEIGHT_STATIC},${WEIGHT_DYNAMIC},${WEIGHT_LOGIN},${WEIGHT_404})." >&2
    exit 1
fi

# Base URL hygiene: enforce the scheme, strip any trailing slashes so appended
# scenario paths never produce double-slash URLs, and require a host part.
case "$BASE_URL" in
    http://*|https://*) ;;
    *)
        echo "ERROR: -u BASE_URL must start with http:// or https:// (got '$BASE_URL')." >&2
        exit 1
        ;;
esac
# Host check runs on the raw remainder (before slash stripping) so degenerate
# inputs like 'https://' cannot pass by having their separator eaten first.
url_host_part=${BASE_URL#*://}
url_host_part=${url_host_part%%/*}
if [[ -z "$url_host_part" ]]; then
    echo "ERROR: -u BASE_URL is missing a host component (got '$BASE_URL')." >&2
    exit 1
fi
while [[ "$BASE_URL" == */ ]]; do
    BASE_URL="${BASE_URL%/}"
done

# Path hygiene: every scenario path must begin with '/' so URL joins are well
# formed regardless of how the caller spelled them on the command line.
normalize_path() {
    local p="$1"
    case "$p" in
        /*) printf '%s' "$p" ;;
        *)  printf '/%s' "$p" ;;
    esac
}
STATIC_PATH=$(normalize_path "$STATIC_PATH")
DYNAMIC_PATH=$(normalize_path "$DYNAMIC_PATH")
LOGIN_PATH=$(normalize_path "$LOGIN_PATH")
NOTFOUND_PATH=$(normalize_path "$NOTFOUND_PATH")

# CSV safety: the summary stage parses rows positionally, so the site label
# must never contain field separators, quote characters, or line breaks.
SITE_LABEL=${SITE_LABEL//,/;}
SITE_LABEL=${SITE_LABEL//\"/}
SITE_LABEL=${SITE_LABEL//$'\r'/ }
SITE_LABEL=${SITE_LABEL//$'\n'/ }

# csv_field: sanitize any single CSV field for positional parsing. The summary
# and CI-gate readers split rows on commas with awk -F',', so a field must not
# carry a field separator, a double-quote, or a line break (which would both
# split and fabricate rows). Applies the same policy as the SITE_LABEL
# sanitization above. The URL field is the one user-derived value that could
# legitimately contain a comma (e.g. in a query string), so it is run through
# here at write time.
csv_field() {
    local val="$1"
    val=${val//,/;}
    val=${val//\"/}
    val=${val//$'\r'/ }
    val=${val//$'\n'/ }
    printf '%s' "$val"
}


# Keep-alive policy: real browsers reuse connections, while one-connection-
# per-request (the default) stresses TCP setup/accept() instead. Neither is
# wrong, but the choice MUST be held constant across sites in a comparative
# study or the results are not comparable.
AB_FLAGS=()
if [[ "$KEEP_ALIVE" -eq 1 ]]; then
    AB_FLAGS+=(-k)
fi

# Launch-order randomization seed: resolved once here so every round's
# shuffle derives from one recorded, reproducible RNG state.
if [[ -z "$RUN_SEED" ]]; then
    RUN_SEED=$SRANDOM
fi

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
if [[ -z "$OUTPUT_CSV" ]]; then
    OUTPUT_CSV="./results_${SITE_LABEL// /_}_${TIMESTAMP}.csv"
fi

# Fail fast on output problems BEFORE pre-flight/warm-up spend time: refuse to
# clobber an existing result file unless -f was given, and verify the target
# directory exists (or can be created) and is writable.
if [[ -e "$OUTPUT_CSV" && "$FORCE_OVERWRITE" -ne 1 ]]; then
    echo "ERROR: output file already exists: $OUTPUT_CSV (pass -f to overwrite)." >&2
    exit 1
fi
output_dir=$(dirname -- "$OUTPUT_CSV")
if [[ ! -d "$output_dir" ]]; then
    mkdir -p -- "$output_dir" || { echo "ERROR: cannot create output directory: $output_dir." >&2; exit 1; }
fi
if [[ ! -w "$output_dir" ]]; then
    echo "ERROR: output directory is not writable: $output_dir." >&2
    exit 1
fi

# ---------------------------------------------------------------------------
# --dry-run: print the fully resolved workload and exit. No network I/O,
# no CSV, no sidecar - the point is to inspect exactly what WOULD run.
# ---------------------------------------------------------------------------
if [[ "$DRY_RUN" -eq 1 ]]; then
    # Compute effective request counts and concurrency per scenario (same
    # logic as the main loop) so the dry-run output matches what will run.
    _eff_static_req=$(( AB_REQUESTS_BASE * WEIGHT_STATIC / 100 ))
    [[ "$_eff_static_req" -lt 1 ]] && _eff_static_req=1
    [[ "$_eff_static_req" -lt "$MIN_ROUND_SAMPLES" ]] && _eff_static_req=$MIN_ROUND_SAMPLES
    _eff_dynamic_req=$(( AB_REQUESTS_BASE * WEIGHT_DYNAMIC / 100 ))
    [[ "$_eff_dynamic_req" -lt 1 ]] && _eff_dynamic_req=1
    [[ "$_eff_dynamic_req" -lt "$MIN_ROUND_SAMPLES" ]] && _eff_dynamic_req=$MIN_ROUND_SAMPLES
    _eff_login_req=$(( AB_REQUESTS_BASE * WEIGHT_LOGIN / 100 ))
    [[ "$_eff_login_req" -lt 1 ]] && _eff_login_req=1
    [[ "$_eff_login_req" -lt "$MIN_ROUND_SAMPLES" ]] && _eff_login_req=$MIN_ROUND_SAMPLES
    _eff_404_req=$(( AB_REQUESTS_BASE * WEIGHT_404 / 100 ))
    [[ "$_eff_404_req" -lt 1 ]] && _eff_404_req=1
    [[ "$_eff_404_req" -lt "$MIN_ROUND_SAMPLES" ]] && _eff_404_req=$MIN_ROUND_SAMPLES
    _eff_static_cc=$(( TOTAL_CONCURRENCY * WEIGHT_STATIC / 100 ))
    [[ "$_eff_static_cc" -lt 1 ]] && _eff_static_cc=1
    _eff_dynamic_cc=$(( TOTAL_CONCURRENCY * WEIGHT_DYNAMIC / 100 ))
    [[ "$_eff_dynamic_cc" -lt 1 ]] && _eff_dynamic_cc=1
    _eff_login_cc=$(( TOTAL_CONCURRENCY * WEIGHT_LOGIN / 100 ))
    [[ "$_eff_login_cc" -lt 1 ]] && _eff_login_cc=1
    _eff_404_cc=$(( TOTAL_CONCURRENCY * WEIGHT_404 / 100 ))
    [[ "$_eff_404_cc" -lt 1 ]] && _eff_404_cc=1
    _eff_total_cc=$(( _eff_static_cc + _eff_dynamic_cc + _eff_login_cc + _eff_404_cc ))
    cat <<EOF
DRY RUN - planned workload for '${SITE_LABEL}' (nothing was executed)

  target           : ${BASE_URL}
  endpoints
    static         : ${STATIC_PATH}   weight=${WEIGHT_STATIC}  -> ${_eff_static_req} req/round  concurrency=${_eff_static_cc}
    dynamic        : ${DYNAMIC_PATH}   weight=${WEIGHT_DYNAMIC}  -> ${_eff_dynamic_req} req/round  concurrency=${_eff_dynamic_cc}
    login          : ${LOGIN_PATH}   weight=${WEIGHT_LOGIN}  -> ${_eff_login_req} req/round  concurrency=${_eff_login_cc}
    notfound       : ${NOTFOUND_PATH}   weight=${WEIGHT_404}  -> ${_eff_404_req} req/round  concurrency=${_eff_404_cc}
  rounds           : ${ROUNDS}
  total concurrency: ${_eff_total_cc} (sum of per-scenario budgets; Little's Law applies)
  min samples/round: ${MIN_ROUND_SAMPLES}
  warm-up          : ${WARMUP_REQUESTS} req x 4 endpoints (unmeasured)
  keep-alive       : $([[ "$KEEP_ALIVE" -eq 1 ]] && echo on || echo off)
  ab extra flags   : ${AB_FLAGS[*]:-none}
  strict preflight : $([[ "$STRICT_PREFLIGHT" -eq 1 ]] && echo on || echo off)
  CI error gate    : ${MAX_ERROR_RATE:-disabled}
  seed             : ${RUN_SEED}   (reproduce with -R ${RUN_SEED})
  output csv       : ${OUTPUT_CSV}$([[ -e "$OUTPUT_CSV" && "$FORCE_OVERWRITE" -ne 1 ]] && echo '  [WOULD REFUSE: exists, no -f]')
  raw latency dumps: per-run TSV in a temp workdir (LOAD_TEST_KEEP_RAW=1 keeps them)

NOTE: endpoint reachability is NOT validated in dry-run mode.
      Run without --dry-run to execute the pre-flight checks.
EOF
    exit 0
fi

WORKDIR=$(mktemp -d) || { echo "ERROR: could not create temporary working directory." >&2; exit 1; }
# Debug/research hook: LOAD_TEST_KEEP_RAW=1 preserves the workdir (including
# per-run raw latency dumps) so results can be independently re-analyzed.
cleanup_workdir() {
    if [[ "${LOAD_TEST_KEEP_RAW:-0}" != "1" ]]; then
        rm -rf "$WORKDIR"
    else
        echo "NOTE: LOAD_TEST_KEEP_RAW=1 -> raw data retained in ${WORKDIR}" >&2
    fi
}
trap cleanup_workdir EXIT

# Run-metadata sidecar: a JSON snapshot of the exact configuration, tool
# versions, and environment next to the results file. Written BEFORE any
# measurement so even an aborted run leaves provenance behind. Fields are
# quote-stripped because every value here is either validated input
# (SITE_LABEL), derived integers, or tool output rendered single-line.
ab_version_line=$(ab -V 2>&1 | head -1 | tr -d '"' )
kernel_line=$(uname -sr 2>/dev/null | tr -d '"')
host_name=$(hostname 2>/dev/null | tr -d '"' || echo unknown)
META_JSON="${OUTPUT_CSV}.meta.json"
{
    printf '{\n'
    printf '  "generated_at_utc": "%s",\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    printf '  "host": "%s",\n' "$host_name"
    printf '  "script": "load-test.sh",\n'
    printf '  "target_base_url": "%s",\n' "${BASE_URL//\"/}"
    printf '  "site_label": "%s",\n' "$SITE_LABEL"
    printf '  "endpoints": { "static": "%s", "dynamic": "%s", "login": "%s", "notfound": "%s" },\n' \
        "$STATIC_PATH" "$DYNAMIC_PATH" "$LOGIN_PATH" "$NOTFOUND_PATH"
    printf '  "rounds": %d,\n' "$ROUNDS"
    printf '  "total_concurrency": %d,\n' "$TOTAL_CONCURRENCY"
    printf '  "ab_requests_base": %d,\n' "$AB_REQUESTS_BASE"
    printf '  "min_round_samples": %d,\n' "$MIN_ROUND_SAMPLES"
    printf '  "warmup_requests": %d,\n' "$WARMUP_REQUESTS"
    printf '  "weights": { "static": %d, "dynamic": %d, "login": %d, "notfound404": %d },\n' \
        "$WEIGHT_STATIC" "$WEIGHT_DYNAMIC" "$WEIGHT_LOGIN" "$WEIGHT_404"
    printf '  "keep_alive": %s,\n' "$([[ "$KEEP_ALIVE" -eq 1 ]] && echo true || echo false)"
    printf '  "strict_preflight": %s,\n' "$([[ "$STRICT_PREFLIGHT" -eq 1 ]] && echo true || echo false)"
    printf '  "max_error_rate_pct": %s,\n' "$([[ -n "$MAX_ERROR_RATE" ]] && echo "$MAX_ERROR_RATE" || echo null)"
    printf '  "run_seed": %d,\n' "$RUN_SEED"
    printf '  "ab_extra_flags": [%s],\n' "$([[ ${#AB_FLAGS[@]} -gt 0 ]] && printf '"%s", ' "${AB_FLAGS[@]}" | sed 's/, $//' || true)"
    printf '  "ab_version": "%s",\n' "$ab_version_line"
    printf '  "kernel": "%s"\n' "$kernel_line"
    printf '}\n'
} > "$META_JSON"

# Child-process lifecycle: every ab invocation (warm-up AND measured rounds)
# is tracked so an interrupt (Ctrl-C / SIGTERM) tears down in-flight
# benchmarks instead of leaving orphaned ab processes hammering the target
# after the harness dies. Installed EARLY - before pre-flight and warm-up -
# so no phase runs unprotected.
child_pids=()
kill_children() {
    local pid
    if [[ ${#child_pids[@]} -gt 0 ]]; then
        echo "" >&2
        echo "Interrupted: terminating ${#child_pids[@]} in-flight scenario worker(s)..." >&2
        for pid in "${child_pids[@]}"; do
            # Kill each worker's direct child (the ab process) BEFORE the
            # worker subshell itself: once the subshell dies, its children
            # are reparented to init and become untrackable orphans that
            # would keep hammering the target host.
            pkill -TERM -P "$pid" 2>/dev/null || true
            kill "$pid" 2>/dev/null || true
        done
        for pid in "${child_pids[@]}"; do
            wait "$pid" 2>/dev/null || true
        done
        child_pids=()
    fi
}
# exit inside the trap matters: a trapped SIGINT/SIGTERM must terminate the
# harness, not resume execution after the handler returns.
trap 'kill_children; exit 130' INT
trap 'kill_children; exit 143' TERM

# ---------------------------------------------------------------------------
# CSV header
#
# Schema note: columns are append-only relative to schema v1 so that older
# analysis tooling keeps working. Taxonomy columns exist because ab folds
# connection errors, receive errors, LENGTH mismatches (e.g. HTML whose byte
# size varies per request due to CSRF tokens/nonces), and exceptions into a
# single "failed" number, while reporting genuine HTTP-level failures
# (Non-2xx) separately. Collapsing those categories has previously produced
# misleading error rates in this project's reports - see
# plan/PERFORMANCE_OPTIMIZATION_PLAN.md and
# report/october-vs-scriptlog/load_test_analysis_report.md §6.3.
# ---------------------------------------------------------------------------
echo "timestamp,site,round,scenario,url,concurrency,requests_sent,complete_requests,failed_requests,rps,mean_latency_ms,p50_ms,p95_ms,p99_ms,non2xx_requests,connect_errors,receive_errors,length_errors,exceptions,ab_exit,raw_samples" > "$OUTPUT_CSV"

# ---------------------------------------------------------------------------
# Scenario URLs
# ---------------------------------------------------------------------------
declare -A URLS=(
    [Static]="${BASE_URL}${STATIC_PATH}"
    [Dynamic]="${BASE_URL}${DYNAMIC_PATH}"
    [Login]="${BASE_URL}${LOGIN_PATH}"
    [404]="${BASE_URL}${NOTFOUND_PATH}"
)
declare -A EXPECTED_STATUS=(
    [Static]="200"
    [Dynamic]="200"
    [Login]="200"
    [404]="404"
)
declare -A WEIGHTS=(
    [Static]=$WEIGHT_STATIC
    [Dynamic]=$WEIGHT_DYNAMIC
    [Login]=$WEIGHT_LOGIN
    [404]=$WEIGHT_404
)

# ---------------------------------------------------------------------------
# Pre-flight: verify every endpoint returns the status we expect BEFORE
# spending time on a full load test against a broken or mislabeled URL.
#
# Transport failures (DNS, connection refused, timeout) are reported
# separately from unexpected HTTP statuses: the former mean the scenario
# cannot be measured at all; the latter mean results will still be recorded,
# but they measure whatever that endpoint actually returns, not what the
# scenario label implies. With -S (strict) any failure aborts the run.
#
# --max-time bounds each probe so a slow-drip server cannot hang pre-flight
# indefinitely (connect-timeout alone only covers the TCP handshake).
# ---------------------------------------------------------------------------
echo "=== Pre-flight status check: ${SITE_LABEL} ==="
preflight_failed=0
for name in "${!URLS[@]}"; do
    url="${URLS[$name]}"
    expected_status="${EXPECTED_STATUS[$name]}"
    actual_status=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout "$CONNECT_TIMEOUT" --max-time "$PREFLIGHT_MAX_TIME" "$url")
    curl_rc=$?
    if [[ "$curl_rc" -ne 0 || "$actual_status" == "000" ]]; then
        printf "  FAIL  %-8s %-45s -> transport error (curl rc=%d, http_code=%s)\n" "$name" "$url" "$curl_rc" "$actual_status"
        preflight_failed=1
    elif [[ "$actual_status" != "$expected_status" ]]; then
        printf "  WARN  %-8s %-45s -> HTTP %s (expected %s)\n" "$name" "$url" "$actual_status" "$expected_status"
        preflight_failed=1
    else
        printf "  OK    %-8s %-45s -> HTTP %s (expected %s)\n" "$name" "$url" "$actual_status" "$expected_status"
    fi
done
if [[ "$preflight_failed" -eq 1 ]]; then
    echo ""
    if [[ "$STRICT_PREFLIGHT" -eq 1 ]]; then
        echo "STRICT MODE (-S): aborting because one or more endpoints failed" >&2
        echo "pre-flight verification. Nothing was measured." >&2
        exit 1
    fi
    echo "One or more endpoints did not pass pre-flight. Results from those"
    echo "scenarios will still be recorded, but treat them as measuring"
    echo "whatever those endpoints actually return (transport failures"
    echo "included), not what the scenario labels imply."
fi
echo ""

# ---------------------------------------------------------------------------
# Warm-up: prime opcode cache / object cache / query cache so measurement
# begins from a steady state instead of mixing cold-start cost into the first
# recorded rounds (standard practice: discard an initial observation window;
# see Jain 1991, "The Art of Computer Systems Performance Analysis", ch. 12).
# Warm-up failures are surfaced on stderr - never silently swallowed - since
# they usually mean the endpoint is down or already rejecting load.
# ---------------------------------------------------------------------------
echo "=== Warm-up: ${WARMUP_REQUESTS} requests per endpoint ==="
warmup_failed=0
# Fire all endpoint warm-up ab runs concurrently (as a tracked set), so the
# server is primed under CONCURRENT load - matching the measured rounds -
# rather than one endpoint at a time. Concurrent steady state is what the
# measured phase requires; sequential warm-up only primes single-endpoint
# caches. Each run is a tracked background job so an interrupt mid-warm-up
# tears it down through the same lifecycle net as the measured rounds
# (see kill_children); all are awaited below before measuring begins.
for name in "${!URLS[@]}"; do
    url="${URLS[$name]}"
    ab -n "$WARMUP_REQUESTS" -c 5 "${AB_FLAGS[@]}" "$url" >/dev/null 2>&1 &
    wpid=$!
    child_pids+=("$wpid")
done
# Wait for all warm-up runs to complete; surface failures per endpoint.
for wpid in "${child_pids[@]}"; do
    if ! wait "$wpid"; then
        echo "  WARN  warm-up run exited nonzero (endpoint may be unreachable or rejecting load)" >&2
        warmup_failed=1
    fi
done
child_pids=()
if [[ "$warmup_failed" -eq 0 ]]; then
    echo "Warm-up complete (concurrent, opcode/query caches primed)."
else
    echo "Warm-up finished with warnings (see stderr)."
fi
echo ""

# ---------------------------------------------------------------------------
# Run one ab test for a single scenario, parse its output, append to CSV.
# Designed to be called in the background so multiple scenarios run
# concurrently within the same round, approximating mixed real traffic.
# ---------------------------------------------------------------------------
run_scenario() {
    local round=$1 name=$2 url=$3 concurrency=$4 requests=$5
    local out raw_file
    out=$(mktemp -p "$WORKDIR")
    # Per-run raw latency dump (ab -g): TSV, header row, then one row per
    # request. NOTE: the leading starttime column contains SPACES
    # ("Sun Aug 23 06:16:30 2026"), so fixed field indices are wrong; the
    # second-to-last field is always ttime (total response time - the same
    # measure ab's own percentile table uses). Keeping these dumps lets the
    # summary compute TRUE global percentiles across all rounds instead of
    # reporting a biased mean-of-round-percentiles.
    raw_file="${WORKDIR}/raw_${name}_${round}.tsv"

    ab "${AB_FLAGS[@]}" -n "$requests" -c "$concurrency" -g "$raw_file" "$url" > "$out" 2>&1
    local ab_exit=$?

    local complete failed rps mean p50 p95 p99
    local non2xx conn_err recv_err len_err exc_err raw_samples
    complete=$(awk -F': *' '/^Complete requests:/ {print $2}' "$out")
    failed=$(awk -F': *' '/^Failed requests:/ {print $2}' "$out")
    rps=$(awk -F': *' '/^Requests per second:/ {print $2}' "$out" | awk '{print $1}')
    mean=$(awk -F': *' '/^Time per request:/ {print $2; exit}' "$out" | awk '{print $1}')
    p50=$(awk '/^ *50%/ {print $2}' "$out")
    p95=$(awk '/^ *95%/ {print $2}' "$out")
    p99=$(awk '/^ *99%/ {print $2}' "$out")

    # HTTP-level fidelity: ab counts a response as "complete" regardless of
    # its status code and reports Non-2xx responses on a separate line.
    # Without capturing that line, a server answering 500 to every request
    # would appear error-free. See october-vs-scriptlog §6.3.
    non2xx=$(awk -F': *' '/^Non-2xx responses:/ {print $2; exit}' "$out")

    # Failure taxonomy: decompose ab's aggregate "Failed requests" into its
    # four reported causes. This is what disambiguates real transport faults
    # (Connect/Receive/Exceptions) from benign Length mismatches caused by
    # per-request HTML variance such as CSRF tokens.
    read -r conn_err recv_err len_err exc_err < <(awk '
        /\(Connect:/ {
            line = $0
            # Trim surrounding whitespace/parens BEFORE splitting: ab indents
            # the taxonomy line, and untrimmed leading spaces would corrupt
            # the first key (" Connect") and shift every value by one slot.
            gsub(/^[ \t]+/, "", line)
            gsub(/[ \t]+$/, "", line)
            gsub(/[()]/, "", line)
            n = split(line, parts, ", ")
            for (i = 1; i <= n; i++) {
                split(parts[i], pair, ": ")
                val[pair[1]] = pair[2]
            }
        }
        END {
            printf "%s %s %s %s\n", val["Connect"], val["Receive"], val["Length"], val["Exceptions"]
        }
    ' "$out")

    if [[ -z "$complete" ]]; then
        # ab never produced its report block at all: connection refused,
        # DNS failure, timeout, or similar. This is a total failure for
        # this scenario in this round, not a "0/0, nothing happened"
        # result. Count every requested request as failed so it is not
        # silently dropped from the error rate, and preserve ab's own
        # error line for troubleshooting.
        complete=0
        failed=$requests
        rps=0
        mean=0
        p50=0
        p95=0
        p99=0
        non2xx=0
        conn_err=0
        recv_err=0
        len_err=0
        exc_err=0
        raw_samples=0
        local ab_error
        ab_error=$(tr ',' ';' < "$out" | tr '\n' ' ' | sed 's/"/'"'"'/g')
        echo "  WARN  round ${round} ${name}: ab exited ${ab_exit} with no report (${ab_error:-no output})" >&2
    else
        complete=${complete:-0}
        failed=${failed:-0}
        rps=${rps:-0}
        mean=${mean:-0}
        p50=${p50:-0}
        p95=${p95:-0}
        p99=${p99:-0}
        non2xx=${non2xx:-0}
        conn_err=${conn_err:-0}
        recv_err=${recv_err:-0}
        len_err=${len_err:-0}
        exc_err=${exc_err:-0}
        # Count usable samples in THIS run's raw dump (rows after the header
        # with the full column set). The global aggregation across rounds
        # happens once at summary time.
        #
        # Robustness (see report/LOAD_TEST_FORENSIC_AUDIT.md F-05/F-15 and
        # P7 in plan/LOAD_TEST_FORENSIC_REMEDIATION_PLAN.md):
        #   * The header row is checked against the documented ab -g layout
        #     (starttime seconds ctime dtime ttime wait). If it does not match,
        #     extraction FALLS BACK to the legacy $(NF-1) ttime column and a
        #     WARN is printed - a layout change is surfaced instead of silently
        #     producing zero samples.
        #   * starttime contains spaces ("Sun Aug 23 06:16:30 2026"), so fixed
        #     field indices are wrong; ttime is ALWAYS the second-to-last field
        #     and wait is always last in the data rows, so $(NF-1) is robust
        #     regardless of how many tokens starttime expands to.
        #   * The last two fields are validated with decimal-tolerant regexes
        #     so a future ab that prints "0.000000" (not "0") does not drop
        #     every row. A data row needs at least 7 fields (5 from the spaced
        #     starttime + seconds + ctime + dtime + ttime + wait).
        raw_samples=$(awk '
            NR == 1 {
                ok = 1
                if (($NF != "wait" || $(NF - 1) != "ttime")) {
                    print "WARN: ab -g header layout unrecognized; using $(NF - 1) fallback" > "/dev/stderr"
                }
                next
            }
            ok && NF >= 7 && $(NF - 1) ~ /^[0-9]+(\.[0-9]+)?$/ && $NF ~ /^[0-9]+(\.[0-9]+)?$/ { c++ }
            END { print c + 0 }
        ' "$raw_file")
        # A nonzero exit WITH a report means ab aborted mid-run (e.g. after
        # repeated failures). The partial numbers are still recorded below,
        # but they must not pass unnoticed as if they were a clean run.
        if [[ "$ab_exit" -ne 0 ]]; then
            echo "  WARN  round ${round} ${name}: ab exited ${ab_exit} with a PARTIAL report (complete=${complete}, failed=${failed})" >&2
        fi
        # A clean ab exit (0) with non-2xx responses is worth a WARN too:
        # ab treats an all-500 round as a successful run, so without this the
        # terminal would show a "clean" scenario while every response was an
        # error. The CSV still records the true non2xx count for the summary.
        if [[ "$ab_exit" -eq 0 && "${non2xx:-0}" -gt 0 ]]; then
            echo "  WARN  round ${round} ${name}: clean ab exit but ${non2xx} non-2xx response(s)" >&2
        fi
    fi

    # Four scenario workers append rows to the shared CSV concurrently.
    # POSIX only guarantees atomic append() for writes up to PIPE_BUF; each
    # row here is ~200 bytes, safely under the 4096-byte threshold, so rows
    # cannot interleave in practice. Documented assumption (F-11 in report/
    # LOAD_TEST_FORENSIC_AUDIT.md) - safe by construction for this row size.
    printf "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n" \
        "$(date -Iseconds)" "$SITE_LABEL" "$round" "$name" "$(csv_field "$url")" "$concurrency" "$requests" \
        "$complete" "$failed" "$rps" "$mean" "$p50" "$p95" "$p99" \
        "$non2xx" "$conn_err" "$recv_err" "$len_err" "$exc_err" "$ab_exit" "$raw_samples" >> "$OUTPUT_CSV"

    rm -f "$out"

    # Nonzero ab exit (no report at all, or aborted partial report) must be
    # visible to the parent loop's wait() so degraded rounds are surfaced,
    # never silently folded into the averages.
    if [[ "$ab_exit" -ne 0 ]]; then
        return 1
    fi
    return 0
}

# ---------------------------------------------------------------------------
# Main loop: each round fires all four scenarios CONCURRENTLY in the
# background, with per-scenario concurrency and request count derived
# from the configured traffic weights. Concurrency is the primary axis
# of weighting: each scenario gets TOTAL_CONCURRENCY * weight% connections.
# Request counts follow the same weight but are floored to
# MIN_ROUND_SAMPLES to ensure statistical sufficiency, which equalizes
# request volume across scenarios. This is the key behavioral change from
# the original scripts, which ran one scenario to completion before
# starting the next.
# ---------------------------------------------------------------------------
echo "=== Running ${ROUNDS} rounds for ${SITE_LABEL} (mixed concurrent traffic) ==="
echo "Launch-order randomization seed: ${RUN_SEED} (reproduce with -R ${RUN_SEED})"
# Print a warning if MIN_ROUND_SAMPLES overrides any weight-based request
# count, so the user knows the effective request mix differs from the
# configured weights.
for _warn_name in "${!URLS[@]}"; do
    _warn_weight=${WEIGHTS[$_warn_name]}
    _warn_weighted=$(( AB_REQUESTS_BASE * _warn_weight / 100 ))
    [[ "$_warn_weighted" -lt 1 ]] && _warn_weighted=1
    if [[ "$_warn_weighted" -lt "$MIN_ROUND_SAMPLES" ]]; then
        echo "  WARN  ${_warn_name}: weight-based request count (${_warn_weighted}) < MIN_ROUND_SAMPLES (${MIN_ROUND_SAMPLES}) -> using ${MIN_ROUND_SAMPLES} req/round" >&2
    fi
done
unset _warn_name _warn_weight _warn_weighted
# Deterministic base order (sorted): a given seed always yields the same
# permutation regardless of bash associative-array internal hash ordering.
mapfile -t scenario_order < <(printf '%s\n' "${!URLS[@]}" | LC_ALL=C sort)
# The launch-order shuffle below must be reproducible from the full recorded
# seed. Bash's $RANDOM is only 15-bit and SRANDOM is non-resettable, so we
# drive the shuffle from a small LCG seeded with the FULL 32-bit RUN_SEED
# (avoids the modulo-truncation entropy loss of RANDOM=$((seed % 32768)); see
# report/LOAD_TEST_FORENSIC_AUDIT.md F-10). 31-bit output, LCG constants are
# the classic usleep/Lua pair (A=1103515245, C=12345, M=2^31).
_prng_state=$RUN_SEED
prng_next() {
    _prng_state=$(( (_prng_state * 1103515245 + 12345) & 2147483647 ))
}

for (( round=1; round<=ROUNDS; round++ )); do
    # Fisher-Yates shuffle of the launch order, driven by the seeded PRNG:
    # removes the systematic bias of starting every round with the same
    # scenario first while remaining reproducible from the recorded seed.
    shuffled=("${scenario_order[@]}")
    for (( si=${#shuffled[@]} - 1; si > 0; si-- )); do
        prng_next
        sj=$(( _prng_state % (si + 1) ))
        tmp_name=${shuffled[si]}
        shuffled[si]=${shuffled[sj]}
        shuffled[sj]=$tmp_name
    done
    child_pids=()
    for name in "${shuffled[@]}"; do        weight=${WEIGHTS[$name]}
        # Scale concurrency and request volume by this scenario's share of traffic.
        scenario_concurrency=$(( TOTAL_CONCURRENCY * weight / 100 ))
        [[ "$scenario_concurrency" -lt 1 ]] && scenario_concurrency=1
        scenario_requests=$(( AB_REQUESTS_BASE * weight / 100 ))
        [[ "$scenario_requests" -lt "$scenario_concurrency" ]] && scenario_requests=$scenario_concurrency
        # Statistical sufficiency floor: low-weight scenarios would otherwise
        # accumulate only 2-4 samples per round, making per-round percentiles
        # meaningless. Default floor is 30 samples/round, lowered to 10 by
        # --relax-min-samples; an explicit -m always wins (see P1 in plan/
        # LOAD_TEST_FORENSIC_REMEDIATION_PLAN.md).
        [[ "$scenario_requests" -lt "$MIN_ROUND_SAMPLES" ]] && scenario_requests=$MIN_ROUND_SAMPLES
        # ab prints no percentile table at all when -n is 1; keep a hard
        # floor of 2 for users who explicitly set -m 1.
        [[ "$scenario_requests" -lt 2 ]] && scenario_requests=2

        run_scenario "$round" "$name" "${URLS[$name]}" "$scenario_concurrency" "$scenario_requests" &
        child_pids+=($!)
    done
    # Wait for all four scenarios in this round to finish before starting
    # the next round, so rounds do not stack indefinitely if the server
    # is slow to respond. Worker exit statuses are captured: a nonzero
    # status means ab failed or aborted for that scenario and the parent
    # says so instead of letting the round pass as clean.
    degraded_round=0
    for pid in "${child_pids[@]}"; do
        if ! wait "$pid"; then
            degraded_round=1
        fi
    done
    if [[ "$degraded_round" -eq 1 ]]; then
        echo "  WARN  round ${round}: one or more scenario workers reported failures (see rows above)" >&2
    fi

    if (( round % 10 == 0 || round == ROUNDS )); then
        echo "  round ${round}/${ROUNDS} complete"
    fi
done
echo ""

# ---------------------------------------------------------------------------
# Summary: per scenario, compute mean RPS, mean of p95 latency, and error
# rate (failed / complete across ALL rounds, not just successful ones).
# Unlike the original scripts, failed rounds are not excluded from the
# denominator here; they are counted explicitly in the error rate.
# ---------------------------------------------------------------------------
echo "======================================================================"
echo "Test Summary - ${SITE_LABEL} (${ROUNDS} rounds, mixed concurrent traffic)"
echo "======================================================================"
# Methodological caveat surfaced in the summary output itself (not only the
# header): ab is a closed-loop client, so absolute tail latencies (p95, p99)
# are lower bounds - server stalls hide behind ab's in-flight window
# (coordinated omission) and are NOT fully reflected in these figures.
echo "NOTE: tail latencies (p95/p99) are LOWER BOUNDS - ab's closed-loop model"
echo "      hides server stalls behind its in-flight window (coordinated omission)."

# Deterministic output order (F-13 in report/LOAD_TEST_FORENSIC_AUDIT.md):
# associative-array iteration order is undefined in bash, so sort the keys
# exactly as the main loop does.
for name in $(printf '%s\n' "${!URLS[@]}" | LC_ALL=C sort); do
    # True global percentiles: aggregate the raw per-request latencies from
    # every round of this scenario and compute nearest-rank percentiles over
    # the pooled sample. Averaging per-round p95 values (printed alongside)
    # is a biased estimator; this pooled figure is the statistically valid one.
    raw_files=()
    shopt -s nullglob
    raw_files=( "${WORKDIR}/raw_${name}_"*.tsv )
    shopt -u nullglob
    if [[ ${#raw_files[@]} -gt 0 ]]; then
        # Nearest-rank percentiles over the POOLED raw per-request latencies
        # (ttime column) from every round of this scenario. Header is checked
        # once (first line of the stream) against the documented ab -g layout;
        # ttime is extracted as the second-to-last field, which is layout-safe
        # regardless of how many tokens starttime expands to. If the header
        # does not match, extraction FALLS BACK to that $(NF-1) column with a
        # WARN instead of silently disabling percentiles (see P7 in plan/
        # LOAD_TEST_FORENSIC_REMEDIATION_PLAN.md and the raw_samples comment
        # in run_scenario).
        global_stats=$(awk '
            NR == 1 {
                ok = 1
                if ($NF != "wait" || $(NF - 1) != "ttime") {
                    print "WARN: ab -g header layout unrecognized; using $(NF - 1) fallback" > "/dev/stderr"
                }
                next
            }
            ok && NF >= 7 && $(NF - 1) ~ /^[0-9]+(\.[0-9]+)?$/ && $NF ~ /^[0-9]+(\.[0-9]+)?$/ { print $(NF - 1) }
        ' "${raw_files[@]}" \
            | LC_ALL=C sort -n \
            | awk '
                function ceil_int(x) { i = int(x); return (i < x) ? i + 1 : i }
                { v[NR] = $1 }
                END {
                    if (NR == 0) { printf "0 0 0 0"; exit }
                    printf "%d %.3f %.3f %.3f", NR,
                        v[ceil_int(0.50 * NR)],
                        v[ceil_int(0.95 * NR)],
                        v[ceil_int(0.99 * NR)]
                }')
        read -r g_n g_p50 g_p95 g_p99 <<< "$global_stats"
        if [[ -n "$g_n" && "$g_n" -gt 0 ]]; then
            printf "        -> global percentiles over %s pooled raw samples (ttime ms): p50=%s p95=%s p99=%s\n" \
                "$g_n" "$g_p50" "$g_p95" "$g_p99"
        else
            printf "        -> no usable raw latency samples were captured\n"
        fi
    else
        printf "        -> no raw latency data available for this scenario\n"
    fi

    # Per-scenario aggregate over all rounds.
    # Error-rate convention follows load_test_analysis_report.md §7.1: the
    # denominator is requests SENT, not completed+failed, so aborted runs
    # cannot dilute the failure percentage. Three rates are reported because
    # ab folds different failure classes into different counters:
    #   transport = failed/sent          (connect/receive/length/exception)
    #   http      = non2xx/sent          (genuine HTTP-level failures)
    #   combined  = (failed+non2xx)/sent (the CI-gate figure; the two sets
    #                                      are disjoint by construction)
    # Dispersion is reported as sample stddev + coefficient of variation so
    # single-round flukes are visible instead of hidden by the mean.
    awk -F',' -v scenario="$name" '
        function sd(sum, sqsum, count,   mean, var_est) {
            if (count < 2) return 0
            mean = sum / count
            var_est = (sqsum - count * mean * mean) / (count - 1)
            return (var_est > 0) ? sqrt(var_est) : 0
        }
        NR == 1 { next }
        $4 == scenario {
            n++
            sent += $7; complete += $8; failed += $9
            non2xx += $15
            conn += $16; recv += $17; len_err += $18; exc += $19
            rps_sum += $10; rps_sqsum += $10 * $10
            p95_sum += $13; p95_sqsum += $13 * $13
        }
        END {
            if (n == 0) { printf "%-8s: no data recorded\n", scenario; exit }
            avg_rps = rps_sum / n
            sd_rps  = sd(rps_sum, rps_sqsum, n)
            cv_rps  = (avg_rps > 0) ? sd_rps / avg_rps * 100 : 0
            avg_p95 = p95_sum / n
            sd_p95  = sd(p95_sum, p95_sqsum, n)
            cv_p95  = (avg_p95 > 0) ? sd_p95 / avg_p95 * 100 : 0
            tr_rate = (sent > 0) ? failed / sent * 100 : 0
            http_rate = (sent > 0) ? non2xx / sent * 100 : 0
            comb_rate = (sent > 0) ? (failed + non2xx) / sent * 100 : 0
            printf "%-8s: rounds=%d sent=%d complete=%d | transport-err=%d (%.2f%%) non2xx=%d (%.2f%%) combined=%.2f%%\n", \
                scenario, n, sent, complete, failed, tr_rate, non2xx, http_rate, comb_rate
            printf "%-8s   taxonomy: connect=%d receive=%d length=%d exceptions=%d\n", \
                "", conn, recv, len_err, exc
            printf "%-8s   rps mean=%.3f sd=%.3f cv=%.1f%% | round-p95 mean=%.1fms sd=%.1fms cv=%.1f%%\n", \
                "", avg_rps, sd_rps, cv_rps, avg_p95, sd_p95, cv_p95
        }
    ' "$OUTPUT_CSV"
done

# ---------------------------------------------------------------------------
# CI error-rate gate (-e)
#
# Evaluated ONCE over the pooled run: if the combined request-level error
# rate exceeds the configured ceiling the harness exits nonzero so CI pipelines
# treat a performance run with excessive failures as a failed build rather
# than a green build with noisy numbers. An empty result set is also a
# violation: a gate that passes on zero evidence is not a gate.
# ---------------------------------------------------------------------------
overall_rc=0
if [[ -n "$MAX_ERROR_RATE" ]]; then
    verdict=$(awk -F',' -v max="$MAX_ERROR_RATE" '
        NR > 1 { rows++; sent += $7; failed += $9; non2xx += $15 }
        END {
            if (rows == 0 || sent == 0) { printf "0.00 VIOLATION"; exit }
            rate = (failed + non2xx) / sent * 100
            printf "%.2f %s", rate, (rate > max) ? "VIOLATION" : "ok"
        }' "$OUTPUT_CSV")
    rate="${verdict%% *}"
    status="${verdict##* }"
    echo ""
    if [[ -z "$status" || "$status" != "VIOLATION" && "$status" != "ok" ]]; then
        # Unreadable/malformed CSV must fail the gate, not silently pass it.
        echo "ERROR-RATE GATE FAILED: could not evaluate error rate from ${OUTPUT_CSV}." >&2
        overall_rc=1
    elif [[ "$status" == "VIOLATION" ]]; then
        echo "ERROR-RATE GATE FAILED: combined error rate ${rate}% exceeds -e ceiling of ${MAX_ERROR_RATE}%." >&2
        overall_rc=1
    else
        echo "Error-rate gate passed: combined ${rate}% <= ${MAX_ERROR_RATE}% (-e)."
    fi
fi

echo ""
echo "Raw per-round results written to: ${OUTPUT_CSV}"
echo "======================================================================"
exit "$overall_rc"
