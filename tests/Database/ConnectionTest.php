<?php

namespace Tests\Database;

use EntityForge\Database\Connection;
use Exception;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function test_throws_on_unreachable_host(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/DB Connection failed/');

        new Connection([
            'driver'   => 'mysql',
            'host'     => '192.0.2.1',  // TEST-NET, guaranteed unreachable
            'port'     => 3306,
            'database' => 'nonexistent',
            'username' => 'root',
            'password'  => 'wrong',
        ]);
    }
}
