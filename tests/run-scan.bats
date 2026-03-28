#!/usr/bin/env bats

# Define project root and script directory
# Assuming the current working directory is the project root for bats tests
PROJECT_ROOT="/var/www/blogware/public_html"
SCRIPT_TO_TEST="$PROJECT_ROOT/docs/security-testing/socrates-blade/run-scan.sh"
TEST_DIR="$PROJECT_ROOT/tests"

# --- Mock Executables ---
# These mocks will be placed in a temporary directory and added to PATH for tests
MOCK_DIR="$TEST_DIR/mock_bin"
MOCK_PHP_EXPORTER="$MOCK_DIR/export_routes.php" # Script itself
MOCK_PYTHON_SCANNER="$MOCK_DIR/socrates-blade.py" # Script itself
MOCK_VENV_ACTIVATE="$MOCK_DIR/activate" # Mock activate script for python3 -m venv
MOCK_PYTHON3_WRAPPER="$MOCK_DIR/python3_wrapper.sh"

# --- Setup and Teardown ---

setup() {
    # Create mock directory and place mock scripts
    mkdir -p "$MOCK_DIR"

    # Define the base directory for mock venv creation and ensure it exists.
    local mock_venv_base_dir="$TEST_DIR/mock_venv_base"
    mkdir -p "$mock_venv_base_dir"

    # Mock PHP exporter
    cat << 'EOF_PHP_EXPORTER' > "$MOCK_PHP_EXPORTER"
#!/bin/bash
# Mock PHP route exporter
OUTPUT_FILE="$1"
OUTPUT_DIR=$(dirname "$OUTPUT_FILE")
mkdir -p "$OUTPUT_DIR"
echo '{
  "routes": [
    {"url": "/test-route", "method": "GET"},
    {"url": "/admin/dashboard", "method": "GET"},
    {"url": "/login", "method": "POST"}
  ]
}' > "$OUTPUT_FILE"
exit 0
EOF_PHP_EXPORTER
    chmod +x "$MOCK_PHP_EXPORTER"

    # Mock Python scanner
    cat << 'EOF_PYTHON_SCANNER' > "$MOCK_PYTHON_SCANNER"
