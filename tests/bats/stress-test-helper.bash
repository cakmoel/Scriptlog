#!/usr/bin/env bash
#
# Shared setup for the stress-test BATS suite.
#
# stress-test.sh shells out to real binaries (ab, curl, awk, bc, mktemp,
# hostname, uname, date), so the suite installs deterministic MOCKS for ab and
# curl on a sandboxed PATH (same pattern as load-test-helper.bash). Real
# awk/bc/mktemp/date stay untouched.
#
# The mock ab:
#   * replies to `ab -V` (metadata sidecar);
#   * logs every invocation to $AB_LOG for exact-flag assertions
#     (warm-up vs measured runs are distinguishable by their -n/-c values);
#   * emits a fixed report (deterministic Complete/Failed/percentiles/...) and
#     a deterministic `ab -g` raw dump (ttime = 1..N) so pooled nearest-rank
#     percentiles are computable exactly.
#
# Behavior switches (set per test BEFORE running):
#   AB_MODE       clean (default) | total-fail (no report, exit 1) | partial (report, exit 1)
#   AB_NON2XX     number placed on the Non-2xx responses line (default 0)
#   AB_CONNERR    failed-request count attributed to the client-side
#                 "(Connect: N, ...)" breakdown; completes N fewer requests
#                 (default 0). Drives the ulimit ADVISORY path.
#   AB_RPS        fixed requests-per-second reported by every run (default 1234.56)
#   AB_RPS_BY_C   comma list mapping concurrency -> rps, e.g. "5=1000,10=1000,20=100";
#                 unmatched levels fall back to AB_RPS. Drives the built-in
#                 throughput-collapse condition deterministically.
#
# The mock curl answers pre-flight probes with a per-scenario status:
#   CURL_STATIC_STATUS / CURL_DYNAMIC_STATUS / CURL_LOGIN_STATUS (default 200)
#   CURL_TRANSPORT_FAIL=1 -> exit 1 (simulates a transport-level failure)

STRESS_SCRIPT="${BATS_TEST_DIRNAME}/../../stress-test.sh"

st_setup() {
    export LC_ALL=C
    unset AB_MODE AB_NON2XX AB_CONNERR AB_RPS AB_RPS_BY_C
    unset CURL_STATIC_STATUS CURL_DYNAMIC_STATUS CURL_LOGIN_STATUS
    unset CURL_TRANSPORT_FAIL
    STRESS_OUT="${BATS_TEST_TMPDIR}/out.csv"
    rm -f "${STRESS_OUT}" "${STRESS_OUT}.meta.json"
    AB_LOG="${BATS_TEST_TMPDIR}/ab_calls.log"
    : > "${AB_LOG}"
    export AB_LOG
    ST_MOCK_BIN="${BATS_TEST_TMPDIR}/bin"
    rm -rf "${ST_MOCK_BIN}"
    mkdir -p "${ST_MOCK_BIN}"
    _st_install_ab_mock
    _st_install_curl_mock
    PATH="${ST_MOCK_BIN}:${PATH}"
    export PATH
}

_st_install_ab_mock() {
    cat > "${ST_MOCK_BIN}/ab" <<'AB_MOCK_EOF'
#!/usr/bin/env bash
# Mock ApacheBench for stress-test.sh BATS tests (see helper header).
set -u

log="${AB_LOG:-/dev/null}"
printf 'ab %s\n' "$*" >> "$log"

if [[ "${1:-}" == "-V" ]]; then
    printf '%s\n' 'ApacheBench/2.4.41 (mock version for stress-test BATS suite)'
    exit 0
fi

mode="${AB_MODE:-clean}"
non2xx="${AB_NON2XX:-0}"
connerr="${AB_CONNERR:-0}"
rps="${AB_RPS:-1234.56}"

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

# Per-concurrency rps override (comma list c=rps,c=rps), first match wins.
if [[ -n "${AB_RPS_BY_C:-}" ]]; then
    IFS=',' read -r -a pairs <<< "$AB_RPS_BY_C"
    for pair in "${pairs[@]}"; do
        lvl="${pair%%=*}"
        val="${pair#*=}"
        if [[ "$c" == "$lvl" ]]; then
            rps="$val"
            break
        fi
    done
fi

if [[ "$mode" == "total-fail" ]]; then
    printf '%s\n' "apachebench: Could not connect to server on ${url}"
    exit 1
fi

# Client-side connect errors: N requests fail at connect time instead of
# completing, which is exactly the situation the script's ADVISORY describes.
complete="$n"
if (( connerr > 0 )); then
    complete=$((n - connerr))
    if (( complete < 0 )); then complete=0; fi
fi
failed="$connerr"

cat <<REPORT_EOF
This is ApacheBench, Version 2.4.41 <mock>
Server Software:        mock-httpd
Server Hostname:        stressmock
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

# Deterministic ab -g dump: ttime = 1..n so nearest-rank pooled percentiles
# are computable exactly by the script.
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
    chmod +x "${ST_MOCK_BIN}/ab"
}

_st_install_curl_mock() {
    cat > "${ST_MOCK_BIN}/curl" <<'CURL_MOCK_EOF'
#!/usr/bin/env bash
# Mock curl for stress-test.sh BATS tests: answers pre-flight probes with a
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
    chmod +x "${ST_MOCK_BIN}/curl"
}

# Canonical invocation: small and deterministic. Extra flags from the caller
# are appended AFTER these (last occurrence wins under getopts).
st_run() {
    run bash "$STRESS_SCRIPT" \
        -u "https://example.test" \
        -d "/dynamic-page.php" \
        -W 3 \
        "$@"
}

# Number of CSV data rows (header excluded).
st_csv_rows() {
    awk 'NR > 1' "$1" | wc -l
}

# Last logged ab argv line.
st_last_ab_args() {
    tail -1 "$AB_LOG"
}
