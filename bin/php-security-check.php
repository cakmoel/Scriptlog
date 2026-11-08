<?php
/**
 * php-security-check.php
 *
 * CLI gate for the October 2026 PHP core RCE advisories:
 * - CVE-2026-109618 (RCE via OOM corruption)
 * - CVE-2026-109619 (RCE via stale global state)
 * - CVE-2026-115560 (informational, session upload progress)
 *
 * The only complete fix is a patched PHP runtime. This script verifies the
 * runtime the code would actually execute on and reports the INI knobs the
 * advisories identify as exploitation paths.
 *
 * Usage:
 *   php bin/php-security-check.php
 *   php bin/php-security-check.php --json
 *
 * Exit codes:
 *   0  Runtime is patched (warnings may still be printed)
 *   1  Runtime is affected by either CVE
 *   2  Invalid usage or non-CLI invocation
 *
 * @category CLI Utility
 * @license MIT
 * @version 1.0
 */

use Scriptlog\Core\PhpRuntimeSecurityGuard;

if (PHP_SAPI !== 'cli') {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 403 Forbidden', true, 403);
    exit('This script is CLI-only.');
}

$options = getopt('', array('json', 'help'));

if (!is_array($options)) {
    $options = array();
}

if (isset($options['help'])) {
    fwrite(STDOUT, "Usage: php bin/php-security-check.php [--json]\n");
    fwrite(STDOUT, "Exit 0 when PHP is patched, 1 when affected by CVE-2026-109618/109619.\n");
    exit(0);
}

$root = dirname(__DIR__);

require_once $root . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'common.php';
require_once $root . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'PhpRuntimeSecurityGuard.php';

$result = PhpRuntimeSecurityGuard::evaluate();
$json = isset($options['json']);

if ($json) {
    fwrite(
        STDOUT,
        json_encode(
            array(
                'result' => $result,
                'fixed_releases' => PhpRuntimeSecurityGuard::fixedReleases(),
                'sapi' => PHP_SAPI,
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ) . PHP_EOL
    );
} else {
    fwrite(STDOUT, "PHP runtime security check\n");
    fwrite(STDOUT, str_repeat('=', 26) . PHP_EOL);
    fwrite(STDOUT, sprintf("Version : %s (%s)\n", $result['version'], PHP_SAPI));
    fwrite(STDOUT, sprintf("Branch  : %s\n", $result['branch']));
    fwrite(STDOUT, sprintf("Patched : %s\n", $result['patched'] ? 'yes' : 'NO'));
    fwrite(STDOUT, sprintf("Severity: %s\n", $result['severity']));

    if ($result['findings'] === array()) {
        fwrite(STDOUT, "Findings: none\n");
    } else {
        fwrite(STDOUT, "Findings:\n");

        foreach ($result['findings'] as $finding) {
            fwrite(
                STDOUT,
                sprintf("  [%s] %s - %s\n", strtoupper($finding['severity']), $finding['code'], $finding['message'])
            );
        }
    }

    if ($result['mitigations'] !== array()) {
        fwrite(STDOUT, "Mitigations:\n");

        foreach ($result['mitigations'] as $mitigation) {
            fwrite(STDOUT, '  - ' . $mitigation . PHP_EOL);
        }
    }

    fwrite(STDOUT, "Fixed releases: ");

    $fixed = array();

    foreach (PhpRuntimeSecurityGuard::fixedReleases() as $branch => $release) {
        $fixed[] = $branch . ' => ' . $release;
    }

    fwrite(STDOUT, implode(', ', $fixed) . PHP_EOL);
}

exit($result['patched'] ? 0 : 1);
