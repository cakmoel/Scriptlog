<?php
/**
 * AssetIntegrityTest
 *
 * Verifies that every sha384 Subresource-Integrity (SRI) attribute rendered
 * by the blog theme templates matches the digest of the asset actually
 * served from disk, and that every cache-busting `v` query token matches
 * the digest-derived token produced by tmp/minify.php's cache_busted_url().
 *
 * Background: regenerating .min assets on disk without refreshing the
 * integrity attribute makes browsers refuse to execute the asset (SRI
 * mismatch), which silently breaks styling and scripts on the live site.
 * tmp/minify.php syncs hashes on rebuild; this suite is the guard that
 * catches a template/asset pair that falls out of sync between rebuilds.
 */

use PHPUnit\Framework\TestCase;

class AssetIntegrityTest extends TestCase
{
    private const THEME_DIR = 'blog';
    private const THEME_DIR_EXPRESSION = '<?= theme_dir(); ?>';

    /**
     * Collect every (template, asset URL, SHA-384 attribute) triple from the
     * blog theme templates, mirroring the href/src-anchored pattern used by
     * sync_integrity_hashes() in tmp/minify.php (so id="..." attributes are
     * not mistaken for asset URLs).
     *
     * @return array[]
     */
    public function integrityAttributesProvider(): array
    {
        $root = dirname(__DIR__, 2);
        $cases = [];

        foreach (glob($root . '/public/themes/' . self::THEME_DIR . '/*.php') as $template) {
            $html = file_get_contents($template);
            preg_match_all(
                '/\b(?:href|src)="([^"]+)"\s+integrity="sha384-([^"]+)"/',
                $html,
                $tags,
                PREG_SET_ORDER
            );

            foreach ($tags as $tag) {
                $path = $this->resolveAssetPath($tag[1], $root);

                if (null === $path) {
                    // Unresolvable external/qualified URLs (CDN cross-origin,
                    // protocol-relative, etc.) are not covered by this suite.
                    continue;
                }

                $cases[basename($template) . ' :: ' . $tag[1]] = [
                    basename($template),
                    $path,
                    $tag[1],
                    $tag[2],
                ];
            }
        }

        return $cases;
    }

    /**
     * Resolve an asset URL from a template against the repository the same
     * way sync_integrity_hashes() does.
     */
    private function resolveAssetPath(string $url, string $root): ?string
    {
        if (strpos($url, self::THEME_DIR_EXPRESSION) === 0) {
            $resolved = $root . '/public/themes/' . self::THEME_DIR . '/'
                . substr($url, strlen(self::THEME_DIR_EXPRESSION));
        } else {
            $path = (string) parse_url(ltrim($url, '/'), PHP_URL_PATH);

            if ('' === $path) {
                return null;
            }

            $resolved = $root . '/' . $path;
        }

        return strtok($resolved, '?');
    }

    /**
     * @dataProvider integrityAttributesProvider
     */
    public function testReferencedAssetExists(string $template, string $path, string $url, string $attributeHash): void
    {
        $this->assertFileExists(
            $path,
            sprintf('%s references "%s" but the asset is missing on disk.', $template, $url)
        );
    }

    /**
     * @dataProvider integrityAttributesProvider
     */
    public function testIntegrityHashMatchesServedAsset(string $template, string $path, string $url, string $attributeHash): void
    {
        $actual = base64_encode(hash('sha384', file_get_contents($path), true));

        $this->assertSame(
            $actual,
            $attributeHash,
            sprintf(
                'Stale integrity hash in %s for "%s": browser will block the asset (SRI mismatch). Run "php tmp/minify.php %s" to resync.',
                $template,
                $url,
                self::THEME_DIR
            )
        );
    }

    /**
     * @dataProvider integrityAttributesProvider
     */
    public function testCacheBustingTokenMatchesAssetDigest(string $template, string $path, string $url, string $attributeHash): void
    {
        $query = parse_url(str_replace(self::THEME_DIR_EXPRESSION, '', $url), PHP_URL_QUERY);

        if (null === $query || false === strpos($query, 'v=')) {
            $this->addToAssertionCount(1); // No cache-buster on this asset; nothing to verify.
            return;
        }

        parse_str($query, $params);
        $actual = base64_encode(hash('sha384', file_get_contents($path), true));
        $expectedToken = substr(hash('sha256', $actual), 0, 12);

        $this->assertSame(
            $expectedToken,
            (string) $params['v'],
            sprintf(
                'Cache-busting token for "%s" in %s no longer matches the asset digest: returning visitors with a 30-day cache would receive stale bytes blocked by the new integrity hash.',
                $url,
                $template
            )
        );
    }
}
