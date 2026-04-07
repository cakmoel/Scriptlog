#!/usr/bin/env python3
"""
Unit Tests for Socrates Blade v3.2 - Security Testing Tool
"""

import os
import sys
import json
import unittest
from unittest.mock import Mock, patch, MagicMock
from datetime import datetime
import argparse

test_dir = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, test_dir)

import config
Config = config.Config
Severity = config.Severity
OWASP = config.OWASP
CWE_MAPPINGS = config.CWE_MAPPINGS

# Import socrates-blade module directly
import importlib.util
spec = importlib.util.spec_from_file_location("socrates_blade", os.path.join(test_dir, "socrates-blade.py"))
socrates_blade = importlib.util.module_from_spec(spec)
sys.modules['socrates_blade'] = socrates_blade
spec.loader.exec_module(socrates_blade)


class MockArgs:
    """Mock arguments for testing"""
    def __init__(self, **kwargs):
        self.target = kwargs.get('target', 'http://localhost')
        self.username = kwargs.get('username', None)
        self.password = kwargs.get('password', None)
        self.routes_file = kwargs.get('routes_file', 'routes.json')
        self.threads = kwargs.get('threads', None)
        self.timeout = kwargs.get('timeout', None)
        self.aggressive = kwargs.get('aggressive', False)
        self.brute_force = kwargs.get('brute_force', False)
        self.wordlist = kwargs.get('wordlist', None)
        self.max_attempts = kwargs.get('max_attempts', 10)
        self.proxy = kwargs.get('proxy', None)
        self.csrf_field = kwargs.get('csrf_field', 'login_form')
        self.output = kwargs.get('output', None)
        self.html_report = kwargs.get('html_report', None)
        self.verify_ssl = kwargs.get('verify_ssl', False)


class TestConfig(unittest.TestCase):
    """Test Config class"""

    def test_xss_payloads_not_empty(self):
        """XSS payloads should not be empty"""
        self.assertGreater(len(Config.XSS_PAYLOADS), 0)

    def test_sqli_payloads_not_empty(self):
        """SQLi payloads should not be empty"""
        self.assertGreater(len(Config.SQLI_PAYLOADS), 0)

    def test_traversal_payloads_not_empty(self):
        """Traversal payloads should not be empty"""
        self.assertGreater(len(Config.TRAVERSAL_PAYLOADS), 0)

    def test_get_all_xss_payloads(self):
        """Test getting all XSS payloads"""
        payloads = Config.get_all_xss_payloads()
        self.assertIsInstance(payloads, list)
        self.assertGreater(len(payloads), 0)

    def test_get_all_sqli_payloads(self):
        """Test getting all SQLi payloads"""
        payloads = Config.get_all_sqli_payloads()
        self.assertIsInstance(payloads, list)
        self.assertGreater(len(payloads), 0)

    def test_get_all_traversal_payloads(self):
        """Test getting all traversal payloads"""
        payloads = Config.get_all_traversal_payloads()
        self.assertIsInstance(payloads, list)
        self.assertGreater(len(payloads), 0)

    def test_get_all_ssrf_payloads(self):
        """Test getting all SSRF payloads"""
        payloads = Config.get_all_ssrf_payloads()
        self.assertIsInstance(payloads, dict)
        self.assertGreater(len(payloads), 0)

    def test_get_brute_force_passwords_default(self):
        """Test getting default brute force passwords"""
        passwords = Config.get_brute_force_passwords()
        self.assertIsInstance(passwords, list)
        self.assertGreater(len(passwords), 0)

    def test_get_brute_force_passwords_custom(self):
        """Test getting custom brute force passwords"""
        passwords = Config.get_brute_force_passwords(None)
        self.assertIsInstance(passwords, list)

    def test_get_sql_time_payload(self):
        """Test SQL time-based payload generation"""
        payloads = Config.get_sql_time_payload(5)
        self.assertIsInstance(payloads, list)

    def test_config_constants(self):
        """Test configuration constants"""
        self.assertEqual(Config.REQUEST_TIMEOUT, 5)
        self.assertEqual(Config.AGGRESSIVE_TIMEOUT, 30)
        self.assertEqual(Config.SQLI_SLEEP_TIME, 5)
        self.assertEqual(Config.CONCURRENCY_LEVEL, 5)


