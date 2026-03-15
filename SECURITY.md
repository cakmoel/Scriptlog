# Security Policy

## Supported Versions

We actively support and provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability in Scriptlog, please send an email to:

**Email:** scriptlog@yandex.com

Please include the following information:

- Type of vulnerability
- Full paths of source file(s) related to the vulnerability
- Location of the affected source code
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

## What to Expect

- **Acknowledgment**: You will receive acknowledgment within 48 hours
- **Status Update**: We will provide a more detailed response within 7 days, including:
  - Confirmation of the vulnerability
  - Our assessment of severity
  - Expected timeline for a fix
- **Disclosure**: We request that you give us reasonable time to address the vulnerability before public disclosure
- **Credit**: We will credit you in the security advisory (unless you prefer to remain anonymous)

## Security Features

Scriptlog includes the following security measures:

- CSRF protection via `CSRFGuard` class
- XSS prevention via `Sanitize` class and HTMLPurifier
- SQL injection prevention via PDO prepared statements
- Custom session handler with secure cookies
- Password hashing using `password_hash()`
- Input validation via `FormValidator`
- Security headers (CSP, X-Frame-Options, HSTS, etc.)
- Data encryption using Defuse PHP Encryption

## Security Best Practices for Users

When deploying Scriptlog:

1. Keep PHP and dependencies up to date
2. Use HTTPS in production
3. Set proper file permissions
4. Remove the `install/` directory after installation
5. Use strong database credentials
6. Enable HTTPS-only cookies

## Supported Destinations for Security Updates

- GitHub Security Advisories
- Official blog announcements
- Security mailing list (if available)
