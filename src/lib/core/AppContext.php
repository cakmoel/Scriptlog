<?php

/**
 * A simple Read-Only Data Object to hold application state.
 *
 *
 */
class AppContext
{
    private array $container = [];

    public function __construct(array $data)
    {
        $this->container = $data;
    }

    // Magic getter to allow $app->db_host
    public function __get($name)
    {
        return $this->container[$name] ?? null;
    }

    // Ensure we can check if a property exists with isset($app->userDao)
    public function __isset($name)
    {
        return isset($this->container[$name]);
    }
}
