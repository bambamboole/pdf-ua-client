<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\CssRule;
use Bambamboole\PdfUaClient\Enums\Align;
use Bambamboole\PdfUaClient\Enums\FontWeight;

final readonly class TypographyConfig
{
    public function __construct(
        #[CssRule(key: 'font-family', value: "'{value}'")]
        public ?string $family = null,
        #[CssRule(key: 'font-size', value: '{value}pt')]
        public ?int $size = null,
        #[CssRule(key: 'font-weight', value: '{value}')]
        public ?FontWeight $weight = null,
        #[CssRule(key: 'text-align', value: '{value}')]
        public ?Align $align = null,
        #[CssRule(key: 'color', value: '{value}')]
        public ?string $color = null,
    ) {}
}
