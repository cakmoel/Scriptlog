<?php

header('Content-Type: text/plain');

echo 'PHP_VERSION=' . PHP_VERSION . PHP_EOL;
echo 'PHP_SAPI=' . PHP_SAPI . PHP_EOL;
echo 'XDEBUG_VERSION=' . (phpversion('xdebug') ?: 'NOT_LOADED') . PHP_EOL;

foreach ([
    'xdebug.mode',
    'xdebug.start_with_request',
    'xdebug.output_dir',
    'xdebug.profiler_output_name',
] as $key) {
    echo $key . '=' . ini_get($key) . PHP_EOL;
}
