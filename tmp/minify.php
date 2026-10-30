<?php
/**
 * Simple Minifier Script for Scriptlog Theme
 * Minifies CSS and JS files in theme directories
 *
 * Usage: php tmp/minify.php            # every theme under public/themes/
 *        php tmp/minify.php blog       # only public/themes/blog/
 *
 * The optional theme argument keeps a routine asset rebuild from touching
 * unrelated themes (each rebuild also rewrites the sha384 integrity hashes in
 * that theme's templates, so an unscoped run produces diffs you did not ask
 * for).
 * 
 * @category function to minify CSS and JS files in Scriptlog theme directories 
 * @author Nirmala Adiba Khanza <nirmala.adiba.khanza@gmail.com>
 * @license MIT 
 * @version 1.0.0
 * @since 1.0.0
 * 
 */

function minify_css($input) {
    if (empty($input)) return $input;
    // Remove comments
    $output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $input);
    // Remove whitespace
    $output = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $output);
    // Remove redundant semi-colons and spaces
    $output = preg_replace(['/((?:\s|;)+)/', '/\s*([{};:>+])\s*/'], ['$1', '$1'], $output);
    $output = str_replace(';}', '}', $output);
    return trim($output);
}

function minify_js($input) {
    if (empty($input)) return $input;
    // VERY basic JS minification - removing comments and redundant whitespace
    // Note: This is not a full compressor like Terser, but safe for simple scripts
    $output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $input); // multi-line comments
    $output = preg_replace('!^\s*//.*$!m', '', $output); // single-line comments at start of line
    $output = preg_replace('/(\n|\r)+/', "\n", $output); // reduce multiple newlines
    $output = preg_replace('/[ \t]+/', ' ', $output); // reduce multiple spaces/tabs
    return trim($output);
}

/**
 * Add or refresh the `v` cache-busting query parameter on an asset URL.
 *
 * The token is derived from the asset digest, so the URL changes exactly
 * when the file content changes. This matters because theme assets are
 * served with a 30-day `Cache-Control: max-age`: a returning visitor keeps
 * the previous .min bytes, and the newly written integrity hash would make
 * the browser block that stale response outright.
 *
 * @param string $url    Asset URL as written in the template.
 * @param string $digest Base64-encoded sha384 digest of the asset.
 * @return string URL carrying an up-to-date `v` parameter.
 */
function cache_busted_url($url, $digest)
{
    $token = substr(hash('sha256', $digest), 0, 12);
    $prefix = '';

    // Keep a leading PHP expression such as theme_dir() intact: its question
    // mark belongs to the PHP tag, not to the asset's query string.
    if (preg_match('/^(<\?.*?\?>)/', $url, $match)) {
        $prefix = $match[1];
        $url = substr($url, strlen($prefix));
    }

    $parts = explode('?', $url, 2);
    $query = [];

    if (isset($parts[1]) && $parts[1] !== '') {
        parse_str($parts[1], $query);
    }

    $query['v'] = $token;

    return $prefix . $parts[0] . '?' . http_build_query($query);
}

/**
 * Recompute and rewrite the sha384 integrity hashes in a theme's PHP
 * templates so they always match the on-disk (served) minified assets.
 *
 * Without this, regenerating .min.css/.min.js files leaves stale
 * integrity attributes behind in header.php/footer.php, and browsers
 * block the assets (SRI mismatch), which silently breaks styling and
 * scripts on the live site.
 *
 * When a hash actually changes the asset URL is cache-busted as well,
 * otherwise returning visitors keep the old cached bytes and the new
 * integrity hash blocks them.
 *
 * @param string $theme_dir Absolute path to the theme directory.
 * @param string $repo_root Absolute path to the repository root.
 * @return int Number of integrity attributes updated.
 */
