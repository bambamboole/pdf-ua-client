<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\ArrayOf;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Contracts\EmitsCss;
use Bambamboole\PdfUaClient\Enums\Align;

final readonly class TableConfig extends BlockConfig implements EmitsCss
{
    /**
     * @param  list<string>|null  $columnAlignments
     * @param  list<int|string>|null  $columnWidths
     */
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        #[Title('Column alignments')]
        #[Description('Text alignment per table column, ordered from left to right.')]
        #[ArrayOf('string')]
        public ?array $columnAlignments = null,
        #[Title('Column widths')]
        #[Description('Column widths as millimetres or CSS width values, ordered from left to right.')]
        #[ArrayOf('int', 'string')]
        public ?array $columnWidths = null,
        #[Title('Style')]
        #[Description('Visual table style preset.')]
        public string $style = 'striped',
    ) {
        parent::__construct($typography, $spacing, $width, $align);
    }

    public function cssRules(string $blockId): string
    {
        return match ($this->style) {
            'striped' => ".{$blockId} tbody tr:nth-child(even) { background-color: #f9fafb; }",
            'bordered' => ".{$blockId} { border-collapse: collapse; } .{$blockId} th, .{$blockId} td { border: 1px solid #d1d5db; }",
            'minimal' => ".{$blockId} thead tr { border-bottom: 2px solid #1a1a2e; } .{$blockId} tbody tr { border-bottom: 1px solid #e5e7eb; }",
            default => '',
        };
    }
}
