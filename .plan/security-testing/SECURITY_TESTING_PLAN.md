# Blogware/Scriptlog Security Testing Plan

**Version:** 2.0  
**Date:** March 27, 2026  
**Target:** Blogware/Scriptlog PHP MVC Blog System  
**Tester:** Security Assessment Team  
**Scope:** Local & Remote Instance Testing

---

## Document Information

| Field | Value |
|-------|-------|
| Document Status | Active |
| Last Updated | March 27, 2026 |
| Test Tool | Socrates Blade v3.2 |
| OWASP Version | Top 10 2021 |

---

## 1. Executive Summary

This document outlines a comprehensive security testing plan for the Blogware/Scriptlog CMS. The plan addresses OWASP Top 10 vulnerabilities, authentication flaws, and application-specific security concerns through automated and manual testing procedures.

### Testing Capabilities

- **Local Testing**: Target `http://localhost`, `http://127.0.0.1`, or custom local URLs
- **Remote Testing**: Target any publicly accessible Blogware instance
- **Aggressive Testing**: Time-based attacks with configurable delays (up to 30s)
- **Brute Force Testing**: Credential brute-forcing against login endpoints
- **Dual Reporting**: JSON and HTML output formats

### Test Environment

| Environment | URL | Notes |
|-------------|-----|-------|
| Local | `http://blogware.site` | Default development instance |
| Production | Configurable | Remote production instances |
| Admin Credentials | `administrator` / `4dMin(*)^` | Primary test account |

---

## 2. Testing Scope

### 2.1 In-Scope Components

| Component | Description | Priority |
|-----------|-------------|----------|
| Frontend (Public) | Public-facing blog pages, posts, categories, tags, archives | HIGH |
| Admin Panel | `/admin/*` - All admin functionality | CRITICAL |
| Authentication | Login, logout, session management, password reset | CRITICAL |
| API Endpoints | `/api/*` - REST API endpoints | HIGH |
| Import/Export | WordPress, Ghost, Blogspot import functionality | HIGH |
| Media Library | File upload and management | HIGH |
| Plugin System | Plugin installation and activation | CRITICAL |

### 2.2 Out-of-Scope

- Third-party plugins not part of core installation
- Server infrastructure and network-level attacks
- Denial of Service testing (resource exhaustion)
- Physical security testing

---

## 3. OWASP Top 10 2021 Coverage

| OWASP Category | Blogware Risk | Test Method | Priority |
|----------------|---------------|-------------|----------|
| A01 Broken Access Control | IDOR in posts/pages, privilege escalation | Tool + Manual | CRITICAL |
| A02 Cryptographic Failures | Session handling, cookie encryption | Manual review | HIGH |
| A03 Injection | SQLi in search/comments, XSS in content | Tool + Manual | CRITICAL |
| A04 Insecure Design | Missing rate limiting, weak CSRF | Tool + Manual | HIGH |
| A05 Security Misconfiguration | Missing headers, debug mode | Tool | MEDIUM |
| A06 Vulnerable Components | Outdated vendor libraries | Manual | MEDIUM |
| A07 Auth Failures | Brute force, session hijacking | Tool + Manual | HIGH |
| A08 Data Integrity Failures | CSRF on admin actions | Tool | HIGH |
| A09 Logging Failures | Insufficient audit trail | Manual | LOW |
| A10 SSRF | Media URL fetching, import URLs | Tool | MEDIUM |

---

## 4. Test Cases

### 4.1 A01 - Broken Access Control

| ID | Test Case | Endpoint | Method | Expected Result |
|----|-----------|----------|--------|----------------|
| TC-001 | IDOR in Post Viewing | `/post/{id}/{slug}` | GET | 403 or redirect for private posts |
| TC-002 | IDOR in Admin Post Editing | `admin/posts.php?action=edit&Id={id}` | GET | 403 Forbidden |
| TC-003 | IDOR in Comment Deletion | `admin/comments.php?action=delete&Id={id}` | GET | 403 Forbidden |
| TC-004 | Privilege Escalation | `admin/users.php` | POST | Redirect to 403 page |
| TC-005 | Direct Object Reference in Media | `/admin/medialib.php` | GET | 403 for private media |
| TC-006 | Admin Page Access Without Auth | All `admin/*.php` | GET | Redirect to login |
| TC-007 | Category Access Control | `/category/{id}` | GET | Respect post visibility |
| TC-008 | Archive Access Control | `/archive/{month}/{year}` | GET | 404 for non-existent archives |

