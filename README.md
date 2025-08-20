# MCP Skeleton - PHP MCP Server Template

A skeleton template for creating [Model Context Protocol (MCP)](https://modelcontextprotocol.io/) servers in PHP.

## What is MCP?

The Model Context Protocol (MCP) is an open protocol that enables AI assistants to securely connect to data sources and tools. This skeleton provides a ready-to-use foundation for building MCP servers in PHP.

## Why MCP Skeleton?

**Perfect for 95% of MCP server needs:**

- **stdio-only** - Works with Claude Desktop and standard MCP clients
- **Zero dependencies** - Just PHP 8.3+, no external frameworks
- **AI-generation friendly** - Simple, predictable structure that AI tools understand
- **Single file deployment** - Copy one executable, it just works

**For complex needs:** Use [php-mcp/server](https://github.com/php-mcp/server) (HTTP transports, dependency injection, enterprise features)

## Features

- Complete MCP protocol implementation
- JSON-RPC 2.0 communication handling
- Type-safe with Psalm annotations
- PHP 8.3+ compatibility
- Easy to extend and customize
- Error handling and debugging support

## Quick Start

### 1. Copy the skeleton

```bash
# Copy this directory to your new project
cp -r mcp-skeleton my-mcp-server
cd my-mcp-server
```

### 2. Install dependencies

```bash
composer install
```

### 3. Customize for your use case

1. **Update `composer.json`**:
   - Change package name from `mcp/skeleton`
   - Update description and keywords
   - Modify namespace from `McpSkeleton`

2. **Implement your server**:
   - Extend `SkeletonMcpServer` class
   - Implement `initializeTools()` method
   - Implement `executeToolCall()` method

3. **Create your executable**:
   - Update `bin/mcp-skeleton` script
   - Point to your server class

4. **Create your README**:
   - Copy `TEMPLATE_README.md` to `README.md`
   - Customize with your server's details

### 4. Example Implementation

```php
<?php

namespace MyProject;

use McpSkeleton\SkeletonMcpServer;

class MyMcpServer extends SkeletonMcpServer
{
    protected const SERVER_NAME = 'my-mcp-server';
    protected const SERVER_VERSION = '1.0.0';

    protected function initializeTools(): void
    {
        $this->addTool(
            'hello',
            'Say hello to someone',
            $this->createInputSchema([
                'name' => $this->createProperty('string', 'Name of the person')
            ], ['name'])
        );
    }

    protected function executeToolCall(string $toolName, array $arguments): string
    {
        switch ($toolName) {
            case 'hello':
                $name = $arguments['name'] ?? 'World';
                return "Hello, {$name}!";
            
            default:
                throw new \Exception("Unknown tool: {$toolName}");
        }
    }
}
```

## Architecture

### Core Components

- **`SkeletonMcpServer`**: Abstract base class with MCP protocol implementation
- **Tool System**: Helper methods for defining tool schemas
- **Type Safety**: Comprehensive Psalm type definitions
- **Error Handling**: Robust error handling and debugging support

### Key Methods to Implement

- `initializeTools()`: Define your MCP tools
- `executeToolCall()`: Handle tool execution

### Key Constants to Define

- `SERVER_NAME`: Your server name
- `SERVER_VERSION`: Your server version

### Helper Methods Available

- `addTool()`: Add a tool to your server
- `createInputSchema()`: Create JSON schema for tool inputs
- `createProperty()`: Define property schemas
- `createEnumProperty()`: Define enum properties
- `createArrayProperty()`: Define array properties

## Configuration

### Environment Variables

- `MCP_DEBUG=1`: Enable debug logging

### Psalm Integration

Run static analysis:

```bash
composer run psalm
```

## Usage Examples

See the `examples/` directory for complete implementation examples:

- **Simple Calculator**: Basic arithmetic operations
- **File Operations**: File system tools
- **API Client**: HTTP request tools

## Testing Your Server

Test with a simple JSON-RPC request:

```bash
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | php bin/mcp-skeleton
```

## MCP Client Integration

Add to your MCP client configuration (e.g., Claude Desktop):

```json
{
  "mcpServers": {
    "my-server": {
      "command": "php",
      "args": ["/path/to/your/bin/mcp-skeleton"]
    }
  }
}
```

## Development

1. **Create new tools**: Add them in `initializeTools()`
2. **Handle tool calls**: Implement logic in `executeToolCall()`
3. **Test frequently**: Use JSON-RPC requests to verify functionality
4. **Type checking**: Run Psalm to ensure type safety

## Best Practices

- Keep tool implementations focused and single-purpose
- Use descriptive tool names and descriptions
- Provide comprehensive input schemas
- Handle errors gracefully
- Log important events for debugging


## Resources

- [Model Context Protocol Specification](https://modelcontextprotocol.io/docs)
- [MCP Servers Repository](https://github.com/modelcontextprotocol/servers)
- [JSON-RPC 2.0 Specification](https://www.jsonrpc.org/specification)
