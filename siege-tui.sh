#!/usr/bin/env bash

# ================================================================
# Siege Interactive Load Testing TUI (Production Edition)
# ================================================================
#
# Author: M.Noermoehammad
#
# Interactive load-testing frontend for Siege.
#
# Test scenarios:
#   1. Static
#   2. Login Page
#   3. Not Found Page
#   4. Dynamic Page
#
# Features:
#   - Interactive TUI (whiptail / dialog / plain fallback)
#   - Per-scenario request profiles: URL, resource type, HTTP
#     method and body (siege inline "URL METHOD body" syntax)
#   - URL templates for realistic workloads:
#       {random-id}    -> random integer 1..9999
#       {random-path}  -> random non-existent slug
#       {file}         -> common asset filename (html/css/js/...)
#     Expanded into a deduplicated URL file run with -f/-i so each
#     test exercises varied URLs instead of one literal address
#   - Configurable concurrency, request count or duration, delay
#   - Custom headers (semicolon-separated) and User-Agent passed
#     to siege via -H / -A
#   - Results analyzer: parses siege --json-output stats with a
#     plain-text fallback; availability/error thresholds colorized;
#     integrity checks (counter agreement, zero-success detection,
#     volume-overrun warning for redirect-heavy targets); analysis
#     appended to every log file
#   - Secret hygiene: request bodies are masked and sensitive header
#     values (Authorization/Cookie) redacted before anything is
#     persisted to disk
#   - Persistent configuration (atomic writes, mode 0600, ownership
#     check and per-field type validation on load)
#   - Timestamped result logs in ~/siege-results/ (collision-safe,
#     exclusive reservation)
#   - Headless automation:  ./siege-tui.sh --run <scenario> --yes
#     (--yes is mandatory with --run so CI never mistakes a skipped
#     confirmation for a successful run)
#   - Hardened: no eval execution, strict input validation,
#     mktemp workspace with signal-safe cleanup
#
# Reproducibility notes (verified against siege 4.0.7):
#   - siege counts each followed redirect hop as one additional
#     transaction; total = users x reps x urls x (1 + hops)
#   - HTTP 4xx responses are counted as transactions but appear in
#     NEITHER successful_transactions NOR failed_transactions while
#     availability still reads 100% -- hence the zero-success check
#
# Usage:
#   ./siege-tui.sh                     interactive TUI
#   ./siege-tui.sh --run static --yes  headless single scenario
#   ./siege-tui.sh --help              usage
#   ./siege-tui.sh --version           version
#
# Requirements:
#   - Bash 4+
#   - siege >= 4.x recommended (-H and --json-output support)
#
# Optional:
#   - whiptail
#   - dialog
#
# ================================================================

set -u
set -o pipefail

# ----------------------------------------------------------------
# Application information
# ----------------------------------------------------------------

APP_NAME="Siege Load Testing TUI"
APP_VERSION="2.2.0"

CONFIG_DIR="${HOME}/.config/siege-tui"
CONFIG_FILE="${CONFIG_DIR}/config"
RESULT_DIR="${HOME}/siege-results"

WORKDIR=""
LAST_EXIT_STATUS=0

NONINTERACTIVE=0
AUTO_YES=0
RUN_SCENARIO=""

# Safety envelope (v2.2.0): caps that keep a run from exhausting the
# host's socket/fd table and saturating a weakly-connected target such as
# a DDEV-in-VirtualBox setup. Overriding them requires --unsafe.
#   - UNSAFE=1 opts out of the envelope (deliberate, documented).
#   - CONCURRENCY_SAFE_CAP clamps -c unless --unsafe is given.
UNSAFE=0
CONCURRENCY_SAFE_CAP=40
SIEGE_TIMEOUT=10
SIEGE_CONNECT_TIMEOUT=5
SIEGE_SOCKET_TIMEOUT=15
SIEGE_FAILURES_ABORT=25

SIEGE_MAJOR=0
SIEGE_VERSION_LINE=""

# Populated by analyze_results for post-run verdict logic.
ANALYZED_TRANSACTIONS="-"
ANALYZED_SUCCESS="-"
ANALYZED_FAILED="-"

CONCURRENCY_SOFT_CAP=200
DELAY_HARD_CAP=3600
SAMPLES_MIN=1
SAMPLES_MAX=1000
URL_MAX_LEN=2048
BODY_MAX_LEN=8192

SCENARIOS=(static login notfound dynamic)

ASSET_FILES=(
    index.html
    styles.css
    app.js
    logo.jpg
    photo.png
    photo.webp
    favicon.ico
    robots.txt
)

# ----------------------------------------------------------------
# Terminal colors
# ----------------------------------------------------------------

if [[ -t 1 ]]; then
    RESET='\033[0m'
    BOLD='\033[1m'
    DIM='\033[2m'

    RED='\033[31m'
    GREEN='\033[32m'
    YELLOW='\033[33m'
    CYAN='\033[36m'
    WHITE='\033[37m'
else
    RESET=''
    BOLD=''
    DIM=''
    RED=''
    GREEN=''
    YELLOW=''
    CYAN=''
    WHITE=''
fi

# ----------------------------------------------------------------
# Default configuration
# ----------------------------------------------------------------

STATIC_URL=""
STATIC_TYPE="html"
STATIC_METHOD="GET"
STATIC_BODY=""

LOGIN_URL=""
LOGIN_TYPE="html"
LOGIN_METHOD="GET"
LOGIN_BODY=""

NOTFOUND_URL=""
NOTFOUND_TYPE="html"
NOTFOUND_METHOD="GET"
NOTFOUND_BODY=""

DYNAMIC_URL=""
DYNAMIC_TYPE="php"
DYNAMIC_METHOD="GET"
DYNAMIC_BODY=""

CONCURRENCY="10"
REQUEST_MODE="requests"
REQUEST_COUNT="100"
DURATION="1M"
DELAY="0"
WORKLOAD_SAMPLES="25"

USER_AGENT="Siege-TUI/${APP_VERSION}"
CUSTOM_HEADERS=""
CONTENT_TYPE=""

HEADER_ARGS=()

WORKLOAD_FILE=""
WORKLOAD_LINES=0

SIEGE_CMD=()
SIEGE_DISPLAY=""

# ----------------------------------------------------------------
# Utility functions
# ----------------------------------------------------------------

die() {
    local message="$1"
    local code="${2:-1}"
    printf '%s\n' "siege-tui: ${message}" >&2
    exit "$code"
}

clear_screen() {
    (( NONINTERACTIVE )) && return 0
    printf '\033[2J\033[H'
}

pause_screen() {
    (( NONINTERACTIVE )) && return 0
    printf '\n'
    read -r -p "Press ENTER to continue..." _
}

print_line() {
    printf '%*s\n' "${1:-70}" '' | tr ' ' '='
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

trim() {
    local value="$1"
    value="${value#"${value%%[![:space:]]*}"}"
    value="${value%"${value##*[![:space:]]}"}"
    printf '%s' "$value"
}

sanitize_filename() {
    local value="$1"
    printf '%s' "$value" | tr -c '[:alnum:]._' '-' | tr -s '-'
}

# Returns a non-reversible display form for a request body so that
# credentials never reach persistent artifacts (logs, reports).
masked_body_note() {
    local body="$1"

    if [[ -z "$body" ]]; then
        return 0
    fi

    printf '<masked, %d bytes>' "${#body}"
}

# Redacts the value of a single "Name: value" header when the name is
# credential-bearing; other headers pass through untouched.
redact_header_value() {
    local header="$1"
    local name="${header%%:*}"

    case "${name,,}" in
        authorization|proxy-authorization|cookie|set-cookie)
            printf '%s: <redacted>' "$name" ;;
        *)
            printf '%s' "$header" ;;
    esac
}

# Redacts the values of credential-bearing headers before persisting
# a header string. Non-sensitive headers pass through unchanged.
redact_sensitive_headers() {
    local raw="$1"
    local -a segments=()
    local item out="" first=1

    [[ -n "$raw" ]] || return 0

    mapfile -t segments < <(printf '%s' "$raw" | tr ';' '\n')

    for item in "${segments[@]}"; do
        item=$(trim "$item")
        [[ -n "$item" ]] || continue
        (( first )) || out+="; "
        out+="$(redact_header_value "$item")"
        first=0
    done

    printf '%s' "$out"
}

# True when $1 is a non-negative integer or decimal number.
is_number() {
    [[ "${1:-}" =~ ^[0-9]+([.][0-9]+)?$ ]]
}

ensure_dirs() {
    [[ -n "${HOME:-}" ]] || die "HOME is not set; cannot resolve config/result paths." 1

    mkdir -p "$CONFIG_DIR" || die "Cannot create config directory: ${CONFIG_DIR}" 1
    chmod 700 "$CONFIG_DIR" 2>/dev/null || true

    mkdir -p "$RESULT_DIR" || die "Cannot create result directory: ${RESULT_DIR}" 1
    chmod 700 "$RESULT_DIR" 2>/dev/null || true
}

init_workdir() {
    WORKDIR=$(mktemp -d "${TMPDIR:-/tmp}/siege-tui.XXXXXX") \
        || die "Cannot create temporary workspace." 1
}

