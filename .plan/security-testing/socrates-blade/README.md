# Socrates Blade v3.2

**Advanced Security Testing Framework for Blogware/Scriptlog CMS**

![Version](https://img.shields.io/badge/version-3.2.0-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![OWASP Top 10](https://img.shields.io/badge/OWASP-Top%2010%202021-red)

---

## Overview

Socrates Blade is a comprehensive security testing framework designed specifically for Blogware/Scriptlog CMS. It provides automated vulnerability scanning with support for OWASP Top 10 2021 categories, brute-force testing, and detailed reporting in multiple formats.

### Features

- **OWASP Top 10 2021 Coverage** - Tests for all major vulnerability categories
- **Route-Aware Scanning** - Understands application structure for targeted testing
- **Aggressive Mode** - Extended testing with configurable delays (up to 30s)
- **Credential Brute-Forcing** - Built-in brute force with custom wordlists
- **Multi-Format Reports** - JSON and HTML output formats
- **CI/CD Integration** - Exit codes, logging, and automation support
- **Local & Remote Testing** - Works with localhost and remote instances

---

## Quick Start

### Prerequisites

- Python 3.8+
- PHP 7.4+ (for route synchronization)
- Blogware/Scriptlog installation (for dynamic routes)

### Installation

```bash
# Clone the repository
cd docs/security-testing/socrates-blade

# Setup virtual environment
python3 -m venv venv
source venv/bin/activate

# Install dependencies
pip install -r scanrequirements.txt
```

### Basic Usage

```bash
# Using the automation wrapper (recommended)
./run-scan.sh http://localhost

# Using Python directly
python3 socrates-blade.py http://localhost

# With authentication (replace <username> and <password> with valid credentials)
./run-scan.sh http://localhost \
    -u <username> \
    -p <password> \
    -o findings.json
```

---

## Usage

### Automation Wrapper (`run-scan.sh`)

The recommended way to run security scans:

```bash
./run-scan.sh <target_url> [options]
```

#### Options

| Option | Description | Default |
|--------|-------------|---------|
| `-u, --username` | Username for authentication | - |
| `-p, --password` | Password for authentication | - |
| `--aggressive` | Enable aggressive testing | false |
| `--brute-force` | Enable brute force attack | false |
| `--threads <n>` | Number of concurrent threads | 5 |
| `--timeout <sec>` | Request timeout in seconds | 5 |
| `--proxy <url>` | HTTP/HTTPS proxy | - |
| `-o, --output <file>` | JSON report file | - |
| `--html-report <file>` | HTML report file | - |
| `--wordlist <file>` | Custom password wordlist | - |
| `--no-sync` | Skip route synchronization | false |
| `--dry-run` | Show commands without executing | false |
| `-h, --help` | Show help message | - |

#### Examples

```bash
# Basic scan (unauthenticated)
./run-scan.sh http://localhost

# Authenticated scan with reports (replace with valid credentials)
./run-scan.sh http://localhost \
    -u <username> \
    -p <password> \
    -o report.json \
    --html-report report.html

# Aggressive scan with brute force
./run-scan.sh https://blog.example.com \
    --aggressive \
    --brute-force \
    --threads 10

# Scan via Burp Suite proxy
./run-scan.sh http://blog.example.com \
    --proxy http://127.0.0.1:8080

# Dry run to see what would execute
./run-scan.sh http://localhost --dry-run
```

### Python Script (`socrates-blade.py`)

Direct Python execution with full control:

```bash
python3 socrates-blade.py <target_url> [options]
```

#### Options

| Option | Description |
|--------|-------------|
| `-u <user>` | Username |
| `-p <pass>` | Password |
| `--aggressive` | Aggressive mode |
| `--brute-force` | Brute force attack |
| `--threads <n>` | Thread count |
| `--timeout <sec>` | Request timeout |
| `--proxy <url>` | Proxy URL |
| `-o <file>` | JSON output |
| `--html-report <file>` | HTML output |
| `--routes-file <file>` | Routes JSON file |

---

## Testing Capabilities

### OWASP Top 10 2021 Coverage

| Category | Vulnerabilities Tested |
|----------|----------------------|
| **A01** - Broken Access Control | IDOR, privilege escalation, auth bypass |
| **A02** - Cryptographic Failures | Session tokens, cookie security, password storage |
| **A03** - Injection | SQLi, XSS, XXE, Path Traversal |
| **A04** - Insecure Design | CSRF, rate limiting |
| **A05** - Security Misconfiguration | Missing headers, debug exposure |
| **A06** - Vulnerable Components | Outdated dependencies |
| **A07** - Auth Failures | Brute force, session hijacking |
| **A08** - Data Integrity Failures | Cookie tampering, token reuse |
| **A09** - Logging Failures | Insufficient audit trail |
| **A10** - SSRF | Server-side request forgery |

### Vulnerability Types

- **SQL Injection** - Error-based, time-based, union-based, boolean blind
- **Cross-Site Scripting (XSS)** - Reflected, stored, DOM-based
- **Path Traversal** - Unix/Windows, encoding bypass
- **SSRF** - Cloud metadata, localhost, protocol smuggling
- **XXE** - XML External Entity injection
- **CSRF** - Missing token protection
- **IDOR** - Insecure direct object references

---

## Project Structure

```
socrates-blade/
├── socrates-blade.py       # Main Python security tester
├── config.py               # Configuration and payloads
├── routes.json             # Blogware route definitions
├── run-scan.sh            # Bash automation wrapper
├── export_routes.php      # PHP route exporter
├── scanrequirements.txt   # Python dependencies
├── payloads/               # Attack payloads
│   ├── xss.txt            # XSS payloads (100+)
│   ├── sqli.txt           # SQL injection payloads
│   ├── traversal.txt      # Path traversal payloads
│   └── ssrf.txt           # SSRF payloads
└── README.md              # This file
```

---

## Configuration

### Python Dependencies

The following packages are required:

```
requests>=2.31.0
beautifulsoup4>=4.12.0
colorama>=0.4.6
```

Install with: `pip install -r scanrequirements.txt`

### Route Synchronization

Routes are automatically synchronized from the Blogware application using `export_routes.php`. This requires:

1. PHP 7.4+ installed
2. Blogware installation accessible
3. Valid `config.php` in Blogware root

To manually sync routes:

```bash
php export_routes.php > routes.json
```

### Custom Payloads

Add custom payloads to the `payloads/` directory:

- `xss.txt` - XSS attack vectors
- `sqli.txt` - SQL injection payloads
- `traversal.txt` - Path traversal strings
- `ssrf.txt` - SSRF targets (format: `URL|Marker`)

---

## Report Formats

### JSON Report

```json
{
  "report_metadata": {
    "tool": "Socrates Blade",
    "version": "3.2.0",
    "target": "http://localhost",
    "scan_duration": 120.5
  },
  "summary": {
    "total_findings": 15,
    "critical": 2,
    "high": 5,
    "medium": 4,
    "low": 4
  },
  "findings": [...]
}
```

### HTML Report

The HTML report includes:
- Executive summary with severity breakdown
- Visual finding cards with severity badges
- Detailed evidence and remediation guidance
- CWE and OWASP mappings

---

## Severity Ratings

| Rating | Description | SLA |
|--------|-------------|-----|
| **CRITICAL** | RCE, full data breach, XXE | Immediate (<24h) |
| **HIGH** | Stored XSS, IDOR, auth bypass | Urgent (<7d) |
| **MEDIUM** | Reflected XSS, CSRF, missing headers | High (<30d) |
| **LOW** | Info disclosure, weak policies | Medium (<90d) |

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Security Scan
on: [push, pull_request]

jobs:
  security-scan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run Security Scan
        run: |
          cd docs/security-testing/socrates-blade
          ./run-scan.sh ${{ secrets.TARGET_URL }} \
            -u ${{ secrets.TARGET_USER }} \
            -p ${{ secrets.TARGET_PASS }} \
            -o scan-results.json \
            --html-report scan-report.html
      - name: Upload Reports
        uses: actions/upload-artifact@v3
        with:
          name: security-reports
          path: |
            docs/security-testing/socrates-blade/scan-results.json
            docs/security-testing/socrates-blade/scan-report.html
```

### GitLab CI Example

```yaml
security_scan:
  stage: test
  script:
    - cd docs/security-testing/socrates-blade
    - pip install -r scanrequirements.txt
    - ./run-scan.sh $TARGET_URL \
        -u $TARGET_USER \
        -p $TARGET_PASS \
        -o security-report.json
  artifacts:
    reports:
      junit: security-report.json
```

---

## Ethical Usage

**IMPORTANT**: This tool is designed for authorized security testing only.

- Only test systems you have permission to test
- Review local laws and regulations before use
- Do not use for unauthorized access
- Respect rate limits and system resources
- Follow responsible disclosure practices

---

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

---

## License

MIT License - See LICENSE file for details.

---

## References

- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [OWASP Testing Guide v4.2](https://owasp.org/www-project-web-security-testing-guide/)
- [CWE Database](https://cwe.mitre.org/)
- [Blogware/Scriptlog Documentation](https://github.com/anomalyco/blogware)

---

**Version**: 3.2.0  
**Last Updated**: March 27, 2026  
**Author**: Security Assessment Team
