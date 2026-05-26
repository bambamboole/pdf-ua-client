<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Config\TableColumn;
use Bambamboole\PdfUaClient\Config\TableConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('table', config: TableConfig::class)]
#[Title('Table')]
final readonly class TableBlock implements BlockInterface
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        public array $rows,
    ) {}

    public function render(TableConfig $config): string
    {
        $headers = array_map(fn (TableColumn $column): string => $column->label, $config->columns);
        if ($config->numberRows) {
            array_unshift($headers, '#');
        }

        $colgroup = $this->colgroup($config);

        $thead = '<thead><tr>';
        foreach ($headers as $i => $h) {
            $align = $this->alignAttribute($config, $i);
            $thead .= "<th{$align}>".e($h).'</th>';
        }
        $thead .= '</tr></thead>';

        $tbody = '<tbody>';
        foreach ($this->renderRows($config->columns) as $rowIndex => $row) {
            $tbody .= '<tr>';
            foreach ($this->numberedRow($row, $rowIndex, $config) as $i => $cell) {
                $align = $this->alignAttribute($config, $i);
                $tbody .= "<td{$align}>".e((string) $cell).'</td>';
            }
            $tbody .= '</tr>';
        }
        $tbody .= '</tbody>';

        return "<table class=\"data-table\">{$colgroup}{$thead}{$tbody}</table>";
    }

    private function colgroup(TableConfig $config): string
    {
        $widths = array_map(
            fn (TableColumn $column): ?string => $column->width,
            $config->columns,
        );

        if ($config->numberRows) {
            array_unshift($widths, null);
        }

        if (! array_any($widths, fn (mixed $width): bool => $width !== null && $width !== '')) {
            return '';
        }

        return '<colgroup>'
            .implode('', array_map(
                fn (mixed $width): string => $width === null || $width === ''
                    ? '<col>'
                    : '<col style="width: '.e((string) $width).';">',
                $widths,
            ))
            .'</colgroup>';
    }

    private function alignAttribute(TableConfig $config, int $index): string
    {
        if ($config->numberRows) {
            if ($index === 0) {
                return ' style="text-align: right;"';
            }

            $index--;
        }

        $align = $config->columns[$index]->align ?? null;

        return $align === null ? '' : ' style="text-align: '.e($align->value).';"';
    }

    /**
     * @param  list<mixed>  $row
     * @return list<mixed>
     */
    private function numberedRow(array $row, int $index, TableConfig $config): array
    {
        if (! $config->numberRows) {
            return $row;
        }

        array_unshift($row, $index + 1);

        return $row;
    }

    /**
     * @param  list<TableColumn>  $columns
     * @return list<list<mixed>>
     */
    private function renderRows(array $columns): array
    {
        return array_map(
            fn (array $row): array => array_map(
                fn (TableColumn $column): mixed => $row[$column->key] ?? '',
                $columns,
            ),
            $this->rows,
        );
    }
}
