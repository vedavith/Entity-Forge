<?php

namespace Tests\Http;

use EntityForge\Http\Middleware\MiddlewareInterface;
use EntityForge\Http\Pipeline;
use EntityForge\Http\Request;
use EntityForge\Http\Response;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    public function test_runs_destination_when_no_middleware(): void
    {
        $pipeline = new Pipeline();
        $request = new Request();

        $response = $pipeline->run($request, fn(Request $req): Response => (new Response())->withJson(['ok' => true]));

        $this->assertSame('{"ok":true}', $response->getBody());
        $this->assertSame(200, $response->getStatus());
    }

    public function test_middleware_can_short_circuit(): void
    {
        $blocker = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return (new Response())->withJson(['error' => 'blocked'], 403);
            }
        };

        $pipeline = (new Pipeline())->pipe($blocker);
        $request = new Request();

        $response = $pipeline->run($request, fn(Request $req): Response => (new Response())->withJson(['ok' => true]));

        $this->assertSame(403, $response->getStatus());
        $this->assertStringContainsString('blocked', $response->getBody());
    }

    public function test_middleware_can_pass_through(): void
    {
        $passthrough = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };

        $pipeline = (new Pipeline())->pipe($passthrough);
        $request = new Request();

        $response = $pipeline->run($request, fn(Request $req): Response => (new Response())->withJson(['reached' => true]));

        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('reached', $response->getBody());
    }

    public function test_middleware_executes_in_order(): void
    {
        $log = [];

        $first = new class ($log) implements MiddlewareInterface {
            public function __construct(private array &$log) {}
            public function handle(Request $request, callable $next): Response
            {
                $this->log[] = 'first-before';
                $response = $next($request);
                $this->log[] = 'first-after';
                return $response;
            }
        };

        $second = new class ($log) implements MiddlewareInterface {
            public function __construct(private array &$log) {}
            public function handle(Request $request, callable $next): Response
            {
                $this->log[] = 'second-before';
                $response = $next($request);
                $this->log[] = 'second-after';
                return $response;
            }
        };

        $pipeline = (new Pipeline())->pipe($first)->pipe($second);
        $pipeline->run(new Request(), fn(Request $req): Response => (new Response())->withJson([]));

        $this->assertSame(['first-before', 'second-before', 'second-after', 'first-after'], $log);
    }

    public function test_pipe_returns_new_instance(): void
    {
        $pipeline = new Pipeline();
        $mw = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };

        $piped = $pipeline->pipe($mw);

        $this->assertNotSame($pipeline, $piped);
    }
}