unique_logfile() {
    local base="$1"
    local candidate="$base"
    local n=1

    while :; do
        # Exclusive creation closes the check/use race between two
        # concurrent runs; the empty reservation is truncated by the
        # caller's redirect before any content is written.
        if ( set -o noclobber; : > "$candidate" ) 2>/dev/null; then
            printf '%s' "$candidate"
            return 0
        fi

        if (( n > 99 )); then
            candidate="${base%.log}-$$.log"
            printf '%s' "$candidate"
            return 0
        fi

        n=$((n + 1))
        candidate="${base%.log}-${n}.log"
    done
}

# ----------------------------------------------------------------
# Input validation
# ----------------------------------------------------------------

validate_url() {
    local url="$1"

    [[ -n "$url" ]] || return 1
    (( ${#url} <= URL_MAX_LEN )) || return 1

    # Scheme required; reject whitespace/control characters, allow other printables.
    [[ "$url" =~ ^https?://[^[:space:][:cntrl:]]+$ ]] || return 1

    # Defense in depth: no shell/quote metacharacters in targets.
    case "$url" in
        *[\"\'\`]*) return 1 ;;
    esac

    return 0
}

# Returns the effective concurrency after applying the safety envelope.
# Unless --unsafe, the returned value never exceeds CONCURRENCY_SAFE_CAP;
# with --unsafe the caller's chosen value is passed through unchanged.
clamp_concurrency() {
    local requested="$1"

    if (( UNSAFE )); then
        printf '%s' "$requested"
        return 0
    fi

    if (( requested > CONCURRENCY_SAFE_CAP )); then
        printf '%s' "$CONCURRENCY_SAFE_CAP"
        return 1
    fi

    printf '%s' "$requested"
    return 0
}

# Converts a siege duration (30S / 5M / 1H / bare) to whole seconds.
duration_to_seconds() {
    local v="${1^^}"
    local n mod
    n=${v%[SMH]}
    mod=${v: -1}
    case "$mod" in
        S) printf '%s' "$n" ;;
        M) printf '%s' $(( n * 60 )) ;;
        H) printf '%s' $(( n * 3600 )) ;;
        *) printf '%s' "$n" ;;
    esac
}

# Computes an absolute upper bound (seconds) for the whole siege run so the
# wrapper can guarantee termination even if siege itself hangs on sockets.
# Always >= a small floor and never allowed to be unset/zero.
siege_max_seconds() {
    local cap=900

    if [[ "$REQUEST_MODE" == "duration" ]] && [[ -n "${DURATION:-}" ]]; then
        local d
        d=$(duration_to_seconds "$DURATION")
        # Allow the request-mode tolerance: duration plus a generous slack.
        cap=$(( d > 900 ? d + 300 : 900 ))
    fi

    printf '%s' "$cap"
}

# Prints a warning when the target resolves to a loopback, private, or VM
# NAT address. Such targets (DDEV hostname -> 127.0.0.1 / VirtualBox NAT)
# are especially prone to host-side socket/CPU saturation under siege.
warn_local_target() {
    local host="$1"
    local resolved
    resolved=$(getent hosts "$host" 2>/dev/null | head -n1) || true
    local addr="${resolved%% *}"
    local addr4="${addr:-}"

    if [[ "$addr4" == "127.0.0.1" || "$addr4" == "::1" || "$addr4" == "::ffff:127.0.0.1" ]]; then
        echo "WARNING: \"${host}\" resolves to the local loopback (${addr4})."
        echo "  This is typical of DDEV or a VM port-forward on the host."
        echo "  High concurrency against a loopback/VM route can saturate the"
        echo "  host's socket table and make the machine appear frozen."
        echo "  Keep concurrency moderate and set a delay in safe mode."
        return 0
    fi

    if [[ "$addr4" =~ ^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.) ]]; then
        echo "NOTE: \"${host}\" resolves to a private address (${addr4})."
        echo "  If this is a VM/NAT target, the same saturation warning applies."
    fi
}

# Pre-flight connectivity check. Tests DNS resolution and TCP
# reachability before launching the full siege run. Catches
# "Network is unreachable" conditions early with actionable
# diagnostics instead of letting siege retry and fail silently.
check_connectivity() {
    local url="$1"
    local authority="${url#*://}"
    authority="${authority%%/*}"
    authority="${authority%%\?*}"

    local host="${authority%%:*}"
    local port

    if [[ "$authority" == *:* ]]; then
        port="${authority##*:}"
    elif [[ "$url" == https://* ]]; then
        port="443"
    else
        port="80"
    fi

    warn_local_target "$host"

    local resolved
    resolved=$(getent hosts "$host" 2>/dev/null | head -n1) || true
    if [[ -z "$resolved" ]]; then
        echo "DNS lookup failed: cannot resolve \"${host}\"."
        echo "Verify the URL and DNS configuration."
        return 1
    fi

    if ! timeout 5 bash -c "echo >/dev/tcp/${host}/${port}" 2>/dev/null; then
        local addr="${resolved%% *}"
        echo "Cannot connect to ${host} (${addr}:${port})."
        echo "The target is unreachable. Possible causes:"
        echo "  - IPv6 routing not configured (try an IPv4 address)"
        echo "  - Firewall blocking outbound connections"
        echo "  - Host is down or on a private network"
        return 1
    fi

    return 0
}

normalize_duration() {
    local value="${1^^}"

    [[ "$value" =~ ^[0-9]+[SMH]?$ ]] || return 1
    [[ "$value" =~ ^[0-9]+$ ]] && value="${value}S"

    printf '%s' "$value"
}

validate_method() {
    case "$1" in
        GET|POST|PUT|PATCH|DELETE) return 0 ;;
        *) return 1 ;;
    esac
}

method_uses_body() {
    case "$1" in
        GET) return 1 ;;
        *) return 0 ;;
    esac
}

