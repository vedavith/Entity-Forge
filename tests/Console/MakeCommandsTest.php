<?php

namespace Tests\Console;

use EntityForge\Console\MakeControllerCommand;
use EntityForge\Console\MakeMiddlewareCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MakeCommandsTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/ef_make_' . uniqid();
        mkdir($this->tmp, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);
    }

    // ── make:middleware ────────────────────────────────────────────────────────

    public function test_make_middleware_command_name(): void
    {
        $this->assertSame('make:middleware', (new MakeMiddlewareCommand())->getName());
    }

    public function test_make_middleware_generates_file(): void
    {
        $tester = new CommandTester(new MakeMiddlewareCommand());
        $code = $tester->execute([
            'name'     => 'AuthMiddleware',
            '--output' => $this->tmp,
        ]);

        $this->assertSame(0, $code);
        $this->assertFileExists($this->tmp . '/AuthMiddleware.php');
    }

    public function test_make_middleware_output_contains_correct_class(): void
    {
        $tester = new CommandTester(new MakeMiddlewareCommand());
        $tester->execute([
            'name'     => 'AuthMiddleware',
            '--output' => $this->tmp,
        ]);

        $content = file_get_contents($this->tmp . '/AuthMiddleware.php');
        $this->assertStringContainsString('class AuthMiddleware', $content);
        $this->assertStringContainsString('implements MiddlewareInterface', $content);
        $this->assertStringContainsString('public function handle(Request $request, callable $next): Response', $content);
    }

    public function test_make_middleware_fails_on_invalid_name(): void
    {
        $tester = new CommandTester(new MakeMiddlewareCommand());
        $code = $tester->execute([
            'name'     => 'invalid-name',
            '--output' => $this->tmp,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Invalid middleware name', $tester->getDisplay());
    }

    // ── make:controller ────────────────────────────────────────────────────────

    public function test_make_controller_command_name(): void
    {
        $this->assertSame('make:controller', (new MakeControllerCommand())->getName());
    }

    public function test_make_controller_generates_file(): void
    {
        $tester = new CommandTester(new MakeControllerCommand());
        $code = $tester->execute([
            'name'     => 'UserController',
            '--output' => $this->tmp,
        ]);

        $this->assertSame(0, $code);
        $this->assertFileExists($this->tmp . '/UserController.php');
    }

    public function test_make_controller_output_contains_crud_stubs(): void
    {
        $tester = new CommandTester(new MakeControllerCommand());
        $tester->execute([
            'name'     => 'UserController',
            '--output' => $this->tmp,
        ]);

        $content = file_get_contents($this->tmp . '/UserController.php');
        $this->assertStringContainsString('class UserController', $content);
        $this->assertStringContainsString('public function index(', $content);
        $this->assertStringContainsString('public function show(', $content);
        $this->assertStringContainsString('public function store(', $content);
        $this->assertStringContainsString('public function update(', $content);
        $this->assertStringContainsString('public function destroy(', $content);
    }

    public function test_make_controller_fails_on_invalid_name(): void
    {
        $tester = new CommandTester(new MakeControllerCommand());
        $code = $tester->execute([
            'name'     => '123Bad',
            '--output' => $this->tmp,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Invalid controller name', $tester->getDisplay());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $item) {
            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }
        rmdir($dir);
    }
}
