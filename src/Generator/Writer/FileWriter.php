<?php
namespace EntityForge\Generator\Writer;

class FileWriter
{
    public function write(string $path, string $content): void
    {
        file_put_contents($path, $content);
    }
}