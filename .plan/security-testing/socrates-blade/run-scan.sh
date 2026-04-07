#!/bin/bash

# =========================================================
# Socrates Blade v3.2: Security Tester Automation Wrapper
# Blogware/Scriptlog CMS Security Scanner
# =========================================================
# This script automates security testing for Blogware/Scriptlog
# - Sets up Python virtual environment
# - Generates routes dynamically from application
# - Executes comprehensive security scan
# - Supports CI/CD integration
# =========================================================

set -euo pipefail

# --- Configuration ---
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TOOL_DIR="$SCRIPT_DIR"
VENV_DIR="$TOOL_DIR/venv"
REQUIREMENTS_FILE="$TOOL_DIR/scanrequirements.txt"
ROUTES_JSON="$TOOL_DIR/routes.json"
PHP_CONVERTER="$TOOL_DIR/export_routes.php"
PYTHON_SCRIPT="$TOOL_DIR/socrates-blade.py"
URL_VALIDATOR_LIB="$TOOL_DIR/url-validator-lib.sh"

# --- Output Colors ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# --- Logging ---
LOG_FILE="${LOG_FILE:-/tmp/socrates-blade-$(date +%Y%m%d-%H%M%S).log}"
REPORT_DIR="${REPORT_DIR:-$TOOL_DIR/reports}"

# --- Source URL Validation Library ---
if [ -f "$URL_VALIDATOR_LIB" ]; then
    source "$URL_VALIDATOR_LIB"
else
    echo -e "${RED}Error: URL validation library not found: $URL_VALIDATOR_LIB${NC}" >&2
    exit 1
fi

# --- Functions ---

log() {
    local level="$1"
    local message="$2"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "$message"
    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"
}

log_info() { log "INFO" "$BLUE*$NC $1"; }
log_success() { log "SUCCESS" "$GREEN+$NC $1"; }
log_warning() { log "WARNING" "$YELLOW!$NC $1"; }
log_error() { log "ERROR" "$RED$NC $1"; }

show_banner() {
    echo -e "${CYAN}"
    echo "  +=============================================================+"
    echo "  |                                                             |"
    echo "  |                S O C R A T E S  B L A D E                   |"
    echo "  |                                                             |"
    echo "  |                                                             |"
    echo "  |              Scriptlog CMS Security Auditor                 |"
    echo "  |                                                             |"
    echo "  +=============================================================+"
    echo -e "${NC}"
}

