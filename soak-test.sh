#!/usr/bin/env bash
#
# soak-test.sh
#
# Author: M.Noermoehammad
#
# SOAK (endurance) test wrapper around Apache Bench (ab).
#
# Companion to load-test.sh / stress-test.sh / spike-test.sh (repo root):
#   load-test.sh  - steady mixed traffic, bounded rounds
#   stress-test.sh- capacity ramp to the breaking point
#   spike-test.sh - short surges + recovery
#   soak-test.sh (this script) - "does performance DEGRADE OVER TIME under
#   sustained normal traffic?" Long-horizon drift, not averages.
#
# METHODOLOGY
#   Load model      : one target endpoint hit with constant -c concurrency,
#                     in request-count checkpoints while the wall clock is
#                     under -d DURATION_SECONDS. ab has no duration control,
#                     so the wall time is bounded by the checkpoint loop: an
#                     ab run that finishes before the checkpoint boundary
#                     removes the leftover (or briefly overshoots by one
#                     lapping run) at the boundary. The -d budget therefore
#                     holds in wall-clock terms up to the granularity of one
#                     checkpoint run.
#   Checkpoints     : quantize the run into -I CHECKPOINT_SECONDS segments
#                     (default 120s, floor 30). Checkpoint rows carry the
#                     ACTUAL elapsed wall-clock seconds so the CSV is always
#                     the truth, not the nominal interval.
#   Drift analysis  : soak's headline statistic. Rows are grouped into
#                     first/middle/last thirds (after the -W warm-up
#                     checkpoint is excluded) and the LAST-third vs
#                     FIRST-third windows are compared:
#                       rps ratio  < 0.85  -> THROUGHPUT-DRIFT down
#                       p95 ratio  > 1.3   -> LATENCY-DRIFT up
#                     Flags are printed, never fatal: they are leads to
#                     investigate with server-side tooling, not verdicts.
#   Error gate      : healthy soak must be clean, not just rare. The pooled
#                     combined error rate passes under the (tighter)
#                     default gate of 1.0% (-e overrides).
#   Black-box limits: SOAK testing exists mostly to catch server-side leaks
#                     (memory, connections, file descriptors) that a
#                     black-box HTTP harness cannot SEE. You relay on
#                     external correlation. Run, on the target host, in
#                     parallel with this script (printed at startup and by
#                     --dry-run):
#                       pidstat -urd -h -p <fpm_pid> <interval> > fpm.log
#                       journalctl -f -u php*-fpm   > fpm-errors.log
#                       mysqladmin extended-status, FPM status page, or the
#                       browsing pool of your choice
#                     This script supplies HTTP truth; your logs supply cause.
#   Coordinated omission : ab is closed-loop; tail latencies are lower bounds.
#
# Requirements: ab (apache2-utils), curl, awk, bc.
#
# Usage:
#   ./soak-test.sh -u https://scriptlog.ddev.site \
#       -C "/?p=1" -d 1800 -r ./soak_scriptlog.csv

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
TARGET_ENDPOINT="dynamic"

DURATION_SECONDS=1800        # -d: total soak budget in wall-clock seconds
CHECKPOINT_SECONDS=120       # -I: nominal checkpoint cadence (floor 30)
CONCURRENCY=10               # -c: sustained concurrency (constant over time)
REQUESTS_PER_CHECKPOINT=""   # -b: ab -n per checkpoint run (default c*12)
MAX_ERROR_PCT="1.0"          # -e: pooled combined error gate
WARMUP_REQUESTS=50           # -W: throwaway requests before measurement
KEEP_ALIVE=0                 # -K: ab -k (hold constant across comparisons)
STRICT_PREFLIGHT=0           # -S: abort before measuring on pre-flight failure
CONNECT_TIMEOUT=5
PREFLIGHT_MAX_TIME=10

OUTPUT_CSV=""
FORCE_OVERWRITE=0            # -f
DRY_RUN=0                    # --dry-run

