#!/usr/bin/env python3
"""
Socrates Blade v3.2 - Advanced Security Testing Tool
Blogware/Scriptlog CMS Security Auditor

Version: 3.2.0
Author: Security Assessment Team
License: MIT

Features:
- OWASP Top 10 2021 coverage
- Local and remote instance testing
- Aggressive mode with configurable delays
- Credential brute-forcing
- Multi-format reporting (JSON, HTML)
- Route-aware vulnerability scanning
- Concurrent scanning support
- Proxy support for Burp Suite integration
"""

import os
import sys
import re
import json
import time
import random
import string
import argparse
import hashlib
import concurrent.futures
from datetime import datetime, timedelta
from urllib.parse import urljoin, urlparse, quote, unquote, parse_qs, urlencode
from bs4 import BeautifulSoup
from colorama import Fore, Style, init
import requests
import urllib3

# Disable SSL warnings
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Initialize colorama
init(autoreset=True)

# Import configuration
from config import Config, Severity, OWASP, CWE_MAPPINGS


class Colors:
    """ANSI color codes for terminal output"""
    RED = Fore.RED
    GREEN = Fore.GREEN
    YELLOW = Fore.YELLOW
    BLUE = Fore.BLUE
    CYAN = Fore.CYAN
    MAGENTA = Fore.MAGENTA
    WHITE = Fore.WHITE
    RESET = Style.RESET_ALL
    BRIGHT = Style.BRIGHT


