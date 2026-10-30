#!/bin/bash
#
# stress-test.sh
#
# Author: M.Noermoehammad
#
# Step-load STRESS test wrapper around Apache Bench (ab).
#
# Companion to load-test.sh (repo root): load-test.sh answers "can this site
# serve the expected mixed traffic with acceptable latency?"; this script
# answers "WHERE is the saturation / breaking point, and how does the site
# behave above it?". It ratchets one target endpoint through increasing
# ApacheBench concurrency levels ("steps") until a stop condition fires:
#
#   Stop conditions (checked after each level; the triggering level is the
#   recorded verdict with its reason):
#     -e  combined error rate ((failed+non2xx)/sent) of the completed level
#         exceeds MAX_ERROR_PCT        -> BREAKING-POINT (error saturation)
#     -L  pooled p95 latency of the completed level exceeds MAX_P95_MS
#                                       -> SATURATED (latency budget)
#     built-in: mean rps of a level drops below 50% of the best observed
#         level's rps (only evaluated after a peak level exists)
#                                       -> COLLAPSED (throughput)
#     -C ceiling reached with no condition firing
#                                       -> NO-BREAKDOWN (ceiling reached)
#
# METHODOLOGY
#   Levels               : concurrency starts at -c, then doubles each level
#                          (-z auto) or advances by a fixed increment
#                          (-z INT), capped by -C. Each level runs
#                          -N "hold runs" of ab, so per-level averages are
#                          not single-run flukes. Per-level requests are
#                          floored at the level size (ab -n must be >= -c).
#   Per-level percentiles: nearest-rank over the POOLED raw per-request
#                          times (ab -g ttime column) of the level's runs -
#                          the same statistic load-test.sh reports globally.
#                          Averaging per-run percentiles is biased and NOT
#                          used for decisions.
#   Error rates          : denominators are requests SENT (not completed),
#                          transport = failed/sent; http = non2xx/sent;
#                          combined = (failed+non2xx)/sent (the -e figure).
#                          Same taxonomy discipline as load-test.sh.
#   Single-endpoint design: capacity ramping needs unambiguous per-level
#                          attribution, so one endpoint is stressed at a
#                          time (-q dynamic|static|login). For a realistic
#                          mixed background, run load-test.sh with a small
#                          -i alongside a soak/spike run, not a stress run.
#   Coordinated omission : like any closed-loop client, ab hides server
#                          stalls behind its own in-flight window; absolute
#                          tail latencies (p95/p99) are lower bounds.
#   Client-side caveat   : ab consumes one file descriptor per concurrent
#                          connection; on low `ulimit -n` hosts the CLIENT
#                          can be the bottleneck. When connect errors appear
#                          the script prints an advisory to check
#                          `ulimit -n` before re-interpreting breakdowns.
#
# Requirements: ab (apache2-utils), curl, awk, bc.
#
# Usage:
#   ./stress-test.sh -u https://scriptlog.ddev.site \
#       -d "/?p=1" -r ./stress_scriptlog.csv
#
#   ./stress-test.sh -u https://scriptlog.ddev.site -q static \
#       -s /public/themes/blog/assets/css/style.sea.min.css \
#       -r ./stress_static.csv
#
#   ./stress-test.sh --dry-run -u https://x.test -d "/?p=1"

set -uo pipefail

export LC_ALL=C

# ---------------------------------------------------------------------------
# Defaults (override via flags; see usage() below)
# ---------------------------------------------------------------------------
BASE_URL=""
SITE_LABEL="site"
STATIC_PATH=""
DYNAMIC_PATH="/?p=1"
LOGIN_PATH=""
TARGET_ENDPOINT="dynamic"       # which scenario to stress: dynamic|static|login

START_CONCURRENCY=5             # -c: first level's concurrency
MAX_LEVEL=200                   # -C/-i: ceiling, never exceeded

MAX_ERROR_PCT="5.0"             # -e: combined error-rate stop threshold in %
MAX_P95_MS="2000"               # -L: pooled p95 latency stop threshold in ms

STEP_SPEC="auto"                # -z: 'auto' (double each level) or an INT increment
HOLD_RUNS=3                     # -N: ab runs per level
REQUESTS_PER_RUN=100            # -b: base ab -n per run (floored at level size)
PAUSE_SECONDS=2                 # -p: sleep between levels (let server shed occupancy)

WARMUP_REQUESTS=50              # -W: throwaway requests before measurement
KEEP_ALIVE=0                    # -K: ab -k
STRICT_PREFLIGHT=0              # -S: abort before measuring on pre-flight failure
CONNECT_TIMEOUT=5
PREFLIGHT_MAX_TIME=10

