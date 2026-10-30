#!/usr/bin/env bash
#
# spike-test.sh
#
# Author: M.Noermoehammad
#
# SPIKE test wrapper around Apache Bench (ab).
#
# Companion to load-test.sh / stress-test.sh (repo root):
#   load-test.sh  - steady mixed traffic, "can it serve expected load?"
#   stress-test.sh- capacity ramp, "where is the breaking point?"
#   spike-test.sh (this script) - "does a sudden short surge above baseline
#   break the system, and does performance RECOVER once the surge ends?"
#
# One cycle = three ApacheBench runs against the SAME target URL:
#
#   1. baseline : -B/-R  (concurrency/requests) - the healthy reference
#   2. spike    : -S/-G  - a short burst far above baseline
#   3. recovery : baseline parameters again; strictly after the spike
#                 run finishes (no parallelism in this design, every phase
#                 sequentially) so recovery measurements measure what it
#                 feels like to come DOWN from the burst.
#   Optionally pause -P seconds between cycles so OS caches age.
#
# Deterministic derived verdicts (per cycle, from ab reports only):
#
#   spike absorbability:
#     - spike combined error rate must stay under the -e gate
#     - spike must not collapse throughput: spike mean rps must stay above
#       25% of that cycle's baseline mean rps
#   recovery quality (vs that cycle's baseline):
#     - rps recovery ratio = recovery.rps / baseline.rps
#         >= 90%  -> RECOVERED
#         <  90%  -> DEGRADED
#     - latency recovery ratio = recovery.p95 / baseline.p95
#         <= 1.2x -> ok, otherwise p95 inflate flagged
#   The final summary prints the WORST cycle verdict.
#
# METHODOLOGY (same discipline as load-test.sh)
#   Error rates          : combined = (failed + non2xx) / requests SENT.
#                          Same failure taxonomy (Connect/Receive/Length/
#                          Exceptions) and parsing idioms.
#   Coordinated omission : tail latencies are lower bounds (closed-loop
#                          client).
#   Comparable runs      : like load-test.sh, hold the keep-alive flag (-K)
#                          constant across compared runs, and re-run the
#                          same command with matching phase counts when you
#                          need a stable baseline reference between hosts.
#
# Requirements: ab (apache2-utils), curl, awk, bc.
#
# Usage:
#   ./spike-test.sh -u https://scriptlog.ddev.site \
#       -d "/?p=1" -r ./spike_scriptlog.csv
#
#   ./spike-test.sh --dry-run -u https://x.test -d "/?p=1"

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

CYCLES=3                     # -i: baseline/spike/recovery cycles
BASE_CONCURRENCY=5           # -B
BASE_REQUESTS=60             # -R
SPIKE_CONCURRENCY=50         # -S
SPIKE_REQUESTS=200           # -G
PAUSE_BETWEEN_CYCLES=2       # -P: seconds between cycles (0 allowed)
MAX_ERROR_PCT="5.0"          # -e: CI gate on the run's pooled combined rate

WARMUP_REQUESTS=50           # -W
KEEP_ALIVE=0                 # -K
STRICT_PREFLIGHT=0           # -S? conflicts with spike S below; use -X instead
CONNECT_TIMEOUT=5
PREFLIGHT_MAX_TIME=10

OUTPUT_CSV=""
FORCE_OVERWRITE=0
DRY_RUN=0

usage() {
    cat <<EOF
Usage: $0 -u BASE_URL [options]

Required:
  -u  Base URL, e.g. https://scriptlog.ddev.site

Target selection:
  -q  Endpoint to spike: dynamic | static | login (default: dynamic)
  -s  Static asset path (REQUIRED only when -q static)
  -d  Dynamic content path (default: "/?p=1")
  -g  Login controller path (REQUIRED only when -q login)

Cycle shape:
  -i  Cycles (default: $CYCLES; minimum 1)
  -B  Baseline concurrency (default: $BASE_CONCURRENCY)
  -R  Baseline requests per cycle (default: $BASE_REQUESTS)
  -S  Spike concurrency (default: $SPIKE_CONCURRENCY)
  -G  Spike requests per cycle (default: $SPIKE_REQUESTS)
  -P  Pause between cycles in seconds (default: $PAUSE_BETWEEN_CYCLES; 0 allowed)

Gates:
  -e  CI gate: exit nonzero when the run's pooled COMBINED error rate
      ((failed+non2xx)/sent) exceeds this percentage (default: $MAX_ERROR_PCT)

General:
  -l  Label for output (default: "site")
  -X  Strict mode: abort before measuring if the target fails pre-flight
  -W  Warm-up requests before measurement (default: $WARMUP_REQUESTS)
  -K  Use HTTP keep-alive (ab -k); hold constant across comparisons
  -r  CSV output path (default: ./spike_<label>_<timestamp>.csv)
  -f  Overwrite the CSV output file if it already exists
  --dry-run  Print the resolved cycle plan and exit without network I/O
  -h  Show this help
EOF
    exit 1
}

