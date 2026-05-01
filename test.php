<?php


require 'vendor/autoload.php';

use EntityForge\Core\Application;

$app = new Application(__DIR__ . '/config');
try {
    $app->boot();
} catch (Exception $e) {
    var_dump($e->getMessage());
}

try {
    print_r($app->getConfig());
} catch (Exception $e) {
    print_r($e->getMessage());
}