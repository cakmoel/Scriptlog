#!/usr/bin/env bash
#
# Shared setup for the spike-test BATS suite.
#
# spike-test.sh shells out to real binaries (ab, curl, awk, bc, mktemp,
# hostname, uname, date), so the suite installs deterministic MOCKS for ab and
# curl on a sandboxed PATH (same pattern as stress-test-helper.bash). Real
# awk/bc/mktemp/date stay untouched.
#
# The mock ab:
#   * replies to `ab -V` (metadata sidecar);
#   * logs every invocation to $SPIKE_LOG for exact-flag assertions
#     (warm-up vs measured runs are distinguishable by their -n/-c values
#     and by the presence of -g);
#   * persists an invocation counter in $SPIKE_CALLCOUNTER so per-call
#     behavior survives across process boundaries;
#   * emits a fixed report (deterministic Complete/Failed/percentiles/...)
#     and a deterministic `ab -g` raw dump (ttime = 1..N).
#
# Behavior switches (set per test BEFORE running):
#
#   AB_MODE       clean (default) | total-fail (no report, exit 1) | partial (report, exit 1)
#   AB_WARMUP_FAIL=1 -> the warm-up invocation exits 1 (measured runs unaffected)
#   AB_NON2XX     number placed on the Non-2xx responses line (default 0)
#   AB_CONNERR    failed-request count attributed to the client-side
#                 "(Connect: N, ...)" breakdown; completes N fewer requests
#                 (default 0)
#   AB_RPS        fixed requests-per-second reported by every run (default 800.00)
#   AB_RPS_SEQ    comma list of rps values consumed IN CALL ORDER by the
#                 MEASURED invocations (warm-up is call 1 and is skipped).
#                 Baseline and recovery runs share identical -n/-c, so call
#                 order is the only way to give the three phases different
#                 numbers, e.g. "1000,50,950" for one cycle. When the list is
#                 exhausted the LAST value repeats.

SPIKE_SCRIPT="${BATS_TEST_DIRNAME}/../../spike-test.sh"

sp_setup() {
    export LC_ALL=C
    unset AB_MODE AB_WARMUP_FAIL AB_NON2XX AB_CONNERR AB_RPS AB_RPS_SEQ
    unset CURL_STATIC_STATUS CURL_DYNAMIC_STATUS CURL_LOGIN_STATUS
    unset CURL_TRANSPORT_FAIL
    SPIKE_OUT="${BATS_TEST_TMPDIR}/out.csv"
    rm -f "${SPIKE_OUT}" "${SPIKE_OUT}.meta.json"
    SPIKE_LOG="${BATS_TEST_TMPDIR}/ab_calls.log"
    : > "${SPIKE_LOG}"
    export SPIKE_LOG
    SPIKE_CALLCOUNTER="${BATS_TEST_TMPDIR}/ab_call_counter"
    printf '0\n' > "${SPIKE_CALLCOUNTER}"
    export SPIKE_CALLCOUNTER
    SP_MOCK_BIN="${BATS_TEST_TMPDIR}/bin"
    rm -rf "${SP_MOCK_BIN}"
    mkdir -p "${SP_MOCK_BIN}"
    _sp_install_ab_mock
    _sp_install_curl_mock
    PATH="${SP_MOCK_BIN}:${PATH}"
    export PATH
}

_sp_install_ab_mock() {
    cat > "${SP_MOCK_BIN}/ab" <<'AB_MOCK_EOF'
#!/usr/bin/env bash
# Mock ApacheBench for spike-test.sh BATS tests (see helper header).
set -u

log="${SPIKE_LOG:-/dev/null}"
printf 'ab %s\n' "$*" >> "$log"

if [[ "${1:-}" == "-V" ]]; then
    printf '%s\n' 'ApacheBench/2.4.41 (mock version for spike-test BATS suite)'
    exit 0
fi

args=("$@")
n=0
c=0
raw=""
url=""
i=0
while (( i < ${#args[@]} )); do
    case "${args[$i]}" in
        -n) n="${args[$((i + 1))]}"; i=$((i + 2)) ;;
        -c) c="${args[$((i + 1))]}"; i=$((i + 2)) ;;
        -g) raw="${args[$((i + 1))]}"; i=$((i + 2)) ;;
        *)  url="${args[$i]}"; i=$((i + 1)) ;;
    esac
done

# Invocation numbering: falls back to nanoseconds if no counter file exists,
# but the helper always provides one.
call=$(cat "${SPIKE_CALLCOUNTER:-/dev/null}" 2>/dev/null || echo 0)
call=$((call + 1))
if [[ -n "${SPIKE_CALLCOUNTER:-}" ]]; then
    printf '%s\n' "$call" > "$SPIKE_CALLCOUNTER"
