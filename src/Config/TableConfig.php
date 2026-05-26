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
     * @param  list<TableColumn>  $columns
     */
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        #[Title('Number rows')]
        #[Description('Render an auto-incrementing row number column before configured data columns.')]
        public bool $numberRows = false,
        #[Title('Columns')]
        #[Description('Fixed table columns. Runtime data uses these keys for each row object.')]
        #[ArrayOf(TableColumn::class)]
        public array $columns = [],
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
