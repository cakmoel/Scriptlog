#!/usr/bin/env bats

# =========================================================
# Socrates Blade v3.2: Unit Tests
# Blogware/Scriptlog CMS Security Scanner
# =========================================================

SCRIPT_DIR="/var/www/blogware/public_html/docs/security-testing/socrates-blade"
SCRIPT="$SCRIPT_DIR/run-scan.sh"
PYTHON_SCRIPT="$SCRIPT_DIR/socrates-blade.py"
EXPORT_PHP="$SCRIPT_DIR/export_routes.php"
ROUTES_JSON="$SCRIPT_DIR/routes.json"

setup() {
    TEST_DIR="$(mktemp -d)"
    export LOG_FILE="$TEST_DIR/test.log"
    export REPORT_DIR="$TEST_DIR/reports"
}

teardown() {
    rm -rf "$TEST_DIR"
}

# =========================================================
# Test: Help Output
# =========================================================

@test "show help with -h flag" {
    run "$SCRIPT" -h
    [ "$status" -eq 0 ]
    [[ "$output" == *"Usage:"* ]]
    [[ "$output" == *"target_url"* ]]
    [[ "$output" == *"-u, --username"* ]]
}

@test "show help with --help flag" {
    run "$SCRIPT" --help
    [ "$status" -eq 0 ]
    [[ "$output" == *"Usage:"* ]]
    [[ "$output" == *"Examples:"* ]]
}

# =========================================================
# Test: URL Validation
# =========================================================

@test "missing target URL shows error" {
    run "$SCRIPT"
    [ "$status" -eq 1 ]
    [[ "$output" == *"Target URL is required"* ]]
}

@test "invalid URL format (no http/https) shows error" {
    run "$SCRIPT" "ftp://example.com"
    [ "$status" -eq 1 ]
    [[ "$output" == *"Invalid URL format"* ]]
}

@test "invalid URL format (no protocol) shows error" {
    run "$SCRIPT" "example.com"
    [ "$status" -eq 1 ]
    [[ "$output" == *"Invalid URL format"* ]]
}

@test "valid http URL passes validation" {
    run "$SCRIPT" --dry-run "http://localhost"
    [ "$status" -eq 0 ]
}

@test "valid https URL passes validation" {
    run "$SCRIPT" --dry-run "https://localhost"
    [ "$status" -eq 0 ]
}

# =========================================================
# Test: Unknown Options
# =========================================================

@test "unknown option shows error" {
    run "$SCRIPT" --unknown-option "http://localhost"
    [ "$status" -eq 1 ]
    [[ "$output" == *"Unknown option"* ]]
}

# =========================================================
# Test: Argument Parsing
# =========================================================

@test "parse -u username flag" {
    run "$SCRIPT" --dry-run "http://localhost" -u testuser
    [ "$status" -eq 0 ]
}

@test "parse -p password flag" {
    run "$SCRIPT" --dry-run "http://localhost" -p "secretpass"
    [ "$status" -eq 0 ]
}

@test "parse --threads flag" {
    run "$SCRIPT" --dry-run "http://localhost" --threads 10
    [ "$status" -eq 0 ]
}

@test "parse --timeout flag" {
    run "$SCRIPT" --dry-run "http://localhost" --timeout 30
    [ "$status" -eq 0 ]
}

@test "parse --aggressive flag" {
    run "$SCRIPT" --dry-run "http://localhost" --aggressive
    [ "$status" -eq 0 ]
}

@test "parse --brute-force flag" {
    run "$SCRIPT" --dry-run "http://localhost" --brute-force
    [ "$status" -eq 0 ]
}

@test "parse --proxy flag" {
    run "$SCRIPT" --dry-run "http://localhost" --proxy "http://127.0.0.1:8080"
    [ "$status" -eq 0 ]
}

@test "parse --wordlist flag" {
    run "$SCRIPT" --dry-run "http://localhost" --wordlist "/tmp/wordlist.txt"
    [ "$status" -eq 0 ]
}

@test "parse --max-attempts flag" {
    run "$SCRIPT" --dry-run "http://localhost" --max-attempts 5
    [ "$status" -eq 0 ]
}

@test "parse --csrf-field flag" {
    run "$SCRIPT" --dry-run "http://localhost" --csrf-field "token_field"
    [ "$status" -eq 0 ]
}

@test "parse --verify-ssl flag" {
    run "$SCRIPT" --dry-run "http://localhost" --verify-ssl
    [ "$status" -eq 0 ]
}

@test "parse -o output flag" {
    run "$SCRIPT" --dry-run "http://localhost" -o "report.json"
    [ "$status" -eq 0 ]
}

@test "parse --html-report flag" {
    run "$SCRIPT" --dry-run "http://localhost" --html-report "report.html"
    [ "$status" -eq 0 ]
}

