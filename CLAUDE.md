# debug-mcp-resources - Resource Provider Package

## Project Overview

This package provides documentation resources for PHP and Symfony development, accessible via the MCP protocol. Resources are served as markdown content optimized for LLM consumption.

**Role in Ecosystem**: Extension package providing contextual documentation

**Key Responsibility**: Curated technical documentation via URI-addressable resources

## Architecture

### Component Structure

```
debug-mcp-resources
├── PhpDocumentationResource
│   ├── php://docs/best-practices
│   └── php://docs/common-patterns
└── SymfonyDocumentationResource
    ├── symfony://docs/console-commands
    └── symfony://docs/dependency-injection
```

### Resource Pattern

Resources use URI templates with dynamic parameters:

```php
use Mcp\Capability\Attribute\McpResourceTemplate;

class DocumentationResource
{
    #[McpResourceTemplate(
        uriTemplate: 'scheme://docs/{topic}',
        name: 'resource_name',
        mimeType: 'text/markdown'
    )]
    public function getContent(string $topic): string
    {
        // Load markdown file based on topic
        return file_get_contents(__DIR__ . "/../resources/{$topic}.md");
    }
}
```

## Resource Specifications

### PhpDocumentationResource

**Purpose**: Provide PHP language and best practices documentation

**URI Pattern**: `php://docs/{topic}`

**Supported Topics**:
- `best-practices`: Modern PHP development standards
- `common-patterns`: Design patterns and architectural idioms

**Implementation Notes**:
- Validates topic parameter against allowed values
- Returns 404/error for unknown topics
- Loads content from `resources/php/` directory
- Returns raw markdown text

**Content Guidelines**:
- Focus on PHP 8.1+ features
- Include type hints and strict types
- Show security-conscious examples
- Reference PSR standards where applicable

### SymfonyDocumentationResource

**Purpose**: Provide Symfony framework documentation and guides

**URI Pattern**: `symfony://docs/{topic}`

**Supported Topics**:
- `console-commands`: Symfony Console component usage
- `dependency-injection`: DI container and service configuration

**Implementation Notes**:
- Validates topic parameter
- Loads content from `resources/symfony/` directory
- Optimized for Symfony 6+ (current LTS and stable)
- Returns markdown with code examples

**Content Guidelines**:
- Show modern Symfony practices (attributes, autowiring)
- Include YAML/PHP configuration examples
- Reference official docs for deep dives
- Demonstrate real-world use cases

## Development Guidelines

### Adding New Resources

**Method 1: Extend Existing Resource Class**

Add new markdown file and update topic validation:

```php
private const ALLOWED_TOPICS = [
    'best-practices',
    'common-patterns',
    'new-topic',  // Add here
];
```

**Method 2: Create New Resource Class**

For a new documentation domain:

1. Create class in `src/`
2. Add `#[McpResourceTemplate]` attribute
3. Implement content loading logic
4. Register in `composer.json` extra config
5. Create `resources/domain/` directory
6. Add markdown files

### Content Creation

**Markdown Structure**:
```markdown
# Topic Title

Brief overview of the topic.

## Section 1

Content with examples.

### Subsection

```php
// Code example
```

## Key Points

- Bullet point 1
- Bullet point 2

## Further Reading

- [Official Docs](https://example.com)
```

**Best Practices**:
- **Headings**: Clear hierarchy (H1 for title, H2 for sections)
- **Code Blocks**: Always specify language for syntax highlighting
- **Length**: Aim for 500-2000 words per resource
- **Examples**: Practical, working code snippets
- **Links**: Minimal external links, focus on self-contained content

### Code Style

- **PSR-12**: Follow PSR-12 coding standards
- **Type Hints**: All parameters and returns typed
- **Validation**: Validate topic parameters before file access
- **Error Handling**: Return meaningful errors for invalid topics
- **File Security**: Validate paths to prevent directory traversal

## Integration Points

### With debug-mcp Server

The server discovers resources through:
1. Reading `vendor/composer/installed.json`
2. Finding this package's `extra.wachterjohannes/debug-mcp.classes`
3. Instantiating resource classes
4. SDK discovers methods via `#[McpResourceTemplate]` attributes

### With MCP SDK

Uses official `modelcontextprotocol/php-sdk` attributes:

```php
use Mcp\Capability\Attribute\McpResourceTemplate;

#[McpResourceTemplate(
    uriTemplate: 'scheme://path/{param}',
    name: 'resource_identifier',
    mimeType: 'text/markdown'
)]
```

**URI Template Parameters**:
- Extracted from curly braces in template: `{param}`
- Passed as method parameters in order
- Must match method signature

**MIME Type**:
- `text/markdown` for documentation content
- `application/json` for structured data
- `text/plain` for raw text

## Key Implementation Patterns

### Topic Validation

```php
private const ALLOWED_TOPICS = ['topic1', 'topic2'];

public function getContent(string $topic): string
{
    if (!in_array($topic, self::ALLOWED_TOPICS, true)) {
        throw new \InvalidArgumentException("Unknown topic: {$topic}");
    }

    $file = __DIR__ . "/../resources/domain/{$topic}.md";

    if (!file_exists($file)) {
        throw new \RuntimeException("Resource file not found: {$topic}");
    }

    return file_get_contents($file);
}
```

### File Path Security

Prevent directory traversal:

```php
public function getContent(string $topic): string
{
    // Validate topic contains no path separators
    if (str_contains($topic, '/') || str_contains($topic, '\\')) {
        throw new \InvalidArgumentException('Invalid topic parameter');
    }

    // Use allowlist instead of path validation
    if (!in_array($topic, self::ALLOWED_TOPICS, true)) {
        throw new \InvalidArgumentException("Unknown topic: {$topic}");
    }

    // Now safe to construct path
    $file = __DIR__ . "/../resources/{$topic}.md";

    return file_get_contents($file);
}
```

### Dynamic Resource Loading

For resources with many topics:

```php
public function getContent(string $topic): string
{
    $directory = __DIR__ . '/../resources/domain';

    // List available files dynamically
    $files = glob($directory . '/*.md');
    $availableTopics = array_map(
        fn($file) => basename($file, '.md'),
        $files
    );

    if (!in_array($topic, $availableTopics, true)) {
        throw new \InvalidArgumentException("Unknown topic: {$topic}");
    }

    return file_get_contents("{$directory}/{$topic}.md");
}
```

## Sample Resource Content

### resources/php/best-practices.md

```markdown
# PHP Best Practices

Modern PHP development guidelines for PHP 8.1+.

## Type Declarations

Always use strict types and declare parameter/return types:

```php
<?php
declare(strict_types=1);

function processData(string $input): array
{
    // Implementation
}
```

## Error Handling

Use typed exceptions and provide context:

```php
throw new InvalidArgumentException(
    sprintf('Invalid email: %s', $email)
);
```
```

## SDK Attribute Reference

### #[McpResourceTemplate]

**Required Properties**:
- `uriTemplate` (string): URI pattern with parameters in curly braces
- `name` (string): Unique resource identifier
- `mimeType` (string): Content MIME type

**Optional Properties**:
- `description` (string): Human-readable resource purpose

**Usage**:
```php
#[McpResourceTemplate(
    uriTemplate: 'scheme://path/{param}',
    name: 'my_resource',
    mimeType: 'text/markdown',
    description: 'Resource description'
)]
```

## Quick Implementation Checklist

- [ ] `src/PhpDocumentationResource.php` - PHP docs resource
- [ ] `src/SymfonyDocumentationResource.php` - Symfony docs resource
- [ ] `resources/php/*.md` - PHP documentation markdown files
- [ ] `resources/symfony/*.md` - Symfony documentation markdown files
- [ ] `composer.json` - Package definition with extra config
- [ ] `README.md` - User documentation
- [ ] `.php-cs-fixer.php` - Code style configuration
- [ ] Test installation and resource access

## Future Resource Ideas

Potential resources to add:

1. **PSR Standards**: Documentation for PSR-1, PSR-12, PSR-4, etc.
2. **Testing Patterns**: PHPUnit, test doubles, integration testing
3. **Database**: Doctrine ORM, migrations, query optimization
4. **Security**: OWASP top 10 for PHP, secure coding practices
5. **Performance**: Profiling, optimization, caching strategies

## Repository Information

- **GitHub**: https://github.com/wachterjohannes/debug-mcp-resources
- **Packagist**: (publish after implementation)
- **License**: MIT
