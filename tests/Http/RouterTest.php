<?php

namespace Tests\Http;

use EntityForge\Http\Request;
use EntityForge\Http\Response;
use EntityForge\Http\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function test_returns_404_for_unregistered_route(): void
    {
        $router = new Router();
        $request = new Request(method: 'GET', path: '/missing');

        $response = $router->dispatch($request);

        $this->assertSame(404, $response->getStatus());
        $this->assertStringContainsString('Not Found', $response->getBody());
    }

    public function test_dispatches_get_route(): void
    {
        $router = new Router();
        $router->get('/users', fn(Request $req): Response => (new Response())->withJson(['users' => []]));

        $request = new Request(method: 'GET', path: '/users');
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('users', $response->getBody());
    }

    public function test_dispatches_post_route(): void
    {
        $router = new Router();
        $router->post('/users', fn(Request $req): Response => (new Response())->withJson(['created' => true], 201));

        $request = new Request(method: 'POST', path: '/users');
        $response = $router->dispatch($request);

        $this->assertSame(201, $response->getStatus());
    }

    public function test_dispatches_put_route(): void
    {
        $router = new Router();
        $router->put('/users/1', fn(Request $req): Response => (new Response())->withJson(['updated' => true]));

        $request = new Request(method: 'PUT', path: '/users/1');
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatus());
    }

    public function test_dispatches_delete_route(): void
    {
        $router = new Router();
        $router->delete('/users/1', fn(Request $req): Response => (new Response())->withJson(['deleted' => true]));

        $request = new Request(method: 'DELETE', path: '/users/1');
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatus());
    }

    public function test_method_mismatch_returns_404(): void
    {
        $router = new Router();
        $router->get('/ping', fn(Request $req): Response => (new Response())->withJson(['pong' => true]));

        $request = new Request(method: 'POST', path: '/ping');
        $response = $router->dispatch($request);

        $this->assertSame(404, $response->getStatus());
    }

    public function test_router_passes_request_to_handler(): void
    {
        $router = new Router();
        $captured = null;
        $router->get('/echo', function (Request $req) use (&$captured): Response {
            $captured = $req;
            return (new Response())->withJson([]);
        });

        $request = new Request(method: 'GET', path: '/echo', headers: ['X-Foo' => 'bar']);
        $router->dispatch($request);

        $this->assertSame('bar', $captured->header('X-Foo'));
    }
}