usage() {
    cat <<EOF
Usage: $0 -u BASE_URL [options]

Required:
  -u  Base URL, e.g. https://scriptlog.ddev.site

Target selection:
  -q  Endpoint to soak: dynamic | static | login (default: dynamic)
  -s  Static asset path (REQUIRED only when -q static)
  -d  NOTE: -d is DURATION_SECONDS for this script (default: $DURATION_SECONDS);
      the dynamic content path is -C (default: "/?p=1")
  -g  Login controller path (REQUIRED only when -q login)

Soak shape:
  -d  Total soak duration in wall-clock seconds (default: $DURATION_SECONDS; min 60)
  -I  Checkpoint cadence in seconds (default: $CHECKPOINT_SECONDS; min 30)
  -c  Sustained concurrency (default: $CONCURRENCY)
  -b  ab requests per checkpoint run
      (default: concurrency x 12, floored at 30 for per-checkpoint percentiles)
  -C  Content path to soak when -q dynamic (default: "/?p=1")

Gates & general:
  -e  CI gate on the pooled COMBINED error rate (default: $MAX_ERROR_PCT;
      soak's default is tighter than load-test.sh's because soak must be clean)
  -l  Label for output (default: "site")
  -W  Warm-up requests (default: $WARMUP_REQUESTS)
  -K  Use HTTP keep-alive (ab -k); hold constant across comparisons
  -S  Strict mode: abort before measuring if the target fails pre-flight
  -r  CSV output path (default: ./soak_<label>_<timestamp>.csv)
  -f  Overwrite the CSV output file if it already exists
  --dry-run  Print the resolved soak plan and exit without network I/O
  -h  Show this help

Operator reminders (NOT automated - run these on the target host in parallel):
  pidstat -urd -h -p <php-fpm_pid> 60 > fpm-soak.log
  journalctl -f -u php*-fpm > fpm-errors.log
  (FPM status page / mysqladmin extended-status as applicable)
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

while getopts ":u:l:s:g:C:q:d:I:c:b:e:W:Sr:fKh" opt; do
    case "$opt" in
        u) BASE_URL="$OPTARG" ;;
        l) SITE_LABEL="$OPTARG" ;;
        s) STATIC_PATH="$OPTARG" ;;
        g) LOGIN_PATH="$OPTARG" ;;
        C) DYNAMIC_PATH="$OPTARG" ;;
        q) TARGET_ENDPOINT="$OPTARG" ;;
        d) DURATION_SECONDS="$OPTARG" ;;
        I) CHECKPOINT_SECONDS="$OPTARG" ;;
        c) CONCURRENCY="$OPTARG" ;;
        b) REQUESTS_PER_CHECKPOINT="$OPTARG" ;;
        e) MAX_ERROR_PCT="$OPTARG" ;;
        W) WARMUP_REQUESTS="$OPTARG" ;;
        S) STRICT_PREFLIGHT=1 ;;
        K) KEEP_ALIVE=1 ;;
        r) OUTPUT_CSV="$OPTARG" ;;
        f) FORCE_OVERWRITE=1 ;;
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
require_positive_int d "$DURATION_SECONDS" "soak duration seconds"
require_positive_int I "$CHECKPOINT_SECONDS" "checkpoint cadence seconds"
require_positive_int c "$CONCURRENCY" "sustained concurrency"
require_positive_int W "$WARMUP_REQUESTS" "warm-up requests per endpoint"

if [[ "$DURATION_SECONDS" -lt 60 ]]; then
    echo "ERROR: -d (soak duration) must be >= 60 seconds (a shorter horizon is a load test, not a soak test; use load-test.sh for those)." >&2
    exit 1
fi
if [[ "$CHECKPOINT_SECONDS" -lt 30 ]]; then
    echo "ERROR: -I (checkpoint cadence) must be >= 30 seconds." >&2
    exit 1
fi
if [[ "$CHECKPOINT_SECONDS" -gt "$DURATION_SECONDS" ]]; then
    echo "ERROR: -I checkpoint cadence ($CHECKPOINT_SECONDS) exceeds -d duration ($DURATION_SECONDS)." >&2
    exit 1
fi
if [[ -n "$REQUESTS_PER_CHECKPOINT" ]]; then
    require_positive_int b "$REQUESTS_PER_CHECKPOINT" "requests per checkpoint"
