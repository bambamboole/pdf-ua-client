<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

use Bambamboole\PdfUaClient\Enums\AttachmentRelationship;

final readonly class Attachment
{
    public function __construct(
        public string $name,
        public string $content,
        public string $mimeType = 'application/octet-stream',
        public AttachmentRelationship $relationship = AttachmentRelationship::Alternative,
        public ?string $description = null,
    ) {}

    public static function facturX(string $xml, string $name = 'factur-x.xml'): self
    {
        return new self(
            name: $name,
            content: $xml,
            mimeType: 'text/xml',
            relationship: AttachmentRelationship::Alternative,
            description: 'Factur-X/ZUGFeRD XML invoice',
        );
    }

    /**
     * @return array{name: string, content: string, mimeType: string, relationship: string, description?: string}
     */
    public function toPayload(): array
    {
        $payload = [
            'name' => $this->name,
            'content' => base64_encode($this->content),
            'mimeType' => $this->mimeType,
            'relationship' => $this->relationship->value,
        ];

        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }

        return $payload;
    }
}