# Long-option shim (--dry-run).
script_args=()
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
        *) script_args+=("$arg") ;;
    esac
done
set -- "${script_args[@]}"

while getopts ":u:l:s:d:g:q:i:B:R:S:G:P:e:W:XKr:fSh" opt; do
    case "$opt" in
        u) BASE_URL="$OPTARG" ;;
        l) SITE_LABEL="$OPTARG" ;;
        s) STATIC_PATH="$OPTARG" ;;
        d) DYNAMIC_PATH="$OPTARG" ;;
        g) LOGIN_PATH="$OPTARG" ;;
        q) TARGET_ENDPOINT="$OPTARG" ;;
        i) CYCLES="$OPTARG" ;;
        B) BASE_CONCURRENCY="$OPTARG" ;;
        R) BASE_REQUESTS="$OPTARG" ;;
        S) SPIKE_CONCURRENCY="$OPTARG" ;;
        G) SPIKE_REQUESTS="$OPTARG" ;;
        P) PAUSE_BETWEEN_CYCLES="$OPTARG" ;;
        e) MAX_ERROR_PCT="$OPTARG" ;;
        W) WARMUP_REQUESTS="$OPTARG" ;;
        X) STRICT_PREFLIGHT=1 ;;
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
require_positive_int i "$CYCLES" "cycles"
require_positive_int B "$BASE_CONCURRENCY" "baseline concurrency"
require_positive_int R "$BASE_REQUESTS" "baseline requests"
require_positive_int S "$SPIKE_CONCURRENCY" "spike concurrency"
require_positive_int G "$SPIKE_REQUESTS" "spike requests"
require_positive_int W "$WARMUP_REQUESTS" "warm-up requests per endpoint"
if ! [[ "$PAUSE_BETWEEN_CYCLES" =~ ^[0-9]+$ ]]; then
    echo "ERROR: -P (pause between cycles) must be a non-negative integer, got '$PAUSE_BETWEEN_CYCLES'." >&2
    exit 1
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

# Sanity ordering: a spike cycle whose burst is SMALLER than the baseline
# is almost certainly a mistake, not a test.
if awk -v b="$SPIKE_CONCURRENCY" -v t="$BASE_CONCURRENCY" 'BEGIN { exit !(b <= t) }'; then
    echo "ERROR: -S spike concurrency ($SPIKE_CONCURRENCY) must exceed -B baseline concurrency ($BASE_CONCURRENCY); a spike cycle whose burst is not higher than its baseline is not a spike test." >&2
    exit 1
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

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
if [[ -z "$OUTPUT_CSV" ]]; then
    OUTPUT_CSV="./spike_${SITE_LABEL// /_}_$(printf '%s' "$TARGET_ENDPOINT")_${TIMESTAMP}.csv"
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

# --dry-run: resolved plan, no network I/O.
if [[ "$DRY_RUN" -eq 1 ]]; then
    cat <<EOF
DRY RUN - planned spike cycles for '${SITE_LABEL}' (nothing was executed)

  target            : ${TARGET_URL}
  cycles            : ${CYCLES}
  per cycle
    baseline        : ${BASE_REQUESTS} requests @ concurrency ${BASE_CONCURRENCY}
    spike           : ${SPIKE_REQUESTS} requests @ concurrency ${SPIKE_CONCURRENCY}
    recovery        : ${BASE_REQUESTS} requests @ concurrency ${BASE_CONCURRENCY}
  pause between     : ${PAUSE_BETWEEN_CYCLES}s
  verdicts per cycle
    spike absorbable : spike combined err <= ${MAX_ERROR_PCT}% AND spike rps >= 25% of baseline rps
    recovery         : rps ratio >= 90% of baseline AND p95 ratio <= 1.2x of baseline
  CI error gate     : pooled combined rate must stay <= ${MAX_ERROR_PCT}%
  warm-up           : ${WARMUP_REQUESTS} requests (unmeasured)
  keep-alive        : $([[ "$KEEP_ALIVE" -eq 1 ]] && echo on || echo off)
  strict preflight  : $([[ "$STRICT_PREFLIGHT" -eq 1 ]] && echo on || echo off)
  output csv        : ${OUTPUT_CSV}$([[ -e "$OUTPUT_CSV" && "$FORCE_OVERWRITE" -ne 1 ]] && echo '  [WOULD REFUSE: exists, no -f]')

