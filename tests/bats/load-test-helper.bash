#!/usr/bin/env bash
#
# Shared setup for the load-test BATS suite.
#
# load-test.sh shells out to real binaries (ab, curl, awk, bc, mktemp,
# hostname, uname, date), so the suite installs deterministic MOCKS for ab and
# curl on a sandboxed PATH. Real awk/bc/mktemp/date stay on PATH untouched.
#
# The mock ab:
#   * replies to `ab -V` (used for the metadata sidecar);
#   * logs every invocation to $AB_LOG so tests can assert exact flags
#     (concurrency, request count, -k, -g) fired for warm-up vs measured runs;
#   * emits a fixed report (deterministic Complete/Failed/percentiles/taxonomy);
#   * writes a deterministic `ab -g` raw dump (ttime = 1..N) for percentile
#     checks, unless AB_MODE says otherwise.
#
# Behavior switches (set per test before running):
#   AB_MODE        clean (default) | total-fail | partial
#   AB_NON2XX      number placed on the Non-2xx responses line (default 0)
#   AB_RAW_HEADER  bad -> emit an invalid ab -g header row (tests P7/F-15)
#
# The mock curl answers pre-flight probes with a per-scenario status code
# (defaults mirror a healthy site: 200 for static/dynamic/login, 404 for the
# not-found URL):
#   CURL_STATIC_STATUS / CURL_DYNAMIC_STATUS / CURL_LOGIN_STATUS / CURL_404_STATUS
#   CURL_TRANSPORT_FAIL=1 -> exit 1 (simulates a transport-level failure)

# Absolute path to the tool under test (repo-relative to this helper).
LOAD_TEST_SCRIPT="${BATS_TEST_DIRNAME}/../../load-test.sh"

lt_setup() {
    export LC_ALL=C

    # Sandboxed mock bin directory prepended to PATH; stale per-test state
    # is cleared so tests cannot leak flags into one another.
    MOCK_BIN="${BATS_TEST_TMPDIR}/bin"
    mkdir -p "${MOCK_BIN}"

    AB_LOG="${BATS_TEST_TMPDIR}/ab_calls.log"
    : > "${AB_LOG}"
    export AB_LOG

    unset AB_MODE AB_NON2XX AB_RAW_HEADER
    unset CURL_STATIC_STATUS CURL_DYNAMIC_STATUS CURL_LOGIN_STATUS CURL_404_STATUS
    unset CURL_TRANSPORT_FAIL

    _lt_install_ab_mock
    _lt_install_curl_mock

    PATH="${MOCK_BIN}:${PATH}"
    export PATH
}

_lt_install_ab_mock() {
    cat > "${MOCK_BIN}/ab" <<'AB_MOCK_EOF'
#!/usr/bin/env bash
# Mock ApacheBench for load-test.sh BATS tests.
# Logs `ab <args>` to $AB_LOG for flag assertions, then emits a fixed report
# and a deterministic ab -g raw dump. See load-test-helper.bash header.
set -u

log="${AB_LOG:-/dev/null}"
printf 'ab %s\n' "$*" >> "$log"

if [[ "${1:-}" == "-V" ]]; then
    printf '%s\n' 'ApacheBench/2.4.41 (mock version for load-test BATS suite)'
    exit 0
fi

mode="${AB_MODE:-clean}"
non2xx="${AB_NON2XX:-0}"
raw_header="${AB_RAW_HEADER:-ok}"

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

if [[ "$mode" == "total-fail" ]]; then
    printf '%s\n' "apachebench: Could not connect to server on ${url}"
    exit 1
fi

cat <<REPORT_EOF
This is ApacheBench, Version 2.4.41 <mock>
Server Software:        mock-httpd
Server Hostname:        ${url##*://}
Server Port:            443
Document Path:          /
Document Length:        512 bytes
Concurrency Level:      ${c}
Time taken for tests:   0.024 seconds
Complete requests:      ${n}
Failed requests:        0
   (Connect: 0, Receive: 0, Length: 0, Exceptions: 0)
Non-2xx responses:      ${non2xx}
Total transferred:      1000 bytes
Requests per second:    1234.56 [#/sec] (mean)
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
  95%     20
  98%     22
  99%     25
 100%     30 (longest request)
Connection Errors (Connect: 0, Receive: 0, Length: 0, Exceptions: 0)
REPORT_EOF

# Raw per-request dump (ab -g). ttime values are 1..N so nearest-rank
# percentiles are fully deterministic (e.g. 30 samples 1..30 -> p50=15,
# p95=29, p99=30). AB_RAW_HEADER=bad emits an invalid header row (a layout
# whose last two fields are NOT "ttime wait") to exercise the P7/F-15
# header-validation WARN path; the valid header is the documented ab layout.
if [[ -n "$raw" ]]; then
    if [[ "$raw_header" == "bad" ]]; then
        printf 'starttime\tseconds\tctime\tdtime\ttime\twat\n' > "$raw"
    else
        printf 'starttime\tseconds\tctime\tdtime\tttime\twait\n' > "$raw"
    fi
    for ((r = 1; r <= n; r++)); do
        printf 'Sun Aug 23 06:16:30 2026\t0\t0\t0\t%s\t0\n' "$r" >> "$raw"
    done
fi

if [[ "$mode" == "partial" ]]; then
    exit 1
fi
exit 0
AB_MOCK_EOF
    chmod +x "${MOCK_BIN}/ab"
}

_lt_install_curl_mock() {
    cat > "${MOCK_BIN}/curl" <<'CURL_MOCK_EOF'
#!/usr/bin/env bash
# Mock curl for load-test.sh BATS tests. Answers pre-flight probes with a
# per-scenario status code derived from the target URL. See helper header.
set -u

url="${!#}"

if [[ "${CURL_TRANSPORT_FAIL:-0}" == "1" ]]; then
    printf '%s\n' "mock curl: connection refused for ${url}" >&2
    exit 1
fi

case "$url" in
    *this-page-is-not-real*) code="${CURL_404_STATUS:-404}" ;;
    *dynamic*)               code="${CURL_DYNAMIC_STATUS:-200}" ;;
    *login*)                 code="${CURL_LOGIN_STATUS:-200}" ;;
    *)                       code="${CURL_STATIC_STATUS:-200}" ;;
esac
printf '%s' "$code"
exit 0
CURL_MOCK_EOF
    chmod +x "${MOCK_BIN}/curl"
}

# Run load-test.sh with a canonical, deterministic argument set. Flags passed
# by the caller are appended AFTER these, so the last occurrence wins within
# getopts (e.g. `lt_run -i 3` overrides the default -i 1; `lt_run -e 10 -r "$o"`).
lt_run() {
    run "$LOAD_TEST_SCRIPT" \
        -u "https://example.test" \
        -s "/assets/app.css" \
        -d "/dynamic-page.php" \
        -g "/admin/login.php" \
        -i 1 \
        -W 5 \
        -R 12345 \
        "$@"
}