validate_body() {
    local body="$1"

    (( ${#body} <= BODY_MAX_LEN )) || return 1
    ! [[ "$body" =~ [[:cntrl:]] ]]
}

validate_header() {
    local header="$1"

    [[ "$header" =~ ^[^[:cntrl:]:]{1,256}:[[:space:]]*[^[:cntrl:]]{1,2048}$ ]]
}

parse_custom_headers() {
    local raw="$1"
    local item trimmed
    local -a parts=()
    local -a parsed=()

    # Split on semicolons only; header values may contain spaces.
    if [[ -n "$raw" ]]; then
        IFS=';' read -r -a parts <<< "$raw"
    fi

    for item in "${parts[@]}"; do
        trimmed=$(trim "$item")
        [[ -n "$trimmed" ]] || continue
        validate_header "$trimmed" || return 1
        parsed+=("-H" "$trimmed")
    done

    # Commit atomically: a single invalid header must not leave a
    # partial set of headers behind for the caller to execute.
    HEADER_ARGS=("${parsed[@]}")

    return 0
}

# ----------------------------------------------------------------
# Dependency check
# ----------------------------------------------------------------

# Populates SIEGE_VERSION_LINE / SIEGE_MAJOR as globals; prints
# nothing so callers can invoke it directly without losing state to a
# command-substitution subshell.
detect_siege_version() {
    SIEGE_MAJOR=0
    SIEGE_VERSION_LINE=""

    local line
    # siege prints its version banner to stderr, not stdout; both
    # streams must be captured. On first run the banner is preceded
    # by a "New configuration template" notice, so select the first
    # line that actually carries a version number.
    line=$(siege --version 2>&1 | grep -m1 -E '[0-9]+(\.[0-9]+)+')
    SIEGE_VERSION_LINE="${line:-unknown version}"

    if [[ "$line" =~ ([0-9]+)(\.[0-9]+)* ]]; then
        SIEGE_MAJOR=${BASH_REMATCH[1]}
    fi
}

check_dependencies() {

    clear_screen

    echo
    echo -e "${BOLD}${CYAN}Dependency Check${RESET}"
    print_line 70
    echo

    if command_exists siege; then
        detect_siege_version
        local siege_line="${SIEGE_VERSION_LINE:-unknown version}"

        echo -e "  ${GREEN}✓${RESET} Siege (${siege_line})"

        if (( SIEGE_MAJOR > 0 && SIEGE_MAJOR < 4 )); then
            echo -e "  ${YELLOW}!${RESET} Siege < 4.x detected."
            echo "    JSON results and custom headers may be unavailable;"
            echo "    the analyzer will fall back to text parsing."
        fi
    else
        echo -e "  ${RED}✗${RESET} Siege is not installed."
        echo

        echo "Install Siege using your distribution package manager."
        echo
        echo "Debian / Ubuntu:"
        echo "  sudo apt install siege"
        echo
        echo "Fedora:"
        echo "  sudo dnf install siege"
        echo
        echo "Arch:"
        echo "  sudo pacman -S siege"
        echo

        pause_screen
        die "Siege is required." 1
    fi

    if command_exists whiptail; then
        echo -e "  ${GREEN}✓${RESET} whiptail"
    elif command_exists dialog; then
        echo -e "  ${GREEN}✓${RESET} dialog"
    else
        echo -e "  ${YELLOW}!${RESET} whiptail/dialog not found"
        echo "    Plain terminal interface will be used."
    fi

    if (( NONINTERACTIVE == 0 )) && ! [[ -t 0 && -t 1 ]]; then
        echo -e "  ${YELLOW}!${RESET} No interactive terminal attached."
        echo "    Interactive menus require a TTY."
        echo "    For unattended runs use: $0 --run <scenario> --yes"
        echo
        pause_screen
        die "Interactive terminal required." 1
    fi

    echo
    pause_screen
}

silent_dependency_check() {
    command_exists siege || die "Siege is not installed." 1
    detect_siege_version >/dev/null
}

# ----------------------------------------------------------------
# Configuration persistence
# ----------------------------------------------------------------

save_config() {

    local tmp_file="${CONFIG_FILE}.tmp.$$"

    cat > "$tmp_file" <<CFG_EOF || die "Cannot write configuration: ${tmp_file}" 1
STATIC_URL=$(printf '%q' "$STATIC_URL")
STATIC_TYPE=$(printf '%q' "$STATIC_TYPE")
STATIC_METHOD=$(printf '%q' "$STATIC_METHOD")
STATIC_BODY=$(printf '%q' "$STATIC_BODY")

LOGIN_URL=$(printf '%q' "$LOGIN_URL")
LOGIN_TYPE=$(printf '%q' "$LOGIN_TYPE")
LOGIN_METHOD=$(printf '%q' "$LOGIN_METHOD")
LOGIN_BODY=$(printf '%q' "$LOGIN_BODY")

NOTFOUND_URL=$(printf '%q' "$NOTFOUND_URL")
NOTFOUND_TYPE=$(printf '%q' "$NOTFOUND_TYPE")
NOTFOUND_METHOD=$(printf '%q' "$NOTFOUND_METHOD")
NOTFOUND_BODY=$(printf '%q' "$NOTFOUND_BODY")

DYNAMIC_URL=$(printf '%q' "$DYNAMIC_URL")
DYNAMIC_TYPE=$(printf '%q' "$DYNAMIC_TYPE")
DYNAMIC_METHOD=$(printf '%q' "$DYNAMIC_METHOD")
DYNAMIC_BODY=$(printf '%q' "$DYNAMIC_BODY")

CONCURRENCY=$(printf '%q' "$CONCURRENCY")
REQUEST_MODE=$(printf '%q' "$REQUEST_MODE")
REQUEST_COUNT=$(printf '%q' "$REQUEST_COUNT")
DURATION=$(printf '%q' "$DURATION")
DELAY=$(printf '%q' "$DELAY")
WORKLOAD_SAMPLES=$(printf '%q' "$WORKLOAD_SAMPLES")

USER_AGENT=$(printf '%q' "$USER_AGENT")
CUSTOM_HEADERS=$(printf '%q' "$CUSTOM_HEADERS")
CONTENT_TYPE=$(printf '%q' "$CONTENT_TYPE")
CFG_EOF

    mv -f "$tmp_file" "$CONFIG_FILE" || die "Cannot persist configuration." 1
    chmod 600 "$CONFIG_FILE" 2>/dev/null || true
}

# True when CONFIG_FILE is owned by the current user. A foreign-owned
# configuration is refused outright (tamper guard, ssh-style).
config_owned_by_self() {
    local owner me

    owner=$(stat -c '%U' "$CONFIG_FILE" 2>/dev/null || true)
    me=$(id -un 2>/dev/null || true)

    [[ -z "$owner" || -z "$me" || "$owner" == "$me" ]]
}

# Resets $1 to $2 unless it currently holds a non-negative integer.
config_int_or_default() {
    is_number "${!1:-}" && [[ "${!1}" =~ ^[0-9]+$ ]] \
        || printf -v "$1" '%s' "$2"
}

# Resets $1 to $2 unless its value appears in the remaining arguments.
config_enum_or_default() {
    local name="$1" default="$2"
    shift 2

    local current="${!name:-}" option
    for option in "$@"; do
        [[ "$current" == "$option" ]] && return 0
    done

    printf -v "$name" '%s' "$default"
}

# Type-checks every persisted field after sourcing; corrupt, truncated,
# or hand-mangled files degrade to safe defaults instead of feeding
# unvalidated strings into siege's argument list.
sanitize_loaded_config() {

    local scenario url_var method_var body_var type_var
    for scenario in STATIC LOGIN NOTFOUND DYNAMIC; do
        url_var="${scenario}_URL"
        method_var="${scenario}_METHOD"
        body_var="${scenario}_BODY"
        type_var="${scenario}_TYPE"

        local stored_url="${!url_var:-}"
        if [[ -n "$stored_url" ]] && ! validate_url "$stored_url"; then
            printf -v "$url_var" '%s' ""
        fi

        config_enum_or_default "$method_var" GET GET POST PUT PATCH DELETE

        local stored_body="${!body_var:-}"
        if (( ${#stored_body} > BODY_MAX_LEN )) || [[ "$stored_body" =~ [[:cntrl:]] ]]; then
            printf -v "$body_var" '%s' ""
        fi

        local stored_type default_type="html"
        [[ "$scenario" == "DYNAMIC" ]] && default_type="php"
        stored_type=$(sanitize_filename "${!type_var:-$default_type}")
        stored_type="${stored_type:0:16}"
        [[ -n "$stored_type" ]] || stored_type="$default_type"
        printf -v "$type_var" '%s' "$stored_type"
    done

    config_int_or_default CONCURRENCY "10"
    config_int_or_default REQUEST_COUNT "100"
    config_int_or_default WORKLOAD_SAMPLES "25"

    is_number "$DELAY" || DELAY="0"

    config_enum_or_default REQUEST_MODE requests requests duration

    local normalized_duration
    normalized_duration=$(normalize_duration "${DURATION:-}") \
        || normalized_duration="1M"
    DURATION="$normalized_duration"

    # Strip control characters and clamp free-text fields.
    USER_AGENT=$(printf '%s' "${USER_AGENT:-}" | tr -d '\000-\010\013\014\016-\037')
    (( ${#USER_AGENT} <= 256 )) || USER_AGENT="${USER_AGENT:0:256}"

    CONTENT_TYPE=${CONTENT_TYPE:-}
    [[ "$CONTENT_TYPE" =~ ^[^[:cntrl:][:space:]]{1,128}$ ]] || CONTENT_TYPE=""

    CUSTOM_HEADERS=${CUSTOM_HEADERS:-}
    if [[ -n "$CUSTOM_HEADERS" ]]; then
        local part headers_ok=1
        while IFS= read -r part; do
            part=$(trim "$part")
            [[ -n "$part" ]] || continue
            validate_header "$part" || { headers_ok=0; break; }
        done <<< "$(printf '%s' "$CUSTOM_HEADERS" | tr ';' '\n')"
        (( headers_ok )) || CUSTOM_HEADERS=""
    fi
}

load_config() {

    if [[ -f "$CONFIG_FILE" ]]; then
        if config_owned_by_self; then
            # shellcheck disable=SC1090
            source "$CONFIG_FILE"
            unset HTTP_METHOD
        else
            printf 'siege-tui: ignoring %s: not owned by current user.\n' "$CONFIG_FILE" >&2
        fi
    fi

    sanitize_loaded_config
}

# ----------------------------------------------------------------
# TUI backend
# ----------------------------------------------------------------

TUI="plain"

if command_exists whiptail; then
    TUI="whiptail"
elif command_exists dialog; then
    TUI="dialog"
fi

tui_msgbox() {

    local title="$1"
    local message="$2"

    if (( AUTO_YES )) ; then
        printf '=== %s ===\n%s\n\n' "$title" "$message"
        return 0
    fi

    if [[ "$TUI" == "whiptail" || "$TUI" == "dialog" ]]; then
        "$TUI" \
            --title "$title" \
            --msgbox "$message" \
            18 76
    else
        clear_screen
        echo
        echo -e "${BOLD}${CYAN}${title}${RESET}"
        print_line 76
        echo
        printf '%b\n' "$message"
        echo
        pause_screen
    fi
}

tui_yesno() {

    local title="$1"
    local message="$2"

    (( AUTO_YES )) && return 0

    if [[ "$TUI" == "whiptail" || "$TUI" == "dialog" ]]; then
        "$TUI" \
            --title "$title" \
            --yesno "$message" \
            15 76
    else
        echo
        echo "$message"
        echo
        local answer
        read -r -p "Continue? [y/N]: " answer
        [[ "$answer" =~ ^[Yy]$ ]]
    fi
}

# Prints the entered value on stdout. Returns non-zero when the
# user cancels (Cancel button, ESC, or Ctrl+D); callers must treat
# a cancelled prompt as "keep previous configuration".
tui_input() {

    local title="$1"
    local prompt="$2"
    local default="$3"

    local result=""
    local rc=0

    if [[ "$TUI" == "whiptail" || "$TUI" == "dialog" ]]; then
        result=$(
            "$TUI" \
                --title "$title" \
                --inputbox "$prompt" \
                12 76 \
                "$default" \
                3>&1 1>&2 2>&3
        ) || rc=$?
    else
        echo
        echo "$prompt"
        echo
        read -r -p "[${default}] > " result || rc=1
        if (( rc == 0 )) && [[ -z "$result" ]]; then
            result="$default"
        fi
    fi

    if (( rc != 0 )); then
        return 1
    fi

    printf '%s' "$result"
}

# Generic menu. Arguments after height are key/description pairs.
# Prints the selected key on stdout; returns non-zero on cancel.
tui_menu() {

    local title="$1"
    local prompt="$2"
    shift 2

    local -a entries=("$@")

    local count=$(( ${#entries[@]} / 2 ))
    local listheight=$(( count > 3 ? count : 3 ))
    local boxheight=$(( listheight + 7 ))

    if [[ "$TUI" == "whiptail" || "$TUI" == "dialog" ]]; then
        "$TUI" \
            --title "$title" \
            --menu "$prompt" \
            "$boxheight" 78 "$listheight" \
            "${entries[@]}" \
            3>&1 1>&2 2>&3
    else
        clear_screen
        echo
        echo -e "${BOLD}${title}${RESET}"
        print_line 70
        echo "  $prompt"
        echo

        local i
        for (( i = 0; i < ${#entries[@]}; i += 2 )); do
            printf '  %s) %s\n' "${entries[$i]}" "${entries[$((i + 1))]}"
        done

        echo
        local choice
        # EOF or empty input signals cancel; callers treat a non-zero
        # return as "keep previous configuration".
        read -r -p "Select an option: " choice || return 1
        [[ -n "$choice" ]] || return 1

        printf '%s' "$choice"
    fi
}

# ----------------------------------------------------------------
# Banner
# ----------------------------------------------------------------

show_banner() {

    clear_screen

    echo
    echo -e "${BOLD}${CYAN}"
    echo "  ███████╗██╗███████╗ ██████╗ ███████╗"
    echo "  ██╔════╝██║██╔════╝██╔════╝ ██╔════╝"
    echo "  ███████╗██║█████╗  ██║  ███╗█████╗"
    echo "  ╚════██║██║██╔══╝  ██║   ██║██╔══╝"
    echo "  ███████║██║███████╗╚██████╔╝███████╗"
    echo "  ╚══════╝╚═╝╚══════╝ ╚═════╝ ╚══════╝"
    echo -e "${RESET}"

    echo -e "  ${BOLD}${WHITE}${APP_NAME}${RESET}"
    echo -e "  ${DIM}Version ${APP_VERSION} • Interactive HTTP Load Testing${RESET}"
    echo

    print_line 70

    echo
}

# ----------------------------------------------------------------
# Scenario registry helpers
# ----------------------------------------------------------------

scenario_label() {
    case "$1" in
        static)   printf '%s' "Static" ;;
        login)    printf '%s' "Login Page" ;;
        notfound) printf '%s' "Not Found" ;;
        dynamic)  printf '%s' "Dynamic Page" ;;
        *)        printf '%s' "$1" ;;
    esac
}

get_scenario_url() {
    case "$1" in
        static)   printf '%s' "$STATIC_URL" ;;
        login)    printf '%s' "$LOGIN_URL" ;;
        notfound) printf '%s' "$NOTFOUND_URL" ;;
        dynamic)  printf '%s' "$DYNAMIC_URL" ;;
    esac
}

get_scenario_type() {
    case "$1" in
        static)   printf '%s' "$STATIC_TYPE" ;;
        login)    printf '%s' "$LOGIN_TYPE" ;;
        notfound) printf '%s' "$NOTFOUND_TYPE" ;;
        dynamic)  printf '%s' "$DYNAMIC_TYPE" ;;
    esac
}

get_scenario_method() {
    case "$1" in
        static)   printf '%s' "$STATIC_METHOD" ;;
        login)    printf '%s' "$LOGIN_METHOD" ;;
        notfound) printf '%s' "$NOTFOUND_METHOD" ;;
        dynamic)  printf '%s' "$DYNAMIC_METHOD" ;;
    esac
}

