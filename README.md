# debug-mcp-resources

**⚠️ PROTOTYPE - FOR TESTING AND DISCUSSION PURPOSES ONLY**

---

Documentation and reference resources for PHP and Symfony development.

## Purpose

Provides curated documentation resources accessible via the MCP protocol:
- **PHP Documentation**: Best practices, common patterns, language features
- **Symfony Documentation**: Console commands, dependency injection, framework guides

## Features

- URI-based resource access (e.g., `php://docs/best-practices`)
- Markdown-formatted content optimized for LLM consumption
- Framework-specific guides and examples
- Automatic discovery by debug-mcp server

## Installation

```bash
composer require wachterjohannes/debug-mcp-resources
```

The resources will be automatically discovered when debug-mcp server starts.

## Available Resources

### PHP Documentation

Access via `php://docs/{topic}` URI pattern.

#### best-practices

URI: `php://docs/best-practices`

Content: PHP coding best practices including:
- Type declarations and strict types
- Error handling patterns
- Security considerations
- Modern PHP features (PHP 8+)

#### common-patterns

URI: `php://docs/common-patterns`

Content: Common PHP design patterns and idioms:
- Dependency injection
- Factory patterns
- Repository patterns
- Service layer architecture

### Symfony Documentation

Access via `symfony://docs/{topic}` URI pattern.

#### console-commands

URI: `symfony://docs/console-commands`

Content: Symfony Console component guide:
- Creating custom commands
- Command arguments and options
- Input/output handling
- Command lifecycle

#### dependency-injection

URI: `symfony://docs/dependency-injection`

Content: Symfony DI container guide:
- Service configuration
- Autowiring
- Service tags
- Compiler passes

## Usage

Resources are accessed via MCP resource requests:

```json
{
  "jsonrpc": "2.0",
  "method": "resources/get",
  "params": {
    "uri": "php://docs/best-practices"
  },
  "id": 1
}
```

Response contains the markdown content:

```json
{
  "jsonrpc": "2.0",
  "result": {
    "contents": [
      {
        "uri": "php://docs/best-practices",
        "mimeType": "text/markdown",
        "text": "# PHP Best Practices\n\n..."
      }
    ]
  },
  "id": 1
}
```

## Resource Structure

### Markdown Files

All resource content is stored as markdown files in `resources/`:

```
resources/
├── php/
│   ├── best-practices.md
│   └── common-patterns.md
└── symfony/
    ├── console-commands.md
    └── dependency-injection.md
```

### Resource Classes

Each resource provider class handles a specific documentation domain:

```php
<?php
namespace Wachterjohannes\DebugMcp\Resources;

use Mcp\Capability\Attribute\McpResourceTemplate;

class PhpDocumentationResource
{
    #[McpResourceTemplate(
        uriTemplate: 'php://docs/{topic}',
        name: 'php_docs',
        mimeType: 'text/markdown'
    )]
    public function getDocumentation(string $topic): string
    {
        // Load and return markdown content
    }
}
```

## Adding New Resources

To add a new resource:

1. **Create Markdown File**:
   Place in appropriate `resources/` subdirectory

2. **Update Resource Class** (if needed):
   - Extend URI template patterns
   - Add topic validation
   - Handle new file locations

3. **Document in README**:
   Add to Available Resources section with URI pattern and description

4. **Optional: Create New Resource Class**:
   For a new documentation domain, create new class and register in composer.json

## Registration

Resources are registered via composer.json extra configuration:

```json
{
  "extra": {
    "wachterjohannes/debug-mcp": {
      "classes": [
        "Wachterjohannes\\DebugMcp\\Resources\\PhpDocumentationResource",
        "Wachterjohannes\\DebugMcp\\Resources\\SymfonyDocumentationResource"
      ]
    }
  }
}
```

## Development

### Code Quality

Format code before committing:

```bash
composer cs-fix
```

### Content Guidelines

When creating resource content:

1. **Use Markdown**: Clear headings, code blocks, lists
2. **Provide Examples**: Working code snippets
3. **Focus on Practical**: Real-world use cases
4. **Keep Concise**: Information density for LLM context
5. **Include Links**: References to official documentation

### Testing Resources

Test resources by:
1. Installing package in debug-mcp instance
2. Starting MCP server
3. Requesting resource via JSON-RPC
4. Verifying content and formatting

## Requirements

- PHP 8.1 or higher
- modelcontextprotocol/php-sdk
- wachterjohannes/debug-mcp (for testing)

## Repository

GitHub: https://github.com/wachterjohannes/debug-mcp-resources

## License

MIT
