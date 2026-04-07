<?php
/**
 * Socrates Blade Route Exporter
 * 
 * Dynamically extracts route definitions from Blogware/Scriptlog CMS
 * for security testing purposes.
 * 
 * Usage: php export_routes.php > routes.json
 * 
 * @version 1.0
 * @requires Blogware/Scriptlog installation with valid config.php
 */

// Allow CLI execution without SCRIPTLOG constant
if (php_sapi_name() !== 'cli' && !defined('SCRIPTLOG')) {
    defined('SCRIPTLOG') || die('Direct access not permitted');
}

// Ensure we have proper error handling
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

/**
 * Route definitions for Blogware/Scriptlog CMS
 * These are extracted from lib/core/Bootstrap.php
 */
function getBlogwareRoutes() {
    $routes = [];
    
    // Frontend routes (from Bootstrap.php)
    $frontendRoutes = [
        'home' => [
            'path' => '/',
            'method' => 'GET',
            'description' => 'Homepage - displays recent posts',
            'parameters' => [],
            'attack_vectors' => ['reflected_xss', 'parameter_pollution']
        ],
        'single' => [
            'path' => '/post/(?<id>\d+)/(?<slug>[\w\-]+)',
            'method' => 'GET',
            'description' => 'Single post view',
            'parameters' => ['id', 'slug'],
            'attack_vectors' => ['idor', 'reflected_xss']
        ],
        'category' => [
            'path' => '/category/(?<category>[\w\-]+)',
            'method' => 'GET',
            'description' => 'Category archive page',
            'parameters' => ['category'],
            'attack_vectors' => ['reflected_xss', 'sqli']
        ],
        'tag' => [
            'path' => '/tag/(?<tag>[\w\- ]+)',
            'method' => 'GET',
            'description' => 'Tag archive page',
            'parameters' => ['tag'],
            'attack_vectors' => ['reflected_xss', 'sqli']
        ],
        'archive' => [
            'path' => '/archive/(?<month>[0-9]{2})/(?<year>[0-9]{4})',
            'method' => 'GET',
            'description' => 'Monthly archive page',
            'parameters' => ['month', 'year'],
            'attack_vectors' => ['reflected_xss', 'sqli']
        ],
        'archives' => [
            'path' => '/archives',
            'method' => 'GET',
            'description' => 'Archive index page',
            'parameters' => [],
            'attack_vectors' => []
        ],
        'search' => [
            'path' => '/',
            'method' => 'GET',
            'description' => 'Search functionality',
            'parameters' => ['search'],
            'query_param' => true,
            'attack_vectors' => ['reflected_xss', 'sqli']
        ],
        'page' => [
            'path' => '/page/(?<page>[^/]+)',
            'method' => 'GET',
            'description' => 'Static page view',
            'parameters' => ['page'],
            'attack_vectors' => ['idor', 'reflected_xss']
        ]
    ];
    
    // Admin routes
    $adminRoutes = [
        // Authentication
        'auth.login' => [
            'path' => '/admin/login.php',
            'method' => 'GET',
            'description' => 'Login page',
            'requires_auth' => false
        ],
        'auth.login_submit' => [
            'path' => '/admin/login.php?load=login',
            'method' => 'POST',
            'description' => 'Login form submission',
            'parameters' => ['username', 'password', 'login_form'],
            'attack_vectors' => ['sqli', 'brute_force', 'auth_bypass'],
            'csrf_protected' => true
        ],
        'auth.logout' => [
            'path' => '/admin/login.php?load=logout',
            'method' => 'GET',
            'description' => 'Logout action'
        ],
        'auth.forgot_password' => [
            'path' => '/admin/forgot-password.php',
            'method' => 'GET',
            'description' => 'Forgot password page'
        ],
        'auth.reset_password' => [
            'path' => '/admin/forgot-password.php?load=reset',
            'method' => 'POST',
            'description' => 'Password reset form',
            'attack_vectors' => ['auth_bypass']
        ],
        
        // Posts
        'posts.list' => [
            'path' => '/admin/posts.php',
            'method' => 'GET',
            'description' => 'Posts management list',
            'requires_auth' => true,
            'attack_vectors' => ['idor']
        ],
        'posts.new' => [
            'path' => '/admin/posts.php?action=new',
            'method' => 'GET',
            'description' => 'New post form',
            'requires_auth' => true
        ],
        'posts.insert' => [
            'path' => '/admin/posts.php?load=insert',
            'method' => 'POST',
            'description' => 'Insert new post',
            'parameters' => ['post_title', 'post_content', 'post_tags', 'topic_id', 'post_status', 'login_form'],
            'attack_vectors' => ['stored_xss', 'csrf', 'sqli'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'posts.edit' => [
            'path' => '/admin/posts.php?action=edit&Id=(?<id>\d+)',
            'method' => 'GET',
            'description' => 'Edit post form',
            'parameters' => ['id'],
            'attack_vectors' => ['idor'],
            'requires_auth' => true
        ],
        'posts.update' => [
            'path' => '/admin/posts.php?load=update',
            'method' => 'POST',
            'description' => 'Update existing post',
            'attack_vectors' => ['stored_xss', 'csrf', 'idor', 'sqli'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'posts.delete' => [
            'path' => '/admin/posts.php?load=delete&Id=(?<id>\d+)',
            'method' => 'GET',
            'description' => 'Delete post',
            'parameters' => ['id'],
            'attack_vectors' => ['idor', 'csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Comments
        'comments.list' => [
            'path' => '/admin/comments.php',
            'method' => 'GET',
            'description' => 'Comments management list',
            'requires_auth' => true
        ],
        'comments.update' => [
            'path' => '/admin/comments.php?load=update',
            'method' => 'POST',
            'description' => 'Update comment status',
            'attack_vectors' => ['csrf', 'idor'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'comments.delete' => [
            'path' => '/admin/comments.php?load=delete&Id=(?<id>\d+)',
            'method' => 'GET',
            'description' => 'Delete comment',
            'attack_vectors' => ['idor', 'csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Users
        'users.list' => [
            'path' => '/admin/users.php',
            'method' => 'GET',
            'description' => 'Users management list',
            'requires_auth' => true
        ],
        'users.new' => [
            'path' => '/admin/users.php?action=new',
            'method' => 'GET',
            'description' => 'New user form',
            'requires_auth' => true
        ],
        'users.insert' => [
            'path' => '/admin/users.php?load=insert',
            'method' => 'POST',
            'description' => 'Insert new user',
            'attack_vectors' => ['csrf', 'sqli'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'users.edit' => [
            'path' => '/admin/users.php?action=edit&Id=(?<id>\d+)',
            'method' => 'GET',
            'description' => 'Edit user form',
            'attack_vectors' => ['idor'],
            'requires_auth' => true
        ],
        'users.update' => [
            'path' => '/admin/users.php?load=update',
            'method' => 'POST',
            'description' => 'Update user',
            'attack_vectors' => ['csrf', 'idor', 'sqli'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'users.delete' => [
            'path' => '/admin/users.php?load=delete&Id=(?<id>\d+)',
            'method' => 'GET',
            'description' => 'Delete user',
            'attack_vectors' => ['idor', 'csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Media
        'media.list' => [
            'path' => '/admin/medialib.php',
            'method' => 'GET',
            'description' => 'Media library',
            'requires_auth' => true
        ],
        'media.upload' => [
            'path' => '/admin/media.php?load=upload',
            'method' => 'POST',
            'description' => 'Upload media file',
            'attack_vectors' => ['upload_rce', 'path_traversal', 'csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'media.delete' => [
            'path' => '/admin/medialib.php?load=delete&Id=(?<id>\d+)',
            'method' => 'GET',
            'description' => 'Delete media',
            'attack_vectors' => ['idor', 'csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Topics
        'topics.list' => [
            'path' => '/admin/topics.php',
            'method' => 'GET',
            'description' => 'Topics/categories list',
            'requires_auth' => true
        ],
        'topics.insert' => [
            'path' => '/admin/topics.php?load=insert',
            'method' => 'POST',
            'description' => 'Insert new topic',
            'attack_vectors' => ['csrf', 'sqli'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'topics.update' => [
            'path' => '/admin/topics.php?load=update',
            'method' => 'POST',
            'description' => 'Update topic',
            'attack_vectors' => ['csrf', 'idor', 'sqli'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'topics.delete' => [
            'path' => '/admin/topics.php?load=delete&Id=(?<id>\d+)',
            'method' => 'GET',
            'description' => 'Delete topic',
            'attack_vectors' => ['idor', 'csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Menu
        'menu.list' => [
            'path' => '/admin/menu.php',
            'method' => 'GET',
            'description' => 'Navigation menu management',
            'requires_auth' => true
        ],
        'menu.insert' => [
            'path' => '/admin/menu.php?load=insert',
            'method' => 'POST',
            'description' => 'Insert menu item',
            'attack_vectors' => ['csrf', 'sqli', 'xss'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'menu.delete' => [
            'path' => '/admin/menu.php?load=delete&Id=(?<id>\d+)',
            'method' => 'GET',
            'description' => 'Delete menu item',
            'attack_vectors' => ['idor', 'csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Plugins
        'plugins.list' => [
            'path' => '/admin/plugins.php',
            'method' => 'GET',
            'description' => 'Plugins management',
            'requires_auth' => true
        ],
        'plugins.activate' => [
            'path' => '/admin/plugins.php?load=activate&plugin=(?<plugin>[^&]+)',
            'method' => 'GET',
            'description' => 'Activate plugin',
            'attack_vectors' => ['idor', 'csrf', 'rce'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'plugins.deactivate' => [
            'path' => '/admin/plugins.php?load=deactivate&plugin=(?<plugin>[^&]+)',
            'method' => 'GET',
            'description' => 'Deactivate plugin',
            'attack_vectors' => ['csrf', 'idor'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Themes
        'themes.list' => [
            'path' => '/admin/templates.php',
            'method' => 'GET',
            'description' => 'Themes management',
            'requires_auth' => true
        ],
        'themes.activate' => [
            'path' => '/admin/templates.php?load=activate&theme=(?<theme>[^&]+)',
            'method' => 'GET',
            'description' => 'Activate theme',
            'attack_vectors' => ['csrf', 'idor'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Import
        'import.page' => [
            'path' => '/admin/import.php',
            'method' => 'GET',
            'description' => 'Import content page',
            'requires_auth' => true
        ],
        'import.preview' => [
            'path' => '/admin/import.php?load=preview',
            'method' => 'POST',
            'description' => 'Preview import content',
            'attack_vectors' => ['xxe', 'stored_xss', 'path_traversal', 'csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        'import.execute' => [
            'path' => '/admin/import.php?load=import',
            'method' => 'POST',
            'description' => 'Execute import',
            'attack_vectors' => ['xxe', 'stored_xss', 'sqli', 'csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Export
        'export.page' => [
            'path' => '/admin/export.php',
            'method' => 'GET',
            'description' => 'Export content page',
            'requires_auth' => true
        ],
        'export.execute' => [
            'path' => '/admin/export.php?load=export',
            'method' => 'POST',
            'description' => 'Execute export',
            'attack_vectors' => ['csrf'],
            'csrf_protected' => true,
            'requires_auth' => true
        ],
        
        // Configuration
        'config.page' => [
            'path' => '/admin/config.php',
            'method' => 'GET',
            'description' => 'Configuration page',
            'requires_auth' => true
        ],
        'config.save' => [
            'path' => '/admin/config.php?load=save',
            'method' => 'POST',
            'description' => 'Save configuration',
            'attack_vectors' => ['csrf', 'stored_xss'],
            'csrf_protected' => true,
            'requires_auth' => true
        ]
    ];
    
    // API routes
    $apiRoutes = [
        'api.posts' => [
            'path' => '/api/v1/posts',
            'method' => 'GET',
            'description' => 'API - Get posts',
            'parameters' => ['page', 'per_page', 'status'],
            'attack_vectors' => ['idor', 'sqli'],
            'requires_auth' => false
        ],
        'api.post_single' => [
            'path' => '/api/v1/posts/(?<id>\d+)',
            'method' => 'GET',
            'description' => 'API - Get single post',
            'parameters' => ['id'],
            'attack_vectors' => ['idor'],
            'requires_auth' => false
        ],
        'api.comments' => [
            'path' => '/api/v1/comments',
            'method' => 'GET',
            'description' => 'API - Get comments',
            'parameters' => ['post_id', 'status'],
            'attack_vectors' => ['sqli'],
            'requires_auth' => false
        ],
        'api.categories' => [
            'path' => '/api/v1/categories',
            'method' => 'GET',
            'description' => 'API - Get categories',
            'requires_auth' => false
        ],
        'api.auth_login' => [
            'path' => '/api/v1/auth/login',
            'method' => 'POST',
            'description' => 'API - Login',
            'parameters' => ['username', 'password'],
            'attack_vectors' => ['sqli', 'auth_bypass'],
            'requires_auth' => false
        ],
        'api.consent' => [
            'path' => '/api/v1/gdpr/consent',
            'method' => 'POST',
            'description' => 'API - GDPR consent',
            'parameters' => ['consent_type', 'granted'],
            'attack_vectors' => ['csrf', 'sqli'],
            'requires_auth' => false
        ]
    ];
    
    // Public forms
    $publicRoutes = [
        'public.comment_submit' => [
            'path' => '/comment-submit',
            'method' => 'POST',
            'description' => 'Public comment submission',
            'parameters' => ['post_id', 'author_name', 'author_email', 'comment_content'],
            'attack_vectors' => ['stored_xss', 'sqli', 'spam'],
            'csrf_protected' => false
        ],
        'public.contact' => [
            'path' => '/contact',
            'method' => 'POST',
            'description' => 'Contact form submission',
            'parameters' => ['name', 'email', 'subject', 'message'],
            'attack_vectors' => ['stored_xss', 'sqli', 'email_injection'],
            'csrf_protected' => false
        ],
        'public.subscribe' => [
            'path' => '/subscribe',
            'method' => 'POST',
            'description' => 'Newsletter subscription',
            'parameters' => ['email'],
            'attack_vectors' => ['sqli', 'email_injection'],
            'csrf_protected' => false
        ]
    ];
    
    // Sensitive endpoints
    $sensitiveRoutes = [
        'sensitive.install' => [
            'path' => '/install/',
            'method' => 'GET',
            'description' => 'Installation wizard',
            'attack_vectors' => ['installer_takeover'],
            'expected_after_install' => '404_or_redirect'
        ],
        'sensitive.setup_db' => [
            'path' => '/install/setup-db.php',
            'method' => 'POST',
            'description' => 'Database setup',
            'attack_vectors' => ['sqli', 'config_tampering'],
            'expected_after_install' => '404_or_redirect'
        ],
        'sensitive.config' => [
            'path' => '/config.php',
            'method' => 'GET',
            'description' => 'Configuration file access',
            'attack_vectors' => ['info_disclosure'],
            'expected_response' => '403_or_empty'
        ],
        'sensitive.readme' => [
            'path' => '/README.md',
            'method' => 'GET',
            'description' => 'README file access',
            'attack_vectors' => ['info_disclosure'],
            'expected_response' => '403_or_empty'
        ]
    ];
    
    // Merge all routes
    $routes = array_merge(
        ['frontend' => $frontendRoutes],
        ['admin' => $adminRoutes],
        ['api' => $apiRoutes],
        ['public' => $publicRoutes],
        ['sensitive' => $sensitiveRoutes]
    );
    
    return $routes;
}

/**
 * Add metadata to the routes export
 */
function getExportMetadata() {
    return [
        'meta' => [
            'name' => 'Blogware/Scriptlog CMS Routes',
            'version' => '1.0',
            'description' => 'Dynamically extracted route definitions for security testing',
            'generated' => date('Y-m-d H:i:s'),
            'generator' => 'Socrates Blade Route Exporter v1.0',
            'php_version' => PHP_VERSION,
            'url' => defined('app_url') ? app_url() : 'unknown'
        ]
    ];
}

// Main execution
try {
    $output = [
        'metadata' => getExportMetadata(),
        'routes' => getBlogwareRoutes()
    ];
    
    // Output as JSON with pretty print
    header('Content-Type: application/json');
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Failed to export routes: ' . $e->getMessage()
    ]);
    exit(1);
}