NOTE: target reachability is NOT validated in dry-run mode.
      Run without --dry-run to execute the pre-flight check.
EOF
    exit 0
fi

WORKDIR=$(mktemp -d) || { echo "ERROR: could not create temporary working directory." >&2; exit 1; }
cleanup_workdir() {
    if [[ "${SPIKE_KEEP_RAW:-0}" != "1" ]]; then
        rm -rf "$WORKDIR"
    else
        echo "NOTE: SPIKE_KEEP_RAW=1 -> raw data retained in ${WORKDIR}" >&2
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
    printf '  "script": "spike-test.sh",\n'
    printf '  "target_base_url": "%s",\n' "${BASE_URL//\"/}"
    printf '  "site_label": "%s",\n' "$SITE_LABEL"
    printf '  "target_endpoint": "%s",\n' "$TARGET_ENDPOINT"
    printf '  "target_url": "%s",\n' "${TARGET_URL//\"/}"
    printf '  "cycles": %d,\n' "$CYCLES"
    printf '  "baseline": { "concurrency": %d, "requests": %d },\n' "$BASE_CONCURRENCY" "$BASE_REQUESTS"
    printf '  "spike": { "concurrency": %d, "requests": %d },\n' "$SPIKE_CONCURRENCY" "$SPIKE_REQUESTS"
    printf '  "pause_between_cycles": %d,\n' "$PAUSE_BETWEEN_CYCLES"
    printf '  "max_error_pct": %s,\n' "$MAX_ERROR_PCT"
    printf '  "warmup_requests": %d,\n' "$WARMUP_REQUESTS"
    printf '  "keep_alive": %s,\n' "$([[ "$KEEP_ALIVE" -eq 1 ]] && echo true || echo false)"
    printf '  "strict_preflight": %s,\n' "$([[ "$STRICT_PREFLIGHT" -eq 1 ]] && echo true || echo false)"
    printf '  "ab_version": "%s",\n' "$ab_version_line"
    printf '  "kernel": "%s"\n' "$kernel_line"
    printf '}\n'
} > "$META_JSON"

# CSV header (same metric schema as stress-test.sh; 'phase' replaces 'level').
echo "timestamp,label,cycle,phase,endpoint,url,concurrency,requests_sent,complete_requests,failed_requests,rps,mean_latency_ms,p50_ms,p95_ms,p99_ms,non2xx_requests,connect_errors,receive_errors,length_errors,exceptions,ab_exit" > "$OUTPUT_CSV"

# ---------------------------------------------------------------------------
# Pre-flight (same discipline as load-test.sh / stress-test.sh).
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
        echo "STRICT MODE (-X): aborting because the target failed pre-flight. Nothing was measured." >&2
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
# ab_run_and_parse CYCLE PHASE CONCURRENCY REQUESTS
#
# One ab invocation, parsed with load-test.sh's idioms, one CSV row appended.
# Prints "rps failed non2xx" on stdout for the caller's per-cycle verdicts
# (unused lines in other scripts; here the caller of a PHASE consumes the
# numbers by reading the CSV). Returns 1 iff ab exited nonzero.
# ---------------------------------------------------------------------------
ab_run_and_parse() {
    local cyc=$1 phase=$2 conc=$3 requests=$4
    local out raw_file
    out=$(mktemp -p "$WORKDIR") || return 1
    raw_file="${WORKDIR}/raw_${cyc}_${phase}.tsv"

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
        # No report block: server down / DNS / timeout. Every requested
        # request is counted failed so nothing is silently dropped.
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
        echo "  WARN  cycle ${cyc} ${phase}: ab exited ${ab_exit} with no report (server down or refusing connections?)" >&2
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
            echo "  WARN  cycle ${cyc} ${phase}: ab exited ${ab_exit} with a PARTIAL report (complete=${complete}, failed=${failed})" >&2
        fi
        if [[ "$ab_exit" -eq 0 && "$non2xx" -gt 0 ]]; then
            echo "  WARN  cycle ${cyc} ${phase}: clean ab exit but ${non2xx} non-2xx response(s)" >&2
        fi
        if [[ "$conn_err" -gt 0 ]]; then
            echo "  ADVISORY  cycle ${cyc} ${phase}: ${conn_err} connect error(s). If spiking from the CLIENT's host" >&2
            echo "                    with a low ulimit -n, the client's file descriptors can be the bottleneck." >&2
        fi
    fi

    printf "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n" \
        "$(date -Iseconds)" "$SITE_LABEL" "$cyc" "$phase" "$TARGET_ENDPOINT" "$(csv_field "$TARGET_URL")" "$conc" "$requests" \
        "$complete" "$failed" "$rps" "$mean" "$p50" "$p95" "$p99" \
        "$non2xx" "$conn_err" "$recv_err" "$len_err" "$exc_err" "$ab_exit" >> "$OUTPUT_CSV"

    rm -f "$out"
    # Pooled percentiles for THIS run's raw dump go to the summary via the CSV
    # columns already appended; the per-cycle verdict math reads the CSV.
    if [[ "$ab_exit" -ne 0 ]]; then
        return 1
    fi
    return 0
}