get_scenario_body() {
    case "$1" in
        static)   printf '%s' "$STATIC_BODY" ;;
        login)    printf '%s' "$LOGIN_BODY" ;;
        notfound) printf '%s' "$NOTFOUND_BODY" ;;
        dynamic)  printf '%s' "$DYNAMIC_BODY" ;;
    esac
}

# ----------------------------------------------------------------
# Workload engine (template expansion -> URL file)
# ----------------------------------------------------------------

random_slug() {
    local words=(missing gone void hidden ghost lost null empty blank quiet)
    local word="${words[RANDOM % ${#words[@]}]}"
    printf '%s-page-%04d' "$word" $(( RANDOM % 10000 ))
}

has_known_template() {
    local url="$1"
    [[ "$url" == *'{random-id}'* || "$url" == *'{random-path}'* || "$url" == *'{file}'* ]]
}

expand_template() {
    local url="$1"

    url="${url//\{random-id\}/$(( (RANDOM % 9999) + 1 ))}"
    url="${url//\{random-path\}/$(random_slug)}"
    url="${url//\{file\}/${ASSET_FILES[RANDOM % ${#ASSET_FILES[@]}]}}"

    printf '%s' "$url"
}

generate_workload() {

    local scenario="$1"
    local url="$2"
    local method="$3"
    local body="$4"

    WORKLOAD_FILE="${WORKDIR}/urls-${scenario}.txt"
    WORKLOAD_LINES=0

    [[ -d "$WORKDIR" ]] || init_workdir

    local samples="$WORKLOAD_SAMPLES"
    has_known_template "$url" || samples=1

    local raw_file="${WORKDIR}/urls-${scenario}-raw.txt"
    : > "$raw_file"

    local i expanded line
    for (( i = 0; i < samples; i++ )); do
        expanded=$(expand_template "$url")
        if [[ "$method" == "GET" ]]; then
            line="$expanded"
        else
            line="${expanded} ${method} ${body}"
        fi
        printf '%s\n' "$line" >> "$raw_file"
    done

    sort -u "$raw_file" > "$WORKLOAD_FILE"
    rm -f "$raw_file"

    WORKLOAD_LINES=$(grep -c . "$WORKLOAD_FILE" 2>/dev/null) || WORKLOAD_LINES=0

    (( WORKLOAD_LINES > 0 )) || die "Workload generation produced no URLs for '${scenario}'." 1
}

# ----------------------------------------------------------------
# Siege command builder
# ----------------------------------------------------------------