@test "parse --report-dir flag" {
    run "$SCRIPT" --dry-run "http://localhost" --report-dir "/tmp/reports"
    [ "$status" -eq 0 ]
}

@test "parse --no-sync flag" {
    run "$SCRIPT" --dry-run "http://localhost" --no-sync
    [ "$status" -eq 0 ]
}

@test "parse -v verbose flag" {
    run "$SCRIPT" --dry-run "http://localhost" -v
    [ "$status" -eq 0 ]
}

@test "parse --verbose flag" {
    run "$SCRIPT" --dry-run "http://localhost" --verbose
    [ "$status" -eq 0 ]
}

@test "parse multiple flags together" {
    run "$SCRIPT" --dry-run "http://localhost" \
        -u admin \
        -p "password" \
        --aggressive \
        --threads 10 \
        --timeout 30
    [ "$status" -eq 0 ]
}

# =========================================================
# Test: Duplicate Target URL
# =========================================================

@test "duplicate target URL shows error" {
    run "$SCRIPT" "http://localhost" "http://example.com"
    [ "$status" -eq 1 ]
    [[ "$output" == *"Unexpected argument"* ]]
}

# =========================================================
# Test: Dry Run Mode
# =========================================================

@test "dry-run shows commands without executing" {
    run "$SCRIPT" --dry-run "http://localhost"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Dry Run Mode"* ]]
    [[ "$output" == *"Virtual Environment"* ]]
    [[ "$output" == *"Route Sync"* ]]
    [[ "$output" == *"Security Scan"* ]]
    [[ "$output" == *"python3"* ]]
}

@test "dry-run with authentication shows username in output" {
    run "$SCRIPT" --dry-run "http://localhost" -u testadmin -p "secret"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Dry Run Mode"* ]]
}

@test "dry-run with all options shows all commands" {
    run "$SCRIPT" --dry-run "http://localhost" \
        -u admin \
        -p "pass" \
        --aggressive \
        --brute-force \
        --threads 10
    [ "$status" -eq 0 ]
    [[ "$output" == *"python3"* ]]
    [[ "$output" == *"socrates-blade.py"* ]]
    [[ "$output" == *"--aggressive"* ]]
    [[ "$output" == *"--brute-force"* ]]
}

# =========================================================
# Test: Banner Display
# =========================================================

@test "banner is displayed in dry-run" {
    run "$SCRIPT" --dry-run "http://localhost"
    [ "$status" -eq 0 ]
    [[ "$output" == *"Framework"* ]]
    [[ "$output" == *"v3.2"* ]]
}

# =========================================================
# Test: --no-sync Flag
# =========================================================

@test "--no-sync disables route synchronization" {
    run "$SCRIPT" --dry-run "http://localhost" --no-sync
    [ "$status" -eq 0 ]
    [[ "$output" == *"Route synchronization disabled by user"* ]]
}

# =========================================================
# Test: Log File Creation
# =========================================================

@test "creates log file in dry-run" {
    run bash -c 'LOG_FILE="'"$TEST_DIR"'/custom-test.log" "'"$SCRIPT"'" --dry-run http://localhost > /dev/null 2>&1'
    [ -f "$TEST_DIR/custom-test.log" ]
}

# =========================================================
# Test: Report Directory
# =========================================================

@test "creates report directory" {
    REPORT_DIR="$TEST_DIR/test-reports" run "$SCRIPT" --dry-run "http://localhost"
    [ -d "$TEST_DIR/test-reports" ]
}

# =========================================================
# Test: Edge Cases
# =========================================================

@test "handles URL with port number" {
    run "$SCRIPT" --dry-run "http://localhost:8080"
    [ "$status" -eq 0 ]
}

@test "handles URL with path" {
    run "$SCRIPT" --dry-run "http://localhost/blog"
    [ "$status" -eq 0 ]
}

@test "handles URL with query string" {
    run "$SCRIPT" --dry-run "http://localhost?test=1"
    [ "$status" -eq 0 ]
}

@test "handles password with special characters" {
    run "$SCRIPT" --dry-run "http://localhost" -p "P@ssw0rd!\$#%^&*()"
    [ "$status" -eq 0 ]
}

@test "handles username with special characters" {
    run "$SCRIPT" --dry-run "http://localhost" -u "user@example.com"
    [ "$status" -eq 0 ]
}

# =========================================================
# Test: Exit Codes
# =========================================================

@test "returns 0 on successful dry-run" {
    run "$SCRIPT" --dry-run "http://localhost"
    [ "$status" -eq 0 ]
}

@test "returns 1 on missing target URL" {
    run "$SCRIPT"
    [ "$status" -eq 1 ]
}

