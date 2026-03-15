# Plugin Developer Guide

## Overview

The Blogware/Scriptlog plugin system uses a hook-based architecture. Plugins register hooks using the `clip()` function, and those hooks are executed at specific points in the application.

## How Plugin Activation Works

### 1. Plugin Installation (ZIP Upload)
When a user uploads a plugin ZIP:
1. `PluginController::installPlugin()` validates the ZIP
2. `upload_plugin()` extracts and validates structure
3. Plugin info from `plugin.ini` is saved to `tbl_plugin`
4. Plugin appears in admin panel (status: Disabled)

### 2. Plugin Activation
When a user clicks "Enable":
1. `PluginController::enablePlugin($id)` is called
2. `PluginService::activateInstalledPlugin()`:
   - Executes `schema.sql` if exists (for database tables)
   - Calls `enable_plugin($plugin_path)` to load PHP files
   - Updates `plugin_status` = 'Y' in database
3. The plugin's PHP class is loaded, hooks are registered

### 3. Plugin Deactivation
When a user clicks "Disable":
1. `PluginController::disablePlugin($id)` is called
2. `PluginService::deactivateInstalledPlugin()` updates `plugin_status` = 'N'
3. Plugin files remain but hooks are not loaded

### 4. Plugin Deletion
When a user clicks "Delete":
1. `PluginController::remove($id)` is called
2. `PluginService::removePlugin()` deletes:
   - Plugin directory from `admin/plugins/`
   - Database record from `tbl_plugin`

---

## Creating a Plugin

### Required Directory Structure
```
admin/plugins/[plugin-name]/
├── plugin.ini           # REQUIRED - Plugin configuration
├── YourClassFile.php   # REQUIRED - Main plugin class
├── functions.php       # OPTIONAL - Helper functions
└── schema.sql         # OPTIONAL - Database schema
```

### Step 1: Create plugin.ini
```ini
[INFO]
plugin_name = "My Plugin Name"
plugin_description = "Description of what the plugin does"
plugin_level = "administrator"
plugin_version = "1.0.0"
plugin_author = "Your Name"
plugin_loader = "MyPlugin"
plugin_action = "my-plugin"
```

**Fields:**
| Field | Required | Description |
|-------|----------|--------------|
| plugin_name | Yes | Display name |
| plugin_description | Yes | What the plugin does |
| plugin_level | Yes | "administrator" or "manager" |
| plugin_version | Yes | Semantic version (e.g., 1.0.0) |
| plugin_author | Yes | Author name |
| plugin_loader | Yes | PHP class filename (without .php) |
| plugin_action | Yes | Action identifier for routing |

### Step 2: Create Main Plugin Class
```php
<?php defined('SCRIPTLOG') || die("Direct access not permitted");

class MyPlugin
{
    private $pluginDir;
    
    public function __construct()
    {
        $this->pluginDir = dirname(__FILE__);
        $this->registerHooks();
    }
    
    private function registerHooks()
    {
        // Register frontend content hook
        clip('clip_my_plugin', null, function($content = '') {
            return $this->frontendContent($content);
        });
        
        // Register admin page hook
        clip('clip_my_plugin_admin', null, function() {
            return $this->adminPage();
        });
    }
    
    public function activate()
    {
        // Runs on activation - create tables, set options
        return true;
    }
    
    public function deactivate()
    {
        // Runs on deactivation - cleanup temporary data
        return true;
    }
    
    public function uninstall()
    {
        // Runs on deletion - remove all plugin data
        return true;
    }
    
    public function adminPage()
    {
        return '<div class="box">
            <div class="box-header"><h3>My Plugin</h3></div>
            <div class="box-body">
                <p>Plugin is active!</p>
            </div>
        </div>';
    }
    
    public function frontendContent($content = '')
    {
        return $content . '<div class="my-plugin">Hello from my plugin!</div>';
    }
}
```

### Step 3: Create Helper Functions (Optional)
```php
<?php defined('SCRIPTLOG') || die("Direct access not permitted");

function my_plugin_instance()
{
    static $instance = null;
    if (null === $instance) {
        $instance = new MyPlugin();
    }
    return $instance;
}

function my_plugin_display($content = '')
{
    return my_plugin_instance()->frontendContent($content);
}
```

### Step 4: Create Database Schema (Optional)
```sql
-- Create tables for your plugin
CREATE TABLE IF NOT EXISTS tbl_my_plugin (
    ID BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add DROP statement for uninstall (commented by default)
-- DROP TABLE IF EXISTS tbl_my_plugin;
```

---

## Hook System Reference

### The clip() Function
```php
clip('hook_name', $value, $callback);
```

**Parameters:**
- `hook_name`: Unique identifier for the hook
- `$value`: Value to pass through the hook chain
- `$callback`: Function to process the value (or null to execute hooks)

**Usage:**
```php
// Register a hook (attach callback)
clip('my_hook', null, function($value) {
    return $value . ' modified';
});

// Execute all callbacks attached to hook
$result = clip('my_hook', 'original');
// $result = 'original modified'
```

