#!/usr/bin/env bash
#
# Shared setup for the soak-test BATS suite.
#
# soak-test.sh shells out to real binaries (ab, curl, awk, bc, mktemp,
# hostname, uname, date, sleep), so the suite installs deterministic MOCKS on
# a sandboxed PATH (same pattern as spike-test-helper.bash) PLUS a FAKE
# WALL CLOCK so multi-checkpoint runs finish instantly and deterministically.
# Real awk/bc/mktemp/date(hostname/uname) stay untouched except where mocking
# time is required.
#
# The fake clock:
#   * $SOAK_FAKECLOCK holds the current fake epoch (starts at 1730000000);
#   * mocked `date %s` reads it, `date -Iseconds`/date +%Y%m%d... delegate
#     formatting to the real /bin/date via -d @epoch;
#   * mocked `sleep N` ADVANCES the clock by N seconds (nothing is really
#     slept), so the soak loop's cadence math runs deterministically.
#
# The mock ab:
#   * replies to `ab -V` (metadata sidecar);
#   * logs every invocation to $SOAK_LOG for exact-flag assertions
#     (warm-up vs measured runs are distinguishable by -n/-c and by -g);
#   * persists an invocation counter in $SOAK_CALLCOUNTER.
#
# Behavior switches (set per test BEFORE running):
#
#   AB_MODE       clean (default) | total-fail (no report, exit 1)
#   AB_FAIL_AT_SEQ  when set to N, the Nth MEASURED invocation (warm-up is
#                 call 1 and is skipped) leaves no report and exits 1; later
#                 measured calls behave per AB_MODE (clean by default). Drives
#                 the soak abort durability tests deterministically.
#   AB_WARMUP_FAIL=1 -> the warm-up invocation exits 1 (measured runs unaffected)
#   AB_NON2XX     number placed on the Non-2xx responses line (default 0)
#   AB_RPS        fixed requests-per-second reported by every run (default 1000.00)
#   AB_RPS_SEQ    comma list of rps values consumed IN CALL ORDER by the
#                 MEASURED invocations; when exhausted the LAST value repeats.
#                 Soak checkpoints share identical -n/-c, so call order is the
#                 only way to script e.g. "1000,1000,400" drift sequences.
#   AB_P95_SEQ    same as AB_RPS_SEQ but for the report-block 95% line
#                 (default 28), which the CSV p95 column and drift ratios read.

SOAK_SCRIPT="${BATS_TEST_DIRNAME}/../../soak-test.sh"

so_setup() {
    export LC_ALL=C
    unset AB_MODE AB_FAIL_AT_SEQ AB_WARMUP_FAIL AB_NON2XX AB_RPS AB_RPS_SEQ
    unset AB_P95_SEQ
    unset CURL_STATIC_STATUS CURL_DYNAMIC_STATUS CURL_LOGIN_STATUS
    unset CURL_TRANSPORT_FAIL
    SOAK_OUT="${BATS_TEST_TMPDIR}/out.csv"
    rm -f "${SOAK_OUT}" "${SOAK_OUT}.meta.json"
    SOAK_LOG="${BATS_TEST_TMPDIR}/ab_calls.log"
    : > "${SOAK_LOG}"
    export SOAK_LOG
    SOAK_CALLCOUNTER="${BATS_TEST_TMPDIR}/ab_call_counter"
    printf '0\n' > "${SOAK_CALLCOUNTER}"
    export SOAK_CALLCOUNTER
    SOAK_FAKECLOCK="${BATS_TEST_TMPDIR}/fake_clock"
    printf '1730000000\n' > "${SOAK_FAKECLOCK}"
    export SOAK_FAKECLOCK
    SO_MOCK_BIN="${BATS_TEST_TMPDIR}/bin"
    rm -rf "${SO_MOCK_BIN}"
    mkdir -p "${SO_MOCK_BIN}"
    _so_install_date_mock
    _so_install_sleep_mock
    _so_install_ab_mock
    _so_install_curl_mock
    PATH="${SO_MOCK_BIN}:${PATH}"
    export PATH
}

_so_install_date_mock() {
    cat > "${SO_MOCK_BIN}/date" <<'DATE_MOCK_EOF'
#!/usr/bin/env bash
# Mock date for soak-test.sh BATS tests: reads the fake clock for +%s and
# delegates formatting to the real /bin/date via -d @epoch.
set -u

clock_read() {
    cat "${SOAK_FAKECLOCK:-/dev/null}" 2>/dev/null || echo 1730000000
}

case "$*" in
    '+%s')                     printf '%s\n' "$(clock_read)" ;;
    '+%Y%m%d_%H%M%S')          /bin/date -d "@$(clock_read)" '+'"%Y%m%d_%H%M%S" ;;
    '-Iseconds')               /bin/date -Iseconds -d "@$(clock_read)" ;;
    '-u +%Y-%m-%dT%H:%M:%SZ')  /bin/date -u -d "@$(clock_read)" '+'"%Y-%m-%dT%H:%M:%SZ" ;;
    *)                         /bin/date "$@" ;;