#!/usr/bin/env python3
# Mock Python security scanner script.
import sys, json, os
DEFAULT_THREADS = 5; DEFAULT_TIMEOUT = 5; DEFAULT_MAX_ATTEMPTS = 10; DEFAULT_CSRF_FIELD = "login_form"
def main():
    routes_file = None; target_url = None; output_file = None; html_report_file = None
    threads = DEFAULT_THREADS; timeout_sec = DEFAULT_TIMEOUT; max_attempts = DEFAULT_MAX_ATTEMPTS; csrf_field = DEFAULT_CSRF_FIELD
    aggressive = False; brute_force = False; verify_ssl = False; proxy = None; username = None; password = None; wordlist = None
    args = sys.argv[1:]; i = 0
    while i < len(args):
        arg = args[i]
        if arg == "--routes-file" and i + 1 < len(args): routes_file = args[i+1]; i += 1
        elif arg == "-o" and i + 1 < len(args): output_file = args[i+1]; i += 1
        elif arg == "--html-report" and i + 1 < len(args): html_report_file = args[i+1]; i += 1
        elif arg == "-u" and i + 1 < len(args): username = args[i+1]; i += 1
        elif arg == "-p" and i + 1 < len(args): password = args[i+1]; i += 1
        elif arg == "--threads" and i + 1 < len(args): threads = int(args[i+1]); i += 1
        elif arg == "--timeout" and i + 1 < len(args): timeout_sec = int(args[i+1]); i += 1
        elif arg == "--max-attempts" and i + 1 < len(args): max_attempts = int(args[i+1]); i += 1
        elif arg == "--csrf-field" and i + 1 < len(args): csrf_field = args[i+1]; i += 1
        elif arg == "--proxy" and i + 1 < len(args): proxy = args[i+1]; i += 1
        elif arg == "--wordlist" and i + 1 < len(args): wordlist = args[i+1]; i += 1
        elif arg == "--aggressive": aggressive = True
        elif arg == "--brute-force": brute_force = True
        elif arg == "--verify-ssl": verify_ssl = True
        elif target_url is None: target_url = arg
        i += 1
    if not target_url: print("Mock Python script error: Target URL is required.", file=sys.stderr); sys.exit(1)
    if not routes_file: print("Mock Python script error: --routes-file is required.", file=sys.stderr); sys.exit(1)
    if not output_file and not html_report_file: print("Mock Python script error: Either -o or --html-report is required.", file=sys.stderr); sys.exit(1)
    if not os.path.exists(routes_file): print(f"Mock Python script error: Routes file not found: {routes_file}", file=sys.stderr); sys.exit(1)
    report_data = {"mock_scan_info": {"target": target_url, "username": username, "threads": threads, "timeout_sec": timeout_sec, "aggressive": aggressive, "brute_force": brute_force, "verify_ssl": verify_ssl, "proxy": proxy, "max_attempts": max_attempts, "csrf_field": csrf_field}, "mock_vulnerabilities": [{"name": "Mock SQL Injection", "severity": "High", "url": "/login", "payload": "admin' --"}, {"name": "Mock XSS", "severity": "Medium", "url": "/test-route", "payload": "<script>alert('XSS')</script>"}]}
    try:
        if output_file: with open(output_file, 'w') as f: json.dump(report_data, f, indent=2)
        if html_report_file: html_content = f"<html><body><h1>Mock Scan Report for {target_url}</h1><p>This is a mock HTML report.</p><pre>{json.int(report_data, indent=2)}</pre></body></html>"; with open(html_report_file, 'w') as f: f.write(html_content)
        sys.exit(0)
    except IOError as e: print(f"Mock Python script error: Could not write to output file: {e}", file=sys.stderr); sys.exit(1)
if __name__ == "__main__": main()
EOF_PYTHON_SCANNER
    chmod +x "$MOCK_PYTHON_SCANNER"

    # Mock venv activate script
    cat << 'EOF_MOCK_ACTIVATE_SCRIPT' > "$MOCK_VENV_ACTIVATE"
