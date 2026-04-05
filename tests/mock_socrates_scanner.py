#!/usr/bin/env python3
# Mock Python security scanner script.
# This script simulates the socrates-blade.py scanner for testing.

import sys
import json
import os

# Define default values
DEFAULT_THREADS = 5
DEFAULT_TIMEOUT = 5
DEFAULT_MAX_ATTEMPTS = 10
DEFAULT_CSRF_FIELD = "login_form"

def main():
    routes_file = None
    target_url = None
    output_file = None
    html_report_file = None
    threads = DEFAULT_THREADS
    timeout_sec = DEFAULT_TIMEOUT
    max_attempts = DEFAULT_MAX_ATTEMPTS
    csrf_field = DEFAULT_CSRF_FIELD
    aggressive = False
    brute_force = False
    verify_ssl = False
    proxy = None
    username = None
    password = None
    wordlist = None

    args = sys.argv[1:]
    i = 0
    while i < len(args):
        arg = args[i]
        if arg == "--routes-file" and i + 1 < len(args):
            routes_file = args[i+1]
            i += 1
        elif arg == "-o" and i + 1 < len(args):
            output_file = args[i+1]
            i += 1
        elif arg == "--html-report" and i + 1 < len(args):
            html_report_file = args[i+1]
            i += 1
        elif arg == "-u" and i + 1 < len(args):
            username = args[i+1]
            i += 1
        elif arg == "-p" and i + 1 < len(args):
            password = args[i+1]
            i += 1
        elif arg == "--threads" and i + 1 < len(args):
            threads = int(args[i+1])
            i += 1
        elif arg == "--timeout" and i + 1 < len(args):
            timeout_sec = int(args[i+1])
            i += 1
        elif arg == "--max-attempts" and i + 1 < len(args):
            max_attempts = int(args[i+1])
            i += 1
        elif arg == "--csrf-field" and i + 1 < len(args):
            csrf_field = args[i+1]
            i += 1
        elif arg == "--proxy" and i + 1 < len(args):
            proxy = args[i+1]
            i += 1
        elif arg == "--wordlist" and i + 1 < len(args):
            wordlist = args[i+1]
            i += 1
        elif arg == "--aggressive":
            aggressive = True
        elif arg == "--brute-force":
            brute_force = True
        elif arg == "--verify-ssl":
            verify_ssl = True
        elif target_url is None: # Assume first non-flag arg is target_url
            target_url = arg
        i += 1

    # Basic validation for mock
    if not target_url:
        print("Mock Python script error: Target URL is required.", file=sys.stderr)
        sys.exit(1)
    if not routes_file:
        print("Mock Python script error: --routes-file is required.", file=sys.stderr)
        sys.exit(1)
    if not output_file and not html_report_file:
        print("Mock Python script error: Either -o or --html-report is required.", file=sys.stderr)
        sys.exit(1)

    # Check if routes file exists
    if not os.path.exists(routes_file):
        print(f"Mock Python script error: Routes file not found: {routes_file}", file=sys.stderr)
        sys.exit(1)

    # Create a dummy report
    report_data = {
        "mock_scan_info": {
            "target": target_url,
            "username": username,
            "threads": threads,
            "timeout_sec": timeout_sec,
            "aggressive": aggressive,
            "brute_force": brute_force,
            "verify_ssl": verify_ssl,
            "proxy": proxy,
            "max_attempts": max_attempts,
            "csrf_field": csrf_field
        },
        "mock_vulnerabilities": [
            {"name": "Mock SQL Injection", "severity": "High", "url": "/login", "payload": "admin' --"},
            {"name": "Mock XSS", "severity": "Medium", "url": "/test-route", "payload": "<script>alert('XSS')</script>"}
        ]
    }

    try:
        if output_file:
            with open(output_file, 'w') as f:
                json.dump(report_data, f, indent=2)
        if html_report_file:
            # Create a dummy HTML report
            html_content = f"""
            <html><body>
            <h1>Mock Scan Report for {target_url}</h1>
            <p>This is a mock HTML report.</p>
            <pre>{json.dumps(report_data, indent=2)}</pre>
            </body></html>
            """
            with open(html_report_file, 'w') as f:
                f.write(html_content)
        sys.exit(0)
    except IOError as e:
        print(f"Mock Python script error: Could not write to output file: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
