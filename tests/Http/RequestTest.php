<?php

namespace Tests\Http;

use EntityForge\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function test_header_returns_value_when_present(): void
    {
        $request = new Request(headers: ['X-Tenant-ID' => 'acme']);

        $this->assertSame('acme', $request->header('X-Tenant-ID'));
    }

    public function test_header_returns_null_when_absent(): void
    {
        $request = new Request();

        $this->assertNull($request->header('X-Tenant-ID'));
    }

    public function test_query_returns_value_when_present(): void
    {
        $request = new Request(query: ['page' => '2']);

        $this->assertSame('2', $request->query('page'));
    }

    public function test_query_returns_null_when_absent(): void
    {
        $request = new Request();

        $this->assertNull($request->query('missing'));
    }

    public function test_body_returns_value_when_present(): void
    {
        $request = new Request(body: ['name' => 'Alice']);

        $this->assertSame('Alice', $request->body('name'));
    }

    public function test_body_returns_null_when_absent(): void
    {
        $request = new Request();

        $this->assertNull($request->body('missing'));
    }

    public function test_method_defaults_to_get(): void
    {
        $request = new Request();

        $this->assertSame('GET', $request->method());
    }

    public function test_method_returns_provided_method(): void
    {
        $request = new Request(method: 'POST');

        $this->assertSame('POST', $request->method());
    }

    public function test_path_defaults_to_slash(): void
    {
        $request = new Request();

        $this->assertSame('/', $request->path());
    }

    public function test_path_returns_provided_path(): void
    {
        $request = new Request(path: '/api/users');

        $this->assertSame('/api/users', $request->path());
    }

    public function test_param_returns_null_when_key_absent(): void
    {
        $request = new Request();

        $this->assertNull($request->param('id'));
    }

    public function test_withParams_returns_new_instance_with_params_set(): void
    {
        $original = new Request(path: '/users/42');
        $modified = $original->withParams(['id' => '42']);

        $this->assertNotSame($original, $modified);
        $this->assertSame('42', $modified->param('id'));
        $this->assertSame([], $original->params());
    }

    public function test_capture_skipped_outside_web_sapi(): void
    {
        if (!function_exists('getallheaders')) {
            $this->markTestSkipped('getallheaders() not available outside web SAPI');
        }

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_GET  = ['q' => 'search'];
        $_POST = ['name' => 'Alice'];

        $request = Request::capture();

        $this->assertSame('PUT', $request->method());
        $this->assertSame('search', $request->query('q'));
        $this->assertSame('Alice', $request->body('name'));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        $_POST = [];
    }

    // ── Attributes ─────────────────────────────────────────────────────────────

    public function test_with_attribute_returns_new_instance(): void
    {
        $request = new Request();
        $with = $request->withAttribute('user', ['id' => 1]);

        $this->assertNotSame($request, $with);
    }

    public function test_get_attribute_returns_set_value(): void
    {
        $user = ['id' => 42, 'email' => 'test@example.com'];
        $request = (new Request())->withAttribute('user', $user);

        $this->assertSame($user, $request->getAttribute('user'));
    }

    public function test_get_attribute_returns_default_when_missing(): void
    {
        $request = new Request();

        $this->assertNull($request->getAttribute('user'));
        $this->assertSame('guest', $request->getAttribute('user', 'guest'));
    }

    public function test_original_request_unaffected_after_with_attribute(): void
    {
        $request = new Request();
        $request->withAttribute('user', ['id' => 1]);

        $this->assertNull($request->getAttribute('user'));
    }

    public function test_attributes_chain(): void
    {
        $request = (new Request())
            ->withAttribute('user', ['id' => 1])
            ->withAttribute('role', 'admin');

        $this->assertSame(['id' => 1], $request->getAttribute('user'));
        $this->assertSame('admin', $request->getAttribute('role'));
    }
}