fi
if [[ -n "$MAX_ERROR_PCT" ]]; then
    if ! [[ "$MAX_ERROR_PCT" =~ ^[0-9]+([.][0-9]+)?$ ]]; then
        echo "ERROR: -e (max error rate) must be a number between 0 and 100, got '$MAX_ERROR_PCT'." >&2
        exit 1
    fi
    if ! awk -v v="$MAX_ERROR_PCT" 'BEGIN { exit !(v >= 0 && v <= 100) }'; then
        echo "ERROR: -e (max error rate) must be between 0 and 100, got '$MAX_ERROR_PCT'." >&2
        exit 1
    fi
fi

# Base URL hygiene (same rules as load-test.sh).
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
[[ -n "$STATIC_PATH" ]] && STATIC_PATH=$(normalize_path "$STATIC_PATH")
DYNAMIC_PATH=$(normalize_path "$DYNAMIC_PATH")
[[ -n "$LOGIN_PATH" ]]  && LOGIN_PATH=$(normalize_path "$LOGIN_PATH")

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

AB_FLAGS=()
if [[ "$KEEP_ALIVE" -eq 1 ]]; then
    AB_FLAGS+=(-k)
fi

# Effective per-checkpoint request count: needs to be big enough for stable
# per-checkpoint percentiles (>= 30 samples) and to keep the cadence feeling
# like 'concurrency x seconds of typical response time'. Defaults c*12, floored
# at 30, never below the concurrency (ab -n must be >= -c).
if [[ -z "$REQUESTS_PER_CHECKPOINT" ]]; then
    REQUESTS_PER_CHECKPOINT=$(( CONCURRENCY * 12 ))
fi
if [[ "$REQUESTS_PER_CHECKPOINT" -lt 30 ]]; then
    echo "NOTE: -b ${REQUESTS_PER_CHECKPOINT} raises to the 30-request floor for valid per-checkpoint percentiles." >&2
    REQUESTS_PER_CHECKPOINT=30
fi
if [[ "$REQUESTS_PER_CHECKPOINT" -lt "$CONCURRENCY" ]]; then
    REQUESTS_PER_CHECKPOINT=$CONCURRENCY
fi

EXPECTED_CHECKPOINTS=$(( (DURATION_SECONDS + CHECKPOINT_SECONDS - 1) / CHECKPOINT_SECONDS ))

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
if [[ -z "$OUTPUT_CSV" ]]; then
    OUTPUT_CSV="./soak_${SITE_LABEL// /_}_$(printf '%s' "$TARGET_ENDPOINT")_${TIMESTAMP}.csv"
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

# --dry-run: resolved soak plan, no network I/O.
if [[ "$DRY_RUN" -eq 1 ]]; then
    cat <<EOF
DRY RUN - planned soak for '${SITE_LABEL}' (nothing was executed)

  target            : ${TARGET_URL}
  duration          : ${DURATION_SECONDS}s
  checkpoint cadence: ${CHECKPOINT_SECONDS}s (expected ~${EXPECTED_CHECKPOINTS} checkpoints)
  concurrency       : ${CONCURRENCY} (constant)
  per-checkpoint run: ${REQUESTS_PER_CHECKPOINT} requests (ab -n)
  drift analysis    : last-third vs first-third of measured checkpoints
                        rps ratio < 0.85 -> THROUGHPUT-DRIFT down
                        p95 ratio > 1.30 -> LATENCY-DRIFT up
  CI error gate     : pooled combined rate must stay <= ${MAX_ERROR_PCT}%
  warm-up           : ${WARMUP_REQUESTS} requests (unmeasured)
  keep-alive        : $([[ "$KEEP_ALIVE" -eq 1 ]] && echo on || echo off)
  strict preflight  : $([[ "$STRICT_PREFLIGHT" -eq 1 ]] && echo on || echo off)
  output csv        : ${OUTPUT_CSV}$([[ -e "$OUTPUT_CSV" && "$FORCE_OVERWRITE" -ne 1 ]] && echo '  [WOULD REFUSE: exists, no -f]')

Operator reminders (run on the TARGET host in parallel with the soak):
  pidstat -urd -h -p <php-fpm_pid> 60 > fpm-soak.log
  journalctl -f -u php*-fpm > fpm-errors.log