show_help() {
    echo -e "${BOLD}Usage:${NC} $0 <target_url> [options]"
    echo ""
    echo -e "${BOLD}Required Arguments:${NC}"
    echo -e "  ${GREEN}target_url${NC}          Target URL (e.g., http://localhost or https://example.com)"
    echo ""
    echo -e "${BOLD}Authentication Options:${NC}"
    echo -e "  ${GREEN}-u, --username${NC}      Administrator username (default: administrator)"
    echo -e "  ${GREEN}-p, --password${NC}      Administrator password"
    echo ""
    echo -e "${BOLD}Scanning Options:${NC}"
    echo -e "  ${GREEN}--aggressive${NC}         Enable aggressive testing (slower, more thorough)"
    echo -e "  ${GREEN}--brute-force${NC}        Enable brute force attack simulation"
    echo -e "  ${GREEN}--threads <n>${NC}        Number of concurrent threads (default: 5)"
    echo -e "  ${GREEN}--timeout <sec>${NC}      Request timeout in seconds (default: 5)"
    echo ""
    echo -e "${BOLD}Reporting Options:${NC}"
    echo -e "  ${GREEN}-o, --output <file>${NC}  Save JSON report"
    echo -e "  ${GREEN}--html-report <file>${NC}  Save HTML report"
    echo -e "  ${GREEN}--report-dir <dir>${NC}    Output directory for reports (default: ./reports)"
    echo ""
    echo -e "${BOLD}Advanced Options:${NC}"
    echo -e "  ${GREEN}--proxy <url>${NC}        HTTP/HTTPS proxy (e.g., http://127.0.0.1:8080)"
    echo -e "  ${GREEN}--wordlist <file>${NC}     Custom password wordlist for brute force"
    echo -e "  ${GREEN}--max-attempts <n>${NC}   Max brute force attempts (default: 10)"
    echo -e "  ${GREEN}--csrf-field <name>${NC}  CSRF token field name (default: login_form)"
    echo -e "  ${GREEN}--verify-ssl${NC}          Verify SSL certificates"
    echo ""
    echo -e "${BOLD}Utility Options:${NC}"
    echo -e "  ${GREEN}--sync-routes${NC}         Sync routes from application before scan"
    echo -e "  ${GREEN}--no-sync${NC}             Skip route synchronization"
    echo -e "  ${GREEN}-v, --verbose${NC}         Verbose output"
    echo -e "  ${GREEN}--dry-run${NC}             Show what would be executed without running"
    echo -e "  ${GREEN}--no-validate${NC}         Skip URL validation"
    echo -e "  ${GREEN}-h, --help${NC}            Show this help message"
    echo ""
    echo -e "${BOLD}Examples:${NC}"
    echo -e "  ${CYAN}# Basic local scan${NC}"
    echo -e "  $0 http://localhost"
    echo ""
    echo -e "  ${CYAN}# Authenticated scan with reports${NC}"
    echo -e "  $0 http://localhost -u administrator -p 'P@ssw0rd' -o report.json --html-report report.html"
    echo ""
    echo -e "  ${CYAN}# Aggressive scan with brute force${NC}"
    echo -e "  $0 https://blog.example.com --aggressive --brute-force --threads 10"
    echo ""
    echo -e "  ${CYAN}# Scan via proxy (Burp Suite)${NC}"
    echo -e "  $0 http://blog.example.com --proxy http://127.0.0.1:8080 -o findings.json"
}

check_requirements() {
    declare -g SYNC_ENABLED
    log_info "Checking system requirements..."

    if ! command -v python3 &> /dev/null; then
        log_error "Python 3 is required but not installed."
        exit 1
    fi

    if ! command -v php &> /dev/null; then
        log_warning "PHP is not installed. Route synchronization will be skipped."
        SYNC_ENABLED=false
    else
        SYNC_ENABLED="$sync_routes_flag"
    fi

    if ! command -v curl &> /dev/null; then
        log_warning "curl is not installed. Some checks may fail."
    fi

    log_success "Requirements check complete"
}

setup_virtualenv() {
    if [ ! -d "$VENV_DIR" ]; then
        log_info "Creating Python virtual environment..."
        python3 -m venv "$VENV_DIR"
        log_success "Virtual environment created at $VENV_DIR"
    fi

    source "$VENV_DIR/bin/activate"

    log_info "Installing Python dependencies..."
    pip install --quiet --upgrade pip
    pip install --quiet -r "$REQUIREMENTS_FILE"
    log_success "Python dependencies installed"
}

sync_routes() {
    if [ "$sync_routes_flag" = false ]; then
        log_warning "Route synchronization disabled by user (--no-sync)"
        return 0
    fi

    if [ "$SYNC_ENABLED" = false ]; then
        log_warning "PHP not available. Route synchronization skipped."
        return 0
    fi

    if [ ! -f "$PHP_CONVERTER" ]; then
        log_warning "Route exporter not found: $PHP_CONVERTER"
        return 0
    fi

    log_info "Synchronizing routes from Blogware application..."

    if php "$PHP_CONVERTER" > "$ROUTES_JSON" 2>&1; then
        log_success "Routes synchronized successfully"
    else
        log_warning "Route synchronization failed. Using existing routes.json"
    fi
}