# ---------------------------------------------------------------------------
# Main cycle loop: strictly sequential phases so each phase starts only after
# the previous one has fully finished.
# ---------------------------------------------------------------------------
echo "=== Spike cycles: ${SITE_LABEL} -> ${TARGET_URL} ==="
echo "Per cycle: baseline ${BASE_REQUESTS}@${BASE_CONCURRENCY} -> spike ${SPIKE_REQUESTS}@${SPIKE_CONCURRENCY} -> recovery ${BASE_REQUESTS}@${BASE_CONCURRENCY}"

for cyc in $(seq 1 "$CYCLES"); do
    echo "--- cycle ${cyc} ---"
    ab_run_and_parse "$cyc" "baseline" "$BASE_CONCURRENCY" "$BASE_REQUESTS"
    ab_run_and_parse "$cyc" "spike" "$SPIKE_CONCURRENCY" "$SPIKE_REQUESTS"
    ab_run_and_parse "$cyc" "recovery" "$BASE_CONCURRENCY" "$BASE_REQUESTS"

    # Per-cycle verdicts: read the three rows this cycle just appended and
    # derive the ratios with awk (bash cannot compare decimals natively).
    verdict=$(awk -F',' -v cyc="$cyc" -v maxerr="$MAX_ERROR_PCT" '
        NR == 1 { next }
        $3 == cyc {
            if ($4 == "baseline") { b_rps = $11 + 0; b_p95 = $14 + 0; b_err = ($10 + $16) / ((($8 + 0) > 0) ? $8 : 1) * 100 }
            if ($4 == "spike")    { s_rps = $11 + 0; s_p95 = $14 + 0; s_err = ($10 + $16) / ((($8 + 0) > 0) ? $8 : 1) * 100; s_conn = $17 + 0 }
            if ($4 == "recovery") { r_rps = $11 + 0; r_p95 = $14 + 0; r_err = ($10 + $16) / ((($8 + 0) > 0) ? $8 : 1) * 100 }
            seen[$4] = 1
        }
        END {
            printf "    baseline : rps=%.1f p95=%.0fms combined-err=%.2f%%\n", b_rps, b_p95, b_err
            if (!("baseline" in seen) || !("spike" in seen)) { printf "\n"; exit }
            absorb_rate = (s_rps >= 0.25 * b_rps) ? "absorbed" : "COLLAPSED"
            absorb_err  = (s_err <= maxerr) ? "ok" : "VIOLATED"
            printf "    spike    : rps=%.1f p95=%.0fms combined-err=%.2f%% (gate %.2f%%) -> %s/%s\n", \
                s_rps, s_p95, s_err, maxerr, absorb_rate, absorb_err
            printf "               spike/baseline rps ratio=%.2f | spike p95 = %.1fx baseline p95\n", \
                (b_rps > 0) ? s_rps / b_rps : 0, (b_p95 > 0) ? s_p95 / b_p95 : 0
            if (!("recovery" in seen)) { printf "\n"; exit }
            rps_ratio = (b_rps > 0) ? r_rps / b_rps : 0
            p95_ratio = (b_p95 > 0) ? r_p95 / b_p95 : 0
            verdict = (rps_ratio >= 0.9 && p95_ratio <= 1.2) ? "RECOVERED" : "DEGRADED"
            printf "    recovery : rps=%.1f p95=%.0fms combined-err=%.2f%% -> %s\n", r_rps, r_p95, r_err, verdict
            printf "               recovery rps ratio=%.2f (>=0.90 recovered) | p95 ratio=%.2f (<=1.20 ok)\n", \
                rps_ratio, p95_ratio
        }
    ' "$OUTPUT_CSV")
    printf '%s\n' "$verdict"

    if [[ "$PAUSE_BETWEEN_CYCLES" -gt 0 && "$cyc" -lt "$CYCLES" ]]; then
        sleep "$PAUSE_BETWEEN_CYCLES"
    fi
done

# ---------------------------------------------------------------------------
# Summary: worst-case verdict + pooled CI gate
# ---------------------------------------------------------------------------
echo ""
echo "======================================================================"
echo "Spike Summary - ${SITE_LABEL} (${TARGET_ENDPOINT} endpoint, ${CYCLES} cycles)"
echo "======================================================================"
echo "NOTE: tail latencies (p95/p99) are LOWER BOUNDS - ab's closed-loop model"
echo "      hides server stalls behind its in-flight window (coordinated omission)."
echo ""

# Worst per-cycle verdicts over the whole run (the pooled error gate is
# evaluated separately below against -e).
final_verdict=$(awk -F',' '
    NR == 1 { next }
    {
        ph = $4
        if (ph == "spike") {
            s_rps[$3] = $11 + 0
            s_err[$3] = ($10 + $16) / ((($8 + 0) > 0) ? $8 : 1) * 100
        }
        if (ph == "recovery") {
            r_rps[$3] = $11 + 0
            r_p95[$3] = $14 + 0
        }
        if (ph == "baseline") {
            b_rps[$3] = $11 + 0
            b_p95[$3] = $14 + 0
        }
    }
    END {
        collapsed = 0
        degraded = 0
        for (c in s_rps) {
            if ((s_rps[c] + 0) < 0.25 * (b_rps[c] + 0)) collapsed++
        }
        for (c in r_rps) {
            rps_ratio = (b_rps[c] > 0) ? r_rps[c] / b_rps[c] : 0
            p95_ratio = (b_p95[c] > 0) ? r_p95[c] / b_p95[c] : 0
            if (rps_ratio < 0.90 || p95_ratio > 1.20) degraded++
        }
        printf "%d %d\n", collapsed, degraded
    }
' "$OUTPUT_CSV")
read -r collapsed_n degraded_n <<< "$final_verdict"

echo "  spike collapses (throughput under 25% of baseline):      ${collapsed_n:-0} cycle(s)"
echo "  degraded recoveries (rps<90% of baseline or p95>1.2x):   ${degraded_n:-0} cycle(s)"
if [[ "${collapsed_n:-0}" -eq 0 && "${degraded_n:-0}" -eq 0 ]]; then
    echo ""
    echo "  VERDICT: RESILIENT - spikes were absorbed and every cycle recovered to baseline."
else
    echo ""
    echo "  VERDICT: NOT RESILIENT - see the per-cycle verdicts above for the failing phase."
fi

# ---- CI pooled error gate (-e) --------------------------------------------
overall_rc=0
verdict_gate=$(awk -F',' -v max="$MAX_ERROR_PCT" '
    NR > 1 { rows++; sent += $8; failed += $10; non2xx += $16 }
    END {
        if (rows == 0 || sent == 0) { printf "0.00 VIOLATION"; exit }
        rate = (failed + non2xx) / sent * 100
        printf "%.2f %s", rate, (rate > max) ? "VIOLATION" : "ok"
    }' "$OUTPUT_CSV")
gate_rate="${verdict_gate%% *}"
gate_status="${verdict_gate##* }"
echo ""
if [[ "$gate_status" == "VIOLATION" ]]; then
    echo "ERROR-RATE GATE FAILED: pooled combined error rate ${gate_rate}% exceeds -e ceiling of ${MAX_ERROR_PCT}%." >&2
    overall_rc=1
elif [[ "$gate_status" != "ok" ]]; then
    echo "ERROR-RATE GATE FAILED: could not evaluate error rate from ${OUTPUT_CSV}." >&2
    overall_rc=1
else
    echo "Error-rate gate passed: pooled combined ${gate_rate}% <= ${MAX_ERROR_PCT}% (-e)."
fi

echo ""
echo "Raw per-cycle results written to: ${OUTPUT_CSV}"
exit "$overall_rc"
