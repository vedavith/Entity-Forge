<?php
namespace EntityForge\Generator\Writer;

class FileWriter
{
    public function write(string $path, string $content, bool $overwrite = true): void
    {
        $directory = dirname($path);

        // ✅ Always ensure directory exists
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }

        // Optional overwrite protection
        if (!$overwrite && file_exists($path)) {
            throw new \RuntimeException(sprintf('File "%s" already exists', $path));
        }

        // Write file content
        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException(sprintf('Failed to write file "%s"', $path));
        }
    }
}