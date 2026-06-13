<?php

namespace EntityForge\Http;

class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $params
     */
    public function __construct(
        private array $headers = [],
        private array $query = [],
        private array $body = [],
        private string $method = 'GET',
        private string $path = '/',
        private array $params = []
    ) {}

    public static function capture(): self
    {
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $parsed = parse_url($uri, PHP_URL_PATH);
        $path   = is_string($parsed) ? $parsed : '/';

        return new self(
            headers: getallheaders() ?: [],
            query: $_GET,
            body: $_POST,
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            path: $path
        );
    }

    public function header(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    public function query(string $key): mixed
    {
        return $this->query[$key] ?? null;
    }

    public function body(string $key): mixed
    {
        return $this->body[$key] ?? null;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function param(string $name): ?string
    {
        return $this->params[$name] ?? null;
    }

    /** @return array<string, string> */
    public function params(): array
    {
        return $this->params;
    }

    /** @param array<string, string> $params */
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }
}