OUTPUT_CSV=""
FORCE_OVERWRITE=0               # -f
DRY_RUN=0                       # --dry-run shim

usage() {
    cat <<EOF
Usage: $0 -u BASE_URL [options]

Required:
  -u  Base URL, e.g. https://scriptlog.ddev.site

Target selection:
  -q  Endpoint to stress: dynamic | static | login (default: dynamic)
  -s  Static asset path (REQUIRED only when -q static)
  -d  Dynamic content path (default: "/?p=1"; used when -q dynamic)
  -g  Login controller path (REQUIRED only when -q login), e.g. /admin/login.php

Ramp shape:
  -c  Starting concurrency (default: $START_CONCURRENCY)
  -i  Max concurrency ceiling (alias of -C; default: $MAX_LEVEL)
  -C  Max concurrency ceiling (default: $MAX_LEVEL)
  -z  Step mode: 'auto' (double each level) or a positive integer increment
      (default: $STEP_SPEC)
  -N  ab runs per level, averaged (default: $HOLD_RUNS)
  -b  Base ab requests per run (floored at the level's concurrency; default: $REQUESTS_PER_RUN)

Stop conditions:
  -e  Stop when a level's COMBINED error rate ((failed+non2xx)/sent) exceeds
      this percentage (default: $MAX_ERROR_PCT; range 0-100)
  -L  Stop when a level's pooled p95 latency exceeds this value in ms
      (default: $MAX_P95_MS)
  -p  Seconds to sleep between levels (default: $PAUSE_SECONDS)

General:
  -l  Label for output (default: "site")
  -W  Warm-up requests before measurement (default: $WARMUP_REQUESTS)
  -K  Use HTTP keep-alive (ab -k), must be held constant across comparisons
  -S  Strict mode: abort before measuring if the target fails pre-flight
  -r  CSV output path (default: ./stress_<label>_<timestamp>.csv)
  -f  Overwrite the CSV output file if it already exists
  --dry-run  Print the resolved ramp (levels, per-level requests, stop
      criteria, endpoint) and exit WITHOUT network I/O
  -h  Show this help
EOF
    exit 1
}

# Long-option shim (--dry-run), mirrors load-test.sh.
script_args=()
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
        *) script_args+=("$arg") ;;
    esac
done
set -- "${script_args[@]}"

while getopts ":u:l:s:d:g:q:i:C:c:z:N:b:L:W:e:p:r:fKSh" opt; do
    case "$opt" in
        u) BASE_URL="$OPTARG" ;;
        l) SITE_LABEL="$OPTARG" ;;
        s) STATIC_PATH="$OPTARG" ;;
        d) DYNAMIC_PATH="$OPTARG" ;;
        g) LOGIN_PATH="$OPTARG" ;;
        q) TARGET_ENDPOINT="$OPTARG" ;;
        i) MAX_LEVEL="$OPTARG" ;;
        C) MAX_LEVEL="$OPTARG" ;;
        c) START_CONCURRENCY="$OPTARG" ;;
        z) STEP_SPEC="$OPTARG" ;;
        N) HOLD_RUNS="$OPTARG" ;;
        b) REQUESTS_PER_RUN="$OPTARG" ;;
        L) MAX_P95_MS="$OPTARG" ;;
        e) MAX_ERROR_PCT="$OPTARG" ;;
        p) PAUSE_SECONDS="$OPTARG" ;;
        r) OUTPUT_CSV="$OPTARG" ;;
        f) FORCE_OVERWRITE=1 ;;
        W) WARMUP_REQUESTS="$OPTARG" ;;
        K) KEEP_ALIVE=1 ;;
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

case "$TARGET_ENDPOINT" in
    dynamic|static|login) ;;
    *) echo "ERROR: -q TARGET_ENDPOINT must be one of: dynamic, static, login (got '$TARGET_ENDPOINT')." >&2; exit 1 ;;
esac

if [[ "$TARGET_ENDPOINT" == "static" && -z "$STATIC_PATH" ]]; then
    echo "ERROR: -s STATIC_PATH is required when -q static (point it at a real static asset)." >&2
    exit 1
fi
if [[ "$TARGET_ENDPOINT" == "login" && -z "$LOGIN_PATH" ]]; then
    echo "ERROR: -g LOGIN_PATH is required when -q login." >&2
    exit 1
fi

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
require_positive_int C "$MAX_LEVEL" "max concurrency ceiling"
[[ -n "$MAX_LEVEL" && "$MAX_LEVEL" -lt 2 ]] && { echo "ERROR: -C (max concurrency ceiling) must be >= 2, got '$MAX_LEVEL'." >&2; exit 1; }
require_positive_int c "$START_CONCURRENCY" "starting concurrency"
require_positive_int N "$HOLD_RUNS" "ab runs per level"
require_positive_int b "$REQUESTS_PER_RUN" "base requests per run"
require_positive_int W "$WARMUP_REQUESTS" "warm-up requests per endpoint"
if ! [[ "$PAUSE_SECONDS" =~ ^[0-9]+$ ]]; then
    echo "ERROR: -p (pause seconds between levels) must be a non-negative integer, got '$PAUSE_SECONDS'." >&2
    exit 1
