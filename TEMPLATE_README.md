# Your MCP Server Name

Brief description of what your MCP server does.

## Installation

```bash
composer install
```

## Usage

### Add to Claude Desktop

```bash
claude mcp add your-server-name php /path/to/your/bin/your-mcp-server
```

### Manual Configuration

Add to your MCP client configuration:

```json
{
  "mcpServers": {
    "your-server-name": {
      "command": "php",
      "args": ["/path/to/your/bin/your-mcp-server"]
    }
  }
}
```

## Available Tools

- **tool_name_1**: Description of what this tool does
- **tool_name_2**: Description of what this tool does
- **tool_name_3**: Description of what this tool does

## Development

```bash
# Run tests
composer test

# Static analysis
composer psalm

# Both
composer check
```

## Requirements

- PHP >= 8.3
- Composer