build_siege_command() {

    local rc_file=""
    if [[ -n "$WORKDIR" && -d "$WORKDIR" ]]; then
        rc_file="$WORKDIR/siegerc"
        cat > "$rc_file" <<SIEGE_RC_EOF
timeout = ${SIEGE_TIMEOUT}
connection-timeout = ${SIEGE_CONNECT_TIMEOUT}
socket-timeout = ${SIEGE_SOCKET_TIMEOUT}
failures-until-abort = ${SIEGE_FAILURES_ABORT}
SIEGE_RC_EOF
        chmod 600 "$rc_file" 2>/dev/null || true
    fi

    local effective_c
    effective_c=$(clamp_concurrency "$CONCURRENCY")

    local -a cmd=(siege)

    cmd+=(-c "$effective_c")

    if [[ "$REQUEST_MODE" == "requests" ]]; then
        cmd+=(-r "$REQUEST_COUNT")
    else
        cmd+=(-t "$DURATION")
    fi

    # Benchmark mode (-b: no delays) and delay mode (-d) are mutually
    # exclusive; passing both is contradictory.
    if [[ "$DELAY" != "0" ]]; then
        cmd+=(-d "$DELAY")
    else
        cmd+=(-b)
    fi

    [[ -n "$USER_AGENT" ]] && cmd+=(-A "$USER_AGENT")

    if (( ${#HEADER_ARGS[@]} > 0 )); then
        cmd+=("${HEADER_ARGS[@]}")
    fi

    [[ -n "$CONTENT_TYPE" ]] && cmd+=(-T "$CONTENT_TYPE")

    (( NONINTERACTIVE )) && cmd+=(-q)

    cmd+=(-j)
    cmd+=(-f "$WORKLOAD_FILE")

    (( WORKLOAD_LINES > 1 )) && cmd+=(-i)

    # Safety envelope: bind siege to a sandboxed resource file with hard
    # connect/socket timeouts and an abort threshold, so a degraded target
    # cannot hold sockets open or grind through failures indefinitely.
    if [[ -n "$rc_file" ]]; then
        cmd+=(-R "$rc_file")
    fi

    SIEGE_CMD=("${cmd[@]}")

    # Display mirror with credential-bearing header values redacted;
    # the raw argv must never reach persisted logs.
    local -a display=()
    local idx
    for (( idx = 0; idx < ${#cmd[@]}; idx++ )); do
        if [[ "${cmd[$idx]}" == "-H" ]]; then
            display+=("-H" "$(redact_header_value "${cmd[$((idx + 1))]}")")
            (( idx++ )) || true
        else
            display+=("${cmd[$idx]}")
        fi
    done
    SIEGE_DISPLAY=$(printf '%q ' "${display[@]}")
}

# ----------------------------------------------------------------
# Results analyzer
# ----------------------------------------------------------------

json_value() {
    local key="$1"
    local file="$2"
    sed -n -E 's/.*"'"$key"'"[[:space:]]*:[[:space:]]*"?([0-9]+(\.[0-9]+)?)"?.*/\1/p' "$file" 2>/dev/null | tail -n 1
}

text_value() {
    local label="$1"
    local file="$2"
    grep -E "^[[:space:]]*${label}:" "$file" 2>/dev/null | tail -n 1 \
        | sed -E 's/[^0-9.]//g'
}

metric_value() {
    local key="$1"
    local label="$2"
    local json_file="$3"
    local log_file="$4"

    local value
    value=$(json_value "$key" "$json_file")
    [[ -n "$value" ]] || value=$(text_value "$label" "$log_file")

    printf '%s' "${value:--}"
}

availability_color() {
    local avail="$1"
    local code
    code=$(awk -v v="$avail" 'BEGIN { print (v+0 >= 99.5) ? 0 : (v+0 >= 95.0 ? 1 : 2) }')
    case "$code" in
        0) printf '%s' "$GREEN" ;;
        1) printf '%s' "$YELLOW" ;;
        *) printf '%s' "$RED" ;;
    esac
}

# Generates a plain-language verdict from the analyzed metrics. The
# verdict helps beginners understand what the numbers actually mean
# without needing to interpret raw siege counters.
verdict_text() {
    local transactions="$1"
    local success="$2"
    local failed="$3"

    if ! is_number "$transactions" || (( transactions == 0 )); then
        printf 'No transactions were recorded. The test may not have run.'
        return
    fi

    if is_number "$success" && (( success > 0 )) && is_number "$failed" && (( failed == 0 )); then
        printf 'All requests succeeded. The server handled the load without errors.'
        return
    fi

    if is_number "$success" && (( success == 0 )) && is_number "$failed" && (( failed > 0 )); then
        printf 'All requests failed. The server may be down or the URL is unreachable.'
        return
    fi

    if is_number "$success" && (( success == 0 )); then
        printf 'No requests succeeded. The server may be returning errors (4xx/5xx) or the target URL is invalid.'
        return
    fi

    if is_number "$failed" && (( failed > 0 )); then
        printf 'Some requests failed (%s out of %s). Check server logs for errors.' "$failed" "$transactions"
        return
    fi

    printf 'Test completed.'
}

analyze_results() {

    local log_file="$1"
    local json_file="$2"
    local append_to_log="${3:-1}"

    local transactions availability elapsed response_time rate throughput concurrency success failed longest shortest
    local note

    transactions=$(metric_value "transactions" "Transactions" "$json_file" "$log_file")
    availability=$(metric_value "availability" "Availability" "$json_file" "$log_file")
    elapsed=$(metric_value "elapsed_time" "Elapsed time" "$json_file" "$log_file")
    response_time=$(metric_value "response_time" "Response time" "$json_file" "$log_file")
    rate=$(metric_value "transaction_rate" "Transaction rate" "$json_file" "$log_file")
    throughput=$(metric_value "throughput" "Throughput" "$json_file" "$log_file")
    concurrency=$(metric_value "concurrency" "Concurrency" "$json_file" "$log_file")
    success=$(metric_value "successful_transactions" "Successful transactions" "$json_file" "$log_file")
    failed=$(metric_value "failed_transactions" "Failed transactions" "$json_file" "$log_file")
    longest=$(metric_value "longest_transaction" "Longest transaction" "$json_file" "$log_file")
    shortest=$(metric_value "shortest_transaction" "Shortest transaction" "$json_file" "$log_file")

    local avail_color
    avail_color=$(availability_color "$availability")

    local fail_color="$GREEN"
    [[ "$failed" == "-" || "$failed" == "0" ]] || fail_color="$RED"

    # Integrity checks: siege's JSON accounting has two documented
    # blind spots (verified against 4.0.7) that would otherwise render
    # failing runs as green:
    #   - HTTP 4xx transactions land in neither success nor failed
    #     counters while availability still reads 100%
    #   - each followed redirect hop is booked as one extra
    #     transaction, so volume can exceed the requested budget
    local -a notes=()
    local budget

    if is_number "$transactions"; then

        if is_number "$success" && is_number "$failed" \
            && (( $(awk -v s="$success" -v f="$failed" -v t="$transactions" \
                    'BEGIN { print (s + f != t) ? 1 : 0 }') )); then
            notes+=("metric anomaly: successful (${success}) + failed (${failed}) != transactions (${transactions})")
        fi

        if is_number "$success" \
            && (( $(awk -v s="$success" -v t="$transactions" \
                    'BEGIN { print (t > 0 && s == 0) ? 1 : 0 }') )); then
            notes+=("ZERO successful transactions in ${transactions} attempts; error statuses such as HTTP 4xx are invisible to siege's availability figure")
        fi

        if [[ "$REQUEST_MODE" == "requests" ]]; then
            budget=$(( CONCURRENCY * REQUEST_COUNT * WORKLOAD_LINES ))
            if (( transactions > budget )); then
                notes+=("volume overrun: ${transactions} transactions exceed the requested budget of ${budget} (users ${CONCURRENCY} x reps ${REQUEST_COUNT} x urls ${WORKLOAD_LINES}); siege counts every followed redirect hop as an additional transaction")
            fi
        fi
    fi

    local verdict
    verdict=$(verdict_text "$transactions" "$success" "$failed")

    if (( append_to_log )); then
        {
            echo
            echo "---------------- ANALYSIS ----------------"
            echo "Availability      : ${availability}%"
            echo "Transactions      : ${transactions}"
            echo "Successful        : ${success}"
            echo "Failed            : ${failed}"
            echo "Elapsed time      : ${elapsed}s"
            echo "Response time avg : ${response_time}s"
            echo "Transaction rate  : ${rate}/sec"
            echo "Throughput        : ${throughput} MB/sec"
            echo "Concurrency       : ${concurrency}"
            echo "Longest / Shortest: ${longest}s / ${shortest}s"
            for note in "${notes[@]}"; do
                echo "WARNING: ${note}"
            done
            echo "VERDICT           : ${verdict}"
            echo "------------------------------------------"
            echo
        } >> "$log_file"
    fi

    echo
    echo -e "${BOLD}${CYAN}Results Analysis${RESET}"
    print_line 62

    # Determine status icons for each metric based on value ranges.
    local icon_avail icon_ok icon_fail
    icon_avail="[--]"
    icon_ok="[OK]"
    icon_fail="[OK]"

    if is_number "$availability"; then
        if (( $(awk -v v="$availability" 'BEGIN { print (v+0 >= 99.5) ? 1 : 0 }') )); then
            icon_avail="[OK]"
        elif (( $(awk -v v="$availability" 'BEGIN { print (v+0 >= 95.0) ? 1 : 0 }') )); then
            icon_avail="[!!]"
        else
            icon_avail="[!!]"
        fi
    fi

    if is_number "$success" && (( success == 0 )) && is_number "$transactions" && (( transactions > 0 )); then
        icon_ok="[!!]"
    fi

    if is_number "$failed" && (( failed > 0 )); then
        icon_fail="[!!]"
    fi

    printf '  %s %-20s %s%s%%%s   %s%s%s\n' \
        "$icon_avail" "Availability" "$avail_color" "$availability" "$RESET" \
        "$DIM" "(server responded to every request)" "$RESET"
    printf '  %s %-20s %s%s%s   %s%s%s\n' \
        "$icon_ok" "Successful" "$GREEN" "$success" "$RESET" \
        "$DIM" "(HTTP 2xx/3xx responses)" "$RESET"
    printf '  %s %-20s %s%s%s   %s%s%s\n' \
        "$icon_fail" "Failed" "$fail_color" "$failed" "$RESET" \
        "$DIM" "(connection errors or HTTP 5xx)" "$RESET"
    printf '  %-20s %s   %s%s%s\n' \
        "Transactions" "$transactions" "$DIM" "(total requests completed)" "$RESET"
    printf '  %-20s %ss   %s%s%s\n' \
        "Elapsed time" "$elapsed" "$DIM" "(total test duration)" "$RESET"
    printf '  %-20s %ss   %s%s%s\n' \
        "Response time (avg)" "$response_time" "$DIM" "(mean time per request)" "$RESET"
    printf '  %-20s %s/sec   %s%s%s\n' \
        "Transaction rate" "$rate" "$DIM" "(requests per second)" "$RESET"
    printf '  %-20s %s MB/sec\n' "Throughput" "$throughput"
    printf '  %-20s %s\n' "Concurrency" "$concurrency"
    printf '  %-20s %ss / %ss\n' "Longest / Shortest" "$longest" "$shortest"

    if (( ${#notes[@]} > 0 )); then
        echo
        for note in "${notes[@]}"; do
            printf '  %s[!!] WARNING:%s %s\n' "$YELLOW" "$RESET" "$note"
        done
        echo
        printf '  %s%sTip:%s Siege counts HTTP 4xx (e.g., 404) as transactions but does not\n' "$BOLD" "$YELLOW" "$RESET"
        printf '       categorize them as successful or failed. A "100%% available" result\n'
        printf '       with 0 successful requests means the server responded but returned\n'
        printf '       errors. Check the target URL or server configuration.\n'
    fi

    echo
    printf '  %s%sVerdict:%s %s\n' "$BOLD" "$CYAN" "$RESET" "$verdict"

    print_line 62

    # Expose verdict inputs to the caller (run_scenario) without a
    # second parse pass.
    ANALYZED_TRANSACTIONS="$transactions"
    ANALYZED_SUCCESS="$success"
    ANALYZED_FAILED="$failed"
}

show_last_report() {

    local newest
    newest=$(find "$RESULT_DIR" -maxdepth 1 -type f -name '*.log' -printf '%T@\t%p\n' 2>/dev/null \
        | sort -rn | head -n 1 | cut -f2-)

    if [[ -z "$newest" ]]; then
        tui_msgbox "No Reports" "No result logs found in:

${RESULT_DIR}"
        return
    fi

    analyze_results "$newest" "/dev/null" 0

    echo
    echo -e "  ${DIM}Report: ${newest}${RESET}"
    echo
    pause_screen
}

# ----------------------------------------------------------------
# Run scenario
# ----------------------------------------------------------------

run_scenario() {

    local scenario="$1"

    local url file_type method body
    url=$(get_scenario_url "$scenario")
    file_type=$(get_scenario_type "$scenario")
    method=$(get_scenario_method "$scenario")
    body=$(get_scenario_body "$scenario")

    if [[ -z "$url" ]]; then
        tui_msgbox "Scenario Not Configured" "$(scenario_label "$scenario") does not have a URL configured.

Configure this scenario before running it."
        LAST_EXIT_STATUS=3
        return 3
    fi

    local connectivity_output
    connectivity_output=$(check_connectivity "$url") || {
        tui_msgbox "Network Unreachable" "Cannot reach the target host.

${connectivity_output}

Configure a reachable URL before running the test."
        LAST_EXIT_STATUS=4
        return 4
    }

    if ! parse_custom_headers "$CUSTOM_HEADERS"; then
        tui_msgbox "Invalid Stored Headers" "The saved custom headers failed validation and will be skipped for this run.

Reconfigure them via HTTP Settings."
    fi

    generate_workload "$scenario" "$url" "$method" "$body"

    build_siege_command

    # Safety envelope: report whether concurrency was clamped so the user
    # is never surprised about the actual load that will be generated.
    local effective_concurrency max_sec timeout_rc
    effective_concurrency=$(clamp_concurrency "$CONCURRENCY")
    effective_concurrency=$(( effective_concurrency ))
    # Involatile outer bound for the whole siege process (see build step).
    max_sec=$(siege_max_seconds)
    timeout_rc=0

    local target_host="${url#*://}"
    target_host="${target_host%%/*}"

    local timestamp safe_name log_file json_file
    timestamp=$(date '+%Y%m%d-%H%M%S')
    safe_name=$(sanitize_filename "$(scenario_label "$scenario")")
    log_file=$(unique_logfile "${RESULT_DIR}/${timestamp}-${safe_name}.log")
    json_file="${WORKDIR}/${scenario}-stats.json"

    local workload_note="single URL"
    if (( WORKLOAD_LINES > 1 )); then
        workload_note="${WORKLOAD_LINES} sampled URLs (randomized)"
    fi

    local summary
    summary="
Scenario:
$(scenario_label "$scenario")

URL:
$url

Method:
$method
"
    if method_uses_body "$method"; then
        summary+="Body:
$(masked_body_note "$body")
"
    fi

    summary+="
Resource type (metadata):
$file_type

Concurrent users:
$effective_concurrency
$( if (( UNSAFE == 0 && effective_concurrency != CONCURRENCY )); then printf '  (requested %s, clamped by safety envelope; use --unsafe to override)' "$CONCURRENCY"; fi )

Test mode:
$REQUEST_MODE
"
    if [[ "$REQUEST_MODE" == "requests" ]]; then
        summary+="Total requests:
$REQUEST_COUNT
"
    else
        summary+="Duration:
$DURATION
"
    fi

    summary+="
Delay:
$DELAY seconds

Workload:
$workload_note

Command:
$SIEGE_DISPLAY

Target host: $target_host

Make sure you have permission to test this server.

Continue?"

    if ! tui_yesno "Confirm Load Test" "$summary"; then
        return 0
    fi

    clear_screen

    echo
    echo -e "${BOLD}${CYAN}Running Siege${RESET}"
    print_line 76

    echo
    echo -e "  Scenario       : ${BOLD}$(scenario_label "$scenario")${RESET}"
    echo -e "  URL            : $url"
    echo -e "  Method         : $method"
    echo -e "  Resource type  : $file_type (metadata)"
    echo -e "  Concurrency    : $effective_concurrency"
    echo -e "  Mode           : $REQUEST_MODE"

    if [[ "$REQUEST_MODE" == "requests" ]]; then
        echo -e "  Requests       : $REQUEST_COUNT"
    else
        echo -e "  Duration       : $DURATION"
    fi

    echo -e "  Delay          : $DELAY"
    echo -e "  Workload       : $workload_note"
    echo -e "  Log            : $log_file"

    echo
    print_line 76
    echo

    echo -e "${YELLOW}Starting load test...${RESET}"
    echo

    {
        echo "============================================================"
        echo "Siege TUI Load Test"
        echo "============================================================"
        echo "Timestamp      : $(date)"
        echo "Tool version   : $APP_VERSION"
        echo "Siege version  : ${SIEGE_VERSION_LINE:-unknown}"
        echo "Scenario       : $(scenario_label "$scenario")"
        echo "URL template   : $url"
        echo "Method         : $method"
        echo "Body           : $(masked_body_note "$body")"
        echo "Resource type  : $file_type"
        echo "User-Agent     : $USER_AGENT"
        echo "Headers        : $(redact_sensitive_headers "$CUSTOM_HEADERS")"
        echo "Concurrency    : $effective_concurrency (requested $CONCURRENCY)"
        echo "Safety mode    : $([ "$UNSAFE" = "1" ] && echo "unsafe" || echo "envelope-on (max $CONCURRENCY_SAFE_CAP)")"
        echo "Socket timeout : ${SIEGE_CONNECT_TIMEOUT}s connect / ${SIEGE_TIMEOUT}s txn / ${SIEGE_SOCKET_TIMEOUT}s socket"
        echo "Outer run cap  : ${max_sec}s"
        echo "Request mode   : $REQUEST_MODE"
        echo "Request count  : $REQUEST_COUNT"
        echo "Duration       : $DURATION"
        echo "Delay          : $DELAY"
        echo "Workload lines : $WORKLOAD_LINES"
        echo "Command        : $SIEGE_DISPLAY"
        echo "============================================================"
        echo
    } > "$log_file"

    # Result artifacts carry infrastructure details (targets, headers,
    # workload shape); keep them private to the invoking user.
    chmod 600 "$log_file" 2>/dev/null || true

    # Safety envelope: bound the ENTIRE siege process with an outer timeout.
    # Even if siege ignores its per-socket timeouts and hangs (a degraded or
    # hanging server, or a saturated VM/NAT path), the wrapper guarantees the
    # runaway process is killed so it can never wedge the host indefinitely.
    local siege_status=0
    ( timeout --signal=KILL "$max_sec" "${SIEGE_CMD[@]}" ) \
        2>&1 > "$json_file" | tee -a "$log_file"
    siege_status=${PIPESTATUS[0]}

    if (( siege_status == 137 || siege_status == 124 )); then
        timeout_rc=1
    fi

    analyze_results "$log_file" "$json_file"

    # Retain machine-readable stats next to the log for CI pipelines.
    if [[ -s "$json_file" ]]; then
        cp -f "$json_file" "${log_file%.log}.json" 2>/dev/null || true
        chmod 600 "${log_file%.log}.json" 2>/dev/null || true
    fi

    {
        echo
        echo "Siege exit status: $siege_status"
        echo "Finished: $(date)"
    } >> "$log_file"

    LAST_EXIT_STATUS=$siege_status

    echo
    if (( timeout_rc )); then
        echo -e "${RED}${BOLD}✗ Siege was terminated after ${max_sec}s to protect the host.${RESET}"
        echo -e "  The target did not complete within the safety bound. This protects the"
        echo -e "  machine from socket saturation (a common cause of host freezes with VM/NAT targets)."
        echo -e "  Reduce concurrency, add a delay (DELAY > 0), or fix the target server."
        {
            echo
            echo "SAFETY: siege terminated after ${max_sec}s (outer timeout) to protect the host."
        } >> "$log_file"
        LAST_EXIT_STATUS=8
    elif (( siege_status != 0 )); then
        echo -e "${RED}${BOLD}✗ Siege exited with status ${siege_status}.${RESET}"
    elif is_number "$ANALYZED_SUCCESS" && is_number "$ANALYZED_TRANSACTIONS" \
        && (( ANALYZED_TRANSACTIONS > 0 && ANALYZED_SUCCESS == 0 )); then
        # Exit status alone lies: siege returns 0 even when every
        # request failed (connection refused, all-4xx targets).
        echo -e "${RED}${BOLD}✗ Test ran but recorded no successful transactions.${RESET}"
    elif [[ "$ANALYZED_FAILED" =~ ^[0-9]+$ ]] && (( ANALYZED_FAILED > 0 )); then
        echo -e "${YELLOW}${BOLD}! Test completed with ${ANALYZED_FAILED} failed transaction(s).${RESET}"
    else
        echo -e "${GREEN}${BOLD}✓ Siege test completed successfully.${RESET}"
    fi

    echo
    echo "Result saved to:"
    echo "  $log_file"
    echo

    pause_screen
    return "$LAST_EXIT_STATUS"
}

# ----------------------------------------------------------------
# Run all configured scenarios
# ----------------------------------------------------------------

run_all_scenarios() {

    local configured=()
    local scenario

    for scenario in "${SCENARIOS[@]}"; do
        if [[ -n "$(get_scenario_url "$scenario")" ]]; then
            configured+=("$scenario")
        fi
    done

    if (( ${#configured[@]} == 0 )); then
        tui_msgbox "No Scenarios Configured" "Configure at least one scenario before starting a load test."
        return
    fi

    local message="The following scenarios will be tested:

"

    for scenario in "${configured[@]}"; do
        message+="• $(scenario_label "$scenario"): $(get_scenario_url "$scenario") [$(get_scenario_method "$scenario")]
"
    done

    message+="
Concurrency: $CONCURRENCY
Workload samples: $WORKLOAD_SAMPLES per templated URL

This will generate HTTP traffic against all configured targets.

Continue?"

    if ! tui_yesno "Run All Scenarios" "$message"; then
        return
    fi

    local recap=""
    local status

    for scenario in "${configured[@]}"; do
        status=0
        run_scenario "$scenario" || status=$?
        recap+="$(scenario_label "$scenario"): exit ${status}
"
    done

    tui_msgbox "Run Complete" "
Recap:

$recap"
}

# ----------------------------------------------------------------
# Scenario configuration
# ----------------------------------------------------------------

configure_scenario() {

    local scenario="$1"

    local url_var type_var method_var body_var

    case "$scenario" in
        static)
            url_var="STATIC_URL"; type_var="STATIC_TYPE"
            method_var="STATIC_METHOD"; body_var="STATIC_BODY" ;;
        login)
            url_var="LOGIN_URL"; type_var="LOGIN_TYPE"
            method_var="LOGIN_METHOD"; body_var="LOGIN_BODY" ;;
        notfound)
            url_var="NOTFOUND_URL"; type_var="NOTFOUND_TYPE"
            method_var="NOTFOUND_METHOD"; body_var="NOTFOUND_BODY" ;;
        dynamic)
            url_var="DYNAMIC_URL"; type_var="DYNAMIC_TYPE"
            method_var="DYNAMIC_METHOD"; body_var="DYNAMIC_BODY" ;;
        *)
            return 1 ;;
    esac

    local current_url="${!url_var}"
    local current_type="${!type_var}"
    local current_body="${!body_var}"

    # ---- URL ----
    local new_url
    while :; do
        if ! new_url=$(tui_input \
            "Configure $(scenario_label "$scenario")" \
            "Enter the complete URL.

Template placeholders (optional):
  {random-id}    random integer 1..9999
  {random-path}  random missing-page slug
  {file}         common asset name (html/css/js/jpg/png/webp)

Examples:
https://example.com/assets/{file}
https://example.com/article/{random-id}
https://example.com/{random-path}
https://example.com/login" \
            "$current_url"); then
            return 1
        fi

        validate_url "$new_url" && break
        tui_msgbox "Invalid URL" "The URL must start with http:// or https://, contain no whitespace or control characters, and be at most ${URL_MAX_LEN} characters."
    done

    # ---- Resource type (metadata only) ----
    local new_type
    if ! new_type=$(tui_input \
        "File / Resource Type" \
        "Enter the resource/file type (test metadata).

Examples:
html php css js json jpg webp xml api none" \
        "$current_type"); then
        return 1
    fi
    new_type=$(sanitize_filename "$new_type")

    # ---- Method ----
    local new_method
    new_method=$(tui_menu \
        "HTTP Method" \
        "Request method sent to $(scenario_label "$scenario"):" \
        "GET"  "GET (default, no body)" \
        "POST" "POST (form/body data)" \
        "PUT"  "PUT (body data)" \
        "PATCH" "PATCH (partial update)" \
        "DELETE" "DELETE (no body)" \
    )
    [[ -n "$new_method" ]] || return 1
    validate_method "$new_method" || return 1

    # ---- Body ----
    local new_body="$current_body"
    if method_uses_body "$new_method"; then
        if ! new_body=$(tui_input \
            "Request Body" \
            "Enter the request body (urlencoded form data).

Example:
username=admin&password=secret

Sent inline as: <url> ${new_method} <body>" \
            "$current_body"); then
            return 1
        fi

        while ! validate_body "$new_body"; do
            tui_msgbox "Invalid Body" "The body must be at most ${BODY_MAX_LEN} characters with no control characters (newlines)."
            if ! new_body=$(tui_input \
                "Request Body" \
                "Enter the request body (urlencoded form data)." \
                "$current_body"); then
                return 1
            fi
        done
    else
        new_body=""
    fi

    printf -v "$url_var" '%s' "$new_url"
    printf -v "$type_var" '%s' "$new_type"
    printf -v "$method_var" '%s' "$new_method"
    printf -v "$body_var" '%s' "$new_body"

    save_config

    tui_msgbox "Configuration Saved" "$(scenario_label "$scenario") configuration:

URL:
$new_url

Resource type (metadata):
$new_type

Method:
$new_method
"
}

configure_all_scenarios() {

    local scenario
    for scenario in "${SCENARIOS[@]}"; do
        configure_scenario "$scenario" || true
    done
}

# ----------------------------------------------------------------
# Load test settings
# ----------------------------------------------------------------

configure_load_test() {

    local value

    # ---- Concurrency ----
    while :; do
        if ! value=$(tui_input \
            "Concurrent Users" \
            "Number of simultaneous Siege users.

Recommended starting value: 10
WARNING: High values can overload your server." \
            "$CONCURRENCY"); then
            return 1
        fi

        if [[ "$value" =~ ^[0-9]+$ ]] && (( value > 0 )); then
            break
        fi

        tui_msgbox "Invalid Value" "Concurrency must be a positive integer."
    done

    local new_concurrency="$value"

    if (( new_concurrency >= CONCURRENCY_SOFT_CAP )); then
        tui_msgbox "High Concurrency Warning" "You requested ${new_concurrency} concurrent users.

Values above ${CONCURRENCY_SOFT_CAP} can saturate PHP-FPM workers,
exhaust database connections, or crash the target.

Proceed only against systems you own."
    fi

    # ---- Mode ----
    local new_mode
    new_mode=$(tui_menu \
        "Test Mode" \
        "Choose how the test should stop:" \
        "requests" "Run a fixed number of requests" \
        "duration" "Run continuously for a specified duration" \
    )
    [[ -n "$new_mode" ]] || return 1

    # ---- Count / duration ----
    local new_count="$REQUEST_COUNT"
    local new_duration="$DURATION"

    if [[ "$new_mode" == "requests" ]]; then
        while :; do
            if ! value=$(tui_input \
                "Total Requests" \
                "Enter the total number of requests.

Example: 10000" \
                "$REQUEST_COUNT"); then
                return 1
            fi

            if [[ "$value" =~ ^[0-9]+$ ]] && (( value > 0 )); then
                break
            fi

            tui_msgbox "Invalid Value" "Request count must be a positive integer."
        done
        new_count="$value"
    else
        while :; do
            if ! value=$(tui_input \
                "Test Duration" \
                "Enter Siege duration.

Examples: 30S  5M  1H  (bare numbers get an S suffix)" \
                "$DURATION"); then
                return 1
            fi

            if value=$(normalize_duration "$value"); then
                break
            fi

            tui_msgbox "Invalid Duration" "Duration must look like: 30S, 5M, 1H (or a bare number)."
        done
        new_duration="$value"
    fi

    # ---- Delay ----
    while :; do
        if ! value=$(tui_input \
            "Request Delay" \
            "Delay between requests in seconds (fractional allowed).

0 = maximum request rate
1 = one second delay" \
            "$DELAY"); then
            return 1
        fi

        if [[ "$value" =~ ^[0-9]+([.][0-9]+)?$ ]] \
            && awk -v v="$value" -v m="$DELAY_HARD_CAP" 'BEGIN { exit !(v+0 <= m+0) }'; then
            break
        fi

        tui_msgbox "Invalid Delay" "Delay must be numeric and at most ${DELAY_HARD_CAP} seconds."
    done

    local new_delay="$value"

    # ---- Workload samples ----
    while :; do
        if ! value=$(tui_input \
            "Workload Sample Size" \
            "How many distinct URLs to expand from a templated URL
before de-duplication (used with siege's random -i mode).

Range: ${SAMPLES_MIN}..${SAMPLES_MAX}

Ignored for URLs without placeholders." \
            "$WORKLOAD_SAMPLES"); then
            return 1
        fi

        if [[ "$value" =~ ^[0-9]+$ ]] && (( value >= SAMPLES_MIN && value <= SAMPLES_MAX )); then
            break
        fi

        tui_msgbox "Invalid Value" "Sample size must be between ${SAMPLES_MIN} and ${SAMPLES_MAX}."
    done

    local new_samples="$value"

    CONCURRENCY="$new_concurrency"
    REQUEST_MODE="$new_mode"
    REQUEST_COUNT="$new_count"
    DURATION="$new_duration"
    DELAY="$new_delay"
    WORKLOAD_SAMPLES="$new_samples"

    save_config
}

# ----------------------------------------------------------------
# HTTP configuration
# ----------------------------------------------------------------

configure_http() {

    local value

    # ---- User-Agent ----
    if ! value=$(tui_input \
        "User-Agent" \
        "Enter the HTTP User-Agent string.

Leave empty to use siege's default." \
        "$USER_AGENT"); then
        return 1
    fi
    USER_AGENT="$value"

    # ---- Custom headers ----
    while :; do
        if ! value=$(tui_input \
            "Custom Headers" \
            "Optional extra HTTP headers.

Separate multiple headers with semicolons.
Each header needs a 'Name: value' form.

Example:
Accept: application/json; X-Test: siege" \
            "$CUSTOM_HEADERS"); then
            return 1
        fi

        if parse_custom_headers "$value"; then
            break
        fi

        tui_msgbox "Invalid Header" "Every header must look like 'Name: value' with no control characters.

Example: Accept: application/json; X-Test: siege"
    done
    CUSTOM_HEADERS="$value"

    # ---- Content-Type ----
    if ! value=$(tui_input \
        "Content-Type" \
        "Content-Type sent with POST/PUT/PATCH bodies.

Common values:
application/x-www-form-urlencoded
application/json

Leave empty for siege's default." \
        "$CONTENT_TYPE"); then
        return 1
    fi
    CONTENT_TYPE="$value"

    save_config
}

# ----------------------------------------------------------------
# Configuration overview
# ----------------------------------------------------------------

show_configuration() {

    clear_screen

    echo
    echo -e "${BOLD}${CYAN}Current Test Configuration${RESET}"
    print_line 76

    echo
    echo -e "${BOLD}SCENARIO PROFILES${RESET}"
    echo

    printf "  %-14s %-34s %-6s %-6s %-10s\n" \
        "Scenario" "URL" "Type" "Meth" "Body"

    print_line 76

    local scenario s_url s_type s_method s_body body_note
    for scenario in "${SCENARIOS[@]}"; do
        s_url=$(get_scenario_url "$scenario")
        s_type=$(get_scenario_type "$scenario")
        s_method=$(get_scenario_method "$scenario")
        s_body=$(get_scenario_body "$scenario")

        if [[ -n "$s_body" ]]; then
            body_note=$(printf '(%d B)' "${#s_body}")
        else
            body_note="<none>"
        fi

        printf "  %-14s %-34s %-6s %-6s %-10s\n" \
            "$(scenario_label "$scenario")" \
            "$(printf '%.*s' 34 "$s_url")" \
            "$s_type" \
            "$s_method" \
            "$body_note"
    done

    echo
    echo -e "${BOLD}LOAD TEST${RESET}"
    echo

    echo "  Concurrent users : $CONCURRENCY"
    echo "  Test mode        : $REQUEST_MODE"
    echo "  Requests         : $REQUEST_COUNT"
    echo "  Duration         : $DURATION"
    echo "  Delay            : $DELAY"
    echo "  Workload samples : $WORKLOAD_SAMPLES"
    echo
    echo "  User-Agent       : $USER_AGENT"
    echo "  Headers          : ${CUSTOM_HEADERS:-<none>}"
    echo "  Content-Type     : ${CONTENT_TYPE:-<default>}"
    echo
    echo "  Results          : $RESULT_DIR"

    echo

    pause_screen
}

# ----------------------------------------------------------------
# Main menu
# ----------------------------------------------------------------

main_menu() {

    while true; do

        show_banner

        local choice
        choice=$(tui_menu \
            "$APP_NAME" \
            "Choose an operation:" \
            "1" "Configure Static Page" \
            "2" "Configure Login Page" \
            "3" "Configure Not Found Page" \
            "4" "Configure Dynamic Page" \
            "5" "Configure All Scenarios" \
            "6" "Load Test Settings" \
            "7" "HTTP Settings" \
            "8" "Run Load Test" \
            "9" "View Last Report" \
            "A" "View Configuration" \
            "0" "Exit" \
        ) || continue

        case "$choice" in

            1) configure_scenario static ;;
            2) configure_scenario login ;;
            3) configure_scenario notfound ;;
            4) configure_scenario dynamic ;;
            5) configure_all_scenarios ;;
            6) configure_load_test ;;
            7) configure_http ;;
            8) run_test_menu ;;
            9) show_last_report ;;
            A) show_configuration ;;

            0)
                clear_screen
                echo
                echo -e "${GREEN}Thank you for using ${APP_NAME}.${RESET}"
                echo
                exit 0
                ;;

            *)
                tui_msgbox "Invalid Selection" "Please select one of the available menu options."
                ;;

        esac

    done
}

run_test_menu() {

    local choice
    choice=$(tui_menu \
        "Run Load Test" \
        "Select a test:" \
        "1" "Static Page" \
        "2" "Login Page" \
        "3" "Not Found Page" \
        "4" "Dynamic Page" \
        "5" "All Configured Scenarios" \
        "0" "Cancel" \
    ) || return 0

    case "$choice" in

        1) run_scenario static ;;
        2) run_scenario login ;;
        3) run_scenario notfound ;;
        4) run_scenario dynamic ;;
        5) run_all_scenarios ;;
        0) return 0 ;;

        *)
            tui_msgbox "Invalid Selection" "Please select a valid test."
            ;;

    esac
}

