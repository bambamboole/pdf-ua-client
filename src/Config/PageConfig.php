<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\PageFormat;

final readonly class PageConfig
{
    public function __construct(
        #[Title('Format')]
        #[Description('Physical page size used for rendering.')]
        public PageFormat $format = PageFormat::A4,
        #[Title('Locale')]
        #[Description('Document locale used for language-aware rendering.')]
        public string $locale = 'de_DE',
        #[Title('Margins')]
        #[Description('Page margins in millimetres.')]
        public SpacingConfig $margins = new SpacingConfig(20, 20, 20, 25),
        #[Title('Fold Marks')]
        #[Description('Show DIN-style fold marks in the page margin.')]
        public bool $foldMarks = false,
        #[Title('Punch Marks')]
        #[Description('Show a center punch mark in the page margin.')]
        public bool $punchMarks = false,
        #[Title('Pagination')]
        #[Description('Page number display settings.')]
        public PageNumbersConfig $pageNumbers = new PageNumbersConfig,
        #[Title('Footer')]
        #[Description('Repeated page footer rows.')]
        public PageFooterConfig $footer = new PageFooterConfig,
    ) {}
}