fi

rps="${AB_RPS:-800.00}"
if [[ -n "${AB_RPS_SEQ:-}" ]]; then
    IFS=',' read -r -a seq <<< "$AB_RPS_SEQ"
    idx=$((call - 2))   # call 1 is the warm-up; measured calls follow
    if (( idx >= 0 && idx < ${#seq[@]} )); then
        rps="${seq[$idx]}"
    elif (( ${#seq[@]} > 0 )); then
        rps="${seq[${#seq[@]} - 1]}"
    fi
fi

# Warm-up runs carry no -g flag.
if [[ -z "$raw" && "${AB_WARMUP_FAIL:-0}" == "1" ]]; then
    printf '%s\n' "apachebench: warm-up forced failure against ${url}"
    exit 1
fi

mode="${AB_MODE:-clean}"
if [[ "$mode" == "total-fail" ]]; then
    printf '%s\n' "apachebench: Could not connect to server on ${url}"
    exit 1
fi

non2xx="${AB_NON2XX:-0}"
connerr="${AB_CONNERR:-0}"

# Client-side connect errors: N requests fail at connect time instead of
# completing.
complete="$n"
if (( connerr > 0 )); then
    complete=$((n - connerr))
    if (( complete < 0 )); then complete=0; fi
fi
failed="$connerr"

cat <<REPORT_EOF
This is ApacheBench, Version 2.4.41 <mock>
Server Software:        mock-httpd
Server Hostname:        spikemock
Server Port:            443
Document Path:          /
Document Length:        512 bytes
Concurrency Level:      ${c}
Time taken for tests:   0.024 seconds
Complete requests:      ${complete}
Failed requests:        ${failed}
   (Connect: ${connerr}, Receive: 0, Length: 0, Exceptions: 0)
Non-2xx responses:      ${non2xx}
Total transferred:      1000 bytes
Requests per second:    ${rps} [#/sec] (mean)
Time per request:       12.345 [ms] (mean)
Time per request:       1.372 [ms] (mean, across all concurrent requests)
Transfer rate:          41.67 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.0      0      0
Processing:     8    9   1.0      9     10
Waiting:        7    8   1.0      8      9
Total:          8    9   1.0      9     10

Percentage of the requests served within a certain time (ms)
  50%     12
  66%     13
  75%     15
  80%     16
  90%     18
  95%     28
  98%     29
  99%     29
 100%     30 (longest request)
REPORT_EOF

# Deterministic ab -g dump: ttime = 1..n.
if [[ -n "$raw" ]]; then
    printf 'starttime\tseconds\tctime\tdtime\tttime\twait\n' > "$raw"
    for ((r = 1; r <= n; r++)); do
        printf 'Sun Aug 23 06:16:30 2026\t0\t0\t0\t%s\t0\n' "$r" >> "$raw"
    done
fi

if [[ "$mode" == "partial" ]]; then
    exit 1
fi
exit 0
AB_MOCK_EOF
    chmod +x "${SP_MOCK_BIN}/ab"
}

_sp_install_curl_mock() {
    cat > "${SP_MOCK_BIN}/curl" <<'CURL_MOCK_EOF'
#!/usr/bin/env bash
# Mock curl for spike-test.sh BATS tests: answers pre-flight probes with a
# per-scenario status code derived from the target URL. See helper header.
set -u

url="${!#}"

if [[ "${CURL_TRANSPORT_FAIL:-0}" == "1" ]]; then
    printf '%s\n' "mock curl: connection refused for ${url}" >&2
    exit 1
fi

case "$url" in
    *login*)   code="${CURL_LOGIN_STATUS:-200}" ;;
    *dynamic*) code="${CURL_DYNAMIC_STATUS:-200}" ;;
    *)         code="${CURL_STATIC_STATUS:-200}" ;;
esac
printf '%s' "$code"
exit 0
CURL_MOCK_EOF
    chmod +x "${SP_MOCK_BIN}/curl"
}

# Canonical invocation: small and deterministic. Extra flags from the caller
# are appended AFTER these (last occurrence wins under getopts). -P 0 keeps
# the suite free of wall-clock sleeps.
sp_run() {
    run bash "$SPIKE_SCRIPT" \
        -u "https://example.test" \
        -d "/dynamic-page.php" \
        -W 3 \
        -P 0 \
        "$@"
}

# Number of CSV data rows (header excluded).
sp_csv_rows() {
    awk 'NR > 1' "$1" | wc -l
}

# Last logged ab argv line.
sp_last_ab_args() {
    tail -1 "$SPIKE_LOG"
}