class TestSeverity(unittest.TestCase):
    """Test Severity class"""

    def test_severity_levels(self):
        """Test severity level constants"""
        self.assertEqual(Severity.CRITICAL, "CRITICAL")
        self.assertEqual(Severity.HIGH, "HIGH")
        self.assertEqual(Severity.MEDIUM, "MEDIUM")
        self.assertEqual(Severity.LOW, "LOW")
        self.assertEqual(Severity.INFO, "INFO")

    def test_severity_levels_list(self):
        """Test severity levels list"""
        self.assertIn(Severity.CRITICAL, Severity.LEVELS)
        self.assertIn(Severity.HIGH, Severity.LEVELS)
        self.assertIn(Severity.MEDIUM, Severity.LEVELS)
        self.assertIn(Severity.LOW, Severity.LEVELS)
        self.assertIn(Severity.INFO, Severity.LEVELS)


class TestOWASP(unittest.TestCase):
    """Test OWASP class"""

    def test_owasp_categories(self):
        """Test OWASP categories"""
        self.assertEqual(OWASP.A01, "A01 - Broken Access Control")
        self.assertEqual(OWASP.A02, "A02 - Cryptographic Failures")
        self.assertEqual(OWASP.A03, "A03 - Injection")
        self.assertEqual(OWASP.A10, "A10 - SSRF")


class TestCWEMappings(unittest.TestCase):
    """Test CWE mappings"""

    def test_cwe_mappings_not_empty(self):
        """CWE mappings should not be empty"""
        self.assertGreater(len(CWE_MAPPINGS), 0)

    def test_cwe_xss(self):
        """Test XSS CWE mapping"""
        self.assertIn("xss", CWE_MAPPINGS)
        self.assertIn("CWE-79", CWE_MAPPINGS["xss"])

    def test_cwe_sqli(self):
        """Test SQLi CWE mapping"""
        self.assertIn("sqli", CWE_MAPPINGS)
        self.assertIn("CWE-89", CWE_MAPPINGS["sqli"])

    def test_cwe_idor(self):
        """Test IDOR CWE mapping"""
        self.assertIn("idor", CWE_MAPPINGS)


