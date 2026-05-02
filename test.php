<?php


use App\Repository\UserRepository;
use EntityForge\Core\Application;

$app = new Application(__DIR__ . '/config');

$app->boot([
    'headers' => [
        'X-Tenant-ID' => 'tenant_999'
    ]
]);

$repo = new UserRepository();

print_r($repo->create([
    'name' => 'Ved'
]));

