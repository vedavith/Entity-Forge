<?php

require 'vendor/autoload.php';

use EntityForge\Generator\EntityGenerator;

// Load config
$configPath = __DIR__ . '/config/entities/User.json';

$config = json_decode(file_get_contents($configPath), true);

if (!$config) {
    throw new Exception("Invalid JSON config");
}

// Run generator
$generator = new EntityGenerator();
$generator->generate($config);

echo "Generation complete.\n";