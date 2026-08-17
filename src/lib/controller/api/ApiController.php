<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Controller\ApiController as BaseApiController;

/**
 * API Info Controller
 *
 * Serves the public /api/v1 info (metadata) endpoint. Unlike the base
 * ApiController (whose default requiresAuth=true protects controllers that
 * do not override it), the API root is deliberately public so clients can
 * discover endpoints without credentials.
 *
 * @category Controller Class
 * @author   Blogware Team
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 * @psalm-suppress UnusedClass — instantiated dynamically by ApiRouter via
 *            the legacy class_alias map (autoload-aliases.php).
 *
 */
class ApiController extends BaseApiController
{
    /**
     * The info endpoint is public metadata — do not require authentication.
     *
     * @var bool
     */
    protected $requiresAuth = false;
}