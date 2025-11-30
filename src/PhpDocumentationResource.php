<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp\Resources;

use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;

/**
 * Provides PHP language documentation and best practices.
 *
 * Serves markdown documentation for PHP development topics including
 * best practices, common patterns, and language features.
 */
class PhpDocumentationResource
{
    /**
     * Allowed documentation topics.
     *
     * @var array<string>
     */
    private const ALLOWED_TOPICS = [
        'best-practices',
        'common-patterns',
    ];

    /**
     * Topic aliases that map to canonical topic names.
     *
     * @var array<string, string>
     */
    private const TOPIC_ALIASES = [
        'best' => 'best-practices',
        'practices' => 'best-practices',
        'patterns' => 'common-patterns',
    ];

    /**
     * Get list of available PHP documentation topics.
     *
     * @return string Markdown-formatted list of available topics
     */
    #[McpResource(
        uri: 'php://docs/index',
        name: 'php_docs_index',
        description: 'List of available PHP documentation topics',
        mimeType: 'text/markdown'
    )]
    public function getIndex(): string
    {
        $topics = array_map(
            fn($topic) => "- `{$topic}`: Access via `php://docs/{$topic}`",
            self::ALLOWED_TOPICS
        );

        $aliases = array_map(
            fn($alias, $target) => "- `{$alias}` → `{$target}`",
            array_keys(self::TOPIC_ALIASES),
            array_values(self::TOPIC_ALIASES)
        );

        $topicsList = implode("\n", $topics);
        $aliasesList = implode("\n", $aliases);

        return <<<MARKDOWN
# PHP Documentation Topics

## Available Topics

{$topicsList}

## Topic Aliases

{$aliasesList}

## Usage Examples

- Full topic name: `php://docs/best-practices`
- Using alias: `php://docs/best`
MARKDOWN;
    }

    /**
     * Get PHP documentation content for a specific topic.
     *
     * @param string $topic The documentation topic to retrieve
     *
     * @return string Markdown-formatted documentation content
     *
     * @throws \InvalidArgumentException When topic is invalid or contains path traversal
     * @throws \RuntimeException When resource file cannot be found or read
     */
    #[McpResourceTemplate(
        uriTemplate: 'php://docs/{topic}',
        name: 'php_docs',
        description: 'PHP language documentation and best practices',
        mimeType: 'text/markdown'
    )]
    public function getDocumentation(string $topic): string
    {
        // Validate topic does not contain path traversal characters
        if (str_contains($topic, '/') || str_contains($topic, '\\')) {
            throw new \InvalidArgumentException(
                sprintf('Invalid topic parameter: %s', $topic)
            );
        }

        // Resolve topic aliases to canonical names
        $canonicalTopic = self::TOPIC_ALIASES[$topic] ?? $topic;

        // Validate topic against allowlist
        if (!in_array($canonicalTopic, self::ALLOWED_TOPICS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown topic: %s. Available topics: %s',
                    $topic,
                    implode(', ', self::ALLOWED_TOPICS)
                )
            );
        }

        // Construct safe file path using canonical topic
        $filePath = __DIR__ . '/../resources/php/' . $canonicalTopic . '.md';

        // Verify file exists
        if (!file_exists($filePath)) {
            throw new \RuntimeException(
                sprintf('Resource file not found for topic: %s', $topic)
            );
        }

        // Read and return content
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException(
                sprintf('Failed to read resource file for topic: %s', $topic)
            );
        }

        return $content;
    }
}