class TestBlogSecurityTester(unittest.TestCase):
    """Test BlogSecurityTester class"""

    def setUp(self):
        """Set up test fixtures"""
        self.mock_args = MockArgs(target='http://localhost')

    def test_init(self):
        """Test BlogSecurityTester initialization"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        self.assertIsNotNone(tester)
        self.assertEqual(tester.base_url, 'http://localhost/')

    def test_format_base_url_with_http(self):
        """Test URL formatting with http prefix"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        url = tester.format_base_url('http://example.com')
        self.assertEqual(url, 'http://example.com/')

    def test_format_base_url_without_http(self):
        """Test URL formatting without http prefix"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        url = tester.format_base_url('example.com')
        self.assertEqual(url, 'http://example.com/')

    def test_format_base_url_trailing_slash(self):
        """Test URL formatting with trailing slash"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        url = tester.format_base_url('http://example.com/')
        self.assertEqual(url, 'http://example.com/')

    def test_format_base_url_strips_spaces(self):
        """Test URL formatting strips whitespace"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        url = tester.format_base_url('  example.com  ')
        self.assertEqual(url, 'http://example.com/')

    def test_resolve_url_simple(self):
        """Test resolving simple URL"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        url = tester.resolve_url('/post/(?<id>\\d+)/(?<slug>[\\w\\-]+)', {'id': '1', 'slug': 'test'})
        self.assertIn('1', url)
        self.assertIn('test', url)

    def test_resolve_url_default_params(self):
        """Test resolving URL with default params"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        url = tester.resolve_url('/post/(?<id>\\d+)/(?<slug>[\\w\\-]+)', {})
        self.assertIn('/post/', url)

    def test_print_status_info(self):
        """Test print_status with info"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        tester.print_status("Test message", "info")

    def test_print_status_success(self):
        """Test print_status with success"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        tester.print_status("Test message", "success")

    def test_print_status_warning(self):
        """Test print_status with warning"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        tester.print_status("Test message", "warning")

    def test_print_status_error(self):
        """Test print_status with error"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        tester.print_status("Test message", "error")

    def test_print_status_with_severity(self):
        """Test print_status with severity"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        tester.print_status("Test message", "info", Severity.CRITICAL)

    def test_get_cwe_for_type_xss(self):
        """Test CWE mapping for XSS"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        cwe = tester.get_cwe_for_type("Reflected XSS")
        self.assertEqual(cwe, "CWE-79")

    def test_get_cwe_for_type_sqli(self):
        """Test CWE mapping for SQLi"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        cwe = tester.get_cwe_for_type("sqli test")
        self.assertEqual(cwe, "CWE-89")

    def test_get_cwe_for_type_unknown(self):
        """Test CWE mapping for unknown type"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        cwe = tester.get_cwe_for_type("Unknown Vulnerability")
        self.assertEqual(cwe, "CWE-UNKNOWN")

    def test_get_owasp_for_type_xss(self):
        """Test OWASP mapping for XSS"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        owasp = tester.get_owasp_for_type("XSS")
        self.assertEqual(owasp, "A03 - Injection")

    def test_get_owasp_for_type_sqli(self):
        """Test OWASP mapping for SQLi"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        owasp = tester.get_owasp_for_type("sqli test")
        self.assertEqual(owasp, "A03 - Injection")

    def test_get_owasp_for_type_idor(self):
        """Test OWASP mapping for IDOR"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        owasp = tester.get_owasp_for_type("IDOR")
        self.assertEqual(owasp, "A01 - Broken Access Control")

    def test_get_owasp_for_type_csrf(self):
        """Test OWASP mapping for CSRF"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        owasp = tester.get_owasp_for_type("CSRF")
        self.assertEqual(owasp, "A08 - Data Integrity Failures")

    def test_get_owasp_for_type_ssrf(self):
        """Test OWASP mapping for SSRF"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        owasp = tester.get_owasp_for_type("SSRF")
        self.assertEqual(owasp, "A10 - SSRF")

    def test_get_owasp_for_type_auth(self):
        """Test OWASP mapping for auth"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        owasp = tester.get_owasp_for_type("Authentication Bypass")
        self.assertEqual(owasp, "A07 - Auth Failures")

    def test_get_owasp_for_type_headers(self):
        """Test OWASP mapping for headers"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        owasp = tester.get_owasp_for_type("Missing Headers")
        self.assertEqual(owasp, "A05 - Security Misconfiguration")

    def test_generate_evidence(self):
        """Test evidence generation"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        evidence = tester.generate_evidence("XSS", "http://test.com", "param", "GET")
        self.assertIn("GET", evidence)
        self.assertIn("param", evidence)

    def test_get_remediation_xss(self):
        """Test remediation for XSS"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        remediation = tester.get_remediation("Reflected XSS")
        self.assertIn("htmlspecialchars", remediation)

    def test_get_remediation_sqli(self):
        """Test remediation for SQLi"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        remediation = tester.get_remediation("SQL Injection")
        self.assertIn("parameterized", remediation)

    def test_get_remediation_csrf(self):
        """Test remediation for CSRF"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        remediation = tester.get_remediation("CSRF")
        self.assertIn("CSRF", remediation)

    def test_get_remediation_unknown(self):
        """Test remediation for unknown type"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        remediation = tester.get_remediation("Unknown")
        self.assertIn("Review", remediation)

    def test_add_finding(self):
        """Test adding a finding"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        tester.add_finding("XSS", "http://test.com", Severity.HIGH, "Test details", "param")
        self.assertEqual(len(tester.findings), 1)
        self.assertEqual(tester.findings[0]['type'], "XSS")
        self.assertEqual(tester.findings[0]['severity'], Severity.HIGH)

    def test_add_finding_no_duplicates(self):
        """Test adding duplicate finding"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        tester.add_finding("XSS", "http://test.com", Severity.HIGH, None, "param")
        tester.add_finding("XSS", "http://test.com", Severity.HIGH, None, "param")
        self.assertEqual(len(tester.findings), 1)

    def test_discover_params_with_query(self):
        """Test discovering params from URL with query string"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        params = tester.discover_params("http://test.com?foo=bar&baz=qux")
        self.assertEqual(params['foo'], 'bar')
        self.assertEqual(params['baz'], 'qux')

    def test_discover_params_without_query(self):
        """Test discovering params from URL without query string"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        params = tester.discover_params("http://test.com")
        self.assertEqual(params, {})

    def test_test_headers_missing(self):
        """Test detecting missing security headers"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        
        headers = {'Server': 'Apache'}
        tester.test_headers("http://test.com", headers)
        
        self.assertEqual(len(tester.findings), 1)
        self.assertEqual(tester.findings[0]['type'], "Missing Security Headers")

    def test_test_headers_present(self):
        """Test when security headers are present"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        
        headers = {
            'Content-Security-Policy': "default-src 'self'",
            'X-Frame-Options': 'DENY',
            'X-Content-Type-Options': 'nosniff',
            'Referrer-Policy': 'strict-origin-when-cross-origin',
            'Strict-Transport-Security': 'max-age=31536000',
            'Permissions-Policy': 'geolocation=()',
            'X-XSS-Protection': '1; mode=block',
        }
        tester.test_headers("http://test.com", headers)
        
        self.assertEqual(len(tester.findings), 0)

    def test_aggressive_mode_config(self):
        """Test aggressive mode configuration"""
        args = MockArgs(target='http://localhost', aggressive=True)
        tester = socrates_blade.BlogSecurityTester(args)
        
        self.assertEqual(tester.timeout, Config.AGGRESSIVE_TIMEOUT)
        self.assertEqual(tester.threads, Config.AGGRESSIVE_CONCURRENCY)

    def test_normal_mode_config(self):
        """Test normal mode configuration"""
        args = MockArgs(target='http://localhost', aggressive=False)
        tester = socrates_blade.BlogSecurityTester(args)
        
        self.assertEqual(tester.timeout, Config.REQUEST_TIMEOUT)
        self.assertEqual(tester.threads, Config.CONCURRENCY_LEVEL)

    def test_proxy_configuration(self):
        """Test proxy configuration"""
        args = MockArgs(target='http://localhost', proxy='http://127.0.0.1:8080')
        tester = socrates_blade.BlogSecurityTester(args)
        
        self.assertIsNotNone(tester.session.proxies)

    def test_session_headers(self):
        """Test session headers are configured"""
        tester = socrates_blade.BlogSecurityTester(self.mock_args)
        
        self.assertIn('User-Agent', tester.session.headers)
        self.assertIn('Accept', tester.session.headers)


class TestColors(unittest.TestCase):
    """Test Colors class"""

    def test_colors_defined(self):
        """Test color constants are defined"""
        self.assertIsNotNone(socrates_blade.Colors.RED)
        self.assertIsNotNone(socrates_blade.Colors.GREEN)
        self.assertIsNotNone(socrates_blade.Colors.YELLOW)
        self.assertIsNotNone(socrates_blade.Colors.BLUE)
        self.assertIsNotNone(socrates_blade.Colors.CYAN)
        self.assertIsNotNone(socrates_blade.Colors.MAGENTA)
        self.assertIsNotNone(socrates_blade.Colors.WHITE)
        self.assertIsNotNone(socrates_blade.Colors.RESET)


if __name__ == '__main__':
    unittest.main(verbosity=2)