NOTE: target reachability is NOT validated in dry-run mode.
      Run without --dry-run to execute the pre-flight check.
EOF
    exit 0
fi

WORKDIR=$(mktemp -d) || { echo "ERROR: could not create temporary working directory." >&2; exit 1; }
cleanup_workdir() {
    if [[ "${SOAK_KEEP_RAW:-0}" != "1" ]]; then
        rm -rf "$WORKDIR"
    else
        echo "NOTE: SOAK_KEEP_RAW=1 -> raw data retained in ${WORKDIR}" >&2
    fi
}
trap cleanup_workdir EXIT
trap 'echo ""; echo "Soak interrupted by the operator: the CSV is append-only and every checkpoint recorded so far is durable." >&2; exit 130' INT
trap 'echo ""; echo "Soak terminated: the CSV is append-only and every checkpoint recorded so far is durable." >&2; exit 143' TERM

# Run-metadata sidecar (provenance, written BEFORE any measurement).
ab_version_line=$(ab -V 2>&1 | head -1 | tr -d '"')
kernel_line=$(uname -sr 2>/dev/null | tr -d '"')
host_name=$(hostname 2>/dev/null | tr -d '"' || echo unknown)
META_JSON="${OUTPUT_CSV}.meta.json"
{
    printf '{\n'
    printf '  "generated_at_utc": "%s",\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    printf '  "host": "%s",\n' "$host_name"
    printf '  "script": "soak-test.sh",\n'
    printf '  "target_base_url": "%s",\n' "${BASE_URL//\"/}"
    printf '  "site_label": "%s",\n' "$SITE_LABEL"
    printf '  "target_endpoint": "%s",\n' "$TARGET_ENDPOINT"
    printf '  "target_url": "%s",\n' "${TARGET_URL//\"/}"
    printf '  "duration_seconds": %d,\n' "$DURATION_SECONDS"
    printf '  "checkpoint_seconds": %d,\n' "$CHECKPOINT_SECONDS"
    printf '  "requests_per_checkpoint": %d,\n' "$REQUESTS_PER_CHECKPOINT"
    printf '  "concurrency": %d,\n' "$CONCURRENCY"
    printf '  "max_error_pct": %s,\n' "$MAX_ERROR_PCT"
    printf '  "warmup_requests": %d,\n' "$WARMUP_REQUESTS"
    printf '  "keep_alive": %s,\n' "$([[ "$KEEP_ALIVE" -eq 1 ]] && echo true || echo false)"
    printf '  "strict_preflight": %s,\n' "$([[ "$STRICT_PREFLIGHT" -eq 1 ]] && echo true || echo false)"
    printf '  "ab_version": "%s",\n' "$ab_version_line"
    printf '  "kernel": "%s"\n' "$kernel_line"
    printf '}\n'
} > "$META_JSON"

# CSV header (same metric schema as the other suite scripts; 'checkpoint'
# replaces 'level' and 'cycle', plus the true elapsed wall-clock seconds).
echo "timestamp,label,checkpoint,elapsed_seconds,endpoint,url,concurrency,requests_sent,complete_requests,failed_requests,rps,mean_latency_ms,p50_ms,p95_ms,p99_ms,non2xx_requests,connect_errors,receive_errors,length_errors,exceptions,ab_exit" > "$OUTPUT_CSV"

# ---------------------------------------------------------------------------
# Pre-flight (same discipline as load-test.sh / stress-test.sh / spike-test.sh).
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

echo "=== Warm-up: ${WARMUP_REQUESTS} requests ==="
if ab ${AB_FLAGS[@]+"${AB_FLAGS[@]}"} -n "$WARMUP_REQUESTS" -c 5 "$TARGET_URL" >/dev/null 2>&1; then
    echo "Warm-up complete (opcode/query caches primed)."
else
    echo "Warm-up finished with warnings (endpoint may be unreachable or rejecting load)." >&2
fi
echo ""

