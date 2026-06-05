<?php

namespace Tests\Http;

use EntityForge\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseValueObjectTest extends TestCase
{
    public function test_defaults_to_200_empty_body(): void
    {
        $response = new Response();
        $this->assertSame(200, $response->getStatus());
        $this->assertSame('', $response->getBody());
        $this->assertSame([], $response->getHeaders());
    }

    public function test_with_json_sets_body_and_status(): void
    {
        $response = (new Response())->withJson(['key' => 'value'], 201);
        $this->assertSame(201, $response->getStatus());
        $this->assertSame('{"key":"value"}', $response->getBody());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);
    }

    public function test_with_json_is_immutable(): void
    {
        $original = new Response();
        $modified = $original->withJson(['x' => 1]);
        $this->assertNotSame($original, $modified);
        $this->assertSame('', $original->getBody());
    }

    public function test_with_status_sets_status(): void
    {
        $response = (new Response())->withStatus(422);
        $this->assertSame(422, $response->getStatus());
    }

    public function test_with_header_adds_header(): void
    {
        $response = (new Response())->withHeader('X-Custom', 'abc');
        $this->assertSame('abc', $response->getHeaders()['X-Custom']);
    }

    public function test_with_header_is_immutable(): void
    {
        $original = new Response();
        $modified = $original->withHeader('X-Foo', 'bar');
        $this->assertNotSame($original, $modified);
        $this->assertArrayNotHasKey('X-Foo', $original->getHeaders());
    }
}
