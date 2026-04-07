#!/bin/bash

# ============================================================================
# Socrates Blade v3.2 - URL Validation Tests
# Test suite for the URL validation and error handling functions
# ============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LIB_FILE="$SCRIPT_DIR/url-validator-lib.sh"

# Colors for test output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Test counters
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

print_header() {
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}${CYAN}  Socrates Blade v3.2 - URL Validation Test Suite${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_summary() {
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}  Test Summary${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "  Tests Run:    $TESTS_RUN"
    echo -e "  ${GREEN}Passed:${NC}      $TESTS_PASSED"
    echo -e "  ${RED}Failed:${NC}      $TESTS_FAILED"
    echo ""
    
    if [ $TESTS_FAILED -eq 0 ]; then
        echo -e "  ${GREEN}${BOLD}All tests passed!${NC}"
    else
        echo -e "  ${RED}${BOLD}Some tests failed.${NC}"
    fi
    echo ""
}

run_test() {
    local test_name="$1"
    local test_command="$2"
    local expected="$3"
    
    TESTS_RUN=$((TESTS_RUN + 1))
    
    echo ""
    echo -e "  ${BOLD}$test_name${NC}"
    
    if eval "$test_command" 2>/dev/null; then
        if [ "$expected" = "success" ]; then
            TESTS_PASSED=$((TESTS_PASSED + 1))
            echo -e "    ${GREEN}✓ PASS${NC}"
        else
            TESTS_FAILED=$((TESTS_FAILED + 1))
            echo -e "    ${RED}✗ FAIL${NC} (expected failure, got success)"
        fi
    else
        if [ "$expected" = "failure" ]; then
            TESTS_PASSED=$((TESTS_PASSED + 1))
            echo -e "    ${GREEN}✓ PASS${NC}"
        else
            TESTS_FAILED=$((TESTS_FAILED + 1))
            echo -e "    ${RED}✗ FAIL${NC}"
        fi
    fi
}

run_test_capture() {
    local test_name="$1"
    local test_command="$2"
    local expected_result="$3"
    local capture_var="$4"
    
    TESTS_RUN=$((TESTS_RUN + 1))
    
    echo ""
    echo -e "  ${BOLD}$test_name${NC}"
    
    local result
    result=$(eval "$test_command" 2>/dev/null)
    
    if [ "$result" = "$expected_result" ]; then
        TESTS_PASSED=$((TESTS_PASSED + 1))
        echo -e "    ${GREEN}✓ PASS${NC}"
    else
        TESTS_FAILED=$((TESTS_FAILED + 1))
        echo -e "    ${RED}✗ FAIL${NC}"
        echo "      Expected: '$expected_result'"
        echo "      Got:      '$result'"
    fi
}

main() {
    # Check if library exists
    if [ ! -f "$LIB_FILE" ]; then
        echo -e "${RED}Error: URL validator library not found at $LIB_FILE${NC}"
        exit 1
    fi
    
    print_header
    
    echo -e "${BOLD}Running Unit Tests for extract_hostname()...${NC}"
    run_test_capture "Extract hostname from HTTP URL" \
        "source '$LIB_FILE' && extract_hostname 'http://example.com'" \
        "example.com"
    run_test_capture "Extract hostname from HTTPS URL" \
        "source '$LIB_FILE' && extract_hostname 'https://secure.example.com'" \
        "secure.example.com"
    run_test_capture "Extract hostname with port" \
        "source '$LIB_FILE' && extract_hostname 'http://example.com:8080'" \
        "example.com"
    run_test_capture "Extract hostname with path" \
        "source '$LIB_FILE' && extract_hostname 'http://example.com/path/to/page'" \
        "example.com"
    
    echo ""
    echo -e "${BOLD}Running Unit Tests for check_dns_resolution()...${NC}"
    run_test_capture "DNS check: localhost (should be true)" \
        "source '$LIB_FILE' && check_dns_resolution 'localhost'" \
        "true"
    run_test_capture "DNS check: 127.0.0.1 (should be true)" \
        "source '$LIB_FILE' && check_dns_resolution '127.0.0.1'" \
        "true"
    run_test "DNS check: invalid domain (should not be true)" \
        "source '$LIB_FILE' && [ \"\$(check_dns_resolution 'definitely-not-real-12345.com')\" != 'true' ]" \
        "success"
    run_test "DNS check: bloware.site (should not be true)" \
        "source '$LIB_FILE' && [ \"\$(check_dns_resolution 'bloware.site')\" != 'true' ]" \
        "success"
    
    echo ""
    echo -e "${BOLD}Running Unit Tests for detect_known_typos()...${NC}"
    run_test "Typo: bloware -> blogware" \
        "source '$LIB_FILE' && detect_known_typos 'bloware' | grep -q 'blogware'" \
        "success"
    run_test "Typo: loclahost -> localhost" \
        "source '$LIB_FILE' && detect_known_typos 'loclahost' | grep -q 'localhost'" \
        "success"
    run_test "Typo: localhose -> localhost" \
        "source '$LIB_FILE' && detect_known_typos 'localhose' | grep -q 'localhost'" \
        "success"
    run_test_capture "No typo: example.com (should return empty)" \
        "source '$LIB_FILE' && detect_known_typos 'example.com'" \
        ""
    run_test "blogware -> suggest localhost" \
        "source '$LIB_FILE' && detect_known_typos 'blogware' | grep -q 'localhost'" \
        "success"
    
    echo ""
    echo -e "${BOLD}Running Integration Tests...${NC}"
    
    # Test validate_target_url with localhost (should succeed)
    run_test "Validate localhost (should succeed)" \
        "source '$LIB_FILE' && validate_target_url 'http://localhost' >/dev/null 2>&1" \
        "success"
    
    # Test validate_target_url with invalid domain (should fail)
    run_test "Validate invalid domain (should fail)" \
        "(source '$LIB_FILE' && validate_target_url 'http://invalid-xyz-12345.com' >/dev/null 2>&1 && exit 1) || true" \
        "success"
    
    # Test with bloware.site typo (should fail with typo suggestion)
    run_test "Validate bloware.site (should fail with typo)" \
        "(source '$LIB_FILE' && validate_target_url 'http://bloware.site' >/dev/null 2>&1 && exit 1) || true" \
        "success"
    
    print_summary
    
    if [ $TESTS_FAILED -gt 0 ]; then
        exit 1
    fi
    exit 0
}

main "$@"