esac
exit 0
DATE_MOCK_EOF
    chmod +x "${SO_MOCK_BIN}/date"
}

_so_install_sleep_mock() {
    cat > "${SO_MOCK_BIN}/sleep" <<'SLEEP_MOCK_EOF'
#!/usr/bin/env bash
# Mock sleep for soak-test.sh BATS tests: advances the fake clock instead of
# sleeping, keeping the soak cadence math exact and the suite fast.
set -u
secs="${1:-0}"
clock=$(cat "${SOAK_FAKECLOCK:-/dev/null}" 2>/dev/null || echo 1730000000)
printf '%s\n' "$(( clock + secs ))" > "$SOAK_FAKECLOCK"
exit 0
SLEEP_MOCK_EOF
    chmod +x "${SO_MOCK_BIN}/sleep"
}

_so_install_ab_mock() {
    cat > "${SO_MOCK_BIN}/ab" <<'AB_MOCK_EOF'
#!/usr/bin/env bash
# Mock ApacheBench for soak-test.sh BATS tests (see helper header).
set -u

log="${SOAK_LOG:-/dev/null}"
printf 'ab %s\n' "$*" >> "$log"

if [[ "${1:-}" == "-V" ]]; then
    printf '%s\n' 'ApacheBench/2.4.41 (mock version for soak-test BATS suite)'
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

# Invocation numbering: call 1 is the warm-up, measured checkpoints follow.
call=$(cat "${SOAK_CALLCOUNTER:-/dev/null}" 2>/dev/null || echo 0)
call=$((call + 1))
if [[ -n "${SOAK_CALLCOUNTER:-}" ]]; then
    printf '%s\n' "$call" > "$SOAK_CALLCOUNTER"
fi

# Pick the per-call value from a comma list (call-order keyed).
seq_pick() {
    local list="$1" fallback="$2" idx=$((call - 2))
    IFS=',' read -r -a seq <<< "$list"
    if (( idx >= 0 && idx < ${#seq[@]} )); then
        printf '%s' "${seq[$idx]}"
    elif (( ${#seq[@]} > 0 )); then
        printf '%s' "${seq[${#seq[@]} - 1]}"
    else
        printf '%s' "$fallback"
    fi
}

rps="${AB_RPS:-1000.00}"
if [[ -n "${AB_RPS_SEQ:-}" ]]; then
    rps=$(seq_pick "$AB_RPS_SEQ" "$rps")
fi
p95="${AB_P95_SEQ:-28.0}"
if [[ -n "${AB_P95_SEQ:-}" ]]; then
    p95=$(seq_pick "$AB_P95_SEQ" "$p95")
fi

non2xx="${AB_NON2XX:-0}"
connerr="${AB_CONNERR:-0}"

# Warm-up runs carry no -g flag.
if [[ -z "$raw" && "${AB_WARMUP_FAIL:-0}" == "1" ]]; then
    printf '%s\n' "apachebench: warm-up forced failure against ${url}"
    exit 1
fi
# AB_FAIL_AT_SEQ: the Nth measured invocation refuses (no report, exit 1).
fail_at="${AB_FAIL_AT_SEQ:-0}"
if [[ -n "$raw" && "$fail_at" -gt 0 && "$((call - 1))" -eq "$fail_at" ]]; then
    printf '%s\n' "apachebench: Could not connect to server on ${url}"
    exit 1
fi

mode="${AB_MODE:-clean}"
is_total_fail=0
if [[ "$mode" == "total-fail" ]]; then
    printf '%s\n' "apachebench: Could not connect to server on ${url}"
    exit 1
fi

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
Server Hostname:        soakmock
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
  50%     21
  66%     23
  75%     25
  80%     26
  90%     27
  95%     ${p95}
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

exit 0
AB_MOCK_EOF
    chmod +x "${SO_MOCK_BIN}/ab"
}

_so_install_curl_mock() {
    cat > "${SO_MOCK_BIN}/curl" <<'CURL_MOCK_EOF'
#!/usr/bin/env bash
# Mock curl for soak-test.sh BATS tests: answers pre-flight probes with a
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
    chmod +x "${SO_MOCK_BIN}/curl"
}

# Canonical invocation: small and deterministic. The fake clock makes
# -d 60 -I 30 (two checkpoints) complete instantly; extra flags from the
# caller are appended AFTER these (last occurrence wins under getopts).
# NOTE: soak-test.sh's -d is the DURATION; the dynamic content path is -C.
so_run() {
    run bash "$SOAK_SCRIPT" \
        -u "https://example.test" \
        -C "/dynamic-page.php" \
        -W 3 \
        -d 60 -I 30 \
        "$@"
}

# Number of CSV data rows (header excluded).
so_csv_rows() {
    awk 'NR > 1' "$1" | wc -l
}

# Last logged ab argv line.
so_last_ab_args() {
    tail -1 "$SOAK_LOG"
}
