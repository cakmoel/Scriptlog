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
#
# build_siege_command is covered further down with exact argv diffs
# (see _siege_argv / _siege_defaults); the test here pins the
# remaining workload-file and JSON-output contract.
# ----------------------------------------------------------------

@test "build_siege_command always targets the workload file with JSON output" {
    _siege_defaults
    run _siege_argv
    [ "$status" -eq 0 ]
    [[ " ${lines[*]} " == *" -j "* ]]
    [[ " ${lines[*]} " == *" -f $BATS_TEST_TMPDIR/wf.txt"* ]]
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

# Build the command with a known, fixed input set and print the resulting
# argv one element per line. Diffing against a here-doc asserts the exact
# flag set and ordering, not just the presence of one flag.
_siege_argv() {
    build_siege_command
    printf '%s\n' "${SIEGE_CMD[@]}"
}

# Membership tests over the argv array rather than over its flattened string:
# BATS_TEST_TMPDIR contains substrings such as "-r", so a flattened
# "not *-r*" assertion would match the path, not a flag.
_siege_has_arg() {
    local needle="$1" i
    for i in "${!SIEGE_CMD[@]}"; do
        [ "${SIEGE_CMD[$i]}" = "$needle" ] && return 0
    done
    return 1
}

_siege_lacks_arg() {
    _siege_has_arg "$1" && return 1
    return 0
}

# build_siege_command calls clamp_concurrency in a command substitution; when
# it clamps it returns non-zero to raise a warning, which errexit would turn
# into an abort. Production runs without errexit, so disable it here too.
_build_noerrexit() {
    set +e
    build_siege_command
    set -e
}

# Defaults are chosen so that only the argument under test varies; each test
# overrides a single one of them.
_siege_defaults() {
    CONCURRENCY=10
    REQUEST_MODE=requests
    REQUEST_COUNT=100
    DURATION=1M
    DELAY=0
    USER_AGENT="UA/2"
    CUSTOM_HEADERS=""
    CONTENT_TYPE=""
    HEADER_ARGS=()
    WORKLOAD_LINES=1
    WORKLOAD_FILE="$BATS_TEST_TMPDIR/wf.txt"
    NONINTERACTIVE=1
    UNSAFE=0
    CONCURRENCY_SAFE_CAP=40
    SIEGE_TIMEOUT=10
    SIEGE_CONNECT_TIMEOUT=5
    SIEGE_SOCKET_TIMEOUT=15
    SIEGE_FAILURES_ABORT=25
    WORKDIR=""
}

@test "build_siege_command emits the exact benchmark-mode argv" {
    _siege_defaults
    _siege_argv >/dev/null
    [ "${SIEGE_CMD[0]}" = "siege" ]
    [ "$(printf '%s\n' "${SIEGE_CMD[@]}")" = "$(cat <<EOF
siege
-c
10
-r
100
-b
-A
UA/2
-q
-j
-f
$(printf '%s' "$BATS_TEST_TMPDIR/wf.txt")
EOF
)" ]
    # -f always carries the sandboxed workload file, and no -R without a
    # workdir, no -d in benchmark mode and no -t outside duration mode.
    _siege_has_arg -f
    _siege_lacks_arg -R
    _siege_lacks_arg -d
    _siege_lacks_arg -t
    _siege_lacks_arg -i
}

@test "build_siege_command swaps -r for -t and -b for -d in duration mode" {
    _siege_defaults
    REQUEST_MODE=duration DURATION=2M DELAY=3
    _siege_argv >/dev/null
    [ "$(printf '%s\n' "${SIEGE_CMD[@]}")" = "$(cat <<EOF
siege
-c
10
-t
2M
-d
3
-A
UA/2
-q
-j
-f
$(printf '%s' "$BATS_TEST_TMPDIR/wf.txt")
EOF
)" ]
    _siege_lacks_arg -r
    _siege_lacks_arg -b
}

@test "build_siege_command keeps -b in duration mode when no delay is set" {
    _siege_defaults
    REQUEST_MODE=duration DURATION=45S DELAY=0
    _siege_argv >/dev/null
    _siege_has_arg -t
    _siege_has_arg -b
    _siege_lacks_arg -d
    [ "${SIEGE_CMD[4]}" = "45S" ]
}

@test "build_siege_command appends custom headers and the content type" {
    _siege_defaults
    HEADER_ARGS=(-H "Accept: application/json" -H "X-Trace: abc")
    CONTENT_TYPE="application/json"
    _siege_argv >/dev/null
    [ "$(printf '%s\n' "${SIEGE_CMD[@]}")" = "$(cat <<EOF
siege
-c
10
-r
100
-b
-A
UA/2
-H
Accept: application/json
-H
X-Trace: abc
-T
application/json
-q
-j
-f
$(printf '%s' "$BATS_TEST_TMPDIR/wf.txt")
EOF
)" ]
}

@test "build_siege_command omits -A and -T when unset, and -q when interactive" {
    _siege_defaults
    USER_AGENT="" CONTENT_TYPE="" NONINTERACTIVE=0
    _siege_argv >/dev/null
    _siege_lacks_arg -A
    _siege_lacks_arg -T
    _siege_lacks_arg -q
    # -j/-f are unconditional, so the command is still fully specified.
    _siege_has_arg -j
    _siege_has_arg -f
}

@test "build_siege_command adds -i only for multi-line workloads" {
    _siege_defaults
    WORKLOAD_LINES=1
    _siege_argv >/dev/null
    _siege_lacks_arg -i

    WORKLOAD_LINES=12
    _siege_argv >/dev/null
    [ "$(printf '%s\n' "${SIEGE_CMD[@]}")" = "$(cat <<EOF
siege
-c
10
-r
100
-b
-A
UA/2
-q
-j
-f
$(printf '%s' "$BATS_TEST_TMPDIR/wf.txt")
-i
EOF
)" ]
}

@test "build_siege_command clamps concurrency inside the argv in safe mode" {
    _siege_defaults
    # clamp_concurrency signals "I clamped" with a non-zero return, which
    # errexit would otherwise treat as a build failure.
    CONCURRENCY=500 CONCURRENCY_SAFE_CAP=40 UNSAFE=0
    _build_noerrexit
    [ "${SIEGE_CMD[1]}" = "-c" ]
    [ "${SIEGE_CMD[2]}" = "40" ]

    CONCURRENCY=500 UNSAFE=1
    _siege_argv >/dev/null
    [ "${SIEGE_CMD[2]}" = "500" ]
}

@test "build_siege_command redacts credentials in the display mirror only" {
    _siege_defaults
    HEADER_ARGS=(-H "Authorization: Bearer sekrit-token")
    build_siege_command

    # Raw argv keeps the real credential; the persisted/displayed mirror does
    # not. SIEGE_DISPLAY is produced with printf %q, so the angle brackets of
    # the placeholder come out backslash-escaped.
    [ "${SIEGE_CMD[9]}" = "Authorization: Bearer sekrit-token" ]
    [[ "$SIEGE_DISPLAY" == *redacted* ]]
    [[ "$SIEGE_DISPLAY" == *"Authorization:"* ]]
    [[ "$SIEGE_DISPLAY" != *"sekrit-token"* ]]
}

@test "build_siege_command binds siege to a sandboxed rc with timeouts" {
    _siege_defaults
    WORKDIR="$BATS_TEST_TMPDIR/wd-rc"
    mkdir -p "$WORKDIR"
    _siege_argv >/dev/null

    grep -q "^timeout = 10$" "$WORKDIR/siegerc"
    grep -q "^connection-timeout = 5$" "$WORKDIR/siegerc"
    grep -q "^socket-timeout = 15$" "$WORKDIR/siegerc"
    grep -q "^failures-until-abort = 25$" "$WORKDIR/siegerc"
    [ "$(stat -c '%a' "$WORKDIR/siegerc")" = "600" ]

    # -R must be the final pair in the argv, and point at that rc file.
    local last=$(( ${#SIEGE_CMD[@]} - 1 ))
    [ "${SIEGE_CMD[$((last - 1))]}" = "-R" ]
    [ "${SIEGE_CMD[$last]}" = "$WORKDIR/siegerc" ]
}

@test "build_siege_command omits -R when no workdir exists" {
    _siege_defaults
    WORKDIR=""
    _siege_argv >/dev/null
    _siege_lacks_arg -R
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
# primitive helpers
# ----------------------------------------------------------------

@test "trim strips leading and trailing whitespace only" {
    [ "$(trim '   a b   ')" = "a b" ]
    [ "$(trim $'\t x \t')" = "x" ]
    [ "$(trim '')" = "" ]
    [ "$(trim '     ')" = "" ]
    [ "$(trim '  a  b  ')" = "a  b" ]
}

@test "is_number accepts plain decimals and rejects everything else" {
    for v in 0 42 007 3.5 0.5; do
        run is_number "$v"
        [ "$status" -eq 0 ]
    done
    for v in "" abc -1 1e5 1.2.3 " 1" "1 "; do
        run is_number "$v"
        [ "$status" -eq 1 ]
    done
}

@test "print_line emits exactly the requested number of equals signs" {
    run print_line 5
    [ "$output" = "=====" ]
    run print_line 0
    [ -z "$output" ]
}

@test "sanitize_filename collapses separators and drops unsafe characters" {
    [ "$(sanitize_filename 'Not Found')" = "Not-Found" ]
    [ "$(sanitize_filename 'a/b c')" = "a-b-c" ]
    [ "$(sanitize_filename 'x<script>')" = "x-script-" ]
    # A run of dashes is squeezed so the name never starts with an option.
    [ "$(sanitize_filename '-----')" = "-" ]
    [ "$(sanitize_filename '----q')" = "-q" ]
    [ "$(sanitize_filename 'café.txt')" = "caf-.txt" ]
    # Underscores and dots are the only punctuation that survives.
    [ "$(sanitize_filename 'a..b')" = "a..b" ]
    [ "$(sanitize_filename 'a_b.c')" = "a_b.c" ]
}

@test "masked_body_note reports the exact byte count" {
    [ "$(masked_body_note 'username=admin&password=s3cret')" = "<masked, 30 bytes>" ]
    # An empty body produces no note at all, so nothing is written to a log.
    [ -z "$(masked_body_note '')" ]
}

@test "redact_header_value masks credential headers and keeps the rest" {
    [ "$(redact_header_value 'Authorization: Bearer sekrit')" = "Authorization: <redacted>" ]
    [ "$(redact_header_value 'authorization: Bearer sekrit')" = "authorization: <redacted>" ]
    [ "$(redact_header_value 'Cookie: sid=abc')" = "Cookie: <redacted>" ]
    [ "$(redact_header_value 'set-cookie: sid=abc')" = "set-cookie: <redacted>" ]
    [ "$(redact_header_value 'Proxy-Authorization: Basic zzz')" = "Proxy-Authorization: <redacted>" ]
    # The header name casing is preserved; non-sensitive values pass through.
    [ "$(redact_header_value 'X-Trace-Id: abc123')" = "X-Trace-Id: abc123" ]
}

@test "redact_sensitive_headers masks only sensitive entries in a multi-header blob" {
    run redact_sensitive_headers $'Authorization: Bearer sekrit\nX-Trace: abc'
    [ "$status" -eq 0 ]
    [[ "$output" == *"Authorization: <redacted>"* ]]
    [[ "$output" == *"X-Trace: abc"* ]]
    [[ "$output" != *"sekrit"* ]]

    [ -z "$(redact_sensitive_headers '')" ]
}

@test "die writes to stderr with the prefix and honours the exit code" {
    run die "boom" 7
    [ "$status" -eq 7 ]
    [[ "$output" == *"siege-tui: boom"* ]]
}

@test "validate_header enforces the siege -H contract" {
    run validate_header "Accept: application/json"
    [ "$status" -eq 0 ]
    run validate_header "no-colon"
    [ "$status" -eq 1 ]
    run validate_header "X-Empty:"
    [ "$status" -eq 1 ]
    run validate_header ": novalue"
    [ "$status" -eq 1 ]
    run validate_header ""
    [ "$status" -eq 1 ]
    run validate_header "$(printf 'X-Bad: value\nInjected: yes')"
    [ "$status" -eq 1 ]
    # Only the "name: value" shape is validated; the value may be arbitrarily
    # long and may contain spaces.
    run validate_header "X-Long: $(printf 'a%.0s' {1..2000})"
    [ "$status" -eq 0 ]
}

# ----------------------------------------------------------------
# boundary conditions
# ----------------------------------------------------------------

@test "validate_url accepts a URL of exactly URL_MAX_LEN characters" {
    local base="https://e.com/"
    local pad=$(( URL_MAX_LEN - ${#base} ))
    local url="${base}$(printf 'a%.0s' $(seq 1 "$pad"))"
    [ "${#url}" -eq "$URL_MAX_LEN" ]
    run validate_url "$url"
    [ "$status" -eq 0 ]

    run validate_url "${url}a"
    [ "$status" -eq 1 ]
}

@test "clamp_concurrency is a no-op exactly at the safe cap" {
    CONCURRENCY_SAFE_CAP=40 UNSAFE=0
    run clamp_concurrency "40"
    [ "$status" -eq 0 ]
    [ "$output" = "40" ]

    run clamp_concurrency "41"
    [ "$status" -eq 1 ]
    [ "$output" = "40" ]
}

@test "siege_max_seconds scales with the duration and always bounds the run" {
    REQUEST_MODE=requests
    run siege_max_seconds
    [ "$output" -eq 900 ]

    # The allowance is duration + 300s but never less than the 900s floor.
    REQUEST_MODE=duration DURATION=30S
    run siege_max_seconds
    [ "$output" -eq 900 ]

    REQUEST_MODE=duration DURATION=30M
    run siege_max_seconds
    [ "$output" -eq 2100 ]

    REQUEST_MODE=duration DURATION=1H
    run siege_max_seconds
    [ "$output" -eq 3900 ]

    # The formula scales linearly once the duration exceeds the floor.
    REQUEST_MODE=duration DURATION=2H
    run siege_max_seconds
    [ "$output" -eq 7500 ]
}

@test "duration_to_seconds treats a bare number as seconds and 0S as zero" {
    [ "$(duration_to_seconds '45')" = "45" ]
    [ "$(duration_to_seconds '0S')" = "0" ]
    [ "$(duration_to_seconds '5m')" = "300" ]
    [ "$(duration_to_seconds '2H')" = "7200" ]
}

@test "normalize_duration rejects modifiers outside S, M and H" {
    run normalize_duration "0S"
    [ "$output" = "0S" ]
    run normalize_duration "10h"
    [ "$output" = "10H" ]
    run normalize_duration "5X"
    [ "$status" -eq 1 ]
    run normalize_duration "1S2"
    [ "$status" -eq 1 ]
    run normalize_duration "10 s"
    [ "$status" -eq 1 ]
}

# ----------------------------------------------------------------
# workload generation
# ----------------------------------------------------------------

@test "random_slug expands to a known noun plus a four digit suffix" {
    local slug i seen=0
    for i in {1..40}; do
        slug=$(random_slug)
        [[ "$slug" =~ ^(missing|gone|void|hidden|ghost|lost|null|empty|blank|quiet)-page-[0-9]{4}$ ]]
        seen=1
    done
    [ "$seen" -eq 1 ]
}

@test "generate_workload collapses duplicates and reports the line count" {
    # WORKLOAD_FILE is derived from WORKDIR before init_workdir runs, so a
    # caller that supplies its own directory must create it first.
    WORKDIR="$BATS_TEST_TMPDIR/wl"
    mkdir -p "$WORKDIR"
    WORKLOAD_SAMPLES=25
    generate_workload static "https://e.com/same" "GET" ""
    [ "$WORKLOAD_LINES" -eq 1 ]
    [ "$(wc -l < "$WORKLOAD_FILE")" -eq 1 ]
    [ "$(cat "$WORKLOAD_FILE")" = "https://e.com/same" ]
    [ ! -e "$WORKDIR/urls-static-raw.txt" ]
}

@test "generate_workload expands random-path templates into unique lines" {
    WORKDIR="$BATS_TEST_TMPDIR/wl"
    mkdir -p "$WORKDIR"
    WORKLOAD_SAMPLES=40
    generate_workload dynamic "https://e.com/{random-path}" "GET" ""
    [ "$WORKLOAD_LINES" -gt 1 ]
    [ "$WORKLOAD_LINES" -eq "$(wc -l < "$WORKLOAD_FILE")" ]
    # Every line is a concrete URL - no template placeholder survives.
    grep -q '{random-path}' "$WORKLOAD_FILE" && false
    # Every line is a concrete URL from the slug word list; grep -c exits 1
    # when nothing matched, hence the explicit "|| true".
    [ "$(grep -cvE '^https://e\.com/(missing|gone|void|hidden|ghost|lost|null|empty|blank|quiet)-page-[0-9]{4}$' \
        "$WORKLOAD_FILE" || true)" -eq 0 ]
}

@test "generate_workload appends the method and body, with a trailing space when empty" {
    WORKDIR="$BATS_TEST_TMPDIR/wl"
    mkdir -p "$WORKDIR"
    WORKLOAD_SAMPLES=1
    generate_workload login "https://e.com/login" "POST" "u=a&p=b"
    [ "$WORKLOAD_LINES" -eq 1 ]
    [ "$(cat "$WORKLOAD_FILE")" = "https://e.com/login POST u=a&p=b" ]

    generate_workload login "https://e.com/login" "POST" ""
    [ "$(cat "$WORKLOAD_FILE")" = "https://e.com/login POST " ]
}

@test "has_known_template detects only the supported placeholders" {
    run has_known_template "https://e.com/{random-id}"
    [ "$status" -eq 0 ]
    run has_known_template "https://e.com/{random-path}"
    [ "$status" -eq 0 ]
    run has_known_template "https://e.com/{file}"
    [ "$status" -eq 0 ]
    run has_known_template "https://e.com/plain"
    [ "$status" -eq 1 ]
}

# ----------------------------------------------------------------
# scenario registry
# ----------------------------------------------------------------

@test "scenario_label maps every known key and passes unknown keys through" {
    [ "$(scenario_label static)" = "Static" ]
    [ "$(scenario_label login)" = "Login Page" ]
    [ "$(scenario_label notfound)" = "Not Found" ]
    [ "$(scenario_label dynamic)" = "Dynamic Page" ]
    [ "$(scenario_label not-a-scenario)" = "not-a-scenario" ]
}

@test "scenario getters read from the matching per-scenario variables" {
    STATIC_URL="https://s.test/" STATIC_METHOD="GET" STATIC_TYPE="html" STATIC_BODY=""
    LOGIN_URL="https://l.test/login" LOGIN_METHOD="POST" LOGIN_TYPE="json" LOGIN_BODY='{"a":1}'
    NOTFOUND_URL="https://n.test/404" NOTFOUND_METHOD="HEAD" NOTFOUND_TYPE="text/html" NOTFOUND_BODY="x"
    DYNAMIC_URL="https://d.test/api" DYNAMIC_METHOD="PATCH" DYNAMIC_TYPE="api" DYNAMIC_BODY="body"

    [ "$(get_scenario_url static)" = "https://s.test/" ]
    [ "$(get_scenario_method static)" = "GET" ]
    [ "$(get_scenario_type static)" = "html" ]
    [ "$(get_scenario_body static)" = "" ]

    [ "$(get_scenario_url login)" = "https://l.test/login" ]
    [ "$(get_scenario_method login)" = "POST" ]
    [ "$(get_scenario_type login)" = "json" ]
    [ "$(get_scenario_body login)" = '{"a":1}' ]

    [ "$(get_scenario_url notfound)" = "https://n.test/404" ]
    [ "$(get_scenario_method notfound)" = "HEAD" ]
    [ "$(get_scenario_type notfound)" = "text/html" ]
    [ "$(get_scenario_body notfound)" = "x" ]

    [ "$(get_scenario_url dynamic)" = "https://d.test/api" ]
    [ "$(get_scenario_method dynamic)" = "PATCH" ]
    [ "$(get_scenario_type dynamic)" = "api" ]
    [ "$(get_scenario_body dynamic)" = "body" ]
}

# ----------------------------------------------------------------
# config sanitising
# ----------------------------------------------------------------

@test "config_int_or_default keeps valid values and repairs the rest" {
    FOO=7; config_int_or_default FOO 5; [ "$FOO" -eq 7 ]
    FOO=0; config_int_or_default FOO 5; [ "$FOO" -eq 0 ]
    FOO=250; config_int_or_default FOO 100; [ "$FOO" -eq 250 ]

    FOO=abc; config_int_or_default FOO 5; [ "$FOO" -eq 5 ]
    FOO=3.5; config_int_or_default FOO 5; [ "$FOO" -eq 5 ]
    FOO=-3; config_int_or_default FOO 5; [ "$FOO" -eq 5 ]
    FOO=; config_int_or_default FOO 5; [ "$FOO" -eq 5 ]
}

@test "config_enum_or_default keeps allow-listed values and repairs the rest" {
    BAR=1M; config_enum_or_default BAR 30S 30S 10M 1M; [ "$BAR" = "1M" ]
    BAR=5X; config_enum_or_default BAR 30S 30S 10M 1M; [ "$BAR" = "30S" ]
    BAR=; config_enum_or_default BAR 1M 30S 10M 1M; [ "$BAR" = "1M" ]
}

@test "load_config repairs a hand-mangled config field by field" {
    cat > "$CONFIG_FILE" <<'EOF'
CONCURRENCY='not-a-number'
REQUEST_MODE='nonsense'
REQUEST_COUNT='-1'
DURATION='99H99'
DELAY='abc'
CONTENT_TYPE='
'
STATIC_TYPE='<script>alert(1)</script>'
STATIC_METHOD='TRACE'
STATIC_BODY='bin
ary'
USER_AGENT='
Mozilla'
EOF

    load_config
    # A non-numeric concurrency falls back to the default 10; the safe cap is
    # applied later, in clamp_concurrency, not while sanitising the config.
    [ "$CONCURRENCY" -eq 10 ]
    [ "$REQUEST_MODE" = "requests" ]
    [ "$REQUEST_COUNT" -ge 0 ]
    [ "$DURATION" = "1M" ]
    [ "$DELAY" -eq 0 ]
    [ -z "$CONTENT_TYPE" ]
    [ "$STATIC_TYPE" != *"<script>"* ]
    [ "$STATIC_METHOD" = "GET" ]
    [ "$STATIC_BODY" != *$'\n'* ]
    [ "$USER_AGENT" != *$'\n'* ]
}

@test "load_config truncates an over-long user agent" {
    printf 'USER_AGENT=%q\n' "$(printf 'a%.0s' {1..400})" > "$CONFIG_FILE"
    load_config
    [ "${#USER_AGENT}" -le 256 ]
}

@test "config_owned_by_self is true for our own file and for a missing file" {
    : > "$CONFIG_FILE"
    run config_owned_by_self
    [ "$status" -eq 0 ]

    CONFIG_FILE="$BATS_TEST_TMPDIR/does-not-exist"
    run config_owned_by_self
    [ "$status" -eq 0 ]

    # A file owned by another user is refused.
    CONFIG_FILE="/etc/shadow"
    run config_owned_by_self
    [ "$status" -eq 1 ]
}

@test "ensure_dirs creates both directories owner-only" {
    rm -rf "$CONFIG_DIR" "$RESULT_DIR"
    run ensure_dirs
    [ "$status" -eq 0 ]
    [ -d "$CONFIG_DIR" ]
    [ -d "$RESULT_DIR" ]
    [ "$(stat -c '%a' "$CONFIG_DIR")" = "700" ]
    [ "$(stat -c '%a' "$RESULT_DIR")" = "700" ]
}

@test "save_config round-trips all four scenarios including special characters" {
    STATIC_URL="https://s.test/a?x=1&y=2" STATIC_METHOD="GET" STATIC_TYPE="html" STATIC_BODY=""
    LOGIN_URL="https://l.test/login" LOGIN_METHOD="POST" LOGIN_TYPE="json" LOGIN_BODY='{"user":"a'"'"'b"}'
    NOTFOUND_URL="https://n.test/404" NOTFOUND_METHOD="GET" NOTFOUND_TYPE="text/html" NOTFOUND_BODY=""
    DYNAMIC_URL="https://d.test/api" DYNAMIC_METHOD="PATCH" DYNAMIC_TYPE="api" DYNAMIC_BODY='a b$c`d'

    save_config
    [ "$(stat -c '%a' "$CONFIG_FILE")" = "600" ]

    unset STATIC_URL LOGIN_URL NOTFOUND_URL DYNAMIC_URL
    unset STATIC_METHOD LOGIN_METHOD NOTFOUND_METHOD DYNAMIC_METHOD
    unset STATIC_BODY LOGIN_BODY NOTFOUND_BODY DYNAMIC_BODY

    load_config
    [ "$STATIC_URL" = "https://s.test/a?x=1&y=2" ]
    [ "$LOGIN_METHOD" = "POST" ]
    [ "$LOGIN_BODY" = '{"user":"a'"'"'b"}' ]
    [ "$DYNAMIC_BODY" = 'a b$c`d' ]
    [ "$NOTFOUND_URL" = "https://n.test/404" ]
}

# ----------------------------------------------------------------
# results analyzer
# ----------------------------------------------------------------

@test "json_value reads siege JSON metrics and ignores non-JSON files" {
    local json="$BATS_TEST_TMPDIR/siege.json"
    cat > "$json" <<'EOF'
{
	"transactions":			12,
	"availability":			99.62,
	"response_time":			0.11,
	"successful_transactions": 11,
	"failed_transactions":		1
}
EOF
    [ "$(json_value transactions "$json")" = "12" ]
    [ "$(json_value availability "$json")" = "99.62" ]
    [ "$(json_value successful_transactions "$json")" = "11" ]
    [ -z "$(json_value not_a_metric "$json")" ]
    [ -z "$(json_value transactions "$BATS_TEST_TMPDIR/missing.log")" ]
}

@test "text_value reads the classic siege text report" {
    local log="$BATS_TEST_TMPDIR/siege.log"
    cat > "$log" <<'EOF'
Transactions:                    7
Availability:                  88.00
Response time:                 0.50
EOF
    [ "$(text_value Transactions "$log")" = "7" ]
    [ "$(text_value Availability "$log")" = "88.00" ]
    [ -z "$(text_value Nope "$log")" ]
}

@test "metric_value prefers JSON, falls back to text, then reports a dash" {
    local json="$BATS_TEST_TMPDIR/m.json"
    local log="$BATS_TEST_TMPDIR/m.log"
    local none="$BATS_TEST_TMPDIR/m.none"
    printf '{"transactions": 12, "availability": 99.62}\n' > "$json"
    printf 'Transactions: 7\nAvailability: 88.00\n' > "$log"
    printf 'no statistics here\n' > "$none"

    [ "$(metric_value transactions Transactions "$json" "$log")" = "12" ]
    [ "$(metric_value transactions Transactions "$none" "$log")" = "7" ]
    [ "$(metric_value availability Availability "$none" "$log")" = "88.00" ]
    [ "$(metric_value transactions Transactions "$none" "$none")" = "-" ]
}

@test "availability_color maps the documented availability bands" {
    GREEN="<green>" YELLOW="<yellow>" RED="<red>"
    [ "$(availability_color 100)" = "<green>" ]
    [ "$(availability_color 99.5)" = "<green>" ]
    [ "$(availability_color 99.49)" = "<yellow>" ]
    [ "$(availability_color 95)" = "<yellow>" ]
    [ "$(availability_color 94.99)" = "<red>" ]
    [ "$(availability_color 0)" = "<red>" ]
}

@test "verdict_text covers every outcome combination" {
    [ "$(verdict_text 0 0 0)" = "No transactions were recorded. The test may not have run." ]
    [ "$(verdict_text 10 10 0)" = "All requests succeeded. The server handled the load without errors." ]
    [ "$(verdict_text 10 0 10)" = "All requests failed. The server may be down or the URL is unreachable." ]
    [ "$(verdict_text 10 0 0)" = "No requests succeeded. The server may be returning errors (4xx/5xx) or the target URL is invalid." ]
    [ "$(verdict_text 10 3 2)" = "Some requests failed (2 out of 10). Check server logs for errors." ]
    [ "$(verdict_text - - -)" = "No transactions were recorded. The test may not have run." ]
    [ "$(verdict_text 10 - -)" = "Test completed." ]
}

@test "analyze_results appends metrics, zero-success warning and a verdict to the log" {
    local log="$BATS_TEST_TMPDIR/analysis.log"
    local json="$BATS_TEST_TMPDIR/analysis.json"
    cat > "$json" <<'EOF'
{"transactions": 5, "availability": 0.00, "response_time": 0.0,
 "successful_transactions": 0, "failed_transactions": 5}
EOF
    : > "$log"
    run analyze_results "$log" "$json" 5 0 5
    [ "$status" -eq 0 ]

    grep -q '^---------------- ANALYSIS ----------------$' "$log"
    grep -q '^Availability      : 0.00%$' "$log"
    grep -q '^Successful        : 0$' "$log"
    grep -q '^Failed            : 5$' "$log"
    grep -q '^WARNING: ZERO successful transactions in 5 attempts' "$log"
    grep -q '^VERDICT           : All requests failed\.' "$log"
    # Analysis replaces the raw banner when TRUNCATE is requested.
    [ "$(grep -c '^VERDICT' "$log")" -eq 1 ]
    # The 4xx explanation is shown on the console; the log keeps the terse
    # WARNING/VERDICT lines only.
    [[ "$output" == *"Tip: Siege counts HTTP 4xx"* ]]
    if grep -q 'Tip: Siege counts HTTP 4xx' "$log"; then false; fi
}

@test "analyze_results reports a partial failure verdict" {
    local log="$BATS_TEST_TMPDIR/partial.log"
    local json="$BATS_TEST_TMPDIR/partial.json"
    printf '{"transactions": 10, "availability": 70.00, "successful_transactions": 7, "failed_transactions": 3}\n' > "$json"
    : > "$log"
    run analyze_results "$log" "$json" 10 7 3
    [ "$status" -eq 0 ]
    grep -q '^VERDICT           : Some requests failed (3 out of 10)\.' "$log"
}

# ----------------------------------------------------------------
# connectivity
# ----------------------------------------------------------------

@test "check_connectivity reports DNS failures for an unresolvable host" {
    run check_connectivity "https://siege-tui-no-such-host.invalid/"
    [ "$status" -eq 1 ]
    [[ "$output" == *"DNS lookup failed"* ]]
}

@test "check_connectivity reports a refused connection on a closed port" {
    run check_connectivity "http://127.0.0.1:1/"
    [ "$status" -eq 1 ]
    [[ "$output" == *"Cannot connect to"* ]]
    [[ "$output" == *":1"* ]]
}

@test "check_connectivity succeeds against a listening socket" {
    command -v nc >/dev/null 2>&1 || skip "nc not available"
    local port
    port=$(for p in {31400..31439}; do
        if ! timeout 1 bash -c "</dev/tcp/127.0.0.1/$p" 2>/dev/null; then printf '%s' "$p"; break; fi
    done)
    [ -n "$port" ] || skip "no free port found"

    nc -l 127.0.0.1 "$port" >/dev/null 2>&1 &
    local listener=$!
    sleep 0.3
    run check_connectivity "http://127.0.0.1:${port}/"
    kill "$listener" 2>/dev/null || true
    [ "$status" -eq 0 ]
}

# ----------------------------------------------------------------
# TUI helpers in plain mode
# ----------------------------------------------------------------

@test "tui_menu prints the options and returns the key of the chosen one" {
    TUI="plain" AUTO_YES=0 NONINTERACTIVE=0
    run tui_menu "Pick" "Choose one:" "a" "alpha" "b" "beta" <<< "2"
    [ "$status" -eq 0 ]
    # The plain backend prints the menu, then the raw key as the last line.
    # The key is echoed back verbatim; the caller maps it to a description.
    [ "${lines[${#lines[@]}-1]}" = "2" ]
    [[ "$output" == *"Choose one:"* ]]
    [[ "$output" == *"a) alpha"* ]]
    [[ "$output" == *"b) beta"* ]]
    [[ "$output" == *"Pick"* ]]
}

@test "tui_input returns typed text, the default on empty input, and 1 on EOF" {
    TUI="plain" AUTO_YES=0 NONINTERACTIVE=0

    run tui_input "Enter URL" "Target URL" "https://default.test" <<< "https://typed.test"
    [ "$status" -eq 0 ]
    [ "${lines[${#lines[@]}-1]}" = "https://typed.test" ]
    [[ "$output" == *"Target URL"* ]]

    run tui_input "Enter URL" "Target URL" "https://default.test" <<< ""
    [ "$status" -eq 0 ]
    [ "${lines[${#lines[@]}-1]}" = "https://default.test" ]

    # Ctrl+D cancels: non-zero return, callers keep the previous value.
    run tui_input "Enter URL" "Target URL" "https://default.test" </dev/null
    [ "$status" -eq 1 ]
}

@test "tui_yesno answers from input and honours AUTO_YES" {
    TUI="plain" NONINTERACTIVE=0
    AUTO_YES=0
    run tui_yesno "Confirm" "Run the test now?" <<< "y"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Run the test now?"* ]]

    run tui_yesno "Confirm" "Run the test now?" <<< "n"
    [ "$status" -eq 1 ]
    run tui_yesno "Confirm" "Run the test now?" <<< ""
    [ "$status" -eq 1 ]
    # Anything that is not exactly y/Y is a no.
    run tui_yesno "Confirm" "Run the test now?" <<< "yes"
    [ "$status" -eq 1 ]

    AUTO_YES=1
    run tui_yesno "Confirm" "Run the test now?" </dev/null
    [ "$status" -eq 0 ]
    [ -z "$output" ]
}

@test "tui_msgbox prints the title and message in AUTO_YES mode" {
    TUI="plain" AUTO_YES=1 NONINTERACTIVE=1
    run tui_msgbox "No Scenarios Configured" "nothing to run"
    [ "$status" -eq 0 ]
    [ "${lines[0]}" = "=== No Scenarios Configured ===" ]
    [ "${lines[1]}" = "nothing to run" ]
}

@test "clear_screen and pause_screen are no-ops in non-interactive mode" {
    NONINTERACTIVE=1
    run clear_screen
    [ -z "$output" ]
    run pause_screen
    [ -z "$output" ]
}

@test "show_banner prints the tool name and version" {
    NONINTERACTIVE=1
    run show_banner
    [ "$status" -eq 0 ]
    [[ "$output" == *"$APP_NAME"* ]]
    [[ "$output" == *"Version $APP_VERSION"* ]]
    [[ "$output" == *"Interactive HTTP Load Testing"* ]]
    # The ASCII-art header is part of the banner.
    [[ "$output" == *"█"* ]]
}

@test "show_configuration lists every scenario row and the load block" {
    ensure_dirs
    STATIC_URL="https://s.test/" STATIC_METHOD="GET" STATIC_TYPE="html"
    LOGIN_URL="https://l.test/" LOGIN_METHOD="POST" LOGIN_TYPE="json"
    NOTFOUND_URL="https://n.test/" NOTFOUND_METHOD="GET" NOTFOUND_TYPE="text/html"
    DYNAMIC_URL="https://d.test/" DYNAMIC_METHOD="GET" DYNAMIC_TYPE="api"
    CONCURRENCY=10 REQUEST_MODE=requests REQUEST_COUNT=100 DELAY=0
    USER_AGENT="UA/2" CONTENT_TYPE="" CUSTOM_HEADERS="" WORKLOAD_SAMPLES=10
    NONINTERACTIVE=1
    run show_configuration
    [ "$status" -eq 0 ]
    [[ "$output" == *"Static"* ]]
    [[ "$output" == *"Login Page"* ]]
    [[ "$output" == *"Not Found"* ]]
    [[ "$output" == *"Dynamic Page"* ]]
    [[ "$output" == *"https://s.test/"* ]]
    [[ "$output" == *"Concurrent users : 10"* ]]
    [[ "$output" == *"Test mode        : requests"* ]]
    [[ "$output" == *"Requests         : 100"* ]]
    [[ "$output" == *"Workload samples : 10"* ]]
    [[ "$output" == *"User-Agent       : UA/2"* ]]
    [[ "$output" == *"Headers          : <none>"* ]]
    [[ "$output" == *"Content-Type     : <default>"* ]]
    [[ "$output" == *"Results          : $RESULT_DIR"* ]]
}

# ----------------------------------------------------------------
# run_scenario (siege mocked, connectivity stubbed)
# ----------------------------------------------------------------

# Emit a siege-shaped JSON report; the counts are overridable so the verdict
# branches can be driven without a real target.
_siege_mock_dir() {
    local dir="$BATS_TEST_TMPDIR/siege-bin"
    mkdir -p "$dir"
    cat > "$dir/siege" <<'MOCK'
#!/usr/bin/env bash
set -u
if [[ "${1:-}" == "--version" ]]; then
    printf 'SIEGE 4.0.7 (forked)\n' >&2
    exit 0
fi
printf '%s\n' "$@" > "${SIEGE_MOCK_ARGV:-/dev/null}"
cat <<JSON
{
	"transactions":			${SIEGE_MOCK_TX:-12},
	"availability":			${SIEGE_MOCK_AVAIL:-100.00},
	"response_time":			${SIEGE_MOCK_RT:-0.01},
	"transaction_rate":		1200.00,
	"throughput":			0.50,
	"concurrency":			9.99,
	"successful_transactions": ${SIEGE_MOCK_OK:-12},
	"failed_transactions":		${SIEGE_MOCK_FAIL:-0},
	"longest_transaction":		0.02,
	"shortest_transaction":	0.01
}
JSON
exit "${SIEGE_MOCK_EXIT:-0}"
MOCK
    chmod +x "$dir/siege"
    printf '%s' "$dir"
}

_scenario_run_fixture() {
    ensure_dirs
    PATH="$(_siege_mock_dir):$PATH"
    # Stub the two pre-flight checks that need a live target; the siege
    # invocation itself is mocked, so the rest of run_scenario is real.
    check_connectivity() { return 0; }
    warn_local_target() { return 0; }
    pause_screen() { return 0; }
    clear_screen() { return 0; }
    export SIEGE_MOCK_ARGV="$BATS_TEST_TMPDIR/siege-argv.txt"

    # WORKLOAD_FILE is derived from WORKDIR, which must already exist.
    WORKDIR="$BATS_TEST_TMPDIR/wd"
    mkdir -p "$WORKDIR"

    STATIC_URL="https://s.test/" STATIC_METHOD="GET" STATIC_TYPE="html" STATIC_BODY=""
    BASE_URL="https://s.test"
    CONCURRENCY=2 REQUEST_MODE=requests REQUEST_COUNT=6 DELAY=0
    USER_AGENT="UA/2" CUSTOM_HEADERS="" CONTENT_TYPE=""
    WORKLOAD_SAMPLES=5 WORKLOAD_PERSIST=0 AUTO_YES=1 NONINTERACTIVE=1 TUI="plain"
    UNSAFE=0 CONCURRENCY_SAFE_CAP=40
    SIEGE_TIMEOUT=10 SIEGE_CONNECT_TIMEOUT=5 SIEGE_SOCKET_TIMEOUT=15 SIEGE_FAILURES_ABORT=25
}

@test "run_scenario writes a 600 log, a JSON sidecar and a success verdict" {
    _scenario_run_fixture
    run run_scenario static
    [ "$status" -eq 0 ]
    [ "$LAST_EXIT_STATUS" -eq 0 ]

    local log
    log=$(find "$RESULT_DIR" -name '*Static*.log' | head -n 1)
    [ -s "$log" ]
    [ "$(stat -c '%a' "$log")" = "600" ]
    grep -q '^VERDICT           : All requests succeeded\.' "$log"
    grep -q '^Availability      : 100.00%$' "$log"

    [ -s "${log%.log}.json" ]
    php -r 'exit(json_decode(file_get_contents($argv[1]), true) === null ? 1 : 0);' "${log%.log}.json"

    # The generated workload was handed to siege together with -j and -f.
    grep -qx -- '-j' "$SIEGE_MOCK_ARGV"
    grep -qx -- '-f' "$SIEGE_MOCK_ARGV"
    grep -q 'urls-static.txt' "$SIEGE_MOCK_ARGV"
    grep -qx -- '-c' "$SIEGE_MOCK_ARGV"
    grep -qx -- '-r' "$SIEGE_MOCK_ARGV"
}

@test "run_scenario maps a killed siege to status 8 and records the safety note" {
    _scenario_run_fixture
    # 137/124 are the signals the outer `timeout --signal=KILL` produces; the
    # mock reproduces one so the mapping is exercised without waiting.
    export SIEGE_MOCK_EXIT=137
    run run_scenario static
    [ "$status" -eq 8 ]
    [[ "$output" == *"Siege was terminated after"* ]]
    [[ "$output" == *"Reduce concurrency"* ]]

    local log
    log=$(find "$RESULT_DIR" -name '*Static*.log' | head -n 1)
    grep -q 'SAFETY: siege terminated after' "$log"
    grep -q '^Siege exit status: 137$' "$log"
}

@test "run_scenario passes a plain non-zero siege exit through unchanged" {
    _scenario_run_fixture
    export SIEGE_MOCK_EXIT=1
    run run_scenario static
    [ "$status" -eq 1 ]
    [[ "$output" == *"Siege exited with status 1"* ]]

    local log
    log=$(find "$RESULT_DIR" -name '*Static*.log' | head -n 1)
    grep -q '^Siege exit status: 1$' "$log"
}

@test "run_scenario flags an all-4xx target whose availability still reads 100%" {
    _scenario_run_fixture
    # siege reports 100% availability and exit 0 for a target that answers
    # 4xx, so only the success count reveals the failure.
    export SIEGE_MOCK_TX=6 SIEGE_MOCK_OK=0 SIEGE_MOCK_FAIL=0 SIEGE_MOCK_AVAIL=100.00
    run run_scenario static
    [[ "$output" == *"recorded no successful transactions"* ]]
    [[ "$output" == *"ZERO successful transactions"* ]]

    local log
    log=$(find "$RESULT_DIR" -name '*Static*.log' | head -n 1)
    grep -q '^Availability      : 100.00%$' "$log"
    grep -q '^Successful        : 0$' "$log"
    grep -q '^VERDICT           : No requests succeeded\.' "$log"
}

@test "run_scenario warns about partial failures without failing the run" {
    _scenario_run_fixture
    export SIEGE_MOCK_TX=10 SIEGE_MOCK_OK=8 SIEGE_MOCK_FAIL=2 SIEGE_MOCK_AVAIL=80.00
    run run_scenario static
    [ "$status" -eq 0 ]
    [[ "$output" == *"completed with 2 failed transaction(s)"* ]]

    local log
    log=$(find "$RESULT_DIR" -name '*Static*.log' | head -n 1)
    grep -q '^VERDICT           : Some requests failed (2 out of 10)\.' "$log"
}

@test "run_all_scenarios reports an empty registry instead of running nothing" {
    ensure_dirs
    NONINTERACTIVE=1 AUTO_YES=1 TUI="plain"
    run run_all_scenarios
    [ "$status" -eq 0 ]
    [[ "$output" == *"=== No Scenarios Configured ==="* ]]
}

@test "unique_logfile falls back to a pid suffix after 99 collisions" {
    local dir="$BATS_TEST_TMPDIR/collide"
    mkdir -p "$dir"
    # Occupy run.log and run-2.log .. run-100.log: unique_logfile walks
    # "<base>-<n>.log" before giving up and appending the pid.
    local i
    : > "$dir/run.log"
    for i in {2..100}; do
        : > "$dir/run-${i}.log"
    done
    run unique_logfile "$dir/run.log"
    [ "$status" -eq 0 ]
    [ "$output" = "$dir/run-$$.log" ]
}

# ----------------------------------------------------------------
# CLI exit-code contract
# ----------------------------------------------------------------

@test "-V and --version print the version and exit 0" {
    run bash "$SIEGE_TUI_SCRIPT" --version
    [ "$status" -eq 0 ]
    [[ "$output" == *"$APP_VERSION"* ]]
    run bash "$SIEGE_TUI_SCRIPT" -V
    [ "$status" -eq 0 ]
    [[ "$output" == *"$APP_VERSION"* ]]
}

@test "-h prints usage and exits 0" {
    run bash "$SIEGE_TUI_SCRIPT" -h
    [ "$status" -eq 0 ]
    [[ "$output" == *"--run"* ]]
    [[ "$output" == *"--unsafe"* ]]
}

@test "--run without a scenario name is a usage error" {
    run bash "$SIEGE_TUI_SCRIPT" --run
    [ "$status" -eq 2 ]
}

@test "an unconfigured scenario maps to exit code 3" {
    run bash "$SIEGE_TUI_SCRIPT" --run static --yes
    [ "$status" -eq 3 ]
    [[ "$output" == *"Scenario Not Configured"* ]]
}

@test "an unresolvable target maps to exit code 4" {
    _seed_config
    run bash "$SIEGE_TUI_SCRIPT" --run static --yes
    [ "$status" -eq 4 ]
    [[ "$output" == *"DNS lookup failed"* ]]
    [[ "$output" == *"fixture.test"* ]]
}

@test "a refused connection maps to exit code 4" {
    _seed_config
    sed -i "s|http://fixture.test/index.html|http://127.0.0.1:1/index.html|" "$CONFIG_FILE"
    run bash "$SIEGE_TUI_SCRIPT" --run static --yes
    [ "$status" -eq 4 ]
    [[ "$output" == *"Cannot connect to"* ]]
}

@test "usage documents the scenarios, the safety envelope and the exit codes" {
    run usage
    [ "$status" -eq 0 ]
    [[ "$output" == *"$APP_NAME v$APP_VERSION"* ]]
    [[ "$output" == *"static | login | notfound | dynamic"* ]]
    [[ "$output" == *"Safety envelope (default ON)"* ]]
    [[ "$output" == *"exits with"* ]]
    [[ "$output" == *"0  siege completed successfully"* ]]
    [[ "$output" == *"2  usage error"* ]]
    [[ "$output" == *"3  scenario not configured"* ]]
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
