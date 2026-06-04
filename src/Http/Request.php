<?php

namespace EntityForge\Http;

class Request
{

    public function __construct(
        private array $headers = [],
        private array $query = [],
        private array $body = [],
        private array|string $method = 'GET'
    ) {}

    public static function capture(): self
    {
        return new self(
            headers: getallheaders(),
            query: $_GET,
            body: $_POST,
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET'
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
}