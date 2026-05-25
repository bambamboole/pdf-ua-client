<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Tests\Support;

final readonly class ComparisonResult
{
    /** @param list<PageComparison> $pages */
    public function __construct(
        public array $pages,
        public int $expectedPages,
        public int $actualPages,
    ) {}

    public function pageCountMatches(): bool
    {
        return $this->expectedPages === $this->actualPages;
    }

    public function worstDistance(): float
    {
        if ($this->pages === []) {
            return 0.0;
        }

        return max(array_map(fn (PageComparison $page): float => $page->distance, $this->pages));
    }

    public function matches(float $threshold): bool
    {
        return $this->pageCountMatches() && $this->worstDistance() <= $threshold;
    }
}
