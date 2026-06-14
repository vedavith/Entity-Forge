<?php

namespace Tests\Http;

use EntityForge\Http\Middleware\AuthMiddlewareInterface;
use EntityForge\Http\Pipeline;
use EntityForge\Http\Request;
use EntityForge\Http\Response;
use EntityForge\Http\Router;
use PHPUnit\Framework\TestCase;

class RouteMiddlewarePipelineTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/me', function (Request $req): Response {
            $user = $req->getAttribute('user');
            return (new Response())->withJson(['email' => $user['email'] ?? null]);
        });
        $this->router->get('/open', function (Request $req): Response {
            return (new Response())->withJson(['public' => true]);
        });
    }

    public function test_auth_middleware_attaches_user_and_handler_reads_it(): void
    {
        $auth = new class implements AuthMiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $token = $request->header('Authorization');
                if ($token !== 'Bearer valid-token') {
                    return (new Response())->withJson(['error' => 'Unauthorized'], 401);
                }
                return $next($request->withAttribute('user', ['email' => 'alice@example.com']));
            }
        };

        $pipeline = (new Pipeline())->pipe($auth);
        $request  = new Request(
            method: 'GET',
            path: '/me',
            headers: ['Authorization' => 'Bearer valid-token']
        );

        $response = $pipeline->run($request, fn(Request $req): Response => $this->router->dispatch($req));

        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('alice@example.com', $response->getBody());
    }

    public function test_auth_middleware_short_circuits_with_401_on_bad_token(): void
    {
        $auth = new class implements AuthMiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                $token = $request->header('Authorization');
                if ($token !== 'Bearer valid-token') {
                    return (new Response())->withJson(['error' => 'Unauthorized'], 401);
                }
                return $next($request->withAttribute('user', ['email' => 'alice@example.com']));
            }
        };

        $pipeline = (new Pipeline())->pipe($auth);
        $request  = new Request(
            method: 'GET',
            path: '/me',
            headers: ['Authorization' => 'Bearer wrong']
        );

        $response = $pipeline->run($request, fn(Request $req): Response => $this->router->dispatch($req));

        $this->assertSame(401, $response->getStatus());
        $this->assertStringContainsString('Unauthorized', $response->getBody());
    }

    public function test_auth_middleware_does_not_block_routes_it_allows(): void
    {
        $auth = new class implements AuthMiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                if ($request->path() === '/open') {
                    return $next($request);
                }
                $token = $request->header('Authorization');
                if (!$token) {
                    return (new Response())->withJson(['error' => 'Unauthorized'], 401);
                }
                return $next($request->withAttribute('user', ['email' => 'user@example.com']));
            }
        };

        $pipeline = (new Pipeline())->pipe($auth);
        $request  = new Request(method: 'GET', path: '/open');

        $response = $pipeline->run($request, fn(Request $req): Response => $this->router->dispatch($req));

        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('public', $response->getBody());
    }

    public function test_multiple_middleware_run_in_order_before_route_handler(): void
    {
        $log = [];

        $auth = new class ($log) implements AuthMiddlewareInterface {
            public function __construct(private array &$log) {}
            public function handle(Request $request, callable $next): Response
            {
                $this->log[] = 'auth';
                return $next($request->withAttribute('user', ['email' => 'bob@example.com']));
            }
        };

        $logger = new class ($log) implements \EntityForge\Http\Middleware\MiddlewareInterface {
            public function __construct(private array &$log) {}
            public function handle(Request $request, callable $next): Response
            {
                $this->log[] = 'logger';
                return $next($request);
            }
        };

        $pipeline = (new Pipeline())->pipe($auth)->pipe($logger);
        $request  = new Request(method: 'GET', path: '/me');

        $response = $pipeline->run($request, fn(Request $req): Response => $this->router->dispatch($req));

        $this->assertSame(['auth', 'logger'], $log);
        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('bob@example.com', $response->getBody());
    }

    public function test_router_returns_404_when_route_not_registered(): void
    {
        $auth = new class implements AuthMiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request->withAttribute('user', ['email' => 'x@example.com']));
            }
        };

        $pipeline = (new Pipeline())->pipe($auth);
        $request  = new Request(method: 'GET', path: '/nonexistent');

        $response = $pipeline->run($request, fn(Request $req): Response => $this->router->dispatch($req));

        $this->assertSame(404, $response->getStatus());
    }
}
