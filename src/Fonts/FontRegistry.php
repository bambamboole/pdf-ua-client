<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Fonts;

final class FontRegistry
{
    /** @var array<string, FontDefinition> */
    private array $fonts = [];

    /** @param array<string, array<string, mixed>> $fonts */
    public static function fromConfig(array $fonts): self
    {
        $registry = new self;

        foreach ($fonts as $key => $font) {
            $registry->register(
                key: (string) $key,
                label: (string) ($font['label'] ?? $key),
                family: (string) ($font['family'] ?? $font['label'] ?? $key),
                url: isset($font['url']) ? (string) $font['url'] : null,
                weight: isset($font['weight']) ? (string) $font['weight'] : null,
                style: (string) ($font['style'] ?? 'normal'),
                display: (string) ($font['display'] ?? 'swap'),
                format: (string) ($font['format'] ?? 'woff2'),
            );
        }

        return $registry;
    }

    public function register(
        string $key,
        string $label,
        string $family,
        ?string $url = null,
        ?string $weight = null,
        string $style = 'normal',
        string $display = 'swap',
        string $format = 'woff2',
    ): self {
        $this->fonts[$key] = new FontDefinition(
            key: $key,
            label: $label,
            family: $family,
            url: $url,
            weight: $weight,
            style: $style,
            display: $display,
            format: $format,
        );

        return $this;
    }

    public function get(string $key): ?FontDefinition
    {
        return $this->fonts[$key] ?? null;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->fonts);
    }

    /** @return array<string, FontDefinition> */
    public function all(): array
    {
        return $this->fonts;
    }
}