# ---------------------------------------------------------------------------
# ab_run_and_parse CHECKPOINT ELAPSED CONCURRENCY REQUESTS
#
# One ab invocation (load-test.sh parsing idioms), one CSV row appended.
# Returns 1 iff ab exited nonzero. Fills the caller's latest_* globals with
# the run's headline figures for console progress reporting.
# ---------------------------------------------------------------------------
latest_rps=0
latest_p50=0
latest_p95=0
latest_p99=0
latest_msg=""

ab_run_and_parse() {
    local ckpt=$1 elapsed=$2 conc=$3 requests=$4
    local out raw_file
    out=$(mktemp -p "$WORKDIR") || return 1
    raw_file="${WORKDIR}/raw_${ckpt}.tsv"

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
        # No report block: server down / DNS / timeout. Count every requested
        # request as failed so nothing is silently dropped.
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
        latest_msg="ab exited ${ab_exit} with no report (server down or refusing connections?)"
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
        latest_msg=""
        if [[ "$ab_exit" -ne 0 ]]; then
            latest_msg="ab exited ${ab_exit} with a PARTIAL report (complete=${complete}, failed=${failed})"
        elif [[ "$non2xx" -gt 0 ]]; then
            latest_msg="clean ab exit but ${non2xx} non-2xx response(s)"
        elif [[ "$conn_err" -gt 0 ]]; then
            latest_msg="${conn_err} connect error(s) (check the client's ulimit -n before blaming the server)"
        fi
    fi

    printf "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n" \
        "$(date -Iseconds)" "$SITE_LABEL" "$ckpt" "$elapsed" "$TARGET_ENDPOINT" "$(csv_field "$TARGET_URL")" "$conc" "$requests" \
        "$complete" "$failed" "$rps" "$mean" "$p50" "$p95" "$p99" \
        "$non2xx" "$conn_err" "$recv_err" "$len_err" "$exc_err" "$ab_exit" >> "$OUTPUT_CSV"

    latest_rps=$rps
    latest_p50=$p50
    latest_p95=$p95
    latest_p99=$p99

    rm -f "$out"
    if [[ -n "$latest_msg" ]]; then
        echo "  WARN  checkpoint ${ckpt}: ${latest_msg}" >&2
    fi
    if [[ "$ab_exit" -ne 0 ]]; then
        return 1
    fi
    return 0
}

# ---------------------------------------------------------------------------
# Main soak loop: wall-clock bounded, checkpoint-quantized. A checkpoint is
# one ab run; when runs complete faster than the cadence, a small sleep keeps
# close to the -I rhythm without ever exceeding one run's worth of error.
# ---------------------------------------------------------------------------
echo "=== Soak: ${SITE_LABEL} -> ${TARGET_URL} ==="
echo "Duration budget: ${DURATION_SECONDS}s | cadence: ${CHECKPOINT_SECONDS}s | concurrency: ${CONCURRENCY} | ${REQUESTS_PER_CHECKPOINT} req per checkpoint"
echo ""
echo "BLACK-BOX LIMITS (soak is about server-side leaks you cannot see from HTTP):"
echo "  Run on the TARGET host in parallel with this soak, then correlate:"
echo "    pidstat -urd -h -p <php-fpm_pid> 60 > fpm-soak.log"
echo "    journalctl -f -u php*-fpm > fpm-errors.log"
echo ""
echo "This soak holds the pooled combined error rate under the -e=${MAX_ERROR_PCT}% gate;"
echo "every warning above is also persisted in the CSV (append-only, interrupt-safe)."
echo ""

start_epoch=$(date +%s)
checkpoint_idx=0
soak_rc=0

