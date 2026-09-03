<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

final readonly class HtmlDocument
{
    /**
     * @param  list<Attachment>  $attachments
     * @param  list<XmpSchema>  $xmpSchemas
     */
    public function __construct(
        public string $html,
        public ?string $baseUrl = null,
        public array $attachments = [],
        public array $xmpSchemas = [],
        public ?bool $embedColorProfile = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = ['html' => $this->html];

        if ($this->baseUrl !== null) {
            $payload['baseUrl'] = $this->baseUrl;
        }

        if ($this->attachments !== []) {
            $payload['attachments'] = array_map(
                static fn (Attachment $attachment): array => $attachment->toPayload(),
                $this->attachments,
            );
        }

        if ($this->xmpSchemas !== []) {
            $payload['xmpSchemas'] = array_map(
                static fn (XmpSchema $schema): array => $schema->toPayload(),
                $this->xmpSchemas,
            );
        }

        if ($this->embedColorProfile !== null) {
            $payload['embedColorProfile'] = $this->embedColorProfile;
        }

        return $payload;
    }
}
