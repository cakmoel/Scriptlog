<?php

declare(strict_types=1);

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * PhpRuntimeSecurityGuard
 *
 * Evaluates whether the current PHP runtime is patched against the two critical
 * PHP engine vulnerabilities published in October 2026 and reports the
 * configuration knobs that the advisories identify as exploitation paths.
 *
 * Advisories covered:
 * - CVE-2026-109618 / GHSA-w8hw-w9fx-j79w - RCE via OOM corruption
 * - CVE-2026-109619 / GHSA-6wmf-4rv5-p3rp - RCE via stale global state
 * - CVE-2026-115560 (informational) - session upload-progress / serialize handler
 *
 * This guard cannot fix a PHP engine defect. It exists so a deployment cannot
 * silently regress onto an affected runtime: `report()` writes one audit line,
 * `enforce()` (opt-in) fails closed, and `evaluate()` is a pure, testable
 * snapshot used by the CLI gate in `bin/php-security-check.php`.
 *
 * @category Core Class
 * @license MIT
 * @version 1.0
 */
final class PhpRuntimeSecurityGuard
{
    /**
     * Advisory identifier for the OOM corruption RCE.
     *
     * @var string
     */
    public const ADVISORY_OOM = 'CVE-2026-109618';

    /**
     * Advisory identifier for the stale global state RCE.
     *
     * @var string
     */
    public const ADVISORY_STALE_STATE = 'CVE-2026-109619';

    /**
     * Informational advisory for the session upload-progress serialize-handler flip.
     *
     * @var string
     */
    public const ADVISORY_UPLOAD_PROGRESS = 'CVE-2026-115560';

    /**
     * First patched release per PHP minor branch.
     *
     * Branches absent from this map are either end-of-life (7.4, 8.0, 8.1) or
     * future branches that inherit the fix from the 8.5 line.
     *
     * @var array<string, string>
     */
    private const FIXED_RELEASES = array(
        '8.5' => '8.5.12',
        '8.4' => '8.4.27',
        '8.3' => '8.3.36',
        '8.2' => '8.2.35',
    );

    /**
     * Lowest release that carries the complete state-reset patch.
     *
     * Used to classify branches newer than 8.5 (for example 8.6.x).
     *
     * @var string
     */
    private const PATCH_FLOOR = '8.5.12';

    /**
     * Guards against duplicate log lines inside one request.
     *
     * @var bool
     */
    private static $reported = false;

    /**
     * Evaluate a PHP version and INI snapshot against both advisories.
     *
     * Pure function: no logging, no global state mutation. Passing explicit
     * values is supported for tests and for the CLI gate.
     *
     * @param string|null $version PHP version string, defaults to PHP_VERSION
     * @param array<string, string|false>|null $iniValues INI overrides keyed by directive name
     * @return array{
     *     version: string,
     *     branch: string,
     *     patched: bool,
     *     severity: string,
     *     findings: array<int, array{code: string, severity: string, message: string}>,
     *     mitigations: array<int, string>
     * }
     */
    public static function evaluate(?string $version = null, ?array $iniValues = null): array
    {
        $version = ($version === null || $version === '') ? PHP_VERSION : $version;
        $ini = ($iniValues === null) ? self::readRuntimeIni() : $iniValues;
        $branch = self::branchOf($version);
        $patched = self::isPatched($version);
        $findings = array();

        if (!$patched) {
            if (isset(self::FIXED_RELEASES[$branch])) {
                $message = sprintf(
                    'PHP %s is affected by %s and %s; upgrade to %s or later on the %s branch.',
                    $version,
                    self::ADVISORY_OOM,
                    self::ADVISORY_STALE_STATE,
                    self::FIXED_RELEASES[$branch],
                    $branch
                );
            } else {
                $message = sprintf(
                    'PHP %s is end-of-life and has no upstream fix for %s / %s; upgrade to 8.2.35+, 8.3.36+, 8.4.27+ or 8.5.12+.',
                    $version,
                    self::ADVISORY_OOM,
                    self::ADVISORY_STALE_STATE
                );
            }

            $findings[] = array(
                'code' => 'php_runtime_unpatched',
                'severity' => 'critical',
                'message' => $message,
            );
        }

        if (self::isEnabled(self::iniValue($ini, 'session.upload_progress.enabled'))) {
            $findings[] = array(
                'code' => 'upload_progress_enabled',
                'severity' => 'warning',
                'message' => 'session.upload_progress.enabled is on; it is a confirmed early session '
                    . 'consumer for ' . self::ADVISORY_STALE_STATE . ' and the trigger for '
                    . self::ADVISORY_UPLOAD_PROGRESS . '. Set it to 0 administratively and restart workers.',
            );
        }

        if (
            version_compare($version, '8.5.0', '>=')
            && self::isEnabled(self::iniValue($ini, 'fatal_error_backtraces'))
        ) {
            $findings[] = array(
                'code' => 'fatal_error_backtraces_enabled',
                'severity' => 'warning',
                'message' => 'fatal_error_backtraces is on; it is the confirmed producer path for '
                    . self::ADVISORY_STALE_STATE . ' on PHP 8.5. Set it to 0 administratively and restart workers.',
            );
        }

        $serializeHandler = self::iniValue($ini, 'session.serialize_handler');

        if ($serializeHandler !== false && strtolower(trim((string) $serializeHandler)) !== 'php') {
            $findings[] = array(
                'code' => 'serialize_handler_non_default',
                'severity' => 'warning',
                'message' => 'session.serialize_handler is "' . $serializeHandler . '"; the '
                    . self::ADVISORY_UPLOAD_PROGRESS . ' path requires an application to switch it to '
                    . '"php" at runtime before session_start(). Keep the default and never ini_set() it.',
            );
        }

        if (
            self::isEnabled(self::iniValue($ini, 'display_errors'))
            && (!defined('APP_DEVELOPMENT') || APP_DEVELOPMENT === false)
        ) {
            $findings[] = array(
                'code' => 'display_errors_enabled',
                'severity' => 'info',
                'message' => 'display_errors is on in a non-development context; disable it and log '
                    . 'errors instead to avoid leaking diagnostics and paths.',
            );
        }

        $mitigations = $patched ? array() : self::mitigations();

        return array(
            'version' => $version,
            'branch' => $branch,
            'patched' => $patched,
            'severity' => self::highestSeverity($findings),
            'findings' => $findings,
            'mitigations' => $mitigations,
        );
    }

