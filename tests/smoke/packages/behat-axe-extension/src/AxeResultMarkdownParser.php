<?php

declare(strict_types=1);

namespace BehatAxeExtension;

/**
 * Converts a raw Axe violation result (as returned by axe.run() and decoded
 * into a PHP array) into a human-readable Markdown report.
 */
class AxeResultMarkdownParser
{
    /**
     * Parses a single Axe result array into Markdown.
     *
     * @param array $result A decoded Axe violation result.
     */
    public static function parse(array $result): string
    {
        $lines = [];

        $lines[] = sprintf(
            '### "%s" (%s impact)',
            $result['id'] ?? 'Unknown rule',
            $result['impact'] ?? 'unknown'
        );
        $lines[] = '';

        if (! empty($result['help'])) {
            $lines[] = sprintf('**Help:** [%s](%s)  ', $result['help'], $result['helpUrl']);
        }

        if (! empty($result['tags']) && is_array($result['tags'])) {
            $lines[] = sprintf('**Tags:** %s  ', implode(', ', $result['tags']));
        }

        $lines[] = '';

        if (! empty($result['nodes']) && is_array($result['nodes'])) {
            $lines[] = "### Affected elements\n";

            foreach ($result['nodes'] as $node) {
                $lines[] = self::parseNode($node);
            }
        }

        return trim(implode("\n", $lines)) . "\n";
    }

    /**
     * Parses a collection of Axe results into a single Markdown document.
     *
     * @param array[] $results A list of decoded Axe violation results.
     */
    public static function parseAll(array $results): string
    {
        if ($results === []) {
            return '';
        }

        return implode(
            "\n---\n\n",
            array_map(fn (array $result): string => self::parse($result), $results)
        );
    }

    private static function parseNode(array $node): string
    {
        $lines = [];

        $target  = implode(', ', $node['target'] ?? []);
        $lines[] = sprintf('- **Selector:** `%s`', $target);

        if (! empty($node['html'])) {
            $lines[] = sprintf("  ```html\n  %s\n  ```", $node['html']);
        }

        foreach (['any', 'all', 'none'] as $checkType) {
            if (empty($node[$checkType]) || ! is_array($node[$checkType])) {
                continue;
            }

            $lines = self::parseCheckType($node[$checkType], $lines);
        }

        return implode("\n", $lines);
    }

    public static function parseCheckType($node, array $lines): array
    {
        foreach ($node as $check) {
            if (!empty($check['message'])) {
                $relatedCount = is_array($check['relatedNodes'] ?? null) ? count($check['relatedNodes']) : 0;
                $suffix       = $relatedCount > 0
                    ? sprintf(' (%d related node%s)', $relatedCount, $relatedCount === 1 ? '' : 's')
                    : '';
                $lines[]      = sprintf('  *Check:* %s%s', $check['message'], $suffix);
            }
        }
        return $lines;
    }
}