# ----------------------------------------------------------------
# CLI entry points
# ----------------------------------------------------------------

usage() {
    cat <<USAGE_EOF
$APP_NAME v$APP_VERSION

Usage:
  $0                          Launch interactive TUI
  $0 --run <scenario> --yes   Run one scenario headless
  $0 --unsafe                 opt out of the host-safety envelope
  $0 --help                   Show this help
  $0 --version                Show version

Scenarios: static | login | notfound | dynamic

Safety envelope (default ON):
  - Concurrency is clamped to ${CONCURRENCY_SAFE_CAP} unless --unsafe.
  - A sandboxed siege resource file sets connect/socket timeouts and an
    abort threshold so a degraded target cannot hang the run.
  - The whole siege process is bounded by an outer timeout (${SIEGE_TIMEOUT}s+
    per socket; whole run capped) so a runaway siege can never wedge the host.
  - Loopback/VM/NAT targets (e.g. DDEV-in-VirtualBox) print a saturation
    warning and should be run at low concurrency with a delay.
  Use --unsafe only against systems you control and understand.

Headless mode (--run) requires --yes and exits with:
  0  siege completed successfully
  2  usage error (unknown option/scenario, --run without --yes)
  3  scenario not configured
  other non-zero  siege failure or setup error

Security note: request bodies and credential-bearing headers are
masked/redacted in all persisted logs.

Configuration: $CONFIG_FILE
Results:       $RESULT_DIR
USAGE_EOF
}

