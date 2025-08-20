<?php

namespace McpSkeleton\Tests;

use McpSkeleton\Examples\DateTimeServer;
use PHPUnit\Framework\TestCase;

class DateTimeServerTest extends TestCase
{
    private DateTimeServer $server;

    protected function setUp(): void
    {
        $this->server = new DateTimeServer();
    }

    public function testInitializeRequest(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [],
                'clientInfo' => ['name' => 'test-client', 'version' => '1.0.0']
            ]
        ];

        $response = $this->invokePrivateMethod($this->server, 'handleRequest', [$request]);

        $this->assertArrayHasKey('jsonrpc', $response);
        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(1, $response['id']);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('datetime-mcp-server', $response['result']['serverInfo']['name']);
        $this->assertEquals('1.0.0', $response['result']['serverInfo']['version']);
    }

    public function testToolsListRequest(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list'
        ];

        $response = $this->invokePrivateMethod($this->server, 'handleRequest', [$request]);

        $this->assertArrayHasKey('result', $response);
        $this->assertArrayHasKey('tools', $response['result']);
        $this->assertCount(3, $response['result']['tools']); // sunset_time, days_remaining, current_time
    }

    public function testSunsetTimeTool(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'sunset_time',
                'arguments' => ['latitude' => 35.6762, 'longitude' => 139.6503]
            ]
        ];

        $response = $this->invokePrivateMethod($this->server, 'handleRequest', [$request]);

        $this->assertArrayHasKey('result', $response);
        $this->assertStringContainsString('Sunset time:', $response['result']['content'][0]['text']);
    }

    public function testUnknownToolRequest(): void
    {
        $request = [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => [
                'name' => 'unknown_tool',
                'arguments' => []
            ]
        ];

        $response = $this->invokePrivateMethod($this->server, 'handleRequest', [$request]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals(-32000, $response['error']['code']);
        $this->assertStringContainsString('Unknown tool: unknown_tool', $response['error']['message']);
    }

    public function testIsCompleteJsonRpc(): void
    {
        $this->assertTrue($this->invokePrivateMethod($this->server, 'isCompleteJsonRpc', ['{"test": "value"}']));
        $this->assertFalse($this->invokePrivateMethod($this->server, 'isCompleteJsonRpc', ['{"test": ']));
        $this->assertFalse($this->invokePrivateMethod($this->server, 'isCompleteJsonRpc', ['']));
        $this->assertFalse($this->invokePrivateMethod($this->server, 'isCompleteJsonRpc', ['not json']));
    }

    private function invokePrivateMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
