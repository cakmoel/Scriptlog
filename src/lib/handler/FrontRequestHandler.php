<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

interface FrontRequestHandler
{
    public function handle(array $params): void;
}
