<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Registry for front-end request handlers.
 *
 * Maps route keys (e.g. 'home', 'post', 'page') to their corresponding
 * FrontRequestHandler implementations. Used by the dispatcher to look up
 * the correct handler for each incoming request.
 */
class HandlerRegistry
{
    /**
     * Registered handlers keyed by route name.
     *
     * @var array<string, FrontRequestHandler>
     */
    private array $handlers = [];

    /**
     * Register a handler for a given route key.
     *
     * @param string               $key     The route identifier (e.g. 'home', 'post').
     * @param FrontRequestHandler  $handler The handler instance.
     * @return void
     */
    public function register(string $key, FrontRequestHandler $handler): void
    {
        $this->handlers[$key] = $handler;
    }

    /**
     * Retrieve a handler by route key.
     *
     * @param string $key The route identifier.
     * @return FrontRequestHandler|null The handler instance, or null if not registered.
     */
    public function get(string $key): ?FrontRequestHandler
    {
        return $this->handlers[$key] ?? null;
    }

    /**
     * Check whether a handler is registered for the given route key.
     *
     * @param string $key The route identifier.
     * @return bool True if a handler is registered for the key.
     */
    public function has(string $key): bool
    {
        return isset($this->handlers[$key]);
    }
}
