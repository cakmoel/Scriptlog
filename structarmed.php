<?php

/**
 * StructArmed Advisory Configuration - Scriptlog/Blogware
 *
 * Status: advisory and report-only. No CI gate. No pre-commit gate.
 * Companion plan: plan/STRUCTARMED_IMPLEMENTATION_PLAN.md
 * Evidence: report/STRUCTARMED_ADVISORY_REPORT.md
 *
 * Scope: PSR4, then PSR1 and PSR12, then MVC and CODEQUALITY.
 * Deferred: PER, PSR15, DDD, YAGNI fix mode, preset=all (see plan Section 6).
 *
 * Location: project root (not lib/). Keeps pre-commit Gate 1 (lib/ layer rules)
 * untouched, and composer compat only scans lib/, admin/, install/, api/, and
 * public/themes/, so this root-level config file is not compat-scanned.
 *
 * Install footprint: StructArmed is a require-dev dependency since 2026-09-25
 * (`composer require --dev boundwize/structarmed:^0.18`; binary at
 * lib/vendor/bin/structarmed, custom vendor-dir; shortcut via
 * `composer structarmed`). Install needs --ignore-platform-req=php because the
 * tool requires PHP ^8.2 while the platform pin stays 7.4.33. Run only on
 * dev/CI PHP 8.2+; production code stays PHP 7.4 compatible and every fix
 * suggestion must still pass composer compat.
 */

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;
use Boundwize\StructArmed\Preset\Presets\Psr1Preset;
use Boundwize\StructArmed\Preset\Presets\Psr4Preset;

return Architecture::define()
    // Project layers. The MVC preset maps Controller, Model, and Service by
    // namespace automatically. Dao, Dto, Handler, Core, and Validator are
    // declared here for the custom ruleset below. ApiController (REM-6a) is
    // the thin-read-endpoint layer: the 12 controllers under
    // lib/controller/api/ resolve here (longest path prefix wins) while the
    // strict Controller rule keeps applying to admin controllers.
    ->layer('Controller', 'lib/controller/')
    ->layer('ApiController', 'lib/controller/api/')
    ->layer('Service', 'lib/service/')
    ->layer('Dao', 'lib/dao/')
    ->layer('Dto', 'lib/dto/')
    ->layer('Model', 'lib/model/')
    ->layer('Handler', 'lib/handler/')
    ->layer('Core', 'lib/core/')
    // Bootstrap (REM-6c) is the composition root: it wires controllers,
    // services, and DAOs, so it lives in its own layer allowed to depend
    // on all layers. Longest path prefix wins, so this file alone leaves
    // the Core layer. Zero runtime risk.
    ->layer('Bootstrap', 'lib/core/Bootstrap.php')
    ->layer('Validator', 'lib/validator/')
    ->ruleset(array(
        // Who is allowed to depend on whom. Controller to Service to Dao.
        // ApiController additionally allows Dao (thin reads, REM-4) and
        // Controller (all 12 extend the shared base ApiController, which
        // lives in the Controller path as API infrastructure).
        'Controller' => array('Service', 'Dto', 'Core', 'Validator'),
        'ApiController' => array('Service', 'Dao', 'Dto', 'Core', 'Validator', 'Controller'),
        // Service allows Model for DownloadModel only (REM-5): it is the
        // working data-access class for the download tables (full CRUD plus
        // reporting queries, no DownloadDao exists), consumed by
        // MediaService and DownloadService through the constructor.
        'Service'    => array('Dao', 'Dto', 'Core', 'Validator', 'Model'),
        // Handler allows Controller for the DownloadHandler to
        // DownloadController file-streaming delegation (REM-6b): HTTP
        // delivery (http_response_code, echo, exit) stays in controllers
        // and must not move into services.
        'Handler'    => array('Service', 'Core', 'Model', 'Controller'),
        'Model'      => array('Dao', 'Core'),
        'Dao'        => array('Core'),
        'Dto'        => array(),
        'Core'       => array(),
        'Bootstrap'  => array('Controller', 'ApiController', 'Service', 'Dao', 'Dto', 'Model', 'Handler', 'Core', 'Validator'),
        'Validator'  => array('Dto', 'Core'),
    ))
    // Never analyse tests or fixtures as project layers.
    ->skipPathsForRuleset(array('tests/*', 'e2e/*', 'fixtures/*'))
    ->skip(array(
        // Third-party, generated, and non-class code.
        // Mirrors phpcs.xml and compat exclusions.
        // lib/vendor/* covers all third-party composer packages.
        'lib/vendor/*',
        'lib/core/HTMLPurifier/*',
        'lib/core/Psr4AutoloadTest.php',
        'lib/autoload-aliases*.php',
        'admin/*', 'install/*', 'api/*', 'public/themes/*',
        // Accepted project conventions (findings F1, F2, F8 in plan Section 3).
        Psr4Preset::CLASSES_MUST_MATCH_COMPOSER,
        Psr1Preset::FILES_SHOULD_DECLARE_SYMBOLS_OR_SIDE_EFFECTS,
    ))
    // Legacy debt to fix later (findings F4, F6-F8 in plan Section 3).
    // Rule for the future CI phase: the baseline may shrink, never grow
    // without a written reason.
    ->baseline('structarmed-baseline.php')
    ->withPresets(
        Preset::PSR4(),
        Preset::PSR1(),
        Preset::PSR12(),
        Preset::MVC(),
        Preset::CODEQUALITY()
    );
