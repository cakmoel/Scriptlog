#!/usr/bin/env bats
#
# BATS suite for siege-tui.sh (production load-testing TUI).
#
# Run:            bats tests/bats
# Opt-in e2e:     SIEGE_TUI_E2E=1 bats tests/bats
#
# Unit tests source the script with a sandboxed HOME; integration tests
# spawn a local php -S fixture and are skipped unless SIEGE_TUI_E2E=1.

setup() {
    load 'test_helper'
    _common_setup
}

teardown() {
    rm -rf "${TEST_HOME:-}"
}

# ----------------------------------------------------------------
# validate_url
# ----------------------------------------------------------------

@test "validate_url accepts https URL with query string" {
    run validate_url "https://example.com/a?b=1&c=d"
    [ "$status" -eq 0 ]
}

@test "validate_url accepts http host with port and path" {
    run validate_url "http://127.0.0.1:8099/index.html"
    [ "$status" -eq 0 ]
}

@test "validate_url rejects non-http schemes" {
    run validate_url "ftp://example.com/x"
    [ "$status" -eq 1 ]
}

@test "validate_url rejects missing scheme" {
    run validate_url "example.com/no-scheme"
    [ "$status" -eq 1 ]
}

@test "validate_url rejects embedded whitespace" {
    run validate_url "https://bad space.test/x"
    [ "$status" -eq 1 ]
}

@test "validate_url rejects control characters" {
    run validate_url "$(printf 'https://x.com/\t')"
    [ "$status" -eq 1 ]
}

@test "validate_url rejects URLs beyond ${URL_MAX_LEN} chars" {
    local long_url="https://x.com/$(printf 'a%.0s' {1..3000})"
    run validate_url "$long_url"
    [ "$status" -eq 1 ]
}

@test "validate_url rejects shell metacharacters as defense in depth" {
    run validate_url "https://e.com/it's-quoted"
    [ "$status" -eq 1 ]
    run validate_url 'https://e.com/back`tick'
    [ "$status" -eq 1 ]
}

@test "validate_url rejects empty input" {
    run validate_url ""
    [ "$status" -eq 1 ]
}

# ----------------------------------------------------------------
# normalize_duration
# ----------------------------------------------------------------

@test "normalize_duration appends S to bare numbers" {
    run normalize_duration "90"
    [ "$status" -eq 0 ]
    [ "$output" = "90S" ]
}

@test "normalize_duration uppercases the modifier" {
    run normalize_duration "5m"
    [ "$output" = "5M" ]
}

@test "normalize_duration passes through valid modifiers" {
    run normalize_duration "1H"
    [ "$output" = "1H" ]
}

@test "normalize_duration rejects garbage and empty input" {
    run normalize_duration "abc"
    [ "$status" -eq 1 ]
    run normalize_duration ""
    [ "$status" -eq 1 ]
}

# ----------------------------------------------------------------
# method helpers
# ----------------------------------------------------------------

@test "validate_method accepts the siege-supported whitelist" {
    for m in GET POST PUT PATCH DELETE; do
        run validate_method "$m"
        [ "$status" -eq 0 ]
    done
}

@test "validate_method rejects unsupported verbs" {
    run validate_method "TRACE"
    [ "$status" -eq 1 ]
    run validate_method "get"
    [ "$status" -eq 1 ]
}

@test "method_uses_body is true for POST and false for GET" {
    run method_uses_body "POST"
    [ "$status" -eq 0 ]
    run method_uses_body "GET"
    [ "$status" -eq 1 ]
}

# ----------------------------------------------------------------
# body / header validation
# ----------------------------------------------------------------

@test "validate_body accepts urlencoded form data" {
    run validate_body "username=admin&password=s3cret"
    [ "$status" -eq 0 ]
}

@test "validate_body rejects newlines and control characters" {
    run validate_body "$(printf 'a\nb')"
    [ "$status" -eq 1 ]
}

@test "validate_body rejects oversized payloads" {
    local big
    big="$(printf 'x%.0s' {1..9000})"
    run validate_body "$big"
    [ "$status" -eq 1 ]
}

