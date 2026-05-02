<?php


require 'vendor/autoload.php';

use EntityForge\Core\Application;
use EntityForge\Repository\UserRepository;

$app = new Application(__DIR__ . '/config');

try {
    $app->boot([
        'headers' => [
            'X-Tenant-ID' => 'tenant-100'
        ]
    ]);
} catch (Exception $e) {
    print_r($e->getMessage());
}

try {
    echo \EntityForge\Tenant\TenantContext::getTenantId();
} catch (Exception $e) {
    print_r($e->getMessage());
}

try {
//    \EntityForge\Tenant\TenantContext::clear();
    $repo = new UserRepository();
    $data = $repo->create(['name' => 'test']);
    print_r($data);
} catch (Exception $e) {
    print_r($e->getMessage());
}

