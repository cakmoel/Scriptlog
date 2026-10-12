# =============================================
# SECURITY RULES - Block access to sensitive files
# =============================================

# Block access to all files starting with a dot (except .htaccess itself)
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block access to specific sensitive file extensions
<FilesMatch "\.(env|log|sql|sh|bak|old|tmp|swp|yaml|yml|ini|dist|example)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block access to config.php
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

# Block access to composer files
<Files "composer.json">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.lock">
    Order allow,deny
    Deny from all
</Files>

# Block access to package files
<Files "package.json">
    Order allow,deny
    Deny from all
</Files>

<Files "package-lock.json">
    Order allow,deny
    Deny from all
</Files>

# Block access to .git directory and its contents
<IfModule mod_rewrite.c>
    RewriteRule ^\.git - [F,L]
</IfModule>

# Block access to sensitive directories
<IfModule mod_rewrite.c>
    RewriteRule ^(lib|install|config)/ - [F,L]
</IfModule>

# =============================================
# ScriptLog URL Rewriting
# =============================================
# START ScriptLog
# The directives (lines) between "START ScriptLog" and "FINISH ScriptLog" are
# dynamically generated, and should only be modified if you know what you are doing.
# Any changes to these lines will be overwritten by scriptlog herself on setting permalink.
<IfModule mod_rewrite.c>
RewriteEngine On
#RewriteCond %{HTTP_USER_AGENT} libwww-perl.* 
#RewriteRule .* – [F,L]
# The RewriteBase of the system (change if you are using this system in a sub-folder)
#RewriteBase /
# Force HTTPS and remove www (canonical: https://yourdomain.TLD) 
#RewriteCond %{HTTPS} off [OR]
#RewriteCond %{HTTP_HOST} ^www\.yourdomain\.TLD$
#RewriteRule ^/?$ "https\:\/\/yourdomain\.TLD\/" [R=301, L]
# Ensure all front-end UI-UX files readable
RewriteCond %{REQUEST_FILENAME} !\.(ico|css|png|jpg|jpeg|webp|gif|js|txt|htm|html|eot|svg|ttf|woff|woff2|webm|ogg|mp4|wav|mp3|pdf)$ [NC]
RewriteRule ^public/.*$ index.php
# API routes
RewriteCond %{REQUEST_URI} ^/api [NC]
RewriteRule ^api/(.*)$ api/index.php [QSA,L]
RewriteCond %{REQUEST_FILENAME} !-d 
RewriteCond %{REQUEST_FILENAME} !-f 
RewriteCond %{REQUEST_FILENAME} !-l 
# Only route known application prefixes to index.php
RewriteRule ^(post|page|blog|category|archive|archives|tag|privacy|download|download_file)(/.*)?$ index.php [QSA,L]
RewriteRule ^$ index.php [QSA,L]
RewriteRule ^[a-z]{2}/(post|page|blog|category|archive|archives|tag|privacy|download|download_file)(/.*)?$ index.php [QSA,L]
RewriteRule ^[a-z]{2}/?$ index.php [QSA,L]
</IfModule>
# FINISH ScriptLog

<IfModule mod_deflate.c>
  # Compress HTML, CSS, JavaScript, Text, XML and fonts
  AddOutputFilterByType DEFLATE application/javascript
  AddOutputFilterByType DEFLATE application/rss+xml
  AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
  AddOutputFilterByType DEFLATE application/x-font
  AddOutputFilterByType DEFLATE application/x-font-opentype
  AddOutputFilterByType DEFLATE application/x-font-otf
  AddOutputFilterByType DEFLATE application/x-font-truetype
  AddOutputFilterByType DEFLATE application/x-font-ttf
  AddOutputFilterByType DEFLATE application/x-javascript
  AddOutputFilterByType DEFLATE application/xhtml+xml
  AddOutputFilterByType DEFLATE application/xml
  AddOutputFilterByType DEFLATE font/opentype
  AddOutputFilterByType DEFLATE font/otf
  AddOutputFilterByType DEFLATE font/ttf
  AddOutputFilterByType DEFLATE image/svg+xml
  AddOutputFilterByType DEFLATE image/x-icon
  AddOutputFilterByType DEFLATE text/css
  AddOutputFilterByType DEFLATE text/html
  AddOutputFilterByType DEFLATE text/javascript
  AddOutputFilterByType DEFLATE text/plain
  AddOutputFilterByType DEFLATE text/xml

  # Remove browser bugs (only needed for really old browsers)
  BrowserMatch ^Mozilla/4 gzip-only-text/html
  BrowserMatch ^Mozilla/4\.0[678] no-gzip
  BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
  Header append Vary User-Agent
</IfModule>

<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresDefault "access plus 1 month"
  ExpiresByType image/x-icon "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType application/x-shockwave-flash "access plus 1 month"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType text/javascript "access plus 1 month"
  ExpiresByType application/x-javascript "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType application/pdf "access plus 1 month"
  ExpiresByType application/x-font-woff "access plus 1 year"
  ExpiresByType application/x-font-woff2 "access plus 1 year"
  ExpiresByType font/woff "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
</IfModule>
