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
        $router->get('/users', fn(Request $_req): Response => (new Response())->withJson(['users' => []]));

        $request = new Request(method: 'GET', path: '/users');
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('users', $response->getBody());
    }

    public function test_dispatches_post_route(): void
    {
        $router = new Router();
        $router->post('/users', fn(Request $_req): Response => (new Response())->withJson(['created' => true], 201));

        $request = new Request(method: 'POST', path: '/users');
        $response = $router->dispatch($request);

        $this->assertSame(201, $response->getStatus());
    }

    public function test_dispatches_put_route(): void
    {
        $router = new Router();
        $router->put('/users/1', fn(Request $_req): Response => (new Response())->withJson(['updated' => true]));

        $request = new Request(method: 'PUT', path: '/users/1');
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatus());
    }

    public function test_dispatches_delete_route(): void
    {
        $router = new Router();
        $router->delete('/users/1', fn(Request $_req): Response => (new Response())->withJson(['deleted' => true]));

        $request = new Request(method: 'DELETE', path: '/users/1');
        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatus());
    }

    public function test_method_mismatch_returns_405(): void
    {
        $router = new Router();
        $router->get('/ping', fn(Request $_req): Response => (new Response())->withJson(['pong' => true]));

        $request = new Request(method: 'POST', path: '/ping');
        $response = $router->dispatch($request);

        $this->assertSame(405, $response->getStatus());
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

        $this->assertInstanceOf(Request::class, $captured);
        $this->assertSame('bar', $captured->header('X-Foo'));
    }

    public function test_single_param_is_extracted(): void
    {
        $router = new Router();
        $router->get('/users/{id}', fn(Request $req): Response =>
            (new Response())->withJson(['id' => $req->param('id')])
        );

        $response = $router->dispatch(new Request(method: 'GET', path: '/users/42'));

        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('42', $response->getBody());
    }

    public function test_multiple_params_are_extracted(): void
    {
        $router = new Router();
        $router->get('/teams/{team}/users/{user}', fn(Request $req): Response =>
            (new Response())->withJson([
                'team' => $req->param('team'),
                'user' => $req->param('user'),
            ])
        );

        $response = $router->dispatch(new Request(method: 'GET', path: '/teams/eng/users/99'));

        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('eng', $response->getBody());
        $this->assertStringContainsString('99', $response->getBody());
    }

    public function test_param_route_does_not_match_extra_segments(): void
    {
        $router = new Router();
        $router->get('/users/{id}', fn(Request $_req): Response => (new Response())->withJson([]));

        $response = $router->dispatch(new Request(method: 'GET', path: '/users/42/extra'));

        $this->assertSame(404, $response->getStatus());
    }

    public function test_exact_route_takes_precedence_over_param_route(): void
    {
        $router = new Router();
        $router->get('/users/me',   fn(Request $_req): Response => (new Response())->withJson(['who' => 'me']));
        $router->get('/users/{id}', fn(Request $_req): Response => (new Response())->withJson(['who' => 'param']));

        $response = $router->dispatch(new Request(method: 'GET', path: '/users/me'));

        $this->assertStringContainsString('me', $response->getBody());
        $this->assertStringNotContainsString('param', $response->getBody());
    }

    public function test_request_params_returns_all_extracted_params(): void
    {
        $router = new Router();
        $captured = null;
        $router->get('/posts/{slug}', function (Request $req) use (&$captured): Response {
            $captured = $req->params();
            return (new Response())->withJson([]);
        });

        $router->dispatch(new Request(method: 'GET', path: '/posts/hello-world'));

        $this->assertSame(['slug' => 'hello-world'], $captured);
    }
}