### Available Hooks

#### Frontend Hooks
| Hook Name | Description | Value Passed |
|-----------|-------------|--------------|
| `clip_[plugin_name]` | Main plugin hook | Content string |
| `clip_content` | Content filter | Post content |
| `clip_footer` | Footer section | - |
| `clip_header` | Header section | - |

#### Admin Hooks
| Hook Name | Description |
|-----------|-------------|
| `clip_[plugin_name]_admin` | Admin page content |
| `clip_plugin_menu` | Plugin navigation |

---

## Invoking Plugins

### From Code
```php
// Check if plugin is enabled
if (is_plugin_enabled('my-plugin')) {
    // Invoke plugin hook
    $content = invoke_plugin('my-plugin', $content);
}
```

### Available Plugin Functions
```php
// Check if plugin exists in database
is_plugin_exist('plugin-name');

// Check if plugin is enabled
is_plugin_enabled('plugin-name');

// Execute plugin hook
invoke_plugin('plugin-name', $args);

// Set plugin navigation in admin sidebar
set_plugin_navigation('plugin-name');
```

---

## Example: Complete Social Share Plugin

### Directory Structure
```
admin/plugins/social-share/
├── plugin.ini
├── SocialSharePlugin.php
└── functions.php
```

### plugin.ini
```ini
[INFO]
plugin_name = "Social Share"
plugin_description = "Add social sharing buttons to posts"
plugin_level = "administrator"
plugin_version = "1.0.0"
plugin_author = "Developer Name"
plugin_loader = "SocialSharePlugin"
plugin_action = "social-share"
```

### SocialSharePlugin.php
```php
<?php defined('SCRIPTLOG') || die("Direct access not permitted");

class SocialSharePlugin
{
    private $pluginDir;
    
    public function __construct()
    {
        $this->pluginDir = dirname(__FILE__);
        $this->registerHooks();
    }
    
    private function registerHooks()
    {
        // Add social share buttons after post content
        clip('clip_social_share', null, function($content = '') {
            return $this->addSocialButtons($content);
        });
        
        // Add admin settings page
        clip('clip_social_share_admin', null, function() {
            return $this->adminPage();
        });
    }
    
    public function activate()
    {
        return true;
    }
    
    public function deactivate()
    {
        return true;
    }
    
    public function addSocialButtons($content = '')
    {
        $buttons = '<div class="social-share" style="margin-top:20px; padding:15px; border-top:1px solid #eee;">
            <h4>Share this post</h4>
            <a href="#" class="btn btn-primary">Facebook</a>
            <a href="#" class="btn btn-info">Twitter</a>
            <a href="#" class="btn btn-danger">Google+</a>
        </div>';
        
        return $content . $buttons;
    }
    
    public function adminPage()
    {
        return '<div class="box box-primary">
            <div class="box-header"><h3>Social Share Settings</h3></div>
            <div class="box-body">
                <p>Configure your social share buttons here.</p>
            </div>
        </div>';
    }
}
```

### functions.php
```php
<?php defined('SCRIPTLOG') || die("Direct access not permitted");

function social_share_plugin()
{
    static $instance = null;
    if (null === $instance) {
        $instance = new SocialSharePlugin();
    }
    return $instance;
}

function social_share_buttons($content = '')
{
    return social_share_plugin()->addSocialButtons($content);
}
```

---

## Testing Your Plugin

### Manual Testing Checklist

1. **Install**: Upload ZIP → Should appear in plugin list (disabled)
2. **Activate**: Click Enable → Status changes to "Enabled"
3. **Test functionality**: 
   - Frontend: Visit site, check for plugin output
   - Backend: Visit plugin admin page if available
4. **Deactivate**: Click Disable → Status changes to "Disabled"
5. **Reactivate**: Click Enable again → Should work
6. **Delete**: Click Delete → Plugin removed from list and directory

### Debug Tips

```php
// Add debugging to your plugin
public function __construct()
{
    $this->pluginDir = dirname(__FILE__);
    
    // Debug: Log plugin initialization
    error_log('MyPlugin: Initialized at ' . date('Y-m-d H:i:s'));
    
    $this->registerHooks();
}
```

---

## Common Issues

| Issue | Solution |
|-------|----------|
| Plugin not appearing in list | Check plugin.ini syntax |
| Plugin loads but no output | Ensure hooks are registered in constructor |
| Activation fails | Check schema.sql syntax |
| Hook not firing | Verify `clip()` call exists and plugin is enabled |

---

## Best Practices

1. **Always** check `defined('SCRIPTLOG')` at the top of PHP files
2. **Always** use unique hook names with plugin prefix
3. **Always** sanitize user input in your plugin
4. **Avoid** modifying core files - use hooks instead
5. **Document** your plugin's hooks and functions
6. **Test** on a development environment first
7. **Version** your plugin using semantic versioning
