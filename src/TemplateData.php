<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

final readonly class TemplateData
{
    /**
     * @param  array<string, mixed>  $template
     * @param  array<string, string>  $placeholders
     * @return array<string, mixed>
     */
    public static function interpolate(array $template, array $placeholders): array
    {
        array_walk_recursive($template, static function (mixed &$value) use ($placeholders): void {
            if (is_string($value)) {
                $value = preg_replace_callback(
                    '/\{\{\s*([a-z_.]+)\s*\}\}/',
                    static fn (array $matches): string => $placeholders[$matches[1]] ?? $matches[0],
                    $value,
                ) ?? $value;
            }
        });

        return $template;
    }

    public static function tidyText(string $text): string
    {
        $collapsed = preg_replace("/\n{3,}/", "\n\n", trim($text, "\n")) ?? $text;

        return implode("\n", array_map(rtrim(...), explode("\n", $collapsed)));
    }

    /**
     * @param  array<string, mixed>  $template
     * @return list<array<string, mixed>>
     */
    public static function blocks(array $template): array
    {
        $blocks = [];
        $rows = [
            ...(array) ($template['rows'] ?? []),
            ...(array) ($template['config']['page']['footer']['rows'] ?? []),
        ];

        foreach ($rows as $row) {
            foreach ((array) ($row['blocks'] ?? []) as $block) {
                if (is_array($block)) {
                    $blocks[] = $block;
                }
            }
        }

        return $blocks;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, string>|null
     */
    public static function contentOverride(array $block, string $content): ?array
    {
        return match ($block['type'] ?? null) {
            'text', 'heading' => ['text' => $content],
            'html' => ['html' => nl2br(e($content))],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, string|null>  $values
     * @return array<string, string>|null
     */
    public static function keyValueOverride(array $block, array $values): ?array
    {
        if (($block['type'] ?? null) !== 'key-value') {
            return null;
        }

        $declared = array_column((array) ($block['fields'] ?? []), 'key');

        return array_map(
            static fn (?string $value): string => $value ?? '',
            array_intersect_key($values, array_flip(array_filter($declared, is_string(...)))),
        );
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  list<array<string, string>>  $rows
     * @return list<array<string, string>>|null
     */
    public static function tableOverride(array $block, array $rows): ?array
    {
        if (($block['type'] ?? null) !== 'table') {
            return null;
        }

        $declared = array_flip(array_filter(array_column((array) ($block['columns'] ?? []), 'key'), is_string(...)));

        return array_map(static fn (array $row): array => array_intersect_key($row, $declared), $rows);
    }
}
