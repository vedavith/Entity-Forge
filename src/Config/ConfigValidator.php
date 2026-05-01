<?php

namespace EntityForge\Config;
use Exception;

class ConfigValidator
{
    /**
     * @throws Exception
     */
    public function validate(array $config): void
    {
        if (!isset($config['tenancy']['enabled'])) {
            throw new Exception("Missing 'tenancy.enabled' in config");
        }
    }
}