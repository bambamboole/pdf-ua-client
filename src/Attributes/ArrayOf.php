<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class ArrayOf
{
    /** @var non-empty-list<class-string|string> */
    public array $itemTypes;

    /** @param class-string|string $itemClass */
    public function __construct(public string $itemClass, string ...$additionalItemTypes)
    {
        $this->itemTypes = [$this->itemClass, ...$additionalItemTypes];
    }
}
