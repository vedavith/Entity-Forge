<?php

namespace Tests\Http;

use EntityForge\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_json_outputs_encoded_array(): void
    {
        $response = new Response();

        ob_start();
        $response->json(['key' => 'value']);
        $output = ob_get_clean();

        $this->assertSame('{"key":"value"}', $output);
    }

    public function test_json_outputs_nested_array(): void
    {
        $response = new Response();

        ob_start();
        $response->json(['user' => ['id' => 1, 'name' => 'Alice']]);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertSame('Alice', $decoded['user']['name']);
    }

    public function test_json_outputs_empty_array(): void
    {
        $response = new Response();

        ob_start();
        $response->json([]);
        $output = ob_get_clean();

        $this->assertSame('[]', $output);
    }
}
