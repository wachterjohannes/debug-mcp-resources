<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp\Resources;

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

        // Validate topic against allowlist
        if (!in_array($topic, self::ALLOWED_TOPICS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown topic: %s. Available topics: %s',
                    $topic,
                    implode(', ', self::ALLOWED_TOPICS)
                )
            );
        }

        // Construct safe file path
        $filePath = __DIR__ . '/../resources/php/' . $topic . '.md';

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