### 4.2 A02 - Cryptographic Failures

| ID | Test Case | Method | Expected Result |
|----|-----------|--------|----------------|
| TC-009 | Session Token Entropy | Code review | Minimum 128-bit entropy |
| TC-010 | Cookie Security Flags | GET | HttpOnly, Secure, SameSite=Strict |
| TC-011 | Password Hashing | Code review | `password_hash()` with cost >= 12 |
| TC-012 | Session Fixation | Login flow | `session_regenerate_id(true)` called |
| TC-013 | Cookie Encryption | Code review | AES-256-GCM or AES-256-CBC with HMAC |

### 4.3 A03 - Injection

#### XSS Tests

| ID | Test Case | Endpoint | Payload Type |
|----|-----------|----------|--------------|
| TC-014 | Reflected XSS in Search | `/?search=<script>` | Reflected |
| TC-015 | Stored XSS in Comments | Comment form | Stored |
| TC-016 | Stored XSS in Post Content | Admin editor | Stored |
| TC-017 | Stored XSS via WordPress Import | Import XML | Stored |
| TC-018 | Stored XSS via Ghost Import | Import JSON | Stored |
| TC-019 | DOM XSS in Theme | Various | DOM-based |
| TC-020 | XSS in Tag Names | `/tag/{tag}` | Reflected |
| TC-021 | XSS in Category Slugs | `/category/{slug}` | Reflected |

#### SQL Injection Tests

| ID | Test Case | Endpoint | Payload Type |
|----|-----------|----------|--------------|
| TC-022 | Error-based SQLi in Search | Search form | Error-based |
| TC-023 | Time-based SQLi in Search | Search form | Time-based (aggressive) |
| TC-024 | Boolean-based SQLi in Tag | `/tag/test'` | Boolean-based |
| TC-025 | SQLi in Comment Submission | Comment form | Error-based |
| TC-026 | SQLi in Login | Login form | Error-based |
| TC-027 | Union-based SQLi in Archive | `/archive/01/2026` | Union-based |

#### Path Traversal Tests

| ID | Test Case | Endpoint | Payload |
|----|-----------|----------|---------|
| TC-028 | Path Traversal in Media Upload | Upload form | `../shell.php` |
| TC-029 | Path Traversal in Import | Import functionality | `php://filter` |
| TC-030 | Local File Inclusion | Various | `file:///etc/passwd` |

#### XXE Tests

| ID | Test Case | Endpoint | Payload |
|----|-----------|----------|---------|
| TC-031 | XXE in WordPress Import | Import XML | `<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>` |
| TC-032 | XXE in Blogspot Import | Import XML | Same as above |

### 4.4 A04 - Insecure Design

| ID | Test Case | Method | Expected Result |
|----|-----------|--------|----------------|
| TC-033 | CSRF on Post Creation | POST | Token validated |
| TC-034 | CSRF on User Role Change | POST | Token validated |
| TC-035 | CSRF on Comment Moderation | POST | Token validated |
| TC-036 | Brute Force Login | Login form | Lockout after 5 attempts |
| TC-037 | Password Reset Rate Limit | Reset form | Rate limited |
| TC-038 | API Rate Limiting | API endpoints | 429 response |

### 4.5 A05 - Security Misconfiguration

| ID | Test Case | Method | Expected Result |
|----|-----------|--------|----------------|
| TC-039 | Security Headers Check | GET | CSP, X-Frame-Options, X-Content-Type-Options present |
| TC-040 | Debug Mode Exposure | Error trigger | Generic error page |
| TC-041 | Installer Access Post-Setup | `/install/` | 404 or redirect |
| TC-042 | Version Disclosure | HTTP headers | No version info |
| TC-043 | Stack Trace Exposure | Error page | Hidden in production |
| TC-044 | Directory Listing | Various paths | Disabled |

### 4.6 A07 - Authentication Failures

| ID | Test Case | Method | Expected Result |
|----|-----------|--------|----------------|
| TC-045 | Default Credentials | Login form | Rejected |
| TC-046 | Weak Password Acceptance | Registration | Rejected |
| TC-047 | Expired Token Reuse | Password reset | Rejected |
| TC-048 | Session Timeout | Idle session | Expires after 30 min |
| TC-049 | Concurrent Session | Multiple logins | Detected |
| TC-050 | Session Fixation | Login flow | New session ID |

### 4.7 A08 - Data Integrity Failures

