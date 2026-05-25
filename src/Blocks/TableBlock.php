<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Config\TableConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('table', config: TableConfig::class)]
final class TableBlock implements BlockInterface
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public function __construct(
        public readonly array $headers,
        public readonly array $rows,
    ) {}

    public function render(TableConfig $config): string
    {
        $columnAlignments = $config->columnAlignments;
        $columnWidths = $config->columnWidths;

        $colgroup = '';
        if ($columnWidths !== null) {
            $colgroup = '<colgroup>'
                .implode('', array_map(
                    fn ($w): string => '<col style="width: '.e((string) $w).';">',
                    $columnWidths,
                ))
                .'</colgroup>';
        }

        $thead = '<thead><tr>';
        foreach ($this->headers as $i => $h) {
            $align = isset($columnAlignments[$i]) ? ' style="text-align: '.e($columnAlignments[$i]).';"' : '';
            $thead .= "<th{$align}>".e($h).'</th>';
        }
        $thead .= '</tr></thead>';

        $tbody = '<tbody>';
        foreach ($this->rows as $row) {
            $tbody .= '<tr>';
            foreach ($row as $i => $cell) {
                $align = isset($columnAlignments[$i]) ? ' style="text-align: '.e($columnAlignments[$i]).';"' : '';
                $tbody .= "<td{$align}>".e((string) $cell).'</td>';
            }
            $tbody .= '</tr>';
        }
        $tbody .= '</tbody>';

        return "<table>{$colgroup}{$thead}{$tbody}</table>";
    }
}
