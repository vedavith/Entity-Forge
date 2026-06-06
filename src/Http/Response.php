<?php

namespace EntityForge\Http;

class Response
{
    private int $status = 200;
    private string $body = '';
    private array $headers = [];

    public function withJson(array $data, int $status = 200): self
    {
        $clone = clone $this;
        $clone->status = $status;
        $clone->body = (string) json_encode($data);
        $clone->headers['Content-Type'] = 'application/json';
        return $clone;
    }

    public function withStatus(int $status): self
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }

    public function stream(callable $body): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        $body();
    }

    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}