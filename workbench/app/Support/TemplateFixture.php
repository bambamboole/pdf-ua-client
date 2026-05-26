<?php

declare(strict_types=1);
namespace Workbench\App\Support;

final readonly class TemplateFixture
{
    /**
     * @param  array<string, mixed>  $template
     * @param  array<string, array<string, mixed>>  $data
     * @param  array<string, mixed>|null  $contract
     */
    public function __construct(
        public string $group,
        public string $slug,
        public string $title,
        public string $path,
        public array $template,
        public array $data,
        public ?array $contract = null,
        public ?string $html = null,
        public ?string $htmlPath = null,
        public ?string $pdfPath = null,
    ) {}

    /** @return array{title: string, template: array<string, mixed>, data: array<string, array<string, mixed>>} */
    public function toExampleEntry(): array
    {
        return [
            'title' => $this->title,
            'template' => $this->template,
            'data' => $this->data,
        ];
    }
}