handle_args() {

    while (( $# > 0 )); do
        case "$1" in

            -h|--help)
                usage
                exit 0
                ;;

            -V|--version)
                printf '%s v%s\n' "$APP_NAME" "$APP_VERSION"
                exit 0
                ;;

            --run)
                shift
                [[ $# -gt 0 ]] || die "--run requires a scenario name (static|login|notfound|dynamic)." 2
                RUN_SCENARIO="$1"
                ;;

            --yes|-y)
                AUTO_YES=1
                NONINTERACTIVE=1
                ;;

            --unsafe)
                UNSAFE=1
                ;;

            *)
                printf 'Unknown option: %s\n\n' "$1" >&2
                usage >&2
                exit 2
                ;;

        esac
        shift
    done

    # --run implies automation; without --yes the confirmation prompt
    # would silently fail under cron/CI (EOF on read) and exit 0 with
    # nothing tested. Make the pairing mandatory.
    if [[ -n "$RUN_SCENARIO" ]] && (( ! AUTO_YES )); then
        printf 'siege-tui: --run requires --yes for unattended execution.\n' >&2
        usage >&2
        exit 2
    fi
}

cleanup() {

    trap - EXIT INT TERM

    if [[ -n "${WORKDIR:-}" && -d "$WORKDIR" ]]; then
        rm -rf -- "$WORKDIR"
    fi

    if (( ${NONINTERACTIVE:-0} == 0 )); then
        printf '\n'
    fi
}

# ----------------------------------------------------------------
# Application startup
# ----------------------------------------------------------------

main() {

    handle_args "$@"

    ensure_dirs
    load_config

    if [[ -n "$RUN_SCENARIO" ]]; then

        local valid=0
        local scenario
        for scenario in "${SCENARIOS[@]}"; do
            [[ "$RUN_SCENARIO" == "$scenario" ]] && valid=1
        done
        (( valid )) || die "Unknown scenario '${RUN_SCENARIO}'. Valid: ${SCENARIOS[*]}" 2

        silent_dependency_check
        init_workdir

        run_scenario "$RUN_SCENARIO"
        exit "$LAST_EXIT_STATUS"
    fi

    check_dependencies
    init_workdir

    main_menu
}

# Launch only when executed directly; sourcing (unit tests) must not start
# the app or hijack the host shell's signal handlers.
if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    trap cleanup EXIT
    trap 'exit 130' INT
    trap 'exit 143' TERM
    main "$@"
fi