class BlogSecurityTester:
    """
    Main security testing class for Blogware/Scriptlog CMS.
    Supports local and remote testing with configurable aggressiveness.
    """
    
    def __init__(self, args):
        self.args = args
        self.session = requests.Session()
        self.session.verify = args.verify_ssl if hasattr(args, 'verify_ssl') else False
        
        # Configure timeout based on aggressive mode
        self.timeout = Config.AGGRESSIVE_TIMEOUT if args.aggressive else Config.REQUEST_TIMEOUT
        self.sleep_time = Config.AGGRESSIVE_SLEEP_TIME if args.aggressive else Config.SQLI_SLEEP_TIME
        self.threads = Config.AGGRESSIVE_CONCURRENCY if args.aggressive else Config.CONCURRENCY_LEVEL
        
        self.base_url = self.format_base_url(args.target)
        self.routes = self.load_routes()
        self.authenticated = False
        self.findings = []
        self.scanned_urls = set()
        self.start_time = datetime.now()
        
        # Configure proxy
        if args.proxy:
            self.session.proxies = {
                'http': args.proxy,
                'https': args.proxy
            }
        
        # Session headers
        self.session.headers.update({
            'User-Agent': 'SocratesBlade/3.2.0 (Security Auditor; +https://security.example.com)',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Accept-Encoding': 'gzip, deflate',
            'DNT': '1',
            'Connection': 'close',
        })
    
    def format_base_url(self, target):
        """Format target URL with proper protocol prefix"""
        target = target.strip()
        if not target.startswith(('http://', 'https://')):
            target = f"http://{target}"
        return target.rstrip('/') + '/'
    
    def print_status(self, message, status='info', severity=None):
        """Print colored status message to console"""
        colors = {
            'info': Colors.BLUE,
            'success': Colors.GREEN,
            'warning': Colors.YELLOW,
            'error': Colors.RED,
            'debug': Colors.CYAN
        }
        chars = {
            'info': '*',
            'success': '+',
            'warning': '!',
            'error': 'X',
            'debug': '~'
        }
        
        sev_str = ""
        if severity:
            sev_colors = {
                Severity.CRITICAL: Colors.RED + Colors.BRIGHT,
                Severity.HIGH: Colors.RED,
                Severity.MEDIUM: Colors.YELLOW,
                Severity.LOW: Colors.CYAN,
                Severity.INFO: Colors.WHITE
            }
            sev_str = f"[{sev_colors.get(severity, Colors.WHITE)}{severity}{Colors.RESET}] "
        
        color = colors.get(status, Colors.BLUE)
        char = chars.get(status, '*')
        print(f"[{color}{char}{Colors.RESET}] {sev_str}{message}")
    
    def load_routes(self):
        """Load route definitions from JSON file"""
        try:
            routes_file = self.args.routes_file
            if not os.path.exists(routes_file):
                routes_file = os.path.join(os.path.dirname(__file__), 'routes.json')
            
            with open(routes_file, 'r') as f:
                data = json.load(f)
                
                # Handle both old format (flat) and new format (nested with metadata)
                if 'routes' in data and isinstance(data['routes'], dict):
                    # New format with metadata
                    flat_routes = {}
                    for category, routes in data['routes'].items():
                        if isinstance(routes, dict):
                            for route_name, route_info in routes.items():
                                full_name = f"{category}.{route_name}"
                                flat_routes[full_name] = route_info
                    return flat_routes
                else:
                    # Old flat format
                    return data
        except Exception as e:
            self.print_status(f"Error loading routes: {e}", 'error')
            return {}
    
    def resolve_url(self, path_tpl, params=None):
        """Resolve route template to actual URL"""
        if params is None:
            params = {}
        
        url = path_tpl
        
        # Replace named groups
        for key, value in params.items():
            pattern = rf"\(\?<{key}>[^)]+\)"
            url = re.sub(pattern, str(value), url)
        
        # Replace any remaining placeholders with defaults
        url = re.sub(r'\(\?<[^>]+>(\[[^\]]+\]|[^\)]+)\)', '1', url)
        
        return urljoin(self.base_url, url.lstrip('/'))
    
    def add_finding(self, vuln_type, url, severity, details=None, param=None, method='GET', cwe=None, owasp=None):
        """Add a security finding"""
        finding = {
            'timestamp': datetime.now().isoformat(),
            'type': vuln_type,
            'severity': severity,
            'url': url,
            'method': method,
            'param': param,
            'details': details,
            'cwe': cwe or self.get_cwe_for_type(vuln_type),
            'owasp': owasp or self.get_owasp_for_type(vuln_type),
            'evidence': self.generate_evidence(vuln_type, url, param, method),
            'remediation': self.get_remediation(vuln_type),
        }
        
        # Avoid duplicate findings
        key = f"{vuln_type}:{url}:{param}"
        if not any(f.get('url') == url and f.get('type') == vuln_type and f.get('param') == param for f in self.findings):
            self.findings.append(finding)
            self.print_status(f"{vuln_type} at {url}" + (f" (Param: {param})" if param else ""), 'error', severity)
    
    def get_cwe_for_type(self, vuln_type):
        """Get CWE identifier for vulnerability type"""
        type_lower = vuln_type.lower()
        for key, cwes in CWE_MAPPINGS.items():
            if key in type_lower:
                return cwes[0]
        return "CWE-UNKNOWN"
    
    def get_owasp_for_type(self, vuln_type):
        """Get OWASP category for vulnerability type"""
        type_lower = vuln_type.lower()
        if 'xss' in type_lower or 'sqli' in type_lower or 'xxe' in type_lower:
            return "A03 - Injection"
        elif 'idor' in type_lower or 'access' in type_lower:
            return "A01 - Broken Access Control"
        elif 'csrf' in type_lower:
            return "A08 - Data Integrity Failures"
        elif 'ssrf' in type_lower:
            return "A10 - SSRF"
        elif 'auth' in type_lower or 'brute' in type_lower:
            return "A07 - Auth Failures"
        elif 'header' in type_lower or 'config' in type_lower:
            return "A05 - Security Misconfiguration"
        return "A00 - Unknown"
    
    def generate_evidence(self, vuln_type, url, param, method):
        """Generate evidence for finding"""
        return f"{method} {url}" + (f"?{param}=<PAYLOAD>" if param else "")
    
    def get_remediation(self, vuln_type):
        """Get remediation advice for vulnerability type"""
        remediations = {
            'XSS': "Apply htmlspecialchars() or use Sanitize class with appropriate level",
            'SQL Injection': "Use parameterized queries or prepared statements exclusively",
            'CSRF': "Implement CSRF tokens on all state-changing forms",
            'IDOR': "Implement proper authorization checks for all resources",
            'SSRF': "Validate and whitelist all user-supplied URLs",
            'XXE': "Disable external entities in XML parsing",
            'Path Traversal': "Validate and sanitize all file paths, use basename()",
            'Brute Force': "Implement rate limiting and account lockout",
            'Missing Headers': "Configure web server to send security headers",
            'Default Credentials': "Change default credentials immediately",
        }
        
        for key, remediation in remediations.items():
            if key.lower() in vuln_type.lower():
                return remediation
        return "Review and fix the identified vulnerability"
    
    def perform_login(self):
        """Attempt to authenticate with provided credentials"""
        try:
            login_url = urljoin(self.base_url, 'admin/login.php')
            resp = self.session.get(login_url, timeout=self.timeout)
            soup = BeautifulSoup(resp.text, 'html.parser')
            
            # Find CSRF token
            csrf_field = self.args.csrf_field
            csrf_input = soup.find('input', {'name': csrf_field})
            csrf_token = csrf_input['value'] if csrf_input else ''
            
            # Attempt login
            login_data = {
                csrf_field: csrf_token,
                'username': self.args.username,
                'password': self.args.password
            }
            
            resp = self.session.post(
                urljoin(self.base_url, 'admin/login.php?load=login'),
                data=login_data,
                allow_redirects=True,
                timeout=self.timeout
            )
            
            if resp.status_code in (200, 302):
                # Check for successful login indicators
                success_indicators = ['logout', '/admin', 'dashboard', 'administrator']
                if any(indicator in resp.text.lower() for indicator in success_indicators):
                    self.print_status(f"Authentication successful as '{self.args.username}'", 'success')
                    self.authenticated = True
                    return True
            
            self.print_status("Authentication failed - proceeding with unauthenticated tests", 'warning')
            return False
            
        except Exception as e:
            self.print_status(f"Login error: {e}", 'error')
            return False
    
    def brute_force_login(self):
        """Perform brute force attack on login endpoint"""
        self.print_status("Starting brute force attack...", 'warning')
        
        login_url = urljoin(self.base_url, 'admin/login.php?load=login')
        passwords = Config.get_brute_force_passwords(self.args.wordlist)
        
        found = False
        attempts = 0
        
        for password in passwords[:self.args.max_attempts]:
            attempts += 1
            try:
                # Get fresh CSRF token
                resp = self.session.get(urljoin(self.base_url, 'admin/login.php'), timeout=self.timeout)
                soup = BeautifulSoup(resp.text, 'html.parser')
                csrf_input = soup.find('input', {'name': self.args.csrf_field})
                csrf_token = csrf_input['value'] if csrf_input else ''
                
                login_data = {
                    self.args.csrf_field: csrf_token,
                    'username': self.args.username,
                    'password': password
                }
                
                resp = self.session.post(login_url, data=login_data, timeout=self.timeout, allow_redirects=True)
                
                if any(indicator in resp.text.lower() for indicator in ['logout', 'dashboard', 'admin']):
                    self.add_finding(
                        'Successful Brute Force',
                        login_url,
                        Severity.CRITICAL,
                        details=f"Valid credentials found: {self.args.username}:{password}",
                        cwe='CWE-307'
                    )
                    found = True
                    break
                
                # Progress indicator
                if attempts % 5 == 0:
                    self.print_status(f"Brute force progress: {attempts}/{min(len(passwords), self.args.max_attempts)} attempts", 'info')
                
                # Delay between attempts
                time.sleep(Config.BRUTE_FORCE.get('delay_between_attempts', 1))
                
            except Exception as e:
                self.print_status(f"Brute force attempt error: {e}", 'warning')
        
        if not found:
            self.print_status(f"Brute force completed: {attempts} attempts, no valid credentials found", 'info')
        
        return found
    
    def test_headers(self, url, headers):
        """Test for missing security headers (A05)"""
        missing = []
        for header in Config.A05_REQUIRED_HEADERS:
            if header.lower() not in {k.lower() for k in headers.keys()}:
                missing.append(header)
        
        if missing:
            self.add_finding(
                'Missing Security Headers',
                url,
                Severity.MEDIUM,
                details=f"Missing headers: {', '.join(missing)}",
                cwe='CWE-16'
            )
    
    def test_xss(self, url, params, method='GET'):
        """Test for reflected XSS vulnerabilities"""
        payloads = Config.get_all_xss_payloads()
        
        for payload in payloads[:20]:  # Limit in non-aggressive mode
            if self.args.aggressive:
                payloads_to_test = payloads
            else:
                payloads_to_test = payloads[:10]
            
            for param in params:
                test_params = params.copy()
                test_params[param] = payload
                
                try:
                    if method == 'GET':
                        resp = self.session.get(url, params=test_params, timeout=self.timeout)
                    else:
                        resp = self.session.post(url, data=test_params, timeout=self.timeout)
                    
                    # Check for payload reflection
                    if payload in resp.text:
                        # Double-check it's not in a comment or script context
                        self.add_finding(
                            'Reflected XSS',
                            url,
                            Severity.HIGH,
                            param=param,
                            method=method,
                            details=f"Payload reflected in response"
                        )
                        return
                        
                except Exception:
                    pass
    
    def test_sqli(self, url, params, method='GET'):
        """Test for SQL injection vulnerabilities"""
        # Error-based SQLi
        for payload in Config.SQLI_PAYLOADS.get('error', [])[:10]:
            for param in params:
                test_params = params.copy()
                test_params[param] = f"{test_params[param]}{payload}"
                
                try:
                    if method == 'GET':
                        resp = self.session.get(url, params=test_params, timeout=self.timeout)
                    else:
                        resp = self.session.post(url, data=test_params, timeout=self.timeout)
                    
                    # Check for SQL error signatures
                    for error_sig in Config.ERROR_SIGNATURES['sql']:
                        if error_sig.lower() in resp.text.lower():
                            self.add_finding(
                                'Error-based SQL Injection',
                                url,
                                Severity.CRITICAL,
                                param=param,
                                method=method,
                                details=f"SQL error detected: {error_sig}"
                            )
                            return
                except Exception:
                    pass
        
        # Time-based SQLi (aggressive mode only)
        if self.args.aggressive:
            for payload_template in Config.SQLI_PAYLOADS.get('time_mysql', []):
                payload = payload_template.format(sleep_time=self.sleep_time)
                
                for param in params:
                    test_params = params.copy()
                    test_params[param] = f"{test_params[param]}{payload}"
                    
                    start = time.time()
                    try:
                        if method == 'GET':
                            self.session.get(url, params=test_params, timeout=self.timeout + 5)
                        else:
                            self.session.post(url, data=test_params, timeout=self.timeout + 5)
                        
                        duration = time.time() - start
                        
                        if duration >= self.sleep_time:
                            self.add_finding(
                                'Time-based SQL Injection',
                                url,
                                Severity.CRITICAL,
                                param=param,
                                method=method,
                                details=f"Response delayed by {duration:.1f}s"
                            )
                            return
                            
                    except requests.exceptions.Timeout:
                        self.add_finding(
                            'Time-based SQL Injection',
                            url,
                            Severity.CRITICAL,
                            param=param,
                            method=method,
                            details=f"Request timed out after {self.timeout}s"
                        )
                        return
                    except Exception:
                        pass
    
    def test_traversal(self, url, params):
        """Test for path traversal vulnerabilities"""
        payloads = Config.get_all_traversal_payloads()
        
        for payload in payloads:
            for param in params:
                test_params = params.copy()
                test_params[param] = payload
                
                try:
                    resp = self.session.get(url, params=test_params, timeout=self.timeout)
                    
                    # Check for file content leakage
                    for signature in Config.ERROR_SIGNATURES['path']:
                        if signature in resp.text:
                            self.add_finding(
                                'Path Traversal',
                                url,
                                Severity.HIGH,
                                param=param,
                                details=f"File content leaked: {signature}"
                            )
                            return
                            
                except Exception:
                    pass
    
    def test_ssrf(self, url, params):
        """Test for Server-Side Request Forgery"""
        candidate_params = [p for p in params if any(cand in p.lower() for cand in Config.A10_CANDIDATE_PARAMS)]
        
        for payload_url, marker in Config.get_all_ssrf_payloads().items():
            for param in candidate_params:
                test_params = params.copy()
                test_params[param] = payload_url
                
                try:
                    resp = self.session.get(url, params=test_params, timeout=self.timeout)
                    
                    # Check if marker appears in response (blind SSRF check)
                    if marker and marker in resp.text:
                        self.add_finding(
                            'Server-Side Request Forgery (SSRF)',
                            url,
                            Severity.CRITICAL,
                            param=param,
                            details=f"SSRF detected with URL: {payload_url}"
                        )
                        return
                        
                except Exception:
                    pass
    
    def test_xxe(self, url):
        """Test for XML External Entity injection"""
        for payload in Config.XXE_PAYLOADS.get('basic', []):
            try:
                headers = {'Content-Type': 'application/xml'}
                resp = self.session.post(url, data=payload, headers=headers, timeout=self.timeout)
                
                # Check for XXE indicators
                for signature in Config.ERROR_SIGNATURES.get('xxe', []):
                    if signature.lower() in resp.text.lower():
                        self.add_finding(
                            'XML External Entity (XXE)',
                            url,
                            Severity.CRITICAL,
                            details=f"XXE detected: {signature}"
                        )
                        return
                
                # Check for file content
                if 'root:x:' in resp.text or '/bin/' in resp.text:
                    self.add_finding(
                        'XML External Entity (XXE)',
                        url,
                        Severity.CRITICAL,
                        details="File content retrieved via XXE"
                    )
                    return
                    
            except Exception:
                pass
    
    def test_idor(self, route_name, spec):
        """Test for Insecure Direct Object Reference"""
        path = spec.get('path', '')
        
        if '(?<id>' in path or '(?<Id>' in path or 'Id=' in path:
            # Try accessing with different IDs
            for target_id in ["1", "2", "999", "0"]:
                test_params = {}
                
                # Extract ID from path
                if '(?<id>' in path:
                    url = self.resolve_url(path, {'id': target_id})
                elif '(?<Id>' in path:
                    url = self.resolve_url(path, {'Id': target_id})
                else:
                    url = urljoin(self.base_url, path.replace('Id=', f'Id={target_id}'))
                
                try:
                    resp = self.session.get(url, timeout=self.timeout)
                    
                    # Check for sensitive data exposure
                    if resp.status_code == 200:
                        for keyword in Config.SENSITIVE_KEYWORDS:
                            if keyword.lower() in resp.text.lower():
                                self.add_finding(
                                    'Insecure Direct Object Reference (IDOR)',
                                    url,
                                    Severity.HIGH,
                                    details=f"Accessed ID {target_id}, found sensitive data"
                                )
                                return
                                
                except Exception:
                    pass
    
    def test_csrf(self, url, forms):
        """Test for missing CSRF protection"""
        for form in forms:
            if form.get('method', 'get').lower() == 'post':
                # Check for CSRF token
                has_token = any(
                    any(pattern in inp['name'].lower() for pattern in Config.CSRF_PATTERNS)
                    for inp in form.get('inputs', [])
                )
                
                if not has_token:
                    self.add_finding(
                        'Missing CSRF Protection',
                        url,
                        Severity.MEDIUM,
                        details=f"Form at {url} lacks CSRF token"
                    )
    
    def test_installer_takeover(self):
        """Test for installer access after installation"""
        install_routes = ['install/', 'install/setup-db.php', 'install/index.php']
        
        for route in install_routes:
            url = urljoin(self.base_url, route)
            try:
                resp = self.session.get(url, timeout=self.timeout)
                if resp.status_code == 200:
                    # Check if installer is still functional
                    if 'install' in resp.text.lower() or 'database' in resp.text.lower():
                        self.add_finding(
                            'Installer Accessible Post-Installation',
                            url,
                            Severity.CRITICAL,
                            details="Installation wizard is accessible and functional"
                        )
            except Exception:
                pass
    
    def test_api_abuse(self):
        """Test for unauthenticated API access"""
        for endpoint in Config.API_ENDPOINTS:
            url = urljoin(self.base_url, endpoint.lstrip('/'))
            try:
                resp = self.session.get(url, timeout=self.timeout)
                if resp.status_code == 200:
                    # Check if sensitive data is exposed
                    if any(keyword in resp.text.lower() for keyword in ['password', 'email', 'token']):
                        self.add_finding(
                            'Unauthenticated API Access',
                            url,
                            Severity.HIGH,
                            details="API endpoint exposes sensitive data without authentication"
                        )
            except Exception:
                pass
    
    def discover_forms(self, url):
        """Discover forms on a page"""
        try:
            resp = self.session.get(url, timeout=self.timeout)
            soup = BeautifulSoup(resp.text, 'html.parser')
            
            forms = []
            for form in soup.find_all('form'):
                action = form.get('action') or ''
                target_url = urljoin(url, action)
                method = form.get('method', 'get').lower()
                
                inputs = []
                for inp in form.find_all(['input', 'textarea', 'select']):
                    name = inp.get('name')
                    if name:
                        inputs.append({
                            'name': name,
                            'type': inp.get('type', 'text'),
                            'value': inp.get('value', '')
                        })
                
                forms.append({
                    'url': target_url,
                    'method': method,
                    'inputs': inputs
                })
            
            return forms
        except Exception:
            return []
    
    def discover_params(self, url):
        """Discover URL parameters"""
        params = {}
        parsed = urlparse(url)
        
        if parsed.query:
            for key, value in parse_qs(parsed.query).items():
                params[key] = value[0] if value else ''
        
        return params
    
    def audit_route(self, route_name):
        """Audit a single route for vulnerabilities"""
        spec = self.routes.get(route_name, {})
        path = spec.get('path', '')
        
        if not path:
            return
        
        url = self.resolve_url(path)
        
        if url in self.scanned_urls:
            return
        self.scanned_urls.add(url)
        
        # Skip if requires auth and not authenticated
        if spec.get('requires_auth') and not self.authenticated:
            return
        
        try:
            # Test headers
            resp = self.session.get(url, timeout=self.timeout)
            self.test_headers(url, resp.headers)
            
            # Test forms
            forms = self.discover_forms(url)
            self.test_csrf(url, forms)
            
            # Test each form
            for form in forms:
                params = {inp['name']: 'test' for inp in form['inputs'] if inp['type'] != 'submit'}
                method = form['method'].upper()
                
                self.test_xss(form['url'], params, method)
                self.test_sqli(form['url'], params, method)
            
            # Test URL parameters
            url_params = self.discover_params(url)
            if url_params:
                self.test_xss(url, url_params, 'GET')
                self.test_sqli(url, url_params, 'GET')
                self.test_traversal(url, url_params)
                self.test_ssrf(url, url_params)
            
            # Test for IDOR
            if 'idor' in spec.get('attack_vectors', []):
                self.test_idor(route_name, spec)
            
            # Test for XXE
            if 'xxe' in spec.get('attack_vectors', []):
                self.test_xxe(url)
                
        except Exception as e:
            self.print_status(f"Error auditing {route_name}: {e}", 'warning')
    
    def generate_json_report(self):
        """Generate JSON report"""
        if not self.args.output:
            return
        
        duration = (datetime.now() - self.start_time).total_seconds()
        
        report = {
            'report_metadata': {
                'tool': 'Socrates Blade',
                'version': '3.2.0',
                'generated_at': datetime.now().isoformat(),
                'target': self.base_url,
                'scan_duration': duration,
                'aggressive_mode': self.args.aggressive,
            },
            'summary': {
                'total_findings': len(self.findings),
                'critical': len([f for f in self.findings if f['severity'] == Severity.CRITICAL]),
                'high': len([f for f in self.findings if f['severity'] == Severity.HIGH]),
                'medium': len([f for f in self.findings if f['severity'] == Severity.MEDIUM]),
                'low': len([f for f in self.findings if f['severity'] == Severity.LOW]),
            },
            'findings': self.findings,
            'scan_details': {
                'routes_scanned': len(self.scanned_urls),
                'total_routes': len(self.routes),
                'authenticated': self.authenticated,
            }
        }
        
        try:
            with open(self.args.output, 'w') as f:
                json.dump(report, f, indent=2)
            self.print_status(f"JSON report saved to {self.args.output}", 'success')
        except Exception as e:
            self.print_status(f"Failed to save JSON report: {e}", 'error')
    
    def generate_html_report(self):
        """Generate HTML report"""
        if not self.args.html_report:
            return
        
        duration = (datetime.now() - self.start_time).total_seconds()
        
        # Group findings by severity
        severity_order = [Severity.CRITICAL, Severity.HIGH, Severity.MEDIUM, Severity.LOW, Severity.INFO]
        grouped = {sev: [] for sev in severity_order}
        for f in self.findings:
            grouped[f['severity']].append(f)
        
        html = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Scan Report - {self.base_url}</title>
    <style>
        * {{ margin: 0; padding: 0; box-sizing: border-box; }}
        body {{ font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; line-height: 1.6; }}
        .container {{ max-width: 1200px; margin: 0 auto; padding: 20px; }}
        .header {{ background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }}
        .header h1 {{ font-size: 2em; margin-bottom: 10px; }}
        .header .meta {{ opacity: 0.8; font-size: 0.9em; }}
        .summary {{ display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }}
        .summary-card {{ background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }}
        .summary-card .count {{ font-size: 2.5em; font-weight: bold; }}
        .summary-card.critical {{ border-top: 4px solid #dc3545; }}
        .summary-card.critical .count {{ color: #dc3545; }}
        .summary-card.high {{ border-top: 4px solid #fd7e14; }}
        .summary-card.high .count {{ color: #fd7e14; }}
        .summary-card.medium {{ border-top: 4px solid #ffc107; }}
        .summary-card.medium .count {{ color: #ffc107; }}
        .summary-card.low {{ border-top: 4px solid #0dcaf0; }}
        .summary-card.low .count {{ color: #0dcaf0; }}
        .finding {{ background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }}
        .finding-header {{ display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }}
        .finding-title {{ font-size: 1.2em; font-weight: bold; }}
        .severity-badge {{ padding: 5px 15px; border-radius: 20px; color: white; font-weight: bold; font-size: 0.8em; }}
        .severity-badge.critical {{ background: #dc3545; }}
        .severity-badge.high {{ background: #fd7e14; }}
        .severity-badge.medium {{ background: #ffc107; color: #333; }}
        .severity-badge.low {{ background: #0dcaf0; }}
        .finding-meta {{ color: #666; font-size: 0.9em; margin-bottom: 10px; }}
        .finding-detail {{ margin: 10px 0; }}
        .finding-detail strong {{ color: #333; }}
        .remediation {{ background: #e8f5e9; padding: 15px; border-radius: 5px; margin-top: 15px; }}
        .remediation strong {{ color: #2e7d32; }}
        .no-findings {{ text-align: center; padding: 50px; background: white; border-radius: 10px; }}
        .no-findings h2 {{ color: #4caf50; }}
        .footer {{ text-align: center; margin-top: 30px; color: #666; font-size: 0.9em; }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Security Scan Report</h1>
            <div class="meta">
                <p>Target: <strong>{self.base_url}</strong></p>
                <p>Scan Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}</p>
                <p>Duration: {timedelta(seconds=int(duration))}</p>
                <p>Tool: Socrates Blade v3.2.0</p>
            </div>
        </div>
        
        <div class="summary">
            <div class="summary-card critical">
                <div class="count">{len(grouped[Severity.CRITICAL])}</div>
                <div>Critical</div>
            </div>
            <div class="summary-card high">
                <div class="count">{len(grouped[Severity.HIGH])}</div>
                <div>High</div>
            </div>
            <div class="summary-card medium">
                <div class="count">{len(grouped[Severity.MEDIUM])}</div>
                <div>Medium</div>
            </div>
            <div class="summary-card low">
                <div class="count">{len(grouped[Severity.LOW])}</div>
                <div>Low</div>
            </div>
        </div>
"""
        
        if not self.findings:
            html += """
        <div class="no-findings">
            <h2>No Vulnerabilities Found</h2>
            <p>The security scan completed without finding any vulnerabilities.</p>
        </div>
"""
        else:
            for severity in severity_order:
                findings = grouped[severity]
                if not findings:
                    continue
                
                html += f"<h2 style='margin: 30px 0 15px; color: #333;'>{severity} Severity</h2>\n"
                
                for finding in findings:
                    html += f"""
        <div class="finding">
            <div class="finding-header">
                <div class="finding-title">{finding['type']}</div>
                <span class="severity-badge {severity.lower()}">{finding['severity']}</span>
            </div>
            <div class="finding-meta">
                <span>{finding['method']}</span> | 
                <span>{finding['url']}</span>
                {f"| Param: <code>{finding['param']}</code>" if finding.get('param') else ""}
            </div>
            <div class="finding-detail">
                <strong>CWE:</strong> {finding.get('cwe', 'N/A')} | 
                <strong>OWASP:</strong> {finding.get('owasp', 'N/A')}
            </div>
            {f"<div class='finding-detail'><strong>Details:</strong> {finding.get('details', 'N/A')}</div>" if finding.get('details') else ""}
            <div class="finding-detail">
                <strong>Evidence:</strong> <code>{finding.get('evidence', 'N/A')}</code>
            </div>
            <div class="remediation">
                <strong>Remediation:</strong> {finding.get('remediation', 'Review and fix the identified vulnerability')}
            </div>
        </div>
"""
        
        html += f"""
        <div class="footer">
            <p>Generated by Socrates Blade v3.2.0 Security Scanner</p>
            <p>Scan completed at {datetime.now().isoformat()}</p>
        </div>
    </div>
</body>
</html>
"""
        
        try:
            with open(self.args.html_report, 'w') as f:
                f.write(html)
            self.print_status(f"HTML report saved to {self.args.html_report}", 'success')
        except Exception as e:
            self.print_status(f"Failed to save HTML report: {e}", 'error')
    
    def print_summary(self):
        """Print scan summary to console"""
        duration = (datetime.now() - self.start_time).total_seconds()
        
        print("\n" + "=" * 60)
        print(f"{Colors.BRIGHT}    Socrates Blade v3.2 Security Scan Summary{Colors.RESET}")
        print("=" * 60)
        print(f"Target: {self.base_url}")
        print(f"Duration: {timedelta(seconds=int(duration))}")
        print(f"Routes Scanned: {len(self.scanned_urls)}")
        print(f"Authentication: {'Success' if self.authenticated else 'Not authenticated'}")
        print()
        print(f"Findings: {len(self.findings)}")
        
        counts = {
            Severity.CRITICAL: 0,
            Severity.HIGH: 0,
            Severity.MEDIUM: 0,
            Severity.LOW: 0,
        }
        for f in self.findings:
            if f['severity'] in counts:
                counts[f['severity']] += 1
        
        severity_display = {
            Severity.CRITICAL: (Colors.RED + Colors.BRIGHT, 'CRITICAL'),
            Severity.HIGH: (Colors.RED, 'HIGH     '),
            Severity.MEDIUM: (Colors.YELLOW, 'MEDIUM   '),
            Severity.LOW: (Colors.CYAN, 'LOW      '),
        }
        
        for sev, count in counts.items():
            color, label = severity_display[sev]
            print(f"  {color}{label}{Colors.RESET}: {count}")
        
        print("=" * 60 + "\n")
        
        if self.args.output:
            print(f"JSON Report: {self.args.output}")
        if self.args.html_report:
            print(f"HTML Report: {self.args.html_report}")
        print()
    
    def run_all(self):
        """Execute all security tests"""
        self.print_status(f"Starting Socrates Blade v3.2.0 Security Scan", 'info')
        self.print_status(f"Target: {self.base_url}", 'info')
        self.print_status(f"Aggressive Mode: {'Enabled' if self.args.aggressive else 'Disabled'}", 'info')
        
        # Authentication
        if self.args.username and self.args.password:
            self.print_status(f"Attempting authentication as '{self.args.username}'...", 'info')
            self.perform_login()
        
        # Brute force (if enabled)
        if self.args.brute_force:
            self.print_status("Starting brute force attack...", 'warning')
            self.brute_force_login()
        
        # Systemic checks
        self.print_status("Running systemic security checks...", 'info')
        self.test_installer_takeover()
        self.test_api_abuse()
        
        # Route-based auditing
        self.print_status(f"Scanning {len(self.routes)} routes...", 'info')
        
        with concurrent.futures.ThreadPoolExecutor(max_workers=self.threads) as executor:
            list(executor.map(self.audit_route, self.routes.keys()))
        
        # Generate reports
        self.print_status("Generating reports...", 'info')
        self.generate_json_report()
        self.generate_html_report()
        
        # Print summary
        self.print_summary()


def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='Socrates Blade v3.2.0 - Advanced Security Testing Tool for Blogware/Scriptlog CMS',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Basic scan (local)
  python3 socrates-blade.py http://localhost
  
  # Full aggressive scan with authentication
  python3 socrates-blade.py http://blogware.site -u administrator -p "password" --aggressive
  
  # Brute force attack
  python3 socrates-blade.py http://blogware.site -u administrator --brute-force
  
  # Custom wordlist for brute force
  python3 socrates-blade.py http://blogware.site -u admin --brute-force --wordlist passwords.txt
  
  # Multi-format reporting
  python3 socrates-blade.py http://blogware.site -o findings.json --html-report report.html
  
  # Remote target with proxy
  python3 socrates-blade.py https://remote-blogware.com -u admin -p "pass" --proxy http://127.0.0.1:8080
"""
    )
    
    parser.add_argument('target', help='Target URL (local or remote)')
    parser.add_argument('-u', '--username', default=None, help='Username for authentication')
    parser.add_argument('-p', '--password', default=None, help='Password for authentication')
    parser.add_argument('--routes-file', default='routes.json', help='Path to routes JSON file')
    parser.add_argument('--threads', type=int, default=None, help='Number of concurrent threads')
    parser.add_argument('--timeout', type=int, default=None, help='Request timeout in seconds')
    parser.add_argument('--aggressive', action='store_true', help='Enable aggressive testing mode')
    parser.add_argument('--brute-force', action='store_true', help='Enable brute force attack on login')
    parser.add_argument('--wordlist', default=None, help='Path to password wordlist')
    parser.add_argument('--max-attempts', type=int, default=10, help='Max brute force attempts')
    parser.add_argument('--proxy', help='HTTP/SOCKS proxy URL')
    parser.add_argument('--csrf-field', default='login_form', help='CSRF token field name')
    parser.add_argument('-o', '--output', help='Save JSON report to file')
    parser.add_argument('--html-report', help='Save HTML report to file')
    parser.add_argument('--verify-ssl', action='store_true', help='Verify SSL certificates')
    
    args = parser.parse_args()
    
    # Override config with CLI args
    if args.threads:
        Config.CONCURRENCY_LEVEL = args.threads
    if args.timeout:
        Config.REQUEST_TIMEOUT = args.timeout
    
    tester = BlogSecurityTester(args)
    tester.run_all()


if __name__ == '__main__':
    main()
