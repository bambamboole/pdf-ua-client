<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Tests\Support;

use Imagick;

final readonly class PageComparison
{
    public function __construct(
        public int $pageIndex,
        public float $distance,
        public Imagick $diffImage,
    ) {}
}
