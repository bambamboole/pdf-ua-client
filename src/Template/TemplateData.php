<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

final readonly class TemplateData
{
    /**
     * @param  array<string, array<string, mixed>>  $example
     * @param  array<string, array<string, mixed>>  $defaults
     * @param  array<string, array<string, mixed>>  $constants
     */
    public function __construct(
        public array $example = [],
        public array $defaults = [],
        public array $constants = [],
    ) {}
}