#!/bin/bash
# Mock venv activate script
VENV_DIR="$1"
if [ -z "$VENV_DIR" ]; then echo "Mock activate script error: Virtual environment path not provided." >&2; exit 1; fi
mkdir -p "$VENV_DIR"; mkdir -p "$VENV_DIR/bin"
cat << 'EOF_ACTIVATE' > "$VENV_DIR/bin/activate"
#!/bin/bash
# Dummy activate script content for testing
export VIRTUAL_ENV="$(dirname "$(dirname "$0")")"
export PATH="$VIRTUAL_ENV/bin:$PATH"
echo "Mock venv activated: $VIRTUAL_ENV"
EOF_ACTIVATE
chmod +x "$VENV_DIR/bin/activate"
echo "Mock virtual environment setup at $VENV_DIR, created activate script at $VENV_DIR/bin/activate"
exit 0
EOF_MOCK_ACTIVATE_SCRIPT
    chmod +x "$MOCK_VENV_ACTIVATE"

    # Wrapper script to mock 'python3 -m venv'
    # Using printf for the template and passing the path as an argument.
    # This avoids complex heredoc parsing issues within the bats test file.
    local wrapper_template
    wrapper_template=$(printf '
#!/bin/bash
# Wrapper script for python3 to mock '''python3 -m venv'''.
MOCK_VENV_BASE_DIR="%s"
mkdir -p "$MOCK_VENV_BASE_DIR"
if [[ "$@" == "-m venv"* ]]; then
    echo "Mocking python3 -m venv..."
    VENV_PATH=$(echo "$@" | sed -n '''s/.*-m venv \(.*\)/\1/p''')
    if [ -z "$VENV_PATH" ]; then
        echo "Mock Python wrapper error: Could not extract venv path from '''\$@'''." >&2
        exit 1
    fi
    FULL_VENV_TARGET_PATH="$MOCK_VENV_BASE_DIR/$VENV_PATH"
    "$MOCK_DIR/activate" "$FULL_VENV_TARGET_PATH"
    export PATH="$FULL_VENV_TARGET_PATH/bin:$PATH"
    exit 0
else
    echo "Executing mock python3 with args: $@"
    if command -v /usr/bin/python3 &> /dev/null; then
        /usr/bin/python3 "$@"
    else
        echo "Mock Python wrapper error: python3 not found or not mocked correctly." >&2
        exit 1
    fi
fi
')

    # Write the content using printf, embedding the path directly.
    printf "$wrapper_template" "$mock_venv_base_dir" > "$MOCK_PYTHON3_WRAPPER"
    chmod +x "$MOCK_PYTHON3_WRAPPER"

    # Create dummy files required by the script
    touch "$PROJECT_ROOT/scanrequirements.txt"
    mkdir -p "$PROJECT_ROOT/docs/security-testing/socrates-blade"
    touch "$PROJECT_ROOT/docs/security-testing/socrates-blade/export_routes.php"
    touch "$PROJECT_ROOT/config.php"
    touch "$PROJECT_ROOT/composer.json"

    # Setup PATH to include mock executables
    export PATH="$MOCK_DIR:$PATH"

    # Make 'python3' command point to our wrapper
    ln -sf "$MOCK_PYTHON3_WRAPPER" "$MOCK_DIR/python3"

    # Ensure 'php' command points to our mock if not found
    if ! command -v php &> /dev/null; then
        ln -sf "$MOCK_PHP_EXPORTER" "$MOCK_DIR/php"
    fi

    # Ensure the directory for routes.json exists
    mkdir -p "$PROJECT_ROOT/docs/security-testing/socrates-blade/reports"
    touch "$PROJECT_ROOT/docs/security-testing/socrates-blade/routes.json"
}

teardown() {
    # Clean up mock directory and remove temp files
    rm -rf "$MOCK_DIR"
    rm -rf "$TEST_DIR/mock_venv_base" # Clean up the venv base dir
    rm -f "$PROJECT_ROOT/scanrequirements.txt"
    rm -f "$PROJECT_ROOT/docs/security-testing/socrates-blade/export_routes.php"
    rm -f "$PROJECT_ROOT/docs/security-testing/socrates-blade/routes.json"
    rm -f "$PROJECT_ROOT/config.php"
    rm -f "$PROJECT_ROOT/composer.json"
    rm -rf "$PROJECT_ROOT/docs/security-testing/socrates-blade/reports"
    # Remove the mock python3 link if it was created.
    if [ -L "$MOCK_DIR/python3" ]; then
        rm "$MOCK_DIR/python3"
    fi
    # Reset PATH
    export PATH="$(echo "$PATH" | sed "s|^$MOCK_DIR:||")"
}

# --- Tests ---

@test "Show help message" {
    run "$SCRIPT_TO_TEST" --help
    assert_output --partial "Usage: $SCRIPT_TO_TEST <target_url> [options]"
    assert_output --partial "Required Arguments:"
    assert_output --partial "Examples:"
}

@test "Missing target URL" {
    run "$SCRIPT_TO_TEST"
    assert_error
    assert_output --partial "Target URL is required"
}

@test "Invalid URL format" {
    run "$SCRIPT_TO_TEST" "invalid-url"
    assert_error
    assert_output --partial "Invalid URL format. Must start with http:// or https://"
}

@test "Missing Python 3 (simulated)" {
    local original_PATH="$PATH"
    export PATH=""
    run "$SCRIPT_TO_TEST" http://localhost
    assert_error
    assert_output --partial "Python 3 is required but not installed."
    export PATH="$original_PATH"
}