while :; do
    now=$(date +%s)
    elapsed=$(( now - start_epoch ))
    if [[ "$elapsed" -ge "$DURATION_SECONDS" ]]; then
        break
    fi
    checkpoint_idx=$(( checkpoint_idx + 1 ))

    # Last partial checkpoint wraps up whatever wall time is left so the
    # pressure pattern stays uniform; all checkpoints are the same size, which
    # makes the drift windows comparable.
    ckpt_requests=$REQUESTS_PER_CHECKPOINT

    if ! ab_run_and_parse "$checkpoint_idx" "$elapsed" "$CONCURRENCY" "$ckpt_requests"; then
        soak_rc=1
        echo "  ERROR checkpoint ${checkpoint_idx}: ab run failed; aborting the soak run (CSV has ${checkpoint_idx} checkpoints)." >&2
        break
    fi
    printf "    checkpoint %-4s elapsed=%-6ss rps=%-8s p50=%-8s p95=%-8s p99=%s\n" \
        "$checkpoint_idx" "$elapsed" "$latest_rps" "$latest_p50" "$latest_p95" "$latest_p99"

    # Keep the cadence: pad only when this checkpoint finished early, small
    # sleeps so the soak rhythm (and CONSUMED wall clock) tracks -I.
    now2=$(date +%s)
    ckpt_elapsed=$(( now2 - start_epoch ))
    target_end=$(( checkpoint_idx * CHECKPOINT_SECONDS ))
    if [[ "$ckpt_elapsed" -lt "$target_end" ]]; then
        pause=$(( target_end - ckpt_elapsed ))
        [[ "$pause" -gt "$CHECKPOINT_SECONDS" ]] && pause=$CHECKPOINT_SECONDS
        [[ "$pause" -gt 0 ]] && sleep "$pause"
    fi
done

total_elapsed=$(( $(date +%s) - start_epoch ))
echo ""
echo "Soak measurement complete: ${checkpoint_idx} checkpoint(s) over ${total_elapsed}s of wall time (budget ${DURATION_SECONDS}s)."

# ---------------------------------------------------------------------------
# Drift analysis + summary
# ---------------------------------------------------------------------------
if [[ "$checkpoint_idx" -lt 2 ]]; then
    echo ""
    echo "======================================================================"
    echo "Soak Summary - ${SITE_LABEL}"
    echo "======================================================================"
    echo "Fewer than 2 checkpoints were recorded; no drift analysis is possible."
    echo "Raise -d, lower -I, or shrink -b so that at least 2 checkpoints exist."
