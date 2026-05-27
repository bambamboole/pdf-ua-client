<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Enums\AttachmentRelationship;
use Bambamboole\PdfUaClient\Http\Attachment;

final readonly class TemplateAttachmentRequirement
{
    public function __construct(
        public string $id,
        public string $name,
        public string $mimeType,
        public ?string $description = null,
        public ?AttachmentRelationship $relationship = null,
        public bool $required = true,
    ) {}

    public function toHttpAttachment(string $contentBase64): Attachment
    {
        return new Attachment(
            name: $this->name,
            contentBase64: $contentBase64,
            mimeType: $this->mimeType,
            description: $this->description,
            relationship: $this->relationship?->value,
        );
    }
}