@test "parse_custom_headers yields no args for empty input" {
    parse_custom_headers ""
    [ "${#HEADER_ARGS[@]}" -eq 0 ]
}

@test "parse_custom_headers splits on semicolons but preserves spaces in values" {
    # Direct call: bats `run` would execute in a subshell and hide
    # the HEADER_ARGS mutation from these assertions.
    parse_custom_headers "Accept: application/json; X-Test: siege run one"
    [ "$?" -eq 0 ]
    [ "${#HEADER_ARGS[@]}" -eq 4 ]
    [ "${HEADER_ARGS[1]}" = "Accept: application/json" ]
    [ "${HEADER_ARGS[3]}" = "X-Test: siege run one" ]
}

@test "parse_custom_headers rejects headers without a colon" {
    run parse_custom_headers "no-colon-here"
    [ "$status" -eq 1 ]
}

# ----------------------------------------------------------------
# workload engine
# ----------------------------------------------------------------

@test "expand_template leaves plain URLs untouched" {
    run expand_template "https://example.com/article/123"
    [ "$output" = "https://example.com/article/123" ]
}

@test "has_known_template detects placeholders only when present" {
    run has_known_template "https://e.com/a/{random-id}"
    [ "$status" -eq 0 ]
    run has_known_template "https://example.com/plain"
    [ "$status" -eq 1 ]
}

@test "expand_template produces varied random ids" {
    local -a seen=()
    local i u
    for i in {1..20}; do
        u=$(expand_template "https://e.com/p/{random-id}")
        seen+=("$u")
    done
    local distinct
    distinct=$(printf '%s\n' "${seen[@]}" | sort -u | wc -l)
    [ "$distinct" -ge 3 ]
}

