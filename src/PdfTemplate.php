<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

use InvalidArgumentException;

final readonly class PdfTemplate
{
    /**
     * @param  array<string, mixed>  $template
     * @param  array<string, mixed>  $data
     * @param  list<Attachment>  $attachments
     * @param  list<XmpSchema>  $xmpSchemas
     */
    public function __construct(
        public array $template,
        public array $data = [],
        public array $attachments = [],
        public array $xmpSchemas = [],
    ) {
        if (($template['version'] ?? null) !== 2) {
            throw new InvalidArgumentException('Only template version 2 is supported.');
        }
    }

    /**
     * @return array{template: array<string, mixed>, data?: array<string, mixed>}
     */
    public function toPayload(): array
    {
        $template = $this->template;

        if ($this->attachments !== []) {
            $template['attachments'] = array_map(
                static fn (Attachment $attachment): array => $attachment->toPayload(),
                $this->attachments,
            );
        }

        if ($this->xmpSchemas !== []) {
            $template['xmpSchemas'] = array_map(
                static fn (XmpSchema $schema): array => $schema->toPayload(),
                $this->xmpSchemas,
            );
        }

        $payload = ['template' => $template];

        if ($this->data !== []) {
            $payload['data'] = $this->data;
        }

        return $payload;
    }
}
