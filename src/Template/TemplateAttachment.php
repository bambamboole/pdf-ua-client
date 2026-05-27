<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Enums\AttachmentRelationship;
use Bambamboole\PdfUaClient\Http\Attachment;

final readonly class TemplateAttachment
{
    public function __construct(
        public string $name,
        public string $contentBase64,
        public string $mimeType,
        public ?string $description = null,
        public ?AttachmentRelationship $relationship = null,
    ) {}

    public function toHttpAttachment(): Attachment
    {
        return new Attachment(
            name: $this->name,
            contentBase64: $this->contentBase64,
            mimeType: $this->mimeType,
            description: $this->description,
            relationship: $this->relationship?->value,
        );
    }
}