function sync_integrity_hashes($theme_dir, $repo_root)
{
    $updated = 0;

    foreach (glob($theme_dir . '/*.php') as $template) {
        $html = file_get_contents($template);

        if (strpos($html, 'integrity="sha384-') === false) {
            continue;
        }

        $html = preg_replace_callback(
            '/\b(?:href|src)="([^"]+)"\s+integrity="sha384-([^"]+)"/',
            function ($tag) use ($theme_dir, $repo_root, &$updated) {
                $tag_html = $tag[0];
                $url = $tag[1];

                if (strpos($url, '<?= theme_dir(); ?>') === 0) {
                    $path = $theme_dir . '/' . substr($url, strlen('<?= theme_dir(); ?>'));
                } else {
                    $rel = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
                    $path = $repo_root . '/' . $rel;
                }

                $path = strtok($path, '?');

                if (!is_file($path)) {
                    return $tag_html;
                }

                $actual = base64_encode(hash('sha384', file_get_contents($path), true));

                if ($tag[2] === $actual) {
                    return $tag_html;
                }

                $updated++;

                $tag_html = str_replace(
                    '"' . $url . '"',
                    '"' . cache_busted_url($url, $actual) . '"',
                    $tag_html
                );

                return str_replace('integrity="sha384-' . $tag[2] . '"', 'integrity="sha384-' . $actual . '"', $tag_html);
            },
            $html
        );

        file_put_contents($template, $html);
    }

    return $updated;
}

$themes_dir = __DIR__ . '/../public/themes';
$repo_root = dirname(__DIR__);
$theme_folders = array_values(array_diff(scandir($themes_dir), ['.', '..', 'index.php', 'maintenance.php']));

// Optional CLI scope: `php tmp/minify.php blog` rebuilds one theme only, so
// unrelated .min assets and integrity hashes stay untouched.
$requested_theme = isset($argv[1]) ? trim((string) $argv[1]) : '';

if ($requested_theme !== '') {
    if (!in_array($requested_theme, $theme_folders, true) || !is_dir($themes_dir . '/' . $requested_theme)) {
        fwrite(STDERR, "Unknown theme: {$requested_theme}\n");
        fwrite(STDERR, 'Available themes: ' . implode(', ', $theme_folders) . "\n");
        exit(1);
    }

    $theme_folders = [$requested_theme];
    echo "Scoped to theme: {$requested_theme}\n";
}

$total_css = 0;
$total_js = 0;

foreach ($theme_folders as $theme) {
    $theme_path = $themes_dir . '/' . $theme . '/assets';
    
    // Skip if assets directory doesn't exist
    if (!is_dir($theme_path)) {
        continue;
    }
    
    $css_dir = $theme_path . '/css';
    $js_dir = $theme_path . '/js';
    
    // Process CSS files
    if (is_dir($css_dir)) {
        echo "Minifying CSS files for theme: $theme\n";
        foreach (glob("$css_dir/*.css") as $file) {
            if (strpos($file, '.min.css') !== false) continue;
            $min_file = str_replace('.css', '.min.css', $file);
            echo "  Processing " . basename($file) . " -> " . basename($min_file) . "\n";
            file_put_contents($min_file, minify_css(file_get_contents($file)));
            $total_css++;
        }
    }
    
    // Process JS files  
    if (is_dir($js_dir)) {
        echo "Minifying JS files for theme: $theme\n";
        foreach (glob("$js_dir/*.js") as $file) {
            if (strpos($file, '.min.js') !== false) continue;
            $min_file = str_replace('.js', '.min.js', $file);
            echo "  Processing " . basename($file) . " -> " . basename($min_file) . "\n";
            file_put_contents($min_file, minify_js(file_get_contents($file)));
            $total_js++;
        }
    }

    // Keep integrity hashes in theme templates in sync with regenerated assets
    $theme_dir = $themes_dir . '/' . $theme;
    $hashes_updated = sync_integrity_hashes($theme_dir, $repo_root);
    if ($hashes_updated > 0) {
        echo "  Updated $hashes_updated stale integrity hash(es) in theme templates.\n";
    }
}

echo "\nMinification complete.\n";
echo "Total CSS files minified: $total_css\n";
echo "Total JS files minified: $total_js\n";