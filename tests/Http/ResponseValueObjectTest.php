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

    public function test_stream_invokes_callable_and_produces_output(): void
    {
        $response = (new Response())->withHeader('Content-Type', 'text/plain');

        ob_start();
        $response->stream(function (): void {
            echo 'chunk1';
            echo 'chunk2';
        });
        $output = ob_get_clean();

        $this->assertSame('chunk1chunk2', $output);
    }

    public function test_stream_callable_is_called_exactly_once(): void
    {
        $calls = 0;
        (new Response())->stream(function () use (&$calls): void {
            $calls++;
        });

        $this->assertSame(1, $calls);
    }

    public function test_stream_uses_response_status_and_headers(): void
    {
        $response = (new Response())
            ->withStatus(206)
            ->withHeader('Content-Range', 'bytes 0-999/5000');

        $this->assertSame(206, $response->getStatus());
        $this->assertSame('bytes 0-999/5000', $response->getHeaders()['Content-Range']);
    }

    public function test_send_outputs_body_to_stdout(): void
    {
        $response = (new Response())->withJson(['ok' => true], 200);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('{"ok":true}', $output);
    }

    public function test_legacy_json_outputs_encoded_body(): void
    {
        ob_start();
        (new Response())->json(['ok' => true], 200);
        $output = ob_get_clean();

        $this->assertSame('{"ok":true}', $output);
    }
}
