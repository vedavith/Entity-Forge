<?php

namespace EntityForge\Config;
use Exception;

class ConfigValidator
{
    private const REQUIRED_DB_KEYS = ['driver', 'host', 'port', 'database', 'username', 'password'];

    /**
     * @param array<string, mixed> $config
     * @throws Exception
     */
    public function validate(array $config): void
    {
        if (!isset($config['tenancy']['enabled'])) {
            throw new Exception("Missing 'tenancy.enabled' in config");
        }

        foreach (self::REQUIRED_DB_KEYS as $key) {
            if (!isset($config['database'][$key])) {
                throw new Exception("Missing 'database.{$key}' in config");
            }
        }
    }
}