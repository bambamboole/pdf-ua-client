<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Http;

final readonly class Attachment
{
    public function __construct(
        public string $name,
        public string $contentBase64,
        public string $mimeType,
        public ?string $description = null,
        public ?string $relationship = null,
    ) {}

    /** @return array<string, string> */
    public function toPayload(): array
    {
        $payload = [
            'name' => $this->name,
            'content' => $this->contentBase64,
            'mimeType' => $this->mimeType,
        ];

        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }

        if ($this->relationship !== null) {
            $payload['relationship'] = $this->relationship;
        }

        return $payload;
    }
}