@test "returns 1 on invalid URL" {
    run "$SCRIPT" "invalid-url"
    [ "$status" -eq 1 ]
}

@test "returns 1 on unknown option" {
    run "$SCRIPT" --unknown "http://localhost"
    [ "$status" -eq 1 ]
}

# =========================================================
# Test: Routes Coherence
# =========================================================

@test "export_routes.php generates valid JSON" {
    run bash -c 'cd /var/www/blogware/public_html && php "'"$EXPORT_PHP"'" > /dev/null 2>&1'
    [ "$status" -eq 0 ]
}

@test "routes.json contains expected structure" {
    run bash -c 'test -f "'"$SCRIPT_DIR/routes.json"'"'
    [ "$status" -eq 0 ]
}

@test "routes.json is valid JSON with metadata and routes" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 -c "import json; data = json.load(open(\"routes.json\")); assert \"metadata\" in data and \"routes\" in data"'
    [ "$status" -eq 0 ]
}

@test "routes.json contains frontend routes" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 -c "import json; data = json.load(open(\"routes.json\")); assert \"frontend\" in data[\"routes\"]"'
    [ "$status" -eq 0 ]
}

@test "routes.json contains admin routes" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 -c "import json; data = json.load(open(\"routes.json\")); assert \"admin\" in data[\"routes\"]"'
    [ "$status" -eq 0 ]
}

@test "routes.json contains api routes" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 -c "import json; data = json.load(open(\"routes.json\")); assert \"api\" in data[\"routes\"]"'
    [ "$status" -eq 0 ]
}

@test "routes.json contains public routes" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 -c "import json; data = json.load(open(\"routes.json\")); assert \"public\" in data[\"routes\"]"'
    [ "$status" -eq 0 ]
}

@test "routes.json contains sensitive routes" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 -c "import json; data = json.load(open(\"routes.json\")); assert \"sensitive\" in data[\"routes\"]"'
    [ "$status" -eq 0 ]
}

@test "routes.json has at least 50 routes total" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 -c "
import json
data = json.load(open(\"routes.json\"))
count = sum(len(routes) for routes in data[\"routes\"].values() if isinstance(routes, dict))
assert count >= 50, f\"Expected >= 50 routes, got {count}\"
"'
    [ "$status" -eq 0 ]
}

@test "routes.json has metadata with generator info" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 -c "import json; data = json.load(open(\"routes.json\")); assert data[\"metadata\"][\"meta\"][\"generator\"] == \"Socrates Blade Route Exporter v1.0\""'
    [ "$status" -eq 0 ]
}

# =========================================================
# Test: Python Script Integration
# =========================================================

@test "socrates-blade.py is accessible" {
    run bash -c 'test -f "'"$PYTHON_SCRIPT"'"'
    [ "$status" -eq 0 ]
}

@test "socrates-blade.py shows help" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 "'"$PYTHON_SCRIPT"'" --help > /dev/null 2>&1'
    [ "$status" -eq 0 ]
}

@test "socrates-blade.py accepts target URL" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 "'"$PYTHON_SCRIPT"'" http://localhost --help > /dev/null 2>&1'
    [ "$status" -eq 0 ]
}

@test "socrates-blade.py loads routes successfully" {
    run bash -c 'cd "'"$SCRIPT_DIR"'" && python3 -c "
import json
import sys
sys.path.insert(0, \".\")
import importlib.util
spec = importlib.util.spec_from_file_location(\"socrates_blade\", \"socrates-blade.py\")
module = importlib.util.module_from_spec(spec)
" 2>&1'
    [ "$status" -eq 0 ]
}

# =========================================================
# Test: Comprehensive Flag Combinations
# =========================================================

@test "all scanning flags work together" {
    run "$SCRIPT" --dry-run "http://localhost" \
        -u admin \
        -p "password" \
        --aggressive \
        --brute-force \
        --threads 10 \
        --timeout 30 \
        --wordlist "/tmp/wordlist.txt" \
        --max-attempts 5 \
        --csrf-field "my_token" \
        --verify-ssl \
        -o "output.json" \
        --html-report "report.html" \
        --report-dir "/tmp/reports"
    
    [ "$status" -eq 0 ]
    [[ "$output" == *"Dry Run Mode"* ]]
}

# =========================================================
# Test: build_python_args Function
# =========================================================

@test "build_python_args includes --routes-file" {
    run "$SCRIPT" --dry-run "http://localhost"
    [ "$status" -eq 0 ]
    [[ "$output" == *"--routes-file"* ]]
}

@test "build_python_args includes routes.json path" {
    run "$SCRIPT" --dry-run "http://localhost"
    [ "$status" -eq 0 ]
    [[ "$output" == *"routes.json"* ]]
}