    /**
     * Determine whether a PHP version carries the complete state-reset patch.
     *
     * @param string|null $version PHP version string, defaults to PHP_VERSION
     * @return bool True when the runtime is not affected by either CVE
     */
    public static function isPatched(?string $version = null): bool
    {
        $version = ($version === null || $version === '') ? PHP_VERSION : $version;
        $branch = self::branchOf($version);

        if (isset(self::FIXED_RELEASES[$branch])) {
            return version_compare($version, self::FIXED_RELEASES[$branch], '>=');
        }

        // Future branches inherit the fix; EOL branches below 8.2 never do.
        return version_compare($version, self::PATCH_FLOOR, '>=');
    }

    /**
     * Check whether the runtime produced by `evaluate()` is exploitable.
     *
     * @param string|null $version PHP version string, defaults to PHP_VERSION
     * @return bool True when the runtime is affected by either CVE
     */
    public static function isVulnerable(?string $version = null): bool
    {
        return !self::isPatched($version);
    }

    /**
     * Log one audit line when the runtime is affected by either CVE.
     *
     * Never throws and never logs configuration warnings: INI findings are
     * constant for a host and would otherwise write one line per request.
     * `bin/php-security-check.php` is the place to surface those warnings.
     *
     * @param array<string, string|false>|null $iniValues INI overrides keyed by directive name
     * @return array<string, mixed> The evaluation result
     */
    public static function report(?array $iniValues = null): array
    {
        $result = self::evaluate(null, $iniValues);

        if (self::$reported || $result['severity'] !== 'critical') {
            return $result;
        }

        self::$reported = true;

        $lines = array();
        $findings = is_array($result['findings']) ? $result['findings'] : array();

        foreach ($findings as $finding) {
            if (!is_array($finding) || !isset($finding['severity'], $finding['code'], $finding['message'])) {
                continue;
            }

            if ((string) $finding['severity'] !== 'critical') {
                continue;
            }

            $lines[] = 'CRITICAL ' . $finding['code'] . ': ' . $finding['message'];
        }

        if ($lines === array()) {
            return $result;
        }

        error_log(
            '[scriptlog][php-runtime-security] version=' . $result['version']
            . ' patched=' . ($result['patched'] ? 'yes' : 'no')
            . ' | ' . implode(' | ', $lines)
        );

        return $result;
    }

