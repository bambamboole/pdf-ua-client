<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

final readonly class XmpSchema
{
    /**
     * @param  list<XmpProperty>  $properties
     */
    public function __construct(
        public string $namespace,
        public string $prefix,
        public string $name,
        public array $properties,
    ) {}

    /**
     * @return array{namespace: string, prefix: string, name: string, properties: list<array<string, string>>}
     */
    public function toPayload(): array
    {
        return [
            'namespace' => $this->namespace,
            'prefix' => $this->prefix,
            'name' => $this->name,
            'properties' => array_map(
                static fn (XmpProperty $property): array => $property->toPayload(),
                $this->properties,
            ),
        ];
    }
}
