<?php
$extensions = [
    'swoole', 'redis', 'protocolbuffers'
];

echo str_repeat('-', 43) . PHP_EOL;
echo sprintf('|%-41s|','extension check').PHP_EOL;
echo str_repeat('-', 43) . PHP_EOL;
foreach ($extensions as $extension) {
    echo sprintf("|%-30s|%10s|", $extension, extension_loaded($extension) ? 'ok' : 'failed') . PHP_EOL;
    echo str_repeat('-', 43) . PHP_EOL;
}