fi
[[ -n "$MAX_P95_MS" ]] || { echo "ERROR: -L (p95 latency budget) must be set." >&2; exit 1; }
if ! [[ "$MAX_P95_MS" =~ ^[0-9]+$ ]]; then
    echo "ERROR: -L (p95 latency budget) must be an integer number of ms, got '$MAX_P95_MS'." >&2
    exit 1
fi
if [[ "$START_CONCURRENCY" -gt "$MAX_LEVEL" ]]; then
    echo "ERROR: -c starting concurrency ($START_CONCURRENCY) exceeds -C ceiling ($MAX_LEVEL)." >&2
    exit 1
fi
if [[ -n "$MAX_ERROR_PCT" ]]; then
    if ! [[ "$MAX_ERROR_PCT" =~ ^[0-9]+([.][0-9]+)?$ ]]; then
        echo "ERROR: -e (error-rate stop threshold) must be a number between 0 and 100, got '$MAX_ERROR_PCT'." >&2
        exit 1
    fi
    if ! awk -v v="$MAX_ERROR_PCT" 'BEGIN { exit !(v >= 0 && v <= 100) }'; then
        echo "ERROR: -e (error-rate stop threshold) must be between 0 and 100, got '$MAX_ERROR_PCT'." >&2
        exit 1
    fi
fi
case "$STEP_SPEC" in
    auto) ;;
    *)
        if ! [[ "$STEP_SPEC" =~ ^[0-9]+$ ]] || [[ "$STEP_SPEC" -lt 1 ]]; then
            echo "ERROR: -z (step mode) must be 'auto' or a positive integer, got '$STEP_SPEC'." >&2
            exit 1
        fi
        ;;
esac

# Base URL hygiene: scheme + host + no trailing slashes (same rules as load-test.sh).
case "$BASE_URL" in
    http://*|https://*) ;;
    *)
        echo "ERROR: -u BASE_URL must start with http:// or https:// (got '$BASE_URL')." >&2
        exit 1
        ;;
esac
url_host_part=${BASE_URL#*://}
url_host_part=${url_host_part%%/*}
if [[ -z "$url_host_part" ]]; then
    echo "ERROR: -u BASE_URL is missing a host component (got '$BASE_URL')." >&2
    exit 1
fi
while [[ "$BASE_URL" == */ ]]; do
    BASE_URL="${BASE_URL%/}"
done