@test "Successful route synchronization and scan" {
    run "$SCRIPT_TO_TEST" http://localhost -o /tmp/report.json
    assert_success
    assert_output --partial "Synchronizing routes from Blogware application..."
    assert_output --partial "Routes synchronized successfully"
    assert_output --partial "Starting security scan..."
    assert_output --partial "Security scan completed successfully"
    assert [ -f "$PROJECT_ROOT/docs/security-testing/socrates-blade/routes.json" ]
    assert [ -f "/tmp/report.json" ]
    assert [ -s "/tmp/report.json" ]
}

@test "Skip route synchronization" {
    run "$SCRIPT_TO_TEST" --no-sync http://localhost -o /tmp/report.json
    assert_success
    refute_output --partial "Synchronizing routes from Blogware application..."
    assert_output --partial "Starting security scan..."
    assert_output --partial "Security scan completed successfully"
    assert [ -f "/tmp/report.json" ]
    assert [ -s "/tmp/report.json" ]
}

@test "Authenticated scan with reports" {
    run "$SCRIPT_TO_TEST" http://localhost -u administrator -p 'P@ssw0rd' -o /tmp/report.json --html-report /tmp/report.html
    assert_success
    assert_output --partial "Starting security scan..."
    assert_output --partial "Security scan completed successfully"
    assert [ -f "/tmp/report.json" ]
    assert [ -s "/tmp/report.json" ]
    assert [ -f "/tmp/report.html" ]
    assert [ -s "/tmp/report.html" ]
    assert_file_content /tmp/report.html "Mock Scan Report for http://localhost"
}

@test "Aggressive scan with brute force and custom threads" {
    run "$SCRIPT_TO_TEST" --aggressive --brute-force --threads 10 http://localhost -o /tmp/report.json
    assert_success
    assert_output --partial "Starting security scan..."
    assert_output --partial "Security scan completed successfully"
    assert [ -f "/tmp/report.json" ]
    report_content=$(cat /tmp/report.json)
    assert_json_value "$report_content" "mock_scan_info.threads" "10"
    assert_json_value "$report_content" "mock_scan_info.aggressive" "true"
    assert_json_value "$report_content" "mock_scan_info.brute_force" "true"
}

@test "Scan via proxy" {
    run "$SCRIPT_TO_TEST" --proxy http://127.0.0.1:8080 http://localhost -o /tmp/report.json
    assert_success
    assert_output --partial "Starting security scan..."
    assert_output --partial "Security scan completed successfully"
    assert [ -f "/tmp/report.json" ]
    report_content=$(cat /tmp/report.json)
    assert_json_value "$report_content" "mock_scan_info.proxy" "http://127.0.0.1:8080"
}

@test "Dry run mode" {
    run "$SCRIPT_TO_TEST" --dry-run http://localhost -u admin -p 'secret'
    assert_success
    assert_output --partial "Dry Run Mode - Commands that would be executed:"
    assert_output --partial "1. Virtual Environment:"
    assert_output --partial "2. Route Sync:"
    assert_output --partial "   php $SCRIPT_DIR/export_routes.php > $PROJECT_ROOT/docs/security-testing/socrates-blade/routes.json"
    assert_output --partial "3. Security Scan:"
    assert_output --partial "   python3 $MOCK_DIR/socrates-blade.py http://localhost -u admin -p secret --routes-file $PROJECT_ROOT/docs/security-testing/socrates-blade/routes.json"
}

assert_json_value() {
    local json_string="$1"; local key_path="$2"; local expected_value="$3"; local actual_value
    if ! command -v jq &> /dev/null; then echo "Warning: jq not found. Cannot verify JSON values." >&2; return 1; fi
    actual_value=$(echo "$json_string" | jq -r "$key_path")
    [ "$actual_value" = "$expected_value" ] || { echo "Expected JSON value for '$key_path' to be '$expected_value', but got '$actual_value'" >&2; return 1; }
}
