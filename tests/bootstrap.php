<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        return [];
    }
}
