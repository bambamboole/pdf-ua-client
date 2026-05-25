<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\TableBlock;
use Bambamboole\PdfUaClient\Config\TableConfig;

it('renders headers and rows inside a <table>', function () {
    $block = new TableBlock(
        headers: ['Description', 'Qty', 'Total'],
        rows: [
            ['Service', '1', '100,00 €'],
            ['Other', '2', '200,00 €'],
        ],
    );

    $html = (string) $block->render(new TableConfig);

    expect($html)->toContain('<table>');
    expect($html)->toContain('<thead>');
    expect($html)->toContain('<th>Description</th>');
    expect($html)->toContain('<td>Service</td>');
    expect($html)->toContain('100,00');
});

it('keeps per-cell text-align inline (per-column, not consolidatable into the id)', function () {
    $block = new TableBlock(
        headers: ['A', 'B'],
        rows: [['1', '2']],
    );

    $html = (string) $block->render(new TableConfig(columnAlignments: ['left', 'right']));

    expect($html)->toContain('text-align: right');
    expect($html)->toContain('text-align: left');
});