else
    echo ""
    echo "======================================================================"
    echo "Soak Summary - ${SITE_LABEL} (${TARGET_ENDPOINT} endpoint)"
    echo "======================================================================"
    echo "NOTE: tail latencies (p95/p99) are LOWER BOUNDS - ab's closed-loop model"
    echo "      hides server stalls behind its in-flight window (coordinated omission)."
    echo ""
    echo "Checkpoint table (timestamp order):"
    awk -F',' '
        NR == 1 { next }
        {
            printf "  ckpt %-4s elapsed=%-7s rps=%-9.1f p50=%-8.0f p95=%-8.0f p99=%-9.0f combined-err=%s%%\n", \
                $3, $4, $11 + 0, $13 + 0, $14 + 0, $15 + 0, \
                sprintf("%.2f", ($10 + $16) / ((($8 + 0) > 0) ? $8 : 1) * 100)
        }
    ' "$OUTPUT_CSV"

    # ---------------------------------------------------------------
    # Drift analysis: LAST THIRD vs FIRST THIRD of measured checkpoints.
    # Windows are by CHECKPOINT INDEX (rows are ordered), not elapsed
    # time, so uneven run durations cannot skew window boundaries.
    # ---------------------------------------------------------------
    drift_stats=$(awk -F',' '
        NR == 1 { next }
        {
            n++
            ck[n] = $3
            sent[n] = $8 + 0
            failed[n] = $10 + 0
            non2xx[n] = $16 + 0
            rps[n] = $11 + 0
            p95[n] = $14 + 0
        }
        END {
            if (n < 2) { printf "0 0 0 0 0 0 0 0\n"; exit }
            w = int(n / 3)
            if (w < 1) w = 1
            first_end = w
            last_start = n - w + 1
            f_sent = 0; f_fail = 0; f_n2 = 0; f_rps = 0; f_p95 = 0
            l_sent = 0; l_fail = 0; l_n2 = 0; l_rps = 0; l_p95 = 0
            for (i = 1; i <= first_end; i++) {
                f_sent += sent[i]; f_fail += failed[i]; f_n2 += non2xx[i]
                f_rps += rps[i]; f_p95 += p95[i]
            }
            for (i = last_start; i <= n; i++) {
                l_sent += sent[i]; l_fail += failed[i]; l_n2 += non2xx[i]
                l_rps += rps[i]; l_p95 += p95[i]
            }
            s_f = (f_sent > 0) ? f_sent : 1
            s_l = (l_sent > 0) ? l_sent : 1
            f_rps_m = f_rps / w
            l_rps_m = l_rps / w
            f_p95_m = f_p95 / w
            l_p95_m = l_p95 / w
            f_err = (f_fail + f_n2) / s_f * 100
            l_err = (l_fail + l_n2) / s_l * 100
            rps_ratio = (f_rps_m > 0) ? l_rps_m / f_rps_m : 0
            p95_ratio = (f_p95_m > 0) ? l_p95_m / f_p95_m : 0
            printf "%d %.3f %.3f %.3f %.3f %.3f %.3f\n", w, f_rps_m, l_rps_m, f_p95_m, l_p95_m, rps_ratio, p95_ratio
        }
    ' "$OUTPUT_CSV")
    read -r drift_w first_rps last_rps first_p95 last_p95 rps_ratio p95_ratio <<< "$drift_stats"

    echo ""
    echo "Drift analysis (windows of ${drift_w:-0} checkpoint(s): first-third vs last-third):"
    printf "  rps  first=%.1f last=%.1f  ratio=%.2f\n" "$first_rps" "$last_rps" "$rps_ratio"
    printf "  p95  first=%.1fms last=%.1fms  ratio=%.2f\n" "$first_p95" "$last_p95" "$p95_ratio"
    drift_flags=""
    if awk -v r="$rps_ratio" 'BEGIN { exit !(r < 0.85) }'; then
        drift_flags="THROUGHPUT-DRIFT down (rps ratio < 0.85)"
        echo "  FLAG: $drift_flags - throughput decaying over time (memory leak, connection leak, or accumulating state are classic causes)"
    fi
    if awk -v r="$p95_ratio" 'BEGIN { exit !(r > 1.30) }'; then
        drift_flags="${drift_flags:+$drift_flags; }LATENCY-DRIFT up (p95 ratio > 1.30)"
        echo "  FLAG: LATENCY-DRIFT up (p95 ratio > 1.30) - tail latency growing over time; correlates with the throughput flag above"
    fi
    if [[ -z "$drift_flags" ]]; then
        echo "  no drift flags: rps ratio >= 0.85 and p95 ratio <= 1.30 within this run's horizon"
    fi
    echo ""
    echo "Soak conclusions are VIEWS ON TRENDS, not proof: correlate with"
    echo "external server-side evidence (pidstat / FPM status / slow query log)"
    echo "captured during this run before opening a remediation item."

    # ---- Pooled CI error gate (-e) ----
    overall_gate=$(awk -F',' -v max="$MAX_ERROR_PCT" '
        NR > 1 { rows++; sent += $8; failed += $10; non2xx += $16 }
        END {
            if (rows == 0 || sent == 0) { printf "0.00 VIOLATION"; exit }
            rate = (failed + non2xx) / sent * 100
            printf "%.2f %s", rate, (rate > max) ? "VIOLATION" : "ok"
        }' "$OUTPUT_CSV")
    gate_rate="${overall_gate%% *}"
    gate_status="${overall_gate##* }"
    echo ""
    if [[ "$soak_rc" -ne 0 ]]; then
        echo "Soak run was ABORTED by an ab failure; the error gate is not evaluated on a partial run." >&2
    elif [[ "$gate_status" == "VIOLATION" ]]; then
        echo "ERROR-RATE GATE FAILED: pooled combined error rate ${gate_rate}% exceeds -e ceiling of ${MAX_ERROR_PCT}%." >&2
        soak_rc=1
    elif [[ "$gate_status" != "ok" ]]; then
        echo "ERROR-RATE GATE FAILED: could not evaluate error rate from ${OUTPUT_CSV}." >&2
        soak_rc=1
    else
        echo "Error-rate gate passed: pooled combined ${gate_rate}% <= ${MAX_ERROR_PCT}% (-e)."
    fi
fi

echo ""
echo "Raw per-checkpoint results written to: ${OUTPUT_CSV}"
exit "$soak_rc"
