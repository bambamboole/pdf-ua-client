<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\CssRule;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\Align;
use Bambamboole\PdfUaClient\Enums\FontWeight;

final readonly class TypographyConfig
{
    public function __construct(
        #[Title('Font family')]
        #[Description('Registered font key used for this text.')]
        #[CssRule(key: 'font-family', value: "'{value}'")]
        public ?string $family = null,
        #[Title('Font size')]
        #[Description('Font size in points.')]
        #[CssRule(key: 'font-size', value: '{value}pt')]
        public ?int $size = null,
        #[Title('Font weight')]
        #[Description('Numeric font weight.')]
        #[CssRule(key: 'font-weight', value: '{value}')]
        public ?FontWeight $weight = null,
        #[Title('Text alignment')]
        #[Description('Text alignment for this typography scope.')]
        #[CssRule(key: 'text-align', value: '{value}')]
        public ?Align $align = null,
        #[Title('Text color')]
        #[Description('CSS color value used for text.')]
        #[CssRule(key: 'color', value: '{value}')]
        public ?string $color = null,
    ) {}
}
