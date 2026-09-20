<?php
// APP_DEBUG is referenced (with a defined() guard) by ApiResponse.php but is
// never declared at runtime; declare it here for static analysis so Psalm can
// resolve the constant in code like: defined('APP_DEBUG') && APP_DEBUG === true
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', (bool) getenv('APP_DEBUG'));
}

// Generate a stub config.php for Psalm static analysis if not present.
// config.php is created during installation and is gitignored, so it
// doesn't exist in CI or dev environments. This stub matches the
// structure from config.sample.php so Psalm can analyze code that
// requires config.php without raising MissingFile errors.
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    file_put_contents($configPath, "<?php\nreturn [\n    'db' => [\n        'host' => 'localhost',\n        'user' => '',\n        'pass' => '',\n        'name' => '',\n        'port' => '3306',\n        'prefix' => ''\n    ],\n    'app' => [\n        'url' => 'http://localhost',\n        'email' => '',\n        'key' => 'base64:psalm-stub-key-change-in-production',\n        'defuse_key' => 'lib/utility/.lts/lts.php'\n    ],\n    'mail' => [\n        'smtp' => [\n            'host' => '',\n            'port' => 587,\n            'encryption' => 'tls',\n            'username' => '',\n            'password' => ''\n        ],\n        'from' => [\n            'email' => 'noreply@blogware.site',\n            'name' => 'Blogware'\n        ]\n    ],\n    'os' => [\n        'system_software' => 'Linux',\n        'distrib_name' => 'Linux Mint'\n    ],\n    'api' => [\n        'allowed_origins' => ''\n    ]\n];\n");
}

require_once __DIR__ . '/lib/common.php';
require_once __DIR__ . '/lib/vendor/autoload.php';
require_once __DIR__ . '/lib/utility-loader.php';
require_once __DIR__ . '/public/themes/blog/functions.php';