    /**
     * Fail closed when strict mode is enabled and the runtime is affected.
     *
     * Strict mode is opt-in through the `SCRIPTLOG_STRICT_PHP_SECURITY`
     * constant or environment variable. It exists for regulated deployments
     * that prefer a hard outage over serving traffic on a vulnerable engine.
     *
     * @param array<string, string|false>|null $iniValues INI overrides keyed by directive name
     * @return void
     * @throws \RuntimeException When strict mode is on and the runtime is unpatched
     */
    public static function enforce(?array $iniValues = null): void
    {
        if (!self::isStrictMode()) {
            return;
        }

        $result = self::evaluate(null, $iniValues);

        if ($result['patched'] === false) {
            throw new \RuntimeException(
                'Unsupported PHP runtime ' . $result['version']
                . ': ' . self::ADVISORY_OOM . ' / ' . self::ADVISORY_STALE_STATE
                . ' require PHP 8.2.35+, 8.3.36+, 8.4.27+ or 8.5.12+.'
            );
        }
    }

    /**
     * Return the fixed-release matrix for display in the CLI gate.
     *
     * @return array<string, string> Branch => first fixed release
     */
    public static function fixedReleases(): array
    {
        return self::FIXED_RELEASES;
    }

    /**
     * Read the security-relevant INI directives from the live runtime.
     *
     * @return array<string, string|false> Directive name => raw ini_get() value
     */
    private static function readRuntimeIni(): array
    {
        $keys = array(
            'session.upload_progress.enabled',
            'session.serialize_handler',
            'fatal_error_backtraces',
            'display_errors',
            'memory_limit',
            'post_max_size',
        );

        $values = array();

        foreach ($keys as $key) {
            $values[$key] = ini_get($key);
        }

        return $values;
    }

    /**
     * Operator mitigations from both advisories, in priority order.
     *
     * @return array<int, string> Human-readable mitigation steps
     */
    private static function mitigations(): array
    {
        return array(
            'Upgrade PHP to 8.2.35+, 8.3.36+, 8.4.27+ or 8.5.12+ (the only complete fix).',
            'Set session.upload_progress.enabled=0 in php.ini/FPM pool and restart every worker.',
            'On PHP 8.5, set fatal_error_backtraces=0 and restart every worker.',
            'Keep post_max_size low (8M without uploads, 32M with) and below any proxy limit.',
            'Remove invalid/deprecated per-request INI settings; prefer startup configuration.',
            'As a last resort on an unpatched host, set memory_limit=-1 with a cgroup memory boundary.',
        );
    }

    /**
     * Resolve the "major.minor" branch from a version string.
     *
     * @param string $version PHP version string
     * @return string Branch such as "8.5", or an empty string when unparseable
     */
    private static function branchOf(string $version): string
    {
        if (preg_match('/^(\d+)\.(\d+)/', $version, $matches) !== 1) {
            return '';
        }

        return $matches[1] . '.' . $matches[2];
    }

    /**
     * Read a directive from an INI snapshot.
     *
     * @param array<string, string|false> $ini INI snapshot
     * @param string $key Directive name
     * @return string|false Raw value or false when absent
     */
    private static function iniValue(array $ini, string $key)
    {
        return array_key_exists($key, $ini) ? $ini[$key] : false;
    }

    /**
     * Interpret a php.ini boolean value.
     *
     * @param string|bool|int|false|null $value Raw INI value
     * @return bool True for "1", "on", "true", "yes" or boolean true
     */
    private static function isEnabled($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === false) {
            return false;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, array('1', 'on', 'true', 'yes', 'enabled'), true);
    }

    /**
     * Pick the most severe finding level.
     *
     * @param array<int, array{code: string, severity: string, message: string}> $findings Findings
     * @return string One of "ok", "info", "warning", "critical"
     */
    private static function highestSeverity(array $findings): string
    {
        $rank = array('ok' => 0, 'info' => 1, 'warning' => 2, 'critical' => 3);
        $highest = 'ok';

        foreach ($findings as $finding) {
            $severity = isset($finding['severity']) ? (string) $finding['severity'] : 'ok';

            if (!isset($rank[$severity])) {
                continue;
            }

            if ($rank[$severity] > $rank[$highest]) {
                $highest = $severity;
            }
        }

        return $highest;
    }

    /**
     * Determine whether fail-closed mode is enabled.
     *
     * @return bool True when SCRIPTLOG_STRICT_PHP_SECURITY is truthy
     */
    private static function isStrictMode(): bool
    {
        if (defined('SCRIPTLOG_STRICT_PHP_SECURITY') && self::isEnabled('SCRIPTLOG_STRICT_PHP_SECURITY')) {
            return true;
        }

        return self::isEnabled(getenv('SCRIPTLOG_STRICT_PHP_SECURITY'));
    }
}
