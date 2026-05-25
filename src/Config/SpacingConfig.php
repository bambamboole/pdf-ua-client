<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\CssRule;
use Bambamboole\PdfUaClient\Attributes\Min;

final readonly class SpacingConfig
{
    public function __construct(
        #[Min(0)] #[CssRule(key: 'margin-top', value: '{value}mm')]
        public ?int $top = null,
        #[Min(0)] #[CssRule(key: 'margin-right', value: '{value}mm')]
        public ?int $right = null,
        #[Min(0)] #[CssRule(key: 'margin-bottom', value: '{value}mm')]
        public ?int $bottom = null,
        #[Min(0)] #[CssRule(key: 'margin-left', value: '{value}mm')]
        public ?int $left = null,
    ) {}
}