setup_reporting() {
    if [ -n "${report_dir:-}" ]; then
        REPORT_DIR="$report_dir"
    fi

    mkdir -p "$REPORT_DIR"

    if [ -n "${report_file:-}" ] && [ ! -e "$(dirname "$report_file")" ]; then
        mkdir -p "$(dirname "$report_file")"
    fi

    if [ -z "${output_file:-}" ]; then
        local timestamp
        timestamp=$(date +%Y%m%d-%H%M%S)
        output_file="$REPORT_DIR/socrates-blade-report-$timestamp.json"
    fi

    if [ -z "${html_report_file:-}" ]; then
        local timestamp
        timestamp=$(date +%Y%m%d-%H%M%S)
        html_report_file="$REPORT_DIR/socrates-blade-report-$timestamp.html"
    fi
}

build_python_args() {
    PYTHON_ARGS=("$TARGET_URL")

    if [ -n "${username:-}" ]; then
        PYTHON_ARGS+=("-u" "$username")
    fi

    if [ -n "${password:-}" ]; then
        PYTHON_ARGS+=("-p" "$password")
    fi

    [ -n "${threads:-}" ] && PYTHON_ARGS+=("--threads" "$threads")
    [ -n "${timeout_sec:-}" ] && PYTHON_ARGS+=("--timeout" "$timeout_sec")
    [ "$aggressive" = true ] && PYTHON_ARGS+=("--aggressive")
    [ "$brute_force" = true ] && PYTHON_ARGS+=("--brute-force")
    [ -n "${proxy:-}" ] && PYTHON_ARGS+=("--proxy" "$proxy")
    [ -n "${wordlist_file:-}" ] && PYTHON_ARGS+=("--wordlist" "$wordlist_file")
    [ -n "${max_attempts:-}" ] && PYTHON_ARGS+=("--max-attempts" "$max_attempts")
    [ -n "${csrf_field:-}" ] && PYTHON_ARGS+=("--csrf-field" "$csrf_field")
    [ "$verify_ssl" = true ] && PYTHON_ARGS+=("--verify-ssl")
    [ -n "${output_file:-}" ] && PYTHON_ARGS+=("-o" "$output_file")
    [ -n "${html_report_file:-}" ] && PYTHON_ARGS+=("--html-report" "$html_report_file")

    PYTHON_ARGS+=("--routes-file" "$ROUTES_JSON")
}

dry_run() {
    echo -e "${BOLD}${YELLOW}Dry Run Mode - Commands that would be executed:${NC}"
    echo ""
    echo -e "${CYAN}1. Virtual Environment:${NC}"
    echo "   python3 -m venv $VENV_DIR"
    echo "   pip install -r $REQUIREMENTS_FILE"
    echo ""
    echo -e "${CYAN}2. Route Sync:${NC}"
    echo "   php $PHP_CONVERTER > $ROUTES_JSON"
    echo ""
    echo -e "${CYAN}3. Security Scan:${NC}"
    echo -n "   python3 $PYTHON_SCRIPT"
    for arg in "${PYTHON_ARGS[@]}"; do
        if [[ "$arg" == --* ]] || [[ "$arg" == - ]]; then
            echo -n " \\"
            echo ""
            echo -n "            $arg"
        else
            echo -n " $arg"
        fi
    done
    echo ""
    echo ""
}

run_scan() {
    log_info "Starting security scan..."
    log_info "Target: $TARGET_URL"
    log_info "Log file: $LOG_FILE"
    echo ""

    if python3 "$PYTHON_SCRIPT" "${PYTHON_ARGS[@]}"; then
        log_success "Security scan completed successfully"
        return 0
    else
        log_error "Security scan failed with exit code $?"
        return 1
    fi
}

cleanup() {
    if [ -n "${VIRTUAL_ENV:-}" ]; then
        deactivate 2>/dev/null || true
    fi
}

trap cleanup EXIT

# --- Argument Parsing ---
TARGET_URL=""
username=""
password=""
threads=""
timeout_sec=""
aggressive=false
brute_force=false
proxy=""
wordlist_file=""
max_attempts=""
csrf_field=""
verify_ssl=false
output_file=""
html_report_file=""
sync_routes_flag=true
verbose=false
dry_run_flag=false
no_validate_flag=false

