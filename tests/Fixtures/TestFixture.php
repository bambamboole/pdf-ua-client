<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Tests\Fixtures;

final readonly class TestFixture
{
    /**
     * @param  array<string, mixed>  $spec
     * @param  array<string, array<string, mixed>>  $data
     */
    public function __construct(
        public array $spec,
        public array $data,
        public string $html,
        public ?string $pdf = null,
    ) {}
}
