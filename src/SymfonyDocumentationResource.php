<?php

declare(strict_types=1);

namespace Wachterjohannes\DebugMcp\Resources;

use PhpMcp\Server\Attributes\McpResourceTemplate;

/**
 * Provides Symfony framework documentation and guides.
 *
 * Serves markdown documentation for Symfony framework topics including
 * console commands, dependency injection, and component usage.
 */
class SymfonyDocumentationResource
{
    /**
     * Allowed documentation topics.
     *
     * @var array<string>
     */
    private const ALLOWED_TOPICS = [
        'console-commands',
        'dependency-injection',
    ];

    /**
     * Get Symfony documentation content for a specific topic.
     *
     * @param string $topic The documentation topic to retrieve
     *
     * @return string Markdown-formatted documentation content
     *
     * @throws \InvalidArgumentException When topic is invalid or contains path traversal
     * @throws \RuntimeException When resource file cannot be found or read
     */
    #[McpResourceTemplate(
        uriTemplate: 'symfony://docs/{topic}',
        name: 'symfony_docs',
        mimeType: 'text/markdown',
        description: 'Symfony framework documentation and guides'
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
        $filePath = __DIR__ . '/../resources/symfony/' . $topic . '.md';

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
