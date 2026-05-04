<?php

require __DIR__ . '/vendor/autoload.php';

use EntityForge\Core\Application;
use EntityForge\Tenant\TenantProvisioner;
use App\Repository\UserRepository;

$app = new Application(__DIR__ . '/config');
$app->boot([], false);

$config = $app->getConfig();
$tenantId = 'tenant_2';

$provisioner = new TenantProvisioner($config);

try {
    $provisioner->create($tenantId);
} catch (\Throwable $e) {
    echo "Provisioning skipped or failed: " . $e->getMessage() . PHP_EOL;
}

$app->boot([
    'headers' => [
        'X-Tenant-ID' => $tenantId
    ]
], true);


$repo = new UserRepository($config);
$user = $repo->create([
    'name' => 'Ved',
    'email' => 'ved@example.com'
]);

echo "Inserted:\n";
print_r($user);