@test "expand_template expands every known placeholder" {
    run expand_template "https://e.com/{random-path}/{file}"
    [[ "$output" != *'{'* ]]
    [[ "$output" == https://e.com/*-page-[0-9][0-9][0-9][0-9]/* ]]
}

@test "generate_workload keeps templated asset URLs within deterministic bounds" {
    WORKDIR="$BATS_TEST_TMPDIR/wd"
    mkdir -p "$WORKDIR"
    WORKLOAD_SAMPLES=25
    generate_workload static "https://e.com/assets/{file}" GET ""
    # Deduplication makes the exact count RANDOM-dependent; assert the
    # deterministic properties instead: bounded, non-trivial, well-formed.
    [ "$WORKLOAD_LINES" -ge 2 ]
    [ "$WORKLOAD_LINES" -le 25 ]
    grep -qE '^https://e\.com/assets/[a-z]+\.(html|css|js|jpg|png|webp|ico|txt)$' "$WORKLOAD_FILE"
}

@test "generate_workload emits siege inline POST syntax" {
    WORKDIR="$BATS_TEST_TMPDIR/wd2"
    mkdir -p "$WORKDIR"
    WORKLOAD_SAMPLES=3
    generate_workload login "https://e.com/login" POST "user=admin&pass=x"
    head -n 1 "$WORKLOAD_FILE" | grep -qE '^https://e\.com/login POST user=admin&pass=x$'
}

@test "generate_workload forces a single line without placeholders" {
    WORKDIR="$BATS_TEST_TMPDIR/wd3"
    mkdir -p "$WORKDIR"
    WORKLOAD_SAMPLES=25
    generate_workload dynamic "https://example.com/article/123" GET ""
    [ "$WORKLOAD_LINES" -eq 1 ]
}

# ----------------------------------------------------------------
# command builder
# ----------------------------------------------------------------

_build_in_sandbox() {
    # $@ = assignments applied before building
    CONCURRENCY=10 REQUEST_MODE=requests REQUEST_COUNT=100 DELAY=0 \
        USER_AGENT="UA/2" CUSTOM_HEADERS="" CONTENT_TYPE="" \
        HEADER_ARGS=() WORKLOAD_LINES="${WLINES:-1}" \
        WORKLOAD_FILE="/tmp/siege-tui-bats-wf.txt" NONINTERACTIVE=0
    if [ -n "${EXTRA:-}" ]; then eval "$EXTRA"; fi
    build_siege_command
}

@test "build_siege_command uses -r and -b in requests mode with zero delay" {
    EXTRA="" WLINES="1" _build_in_sandbox
    [[ "${SIEGE_CMD[*]}" == *" -c 10 "* || "${SIEGE_CMD[*]}" == "-c 10 "* ]]
    [[ " ${SIEGE_CMD[*]} " == *" -r 100 "* ]]
    [[ " ${SIEGE_CMD[*]} " == *" -b "* ]]
    [[ " ${SIEGE_CMD[*]} " != *" -d "* ]]
}

@test "build_siege_command switches to duration mode via -t" {
    EXTRA='REQUEST_MODE=duration DURATION=2M' WLINES="1" _build_in_sandbox
    [[ " ${SIEGE_CMD[*]} " == *" -t 2M "* ]]
    [[ " ${SIEGE_CMD[*]} " != *" -r "* ]]
}

@test "build_siege_command makes delay and benchmark mutually exclusive" {
    EXTRA='DELAY=2' WLINES="7" _build_in_sandbox
    [[ " ${SIEGE_CMD[*]} " == *" -d 2 "* ]]
    [[ " ${SIEGE_CMD[*]} " != *" -b "* ]]
}

@test "build_siege_command always targets the workload file with JSON output" {
    EXTRA="" WLINES="1" _build_in_sandbox
    [[ " ${SIEGE_CMD[*]} " == *" -j "* ]]
    # Trailing glob required: the padded subject ends with a space.
    [[ " ${SIEGE_CMD[*]} " == *" -f /tmp/siege-tui-bats-wf.txt"* ]]
}

@test "build_siege_command adds -i only for multi-line workloads" {
    EXTRA="" WLINES="7" _build_in_sandbox
    [[ " ${SIEGE_CMD[*]} " == *" -i "* || " ${SIEGE_CMD[*]} " == *" -i" ]]
    EXTRA="" WLINES="1" _build_in_sandbox
    [[ " ${SIEGE_CMD[*]} " != *" -i "* && " ${SIEGE_CMD[*]} " != *" -i" ]]
}

@test "build_siege_command passes UA, headers, content-type and quiet flag" {
    EXTRA='USER_AGENT=Bats-UA CUSTOM_HEADERS=x NONINTERACTIVE=1 HEADER_ARGS=(-H "A: b") CONTENT_TYPE=application/json' \
        WLINES="1" _build_in_sandbox
    [[ " ${SIEGE_CMD[*]} " == *" -A Bats-UA "* ]]
    [[ " ${SIEGE_CMD[*]} " == *" -H A: b"* ]]
    [[ " ${SIEGE_CMD[*]} " == *" -T application/json "* ]]
    [[ " ${SIEGE_CMD[*]} " == *" -q "* || " ${SIEGE_CMD[*]} " == *" -q" ]]
}

# ----------------------------------------------------------------
# results analyzer
# ----------------------------------------------------------------

_analyzer_fixture() {
    FIXTURE_DIR="$BATS_TEST_TMPDIR/an"
    mkdir -p "$FIXTURE_DIR"
    printf 'Transactions:\t1023 hits\nAvailability:\t99.12 %%\nFailed transactions:\t9\n' > "$FIXTURE_DIR/fake.log"
    cat > "$FIXTURE_DIR/stats.json" <<'JSON'
{
    "transactions":	1023,
    "availability":	99.12,
    "response_time":	0.05,
    "transaction_rate":	82.90,
    "throughput":	0.10,
    "concurrency":	9.87,
    "successful_transactions": 1014,
    "failed_transactions": 9,
    "longest_transaction": 0.51,
    "shortest_transaction": 0.01
}
JSON
}

@test "analyze_results extracts metrics from siege JSON output" {
    _analyzer_fixture
    run analyze_results "$FIXTURE_DIR/fake.log" "$FIXTURE_DIR/stats.json" 0
    [[ "$output" == *"99.12%"* ]]
    [[ "$output" == *"82.90/sec"* ]]
}

@test "analyze_results append=0 never modifies the log file" {
    _analyzer_fixture
    local before after
    before=$(wc -l < "$FIXTURE_DIR/fake.log")
    analyze_results "$FIXTURE_DIR/fake.log" "$FIXTURE_DIR/stats.json" 0 >/dev/null
    after=$(wc -l < "$FIXTURE_DIR/fake.log")
    [ "$before" -eq "$after" ]
}

@test "analyze_results falls back to text parsing and embeds an analysis block" {
    _analyzer_fixture
    analyze_results "$FIXTURE_DIR/fake.log" "/dev/null" 1 >/dev/null
    grep -q "ANALYSIS" "$FIXTURE_DIR/fake.log"
    grep -q "Availability      : 99.12%" "$FIXTURE_DIR/fake.log"
}

# ----------------------------------------------------------------
# configuration persistence
# ----------------------------------------------------------------

@test "save_config writes mode 600 atomically and load_config round-trips values" {
    # URL must stay within the tool's own validation rules (no raw
    # whitespace) while still stressing %q quoting (&, %, spaces).
    STATIC_URL='https://tricky.test/a?b=1&c=d&e=f%20g'
    USER_AGENT='Weird "UA" v$1'
    save_config

    [ "$(stat -c %a "$CONFIG_FILE")" = "600" ]

    STATIC_URL='sentinel'
    USER_AGENT='sentinel'
    load_config

    [ "$STATIC_URL" = 'https://tricky.test/a?b=1&c=d&e=f%20g' ]
    [ "$USER_AGENT" = 'Weird "UA" v$1' ]
}

@test "load_config neutralizes the legacy HTTP_METHOD key" {
    seed_legacy_config() {
        printf "HTTP_METHOD='POST'\nCONCURRENCY='5'\n" > "$CONFIG_FILE"
    }
    seed_legacy_config
    load_config
    [ -z "${HTTP_METHOD+x}" ]
    [ "$CONCURRENCY" = "5" ]
}

@test "seeded defaults survive load_config without a config file" {
    rm -f "$CONFIG_FILE"
    load_config
    [ "$REQUEST_MODE" = "requests" ]
    [ "$WORKLOAD_SAMPLES" = "25" ]
}

# ----------------------------------------------------------------
# misc helpers
# ----------------------------------------------------------------

@test "sanitize_filename maps unsafe characters to dashes" {
    run sanitize_filename "Not Found"
    [ "$output" = "Not-Found" ]
}

@test "unique_logfile resolves collisions with numeric suffixes" {
    RESULT_DIR="$BATS_TEST_TMPDIR/results"
    mkdir -p "$RESULT_DIR"
    touch "$RESULT_DIR/collide.log"
    run unique_logfile "$RESULT_DIR/collide.log"
    [ "$output" = "$RESULT_DIR/collide-2.log" ]
    run unique_logfile "$RESULT_DIR/free.log"
    [ "$output" = "$RESULT_DIR/free.log" ]
}

# ----------------------------------------------------------------
# CLI contract
# ----------------------------------------------------------------

@test "--help exits 0 and prints usage" {
    run bash "$SIEGE_TUI_SCRIPT" --help
    [ "$status" -eq 0 ]
    [[ "$output" == *"Usage:"* ]]
}

@test "--version exits 0 and reports the tool version" {
    run bash "$SIEGE_TUI_SCRIPT" --version
    [ "$status" -eq 0 ]
    [[ "$output" == *"2.2.0"* ]]
}

@test "unknown options exit 2 with usage on stderr" {
    run bash "$SIEGE_TUI_SCRIPT" --bogus
    [ "$status" -eq 2 ]
}

@test "--run with unknown scenario exits 2" {
    run bash "$SIEGE_TUI_SCRIPT" --run doesnotexist --yes
    [ "$status" -eq 2 ]
}

@test "--run unconfigured scenario exits 3" {
    run bash "$SIEGE_TUI_SCRIPT" --run notfound --yes
    [ "$status" -eq 3 ]
}

# ----------------------------------------------------------------
# secret hygiene (v2.1.0)
# ----------------------------------------------------------------

@test "masked_body_note hides content but reports payload size" {
    run masked_body_note "username=admin&password=s3cret"
    [ "$status" -eq 0 ]
    [[ "$output" == "<masked, "* ]]
    [[ "$output" == *" bytes>" ]]
    [[ "$output" != *s3cret* ]]
}

@test "masked_body_note yields nothing for empty bodies" {
    run masked_body_note ""
    [ "$status" -eq 0 ]
    [ -z "$output" ]
}

@test "redact_sensitive_headers masks credentials and preserves the rest" {
    run redact_sensitive_headers "Authorization: Bearer abc.def; X-Test: visible; Cookie: sid=xyz"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Authorization: <redacted>"* ]]
    [[ "$output" == *"Cookie: <redacted>"* ]]
    [[ "$output" == *"X-Test: visible"* ]]
    [[ "$output" != *abc.def* ]]
    [[ "$output" != *sid=xyz* ]]
}

@test "parse_custom_headers commits nothing on partial failure" {
    HEADER_ARGS=()
    run parse_custom_headers "Good: yes;bad-no-colon"
    [ "$status" -eq 1 ]
    [ "${#HEADER_ARGS[@]}" -eq 0 ]
}

@test "detect_siege_version populates globals from stderr output" {
    command -v siege >/dev/null 2>&1 || skip "siege not installed"
    detect_siege_version
    [[ "$SIEGE_MAJOR" =~ ^[0-9]+$ ]]
    [ "$SIEGE_MAJOR" -ge 1 ]
    [[ "$SIEGE_VERSION_LINE" =~ [0-9] ]]
}

# ----------------------------------------------------------------
# configuration sanitization (v2.1.0)
# ----------------------------------------------------------------

@test "load_config resets corrupted numeric settings to defaults" {
    _seed_config
    sed -i "s/^CONCURRENCY=.*/CONCURRENCY='not-a-number'/" "$CONFIG_FILE"
    load_config
    [ "$CONCURRENCY" = "10" ]
    [ "$REQUEST_COUNT" = "42" ]
}

@test "load_config clears invalid URLs and header strings" {
    _seed_config
    printf "STATIC_URL='not-a-valid-url'\nCUSTOM_HEADERS='BrokenHeader'\n" >> "$CONFIG_FILE"
    load_config
    [ -z "$STATIC_URL" ]
    [ -z "$CUSTOM_HEADERS" ]
    [ "$REQUEST_COUNT" = "42" ]
}

@test "seeded valid configuration survives sanitization untouched" {
    _seed_config
    load_config
    [ "$CONCURRENCY" = "7" ]
    [ "$WORKLOAD_SAMPLES" = "9" ]
    [ "$STATIC_URL" = 'http://fixture.test/index.html' ]
    [ "$LOGIN_METHOD" = 'GET' ]
}

# ----------------------------------------------------------------
# analyzer integrity checks (v2.1.0)
# ----------------------------------------------------------------

_zero_success_fixture() {
    FIXTURE_DIR="$BATS_TEST_TMPDIR/an-zero"
    mkdir -p "$FIXTURE_DIR"
    echo "placeholder log" > "$FIXTURE_DIR/run.log"
    cat > "$FIXTURE_DIR/stats.json" <<'JSON'
{
    "transactions":	1000,
    "availability":	100.00,
    "successful_transactions": 0,
    "failed_transactions": 0
}
JSON
}

@test "analyze_results flags runs where every transaction failed silently" {
    _zero_success_fixture
    run analyze_results "$FIXTURE_DIR/run.log" "$FIXTURE_DIR/stats.json" 0
    [[ "$output" == *"ZERO successful transactions"* ]]
}

@test "analyze_results flags volume overrun against the request budget" {
    FIXTURE_DIR="$BATS_TEST_TMPDIR/an-vol"
    mkdir -p "$FIXTURE_DIR"
    echo "placeholder log" > "$FIXTURE_DIR/run.log"
    cat > "$FIXTURE_DIR/stats.json" <<'JSON'
{
    "transactions":	99999,
    "availability":	100.00,
    "successful_transactions": 99999,
    "failed_transactions": 0
}
JSON
    CONCURRENCY=2 REQUEST_MODE=requests REQUEST_COUNT=5 WORKLOAD_SAMPLES=3
    run analyze_results "$FIXTURE_DIR/run.log" "$FIXTURE_DIR/stats.json" 0
    [[ "$output" == *"volume overrun"* ]]
    [[ "$output" == *"redirect hop"* ]]
}

@test "analyze_results reports counter disagreement as a metric anomaly" {
    FIXTURE_DIR="$BATS_TEST_TMPDIR/an-anom"
    mkdir -p "$FIXTURE_DIR"
    echo "placeholder log" > "$FIXTURE_DIR/run.log"
    cat > "$FIXTURE_DIR/stats.json" <<'JSON'
{
    "transactions":	1000,
    "availability":	100.00,
    "successful_transactions": 600,
    "failed_transactions": 100
}
JSON
    run analyze_results "$FIXTURE_DIR/run.log" "$FIXTURE_DIR/stats.json" 0
    [[ "$output" == *"metric anomaly"* ]]
}

# ----------------------------------------------------------------
# CLI contract additions (v2.1.0)
# ----------------------------------------------------------------

@test "--run without --yes is rejected with exit 2" {
    run bash "$SIEGE_TUI_SCRIPT" --run static
    [ "$status" -eq 2 ]
    [[ "$output" == *"--yes"* ]]
}

@test "--yes may precede --run without tripping the contract" {
    run bash "$SIEGE_TUI_SCRIPT" --yes --run doesnotexist
    [ "$status" -eq 2 ]
}

# ----------------------------------------------------------------
# misc helpers (v2.1.0)
# ----------------------------------------------------------------

@test "unique_logfile reserves the chosen path exclusively" {
    RESULT_DIR="$BATS_TEST_TMPDIR/results-v21"
    mkdir -p "$RESULT_DIR"
    run unique_logfile "$RESULT_DIR/fresh.log"
    [ "$status" -eq 0 ]
    [ -e "$output" ]
    [ ! -s "$output" ]
}

@test "tui_menu signals cancel on EOF in plain mode" {
    TUI="plain"
    AUTO_YES=0 NONINTERACTIVE=0
    run tui_menu "Title" "Prompt" "a" "opt-a" </dev/null
    [ "$status" -eq 1 ]
}

# ----------------------------------------------------------------
# safety envelope (v2.2.0)
# ----------------------------------------------------------------

@test "clamp_concurrency caps concurrency in safe mode" {
    CONCURRENCY_SAFE_CAP=40 UNSAFE=0
    run clamp_concurrency "10"
    [ "$status" -eq 0 ]
    [ "$output" = "10" ]
    run clamp_concurrency "500"
    [ "$status" -eq 1 ]
    [ "$output" = "40" ]
}

@test "clamp_concurrency passes through with --unsafe" {
    CONCURRENCY_SAFE_CAP=40 UNSAFE=1
    run clamp_concurrency "500"
    [ "$status" -eq 0 ]
    [ "$output" = "500" ]
}

@test "duration_to_seconds converts siege modifiers" {
    run duration_to_seconds "30S"
    [ "$output" = "30" ]
    run duration_to_seconds "5M"
    [ "$output" = "300" ]
    run duration_to_seconds "1H"
    [ "$output" = "3600" ]
}

@test "siege_max_seconds returns a finite bound in every mode" {
    REQUEST_MODE=requests
    run siege_max_seconds
    [ "$status" -eq 0 ]
    [ "$output" -ge 900 ]
    REQUEST_MODE=duration DURATION=10S
    run siege_max_seconds
    [ "$output" -ge 900 ]
}

@test "build_siege_command binds siege to a sandboxed rc with timeouts" {
    WORKDIR="$BATS_TEST_TMPDIR/wd-rc"
    mkdir -p "$WORKDIR"
    SIEGE_TIMEOUT=10 SIEGE_CONNECT_TIMEOUT=5 SIEGE_SOCKET_TIMEOUT=15 SIEGE_FAILURES_ABORT=25 \
        CONCURRENCY=10 REQUEST_MODE=requests REQUEST_COUNT=100 DELAY=0 \
        USER_AGENT="UA/2" CUSTOM_HEADERS="" CONTENT_TYPE="" \
        HEADER_ARGS=() WORKLOAD_LINES=1 WORKLOAD_FILE="$WORKDIR/wf.txt" NONINTERACTIVE=0 \
        UNSAFE=0 CONCURRENCY_SAFE_CAP=40
    build_siege_command
    [[ " ${SIEGE_CMD[*]} " == *" -R $WORKDIR/siegerc"* ]]
    grep -q "timeout = 10" "$WORKDIR/siegerc"
    grep -q "connection-timeout = 5" "$WORKDIR/siegerc"
    grep -q "socket-timeout = 15" "$WORKDIR/siegerc"
}

@test "build_siege_command omits -R when no workdir exists" {
    WORKDIR=""
    WORKLOAD_FILE="/tmp/wf.txt" CONCURRENCY=10 REQUEST_MODE=requests REQUEST_COUNT=100 DELAY=0 \
        USER_AGENT="UA/2" CUSTOM_HEADERS="" CONTENT_TYPE="" \
        HEADER_ARGS=() WORKLOAD_LINES=1 NONINTERACTIVE=0 UNSAFE=0 CONCURRENCY_SAFE_CAP=40
    build_siege_command
    [[ " ${SIEGE_CMD[*]} " != *" -R "* ]]
}

@test "--unsafe is accepted and disables the concurrency clamp" {
    run bash "$SIEGE_TUI_SCRIPT" --unsafe --run doesnotexist --yes
    [ "$status" -eq 2 ]
    run bash "$SIEGE_TUI_SCRIPT" --unsafe --help
    [ "$status" -eq 0 ]
    [[ "$output" == *"--unsafe"* ]]
}

@test "warn_local_target flags loopback targets without failing" {
    run warn_local_target "localhost"
    [ "$status" -eq 0 ]
    [[ "$output" == *"loopback"* ]]
}

@test "warn_local_target is a no-op failure on unresolvable names" {
    run warn_local_target "siege-tui-no-such-host.invalid"
    [ "$status" -eq 0 ]
}

# ----------------------------------------------------------------
# integration (opt-in: SIEGE_TUI_E2E=1)
# ----------------------------------------------------------------

_free_port() {
    local p
    for p in {31000..31049}; do
        if ! timeout 1 bash -c "</dev/tcp/127.0.0.1/$p" 2>/dev/null; then
            printf '%s' "$p"
            return 0
        fi
    done
    return 1
}

@test "integration: headless GET run reaches a local php fixture" {
    [ "${SIEGE_TUI_E2E:-}" = "1" ] || skip "set SIEGE_TUI_E2E=1 to enable"
    command -v php >/dev/null 2>&1 || skip "php not available"

    local port docroot
    port=$(_free_port) || skip "no free port found"
    docroot="$BATS_TEST_TMPDIR/docroot"
    mkdir -p "$docroot"
    echo '<html>fixture</html>' > "$docroot/index.html"

    php -S "127.0.0.1:${port}" -t "$docroot" >/dev/null 2>&1 &
    local srv=$!
    sleep 1
    curl -s -o /dev/null -m 2 "http://127.0.0.1:${port}/index.html" || {
        kill "$srv" 2>/dev/null
        skip "fixture server failed to bind :${port}"
    }

    cat > "$CONFIG_FILE" <<EOF
STATIC_URL='http://127.0.0.1:${port}/index.html'
STATIC_TYPE='html'
STATIC_METHOD='GET'
STATIC_BODY=''
CONCURRENCY='2'
REQUEST_MODE='requests'
REQUEST_COUNT='6'
DELAY='0'
EOF

    run bash "$SIEGE_TUI_SCRIPT" --run static --yes
    kill "$srv" 2>/dev/null

    [ "$status" -eq 0 ]
    local log
    log=$(find "$RESULT_DIR" -name '*Static*.log' | head -n 1)
    [ -s "$log" ]
    grep -q "ANALYSIS" "$log"
    [ -s "${log%.log}.json" ]
}
