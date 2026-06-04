<?php
namespace EntityForge\Config;

use Symfony\Component\Yaml\Yaml;
use Exception;

class ConfigLoader
{
    private array $config = [];

    /**
     * @throws Exception
     */
    public function load(string $path): array
    {
        if (!file_exists($path)) {
            throw new Exception("Config file not found: {$path}");
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return match ($extension) {
            'yaml', 'yml' => Yaml::parseFile($path),
            'json' => json_decode(file_get_contents($path), true),
            default => throw new \Exception("Unsupported config format: {$extension}")
        };
    }

    public function loadMultiple(array $paths): array
    {
        $merged = [];

        foreach ($paths as $path) {
            $config = $this->load($path);
            $merged = array_replace_recursive($merged, $config);
        }

        return $merged;
    }
}