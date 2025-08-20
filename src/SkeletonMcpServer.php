<?php

namespace McpSkeleton;

/**
 * SkeletonMcpServer - A base MCP server implementation
 * 
 * This is a generic MCP (Model Context Protocol) server skeleton that can be extended
 * to create custom MCP servers. It provides the basic MCP protocol handling,
 * JSON-RPC communication, and a framework for implementing custom tools.
 * 
 * @psalm-type McpTool = array{
 *     name: string,
 *     description: string,
 *     inputSchema: array<string, mixed>
 * }
 * @psalm-type McpToolsList = list<McpTool>
 * @psalm-type McpJsonRpcRequest = array{
 *     jsonrpc: string,
 *     method: string,
 *     id: int|string|null,
 *     params?: array<string, mixed>
 * }
 * @psalm-type McpJsonRpcResponse = array{
 *     jsonrpc: string,
 *     id: int|string|null,
 *     result?: array<string, mixed>,
 *     error?: array{code: int, message: string}
 * }
 * @psalm-type McpInitializeResult = array{
 *     protocolVersion: string,
 *     capabilities: array{
 *         tools: array{listChanged: bool},
 *         resources: object,
 *         prompts: object
 *     },
 *     serverInfo: array{name: string, version: string}
 * }
 * @psalm-type McpToolCallResult = array{
 *     content: list<array{type: string, text: string}>
 * }
 * @psalm-type McpInputSchema = array{
 *     type: string,
 *     properties: object|array<string, mixed>,
 *     required?: list<string>
 * }
 * @psalm-type McpPropertySchema = array{
 *     type: string,
 *     description?: string,
 *     default?: mixed,
 *     enum?: list<string>,
 *     items?: array<string, mixed>
 * }
 * @psalm-type DebugLogData = array{
 *     timestamp: string,
 *     message: string,
 *     data: array<string, mixed>
 * }
 * @template TArguments of array<string, mixed> = array<string, mixed>
 */
abstract class SkeletonMcpServer
{
    /** Server name constant - override in subclass */
    protected const SERVER_NAME = 'skeleton-mcp-server';
    
    /** Server version constant - override in subclass */
    protected const SERVER_VERSION = '1.0.0';
    
    /** @var array<string, McpTool> */
    protected array $tools = [];
    private bool $debugMode = false;

    public function __construct()
    {
        $envDebug = getenv('MCP_DEBUG');
        $this->debugMode = in_array(strtolower((string)$envDebug), ['1', 'true'], true);
        $this->initializeTools();
    }

    /**
     * Override this method to define your custom tools
     */
    abstract protected function initializeTools(): void;

    /**
     * Override this method to handle tool execution
     */
    /**
     * @param TArguments $arguments
     */
    abstract protected function executeToolCall(string $toolName, array $arguments): string;