normalize_path() {
    local q="$1"
    case "$q" in
        /*) printf '%s' "$q" ;;
        *)  printf '/%s' "$q" ;;
    esac
}
[[ -n "$STATIC_PATH" ]]  && STATIC_PATH=$(normalize_path "$STATIC_PATH")
DYNAMIC_PATH=$(normalize_path "$DYNAMIC_PATH")
[[ -n "$LOGIN_PATH" ]]   && LOGIN_PATH=$(normalize_path "$LOGIN_PATH")

# Resolve the target URL and expected pre-flight status.
case "$TARGET_ENDPOINT" in
    static) TARGET_URL="${BASE_URL}${STATIC_PATH}" ;;
    login)  TARGET_URL="${BASE_URL}${LOGIN_PATH}" ;;
    *)      TARGET_URL="${BASE_URL}${DYNAMIC_PATH}" ;;
esac
EXPECTED_STATUS="200"

# CSV safety: same positional-parsing policy as load-test.sh.
SITE_LABEL=${SITE_LABEL//,/;}
SITE_LABEL=${SITE_LABEL//\"/}
SITE_LABEL=${SITE_LABEL//$'\r'/ }
SITE_LABEL=${SITE_LABEL//$'\n'/ }
csv_field() {
    local val="$1"
    val=${val//,/;}
    val=${val//\"/}
    val=${val//$'\r'/ }
    val=${val//$'\n'/ }
    printf '%s' "$val"
}

# Keep-alive policy mirrors load-test.sh: hold -k constant across runs you want
# to compare, or the numbers are not comparable.
AB_FLAGS=()
if [[ "$KEEP_ALIVE" -eq 1 ]]; then
    AB_FLAGS+=(-k)
fi

# Resolve the level sequence (used by --dry-run AND the run loop).
LEVELS=()
level_cursor=$START_CONCURRENCY
LEVELS+=("$level_cursor")
while [[ "$level_cursor" -lt "$MAX_LEVEL" ]]; do
    case "$STEP_SPEC" in
        auto) next=$(( level_cursor * 2 )) ;;
        *)    next=$(( level_cursor + STEP_SPEC )) ;;
    esac
    [[ "$next" -gt "$MAX_LEVEL" ]] && next=$MAX_LEVEL
    if [[ "$next" -le "$level_cursor" ]]; then
        # Degenerate guard: an increment smaller than the gap can never loop
        # forever with positive ints, but defend against overflow wraps.
        next=$(( level_cursor + 1 ))
        [[ "$next" -gt "$MAX_LEVEL" ]] && break
    fi
    LEVELS+=("$next")
    level_cursor=$next
done

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
if [[ -z "$OUTPUT_CSV" ]]; then
    OUTPUT_CSV="./stress_${SITE_LABEL// /_}_$(printf '%s' "$TARGET_ENDPOINT")_${TIMESTAMP}.csv"
fi
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

# --dry-run: print the resolved ramp without touching the network.
if [[ "$DRY_RUN" -eq 1 ]]; then
    cat <<EOF
DRY RUN - planned stress ramp for '${SITE_LABEL}' (nothing was executed)

  target           : ${TARGET_URL}
  ramp levels      : ${LEVELS[*]}
  hold runs/level  : ${HOLD_RUNS}
  run size         : per level max(${REQUESTS_PER_RUN}, level) requests per ab run
  start / ceiling  : ${START_CONCURRENCY} -> ${MAX_LEVEL}
  step mode        : ${STEP_SPEC}
  stop conditions
    error rate     : combined > ${MAX_ERROR_PCT}%
    latency budget : pooled p95 > ${MAX_P95_MS}ms
    throughput     : rps < 50% of best observed level (built-in)
  warm-up          : ${WARMUP_REQUESTS} requests (unmeasured)
  keep-alive       : $([[ "$KEEP_ALIVE" -eq 1 ]] && echo on || echo off)
  strict preflight : $([[ "$STRICT_PREFLIGHT" -eq 1 ]] && echo on || echo off)
  output csv       : ${OUTPUT_CSV}$([[ -e "$OUTPUT_CSV" && "$FORCE_OVERWRITE" -ne 1 ]] && echo '  [WOULD REFUSE: exists, no -f]')

NOTE: target reachability is NOT validated in dry-run mode.
      Run without --dry-run to execute the pre-flight check.
EOF
    exit 0
fi

WORKDIR=$(mktemp -d) || { echo "ERROR: could not create temporary working directory." >&2; exit 1; }
cleanup_workdir() {
    if [[ "${STRESS_KEEP_RAW:-0}" != "1" ]]; then
        rm -rf "$WORKDIR"
    else
        echo "NOTE: STRESS_KEEP_RAW=1 -> raw data retained in ${WORKDIR}" >&2
    fi
}
trap cleanup_workdir EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

# Run-metadata sidecar (provenance, written BEFORE any measurement).
ab_version_line=$(ab -V 2>&1 | head -1 | tr -d '"')
kernel_line=$(uname -sr 2>/dev/null | tr -d '"')
host_name=$(hostname 2>/dev/null | tr -d '"' || echo unknown)
META_JSON="${OUTPUT_CSV}.meta.json"
{
    printf '{\n'
    printf '  "generated_at_utc": "%s",\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    printf '  "host": "%s",\n' "$host_name"
    printf '  "script": "stress-test.sh",\n'
    printf '  "target_base_url": "%s",\n' "${BASE_URL//\"/}"
    printf '  "site_label": "%s",\n' "$SITE_LABEL"
    printf '  "target_endpoint": "%s",\n' "$TARGET_ENDPOINT"
    printf '  "target_url": "%s",\n' "${TARGET_URL//\"/}"
    printf '  "levels": [%s],\n' "$(printf '%s, ' "${LEVELS[@]}" | sed 's/, $//')"
    printf '  "hold_runs": %d,\n' "$HOLD_RUNS"
    printf '  "requests_per_run": %d,\n' "$REQUESTS_PER_RUN"
    printf '  "step_spec": "%s",\n' "$STEP_SPEC"
    printf '  "max_error_pct": %s,\n' "$MAX_ERROR_PCT"
    printf '  "max_p95_ms": %d,\n' "$MAX_P95_MS"
    printf '  "warmup_requests": %d,\n' "$WARMUP_REQUESTS"
    printf '  "keep_alive": %s,\n' "$([[ "$KEEP_ALIVE" -eq 1 ]] && echo true || echo false)"
    printf '  "strict_preflight": %s,\n' "$([[ "$STRICT_PREFLIGHT" -eq 1 ]] && echo true || echo false)"
    printf '  "ab_version": "%s",\n' "$ab_version_line"
    printf '  "kernel": "%s"\n' "$kernel_line"
    printf '}\n'
} > "$META_JSON"

# Internal per-level rollup (level, runs, sent, complete, failed, non2xx,
# mean_rps, rps_sd, mean_p95, pooled p50/p95/p99, combined_err_pct).
LEVELS_TSV="${WORKDIR}/levels.tsv"
printf 'level\truns\tsent\tcomplete\tfailed\tnon2xx\tmean_rps\trps_sd\tmean_p95\tp50\tp95\tp99\tcombined_err\n' > "$LEVELS_TSV"

# CSV header (schema shared with load-test.sh plus 'level' and 'run'; the
# 404-specific columns of load-test.sh are not applicable here).
echo "timestamp,label,level,run_index,endpoint,url,concurrency,requests_sent,complete_requests,failed_requests,rps,mean_latency_ms,p50_ms,p95_ms,p99_ms,non2xx_requests,connect_errors,receive_errors,length_errors,exceptions,ab_exit" > "$OUTPUT_CSV"

# ---------------------------------------------------------------------------
# Pre-flight: verify the target responds before spending time on a ramp
# against a down or mislabeled endpoint (same discipline as load-test.sh).
# ---------------------------------------------------------------------------
echo "=== Pre-flight status check: ${TARGET_URL} ==="
actual_status=$(curl -s -o /dev/null -w "%{http_code}" \
    --connect-timeout "$CONNECT_TIMEOUT" --max-time "$PREFLIGHT_MAX_TIME" "$TARGET_URL")
curl_rc=$?
preflight_failed=0
if [[ "$curl_rc" -ne 0 || "$actual_status" == "000" ]]; then
    echo "  FAIL  ${TARGET_URL} -> transport error (curl rc=$curl_rc, http_code=$actual_status)"
    preflight_failed=1
elif [[ "$actual_status" != "$EXPECTED_STATUS" ]]; then
    echo "  WARN  ${TARGET_URL} -> HTTP $actual_status (expected $EXPECTED_STATUS)"
    preflight_failed=1
else
    echo "  OK    ${TARGET_URL} -> HTTP $actual_status"
fi
if [[ "$preflight_failed" -eq 1 ]]; then
    if [[ "$STRICT_PREFLIGHT" -eq 1 ]]; then
        echo "STRICT MODE (-S): aborting because the target failed pre-flight. Nothing was measured." >&2
        exit 1
    fi
    echo "NOTE: results will measure whatever ${TARGET_URL} actually returns," >&2
    echo "      not what the scenario label implies." >&2
fi

# Warm-up (same rationale as load-test.sh: measure from steady state).
echo "=== Warm-up: ${WARMUP_REQUESTS} requests ==="
if ab ${AB_FLAGS[@]+"${AB_FLAGS[@]}"} -n "$WARMUP_REQUESTS" -c 5 "$TARGET_URL" >/dev/null 2>&1; then
    echo "Warm-up complete (opcode/query caches primed)."
else
    echo "Warm-up finished with warnings (endpoint may be unreachable or rejecting load)." >&2
fi
echo ""

# ---------------------------------------------------------------------------
# ab_run_and_parse LEVEL RUN_INDEX CONCURRENCY REQUESTS
#
# One ab invocation: parse its report (identical parsing idioms to
# load-test.sh), append one CSV row. Echoes "rps failed non2xx" on stdout for
# the caller's rollup. Returns 1 iff ab exited nonzero.
# ---------------------------------------------------------------------------
ab_run_and_parse() {
    local lvl=$1 idx=$2 conc=$3 requests=$4
    local out raw_file
    out=$(mktemp -p "$WORKDIR") || return 1
    raw_file="${WORKDIR}/raw_${lvl}_${idx}.tsv"

    ab ${AB_FLAGS[@]+"${AB_FLAGS[@]}"} -n "$requests" -c "$conc" -g "$raw_file" "$TARGET_URL" > "$out" 2>&1
    local ab_exit=$?

    local complete failed rps mean p50 p95 p99
    local non2xx conn_err recv_err len_err exc_err
    complete=$(awk -F': *' '/^Complete requests:/ {print $2}' "$out")
    failed=$(awk -F': *' '/^Failed requests:/ {print $2}' "$out")
    rps=$(awk -F': *' '/^Requests per second:/ {print $2}' "$out" | awk '{print $1}')
    mean=$(awk -F': *' '/^Time per request:/ {print $2; exit}' "$out" | awk '{print $1}')
    p50=$(awk '/^ *50%/ {print $2}' "$out")
    p95=$(awk '/^ *95%/ {print $2}' "$out")
    p99=$(awk '/^ *99%/ {print $2}' "$out")
    non2xx=$(awk -F': *' '/^Non-2xx responses:/ {print $2; exit}' "$out")
    read -r conn_err recv_err len_err exc_err < <(awk '
        /\(Connect:/ {
            line = $0
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
        # No report block: connection refused / DNS failure / timeout. Count
        # every requested request as failed so nothing is silently dropped.
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
        echo "  WARN  level ${lvl} run ${idx}: ab exited ${ab_exit} with no report (server down or refusing connections?)" >&2
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
        if [[ "$ab_exit" -ne 0 ]]; then
            echo "  WARN  level ${lvl} run ${idx}: ab exited ${ab_exit} with a PARTIAL report (complete=${complete}, failed=${failed})" >&2
        fi
        if [[ "$ab_exit" -eq 0 && "$non2xx" -gt 0 ]]; then
            echo "  WARN  level ${lvl} run ${idx}: clean ab exit but ${non2xx} non-2xx response(s)" >&2
        fi
        if [[ "$conn_err" -gt 0 ]]; then
            echo "  ADVISORY  level ${lvl} run ${idx}: ${conn_err} connect error(s). On stressed clients the LOCAL" >&2
            echo "                    ulimit -n (open file descriptors) can be the bottleneck: check it before" >&2
            echo "                    re-interpreting this as a server-side breakdown." >&2
        fi
    fi

    printf "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n" \
        "$(date -Iseconds)" "$SITE_LABEL" "$lvl" "$idx" "$TARGET_ENDPOINT" "$(csv_field "$TARGET_URL")" "$conc" "$requests" \
        "$complete" "$failed" "$rps" "$mean" "$p50" "$p95" "$p99" \
        "$non2xx" "$conn_err" "$recv_err" "$len_err" "$exc_err" "$ab_exit" >> "$OUTPUT_CSV"

    rm -f "$out"
    if [[ "$ab_exit" -ne 0 ]]; then
        return 1
    fi
    return 0
}

# ---------------------------------------------------------------------------
# Level rollup: after a level's runs finish, compute pooled percentiles over
# the level's raw dumps and per-level aggregates, append to LEVELS_TSV, and
# print the staircase row. Echoes "mean_rps p95_pool combined_err" for the
# caller's stop-condition check.
# ---------------------------------------------------------------------------
level_rollup() {
    local lvl=$1
    awk -F',' -v lvl="$lvl" '
        function sd(sum, sqsum, count,   mean, var_est) {
            if (count < 2) return 0
            mean = sum / count
            var_est = (sqsum - count * mean * mean) / (count - 1)
            return (var_est > 0) ? sqrt(var_est) : 0
        }
        NR == 1 { next }
        $3 == lvl {
            n++
            sent += $8; complete += $9; failed += $10
            non2xx += $16
            rps_sum += $11; rps_sqsum += $11 * $11
            p95_sum += $14
        }
        END {
            if (n == 0) { printf "0 0 0 0 0 0 0 0 0 0 0 0 0\n"; exit }
            mean_rps = rps_sum / n
            sd_rps = sd(rps_sum, rps_sqsum, n)
            mean_p95 = p95_sum / n
            sent = (sent == 0) ? 1 : sent
            err = (failed + non2xx) / sent * 100
            printf "%d %d %d %d %d %d %.3f %.3f %.1f %d\n", \
                lvl, n, sent, complete, failed, non2xx, mean_rps, sd_rps, mean_p95, 0
        }
    ' "$OUTPUT_CSV"
}

pooled_percentiles() {
    # Nearest-rank percentiles over the POOLED ttime samples of the given raw
    # files (same extraction logic as load-test.sh's summary stage).
    awk '
        NR == 1 {
            ok = 1
            if ($NF != "wait" || $(NF - 1) != "ttime") {
                print "WARN: ab -g header layout unrecognized; using $(NF - 1) fallback" > "/dev/stderr"
            }
            next
        }
        ok && NF >= 7 && $(NF - 1) ~ /^[0-9]+(\.[0-9]+)?$/ && $NF ~ /^[0-9]+(\.[0-9]+)?$/ { print $(NF - 1) }
    ' "$@" \
        | LC_ALL=C sort -n \
        | awk '
            function ceil_int(x) { i = int(x); return (i < x) ? i + 1 : i }
            { v[NR] = $1 }
            END {
                if (NR == 0) { printf "0 0 0 0"; exit }
                printf "%d %.3f %.3f %.3f", NR, v[ceil_int(0.50 * NR)], v[ceil_int(0.95 * NR)], v[ceil_int(0.99 * NR)]
            }'
}

# ---------------------------------------------------------------------------
# Main ramp loop
# ---------------------------------------------------------------------------
echo "=== Stress ramp: ${SITE_LABEL} -> ${TARGET_URL} ==="
echo "Levels: ${LEVELS[*]}  | hold runs/level: ${HOLD_RUNS} | stop: err>${MAX_ERROR_PCT}% p95>${MAX_P95_MS}ms rps<50%of-peak"

STOP_REASON=""
STOP_LEVEL=""
best_rps=0
best_level=0
peak_seen=0

for lvl in "${LEVELS[@]}"; do
    echo "--- level concurrency = ${lvl} ---"
    level_requests=$REQUESTS_PER_RUN
    [[ "$level_requests" -lt "$lvl" ]] && level_requests=$lvl
    [[ "$level_requests" -lt 2 ]] && level_requests=2

    for run_idx in $(seq 1 "$HOLD_RUNS"); do
        ab_run_and_parse "$lvl" "$run_idx" "$lvl" "$level_requests" || break
    done

    # Rollup for this level (per-run aggregates). Pooled percentiles come from
    # this level's raw dumps only.
    read -r _lvl _runs l_sent l_complete l_failed l_non2xx mean_rps rps_sd mean_p95 _pad \
        < <(level_rollup "$lvl")
    raw_files=()
    shopt -s nullglob
    raw_files=( "${WORKDIR}/raw_${lvl}_"*.tsv )
    shopt -u nullglob
    if [[ ${#raw_files[@]} -gt 0 ]]; then
        read -r g_n g_p50 g_p95 g_p99 <<< "$(pooled_percentiles "${raw_files[@]}")"
    else
        g_n=0; g_p50=0; g_p95=0; g_p99=0
    fi
    l_sent=${l_sent:-0}
    l_failed=${l_failed:-0}
    l_non2xx=${l_non2xx:-0}
    combined_err=$(awk -v f="$l_failed" -v n2="$l_non2xx" -v s="$l_sent" 'BEGIN { if (s <= 0) s = 1; printf "%.2f", (f + n2) / s * 100 }')
    mean_rps=${mean_rps:-0}
    g_p95=${g_p95:-0}
    g_n=${g_n:-0}
    printf "    level %-4s rps mean=%.1f (sd=%.1f) | pooled p50=%s p95=%s p99=%s (%s samples) | combined err=%s%% (%d runs)\n" \
        "$lvl" "$mean_rps" "$(awk -v v="${rps_sd:-0}" 'BEGIN{printf "%.1f", v}')" "$g_p50" "$g_p95" "$g_p99" "$g_n" "$combined_err" "${_runs:-0}"

    # Track the peak level for the built-in throughput-collapse condition.
    if awk -v a="$mean_rps" -v b="$best_rps" 'BEGIN { exit !(a > b) }'; then
        best_rps=$mean_rps
        best_level=$lvl
    fi
    [[ "$lvl" -gt "$best_level" ]] && peak_seen=1

    # Append rollup row.
    printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n' \
        "$lvl" "${_runs:-0}" "$l_sent" "$l_complete" "$l_failed" "$l_non2xx" \
        "$mean_rps" "${rps_sd:-0}" "${mean_p95:-0}" "$g_p50" "$g_p95" "$g_p99" "$combined_err" >> "$LEVELS_TSV"

    # ---- Stop conditions (evaluated AFTER a level completes) ----
    # 1. Error saturation: combined error rate over the -e ceiling.
    if awk -v r="$combined_err" -v m="$MAX_ERROR_PCT" 'BEGIN { exit !(r > m) }'; then
        STOP_REASON="BREAKING-POINT error saturation"
        STOP_LEVEL=$lvl
        break
    fi
    # 2. Latency budget: pooled p95 over -L ms.
    if awk -v l="$g_p95" -v m="$MAX_P95_MS" 'BEGIN { exit !(l > m) }'; then
        STOP_REASON="SATURATED latency budget"
        STOP_LEVEL=$lvl
        break
    fi
    # 3. Throughput collapse: below half of the best level's rps, but only
    #    once a genuine peak level exists (the condition is meaningless when
    #    ascending without ever having seen a peak).
    if [[ "$peak_seen" -eq 1 && "$best_level" -lt "$lvl" ]]; then
        if awk -v a="$mean_rps" -v b="$best_rps" 'BEGIN { exit !(a < b * 0.5) }'; then
            STOP_REASON="COLLAPSED throughput"
            STOP_LEVEL=$lvl
            break
        fi
    fi

    # Pause between levels (except after the final level) so connection pools
    # and request queues shed occupancy before the next step starts.
    if [[ "$lvl" -lt "${LEVELS[${#LEVELS[@]} - 1]}" && "$PAUSE_SECONDS" -gt 0 ]]; then
        sleep "$PAUSE_SECONDS"
    fi
done

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo ""
echo "======================================================================"
echo "Stress Summary - ${SITE_LABEL} (${TARGET_ENDPOINT} endpoint, ceiling ${MAX_LEVEL})"
echo "======================================================================"
echo "NOTE: tail latencies (p95/p99) are LOWER BOUNDS - ab's closed-loop model"
echo "      hides server stalls behind its in-flight window (coordinated omission)."
echo ""

if [[ ${#LEVELS[@]} -eq 0 ]]; then
    echo "No levels were run."
fi

# Staircase table from the CSV (per-level re-aggregation, deterministic order).
# Note: numeric key sort is a hand-rolled insertion sort because `asort()` is a
# gawk extension that is absent on mawk-only hosts.
awk -F',' '
    function sd(sum, sqsum, count,   mean, var_est) {
        if (count < 2) return 0
        mean = sum / count
        var_est = (sqsum - count * mean * mean) / (count - 1)
        return (var_est > 0) ? sqrt(var_est) : 0
    }
    NR == 1 { next }
    {
        lvl = $3 + 0
        n[lvl]++
        sent[lvl] += $8; comp[lvl] += $9; fail[lvl] += $10; n2xx[lvl] += $16
        conn[lvl] += $17; recv[lvl] += $18
        rps_sum[lvl] += $11; rps_sq[lvl] += $11 * $11
        p95_sum[lvl] += $14
        seen[lvl] = 1
    }
    END {
        len = 0
        for (lvl in seen) { keys[len] = lvl; len++ }
        for (i = 1; i < len; i++) {
            v = keys[i]
            for (j = i - 1; j >= 0 && keys[j] > v; j--) keys[j + 1] = keys[j]
            keys[j + 1] = v
        }
        printf "%-8s %-6s %-16s %-18s %-12s %-10s\n", "level", "runs", "rps mean(sd)", "err%(combined)", "conn-err", "p95 mean(ms)"
        for (i = 0; i < len; i++) {
            lvl = keys[i]
            avg_rps = (n[lvl] > 0) ? rps_sum[lvl] / n[lvl] : 0
            s_rps = sd(rps_sum[lvl], rps_sq[lvl], n[lvl])
            s2 = (sent[lvl] == 0) ? 1 : sent[lvl]
            err = (fail[lvl] + n2xx[lvl]) / s2 * 100
            p95m = (n[lvl] > 0) ? p95_sum[lvl] / n[lvl] : 0
            printf "%-8s %-6d %-16.1f %-18.2f %-12d %-10.1f\n", \
                lvl, n[lvl], avg_rps, err, conn[lvl], p95m
        }
    }
' "$OUTPUT_CSV"

if [[ -n "${STOP_REASON:-}" ]]; then
    echo ""
    echo "VERDICT: ${STOP_REASON} at level ${STOP_LEVEL}"
    case "$STOP_REASON" in
        "BREAKING-POINT"*) echo "  The combined error rate crossed the -e ceiling: the server is rejecting/failed beyond that concurrency." ;;
        "SATURATED"*)      echo "  Pooled p95 crossed the -L latency budget: the endpoint no longer meets the SLO well below the ceiling." ;;
        "COLLAPSED"*)      echo "  Mean throughput halved against the best level: the server is saturated and shedding work (do NOT quote rps above the peak as capacity)." ;;
        "NO-BREAKDOWN"*)   echo "  The ceiling was reached with no stop condition firing: raise -C or tighten -L/-e for the real breakdown point." ;;
    esac
else
    echo ""
    echo "VERDICT: NO-BREAKDOWN ceiling reached"
    echo "  ${MAX_LEVEL} concurrent connections completed without triggering the error," >&2
    echo "  latency, or throughput-collapse conditions. Raise -C (or tighten -L/-e)"
    echo "  if you need the true breaking point within this run."
fi

echo ""
peak_level=$(awk -F'\t' 'NR > 1 && $7+0 > peak { peak = $7+0; peak_lvl = $1 } END { printf "%s", peak_lvl+0 }' "$LEVELS_TSV")
peak_rps=$(awk -F'\t' 'NR > 1 && $7+0 > peak { peak = $7+0 } END { printf "%.1f", peak }' "$LEVELS_TSV")
if [[ -n "$peak_level" && "$peak_level" -gt 0 ]]; then
    echo "Peak steady throughput ~ ${peak_rps} rps at level ${peak_level}."
    echo "Do NOT quote levels above the peak as capacity: see the verdict above for the breakdown behavior."
fi
echo ""
echo "Raw per-run results written to: ${OUTPUT_CSV}"
echo "Per-level rollup retained in:   ${LEVELS_TSV} (kept in the script workdir)"
exit 0