while [[ $# -gt 0 ]]; do
    case $1 in
        -u|--username)
            username="$2"
            shift 2
            ;;
        -p|--password)
            password="$2"
            shift 2
            ;;
        --threads)
            threads="$2"
            shift 2
            ;;
        --timeout)
            timeout_sec="$2"
            shift 2
            ;;
        --aggressive)
            aggressive=true
            shift
            ;;
        --brute-force)
            brute_force=true
            shift
            ;;
        --proxy)
            proxy="$2"
            shift 2
            ;;
        --wordlist)
            wordlist_file="$2"
            shift 2
            ;;
        --max-attempts)
            max_attempts="$2"
            shift 2
            ;;
        --csrf-field)
            csrf_field="$2"
            shift 2
            ;;
        --verify-ssl)
            verify_ssl=true
            shift
            ;;
        -o|--output)
            output_file="$2"
            shift 2
            ;;
        --html-report)
            html_report_file="$2"
            shift 2
            ;;
        --report-dir)
            report_dir="$2"
            shift 2
            ;;
        --sync-routes)
            sync_routes_flag=true
            shift
            ;;
        --no-sync)
            sync_routes_flag=false
            shift
            ;;
        --no-validate)
            no_validate_flag=true
            shift
            ;;
        -v|--verbose)
            verbose=true
            shift
            ;;
        --dry-run)
            dry_run_flag=true
            shift
            ;;
        -h|--help)
            show_banner
            show_help
            exit 0
            ;;
        -*)
            echo -e "${RED}Unknown option: $1${NC}"
            show_help
            exit 1
            ;;
        *)
            if [ -z "$TARGET_URL" ]; then
                TARGET_URL="$1"
            else
                echo -e "${RED}Unexpected argument: $1${NC}"
                show_help
                exit 1
            fi
            shift
            ;;
    esac
done

# --- Main Execution ---
main() {
    show_banner

    if [ -z "$TARGET_URL" ]; then
        log_error "Target URL is required"
        show_help
        exit 1
    fi

    # Validate URL format
    if ! [[ "$TARGET_URL" =~ ^https?:// ]]; then
        log_error "Invalid URL format. Must start with http:// or https://"
        show_help
        exit 1
    fi

    # Validate URL reachability with retry prompt (unless --no-validate is set)
    if [ "$no_validate_flag" = false ]; then
        if ! validate_target_url "$TARGET_URL"; then
            log_warning "Target URL validation failed"
            echo ""
            echo -e "${BOLD}${YELLOW}The target URL could not be reached. Would you like to:${NC}"
            echo "  1) Enter a different URL"
            echo "  2) Skip URL validation and proceed anyway"
            echo "  3) Exit"
            echo ""
            read -r -p "Choose an option [1]: " choice
            
            case "${choice:-1}" in
                1)
                    echo ""
                    if ! prompt_for_url; then
                        log_error "Maximum retry attempts ($MAX_URL_RETRIES) exceeded. Exiting."
                        exit 1
                    fi
                    ;;
                2)
                    log_warning "Proceeding without URL validation"
                    echo ""
                    ;;
                3|"")
                    log_info "Exiting. Please run again with a valid URL."
                    exit 0
                    ;;
                *)
                    log_error "Invalid option. Exiting."
                    exit 1
                    ;;
            esac
        else
            echo ""
            echo -e "${GREEN}* Target URL validated successfully${NC}"
            echo ""
        fi
    else
        echo -e "${YELLOW}* URL validation skipped (--no-validate)${NC}"
        echo ""
    fi

    check_requirements
    setup_virtualenv
    setup_reporting

    sync_routes

    build_python_args

    if [ "$dry_run_flag" = true ]; then
        dry_run
        exit 0
    fi

    run_scan
    exit $?
}

main "$@"
