<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

final readonly class XmpProperty
{
    public function __construct(
        public string $name,
        public string $value,
        public string $valueType = 'Text',
        public string $category = 'external',
        public ?string $description = null,
    ) {}

    /**
     * @return array{name: string, value: string, valueType: string, category: string, description?: string}
     */
    public function toPayload(): array
    {
        $payload = [
            'name' => $this->name,
            'value' => $this->value,
            'valueType' => $this->valueType,
            'category' => $this->category,
        ];

        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }

        return $payload;
    }
}