| ID | Test Case | Method | Expected Result |
|----|-----------|--------|----------------|
| TC-051 | Cookie Tampering | Cookie edit | Invalid signature rejected |
| TC-052 | Anti-CSRF Token Reuse | POST | Token invalidated after use |
| TC-053 | Token Prediction | Token analysis | Unpredictable tokens |

### 4.8 A10 - Server-Side Request Forgery

| ID | Test Case | Endpoint | Payload |
|----|-----------|----------|---------|
| TC-054 | SSRF in Media Import | Import URL | `http://169.254.169.254/` |
| TC-055 | SSRF in URL Fetching | Various | `http://localhost:8080/` |
| TC-056 | SSRF Blind | Various | Time-based detection |

---

## 5. Testing Tool Configuration

### 5.1 Socrates Blade v3.2 Features

```
Socrates Blade - Advanced Security Testing Tool
├── Route-aware vulnerability scanning
├── OWASP Top 10 coverage
├── Authentication bypass testing
├── Credential brute-forcing (configurable wordlist)
├── Time-based injection attacks (up to 30s delays)
├── SSRF testing with cloud metadata detection
├── XXE detection
├── Multiple report formats (JSON, HTML)
├── Concurrent scanning (configurable threads)
├── Proxy support for Burp Suite integration
└── External payload loading
```

### 5.2 File Structure

```
docs/security-testing/
├── SECURITY_TESTING_PLAN.md    # This document
├── socrates-blade/
│   ├── socrates-blade.py       # Main testing script
│   ├── config.py               # Configuration and payloads
│   ├── routes.json             # Blogware route definitions
│   ├── payloads/
│   │   ├── xss.txt             # XSS payloads
│   │   ├── sqli.txt            # SQL injection payloads
│   │   ├── traversal.txt       # Path traversal payloads
│   │   └── ssrf.txt            # SSRF payloads
│   └── wordlists/
│       ├── passwords.txt       # Brute force passwords
│       └── usernames.txt       # Brute force usernames
```

### 5.3 Command Line Usage

#### Basic Scan
```bash
python3 socrates-blade.py http://blogware.site
```

#### Full Aggressive Scan with Auth
```bash
python3 socrates-blade.py http://blogware.site \
    -u administrator \
    -p "4dMin(*)^" \
    --aggressive \
    --timeout 30 \
    --threads 10
```

#### Brute Force Attack
```bash
python3 socrates-blade.py http://blogware.site \
    --brute-force \
    --wordlist wordlists/passwords.txt \
    -u administrator
```

#### Remote Target with Proxy
```bash
python3 socrates-blade.py https://remote-blogware.com \
    -u administrator \
    -p "password" \
    --proxy http://127.0.0.1:8080
```

#### Generate Both Reports
```bash
python3 socrates-blade.py http://blogware.site \
    -o findings.json \
    --html-report findings.html
```

### 5.4 Configuration Options

| Parameter | Default | Description |
|-----------|---------|-------------|
| `--target` | Required | Target URL (local or remote) |
| `-u, --username` | admin | Username for authenticated tests |
| `-p, --password` | admin | Password for authentication |
| `--routes-file` | routes.json | Path to route definitions |
| `--threads` | 5 | Number of concurrent threads |
| `--timeout` | 5 | Request timeout in seconds |
| `--aggressive` | False | Enable aggressive testing mode |
| `--brute-force` | False | Enable credential brute-forcing |
| `--wordlist` | None | Path to password wordlist |
| `--proxy` | None | HTTP/SOCKS proxy URL |
| `-o, --output` | None | JSON output file |
| `--html-report` | None | HTML report output file |
| `--csrf-field` | login_form | CSRF token field name |
| `--sleep-time` | 5 | Time-based injection delay (aggressive: 30) |

---

## 6. Report Formats

### 6.1 JSON Report Structure

```json
{
  "report_metadata": {
    "tool": "Socrates Blade v3.2",
    "version": "3.2.0",
    "generated_at": "2026-03-27T12:00:00Z",
    "target": "http://blogware.site",
    "scan_duration": 3600
  },
  "summary": {
    "total_findings": 15,
    "critical": 2,
    "high": 5,
    "medium": 4,
    "low": 4
  },
  "findings": [
    {
      "id": "TC-014",
      "title": "Reflected XSS in Search",
      "severity": "HIGH",
      "url": "http://blogware.site/?search=<script>",
      "parameter": "search",
      "method": "GET",
      "evidence": "Payload reflected unescaped",
      "remediation": "Apply htmlspecialchars() or Sanitize class",
      "cwe": "CWE-79",
      "owasp": "A03"
    }
  ],
  "headers_analysis": {
    "missing": ["Content-Security-Policy", "Strict-Transport-Security"],
    "present": ["X-Content-Type-Options"]
  },
  "scan_details": {
    "routes_scanned": 20,
    "forms_tested": 15,
    "endpoints_tested": 45,
    "payloads_used": 200
  }
}
```

