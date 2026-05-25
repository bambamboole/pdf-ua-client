<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\CssRule;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Min;
use Bambamboole\PdfUaClient\Attributes\Title;

final readonly class SpacingConfig
{
    public function __construct(
        #[Title('Top')]
        #[Description('Top spacing in millimetres.')]
        #[Min(0)] #[CssRule(key: 'margin-top', value: '{value}mm')]
        public ?int $top = null,
        #[Title('Right')]
        #[Description('Right spacing in millimetres.')]
        #[Min(0)] #[CssRule(key: 'margin-right', value: '{value}mm')]
        public ?int $right = null,
        #[Title('Bottom')]
        #[Description('Bottom spacing in millimetres.')]
        #[Min(0)] #[CssRule(key: 'margin-bottom', value: '{value}mm')]
        public ?int $bottom = null,
        #[Title('Left')]
        #[Description('Left spacing in millimetres.')]
        #[Min(0)] #[CssRule(key: 'margin-left', value: '{value}mm')]
        public ?int $left = null,
    ) {}
}
