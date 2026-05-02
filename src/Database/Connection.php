<?php

namespace EntityForge\Database;

use PDO;
use PDOException;

class Connection
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        try {
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database']
            );

            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            );
        } catch (PDOException $e) {
            throw new \Exception("DB Connection failed: " . $e->getMessage());
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}