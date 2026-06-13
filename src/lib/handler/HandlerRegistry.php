<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class HandlerRegistry
{
    /** @var array<string, FrontRequestHandler> */
    private array $handlers = [];

    public function register(string $key, FrontRequestHandler $handler): void
    {
        $this->handlers[$key] = $handler;
    }

    public function get(string $key): ?FrontRequestHandler
    {
        return $this->handlers[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->handlers[$key]);
    }
}
