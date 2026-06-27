<?php

namespace EntityForge\Support;

class Str
{
    public static function toTableName(string $entityName): string
    {
        $snake = strtolower((string) preg_replace('/([A-Z])/', '_$1', lcfirst($entityName)));
        return self::pluralize($snake);
    }

    public static function pluralize(string $word): string
    {
        if (str_ends_with($word, 'y') && !preg_match('/[aeiou]y$/i', $word)) {
            return substr($word, 0, -1) . 'ies';
        }
        if (preg_match('/(s|x|z|ch|sh)$/', $word)) {
            return $word . 'es';
        }
        return $word . 's';
    }
}
