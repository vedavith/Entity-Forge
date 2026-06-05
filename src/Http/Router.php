<?php

namespace EntityForge\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): self
    {
        return $this->register('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): self
    {
        return $this->register('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): self
    {
        return $this->register('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): self
    {
        return $this->register('DELETE', $path, $handler);
    }

    private function register(string $method, string $path, callable $handler): self
    {
        $this->routes[] = [$method, $path, $handler];
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $routes  = $this->routes;
        $dispatcher = simpleDispatcher(function (RouteCollector $r) use ($routes): void {
            foreach ($routes as [$method, $path, $handler]) {
                $r->addRoute($method, $path, $handler);
            }
        });

        $result = $dispatcher->dispatch(
            strtoupper($request->method()),
            $request->path()
        );

        return match ($result[0]) {
            Dispatcher::FOUND => ($result[1])($request->withParams($result[2])),
            Dispatcher::METHOD_NOT_ALLOWED => (new Response())->withJson(['error' => 'Method Not Allowed'], 405),
            default => (new Response())->withJson(['error' => 'Not Found'], 404),
        };
    }
}