    /**
     * Log debug information when debug mode is enabled
     */
    /**
     * @param array<string, mixed> $data
     */
    private function debugLog(string $message, array $data = []): void
    {
        if ($this->debugMode) {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => $message,
                'data' => $data
            ];
            error_log('MCP Debug: ' . json_encode($logData));
        }
    }

    /**
     * Main execution method - handles JSON-RPC communication
     */
    public function __invoke(): void
    {
        try {
            $input = '';

            // Check if STDIN is available and not closed immediately
            if (feof(STDIN)) {
                // Handle immediate EOF - this might be a health check
                return;
            }

            while (($line = fgets(STDIN)) !== false) {
                $input .= $line;

                if ($this->isCompleteJsonRpc($input)) {
                    $request = json_decode(trim($input), true);

                    if ($request === null) {
                        // Invalid JSON, send parse error
                        $errorResponse = [
                            'jsonrpc' => '2.0',
                            'id' => null,
                            'error' => [
                                'code' => -32700,
                                'message' => 'Parse error'
                            ]
                        ];
                        echo json_encode($errorResponse) . "\n";
                        fflush(STDOUT);
                    } else {
                        $this->debugLog('Received request', $request);

                        try {
                            $response = $this->handleRequest($request);

                            if ($response !== null) {
                                $this->debugLog('Sending response', $response);
                                echo json_encode($response) . "\n";
                                fflush(STDOUT);
                            }
                        } catch (\Exception $e) {
                            error_log("MCP Server Error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());

                            $errorResponse = [
                                'jsonrpc' => '2.0',
                                'id' => $request['id'] ?? null,
                                'error' => [
                                    'code' => -32603,
                                    'message' => 'Internal error: ' . $e->getMessage()
                                ]
                            ];
                            echo json_encode($errorResponse) . "\n";
                            fflush(STDOUT);
                        }
                    }

                    $input = '';
                }
            }
        } catch (\Exception $e) {
            error_log("MCP Server Fatal Error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Check if input contains a complete JSON-RPC message
     */
    private function isCompleteJsonRpc(string $input): bool
    {
        $trimmed = trim($input);
        if (empty($trimmed)) {
            return false;
        }

        return json_validate($trimmed);
    }

    /**
     * Handle incoming JSON-RPC requests
     */
    /**
     * @param McpJsonRpcRequest $request
     * @return McpJsonRpcResponse|null
     */
    private function handleRequest(array $request): ?array
    {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;

        try {
            switch ($method) {
                case 'initialize':
                    return $this->handleInitialize($id, $params);
                case 'tools/list':
                    return $this->handleToolsList($id);
                case 'tools/call':
                    return $this->handleToolCall($id, $params);
                case 'resources/list':
                    return $this->handleResourcesList($id);
                case 'prompts/list':
                    return $this->handlePromptsList($id);
                case 'notifications/initialized':
                    // Handle initialized notification (no response needed)
                    return null;
                default:
                    return [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'error' => [
                            'code' => -32601,
                            'message' => "Method not found: {$method}"
                        ]
                    ];
            }
        } catch (\Exception $e) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32000,
                    'message' => 'Server error: ' . $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Handle initialize request
     * Override this method to customize server information
     */
    /**
     * @param array<string, mixed> $params
     * @return McpJsonRpcResponse
     */
    protected function handleInitialize(mixed $id, array $params): array
    {
        // Use the protocol version requested by the client, defaulting to latest
        $clientVersion = $params['protocolVersion'] ?? '2025-06-18';

        // Ensure we support the requested version
        $supportedVersions = ['2024-11-05', '2025-03-26', '2025-06-18'];
        if (!in_array($clientVersion, $supportedVersions)) {
            $clientVersion = '2025-06-18';
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => $clientVersion,
                'capabilities' => [
                    'tools' => [
                        'listChanged' => true
                    ],
                    'resources' => (object)[],
                    'prompts' => (object)[]
                ],
                'serverInfo' => [
                    'name' => static::SERVER_NAME,
                    'version' => static::SERVER_VERSION
                ]
            ]
        ];
    }


    /**
     * Handle tools/list request
     */
    /**
     * @return McpJsonRpcResponse
     */
    private function handleToolsList(mixed $id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => array_values($this->tools)
            ]
        ];
    }

    /**
     * Handle resources/list request
     * Override this method to provide resources
     */
    /**
     * @return McpJsonRpcResponse
     */
    protected function handleResourcesList(mixed $id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'resources' => []
            ]
        ];
    }

    /**
     * Handle prompts/list request
     * Override this method to provide prompts
     */
    /**
     * @return McpJsonRpcResponse
     */
    protected function handlePromptsList(mixed $id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'prompts' => []
            ]
        ];
    }

    /**
     * Handle tools/call request
     */
    /**
     * @param array<string, mixed> $params
     * @return McpJsonRpcResponse
     */
    private function handleToolCall(mixed $id, array $params): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        try {
            $result = $this->executeToolCall($toolName, $arguments);

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $result
                        ]
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32000,
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Helper method to add a tool to the tools array
     */
    /**
     * @param McpInputSchema $inputSchema
     */
    protected function addTool(string $name, string $description, array $inputSchema): void
    {
        $this->tools[$name] = [
            'name' => $name,
            'description' => $description,
            'inputSchema' => $inputSchema
        ];
    }

    /**
     * Helper method to create a simple input schema
     */
    /**
     * @param array<string, McpPropertySchema> $properties
     * @param list<string> $required
     * @return McpInputSchema
     */
    protected function createInputSchema(array $properties = [], array $required = []): array
    {
        return [
            'type' => 'object',
            'properties' => empty($properties) ? (object)[] : $properties,
            'required' => $required
        ];
    }

    /**
     * Helper method to create a property definition
     */
    /**
     * @return McpPropertySchema
     */
    protected function createProperty(string $type, ?string $description = null, $default = null): array
    {
        $property = ['type' => $type];
        
        if ($description !== null) {
            $property['description'] = $description;
        }
        
        if ($default !== null) {
            $property['default'] = $default;
        }
        
        return $property;
    }

    /**
     * Helper method to create an enum property
     */
    /**
     * @param list<string> $values
     * @return McpPropertySchema
     */
    protected function createEnumProperty(array $values, ?string $description = null, $default = null): array
    {
        $property = [
            'type' => 'string',
            'enum' => $values
        ];
        
        if ($description !== null) {
            $property['description'] = $description;
        }
        
        if ($default !== null) {
            $property['default'] = $default;
        }
        
        return $property;
    }

    /**
     * Helper method to create an array property
     */
    /**
     * @param array<string, mixed> $items
     * @return McpPropertySchema
     */
    protected function createArrayProperty(array $items = [], ?string $description = null): array
    {
        $property = ['type' => 'array'];
        
        if (!empty($items)) {
            $property['items'] = $items;
        }
        
        if ($description !== null) {
            $property['description'] = $description;
        }
        
        return $property;
    }
}