### 6.2 HTML Report Structure

- Executive Summary with severity breakdown
- Findings by category (OWASP Top 10)
- Detailed finding cards with:
  - Title and severity badge
  - Affected endpoint and parameter
  - Proof of concept / evidence
  - Remediation steps
  - References (CWE, OWASP)
- Header analysis section
- Recommendations section

---

## 7. Severity Ratings

| Rating | Description | SLA |
|--------|-------------|-----|
| CRITICAL | RCE, SQL injection with data leak, XXE | Immediate (<24h) |
| HIGH | Stored XSS in admin, IDOR, auth bypass | Urgent (<7d) |
| MEDIUM | Reflected XSS, CSRF, missing headers | High (<30d) |
| LOW | Info disclosure, weak password policy | Medium (<90d) |
| INFO | Best practice violations | None |

---

## 8. Remediation Priority Matrix

### Immediate Actions (CRITICAL Findings)

| Finding | Location | Remediation |
|---------|----------|-------------|
| SQL Injection | `lib/core/Db.php`, search, comments | Audit ALL prepared statements |
| Stored XSS via Import | `lib/utility/import-*.php` | Apply HTMLPurifier after parsing |
| XXE in XML Import | `import-wordpress.php`, `import-blogspot.php` | Disable external entities |
| IDOR in Admin | `admin/posts.php`, `admin/comments.php` | Add ownership verification |

### Urgent Actions (HIGH Findings)

| Finding | Location | Remediation |
|---------|----------|-------------|
| Stored XSS | Post editor, comments | Sanitize all HTML input |
| CSRF on Critical Forms | User management, plugin activation | Add CSRF tokens |
| Session Fixation | `Authentication.php` | Regenerate session on login |
| Brute Force | Login endpoint | Implement rate limiting |

### High Priority (MEDIUM Findings)

| Finding | Location | Remediation |
|---------|----------|-------------|
| Missing Security Headers | All responses | Configure web server |
| Debug Mode | Production config | Disable in production |
| API CORS Wildcard | `api/index.php` | Restrict origins |

---

## 9. Test Credentials

| Username | Password | Role | Purpose |
|----------|----------|------|---------|
| administrator | `4dMin(*)^` | Admin | Primary test account |
| editor_test | `Test@123456` | Editor | Privilege testing |
| author_test | `Test@123456` | Author | Content testing |
| subscriber_test | `Test@123456` | Subscriber | Read-only testing |

---

## 10. Testing Schedule

| Phase | Duration | Activities |
|-------|----------|-----------|
| Phase 1: Setup | 30 min | Configure tool, verify connectivity |
| Phase 2: Recon | 1 hour | Map application, enumerate endpoints |
| Phase 3: Automated Scan | 2 hours | Run Socrates Blade, SQLMap |
| Phase 4: Manual Testing | 3 hours | Auth testing, CSRF, access control |
| Phase 5: Analysis | 2 hours | Consolidate findings, assign severity |
| Phase 6: Reporting | 1 hour | Generate JSON/HTML reports |

**Total Estimated Time:** 9.5 hours

---

## 11. Known Security Mechanisms

| Mechanism | File | Coverage |
|-----------|------|----------|
| CSRF Protection | `lib/core/CSRFGuard.php` | Token-protected forms |
| XSS Prevention | `lib/core/Sanitize.php` | Content sanitization |
| SQL Injection | `lib/core/Db.php` | Prepared statements |
| Session Security | `lib/core/SessionMaker.php` | Cookie encryption |
| Access Control | `lib/core/Authentication.php` | Role-based access |
| File Validation | `lib/utility/upload-photo.php` | MIME type checking |

---

## 12. References

- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [OWASP Testing Guide v4.2](https://owasp.org/www-project-web-security-testing-guide/)
- [CWE Database](https://cwe.mitre.org/)
- [Socrates Blade GitHub](https://github.com/anomalyco/socrates-blade)

---

**Document Version:** 2.0  
**Status:** Active  
**Next Review:** After security remediation cycle  
**Approval:** Security Review Board
